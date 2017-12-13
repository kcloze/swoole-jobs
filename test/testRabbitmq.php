<?php


define('APP_PATH', __DIR__ . '/..');

date_default_timezone_set('Asia/Shanghai');

require APP_PATH . '/vendor/autoload.php';

use Enqueue\AmqpExt\AmqpConnectionFactory;
use Interop\Amqp\AmqpQueue;
use Interop\Amqp\AmqpTopic;
use Interop\Amqp\Impl\AmqpBind;

$config = [
    'host'   => '192.168.9.24',
    'user'   => 'phpadmin',
    'pass'   => 'phpadmin',
    'port'   => '5672',
    'vhost'  => 'php',
];

$factory = new AmqpConnectionFactory($config);
$context = $factory->createContext();

$topic = $context->createTopic('php.amqp.ext');
$topic->addFlag(AmqpTopic::FLAG_DURABLE);
$topic->setType(AmqpTopic::TYPE_FANOUT);
//$topic->setArguments(['alternate-exchange' => 'foo']);

$context->deleteTopic($topic);
$context->declareTopic($topic);

$message = $context->createMessage('Hello Bar!');
$message->setExpiration(60);
$message->setPriority('hight');

//var_dump($message); exit;

while (true) {
    $fooQueue = $context->createQueue('foo');
    $fooQueue->addFlag(AmqpQueue::FLAG_DURABLE);
    $count =$context->declareQueue($fooQueue);
    $result=$context->createProducer()->send($fooQueue, $message);
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
