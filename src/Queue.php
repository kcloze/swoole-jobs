<?php

/*
 * This file is part of PHP CS Fixer.
 * (c) kcloze <pei.greet@qq.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
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
