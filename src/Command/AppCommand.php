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
        echo 'swoole-jobs is starting.' . PHP_EOL;
    }

    protected function restart()
    {
        $this->logger->log('restarting...');
        $this->kill();
        sleep(3);
        $this->start();
    }

    protected function status()
    {
        $this->sendSignal(SIGUSR2);
    }

    protected function exit()
    {
        $this->kill();
    }

   
}
