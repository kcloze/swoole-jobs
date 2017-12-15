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
    const MAX_POP          = 100; // 单个topic进程启动后每次最多取多少次，超过之后自然推出，防内存泄漏
    const SLEEP_TIME       = 5; // 单个topic如果没有任务，该进程暂停秒数，不能低于1秒，数值太小无用进程会频繁拉起

    public $logger  = null;
    public $queue   = null;
    public $sleep   = 10;
    public $config  = [];

    public function __construct()
    {
        $this->config  = Config::getConfig(); //读取配置文件
        $this->sleep   = self::SLEEP_TIME;
        $this->logger  = Logs::getLogger($this->config['logPath'] ?? []);
    }

    public function run($topic='')
    {
        if ($topic) {
            $this->queue = Queue::getQueue($this->config['job']['queue']);
            if (empty($this->queue)) {
                sleep($this->sleep);

                return;
            }
            $this->queue->setTopics($this->config['job']['topics'] ?? []);

            $len = $this->queue->len($topic);
            $this->logger->log($topic . ' pop len: ' . $len, 'info');
            if ($len > 0) {
                //每次最多取MAX_POP个任务执行
                for ($i = 0; $i < self::MAX_POP; $i++) {
                    $data = $this->queue->pop($topic);
                    $this->logger->log('pop data: ' . print_r($data, true), 'info');
                    if (!empty($data) && is_object($data)) {
                        // 根据自己的业务需求改写此方法
                        $jobObject               =  $this->loadObject($data);
                        $baseAction              =  $this->loadFrameworkAction();
                        $baseAction->start($jobObject);
                    } else {
                        $this->logger->log('pop error data: ' . print_r($data, true), 'error');
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
            $this->queue->close();
        } else {
            $this->logger->log('All topic no work to do!', 'info');
        }
    }

    //根据配置装入不同的框架
    private function loadFrameworkAction()
    {
        $type = $this->config['framework']['type'] ?? 'swoole-jobs';
        switch ($type) {
            case 'yii':
                $classFramework=$this->config['framework']['class'] ?? '\Kcloze\Jobs\Action\YiiAction';
                break;
            case 'phalcon':
                $classFramework=$this->config['framework']['class'] ?? '\Kcloze\Jobs\Action\PhalconAction';
                break;

            default:
                $classFramework=$this->config['framework']['class'] ?? '\Kcloze\Jobs\Action\SwooleJobsAction';

                break;
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
        if (is_object($data)) {
            return $data;
        }

        return fasle;
    }
}
