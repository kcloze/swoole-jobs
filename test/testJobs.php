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

use Kcloze\Jobs\Queue\Queue;

$config = require_once APP_PATH . '/config.php';

$queue=Queue::getQueue($config['job']['queue']);

var_dump($queue);

$queue->setTopics($config['job']['topics']);

if (!$queue) {
    die("queue object is null\n");
}

//jobs的topic需要在配置文件里面定义，并且一次性注册进去
$topics = $queue->getTopics();
var_dump($topics);

//往topic为MyJob的任务增加执行job
for ($i = 0; $i < 100; $i++) {
    // 根据自定义的 $jobs->load() 方法, 自定义数据格式
    $data = [
        'topic'      => 'MyJob',
        'jobClass'   => 'Kcloze\Jobs\Jobs\MyJob',
        'jobMethod'  => 'test1',
        'jobParams'  => ['kcloze', time()],
    ];
    $queue->push($data['topic'], $data);
}
for ($i = 0; $i < 100; $i++) {
    // 根据自定义的 $jobs->load() 方法, 自定义数据格式
    $data = [
        'topic'       => 'MyJob',
        'jobClass'    => 'Kcloze\Jobs\Jobs\MyJob',
        'jobMethod'   => 'test2',
        'jobParams'   => ['kcloze', time(), ['a', 'b']],
    ];
    $queue->push($data['topic'], $data);
}
for ($i = 0; $i < 100; $i++) {
    $data = [
        'topic'       => 'MyJob',
        'jobClass'    => 'Kcloze\Jobs\Jobs\MyJob',
        'jobMethod'   => 'testError',
        'jobParams'   => ['kcloze', time()],
    ];
    $queue->push($data['topic'], $data);
}

//往topic为MyJob2的任务增加执行job

for ($i = 0; $i < 100; $i++) {
    // 根据自定义的 $jobs->load() 方法, 自定义数据格式
    $data = [
        'topic'       => 'MyJob2',
        'jobClass'    => 'Kcloze\Jobs\Jobs\MyJob2',
        'jobMethod'   => 'test1',
        'jobParams'   => ['kcloze', time()],
    ];
    $queue->push($data['topic'], $data);
}
for ($i = 0; $i < 100; $i++) {
    // 根据自定义的 $jobs->load() 方法, 自定义数据格式
    $data = [
        'topic'       => 'MyJob2',
        'jobClass'    => 'Kcloze\Jobs\Jobs\MyJob2',
        'jobMethod'   => 'test2',
        'jobParams'   => ['kcloze', time(), ['a', 'b']],
    ];
    $queue->push($data['topic'], $data);
}
for ($i = 0; $i < 100; $i++) {
    $data = [
        'topic'       => 'MyJob2',
        'jobClass'    => 'Kcloze\Jobs\Jobs\MyJob2',
        'jobMethod'   => 'testError',
        'jobParams'   => ['kcloze', time()],
    ];
    $queue->push($data['topic'], $data);
}
