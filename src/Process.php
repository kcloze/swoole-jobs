<?php

/*
 * This file is part of PHP CS Fixer.
 * (c) kcloze <pei.greet@qq.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Kcloze\Jobs;

class Process
{
    public $processName    = ':swooleProcessTopicQueueJob'; // 进程重命名, 方便 shell 脚本管理
    public $jobs           = null;
    private $workers;
    private $ppid;
    private $workNum  = 5;
    private $config   = [];
    private $pidFile  = '/master.pid';
    private $status   ='running'; //主进程状态

    public function start(Jobs $jobs, $config)
    {
        \Swoole\Process::daemon();
        $this->config  = $config;
        $this->jobs    = $jobs;
        if (isset($config['pidPath']) && !empty($config['pidPath'])) {
            $this->pidFile=$config['pidPath'] . $this->pidFile;
        } else {
            $this->pidFile=APP_PATH . '/log/' . $this->pidFile;
        }
        if (isset($config['processName']) && !empty($config['processName'])) {
            $this->processName = $config['processName'];
        }
        if (isset($config['workNum']) && $config['workNum'] > 0) {
            $this->workNum = $config['workNum'];
        }

        /*
         * master.pid 文件记录 master 进程 pid, 方便之后进程管理
         * 请管理好此文件位置, 使用 systemd 管理进程时会用到此文件
         */

        if (file_exists($this->pidFile)) {
            echo '已有进程运行中,请先结束或重启' . PHP_EOL;
            die();
        }
        $this->ppid = getmypid();
        file_put_contents($this->pidFile, $this->ppid . PHP_EOL);
        $this->setProcessName('job master ' . $this->ppid . $this->processName);

        //开启多个进程消费队列
        for ($i = 0; $i < $this->workNum; $i++) {
            $this->reserveQueue($i);
        }
        $this->registSignal($this->workers);
    }

    //独立进程消费队列
    public function reserveQueue($workNum)
    {
        $self           = $this;
        $reserveProcess = new \Swoole\Process(function () use ($self, $workNum) {
            //设置进程名字
            $this->setProcessName('job ' . $workNum . $self->processName);
            try {
                $self->jobs->run();
            } catch (\Exception $e) {
                echo $e->getMessage();
            }
            echo 'reserve process ' . $workNum . " is working ...\n";
        });
        $pid                 = $reserveProcess->start();
        $this->workers[$pid] = $reserveProcess;
        echo "reserve start...\n";
    }

    //监控子进程
    public function registSignal(&$workers)
    {
        \Swoole\Process::signal(SIGTERM, function ($signo) {
            $this->exitMaster();
        });
        \Swoole\Process::signal(SIGCHLD, function ($signo) use (&$workers) {
            while (true) {
                $ret = \Swoole\Process::wait(false);
                if ($ret) {
                    //主进程状态为running才需要拉起子进程
                    if ($this->status == 'running') {
                        $pid           = $ret['pid'];
                        $child_process = $workers[$pid];
                        echo "Worker Exit, kill_signal={$ret['signal']} PID=" . $pid . PHP_EOL;
                        $new_pid           = $child_process->start();
                        $workers[$new_pid] = $child_process;
                        unset($workers[$pid]);
                    }
                } else {
                    break;
                }
            }
        });
    }

    private function exitMaster()
    {
        @unlink($this->pidFile);
        $this->log('Time: ' . microtime(true) . '主进程' . $this->ppid . '退出' . PHP_EOL);
        //修改主进程状态为stop
        $this->status   ='stop';
        foreach ($this->workers as $pid => $worker) {
            //平滑退出，用exit；强制退出用kill
            \Swoole\Process::kill($pid);
            unset($this->workers[$pid]);
            $this->log('主进程收到退出信号,[' . $pid . ']子进程跟着退出');
            $this->log('Worker count: ' . count($this->workers));
        }
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

    private function log($txt)
    {
        file_put_contents($this->config['logPath'] . '/worker.log', $txt . "\n", FILE_APPEND);
    }
}
