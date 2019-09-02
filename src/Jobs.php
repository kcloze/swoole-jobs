<?php

/*
 * This file is part of Swoole-jobs
 * (c) kcloze <pei.greet@qq.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Kcloze\Jobs;

use Kcloze\Jobs\Queue\Queue;

class Jobs
{
    const MinTimeJob            =0.0001; //job最少执行时间，少于这个时间不正常，用于worker安全进程退出依据
    const MaxTimeJob            =15; //job最大执行时间，大于这个时间不正常，用于worker安全进程退出依据

    public $logger              = null;
    public $queue               = null;
    public $sleep               = 2; //单个topic如果没有任务，该进程暂停秒数，不能低于1秒，数值太小无用进程会频繁拉起
    public $config              = [];

    public $popNum              = 0;   // 用来记录job执行次数,操过次数退出循环
    public $maxPopNum           = 500; // 子进程启动后每个循环最多取多少个job，该参数已经删除
    private $pidInfoFile        = ''; // 主进程pid信息文件路径

    public function __construct($pidInfoFile)
    {
        $this->config                     = Config::getConfig(); //读取配置文件
        $this->pidInfoFile                = $pidInfoFile;
        $this->sleep                      = $this->config['sleep'] ?? $this->sleep;
        $this->maxPopNum                  = $this->config['maxPopNum'] ?? $this->maxPopNum;
        $this->logger                     = Logs::getLogger($this->config['logPath'] ?? '', $this->config['logSaveFileApp'] ?? '', $this->config['system'] ?? '');
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
                /*
                * 循环拿出队列消息
                * 每次最多取maxPopNum个任务执行
                */
                $slpTimes=0; //空消息达到一定次数，说明队列确实没有消息
                do {
                    ++$this->popNum;

                    //主进程状态不是running状态，退出循环
                    if (Process::STATUS_RUNNING != $this->getMasterData('status')) {
                        break;
                    }

                    $this->queue && $data = $this->queue->pop($topic);

                    if (empty($data)) {
                        ++$slpTimes;
                        if ($slpTimes > 10) {
                            //空消息达到一定次数，说明队列确实没有消息
                            break;
                        }
                        //暂停1毫秒
                        usleep(1000);
                        continue;
                    }

                    $this->logger->log('pop data: ' . json_encode($data), 'info');
                    $autoAckBeforeJobStart=Config::getTopicConfig($this->config['job']['topics'], $topic, 'autoAckBeforeJobStart') ?? true;
                    if (true === $autoAckBeforeJobStart) {
                        $this->queue->ack();
                    }
                    if (!empty($data) && (\is_object($data) || \is_array($data))) {
                        $beginTime=microtime(true);
                        // 根据自己的业务需求改写此方法
                        $jobObject               =  $this->loadObject($data);
                        if ($jobObject instanceof JobObject) {
                            $jobObject = $this->formatJobObjectByTopicConfig($jobObject, $topic, $data);
                        }
                        $baseAction              =  $this->loadFrameworkAction();
                        $baseAction->start($jobObject);
                        $endTime =microtime(true);
                        $execTime=$endTime - $beginTime;
                        $this->logger->log('pid: ' . getmypid() . ', job id: ' . $jobObject->uuid . ' done, spend time: ' . $execTime, 'info');
                        //确认消息安全消费完成
                        if (true !== $autoAckBeforeJobStart) {
                            $this->queue->ack();
                        }
                        //黑科技：实践中发现有可能进不到业务代码，造成消息丢失,job执行太快或者太慢(业务出现异常)，worker进程都安全退出
                        $minTimeJob=$this->config['job']['profile']['minTime'] ?? self::MinTimeJob;
                        $maxTimeJob=$this->config['job']['profile']['maxTime'] ?? self::MaxTimeJob;
                        if ($execTime < $minTimeJob || $execTime > $maxTimeJob) {
                            //$this->queue->push($topic, $jobObject);
                            $msgJobError=($execTime < $minTimeJob) ? 'too fast' : 'too slow';
                            $this->logger->log('job execute ' . $msgJobError . ',  uuid: ' . $jobObject->uuid . ', execTime:' . $execTime, 'error', 'error');
                            //进程安全退出
                            exit;
                        }
                    } else {
                        $this->logger->log('pop error data: ' . print_r($data, true), 'error', 'error');
                    }
                    //防止内存泄漏，每次执行一个job就退出[极端情况才需要开启]
                    if (isset($this->config['eachJobExit']) && true == $this->config['eachJobExit']) {
                        $this->logger->log('Each Job Exit, job id: ' . $jobObject->uuid . PHP_EOL);
                        exit;
                    }
                    // if ($this->queue->len($topic) <= 0) {
                    //     break;
                    // }
                    unset($jobObject, $baseAction);
                } while ($this->popNum <= $this->maxPopNum);
            } else {
                sleep($this->sleep);
            }
            // $this->queue->close();
            // Queue::$_instance=null;
        } else {
            $this->logger->log('All topic no work to do!', 'info');
        }
    }

    /**
     * 获取topic配置对象，格式化JobObject.
     *
     * @param JobObject $jobObject
     * @param string    $topic
     * @param mixed     $data
     *
     * @return JobObject
     */
    public function formatJobObjectByTopicConfig(JobObject $jobObject, $topic, $data)
    {
        $topicConfigObject = new TopicConfigObject();
        if ('' == $topic) {
            return $jobObject;
        }
        if ('' === $jobObject->topic) {
            $jobObject->topic = $topic;
        }
        //如果消息体对象的callback class或method为空，则尝试读取配置的默认class和method
        if ('' == $jobObject->jobClass || '' == $jobObject->jobMethod) {
            $topicConfig = $this->getConfigByTopic($topic);
            if ($topicConfig != []) {
                $topicConfigObject->initAttributes($topicConfig);
                if ('' == $jobObject->jobClass) {
                    $jobObject->jobClass = $topicConfigObject->getDefaultJobClass();
                }
                if ('' == $jobObject->jobMethod) {
                    $jobObject->jobMethod = $topicConfigObject->getDefaultJobMethod();
                }
                if ($jobObject->jobParams == []) {
                    $jobObject->jobParams = $data;
                }
            }
        }

        return $jobObject;
    }

    /**
     * 获取对应topic的配置数组.
     *
     * @param string $topic
     *
     * @return array
     */
    public function getConfigByTopic($topic)
    {
        $topicsConfig = $this->config['job']['topics'] ?? [];
        $topicConfig  = array_filter($topicsConfig, function ($config) use ($topic) {
            return $config['name'] == $topic;
        });

        return $topicConfig != [] ? reset($topicConfig) : [];
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
        if (\is_object($data)) {
            return new JobObject($data->topic ?? '', $data->jobClass ?? '', $data->jobMethod ?? '', $data->jobParams ?? [], $data->jobExtras ?? [], $data->uuid ?? '');
        } elseif (\is_array($data)) {
            return new JobObject($data['topic'] ?? '', $data['jobClass'] ?? '', $data['jobMethod'] ?? '', $data['jobParams'] ?? [], $data['jobExtras'] ?? [], $data['uuid'] ?? '');
        }

        return false;
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
