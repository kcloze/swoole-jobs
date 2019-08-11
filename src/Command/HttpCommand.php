<?php

/*
 * This file is part of Swoole-jobs
 * (c) kcloze <pei.greet@qq.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Kcloze\Jobs\Command;

use Kcloze\Jobs\HttpServer;
use Symfony\Component\Console\Input\InputArgument;

class HttpCommand extends Command
{
    protected static $defaultName = 'http';

    public function __construct(array $config)
    {
        parent::__construct($config);
    }

    protected function configure()
    {
        $this->setDescription('manager swoole-jobs http api ');
        $this->addArgument('name', InputArgument::REQUIRED, 'Who do you want to start swoole-jobs http api?');
    }

    protected function start()
    {
        //启动
        if (isset($this->config['httpServer'])) {
            $this->output->writeln('swoole-jobs http server is starting.');
            HttpServer::getInstance($this->config);
        } else {
            $this->output->writeln('sorrry,swoole-jobs http server config is not setting!');
        }
    }

    protected function restart()
    {
        $this->logger->log('api server restarting...');
        $this->stop();
        sleep(3);
        $this->start();
    }

    protected function stop()
    {
        $this->sendSignalHttpServer(SIGTERM);
    }

    protected function status()
    {
        $this->output->writeln('there is no status command.');
    }

    protected function exit()
    {
        $this->sendSignalHttpServer(SIGTERM);
    }

    protected function printHelpMessage()
    {
        $msg=<<<'EOF'
NAME
      - swoole-jobs http api 

SYNOPSIS
      -php ./bin/swoole-jobs.php http [options]
            -Manage swoole-jobs http api.

WORKFLOWS

      -help [command]
        -Show this help, or workflow help for command.

      -http start 
        -Start swoole http server for apis.

      -http stop
        -Stop swoole http server for api.

      -http exit
        -Stop swoole http server for api.


EOF;

        echo $msg;
    }
}
