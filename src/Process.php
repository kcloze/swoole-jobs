<?php

/*
 * This file is part of PHP CS Fixer.
 * (c) kcloze <pei.greet@qq.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Kcloze\Jobs;

use Kcloze\Jobs\Queue\BaseTopicQueue;

class Process
{
    const CHILD_PROCESS_CAN_RESTART          ='canRestart'; //子进程可以重启
    const CHILD_PROCESS_CAN_NOT_RESTART      ='canNotRestart'; //子进程不可以重启

    public $processName      = ':swooleProcessTopicQueueJob'; // 进程重命名, 方便 shell 脚本管理
    public $jobs             = null;

    public $workers                       =[];
    private $queueMaxNum                  =100; //队列达到一定长度，增加子进程个数
    private $queueTickTimer               =2000; //一定时间间隔（毫秒）检查队列长度
    private $workerNum                    =0; //固定分配的子进程个数
    private $canNotRestartWorkerNum       =[]; //一次性（不能重启）的子进程计数，最大数为每个topic配置workerMaxNum，它的个数是动态变化的
    private $workersInfo                  =[];
    private $ppid;
    private $config   = [];
    private $pidFile  = '';
    private $status   ='running'; //主进程状态

    public function __construct(Jobs $jobs, BaseTopicQueue $queue)
    {
        $this->config  =  Config::getConfig();
        $this->logger  = Logs::getLogger($this->config['logPath'] ?? []);
        $this->jobs    = $jobs;
        $this->queue   = $queue;

        if (isset($this->config['pidPath']) && !empty($this->config['pidPath'])) {
            $this->pidFile=$this->config['pidPath'] . '/master.pid';
        } else {
            $this->pidFile=APP_PATH . '/master.pid';
        }
        if (isset($this->config['processName']) && !empty($this->config['processName'])) {
            $this->processName = $this->config['processName'];
        }

        /*
         * master.pid 文件记录 master 进程 pid, 方便之后进程管理
         * 请管理好此文件位置, 使用 systemd 管理进程时会用到此文件
         * 判断文件是否存在，并判断进程是否在运行
         */
        if (file_exists($this->pidFile)) {
            $pid    =file_get_contents($this->pidFile);
            if ($pid && @\Swoole\Process::kill($pid, 0)) {
                die('已有进程运行中,请先结束或重启' . PHP_EOL);
            }
        }

        \Swoole\Process::daemon();
        $this->ppid = getmypid();
        file_put_contents($this->pidFile, $this->ppid);
        $this->setProcessName('job master ' . $this->ppid . $this->processName);
    }

    public function start()
    {
        $topics = $this->queue->getTopics();
        $this->logger->log('topics: ' . json_encode($topics));

        if ($topics) {
            //遍历topic任务列表
            foreach ((array) $topics as  $topic) {
                if (isset($topic['workerMinNum']) && isset($topic['name'])) {
                    //每个topic开启最少个进程消费队列
                    for ($i = 0; $i < $topic['workerMinNum']; $i++) {
                        $this->reserveQueue($i, $topic['name'], self::CHILD_PROCESS_CAN_RESTART);
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
        $reserveProcess = new \Swoole\Process(function () use ($num, $topic, $type) {
            try {
                //设置进程名字
                $this->setProcessName($type . ' job ' . $num . ' ' . $topic . ' ' . $this->processName);
                $this->jobs->run($topic);
            } catch (\Throwable $e) {
                $this->logger->log($e->getMessage(), 'error', Logs::LOG_SAVE_FILE_WORKER);
            } catch (\Exception $e) {
                $this->logger->log($e->getMessage(), 'error', Logs::LOG_SAVE_FILE_WORKER);
            }
            $this->logger->log('worker id: ' . $num . ' is done!!!', 'info', Logs::LOG_SAVE_FILE_WORKER);
        });
        $pid                                        = $reserveProcess->start();
        $this->workers[$pid]                        = $reserveProcess;
        $this->workersInfo[$pid]['type']            = $type;
        $this->workersInfo[$pid]['topic']           = $topic;
        $this->logger->log('topic: ' . $topic . ' ' . $type . ' worker id: ' . $num . ' pid: ' . $pid . ' is start...', 'info', Logs::LOG_SAVE_FILE_WORKER);
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
                    $this->logger->log('signoError: ' . $signo . $e->getMessage(), 'error', Logs::LOG_SAVE_FILE_WORKER);
                }
                if ($ret) {
                    $pid           = $ret['pid'];
                    $childProcess = $this->workers[$pid];
                    $topic = $this->workersInfo[$pid]['topic'] ?? '';
                    $topicCanNotRestartNum =  $this->canNotRestartWorkerNum[$topic] ?? 'null';
                    $this->logger->log(self::CHILD_PROCESS_CAN_RESTART . '---' . $topic . '***' . $topicCanNotRestartNum . '***' . $this->status . '***' . $this->workersInfo[$pid]['type'] . '***' . $pid, 'info', Logs::LOG_SAVE_FILE_WORKER);
                    //主进程状态为running并且该子进程是可以重启的
                    if ($this->status == 'running' && $this->workersInfo[$pid]['type'] == self::CHILD_PROCESS_CAN_RESTART) {
                        try {
                            $num = $this->workerNum;
                            $newPid           = $childProcess->start();
                            $this->workers[$newPid] = $childProcess;
                            $this->workersInfo[$newPid]['type'] = self::CHILD_PROCESS_CAN_RESTART;
                            $this->workersInfo[$newPid]['topic'] = $topic;
                            $this->workerNum++;
                            $this->logger->log("Worker Restart, kill_signal={$ret['signal']} PID=" . $newPid, 'info', Logs::LOG_SAVE_FILE_WORKER);
                        } catch (\Throwable $e) {
                            $this->logger->log('restartErrorThrow' . $e->getMessage(), 'error', Logs::LOG_SAVE_FILE_WORKER);
                        } catch (\Exception $e) {
                            $this->logger->log('restartError: ' . $e->getMessage(), 'error', Logs::LOG_SAVE_FILE_WORKER);
                        }
                    }
                    //某个topic动态变化的子进程，退出之后个数减少一个
                    if ($this->workersInfo[$pid]['type'] == self::CHILD_PROCESS_CAN_NOT_RESTART) {
                        $this->canNotRestartWorkerNum[$topic]--;
                    }
                    $this->logger->log("Worker Exit, kill_signal={$ret['signal']} PID=" . $pid, 'info', Logs::LOG_SAVE_FILE_WORKER);
                    unset($this->workers[$pid], $this->workersInfo[$pid]);
                    $this->workerNum--;

                    $this->logger->log('Worker count: ' . count($this->workers) . '==' . $this->workerNum, 'info', Logs::LOG_SAVE_FILE_WORKER);
                    //如果$this->workers为空，且主进程状态为wait，说明所有子进程安全退出，这个时候主进程退出
                    if (empty($this->workers) && $this->status == 'wait') {
                        $this->logger->log('主进程收到所有信号子进程的退出信号，子进程安全退出完成', 'info', Logs::LOG_SAVE_FILE_WORKER);
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
            $topics = $this->queue->getTopics();
            if ($topics && $this->status   ='running') {
                //遍历topic任务列表
                foreach ((array) $topics as  $topic) {
                    $len=$this->queue->len($topic['name']);
                    $this->canNotRestartWorkerNum[$topic['name']]=$this->canNotRestartWorkerNum[$topic['name']] ?? 0;
                    $num = $this->canNotRestartWorkerNum[$topic['name']];
                    $topic['workerMaxNum']=$topic['workerMaxNum'] ?? 0;
                    if ($len > $this->queueMaxNum && $num < $topic['workerMaxNum']) {
                        //队列堆积达到一定数据，拉起一次性子进程,这类进程不会自动重启[没必要]
                        $this->reserveQueue($num, $topic['name'], self::CHILD_PROCESS_CAN_NOT_RESTART);
                        $this->canNotRestartWorkerNum[$topic['name']]++;
                    }
                    $this->logger->log('topic ' . $topic['name'] . ' len: ' . $len, 'info', Logs::LOG_SAVE_FILE_WORKER);
                }
            }
        });
    }

    //平滑等待子进程退出之后，再退出主进程
    private function waitWorkers()
    {
        $this->status   ='wait';
    }

    //强制杀死子进程并退出主进程
    private function killWorkersAndExitMaster()
    {
        //修改主进程状态为stop
        $this->status   ='stop';
        if ($this->workers) {
            foreach ($this->workers as $pid => $worker) {
                //强制杀workers子进程
            \Swoole\Process::kill($pid);
                unset($this->workers[$pid]);
                $this->logger->log('主进程收到退出信号,[' . $pid . ']子进程跟着退出', 'info', Logs::LOG_SAVE_FILE_WORKER);
                $this->logger->log('Worker count: ' . count($this->workers), 'info', Logs::LOG_SAVE_FILE_WORKER);
            }
        }
        $this->exitMaster();
    }

    //退出主进程
    private function exitMaster()
    {
        @unlink($this->pidFile);
        $this->logger->log('Time: ' . microtime(true) . '主进程' . $this->ppid . '退出', 'info', Logs::LOG_SAVE_FILE_WORKER);
        sleep(1);
        exit();
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
}
