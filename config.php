<?php

return $config = [
    //log目录
    'logPath'      => __DIR__ . '/log',
    'pidPath'      => __DIR__ . '/log',
    'workerNum'    => 5, // 工作进程数, 默认值 5
    'processName'  => ':swooleTopicQueue', // 设置进程名, 方便管理, 默认值 swooleTopicQueue
    //job任务相关
    'job'         => [
        'topics'  => ['MyJob', 'MyJob2'],
        'queue'   => [
            'type' => 'redis',
            'host' => '127.0.0.1',
            'port' => 6379,
        ],
   ],

];
