<?php
/**
 *
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
