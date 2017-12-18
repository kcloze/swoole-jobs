<?php

/*
 * This file is part of PHP CS Fixer.
 * (c) kcloze <pei.greet@qq.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define('APP_PATH', __DIR__ . '/..');

date_default_timezone_set('Asia/Shanghai');

require APP_PATH . '/vendor/autoload.php';

use Kcloze\Jobs\Cache;

$config=[
    'host'    => '127.0.0.1',
    'port'    => 6379,
];
$cache=new Cache($config);

$cache->set('status', 'running');
