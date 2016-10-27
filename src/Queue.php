<?php
/**
 *
 * 队列封装
 */

namespace Kcloze\Jobs;

class Queue
{
    const TOPIC_LIST_NAME = 'topic_list';

    private $redis = null;

    public function __construct(array $config)
    {
        $this->redis = new \Redis();
        try {
            $this->redis->connect($config['host'], $config['port']);
        } catch (Exception $e) {
            echo $e->getMessage() . "\n";
        }
    }

    public function push($key, $value)
    {
        return $this->redis->lPush($key, $value);
    }

    public function pop($key)
    {
        return $this->redis->lPop($key);
    }

    public function addTopic($key)
    {
        return $this->redis->sAdd(self::TOPIC_LIST_NAME, $key);
    }

    public function getTopics($key)
    {
        return $this->redis->sort(self::TOPIC_LIST_NAME);
    }

}
