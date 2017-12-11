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
    public static function getQueue()
    {

        //job相关配置
        $config=Config::getConfig()['job']['queue'] ?? [];

        if (isset($config['type']) && $config['type'] == 'redis') {
            try {
                $redis = new \Redis();
                $redis->connect($config['host'], $config['port']);
            } catch (\Exception $e) {
                die($e->getMessage() . PHP_EOL);
            }
            $connection = new RedisTopicQueue($redis);
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
                $connection       = new RabbitmqTopicQueue(['conn'=>$conn, 'queue' => $queue, 'exchange' => $exchange]);
            } catch (\Exception $e) {
                die($e->getMessage() . PHP_EOL);
            }
        } else {
            echo 'you must add queue config' . PHP_EOL;
            exit;
        }

        return $connection;
    }
}
