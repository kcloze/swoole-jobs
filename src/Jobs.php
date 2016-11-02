<?php

namespace Kcloze\Jobs;

use Kcloze\Jobs\Logs;
use Kcloze\Jobs\Rabbitmq;
use Kcloze\Jobs\Redis;

class Jobs
{
    const MAX_POP     = 100; //单个topic每次最多取多少次
    const MAX_REQUEST = 10000; //每个子进程while循环里面最多循坏次数，防止内存泄漏

    public $logger = null;
    public $queue  = null;

    public function __construct($config)
    {
        $this->config = $config;
        $this->logger = new Logs($config['logPath']);
        $this->getQueue($config['queue']);
        $this->queue && $this->queue->addTopics($config['topics']);
    }
    public function run()
    {
        //循环次数计数
        $req = 0;
        while (true) {
            $topics = $this->queue->getTopics();
            if ($topics) {
                //遍历topic任务列表
                foreach ($topics as $key => $jobName) {
                    //每次最多取MAX_POP个任务执行
                    for ($i = 0; $i < self::MAX_POP; $i++) {
                        $data = $this->queue->pop($jobName);
                        $this->logger->log(print_r($data, true), 'info');
                        if (!empty($data) && isset($data['jobAction'])) {
                            //$this->logger->log(print_r([$jobName, $jobAction], true), 'info');
                            $jobAction = $data['jobAction'];
                            //注意如果嵌入自己的框架，可以修改这个路径
                            //根据jobName，jobAction执行业务代码
                            $this->loadFramework($jobName, $jobAction, $data);
                        } else {
                            $this->logger->log($jobName . " no work to do!", 'info');
                            break;
                        }
                    }
                }
            } else {
                $this->logger->log("All no work to do!", 'info');
            }
            $this->logger->log("sleep 1 second!", 'info');
            $this->logger->flush();
            sleep(1);
            $req++;
            //达到最大循环次数，退出循环，防止内存泄漏
            if ($req >= self::MAX_REQUEST) {
                echo "达到最大循环次数，让子进程退出，主进程会再次拉起子进程\n";
                break;
            }
        }
    }

    public function getQueue($config)
    {
        if (isset($config['type']) && $config['type'] == 'redis') {
            $this->queue = new Redis($config);
        } elseif (isset($config['type']) && $config['type'] == 'rabbitmq') {
            $this->queue = new Rabbitmq($config);
        } else {
            echo "you must add queue config\n";
        }

        return $this->queue;
    }
    //可以在这里载入自己的框架代码
    private function loadFramework($jobName, $jobAction, $data)
    {
        if (isset($this->config['framework']) && $this->config['framework'] == 'yii2') {
            $this->loadYii2Console($jobName, $jobAction, $data);
        } else {
            $this->loadTest($jobName, $jobAction, $data);
        }
    }
    //载入本项目test实例
    private function loadTest($jobName, $jobAction, $data)
    {
        $jobName = "Kcloze\MyJob\\" . ucfirst($jobName);
        if (method_exists($jobName, $jobAction)) {
            try {
                $job = new $jobName();
                $job->$jobAction($data);
                $this->logger->log("uuid: " . $data['uuid'] . " one job has been done!", 'trace', 'jobs');
            } catch (Exception $e) {
                $this->logger->log($e->getMessage(), 'error');
            }
        } else {
            $this->logger->log($jobAction . " action not find!", 'warning');
        }
    }

    private function loadYii2Console($jobName, $jobAction, $data)
    {
        require $this->config['rootPath'] . '/vendor/yiisoft/yii2/Yii.php';
        $application = new yii\console\Application($this->config['config']);
        $route       = $jobName . '/' . $jobAction;
        $params      = $data;
        try {
            $exitCode = $application->runAction($route, $params);
        } catch (Exception $e) {
            $this->logger->log($e->getMessage(), 'error');
        }
    }
}
