<?php

/*
 * This file is part of Swoole-jobs
 * (c) kcloze <pei.greet@qq.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Kcloze\Jobs;

use Symfony\Component\Console\Command\Command as SCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Command extends SCommand
{
    protected static $defaultName = 'app';
    private $config               =[];
    private static $server =null;

    public function __construct(array $config)
    {
        parent::__construct();
        Config::setConfig($config);
        $this->config  = Config::getConfig();
        $this->logger  = new Logs($this->config['logPath'] ?? '', $this->config['logSaveFileApp'] ?? '', $this->config['system'] ?? '');
    }

    protected function configure()
    {
        $this->setDescription('manager swoole-jobs ');
        $this->addArgument('name', InputArgument::REQUIRED, 'Who do you want to start swoole-jobs?');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $command=$input->getArgument('name');
        //var_dump($command);exit;
        switch ($command) {
            case 'start':
                static::$server=Swoole::getInstance($this->config);

                $output->writeln('swoole-jobs is start!');
                break;
            case 'stop':
                // code...
                break;
            case 'status':
                // code...
                break;
            case 'exit':
                // code...
                break;

            default:
                $this->printHelpMessage();
                break;
        }
    }

    private function start()
    {
        //启动
        $process = new Process();
        $process->start();
        echo 'swoole-jobs is starting.' . PHP_EOL;
    }

    private function stop()
    {
    }

    private function status()
    {
    }

    private function exit()
    {
    }

    private function printHelpMessage()
    {
        $msg=<<<'EOF'
NAME
      php swoole-jobs - manage swoole-jobs

SYNOPSIS
      php swoole-jobs command [options]
          Manage swoole-jobs daemons.

WORKFLOWS

      help [command]
      Show this help, or workflow help for command.

      restart
      Stop, then start swoole-jobs master and workers.

      start
      Start swoole-jobs master and workers.

      start http
      Start swoole http server for apis.

      stop
      Wait all running workers smooth exit, please check swoole-jobs status for a while.
      
      stop http
      Stop swoole http server for api.

      exit
      Kill all running workers and master PIDs.

      exit http
      Stop swoole http server for api.


EOF;
        echo $msg;
    }
}
