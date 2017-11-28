<?php

/*
 * This file is part of PHP CS Fixer.
 * (c) kcloze <pei.greet@qq.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Kcloze\Jobs;

use Kcloze\Jobs\Queue\BaseTopicQueue;

class Jobs
{
    const MAX_POP     = 100; // 单个topic每次最多取多少次
    const MAX_REQUEST = 10000; // 每个子进程while循环里面最多循坏次数，防止内存泄漏

    public $logger = null;
    public $queue  = null;
    public $config = [];

    public function __construct(BaseTopicQueue $queue, Logs $log, $config = [])
    {
        $this->config = $config; // 配置可能之后还会用到

        $this->queue = $queue;
        $this->queue->setTopics($config['topics'] ?? []);

        $this->logger = $log;
    }

    public function run()
    {
        //循环次数计数
        $req = 0;
        while (true) {
            $topics = $this->queue->getTopics();
            $this->logger->log('topics: ' . json_encode($topics));

            if ($topics) {
                //遍历topic任务列表
                foreach ($topics as $key => $topic) {
                    //每次最多取MAX_POP个任务执行
                    for ($i = 0; $i < self::MAX_POP; $i++) {
                        $data = $this->queue->pop($topic);
                        $this->logger->log(print_r($data, true), 'info');
                        if (!empty($data)) {
                            // 根据自己的业务需求改写此方法
                            $this->load($data);
                        } else {
                            $this->logger->log($topic . ' no work to do!', 'info');
                            break;
                        }
                    }
                }
            } else {
                $this->logger->log('All no work to do!', 'info');
            }
            $this->logger->log('sleep 1 second!', 'info');
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

    // 重写这个方法实现业务逻辑
    private function load($data)
    {
        // 下面三种方式
    }

    // 方式一: 载入自己的框架, 这里使用 yii2 为例
    private function loadYii2Console($data)
    {
        //jobAction不要带上Action结尾
//        require_once $this->config['rootPath'] . '/vendor/yiisoft/yii2/Yii.php'; // 推荐在 composer.json 中管理依赖
        $application = new \yii\console\Application($this->config['config']);
        $route       = strtolower($data['job_class']) . '/' . $data['job_method'];
        $params      = [$data];
        $exitCode    = 0;
        //var_dump("yii2 route: ", $route, $params);
        try {

            // $route  = 'hello/index';
            // $params = [['a' => ['sdfsdf']], ['b' => ['sdfsdf', 'sdfsdf']]];
            // $exitCode = $application->runAction($route, $params);
            $exitCode = $application->runAction($route, $params);
        } catch (\Exception $e) {
            $this->logger->log($e->getMessage(), 'error');
        }
        unset($application);

        return $exitCode;
    }

    // 方式二: 使用类静态方法, 推荐, 静态方法性能更好, 依赖更好管理; 本项目提供的示例
    private function loadTest1($data)
    {
        $jobMethod = $data['job_method'];
        try {
            $exitCode = $data['job_class']::$jobMethod(...$data['job_param']); // ... 语法实现多参数输入
        } catch (\Exception $e) {
            $this->logger->log($e->getMessage(), 'error');
        }
    }

    // 方式三: 使用类方法, 本项目提供的示例
    private function loadTest2($data)
    {
        try {
            $job       = new $data['job_class']();
            $jobMethod = $data['job_method'];
            $exitCode  = $job->$jobMethod(...$data['job_param']);
        } catch (\Exception $e) {
            $this->logger->log($e->getMessage(), 'error');
        }
    }
}
