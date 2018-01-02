<?php

/*
 * This file is part of PHP CS Fixer.
 * (c) kcloze <pei.greet@qq.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Kcloze\Jobs;

class Utils
{
    /**
     * 循环创建目录.
     *
     * @param mixed $path
     * @param mixed $recursive
     * @param mixed $mode
     */
    public static function mkdir($path, $mode=0777, $recursive=true)
    {
        if (!is_dir($path)) {
            mkdir($path, $mode, $recursive);
        }
    }

    public static function catchError(Logs $logger, $exception)
    {
        $error  = '错误类型：' . get_class($exception) . PHP_EOL;
        $error .= '错误代码：' . $exception->getCode() . PHP_EOL;
        $error .= '错误信息：' . $exception->getMessage() . PHP_EOL;
        $error .= '错误堆栈：' . $exception->getTraceAsString() . PHP_EOL;

        $logger && $logger->log($error, 'error');
    }

    public static function getMillisecond()
    {
        return microtime(true);
    }

    /**
     * Get Server Memory Usage.
     *
     * @return string
     */
    public static function getServerMemoryUsage()
    {
        return round(memory_get_usage(true) / (1024 * 1024), 2) . ' MB';
    }

    /**
     * Get Server load avg.
     *
     * @return string
     */
    public static function getSysLoadAvg()
    {
        $loadavg = function_exists('sys_getloadavg') ? array_map('round', sys_getloadavg(), [2]) : ['-', '-', '-'];

        return 'load average: ' . implode(', ', $loadavg);
    }
}
