<?php

/*
 * This file is part of PHP CS Fixer.
 * (c) kcloze <pei.greet@qq.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

// $pid =112;
// $status_str .= "$pid\t" . str_pad('N/A', 7) . ' '
//                     //. str_pad($info['listen'], static::$_maxSocketNameLength) . ' '
//                     //. str_pad($info['name'], static::$_maxWorkerNameLength) . ' '
//                     . str_pad('N/A', 11) . ' ' . str_pad('N/A', 9) . ' '
//                     . str_pad('N/A', 7) . ' ' . str_pad('N/A', 13) . " N/A    [busy] \n";

// echo $status_str;

define('SWOOLE_JOBS_ROOT_PATH', __DIR__ . '/..');

date_default_timezone_set('Asia/Shanghai');

require SWOOLE_JOBS_ROOT_PATH . '/vendor/autoload.php';

use Kcloze\Jobs\Config;
use Kcloze\Jobs\Message\Message;

$config = require_once SWOOLE_JOBS_ROOT_PATH . '/config.php';
Config::setConfig($config);

$content ='测试机器人吧';
$message =Message::getMessage($config['message']);
$ret     =$message->send($content, $config['message']['token']);
var_dump($ret);
