<?php
/**
 *
 * rabbitmq做队列服务
 * 如果需要使用rabbitmq/zeromq等其他队列，可以继承queue类
 */

namespace Kcloze\Jobs;

use Kcloze\Jobs\Queue;

class Rabbitmq extends Queue
{
    const TOPIC_LIST_NAME = 'topic_list';

    private $connection = null;
    private $channel    = null;
    private $exchange   = null;
    private $queue      = null;

    public function __construct(array $config)
    {
        try {
            $class = class_exists('AMQPConnection', false);
            if ($class) {
                $this->connection = new AMQPConnection();
                $this->connection->setHost($config['host']);
                $this->connection->setLogin($config['login']);
                $this->connection->setPassword($config['password']);
                $this->connection->connect();
            } else {
                die('you need install pecl amqp extension');
            }
            $this->channel = new \AMQPChannel($connection);
            //AMQPC Exchange is the publishing mechanism
            $this->exchange = new \AMQPExchange($channel);

            $this->queue = new AMQPQueue($channel);

        } catch (Exception $e) {
            echo $e->getMessage() . "\n";
        }
    }

    public function push($key, $value)
    {
        $this->queue->setName($key);
        $this->queue->setFlags(AMQP_DURABLE);
        $this->queue->declareQueue();
        $result = $this->exchange->publish($key, serialize($value));
        return $result;
    }

    public function pop($key)
    {
        $this->queue->setName($key);
        $this->queue->setFlags(AMQP_DURABLE);
        $this->queue->declareQueue();
        $result = $this->queue->get(AMQP_AUTOACK);
        return $result ? unserialize($result) : false;
    }

    public function addTopic($key)
    {
        //return $this->rabbitmq->sAdd(self::TOPIC_LIST_NAME, $key);
        $this->exchange->setName($exchange_name);
        $this->exchange->declareExchange();
    }

    public function getTopics()
    {
        //return $this->rabbitmq->sMembers(self::TOPIC_LIST_NAME);
    }

}
