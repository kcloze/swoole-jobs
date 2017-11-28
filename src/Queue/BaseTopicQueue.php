<?php

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

    public function push($topic, $value)
    {
    }

    public function pop($topic)
    {
    }
}
