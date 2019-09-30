<?php

/*
 * This file is part of Swoole-jobs
 * (c) kcloze <pei.greet@qq.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Kcloze\Jobs\Queue;

use Kcloze\Jobs\JobObject;
use Kcloze\Jobs\Logs;

abstract class BaseTopicQueue implements TopicQueueInterface
{
    //队列优先级
    const HIGH_LEVEL_1=1;
    const HIGH_LEVEL_2=2;
    const HIGH_LEVEL_3=3;
    const HIGH_LEVEL_4=4;
    const HIGH_LEVEL_5=5;

    public $topics = [];
    public $queue  = null;

    public function getTopics()
    {
        //根据key大到小排序，并保持索引关系
        arsort($this->topics);

        return array_values($this->topics);
    }

    public function setTopics(array $topics)
    {
        $this->topics = $topics;
    }

    abstract public static function getConnection(array $config, Logs $logger);

    abstract public function push($topic, JobObject $job): string;

    abstract public function pop($topic);

    abstract public function ack(): bool;

    /**
     * 清空队列，保留队列名.
     *
     * @param [type] $topic
     */
    abstract public function purge($topic);

    /**
     * 删除队列.
     *
     * @param [type] $topic
     */
    abstract public function delete($topic);

    abstract public function len($topic): int;

    abstract public function close();

    abstract public function isConnected();
}
