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
    public $logger              = null;
    public $queue               = null;
    public $sleep               = 2; //单个topic如果没有任务，该进程暂停秒数，不能低于1秒，数值太小无用进程会频繁拉起
    public $config              = [];

    public $popNum              = 0;   // 用来记录job执行次数,操过次数退出循环
    public $maxPopNum           = 500; // 子进程启动后每个循环最多取多少个job，该参数已经删除
    private $pidInfoFile        = ''; // 主进程pid信息文件路径

    public function __construct($pidInfoFile)
    {
        $this->config      = Config::getConfig(); //读取配置文件
        $this->pidInfoFile = $pidInfoFile;
        $this->sleep       = $this->config['sleep'] ?? $this->sleep;
        $this->maxPopNum   = $this->config['maxPopNum'] ?? $this->maxPopNum;
        $this->logger      = Logs::getLogger($this->config['logPath'] ?? '', $this->config['logSaveFileApp'] ?? '', $this->config['system'] ?? '');
    }

    public function run($topic='')
    {
        if ($topic) {
            $this->queue = Queue::getQueue($this->config['job']['queue'], $this->logger);
            if (empty($this->queue)) {
                sleep($this->sleep);

                return;
            }
            $this->queue->setTopics($this->config['job']['topics'] ?? []);

            $len = $this->queue->len($topic);
            //$this->logger->log($topic . ' pop len: ' . $len, 'info');
            if ($len > 0) {
                //循环拿出队列消息
                //每次最多取maxPopNum个任务执行
                do {
                    ++$this->popNum;

                    //主进程状态不是running状态，退出循环
                    if (Process::STATUS_RUNNING != $this->getMasterData('status')) {
                        break;
                    }

                    $this->queue && $data = $this->queue->pop($topic);
                    if (empty($data)) {
                        //暂停1毫秒
                        usleep(1000);
                        continue;
                    }
                    $this->logger->log('pop data: ' . json_encode($data), 'info');
                    if (!empty($data) && (is_object($data) || is_array($data))) {
                        $beginTime=microtime(true);
                        // 根据自己的业务需求改写此方法
                        $jobObject               =  $this->loadObject($data);
                        $baseAction              =  $this->loadFrameworkAction();
                        $baseAction->start($jobObject);
                        $endTime=microtime(true);
                        $this->logger->log('pid: ' . getmypid() . ', job id: ' . $jobObject->uuid . ' done, spend time: ' . ($endTime - $beginTime), 'info');
                        unset($jobObject, $baseAction);
                    } else {
                        $this->logger->log('pop error data: ' . print_r($data, true), 'error');
                    }
                    //防止内存泄漏，每次执行一个job就退出[极端情况才需要开启]
                    if (isset($this->config['eachJobExit']) && true == $this->config['eachJobExit']) {
                        exit('Each Job Exit' . PHP_EOL);
                    }
                    // if ($this->queue->len($topic) <= 0) {
                    //     break;
                    // }
                } while ($this->popNum <= $this->maxPopNum);
            } else {
                sleep($this->sleep);
            }
            //$this->queue->close();
        } else {
            $this->logger->log('All topic no work to do!', 'info');
        }
    }

    //根据配置装入不同的框架
    private function loadFrameworkAction()
    {
        $classFramework=$this->config['framework']['class'] ?? '\Kcloze\Jobs\Action\SwooleJobsAction';
        try {
            $action = new $classFramework();
        } catch (\Throwable $e) {
            Utils::catchError($this->logger, $e);
        } catch (\Exception $e) {
            Utils::catchError($this->logger, $e);
        }

        return $action;
    }

    //实例化job对象
    private function loadObject($data)
    {
        if (is_object($data)) {
            return new JobObject($data->topic ?? '', $data->jobClass ?? '', $data->jobMethod ?? '', $data->jobParams ?? [], $data->jobExtras ?? [], $data->uuid ?? '');
        } elseif (is_array($data)) {
            return new JobObject($data['topic'] ?? '', $data['jobClass'] ?? '', $data['jobMethod'] ?? '', $data['jobParams'] ?? [], $data['jobExtras'] ?? [], $data['uuid'] ?? '');
        }

        return fasle;
    }

    private function getMasterData($key='')
    {
        $data=unserialize(file_get_contents($this->pidInfoFile));
        if ($key) {
            return $data[$key] ?? null;
        }

        return $data;
    }
}
