<?php
/**
 *抽象queue基础类，不同的队列存储都需要继承此类
 */
namespace Kcloze\Jobs;

abstract class Queue
{
    public $topics = [];

    public function push($key, $value)
    {
    }

    public function pop($key)
    {
    }

    public function addTopics(array $topics)
    {
        $this->topics = $topics;
    }

    public function getTopics()
    {
        return $this->topics;
    }

    public function uuid()
    {
        $len     = 20;
        $hashStr = substr(str_shuffle(str_repeat('abcdefghijklmnopqrstuvwxyz0123456789', $len)), 0, $len);

        $uuid = md5(uniqid($hashStr, true) . microtime(true) . mt_rand(0, 1000));
        return $uuid;
    }

}
