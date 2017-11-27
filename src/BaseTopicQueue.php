<?php


namespace Kcloze\Jobs;


abstract class BaseTopicQueue implements TopicQueueInterface
{
    public $topics = [];
    public $queue = null;

    public function getTopics()
    {
        return $this->topics;
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