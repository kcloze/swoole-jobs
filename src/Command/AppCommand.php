<?php

/*
 * This file is part of Swoole-jobs
 * (c) kcloze <pei.greet@qq.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Kcloze\Jobs\Command;

use Symfony\Component\Console\Input\InputArgument;
use Kcloze\Jobs\Process;

class AppCommand extends Command
{
    protected static $defaultName = 'app';

    public function __construct(array $config)
    {
        parent::__construct($config);
    }

    protected function configure()
    {
        $this->setDescription('manager swoole-jobs ');
        $this->addArgument('name', InputArgument::REQUIRED, 'Who do you want to start swoole-jobs?');
    }


    protected function start()
    {
        //启动
        $process = new Process();
        $process->start();
        $this->output->writeln('swoole-jobs is starting.');
    }

    protected function restart()
    {
        $this->logger->log('restarting...');
        $this->stop();
        sleep(3);
        $this->start();
    }

    protected function stop()
    {
        $this->sendSignal(SIGUSR1);
    }

    protected function status()
    {
        $this->sendSignal(SIGUSR2);
    }

    protected function exit()
    {
        $this->sendSignal(SIGTERM);

    }

    protected function printHelpMessage()
    {
        $msg=<<<'EOF'
NAME
      - manage swoole-jobs

SYNOPSIS
      -php bin/swoole-jobs.php app [options]
        -Manage swoole-jobs daemons.

WORKFLOWS

      -help [command]
        -Show this help, or workflow help for command.

      -restart
        -Stop, then start swoole-jobs master and workers.

      -start
        -Start swoole-jobs master and workers.

      -stop
        -Wait all running workers smooth exit, please check swoole-jobs status for a while.

      -exit
        -Kill all running workers and master PIDs.



EOF;

        echo $msg;
    }
   
}
