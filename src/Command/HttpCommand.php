<?php

/*
 * This file is part of Swoole-jobs
 * (c) kcloze <pei.greet@qq.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Kcloze\Jobs\Command;

use Symfony\Component\Console\Input\InputArgument;


class HttpCommand extends Command
{
    protected static $defaultName = 'http';

    public function __construct(array $config)
    {
        parent::__construct($config);
    
    }

    protected function start()
    {
        //启动
        if (isset($this->config['httpServer'])) {
            //echo 'swoole-jobs http server is starting.' . PHP_EOL;
            HttpServer::getInstance($this->config);
        }
    }
    protected function restart()
    {
        $this->logger->log('api server restarting...');
        $this->killHttpServer();
        sleep(3);
        $this->startHttpServer();
    }

 
    protected function kill()
    {
        $this->sendSignal(SIGTERM);
    }

    protected function killHttpServer()
    {
        $this->sendSignalHttpServer(SIGTERM);
    }

    protected function configure()
    {
        $this->setDescription('manager swoole-jobs http api ');
        $this->addArgument('name', InputArgument::REQUIRED, 'Who do you want to start swoole-jobs http api?');
    }


   
}
