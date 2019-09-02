<?php

/*
 * This file is part of Swoole-jobs
 * (c) kcloze <pei.greet@qq.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Kcloze\Jobs\Command;

use Kcloze\Jobs\Config;
use Kcloze\Jobs\Logs;
use Kcloze\Jobs\Utils;
use Symfony\Component\Console\Command\Command as SCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class Command extends SCommand
{
    protected $input;
    protected $output;
    protected $config               =[];

    public function __construct(array $config)
    {
        parent::__construct();
        Config::setConfig($config);
        $this->config  = Config::getConfig();
        $this->logger  = new Logs($this->config['logPath'] ?? '', $this->config['logSaveFileApp'] ?? '', $this->config['system'] ?? '');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input =$input;
        $this->output=$output;
        $this->checkSwooleSetting();
        $command=$this->input->getArgument('name');

        switch ($command) {
            case 'start':
                $this->start();
                break;
            case 'stop':
                $this->stop();
                break;
            case 'restart':
                $this->restart();
                break;
            case 'status':
                $this->status();
                break;
            case 'exit':
                $this->exit();
                break;
            case 'help':
                $this->printHelpMessage();
                break;

            default:
                $this->printHelpMessage();
                break;
        }
    }

    abstract protected function start();

    abstract protected function restart();

    abstract protected function status();

    abstract protected function stop();

    abstract protected function exit();

    /**
     *  给主进程发送信号：
     *  SIGUSR1 自定义信号，让子进程平滑退出
     *  SIGUSR2 自定义信号2，显示进程状态
     *  SIGTERM 程序终止，让子进程强制退出.
     *
     * @param [type] $signal
     */
    protected function sendSignal($signal=SIGUSR1)
    {
        $this->logger->log($signal . (SIGUSR1 == $signal) ? ' smooth to exit...' : ' force to exit...');

        if (isset($this->config['pidPath']) && !empty($this->config['pidPath'])) {
            $this->config['pidPath']=$this->config['pidPath'] . '/' . Utils::getHostName();
            $masterPidFile          =$this->config['pidPath'] . '/master.pid';
            $pidStatusFile          =$this->config['pidPath'] . '/status.info';
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

    protected function sendSignalHttpServer($signal=SIGTERM)
    {
        if (isset($this->config['httpServer']) && isset($this->config['httpServer']['settings']['pid_file'])) {
            $httpServerPid                                                                       =null;
            file_exists($this->config['httpServer']['settings']['pid_file']) && $httpServerPid   =file_get_contents($this->config['httpServer']['settings']['pid_file']);
            if (!$httpServerPid) {
                echo 'http server pid is null, maybe http server is not running!' . PHP_EOL;

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

    private function checkSwooleSetting()
    {
        if (version_compare(swoole_version(), '4.0.0', '>=') && 'Off' !== ini_get('swoole.enable_coroutine')) {
            $this->output->writeln('swoole version >=4.0.0,you have to disable coroutine in php.ini');
            $this->output->writeln('details jump to: https://github.com/swoole/swoole-src/issues/2716');
            exit;
        }
    }
}
