<?php

/*
 * This file is part of Swoole-jobs
 * (c) kcloze <pei.greet@qq.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define('SWOOLE_JOBS_ROOT_PATH', __DIR__ . '/..');

use Kcloze\Jobs\Config;
use Kcloze\Jobs\JobObject;
use Kcloze\Jobs\Logs;
use Kcloze\Jobs\Queue\BaseTopicQueue;
use Kcloze\Jobs\Queue\Queue;
use PHPUnit\Framework\TestCase;

class QueueTest extends TestCase
{
    private $queue =null;
    private $config=null;

    public function __construct()
    {
        $this->config              = require SWOOLE_JOBS_ROOT_PATH . '/config.php';
        $logger                    = Logs::getLogger($this->config['logPath'] ?? '', $this->config['logSaveFileApp'] ?? '');
        $this->queue               =Queue::getQueue($this->config['job']['queue'], $logger);
    }

    public function testQueue()
    {
        $this->assertInternalType('object', $this->queue);

        $len                   =$this->queue->len('MyJob');

        $rand                   =mt_rand(0, 100);
        $delay                  =$rand * 1000;
        $priority               =BaseTopicQueue::HIGH_LEVEL_1;
        // $jobExtras['delay']     =$delay;
        // $jobExtras['priority']  =$priority;
        $job                    =new JobObject('MyJob', '\Kcloze\Jobs\Jobs\MyJob', 'test1', ['kcloze', time()]);
        $result                 =$this->queue->push('MyJob', $job, 1, 'json');
        $len2                   =$this->queue->len('MyJob');
        $this->assertGreaterThan($len, $len2);
        //删除队列
        $this->queue->purge('MyJob');
        $len = $this->queue->len('MyJob');
        $this->assertSame(0, $len);
        //清空队列
        $result         = $this->queue->push('MyJob', $job, 1, 'json');
        $this->queue->delete('MyJob');
        $len                   =$this->queue->len('MyJob');
        $this->assertSame(0, $len);
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

    public function testConfig()
    {
        $topics=$this->config['job']['topics'];
        $this->assertFalse(Config::getTopicConfig($topics, 'MyJob', 'autoAckBeforeJobStart'));
        $this->assertTrue(Config::getTopicConfig($topics, 'MyJob2', 'autoAckBeforeJobStart'));
    }
}
