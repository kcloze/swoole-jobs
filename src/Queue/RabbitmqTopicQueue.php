<?php

/*
 * This file is part of PHP CS Fixer.
 * (c) kcloze <pei.greet@qq.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Kcloze\Jobs\Queue;

class RabbitmqTopicQueue extends BaseTopicQueue
{
    /**
     * RabbitmqTopicQueue constructor.
     * 使用依赖注入的方式.
     *
     * @param array $queue
     */
    public function __construct(array $queue)
    {
        $this->queue = $queue;
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
        $result   = $exchange->publish(serialize($value), $topic);

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
        $result  = null;
        if ($message) {
            $result = $message->getBody();
        }

        return !empty($result) ? unserialize($result) : null;
    }

    public function len($topic)
    {
        $queue = $this->queue['queue'];
        $queue->setName($topic);
        $queue->setFlags(AMQP_DURABLE);

        return $queue->declareQueue();
    }

    public function close()
    {
        $this->queue['conn']->disconnect();
    }
}
