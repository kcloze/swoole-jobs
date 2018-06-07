<?php

/*
 * This file is part of PHP CS Fixer.
 * (c) kcloze <pei.greet@qq.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define('SWOOLE_JOBS_ROOT_PATH', __DIR__ . '/..');

date_default_timezone_set('Asia/Shanghai');

require SWOOLE_JOBS_ROOT_PATH . '/vendor/autoload.php';

use Kcloze\Jobs\JobObject;
use Kcloze\Jobs\Serialize;

$jobObj=new JobObject('MyJob', '\Kcloze\Jobs\Jobs\MyJob', 'test1', ['kcloze', time()], $jobExtras=[]);

$str=Serialize::serialize($jobObj, 'php');

$obj=Serialize::unSerialize($str, 'php');
var_dump($obj);

$str=Serialize::serialize($jobObj, 'json');
$obj=Serialize::unSerialize($str, 'json');
var_dump($obj);

$str2='{"uuid":"MyJob5b10cd256b53a.1527827749.4396","topic":"MyJob","jobClass":"\\Kcloze\\Jobs\\Jobs\\MyJob","jobMethod":"test1","jobParams":["kcloze",1527827749],"jobExtras":{"delay":84000,"priority":1}}';
$obj =Serialize::unSerialize($str, 'json');
var_dump($obj);
