<?php

/*
 * This file is part of PHP CS Fixer.
 * (c) kcloze <pei.greet@qq.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Kcloze\Jobs;

class Redis extends Queue
{
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
        return $this->redis->lPush($key, serialize($value));
    }

    public function pop($key)
    {
        $result = $this->redis->lPop($key);

        return $result ? unserialize($result) : false;
    }
}
