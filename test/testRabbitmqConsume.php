<?php


define('APP_PATH', __DIR__ . '/..');

date_default_timezone_set('Asia/Shanghai');

require APP_PATH . '/vendor/autoload.php';

use Enqueue\AmqpExt\AmqpConnectionFactory;
use Interop\Amqp\AmqpQueue;
use Interop\Amqp\AmqpTopic;
use Interop\Amqp\Impl\AmqpBind;

$config = require_once APP_PATH . '/config.php';

$factory = new AmqpConnectionFactory($config['job']['queue']);
$context = $factory->createContext();

$topic = $context->createTopic($config['job']['queue']['exchange']);
$topic->addFlag(AmqpTopic::FLAG_DURABLE);
$topic->setType(AmqpTopic::TYPE_FANOUT);
//$topic->setArguments(['alternate-exchange' => 'foo']);

$context->deleteTopic($topic);
$context->declareTopic($topic);

while (true) {
    $fooQueue = $context->createQueue('foo');
    $fooQueue->addFlag(AmqpQueue::FLAG_DURABLE);
    $count =$context->declareQueue($fooQueue);

    $consumer = $context->createConsumer($fooQueue);
    if ($m = $consumer->receive(1)) {
        $result=$m->getBody();
        $consumer->acknowledge($m);
    }

    var_dump($count, $result);
    sleep(2);
}

// $context->deleteQueue($fooQueue);
// $context->declareQueue($fooQueue);

// $context->bind(new AmqpBind($topic, $fooQueue));

// $barQueue = $context->createQueue('bar');
// $barQueue->addFlag(AmqpQueue::FLAG_DURABLE);

// $context->deleteQueue($barQueue);
// $context->declareQueue($barQueue);

// $context->bind(new AmqpBind($topic, $barQueue));

// $message = $context->createMessage('Hello Bar!');

// while (true) {
//     $context->createProducer()->send($fooQueue, $message);
//     $context->createProducer()->send($barQueue, $message);
// }

echo 'Done' . "\n";
