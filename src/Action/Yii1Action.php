<?php

/*
 * 消费队列服务类
 */

namespace YcfTeam\Library\Foundation;

use Kcloze\Jobs\Config;
use Kcloze\Jobs\JobObject;
use Kcloze\Jobs\Logs;
use Kcloze\Jobs\Utils;

class Yii1Action
{
    private $logger=null;

    public function init()
    {
        $this->logger  = Logs::getLogger(Config::getConfig()['logPath'] ?? '', Config::getConfig()['logSaveFileApp'] ?? '');
    }

    public function start(JobObject $JobObject)
    {
        $this->init();

        try {
            if (defined('YII_ENV') && YII_ENV == 'development') {
                $name = 'console-dev.php';
            } elseif (defined('YII_ENV') && YII_ENV == 'local') {
                $name = 'console-local.php';
            } else {
                $name = 'console.php';
            }
            $config =  YCF_CONFIG_PATH . '/' . $name;
            require_once SWOOLE_JOBS_ROOT_PATH . '/../../framework/yii.php';
            // Console Application
            $argv               = ['yiic', $JobObject->jobClass, $JobObject->jobMethod];
            $_SERVER['argv']    = array_merge($argv, $JobObject->jobParams);
            $application        = new \CConsoleApplication($config);
            $application->processRequest();
            $this->logger->log('Action has been done, action content: ' . json_encode($JobObject));
        } catch (\Throwable $e) {
            Utils::catchError($this->logger, $e);
        } catch (\Exception $e) {
            Utils::catchError($this->logger, $e);
        }
    }
}
