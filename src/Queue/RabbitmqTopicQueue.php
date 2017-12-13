<?php

/*
 * This file is part of PHP CS Fixer.
 * (c) kcloze <pei.greet@qq.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Kcloze\Jobs\Queue;

use Enqueue\AmqpExt\AmqpContext;
use Interop\Amqp\AmqpQueue;
use Interop\Amqp\AmqpTopic;

class RabbitmqTopicQueue extends BaseTopicQueue
{
    const EXCHANGE    ='php.amqp.ext';
    //队列优先级
    const HIGH_LEVEL_1=1;
    const HIGH_LEVEL_2=2;
    const HIGH_LEVEL_3=3;
    const HIGH_LEVEL_4=4;
    const HIGH_LEVEL_5=5;

    public $queue   =null;
    private $context=null;

    /**
     * RabbitmqTopicQueue constructor.
     * 使用依赖注入的方式.
     *
     * @param array $queue
     * @param mixed $exchange
     */
    public function __construct(AmqpContext $context, $exchange)
    {
        $rabbitTopic  = $context->createTopic($exchange ?? self::EXCHANGE);
        $rabbitTopic->addFlag(AmqpTopic::FLAG_DURABLE);
        $rabbitTopic->setType(AmqpTopic::TYPE_FANOUT);
        $context->declareTopic($rabbitTopic);
        $this->context = $context;
    }

    /*
     * push message to queue.
     *
     * @param [string] $topic
     * @param [sting]  $value
     * @param [int]    $delay    延迟毫秒
     * @param [int]    $priority 优先级
     * @param [int]    $expiration      过期毫秒
     */
    public function push($topic, $value, $delay=0, $priority=self::HIGH_LEVEL_1, $expiration=0)
    {
        $queue   = $this->createQueue($topic);
        $message = $this->context->createMessage(serialize($value));
        $delay && $message->setExpiration($delay);
        $priority && $message->setPriority($priority);
        $expiration && $message->setTimestamp($expiration);

        $result=$this->context->createProducer()->send($queue, $message);

        return $result;
    }

    public function pop($topic)
    {
        $queue    = $this->createQueue($topic);
        $consumer = $this->context->createConsumer($queue);
        if ($m = $consumer->receive(1)) {
            $result=$m->getBody();
            $consumer->acknowledge($m);
        }

        return !empty($result) ? unserialize($result) : null;
    }

    //这里的topic跟rabbitmq不一样，其实就是队列名字
    public function len($topic)
    {
        $queue = $this->createQueue($topic);

        return $this->context->declareQueue($queue);
    }

    public function close()
    {
        $this->context->close();
    }

    private function createQueue($topic)
    {
        $queue = $this->context->createQueue($topic);
        $queue->addFlag(AmqpQueue::FLAG_DURABLE);

        return $queue;
    }
}
