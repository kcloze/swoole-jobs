<?php
date_default_timezone_set('Asia/Shanghai');

require __DIR__ . '/../vendor/autoload.php';

$config = [
    'queue'   => ['host' => '127.0.0.1', 'port' => 6379],
    'logPath' => __DIR__ . '/../log',
];

$queue = new Kcloze\Jobs\Redis($config);

//jobs必须要存在helloAction方法，否则无效
$jobName = 'MyJob';
$queue->addTopic($jobName);
$topics = $queue->getTopics();
var_dump($topics);

for ($i = 0; $i < 10; $i++) {
    $data = ['jobAction' => 'helloAction', 'title' => 'kcloze', 'time' => time()];
    $queue->push($jobName, $data);
    echo "ok\n";
    //$result = $queue->pop($jobName);
    //var_dump($result);
}
for ($i = 0; $i < 10; $i++) {
    $data = ['jobAction' => 'errorAction', 'title' => 'kcloze', 'time' => time()];
    $queue->push($jobName, $data);
    echo "ok\n";
    //$result = $queue->pop($jobName);
    //var_dump($result);
}
$result = $queue->pop($jobName);
var_dump($result);
// for ($i = 0; $i < 100; $i++) {
//     $data = $queue->pop('hello');
//     $jobs = new Jobs();
//     $jobs->run('hello', $data);
// }
