<?php

namespace Kcloze\Jobs;

interface TopicQueueInterface
{
    /**
     * @return array a array of topics
     */
    public function getTopics();

    /**
     * @param array $topics
     */
    public function setTopics(array $topics);

    /**
     * @param $topic
     * @param $value
     */
    public function push($topic, $value);

    /**
     * @param $topic
     * @return mixed
     */
    public function pop($topic);
}