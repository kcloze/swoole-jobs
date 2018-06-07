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
    'logPath'           => __DIR__ . '/log',
    'logSaveFileApp'    => 'application.log', //默认log存储名字
    'logSaveFileWorker' => 'crontab.log', // 进程启动相关log存储名字
    'pidPath'           => __DIR__ . '/log',
    'sleep'             => 2, // 队列没消息时，暂停秒数
    'queueMaxNum'       => 10, // 队列达到一定长度，启动动态子进程个数发和送消息提醒
    'excuteTime'        => 3600, // 子进程最长执行时间，防止内存泄漏
    'queueTickTimer'    => 1000 * 15, //一定时间间隔（毫秒）检查队列长度;默认10秒钟
    'messageTickTimer'  => 1000 * 180, //一定时间间隔（毫秒）发送消息提醒;默认3分钟
    'processName'       => ':swooleTopicQueue', // 设置进程名, 方便管理, 默认值 swooleTopicQueue
    //job任务相关
    'job'         => [
        'topics'  => [
            ['name'=>'MyJob', 'workerMinNum'=>3, 'workerMaxNum'=>30],
            ['name'=> 'MyJob2', 'workerMinNum'=>3, 'workerMaxNum'=>20],
            ['name'=> 'MyJob3', 'workerMinNum'=>1, 'workerMaxNum'=>1],
            //不需要swoole-jobs消费的队列，只往队列里面写数据
            ['name'=> 'TojavaConsumer'],
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
       'token'  => '***your-dingding-token***',
   ],
];
