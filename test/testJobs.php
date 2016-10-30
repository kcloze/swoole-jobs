<?php
date_default_timezone_set('Asia/Shanghai');

require __DIR__ . '/../vendor/autoload.php';

$config = [
    'queue'   => ['type' => 'redis', 'host' => '127.0.0.1', 'port' => 6379],
    'logPath' => __DIR__ . '/../log',
    'topics'  => ['MyJob', 'MyJob2'],
];

$queue = new Kcloze\Jobs\Redis($config['queue']);

//jobs的topic需要在配置文件里面定义，并且一次性注册进去
$queue->addTopics($config['topics']);
$topics = $queue->getTopics();
var_dump($topics);

//uuid和jobAction必须得有
for ($i = 0; $i < 1000; $i++) {
    $uuid      = $queue->uuid();
    $data      = ['uuid' => $uuid, 'jobAction' => 'helloAction', 'title' => 'kcloze', 'time' => time()];
    $topicName = 'MyJob';
    $queue->push($topicName, $data);
    echo $uuid . " ok\n";
    //$result = $queue->pop($topicName);
    //var_dump($result);
}
for ($i = 0; $i < 1000; $i++) {
    $uuid      = $queue->uuid();
    $data      = ['uuid' => $uuid, 'jobAction' => 'errorAction', 'title' => 'kcloze', 'time' => time()];
    $topicName = 'MyJob';
    $queue->push($topicName, $data);
    echo $uuid . " ok\n";
    //$result = $queue->pop($topicName);
    //var_dump($result);
}
// for ($i = 0; $i < 1000; $i++) {
//     $result = $queue->pop($topicName);
//     var_dump($result);
// }
