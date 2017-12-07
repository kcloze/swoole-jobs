<?php

/*
 * This file is part of PHP CS Fixer.
 * (c) kcloze <pei.greet@qq.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Kcloze\Jobs\Queue;

class RedisTopicQueue extends BaseTopicQueue
{
    /**
     * RedisTopicQueue constructor.
     * 使用依赖注入的方式.
     *
     * @param \Redis $redis
     */
    public function __construct(\Redis $redis)
    {
        /*
         * $redis 的简单初始化方法
         * try {
         * $redis = new \Redis();
         * $redis->connect($host, $port);
         * $redis->auth($pwd);
         * $redis->select($db);
         * } catch (\Exception $e) {
         * echo $e->getMessage();
         * }
         */
        $this->queue = $redis;
    }

    public function push($topic, $value)
    {
        return $this->queue->lPush($topic, serialize($value));
    }

    public function pop($topic)
    {
        $result = $this->queue->lPop($topic);

        return !empty($result) ? @unserialize($result) : null;
    }

    public function len($topic)
    {
        return (int) $this->queue->lLen($topic) ?? 0;
    }
}
