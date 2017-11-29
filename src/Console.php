<?php

/*
 * This file is part of PHP CS Fixer.
 *  * (c) kcloze <pei.greet@qq.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Kcloze\Jobs;

use Kcloze\Jobs\Queue\Queue;

class Console
{
    public $logger    = null;
    private $config   = [];

    public function __construct($config)
    {
        Config::setConfig($config);
        $this->config = Config::getConfig();
        $this->logger = Logs::getLogger(Config::getConfig()['logPath'] ?? []);
    }

    public function run()
    {
        $this->getOpt();
    }

    public function start()
    {
        $queue   =  Queue::getQueue();
        $action  =  Queue::loadAction();
        $jobs    = new Jobs($queue, $action);
        //启动
        $process = new Process($jobs);
        $process->start();
    }

    /**
     * 给主进程发送信号：
     *  SIGUSR1 自定义信号，让子进程平滑退出
     *  SIGTERM 程序终止，让子进程强制退出.
     *
     * @param [type] $signal
     */
    public function stop($signal=SIGTERM)
    {
        if (isset($this->config['pidPath']) && !empty($this->config['pidPath'])) {
            $masterPidFile=$this->config['pidPath'] . '/master.pid';
        } else {
            $masterPidFile=APP_PATH . '/log/master.pid';
        }

        if (file_exists($masterPidFile)) {
            $ppid=file_get_contents($masterPidFile);
            if (empty($ppid)) {
                exit('service is not running' . PHP_EOL);
            }
            if (function_exists('posix_kill')) {
                //macOS 只接受SIGUSR1信号
                $signal=(PHP_OS == 'Darwin') ? SIGKILL : $signal;
                $return=@posix_kill($ppid, $signal);
                if ($return) {
                    $this->logger->log('[pid: ' . $ppid . '] has been stopped success');
                } else {
                    $this->logger->log('[pid: ' . $ppid . '] has been stopped fail');
                }
            } else {
                system('kill -' . $signal . $ppid);
                $this->logger->log('[pid: ' . $ppid . '] has been stopped success');
            }
        } else {
            exit('service is not running' . PHP_EOL);
        }
    }

    public function restart()
    {
        $this->logger->log('restarting...');
        $this->stop();
        sleep(3);
        $this->start();
    }

    public function reload()
    {
        $this->logger->log('reload...');
        $this->stop(SIGUSR1);
        sleep(3);
        $this->start();
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
      run.php - manage swoole-bot

SYNOPSIS
      run.php command [options]
          Manage swoole-bot daemons.


WORKFLOWS


      help [command]
      Show this help, or workflow help for command.


      restart
      Stop, then start the standard daemon loadout.

      start
      Start the standard configured collection of Phabricator daemons. This
      is appropriate for most installs. Use phd launch to customize which
      daemons are launched.


      stop
      Stop all running daemons, or specific daemons identified by PIDs. Use
      run.php status to find PIDs.

EOF;
        echo $msg;
    }
}
