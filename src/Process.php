<?php

/*
 * This file is part of PHP CS Fixer.
 * (c) kcloze <pei.greet@qq.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Kcloze\Jobs;

use Kcloze\Jobs\Queue\Queue;

class Process
{
    const CHILD_PROCESS_CAN_RESTART                   ='staticWorker'; //子进程可以重启,进程个数固定
    const CHILD_PROCESS_CAN_NOT_RESTART               ='dynamicWorker'; //子进程不可以重启，进程个数根据队列堵塞情况动态分配
    const STATUS_RUNNING                              ='runnning'; //主进程running状态
    const STATUS_WAIT                                 ='wait'; //主进程wait状态
    const STATUS_STOP                                 ='stop'; //主进程stop状态
    const APP_NAME                                    ='swoole-jobs'; //app name
    const STATUS_HSET_KEY_HASH                        ='status'; //status hash名

    public $processName      = ':swooleProcessTopicQueueJob'; // 进程重命名, 方便 shell 脚本管理
    public $jobs             = null;

    public $workers                       =[];

    private $queueMaxNum                  =10; //队列达到一定长度，增加子进程个数
    private $queueTickTimer               =2000; //一定时间间隔（毫秒）检查队列长度
    private $workerNum                    =0; //固定分配的子进程个数
    private $dynamicWorkerNum             =[]; //动态（不能重启）子进程计数，最大数为每个topic配置workerMaxNum，它的个数是动态变化的
    private $workersInfo                  =[];
    private $ppid;
    private $config                       = [];
    private $pidFile                      = 'master.pid'; //pid存放文件
    private $pidInfoFile                  = 'master.info'; //pid 序列化信息
    private $status                       = '';
    private $logger                       = null;
    private $queue                        = null;
    private $topics                       = null;
    private $logSaveFileWorker            = 'workers.log';

    public function __construct()
    {
        $this->config  =  Config::getConfig();
        $this->logger  = new Logs($this->config['logPath'] ?? '', $this->config['logSaveFileApp'] ?? '');
        $this->topics  =$this->config['job']['topics'] ?? [];

        //该变量需要在多进程共享
        $this->status=self::STATUS_RUNNING;

        if (isset($this->config['pidPath']) && !empty($this->config['pidPath'])) {
            Utils::mkdir($this->config['pidPath']);
            $this->pidFile    =$this->config['pidPath'] . '/' . $this->pidFile;
            $this->pidInfoFile=$this->config['pidPath'] . '/' . $this->pidInfoFile;
        } else {
            die('config pidPath must be set!' . PHP_EOL);
        }
        if (isset($this->config['processName']) && !empty($this->config['processName'])) {
            $this->processName = $this->config['processName'];
        }
        if (isset($this->config['logSaveFileWorker']) && !empty($this->config['logSaveFileWorker'])) {
            $this->logSaveFileWorker = $this->config['logSaveFileWorker'];
        }
        /*
         * master.pid 文件记录 master 进程 pid, 方便之后进程管理
         * 请管理好此文件位置, 使用 systemd 管理进程时会用到此文件
         * 判断文件是否存在，并判断进程是否在运行
         */
        if (file_exists($this->pidFile)) {
            $pid=$this->getMasterData('pid');
            if ($pid && @\Swoole\Process::kill($pid, 0)) {
                die('已有进程运行中,请先结束或重启' . PHP_EOL);
            }
        }

        \Swoole\Process::daemon();
        $this->ppid    = getmypid();
        $data['pid']   =$this->ppid;
        $data['status']=$this->status;
        $this->saveMasterData($data);
        $this->setProcessName(self::APP_NAME . ' master ' . $this->ppid . $this->processName);
    }

    public function start()
    {
        $topics = $this->topics;
        $this->logger->log('topics: ' . json_encode($topics));

        if ($topics) {
            //遍历topic任务列表
            foreach ((array) $topics as  $topic) {
                if (isset($topic['workerMinNum']) && isset($topic['name'])) {
                    //每个topic开启最少个进程消费队列
                    for ($i = 0; $i < $topic['workerMinNum']; $i++) {
                        $this->reserveQueue($i, $topic['name'], \Kcloze\Jobs\Process::CHILD_PROCESS_CAN_RESTART);
                    }
                }
            }
        }

        $this->registSignal();
        $this->registTimer();
    }

    /**
     * fork子进程消费队列.
     *
     * @param [type] $num   子进程编号
     * @param [type] $topic topic名称
     * @param string $type  是否会重启 canRestart|unRestart
     */
    public function reserveQueue($num, $topic, $type=self::CHILD_PROCESS_CAN_RESTART)
    {
        $reserveProcess = new \Swoole\Process(function ($worker) use ($num, $topic, $type) {
            $this->checkMpid($worker);
            $beginTime=microtime(true);
            try {
                //设置进程名字
                $this->setProcessName($type . ' ' . $topic . ' job ' . $num . ' ' . $this->processName);
                $jobs  = new Jobs();
                $jobs->run($topic);
            } catch (\Throwable $e) {
                Utils::catchError($this->logger, $e);
            } catch (\Exception $e) {
                Utils::catchError($this->logger, $e);
            }

            $endTime=microtime(true);
            $this->logger->log($topic . ' worker id: ' . $num . ' is done!!! Timing: ' . ($endTime - $beginTime), 'info', $this->logSaveFileWorker);
        });
        $pid                                        = $reserveProcess->start();
        $this->workers[$pid]                        = $reserveProcess;
        $this->workersInfo[$pid]['type']            = $type;
        $this->workersInfo[$pid]['topic']           = $topic;
        $this->logger->log('topic: ' . $topic . ' ' . $type . ' worker id: ' . $num . ' pid: ' . $pid . ' is start...', 'info', $this->logSaveFileWorker);
        $this->workerNum++;
    }

    //注册信号
    public function registSignal()
    {
        \Swoole\Process::signal(SIGTERM, function ($signo) {
            $this->killWorkersAndExitMaster();
        });
        \Swoole\Process::signal(SIGKILL, function ($signo) {
            $this->killWorkersAndExitMaster();
        });
        \Swoole\Process::signal(SIGUSR1, function ($signo) {
            $this->waitWorkers();
        });

        \Swoole\Process::signal(SIGCHLD, function ($signo) {
            while (true) {
                try {
                    $ret = \Swoole\Process::wait(false);
                } catch (\Exception $e) {
                    $this->logger->log('signoError: ' . $signo . $e->getMessage(), 'error', $this->logSaveFileWorker);
                }
                if ($ret) {
                    $pid           = $ret['pid'];
                    $childProcess = $this->workers[$pid];
                    $topic = $this->workersInfo[$pid]['topic'] ?? '';
                    $this->status=$this->getMasterData('status');
                    $topicCanNotRestartNum =  $this->dynamicWorkerNum[$topic] ?? 'null';
                    $this->logger->log(Process::CHILD_PROCESS_CAN_RESTART . '---' . $topic . '***' . $topicCanNotRestartNum . '***' . $this->status . '***' . $this->workersInfo[$pid]['type'] . '***' . $pid, 'info', $this->logSaveFileWorker);
                    $this->logger->log($pid . ',' . $this->status . ',' . Process::STATUS_RUNNING . ',' . $this->workersInfo[$pid]['type'] . ',' . Process::CHILD_PROCESS_CAN_RESTART, 'info', $this->logSaveFileWorker);

                    //主进程状态为running并且该子进程是可以重启的
                    if ($this->status == Process::STATUS_RUNNING && $this->workersInfo[$pid]['type'] == Process::CHILD_PROCESS_CAN_RESTART) {
                        try {
                            //子进程重启可能失败，必须启动成功之后，再往下执行
                            while (true) {
                                $newPid = $childProcess->start();
                                if ($newPid > 0) {
                                    break;
                                }
                            }
                            $this->workers[$newPid] = $childProcess;
                            $this->workersInfo[$newPid]['type'] = Process::CHILD_PROCESS_CAN_RESTART;
                            $this->workersInfo[$newPid]['topic'] = $topic;
                            $this->workerNum++;
                            $this->logger->log("Worker Restart, kill_signal={$ret['signal']} PID=" . $newPid, 'info', $this->logSaveFileWorker);
                        } catch (\Throwable $e) {
                            $this->logger->log('restartErrorThrow' . $e->getMessage(), 'error', $this->logSaveFileWorker);
                        } catch (\Exception $e) {
                            $this->logger->log('restartError: ' . $e->getMessage(), 'error', $this->logSaveFileWorker);
                        }
                    }
                    //某个topic动态变化的子进程，退出之后个数减少一个
                    if ($this->workersInfo[$pid]['type'] == Process::CHILD_PROCESS_CAN_NOT_RESTART) {
                        $this->dynamicWorkerNum[$topic]--;
                    }
                    $this->logger->log("Worker Exit, kill_signal={$ret['signal']} PID=" . $pid, 'info', $this->logSaveFileWorker);
                    unset($this->workers[$pid], $this->workersInfo[$pid]);
                    $this->workerNum--;

                    $this->logger->log('Worker count: ' . count($this->workers) . '==' . $this->workerNum, 'info', $this->logSaveFileWorker);
                    //如果$this->workers为空，且主进程状态为wait，说明所有子进程安全退出，这个时候主进程退出
                    if (empty($this->workers) && $this->status == Process::STATUS_WAIT) {
                        $this->logger->log('主进程收到所有信号子进程的退出信号，子进程安全退出完成', 'info', $this->logSaveFileWorker);
                        $this->exitMaster();
                    }
                } else {
                    break;
                }
            }
        });
    }

    //增加定时器，检查队列积压情况；
    public function registTimer()
    {
        \Swoole\Timer::tick($this->queueTickTimer, function ($timerId) {
            $topics = $this->topics;
            $this->status  =$this->getMasterData('status');
            $this->queue   = Queue::getQueue($this->config['job']['queue']);
            if (empty($this->queue)) {
                return;
            }
            $this->queue->setTopics($topics);

            if ($topics && $this->status == Process::STATUS_RUNNING) {
                //遍历topic任务列表
                foreach ((array) $topics as  $topic) {
                    if (empty($topic['name'])) {
                        continue;
                    }
                    $this->dynamicWorkerNum[$topic['name']]=$this->dynamicWorkerNum[$topic['name']] ?? 0;
                    $topic['workerMaxNum']                       =$topic['workerMaxNum'] ?? 0;
                    try {
                        $len=$this->queue->len($topic['name']);
                        $this->logger->log('topic: ' . $topic['name'] . ' ' . $this->status . ' len: ' . $len, 'info', $this->logSaveFileWorker);
                    } catch (\Throwable $e) {
                        $this->logger->log('queueError' . $e->getMessage(), 'error', $this->logSaveFileWorker);
                    } catch (\Exception $e) {
                        $this->logger->log('queueError: ' . $e->getMessage(), 'error', $this->logSaveFileWorker);
                    }
                    $this->status=$this->getMasterData('status');

                    if ($this->status == Process::STATUS_RUNNING && $len > $this->queueMaxNum && $this->dynamicWorkerNum[$topic['name']] < $topic['workerMaxNum']) {
                        $max=$topic['workerMaxNum'] - $this->dynamicWorkerNum[$topic['name']];
                        for ($i=0; $i < $max; $i++) {
                            //队列堆积达到一定数据，拉起一次性子进程,这类进程不会自动重启[没必要]
                            $this->reserveQueue($this->dynamicWorkerNum[$topic['name']], $topic['name'], Process::CHILD_PROCESS_CAN_NOT_RESTART);
                            $this->dynamicWorkerNum[$topic['name']]++;
                            $this->logger->log('topic: ' . $topic['name'] . ' ' . $this->status . ' len: ' . $len . ' for: ' . $i . ' ' . $max, 'info', $this->logSaveFileWorker);
                        }
                    }
                }
            }
            $this->queue->close();
        });
    }

    //平滑等待子进程退出之后，再退出主进程
    private function waitWorkers()
    {
        $data['pid']   =$this->ppid;
        $data['status']=self::STATUS_WAIT;
        $this->saveMasterData($data);
        $this->status = self::STATUS_WAIT;
        $this->logger->log('master status: ' . $this->status, 'info', $this->logSaveFileWorker);
    }

    //强制杀死子进程并退出主进程
    private function killWorkersAndExitMaster()
    {
        //修改主进程状态为stop
        $this->status   =self::STATUS_STOP;
        if ($this->workers) {
            foreach ($this->workers as $pid => $worker) {
                //强制杀workers子进程
                \Swoole\Process::kill($pid);
                unset($this->workers[$pid]);
                $this->logger->log('主进程收到退出信号,[' . $pid . ']子进程跟着退出', 'info', $this->logSaveFileWorker);
                $this->logger->log('Worker count: ' . count($this->workers), 'info', $this->logSaveFileWorker);
            }
        }
        $this->exitMaster();
    }

    //退出主进程
    private function exitMaster()
    {
        @unlink($this->pidFile);
        @unlink($this->pidInfoFile);
        $this->logger->log('Time: ' . microtime(true) . '主进程' . $this->ppid . '退出', 'info', $this->logSaveFileWorker);

        sleep(1);
        exit();
    }

    //主进程如果不存在了，子进程退出
    private function checkMpid(&$worker)
    {
        if (!@\Swoole\Process::kill($this->ppid, 0)) {
            $worker->exit();
            $this->logger->log("Master process exited, I [{$worker['pid']}] also quit", 'info', $this->logSaveFileWorker);
        }
    }

    /**
     * 设置进程名.
     *
     * @param mixed $name
     */
    private function setProcessName($name)
    {
        //mac os不支持进程重命名
        if (function_exists('swoole_set_process_name') && PHP_OS != 'Darwin') {
            swoole_set_process_name($name);
        }
    }

    private function saveMasterData($data=[])
    {
        file_put_contents($this->pidFile, $data['pid']);
        file_put_contents($this->pidInfoFile, serialize($data));
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
