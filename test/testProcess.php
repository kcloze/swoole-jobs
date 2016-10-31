<?php
date_default_timezone_set('Asia/Shanghai');

require __DIR__ . '/../vendor/autoload.php';

$config = [
    'queue'    => ['type' => 'redis', 'host' => '127.0.0.1', 'port' => 6379],
    'rootPath' => __DIR__ . '/..',
    'logPath'  => __DIR__ . '/../log',
    'topics'   => ['MyJob', 'MyJob2'],
    //'framework' => 'yii2',

];

//启动
$process = new Kcloze\Jobs\Process();
$process->start($config);
