<?php

/*
 * This file is part of PHP CS Fixer.
 * (c) kcloze <pei.greet@qq.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Kcloze\Jobs;

class JobObject
{
    public $uuid        =''; //job uuid
    public $topic       =''; //job 队列名
    public $jobClass    =''; //job 执行类
    public $jobMethod   =''; //job 执行方法
    public $jobParams   =[]; //job参数
    public $jobExtras   =[]; //附件信息，delay/expiration/priority等

    public function __construct(string $topic, string $jobClass, string $jobMethod, array $jobParams=[], array $jobExtras=[], $uuid='')
    {
        $this->uuid       =$uuid ?? uniqid($topic) . '.' . Utils::getMillisecond();
        $this->topic      =$topic;
        $this->jobClass   =$jobClass;
        $this->jobMethod  =$jobMethod;
        $this->jobParams  =$jobParams;
        $this->jobExtras  =$jobExtras;
    }
}
