<?php

/**
 * Swoole process多进程类
 * @author Kcloze
 * @since 2016.10.20
 *
 */
namespace Kcloze\Jobs;

use Kcloze\Jobs\Jobs;

class Process
{
    private $reserveProcess;
    private $workers;
    private $workNum = 5;
    private $config  = [];

    //const LOG_PATH = '/data/';

    public function start($config)
    {
        //\swoole_process::daemon();
        $this->config = $config;
        //开启多个进程消费队列
        for ($i = 0; $i < $this->workNum; $i++) {
            $this->reserveQueue($i);
        }

        $this->registSignal($this->workers);
        \swoole_process::wait();

    }
    //独立进程消费队列
    public function reserveQueue($workNum)
    {
        //$this->log('starting to run');
        $self = $this;
        $ppid = getmypid();
        file_put_contents($this->config['logPath'] . '/master.pid.log', $ppid . "\n");
        \swoole_set_process_name("job master " . $ppid . " : reserve process");

        $reserveProcess = new \swoole_process(function () use ($self, $workNum) {

            //设置进程名字
            swoole_set_process_name("job " . $workNum . ": reserve process");
            try {
                $job = new Jobs();
                $job->run($self->config);
            } catch (Exception $e) {
                echo $e->getMessage();
            }

            echo "reserve process " . $workNum . " is working ...\n";

        });
        $pid                 = $reserveProcess->start();
        $this->workers[$pid] = $reserveProcess;
        echo "reserve start...\n";

    }

    //监控子进程
    public function registSignal($workers)
    {
        \swoole_process::signal(SIGTERM, function ($signo) use (&$workers) {

            $this->exitMaster("收到退出信号,退出主进程");
        });
        \swoole_process::signal(SIGCHLD, function ($signo) use (&$workers) {
            while (1) {
                $ret = \swoole_process::wait(false);
                if ($ret) {
                    $pid           = $ret['pid'];
                    $child_process = $workers[$pid];
                    //unset($workers[$pid]);
                    echo "Worker Exit, kill_signal={$ret['signal']} PID=" . $pid . PHP_EOL;
                    $new_pid           = $child_process->start();
                    $workers[$new_pid] = $child_process;
                    unset($workers[$pid]);
                } else {
                    break;
                }
            }
        });

    }

    private function exitMaster()
    {
        @unlink($this->config['logPath'] . '/master.pid.log');
        $this->log("Time: " . microtime(true) . "主进程退出" . "\n");
        exit();
    }

    private function log($txt)
    {
        file_put_contents($this->config['logPath'] . '/worker.log', $txt . "\n", FILE_APPEND);
    }

}
