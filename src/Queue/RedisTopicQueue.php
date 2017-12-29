<?php

/*
 * This file is part of PHP CS Fixer.
 * (c) kcloze <pei.greet@qq.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Kcloze\Jobs\Queue;

use Kcloze\Jobs\JobObject;
use Kcloze\Jobs\Logs;
use Kcloze\Jobs\Utils;

class RedisTopicQueue extends BaseTopicQueue
{
    private $logger =null;

    /**
     * RedisTopicQueue constructor.
     * 使用依赖注入的方式.
     *
     * @param \Redis $redis
     */
    public function __construct(\Redis $redis, Logs $logger)
    {
        $this->queue   = $redis;
        $this->logger  = $logger;
    }

    public static function getConnection(array $config, Logs $logger)
    {
        try {
            $redis = new \Redis();
            $redis->connect($config['host'], $config['port']);
            if (isset($config['password']) && !empty($config['password'])) {
                $redis->auth($config['password']);
            }
        } catch (\Throwable $e) {
            Utils::catchError($logger, $e);

            return false;
        } catch (\Exception $e) {
            Utils::catchError($logger, $e);

            return false;
        }
        $connection = new self($redis, $logger);

        return $connection;
    }

    /**
     * push message to queue.
     *
     * @param [type]    $topic
     * @param JobObject $job
     */
    public function push($topic, JobObject $job): string
    {
        if (!$this->isConnected()) {
            return '';
        }

        $this->queue->lPush($topic, serialize($job));

        return $job->uuid ?? '';
    }

    public function pop($topic)
    {
        if (!$this->isConnected()) {
            return;
        }

        $result = $this->queue->lPop($topic);

        return !empty($result) ? unserialize($result) : null;
    }

    public function len($topic): int
    {
        if (!$this->isConnected()) {
            return 0;
        }

        return (int) $this->queue->lSize($topic) ?? 0;
    }

    public function close()
    {
        if (!$this->isConnected()) {
            return;
        }

        $this->queue->close();
    }

    public function isConnected()
    {
        try {
            $this->queue->ping();
        } catch (\Throwable $e) {
            Utils::catchError($this->logger, $e);

            return false;
        } catch (\Exception $e) {
            Utils::catchError($this->logger, $e);

            return false;
        }

        return true;
    }
}
