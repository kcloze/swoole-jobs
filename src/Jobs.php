<?php

/*
 * This file is part of PHP CS Fixer.
 * (c) kcloze <pei.greet@qq.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Kcloze\Jobs;

use Kcloze\Jobs\Queue\Queue;

class Jobs
{
    const MAX_POP          = 100; // 单个topic每次最多取多少次
    const SLEEP_TIME       = 5; // 单个topic如果没有任务，该进程暂停秒数，不能低于1秒，数值太小无用进程会频繁拉起

    public $logger  = null;
    public $queue   = null;
    public $sleep   = 10;
    public $config  = [];

    public function __construct()
    {
        $this->config  = Config::getConfig(); //读取配置文件
        $this->queue   = Queue::getQueue($this->config['job']['queue']);
        $this->queue->setTopics($this->config['job']['topics'] ?? []);
        $this->sleep   = self::SLEEP_TIME;
        $this->logger  = Logs::getLogger($this->config['logPath'] ?? []);
    }

    public function run($topic='')
    {
        if ($topic) {
            //每次最多取MAX_POP个任务执行

            $len = $this->queue->len($topic);
            $this->logger->log($topic . ' pop len: ' . $len, 'info');
            if ($len > 0) {
                for ($i = 0; $i < self::MAX_POP; $i++) {
                    $data = $this->queue->pop($topic);
                    $this->logger->log('pop data: ' . print_r($data, true), 'info');
                    if (!empty($data)) {
                        // 根据自己的业务需求改写此方法
                        $jobObject               =  $this->loadObject($data);
                        $baseAction              =  $this->loadFrameworkAction();
                        $baseAction->start($jobObject);
                    }
                    if ($this->queue->len($topic) <= 0) {
                        break;
                    }
                }
            } else {
                $this->logger->log($topic . ' no work to do!', 'info');
                sleep($this->sleep);
                $this->logger->log('sleep ' . $this->sleep . ' second!', 'info');
            }
        } else {
            $this->logger->log('All topic no work to do!', 'info');
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
