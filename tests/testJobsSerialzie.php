<?php

/*
 * This file is part of Swoole-jobs
 * (c) kcloze <pei.greet@qq.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define('SWOOLE_JOBS_ROOT_PATH', __DIR__ . '/..');

date_default_timezone_set('Asia/Shanghai');

require SWOOLE_JOBS_ROOT_PATH . '/vendor/autoload.php';

use Kcloze\Jobs\JobObject;
use Kcloze\Jobs\Logs;
use Kcloze\Jobs\Queue\BaseTopicQueue;
use Kcloze\Jobs\Queue\Queue;

$config        = require_once SWOOLE_JOBS_ROOT_PATH . '/config.php';
$logger        = Logs::getLogger($config['logPath'] ?? '', $config['logSaveFileApp'] ?? '');
$queue         =Queue::getQueue($config['job']['queue'], $logger);

//var_dump($queue);

$queue->setTopics($config['job']['topics']);

if (!$queue) {
    die("queue object is null\n");
}

//jobs的topic需要在配置文件里面定义，并且一次性注册进去
$topics = $queue->getTopics();
//var_dump($topics); exit;
$times=10;
addTest1($queue, $times);
addTest2($queue, $times);
addTest3($queue, $times);
addTest4($queue, $times);

//往topic为MyJob的任务增加执行job
function addTest1($queue, $times)
{
    for ($i = 0; $i < $times; ++$i) {
        $rand                  =mt_rand(0, 100);
        $delay                 =$rand * 1000;
        $priority              =BaseTopicQueue::HIGH_LEVEL_1;
        ////$jobExtras['delay']    =$delay;
        $jobExtras['priority'] =$priority;
        $job                   =new JobObject('MyJob', '\Kcloze\Jobs\Jobs\MyJob', 'test1', ['kcloze', time()], $jobExtras);
        // var_dump($job);
        // exit;
        $result                =$queue->push('MyJob', $job, 1, 'php');
        var_dump($result);
    }
}

function addTest2($queue, $times)
{
    for ($i = 0; $i < $times; ++$i) {
        $rand                  =mt_rand(0, 100);
        $delay                 =$rand * 1000;
        $priority              =BaseTopicQueue::HIGH_LEVEL_2;
        //$jobExtras['delay']    =$delay;
        $jobExtras['priority'] =$priority;
        $job                   =new JobObject('MyJob', '\Kcloze\Jobs\Jobs\MyJob', 'test2', ['kcloze', time(), 'oop'], $jobExtras);
        $result                =$queue->push('MyJob', $job, 1, 'php');
        var_dump($result);
    }
}

function addTest3($queue, $times)
{
    for ($i = 0; $i < $times; ++$i) {
        $rand                  =mt_rand(0, 100);
        $delay                 =$rand * 1000;
        $priority              =BaseTopicQueue::HIGH_LEVEL_3;
        //$jobExtras['delay']    =$delay;
        $jobExtras['priority'] =$priority;
        $job                   =new JobObject('MyJob', '\Kcloze\Jobs\Jobs\MyJob', 'testError', ['kcloze', time()], $jobExtras);
        $result                =$queue->push('MyJob', $job, 1, 'php');
        var_dump($result);
    }
}

function addTest4($queue, $times)
{
    for ($i = 0; $i < $times; ++$i) {
        $rand                  =mt_rand(0, 100);
        $delay                 =$rand * 1000;
        $priority              =BaseTopicQueue::HIGH_LEVEL_2;
        //$jobExtras['delay']    =$delay;
        $jobExtras['priority'] =$priority;
        $job                   =new JobObject('MyJob2', '\Kcloze\Jobs\Jobs\MyJob2', 'test1', ['kcloze', time()], $jobExtras);
        $result                =$queue->push('MyJob2', $job, 1, 'php');
        var_dump($result);
    }
    for ($i = 0; $i < $times; ++$i) {
        $rand                  =mt_rand(0, 100);
        $delay                 =$rand * 1000;
        $priority              =BaseTopicQueue::HIGH_LEVEL_2;
        //$jobExtras['delay']    =$delay;
        $jobExtras['priority'] =$priority;
        $job                   =new JobObject('MyJob2', '\Kcloze\Jobs\Jobs\MyJob2', 'test2', ['kcloze', time(), 'oop'], $jobExtras);
        $result                =$queue->push('MyJob2', $job, 1, 'php');
        var_dump($result);
    }
    for ($i = 0; $i < $times; ++$i) {
        $rand                  =mt_rand(0, 100);
        $delay                 =$rand * 1000;
        $priority              =BaseTopicQueue::HIGH_LEVEL_2;
        //$jobExtras['delay']    =$delay;
        $jobExtras['priority'] =$priority;
        $job                   =new JobObject('MyJob2', '\Kcloze\Jobs\Jobs\MyJob2', 'testError', ['kcloze', time()], $jobExtras);
        $result                =$queue->push('MyJob2', $job, 1, 'php');
        var_dump($result);
    }
}
