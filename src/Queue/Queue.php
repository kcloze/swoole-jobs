<?php

/*
 * This file is part of PHP CS Fixer.
 * (c) kcloze <pei.greet@qq.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Kcloze\Jobs\Queue;

use Kcloze\Jobs\Config;

class Queue
{
    protected static $connection = null;

    public static function getQueue()
    {
        if (isset(self::$connection) && self::$connection !== null) {
            return self::$connection;
        }
        //job相关配置
        $config=Config::getConfig()['job']['queue'] ?? [];

        if (isset($config['type']) && $config['type'] == 'redis') {
            $redis = new \Redis();
            $redis->pconnect($config['host'], $config['port']);
            self::$connection = new RedisTopicQueue($redis);
        } elseif (isset($config['type']) && $config['type'] == 'rabbitmq') {
            try {
                $conn = new \AMQPConnection();
                $conn->setHost($config['host']);
                $conn->setLogin($config['login']);
                $conn->setPassword($config['pwd']);
                $conn->setVhost($config['vHost']);
                $conn->connect();
                $channel          = new \AMQPChannel($conn);
                $exchange         = new \AMQPExchange($channel);
                $queue            = new \AMQPQueue($channel);
                self::$connection = new RabbitmqTopicQueue(['conn'=>$conn, 'queue' => $queue, 'exchange' => $exchange]);
            } catch (\Exception $e) {
                echo $e->getMessage();
            }
        } else {
            echo 'you must add queue config' . PHP_EOL;
            exit;
        }

        return self::$connection;
    }
}
