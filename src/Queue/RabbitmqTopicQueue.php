<?php

/*
 * This file is part of Swoole-jobs
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
use Kcloze\Jobs\Logs;
use Kcloze\Jobs\Serialize;
use Kcloze\Jobs\Utils;

class RabbitmqTopicQueue extends BaseTopicQueue
{
    const EXCHANGE    ='php.amqp.ext';

    public $context         =null;
    private $logger         =null;
    private $consumer       =null;
    private $message        =null;

    /**
     * RabbitmqTopicQueue constructor.
     * 使用依赖注入的方式.
     *
     * @param array $queue
     * @param mixed $exchange
     */
    public function __construct(AmqpContext $context, $exchange, Logs $logger)
    {
        $this->logger  = $logger;
        $rabbitTopic   = $context->createTopic($exchange ?? self::EXCHANGE);
        $rabbitTopic->addFlag(AmqpTopic::FLAG_DURABLE);
        //$rabbitTopic->setType(AmqpTopic::TYPE_FANOUT);
        $context->declareTopic($rabbitTopic);
        $this->context = $context;
    }

    public static function getConnection(array $config, Logs $logger)
    {
        try {
            $factory          = new AmqpConnectionFactory($config);
            $context          = $factory->createContext();
            $connection       = new self($context, $config['exchange'] ?? null, $logger);
        } catch (\AMQPConnectionException $e) {
            Utils::catchError($logger, $e);

            return false;
        } catch (\Throwable $e) {
            Utils::catchError($logger, $e);

            return false;
        } catch (\Exception $e) {
            Utils::catchError($logger, $e);

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
     * @param mixed     $serializeFunc
     */
    public function push($topic, JobObject $job, $delayStrategy=1, $serializeFunc='php'): string
    {
        if (!$this->isConnected()) {
            return '';
        }

        $queue        = $this->createQueue($topic);
        if (!is_object($queue)) {
            //对象有误 则直接返回空
            return '';
        }
        $message      = $this->context->createMessage(Serialize::serialize($job, $serializeFunc));
        $producer     =$this->context->createProducer();
        $delay        = $job->jobExtras['delay'] ?? 0;
        $priority     = $job->jobExtras['priority'] ?? BaseTopicQueue::HIGH_LEVEL_1;
        $expiration   = $job->jobExtras['expiration'] ?? 0;
        if ($delay > 0) {
            //有两种策略实现延迟队列：rabbitmq插件,对消息创建延迟队列；自带队列延迟，变像实现，每个不同的过期时间都会创建队列(不推荐)
            if (1 == $delayStrategy) {
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

    /**
     * 入队列 .
     *
     * @param [type] $topic
     * @param string $unSerializeFunc 反序列化类型
     */
    public function pop($topic, $unSerializeFunc='php')
    {
        if (!$this->isConnected()) {
            return;
        }
        //reset consumer and message properties
        $this->consumer=null;
        $this->message=null;

        $queue    = $this->createQueue($topic);
        $consumer = $this->context->createConsumer($queue);

        if ($m = $consumer->receive(1)) {
            $result         =$m->getBody();
            $this->consumer =$consumer;
            $this->message =$m;
            //判断字符串是否是php序列化的字符串，目前只允许serialzie和json两种
            $unSerializeFunc=Serialize::isSerial($result) ? 'php' : 'json';

            return !empty($result) ? Serialize::unserialize($result, $unSerializeFunc) : null;
        }
    }

    public function ack(): boolean
    {
        if ($this->consumer && $this->message) {
            $this->consumer->acknowledge($this->message);

            return true;
        }
        throw new \Exception(self::get_class() . ' properties consumer or message is null !');

        return false;
    }

    //这里的topic跟rabbitmq不一样，其实就是队列名字
    public function len($topic): int
    {
        if (!$this->isConnected()) {
            return 0;
        }

        $queue = $this->createQueue($topic);
        if (!is_object($queue)) {
            //对象有误 则直接返回空
            return -1;
        }
        $len   =$this->context->declareQueue($queue);

        return $len ?? 0;
    }

    //清空mq队列数据
    public function purge($topic)
    {
        if (!$this->isConnected()) {
            return 0;
        }
        $queue = $this->createQueue($topic);

        return $this->context->purgeQueue($queue);
    }

    //删除mq队列
    public function delete($topic)
    {
        if (!$this->isConnected()) {
            return 0;
        }
        $queue = $this->createQueue($topic);

        return $this->context->deleteQueue($queue);
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
            $i = 0;
            do {
                $queue = $this->context->createQueue($topic);
                ++$i;
                if (($queue && $this->isConnected()) || $i >= 3) {
                    //成功 或 链接超过3次则跳出
                    break;
                }
                sleep(1); //延迟1秒
            } while (!$queue);
            $queue->addFlag(AmqpQueue::FLAG_DURABLE);
            $len   =$this->context->declareQueue($queue);
        } catch (\Throwable $e) {
            Utils::catchError($this->logger, $e);

            return false;
        } catch (\Exception $e) {
            Utils::catchError($this->logger, $e);

            return false;
        }

        return $queue;
    }
}
