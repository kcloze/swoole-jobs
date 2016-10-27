<?php
date_default_timezone_set('Asia/Shanghai');
define('DS', DIRECTORY_SEPARATOR);
define('APP_PATH', realpath(dirname(__FILE__)) . DS . '..' . DS);

require_once APP_PATH . 'src/Jobs.php';
require_once APP_PATH . 'src/Queue.php';
require_once APP_PATH . 'src/Process.php';

//启动
$process = new Kcloze\Jobs\Process();
$process->start();
