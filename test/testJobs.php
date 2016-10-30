<?php
date_default_timezone_set('Asia/Shanghai');

require __DIR__ . '/../vendor/autoload.php';

$config = [
    'queue'   => ['type' => 'redis', 'host' => '127.0.0.1', 'port' => 6379],
    'logPath' => __DIR__ . '/../log',
    'topics'  => ['MyJob', 'MyJob2'],
];

$jobs = new Kcloze\Jobs\Jobs($config);

if (!$jobs->queue) {
    die("queue object is null\n");
}

//jobs的topic需要在配置文件里面定义，并且一次性注册进去
$topics = $jobs->queue->getTopics();
var_dump($topics);

//uuid和jobAction必须得有
for ($i = 0; $i < 100; $i++) {
    $topicName = 'MyJob';
    $uuid      = $jobs->queue->uuid();
    $data      = [
        'uuid'  => $uuid, 'jobName' => $topicName, 'jobAction' => 'helloAction',
        'title' => 'kcloze', 'time' => time(),
    ];
    $jobs->queue->push($topicName, $data);
    echo $uuid . " ok\n";
    //$result = $jobs->queue->pop($topicName);
    //var_dump($result);
}
for ($i = 0; $i < 100; $i++) {
    $topicName = 'MyJob';
    $uuid      = $jobs->queue->uuid();
    $data      = [
        'uuid'  => $uuid, 'jobName' => $topicName, 'jobAction' => 'errorAction',
        'title' => 'kcloze', 'time' => time(),
    ];
    $jobs->queue->push($topicName, $data);
    echo $uuid . " ok\n";
    //$result = $jobs->queue->pop($topicName);
    //var_dump($result);
}
// for ($i = 0; $i < 1000; $i++) {
//     $result = $jobs->queue->pop($topicName);
//     var_dump($result);
// }
