<?php
date_default_timezone_set('Asia/Shanghai');

require __DIR__ . '/../vendor/autoload.php';

$config = [
    'queue'   => ['host' => '127.0.0.1', 'port' => 6379],
    'logPath' => __DIR__ . '/../log',
];

$queue = new Kcloze\Jobs\Redis($config['queue']);

//jobs必须要存在helloAction方法，否则无效
$jobName = 'MyJob';
$queue->addTopic($jobName);
$topics = $queue->getTopics();
var_dump($topics);

//uuid和jobAction必须得有
for ($i = 0; $i < 1000; $i++) {
    $uuid = $queue->uuid();
    $data = ['uuid' => $uuid, 'jobAction' => 'helloAction', 'title' => 'kcloze', 'time' => time()];
    $queue->push($jobName, $data);
    echo $uuid . " ok\n";
    //$result = $queue->pop($jobName);
    //var_dump($result);
}
for ($i = 0; $i < 1000; $i++) {
    $uuid = $queue->uuid();
    $data = ['uuid' => $uuid, 'jobAction' => 'errorAction', 'title' => 'kcloze', 'time' => time()];
    $queue->push($jobName, $data);
    echo $uuid . " ok\n";
    //$result = $queue->pop($jobName);
    //var_dump($result);
}
// for ($i = 0; $i < 1000; $i++) {
//     $result = $queue->pop($jobName);
//     var_dump($result);
// }
