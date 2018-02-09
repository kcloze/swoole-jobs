<?php

/*
 * This file is part of PHP CS Fixer.
 * (c) kcloze <pei.greet@qq.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Kcloze\Jobs\Queue;

use Kcloze\Jobs\Logs;

class Queue
{
    public static function getQueue(array $config, Logs $logger)
    {
        $classQueue=$config['class'] ?? '\Kcloze\Jobs\Queue\RedisTopicQueue';
        if (is_callable([$classQueue, 'getConnection'])) {
            //最多尝试连接3次
            for ($i=0; $i < 3; $i++) {
                $connection=$classQueue::getConnection($config, $logger);
                if (is_object($connection)) {
                    break;
                }
            }

            return $connection;
        }
        echo 'you must add queue config' . PHP_EOL;
        exit;
    }
}
