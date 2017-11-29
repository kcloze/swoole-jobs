<?php

namespace Kcloze\Jobs\Queue;

use Kcloze\Jobs\Config;

class Queue
{
    protected static $connection=null;

    public static function getQueue()
    {
        if (isset(self::$connection) && self::$connection !== null) {
            return self::$connection;
        }
        //job相关配置
        $config=Config::getConfig()['job']['queue'] ?? [];

        if (isset($config['type']) && $config['type'] == 'redis') {
            $redis                  = new \Redis();
            $redis->connect($config['host'], $config['port']);
            self::$connection       = new RedisTopicQueue($redis);
        } elseif (isset($config['type']) && $config['type'] == 'rabbitmq') {
            try {
                $conn = new \AMQPConnection();
                $conn->setHost($config['host']);
                $conn->setLogin($config['login']);
                $conn->setPassword($config['pwd']);
                $conn->setVhost($config['vHost']);
                $conn->connect();
                $channel  = new \AMQPChannel($conn);
                $exchange = new \AMQPExchange($channel);
                $queue    = new \AMQPQueue($exchange);
            } catch (\Exception $e) {
                echo $e->getMessage();
            }

            self::$connection       = new RabbitmqTopicQueue($queue);
        } else {
            echo 'you must add queue config' . PHP_EOL;
            exit;
        }

        return self::$connection;
    }
}
