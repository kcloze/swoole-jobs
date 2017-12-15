<?php

/*
 * This file is part of PHP CS Fixer.
 * (c) kcloze <pei.greet@qq.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Kcloze\Jobs\Action;

use Kcloze\Jobs\Config;
use Kcloze\Jobs\JobObject;
use Kcloze\Jobs\Logs;
use Kcloze\Jobs\Utils;
use Phalcon\Cli\Console as ConsoleApp;

class PhalconAction
{
    public function init()
    {
        $this->logger = Logs::getLogger(Config::getConfig()['logPath'] ?? []);
    }

    public function start(JobObject $JobObject)
    {
        try {
            $arguments['task']  =$JobObject->jobClass;
            $arguments['action']=$JobObject->jobMethod;
            $arguments['params']=$JobObject->jobParams;

            $config = include APP_PATH . '/../ycf_config/' . YII_ENV_APP_NAME . '/config.php';
            include APP_PATH . '/app/config/loader.php';
            include APP_PATH . '/app/config/mainCli.php';

            $console            = new ConsoleApp($di);
            $console->handle($arguments);
            $console->logger->flush();
        } catch (\Throwable $e) {
            Utils::catchError($this->logger, $e);
        } catch (\Exception $e) {
            Utils::catchError($this->logger, $e);
        }
    }
}
