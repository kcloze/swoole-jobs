<?php

/*
 * This file is part of PHP CS Fixer.
 * (c) kcloze <pei.greet@qq.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Kcloze\Jobs\Queue;

abstract class BaseTopicQueue implements TopicQueueInterface
{
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

    /**
     * push message to queue.
     *
     * @param [string] $topic
     * @param [sting]  $value
     * @param [int]    $delay    延迟秒数
     * @param [string] $priority 优先级
     * @param [int]    $ttl      超时时间
     */
    public function push($topic, $value, $delay=0, $priority='', $ttl=0)
    {
    }

    public function pop($topic)
    {
    }

    public function len($topic)
    {
    }

    public function close()
    {
    }
}
