<?php

/*
 * This file is part of PHP CS Fixer.
 * (c) kcloze <pei.greet@qq.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Kcloze\Jobs;

class RabbitmqTopicQueue extends BaseTopicQueue
{
    /**
     * RabbitmqTopicQueue constructor.
     * 使用依赖注入的方式
     *
     * @param array $AMQPQueue
     */
    public function __construct(array $AMQPQueue)
    {
        /**
         * AMQPQueue 的简单初始化方式
         *
         * try {
         * $conn = new \AMQPConnection();
         * $conn->setHost($host);
         * $conn->setLogin($login);
         * $conn->setPassword($pwd);
         * $conn->setVhost($vHost);
         * $conn->connect();
         * $channel = new \AMQPChannel($conn);
         * $exchange = new \AMQPExchange($channel);
         * $queue = new \AMQPQueue($exchange);
         * //    return ['queue' => $queue, 'exchange' => $exchange];
         * } catch (\Exception $e) {
         * echo $e->getMessage();
         * }
         */
        $this->queue = $AMQPQueue;
    }

    public function push($topic, $value)
    {
        /* @var \AMQPQueue $queue */
        $queue = $this->queue['queue'];
        $queue->setName($topic);
        $queue->setFlags(AMQP_DURABLE);
        $queue->declareQueue();

        /* @var \AMQPExchange $exchange */
        $exchange = $this->queue['exchange'];
        $result = $exchange->publish(serialize($value), $topic);

        return $result;
    }

    public function pop($topic)
    {
        /* @var \AMQPQueue $queue */
        $queue = $this->queue['queue'];
        $queue->setName($topic);
        $queue->setFlags(AMQP_DURABLE);
        $queue->declareQueue();
        $message = $queue->get(AMQP_AUTOACK);
        $result = null;
        if ($message) {
            $result = $message->getBody();
        }

        return $result ? unserialize($result) : null;
    }
}
