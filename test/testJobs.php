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

use Kcloze\Jobs\JobObject;
use Kcloze\Jobs\Queue\BaseTopicQueue;
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

addTest1($queue);
// addTest2($queue);
// addTest3($queue);
// addTest4($queue);

//往topic为MyJob的任务增加执行job
function addTest1($queue)
{
    for ($i = 0; $i < 100; $i++) {
        $job         =new JobObject('MyJob', 'Kcloze\Jobs\Jobs\MyJob', 'test1', ['kcloze', time()]);
        $delay       =60 * 1000;
        $priority    =BaseTopicQueue::HIGH_LEVEL_1;
        $queue->push('MyJob', $job, $delay, $priority);
    }
}

function addTest2($queue)
{
    for ($i = 0; $i < 100; $i++) {
        $job         =new JobObject('MyJob', 'Kcloze\Jobs\Jobs\MyJob', 'test2', ['kcloze', time()]);
        $delay       =60 * 1000;
        $priority    =BaseTopicQueue::HIGH_LEVEL_2;
        $queue->push('MyJob', $job, $delay, $priority);
    }
}

function addTest3($queue)
{
    for ($i = 0; $i < 100; $i++) {
        $job         =new JobObject('MyJob', 'Kcloze\Jobs\Jobs\MyJob', 'testError', ['kcloze', time()]);
        $delay       =60 * 1000;
        $priority    =BaseTopicQueue::HIGH_LEVEL_3;
        $queue->push('MyJob', $job, $delay, $priority);
    }
}

function addTest4($queue)
{
    for ($i = 0; $i < 100; $i++) {
        $job         =new JobObject('MyJob2', 'Kcloze\Jobs\Jobs\MyJob2', 'test1', ['kcloze', time()]);
        $delay       =60 * 1000;
        $priority    =BaseTopicQueue::HIGH_LEVEL_1;
        $queue->push('MyJob', $job, $delay, $priority);
    }
    for ($i = 0; $i < 100; $i++) {
        $job         =new JobObject('MyJob2', 'Kcloze\Jobs\Jobs\MyJob2', 'test2', ['kcloze', time()]);
        $delay       =60 * 1000;
        $priority    =BaseTopicQueue::HIGH_LEVEL_1;
        $queue->push('MyJob', $job, $delay, $priority);
    }
    for ($i = 0; $i < 100; $i++) {
        $job         =new JobObject('MyJob2', 'Kcloze\Jobs\Jobs\MyJob2', 'testError', ['kcloze', time()]);
        $delay       =60 * 1000;
        $priority    =BaseTopicQueue::HIGH_LEVEL_1;
        $queue->push('MyJob', $job, $delay, $priority);
    }
}
