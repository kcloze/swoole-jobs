<?php

/*
 * This file is part of PHP CS Fixer.
 * (c) kcloze <pei.greet@qq.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define('SWOOLE_JOBS_ROOT_PATH', __DIR__ . '/..');

use Kcloze\Jobs\JobObject;
use Kcloze\Jobs\Logs;
use Kcloze\Jobs\Queue\BaseTopicQueue;
use Kcloze\Jobs\Queue\Queue;
use PHPUnit\Framework\TestCase;

class Test extends TestCase
{
    private $queue=null;

    public function __construct()
    {
        $config              = require SWOOLE_JOBS_ROOT_PATH . '/config.php';
        $logger              = Logs::getLogger($config['logPath'] ?? '', $config['logSaveFileApp'] ?? '');
        $this->queue         =Queue::getQueue($config['job']['queue'], $logger);
    }

    public function testQueue()
    {
        $len                   =$this->queue->len('MyJob');

        $rand                   =mt_rand(0, 100);
        $delay                  =$rand * 1000;
        $priority               =BaseTopicQueue::HIGH_LEVEL_1;
        $jobExtras['delay']     =$delay;
        $jobExtras['priority']  =$priority;
        $job                    =new JobObject('MyJob', '\Kcloze\Jobs\Jobs\MyJob', 'test1', ['kcloze', time()], $jobExtras);
        $result                 =$this->queue->push('MyJob', $job, 1, 'json');
        $len2                   =$this->queue->len('MyJob');

        $this->assertGreaterThan($len, $len2);
    }

    public function testPushAndPop()
    {
        $stack = [];
        $this->assertSame(0, count($stack));

        array_push($stack, 'foo');
        $this->assertSame('foo', $stack[count($stack) - 1]);
        $this->assertSame(1, count($stack));

        $this->assertSame('foo', array_pop($stack));
        $this->assertSame(0, count($stack));
    }
}
