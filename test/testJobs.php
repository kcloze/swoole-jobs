<?php

/*
 * This file is part of PHP CS Fixer.
 * (c) kcloze <pei.greet@qq.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

date_default_timezone_set('Asia/Shanghai');

require __DIR__ . '/../vendor/autoload.php';

use Kcloze\Jobs\Jobs;
use Kcloze\Jobs\Logs;
use Kcloze\Jobs\Queue\Queue;

$config = require_once __DIR__ . '/../config.php';

$queue   =  Queue::getQueue($config['job']['queue']);
$log     = new Logs($config['logPath']);
$jobs    = new Jobs($queue, $log, $config['job']);

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
        'job_class'  => 'MyJob',
        'job_method' => 'test1',
        'job_param'  => [['title' => 'kcloze', 'time' => time()]],
    ];
    $jobs->queue->push($data['topic'], $data);
    //$result = $jobs->queue->pop($topicName);
    //var_dump($result);
}
for ($i = 0; $i < 100; $i++) {
    // 根据自定义的 $jobs->load() 方法, 自定义数据格式
    $data = [
        'topic'      => 'MyJob',
        'job_class'  => 'MyJob',
        'job_method' => 'test2',
        'job_param'  => [['title' => 'kcloze', 'time' => time()]],
    ];
    $jobs->queue->push($data['topic'], $data);
    //$result = $jobs->queue->pop($topicName);
    //var_dump($result);
}
for ($i = 0; $i < 100; $i++) {
    $data = [
        'topic'      => 'MyJob',
        'job_class'  => 'MyJob',
        'job_method' => 'testError',
        'job_param'  => [['title' => 'kcloze', 'time' => time()]],
    ];
    $jobs->queue->push($data['topic'], $data);
    //$result = $jobs->queue->pop($topicName);
    //var_dump($result);
}
// for ($i = 0; $i < 200; $i++) {
//     $topicName = 'MyJob';
//     $result    = $jobs->queue->pop($topicName);
//     var_dump($result);
// }
