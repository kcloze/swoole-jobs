<?php

return $config = [
    //log目录
    'logPath'      => __DIR__ . '/log',
    'pidPath'      => __DIR__ . '/log',
    'workerNum'    => 5, // 工作进程数, 默认值 5
    'usleep'       => 10000, //每次topic消费完之后停留毫秒数，线上环境不能过大
    'processName'  => ':swooleTopicQueue', // 设置进程名, 方便管理, 默认值 swooleTopicQueue
    //job任务相关
    'job'         => [
        'topics'  => [
            //key值越大，优先消费
            23=> 'MyJob', 86=>'MyJob2', 8=>'MyJob3',
        ],
        'queue'   => [
            'type' => 'redis',
            'host' => '127.0.0.1',
            'port' => 6379,
        ],
   ],

];
