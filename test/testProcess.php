<?php
date_default_timezone_set('Asia/Shanghai');

require __DIR__ . '/../vendor/autoload.php';

$config = [
    'queue'   => ['type' => 'redis', 'host' => '127.0.0.1', 'port' => 6379],
    'logPath' => __DIR__ . '/../log',
];

//启动
$process = new Kcloze\Jobs\Process();
$process->start($config);
