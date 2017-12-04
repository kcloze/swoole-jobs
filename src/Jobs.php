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
    const MAX_REQUEST = 1000; // 每个子进程while循环里面最多循坏次数，防止内存泄漏

    public $logger  = null;
    public $queue   = null;
    public $usleep  = 10;
    public $config  = [];

    public function __construct(BaseTopicQueue $queue)
    {
        $this->config  = Config::getConfig(); //读取配置文件
        $this->queue   = $queue;
        $this->usleep  = $this->config['usleep'] ?? $this->usleep;
        $this->logger  = Logs::getLogger($this->config['logPath'] ?? []);
    }

    public function run($topic='')
    {
        //循环次数计数
        $req = 0;
        while (true) {
            if ($topic) {
                //每次最多取MAX_POP个任务执行
                for ($i = 0; $i < self::MAX_POP; $i++) {
                    $data = $this->queue->pop($topic);
                    $this->logger->log(print_r($data, true), 'info');
                    if (!empty($data)) {
                        // 根据自己的业务需求改写此方法
                        $jobObject   =  $this->loadObject($data);
                        $baseAction  =  $this->loadFrameworkAction();
                        $baseAction->start($jobObject);
                    } else {
                        $this->logger->log($topic . ' no work to do!', 'info');
                        break;
                    }
                }
            } else {
                $this->logger->log('All topic no work to do!', 'info');
            }
            $this->logger->log('usleep ' . $this->usleep . ' mirc second!', 'info');
            $this->logger->flush();
            usleep($this->usleep);
            $req++;
            //达到最大循环次数，退出循环，防止内存泄漏
            if ($req >= self::MAX_REQUEST) {
                $this->logger->log('达到最大循环次数，让子进程退出，主进程会再次拉起子进程');
                break;
            }
        }
    }

    //根据配置装入不同的框架
    private function loadFrameworkAction()
    {
        if (isset($this->config['framework']['type']) && $this->config['framework']['type'] == 'yii') {
            //Yii框架命令行任务
            $classFramework=$this->config['framework']['class'] ?? '\Kcloze\Jobs\Action\YiiAction';
        } else {
            //swoole-jobs自带jobs
            $classFramework=$this->config['framework']['class'] ?? '\Kcloze\Jobs\Action\SwooleJobsAction';
        }
        try {
            $action = new $classFramework();
        } catch (\Exception $e) {
            echo $e->getMessage();
        }

        return $action;
    }

    //实例化job对象
    private function loadObject($data)
    {
        return new JobObject($data['topic'], $data['jobClass'], $data['jobMethod'], $data['jobParams']);
    }
}
