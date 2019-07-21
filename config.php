<?php

/*
 * This file is part of PHP CS Fixer.
 * (c) kcloze <pei.greet@qq.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

return $config = [
    //项目/系统标识
    'system'            => 'swoole-jobs',
    //log目录
    'logPath'            => __DIR__ . '/log',
    'logSaveFileApp'     => 'application.log', //默认log存储名字
    'logSaveFileWorker'  => 'crontab.log', // 进程启动相关log存储名字
    'pidPath'            => __DIR__ . '/log',
    'sleep'              => 2, // 队列没消息时，暂停秒数
    'queueMaxNum'        => 10, // 队列达到一定长度，启动动态子进程个数发和送消息提醒
    'maxPopNum'          => 50, //子进程最多执行任务数，达到这个数量之后，自动退出
    'excuteTime'         => 600, // 子进程最长执行时间，防止内存泄漏
    'queueTickTimer'     => 1000 * 15, //一定时间间隔（毫秒）检查队列长度;默认10秒钟
    'messageTickTimer'   => 1000 * 180, //一定时间间隔（毫秒）发送消息提醒;默认3分钟
    'processName'        => ':swooleTopicQueue', // 设置进程名, 方便管理, 默认值 swooleTopicQueue
    //'eachJobExit'        => false, // true 开启； false 关闭；每个job执行完之后，主动exit,防止业务代码出现（正常不需要开启）

    //job任务相关
    'job'         => [
        //job相关属性
        'profile'=> [
            'maxTime'=> 3, //单个job最大执行时间
            'minTime'=> 0.0001, //单个job最少执行时间
        ],
        'topics'  => [
            ['name'=>'MyJob', 'workerMinNum'=>3, 'workerMaxNum'=>30, 'queueMaxNum'=>10000],
            ['name'=> 'MyJob2', 'workerMinNum'=>3, 'workerMaxNum'=>20],
            ['name'=> 'MyJob3', 'workerMinNum'=>1, 'workerMaxNum'=>1],
            ['name'=> 'DefaultClassMethod.test1', 'workerMinNum'=>1, 'workerMaxNum'=>2, 'defaultJobClass'=>'DefaultClassMethod', 'defaultJobMethod'=>'test1'],
            ['name'=> 'DefaultClassMethod.test2', 'workerMinNum'=>1, 'workerMaxNum'=>2, 'defaultJobClass'=>'DefaultClassMethod', 'defaultJobMethod'=>'test2'],
            //不需要swoole-jobs消费的队列，只往队列里面写数据
            //['name'=> 'TojavaConsumer'],
        ],
        // redis
        'queue'   => [
            'class'    => '\Kcloze\Jobs\Queue\RedisTopicQueue',
            'host'     => '127.0.0.1',
            'port'     => 6379,
            //'password'=> 'pwd',
        ],

        // rabbitmq
        // 'queue'   => [
        //     'class'         => '\Kcloze\Jobs\Queue\RabbitmqTopicQueue',
        //     'host'          => '192.168.9.24',
        //     'user'          => 'phpadmin',
        //     'pass'          => 'phpadmin',
        //     'port'          => '5671',
        //     'vhost'         => 'php',
        //     'exchange'      => 'php.amqp.ext',
        // ],
   ],
   //框架类型及装载类
   'framework' => [
       //可以自定义，但是该类必须继承\Kcloze\Jobs\Action\BaseAction
       'class'=> '\Kcloze\Jobs\Action\SwooleJobsAction',
   ],
   'message'=> [
        'class'  => '\Kcloze\Jobs\Message\DingMessage',
        'token'  => '6f5bf4dedc7698cdf3567f29ce5ebe5308a02b743d0f21cbe9c78e5417312206',
   ],
   'httpServer' => [
                'host'    => '0.0.0.0',
                'port'    => 9501,
                'settings'=> [
                    'worker_num'    => 3,
                    'daemonize'     => true,
                    'max_request'   => 10,
                    'dispatch_mode' => 1,
                    'pid_file'      => __DIR__ . '/log/server.pid',
                    'log_file'      => __DIR__ . '/log/server.log',
            ],
   ],
];
