<?php

/*
 * This file is part of Swoole-jobs
 * (c) kcloze <pei.greet@qq.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use Kcloze\Jobs\Command\AppCommand;
use Symfony\Component\Console\Application;

define('SWOOLE_JOBS_ROOT_PATH', dirname(__DIR__));

require SWOOLE_JOBS_ROOT_PATH . '/vendor/autoload.php';
$config = require_once SWOOLE_JOBS_ROOT_PATH . '/config.php';

$application    = new Application();
$appCommand     = new AppCommand($config);
$application->add($appCommand);

//api service disable
// $httpCommand    = new HttpCommand($config);
//$application->add($httpCommand);

$application->setDefaultCommand($appCommand->getName(),true);
$application->run();
