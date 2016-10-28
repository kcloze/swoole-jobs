<?php
/**
 *抽象queue基础类，不同的队列存储都需要继承此类
 */
namespace Kcloze\Jobs;

abstract class Queue
{

    public function push($key, $value)
    {
    }

    public function pop($key)
    {
    }

    public function addTopic($key)
    {
    }

    public function getTopics()
    {
    }
}
