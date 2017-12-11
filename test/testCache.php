<?php


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
