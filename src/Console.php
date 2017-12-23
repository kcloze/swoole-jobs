<?php

/*
 * This file is part of PHP CS Fixer.
 * (c) kcloze <pei.greet@qq.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Kcloze\Jobs;

class Console
{
    public $logger    = null;
    private $config   = [];

    public function __construct($config)
    {
        Config::setConfig($config);
        $this->config  = Config::getConfig();
        $this->logger  = new Logs($this->config['logPath'] ?? '', $this->config['logSaveFileApp'] ?? '');
    }

    public function run()
    {
        $this->getOpt();
    }

    public function start()
    {
        //启动
        $process = new Process();
        $process->start();
    }

    /**
     * 给主进程发送信号：
     *  SIGUSR1 自定义信号，让子进程平滑退出
     *  SIGTERM 程序终止，让子进程强制退出.
     *
     * @param [type] $signal
     */
    public function stop($signal=SIGUSR1)
    {
        $this->logger->log($signal . ($signal == SIGUSR1) ? ' smooth to exit...' : ' force to exit...');

        if (isset($this->config['pidPath']) && !empty($this->config['pidPath'])) {
            $masterPidFile=$this->config['pidPath'] . '/master.pid';
        } else {
            die('config pidPath must be set!' . PHP_EOL);
        }

        if (file_exists($masterPidFile)) {
            $pid   =file_get_contents($masterPidFile);
            if ($pid && !@\Swoole\Process::kill($pid, 0)) {
                exit('service is not running' . PHP_EOL);
            }
            //macOS 只接受SIGKILL信号,kill之后进程居然直接崩溃类...,还好生产环境也不会用macOS吧
            //$signal=(PHP_OS == 'Darwin') ? SIGKILL : $signal;
            if (@\Swoole\Process::kill($pid, $signal)) {
                $this->logger->log('[pid: ' . $pid . '] has been stopped success');
            } else {
                $this->logger->log('[pid: ' . $pid . '] has been stopped fail');
            }
        } else {
            exit('service is not running' . PHP_EOL);
        }
    }

    public function restart()
    {
        $this->logger->log('restarting...');
        $this->kill();
        sleep(3);
        $this->start();
    }

    public function kill()
    {
        $this->stop(SIGTERM);
    }

    public function getOpt()
    {
        global $argv;
        if (empty($argv[1])) {
            $this->printHelpMessage();
            exit(1);
        }
        $opt=$argv[1];
        switch ($opt) {
            case 'start':
                $this->start();
                break;
            case 'stop':
                $this->stop();
                break;
            case 'exit':
                $this->kill();
                break;
            case 'restart':
                $this->restart();
                break;
            case 'help':
                $this->printHelpMessage();
                break;

            default:
                $this->printHelpMessage();
                break;
        }
    }

    public function printHelpMessage()
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

      stop
      Wait all running workers smooth exit, please check swoole-jobs status for a while.

      exit
      Kill all running workers and master PIDs.


EOF;
        echo $msg;
    }
}
