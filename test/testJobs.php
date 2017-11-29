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

use Kcloze\Jobs\Config;
use Kcloze\Jobs\Jobs;
use Kcloze\Jobs\Queue\Queue;

$config = require_once APP_PATH . '/config.php';
Config::setConfig($config);

$queue   =  Queue::getQueue();
$jobs    = new Jobs($queue);

if (!$jobs->queue) {
    die("queue object is null\n");
}

//jobs的topic需要在配置文件里面定义，并且一次性注册进去
$topics = $jobs->queue->getTopics();
var_dump($topics);

for ($i = 0; $i < 100; $i++) {
    // 根据自定义的 $jobs->load() 方法, 自定义数据格式
    $data = [
        'topic'      => 'MyJob',
        'jobClass'   => 'MyJob',
        'jobMethod'  => 'test1',
        'jobParams'  => [['title' => 'kcloze', 'time' => time()]],
    ];
    $jobs->queue->push($data['topic'], $data);
}
for ($i = 0; $i < 100; $i++) {
    // 根据自定义的 $jobs->load() 方法, 自定义数据格式
    $data = [
        'topic'       => 'MyJob',
        'jobClass'    => 'MyJob',
        'jobMethod'   => 'test2',
        'jobParams'   => [['title' => 'kcloze', 'time' => time()]],
    ];
    $jobs->queue->push($data['topic'], $data);
}
for ($i = 0; $i < 100; $i++) {
    $data = [
        'topic'       => 'MyJob',
        'jobClass'    => 'MyJob',
        'jobMethod'   => 'testError',
        'jobParams'   => [['title' => 'kcloze', 'time' => time()]],
    ];
    $jobs->queue->push($data['topic'], $data);
}
