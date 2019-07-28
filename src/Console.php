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
        $this->logger  = new Logs($this->config['logPath'] ?? '', $this->config['logSaveFileApp'] ?? '', $this->config['system'] ?? '');
    }

    public function run()
    {
        $this->runOpt();
    }

    public function start()
    {
        //启动
        $process = new Process();
        $process->start();
        echo 'swoole-jobs is starting.' . PHP_EOL;
    }

    public function startHttpServer()
    {
        //启动
        if (isset($this->config['httpServer'])) {
            //echo 'swoole-jobs http server is starting.' . PHP_EOL;
            HttpServer::getInstance($this->config);
        }
    }

    /**
     *  给主进程发送信号：
     *  SIGUSR1 自定义信号，让子进程平滑退出
     *  SIGUSR2 自定义信号2，显示进程状态
     *  SIGTERM 程序终止，让子进程强制退出.
     *
     * @param [type] $signal
     */
    public function sendSignal($signal=SIGUSR1)
    {
        $this->logger->log($signal . (SIGUSR1 == $signal) ? ' smooth to exit...' : ' force to exit...');

        if (isset($this->config['pidPath']) && !empty($this->config['pidPath'])) {
            $masterPidFile=$this->config['pidPath'] . '/master.pid';
            $pidStatusFile=$this->config['pidPath'] . '/status.info';
        } else {
            echo 'config pidPath must be set!' . PHP_EOL;

            return;
        }

        if (file_exists($masterPidFile)) {
            $pid   =file_get_contents($masterPidFile);
            if (!$pid) {
                echo 'swoole-jobs pid is null' . PHP_EOL;

                return;
            }

            if ($pid && !@\Swoole\Process::kill($pid, 0)) {
                echo 'service is not running' . PHP_EOL;

                return;
            }
            if (@\Swoole\Process::kill($pid, $signal)) {
                $this->logger->log('[master pid: ' . $pid . '] has been received  signal' . $signal);
                sleep(1);
                //如果是SIGUSR2信号，显示swoole-jobs状态信息
                if (SIGUSR2 == $signal) {
                    $statusStr=@file_get_contents($pidStatusFile);

                    echo $statusStr ? $statusStr : 'sorry,show status fail.';
                    @unlink($pidStatusFile);

                    return;
                } elseif (SIGTERM == $signal) {
                    //尝试5次发送信号
                    $i=0;
                    do {
                        ++$i;
                        $this->logger->log('[master pid: ' . $pid . '] has been received  signal' . $signal . ' times: ' . $i);
                        if (!@\Swoole\Process::kill($pid, 0)) {
                            echo 'swoole-jobs kill successful, status is stopped.' . PHP_EOL;

                            return;
                        }
                        @\Swoole\Process::kill($pid, $signal);

                        sleep(3);
                    } while ($i <= 5);

                    echo 'swoole-jobs kill failed.' . PHP_EOL;
                }
                echo 'swoole-jobs stop success.' . PHP_EOL;
            }
            $this->logger->log('[master pid: ' . $pid . '] has been received signal fail');

            return;
        }
        echo 'service is not running' . PHP_EOL;
    }

    public function sendSignalHttpServer($signal=SIGTERM)
    {
        if (isset($this->config['httpServer']) && isset($this->config['httpServer']['settings']['pid_file'])) {
            $httpServerPid                                                                       =null;
            file_exists($this->config['httpServer']['settings']['pid_file']) && $httpServerPid   =file_get_contents($this->config['httpServer']['settings']['pid_file']);
            if (!$httpServerPid) {
                echo 'http server pid is null' . PHP_EOL;

                return;
            }
            //尝试5次发送信号
            $i=0;
            do {
                ++$i;
                $this->logger->log('[httpServerPid : ' . $httpServerPid . '] has been received  signal' . $signal . ' times: ' . $i);
                if (!@\Swoole\Process::kill($httpServerPid, 0)) {
                    echo 'http server status is stopped' . PHP_EOL;

                    return;
                }
                @\Swoole\Process::kill($httpServerPid, $signal);

                sleep(1);
            } while ($i <= 5);
            echo 'swoole-jobs kill failed.' . PHP_EOL;
        } else {
            echo 'configs with http server not settting' . PHP_EOL;

            return;
        }
    }

    public function restart()
    {
        $this->logger->log('restarting...');
        $this->kill();
        sleep(3);
        $this->start();
    }

    public function restartHttpServer()
    {
        $this->logger->log('api server restarting...');
        $this->killHttpServer();
        sleep(3);
        $this->startHttpServer();
    }

    public function kill()
    {
        $this->sendSignal(SIGTERM);
    }

    public function killHttpServer()
    {
        $this->sendSignalHttpServer(SIGTERM);
    }

    public function runOpt()
    {
        global $argv;
        if (empty($argv[1])) {
            $this->printHelpMessage();
            exit(1);
        }
        $opt=$argv[1];
        switch ($opt) {
            case 'start':
                $op2=$argv[2] ?? '';
                if ('http' == $op2) {
                    $this->startHttpServer();
                    break;
                }
                $this->start();

                break;
            case 'stop':
                $op2=$argv[2] ?? '';
                if ('http' == $op2) {
                    $this->killHttpServer();
                    break;
                }
                $this->sendSignal();
                break;
            case 'status':
                $this->sendSignal(SIGUSR2);
                break;
            case 'exit':
                $op2=$argv[2] ?? '';
                if ('http' == $op2) {
                    $this->killHttpServer();
                    break;
                }
                $this->kill();
                break;
            case 'restart':
                $op2=$argv[2] ?? '';
                if ('http' == $op2) {
                    $this->restartHttpServer();
                    break;
                }

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
