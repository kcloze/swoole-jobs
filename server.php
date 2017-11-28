<?php

$cmd         = $argv[1] ?? 'start';
$processFile = 'test/testProcess.php';
$processMark = ':swooleProcessTopicQueueJob';

// 使用 PHP_OS 判断系统, 参照 server.sh 即可

// 服务管理
stop($processMark);
if ($cmd == 'start' || $cmd == 'restart') {
    sleep(1);
    start($processFile);
}

function start($processFile)
{
    echo 'starting swooler server...', "\n";
    exec("php $processFile &>> log/server.log", $output, $code);
    $status = $code == 0 ? 'success' : 'fail';
    echo "server start $status \n";
}

function stop($processMark)
{
    shell_exec("ps aux|grep '$processMark'|grep -v 'grep'|awk '{print $1}'|xargs kill -9"); // awk '{print $1}', 根据 linux 平台不同, 可能是 $2
    $processNum = shell_exec("ps aux|grep '$processMark'|grep -v 'grep'|awk '{print $1}'|wc -l");
    $status     = $processNum == 0 ? 'success' : 'fail';
    echo "server stop $status \n";
}
