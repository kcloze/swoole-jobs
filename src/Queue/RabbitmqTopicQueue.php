<?php

/*
 * This file is part of PHP CS Fixer.
 * (c) kcloze <pei.greet@qq.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Kcloze\Jobs\Queue;

use Enqueue\AmqpExt\AmqpConnectionFactory;
use Enqueue\AmqpExt\AmqpContext;
use Enqueue\AmqpTools\RabbitMqDelayPluginDelayStrategy;
use Enqueue\AmqpTools\RabbitMqDlxDelayStrategy;
use Interop\Amqp\AmqpQueue;
use Interop\Amqp\AmqpTopic;
use Kcloze\Jobs\JobObject;

class RabbitmqTopicQueue extends BaseTopicQueue
{
    const EXCHANGE    ='php.amqp.ext';

    public $queue   =null;
    public $context =null;

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
        //$rabbitTopic->setType(AmqpTopic::TYPE_FANOUT);
        $context->declareTopic($rabbitTopic);
        $this->context = $context;
    }

    public static function getConnection(array $config)
    {
        try {
            $factory          = new AmqpConnectionFactory($config);
            $context          = $factory->createContext();
            $connection       = new self($context, $config['exchange'] ?? null);
        } catch (\Exception $e) {
            echo $e->getMessage() . PHP_EOL;

            return false;
        } catch (\Throwable $e) {
            echo $e->getMessage() . PHP_EOL;

            return false;
        }

        return $connection;
    }

    /*
     * push message to queue.
     *
     * @param [string] $topic
     * @param [JobObject]  $job
     * @param [int]    $delay    延迟毫秒
     * @param [int]    $priority 优先级
     * @param [int]    $expiration      过期毫秒
     */

    /**
     * push message to queue.
     *
     * @param [type]    $topic
     * @param JobObject $job
     * @param int       $delayStrategy
     */
    public function push($topic, JobObject $job, $delayStrategy=1): string
    {
        if (!$this->isConnected()) {
            return '';
        }

        $queue        = $this->createQueue($topic);
        $message      = $this->context->createMessage(serialize($job));
        $producer     =$this->context->createProducer();
        $delay        = $job->jobExtras['delay'] ?? 0;
        $priority     = $job->jobExtras['priority'] ?? BaseTopicQueue::HIGH_LEVEL_1;
        $expiration   = $job->jobExtras['expiration'] ?? 0;
        if ($delay > 0) {
            //有两种策略实现延迟队列：rabbitmq插件,对消息创建延迟队列；自带队列延迟，变像实现，每个不同的过期时间都会创建队列(不推荐)
            if ($delayStrategy == 1) {
                $delayStrategyObj= new RabbitMqDelayPluginDelayStrategy();
            } else {
                $delayStrategyObj= new RabbitMqDlxDelayStrategy();
            }
            $producer->setDelayStrategy($delayStrategyObj);
            $producer->setDeliveryDelay($delay);
        }
        if ($priority) {
            $producer->setPriority($priority);
        }
        if ($expiration > 0) {
            $producer->setTimeToLive($expiration);
        }

        $result=$producer->send($queue, $message);

        return $job->uuid ?? '';
    }

    public function pop($topic)
    {
        if (!$this->isConnected()) {
            return;
        }

        $queue    = $this->createQueue($topic);
        $consumer = $this->context->createConsumer($queue);
        if ($m = $consumer->receive(1)) {
            $result=$m->getBody();
            $consumer->acknowledge($m);
        }

        return !empty($result) ? unserialize($result) : null;
    }

    //这里的topic跟rabbitmq不一样，其实就是队列名字
    public function len($topic): int
    {
        if (!$this->isConnected()) {
            return 0;
        }

        $queue = $this->createQueue($topic);
        $len   =$this->context->declareQueue($queue);

        return $len ?? 0;
    }

    public function close()
    {
        if (!$this->isConnected()) {
            return;
        }

        $this->context->close();
    }

    public function isConnected()
    {
        return $this->context->getExtChannel()->getConnection()->isConnected();
    }

    private function createQueue($topic)
    {
        try {
            $queue = $this->context->createQueue($topic);
            $queue->addFlag(AmqpQueue::FLAG_DURABLE);
            $len   =$this->context->declareQueue($queue);
        } catch (\Throwable $e) {
            echo 'queue Error: ' . $e->getMessage() . PHP_EOL;
        } catch (\Exception $e) {
            echo 'queue Error: ' . $e->getMessage() . PHP_EOL;
        }

        return $queue;
    }
}
