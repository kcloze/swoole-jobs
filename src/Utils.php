<?php

/*
 * This file is part of PHP CS Fixer.
 *  * (c) kcloze <pei.greet@qq.com>
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
        if (stristr(PHP_OS, 'Linux')) {
            return static::getServerMemoryUsageForLinux();
        }

        return (memory_get_usage() / 1048576) . ' MB';
    }

    /**
     * Get Server Cpu Usage.
     *
     * @return string
     */
    public static function getServerCpuUsage()
    {
        $load = sys_getloadavg();

        return $load[0];
    }

    private static function getServerMemoryUsageForLinux()
    {
        $free    = shell_exec('free');
        $free    = (string) trim($free);
        $freeArr = explode("\n", $free);
        $mem     = explode(' ', $freeArr[1]);
        $mem     = array_filter($mem);
        $mem     = array_merge($mem);

        return $mem[2] / $mem[1] * 100;
    }
}
