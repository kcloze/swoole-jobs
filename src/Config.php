<?php

/*
 * This file is part of PHP CS Fixer.
 * (c) kcloze <pei.greet@qq.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Kcloze\Jobs;

class Config
{
    private static $config=[];

    public static function setConfig($config)
    {
        self::$config=$config;
    }

    public static function getConfig()
    {
        return self::$config;
    }

    /**
     * 从配置数组中获取相关值
     *      $config=[
     *                  ['name'=>'MyJob', 'workerMinNum'=>1, 'workerMaxNum'=>3, 'queueMaxNum'=>10000, 'queueMaxNumForProcess' => 100, 'autoAckBeforeJobStart'=>false],
     *                  ['name'=> 'MyJob2', 'workerMinNum'=>1, 'workerMaxNum'=>3],
     *                  ['name'=> 'MyJob3', 'workerMinNum'=>1, 'workerMaxNum'=>1],
     *                  ['name'=> 'DefaultClassMethod.test1', 'workerMinNum'=>1, 'workerMaxNum'=>2, 'defaultJobClass'=>'DefaultClassMethod', 'defaultJobMethod'=>'test1']
     *               ].
     *
     * @param mixed $config
     * @param mixed $topic
     * @param mixed $name
     *  */

    public static function getTopicConfig($config, $topic, $name)
    {
        $key=array_search($topic, array_column($config, 'name'), true);

        return $config[$key][$name] ?? false;
    }
}
