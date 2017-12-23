<?php

/*
 * This file is part of PHP CS Fixer.
 * (c) kcloze <pei.greet@qq.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Kcloze\Jobs\Queue;

class Queue
{
    public static function getQueue($config)
    {
        $classQueue=$config['class'] ?? '\Kcloze\Jobs\Queue\RedisTopicQueue';
        if (is_callable([$classQueue, 'getConnection'])) {
            return $classQueue::getConnection($config);
        }
        echo 'you must add queue config' . PHP_EOL;
        exit;
    }
}
