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
    private $workNum = 5;

    //const LOG_PATH = '/data/';

    public function start()
    {
        \swoole_process::daemon();
        //开启多个进程消费队列
        for ($i = 0; $i < $this->workNum; $i++) {
            $this->reserveQueue($i);
        }
        \swoole_process::wait();

    }
    //独立进程消费队列
    public function reserveQueue($workNum)
    {
        //$this->log('starting to run');
        $self = $this;
        $pid  = getmypid();
        file_put_contents(APP_PATH . '/data/master.pid.log', $pid . "\n");
        \swoole_set_process_name("job master " . $pid . " : reserve process");
        $this->reserveProcess = new \swoole_process(function () use ($self, $workNum) {

            $self->init();

            //设置进程名字
            swoole_set_process_name("job " . $workNum . ": reserve process");
            try {
                $job = Jobs();
                $job->run();
            } catch (Exception $e) {
                echo $e->getMessage();
            }

            echo "reserve process " . $workNum . " is working ...\n";

        });
        $this->reserveProcess->start();
        echo "reserve start...\n";

    }

    private function init()
    {
        //$this->conselApp = new Jobs();
    }

    private function log($txt)
    {
        file_put_contents(APP_PATH . '/data/worker.log', $txt . "\n", FILE_APPEND);
    }

}
