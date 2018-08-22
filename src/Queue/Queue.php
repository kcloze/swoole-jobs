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
    public static $_instance=[];

    public static function getQueue(array $config, Logs $logger)
    {
        $classQueue=$config['class'] ?? '\Kcloze\Jobs\Queue\RedisTopicQueue';
        if (is_callable([$classQueue, 'getConnection'])) {
            //最多尝试连接3次
            for ($i=0; $i < 3; ++$i) {
                $connection=static::getInstance($classQueue, $config, $logger);
                if ($connection && is_object($connection)) {
                    // $logger->log('connect...,retry=' . ($i + 1), 'info');
                    break;
                }
                $logger->log('connect...,retry=' . ($i + 1), 'error', 'error');
            }

            return $connection;
        }
        $logger->log('queue connection is lost', 'error', 'error');

        return false;
    }

    /**
     * queue连接实体 单例模式.
     *
     * @param mixed $class
     * @param mixed $config
     * @param mixed $logger
     *
     * @return object 类对象
     */
    public static function getInstance($class, $config, $logger)
    {
        //static $_instance=[];
        $pid             =getmypid();
        $key             = md5($pid . $class . serialize($config));
        if (!isset(static::$_instance[$key])) {
            static::$_instance[$key]=$class::getConnection($config, $logger);
            if (!is_object(static::$_instance[$key])) {
                //异常抛出
                throw new \Exception('class name:' . $class . ' not exists');
            }
        }
        if (static::$_instance[$key]->isConnected()) {
            return static::$_instance[$key];
        }
        static::$_instance[$key]=null;
        $logger->log('queue instance is null', 'error', 'error');

        return false;
    }
}
