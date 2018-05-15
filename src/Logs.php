<?php

/*
 * This file is part of PHP CS Fixer.
 * (c) kcloze <pei.greet@qq.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Kcloze\Jobs;

class Logs
{
    const LEVEL_TRACE          = 'trace';
    const LEVEL_WARNING        = 'warning';
    const LEVEL_ERROR          = 'error';
    const LEVEL_INFO           = 'info';
    const LEVEL_PROFILE        = 'profile';
    const MAX_LOGS             = 10000;

    public $rotateByCopy       = true;
    public $maxLogFiles        = 5;
    public $maxFileSize        = 1000; // in MB

    //系统日志标识
    private $logSystem       = 'swoole-jobs';

    private $logPath      = '';
    //单个类型log
    private $logs                 = [];
    private $logCount             = 0;
    //默认log文件存储名
    private $logSaveFileApp       = 'application.log';

    private static $instance=null;

    public function __construct($logPath, $logSaveFileApp='', $logSystem = '')
    {
        if (empty($logPath)) {
            die('config logPath must be set!' . PHP_EOL);
        }
        Utils::mkdir($logPath);
        $this->logPath = $logPath;
        if ($logSaveFileApp) {
            $this->logSaveFileApp = $logSaveFileApp;
        }

        $logSystem && $this->logSystem = $logSystem;
    }

    /**
     * 获取日志实例.
     *
     * @$logPath
     *
     * @param mixed $logPath
     * @param mixed $logSaveFileApp
     */
    public static function getLogger($logPath='', $logSaveFileApp='', $logSystem = '')
    {
        if (isset(self::$instance) && self::$instance !== null) {
            return self::$instance;
        }
        self::$instance=new self($logPath, $logSaveFileApp, $logSystem);

        return self::$instance;
    }

    /**
     * 格式化日志信息.
     *
     * @param mixed $message
     * @param mixed $level
     * @param mixed $category
     * @param mixed $time
     */
    public function formatLogMessage($message, $level, $category, $time)
    {
        return @date('Y/m/d H:i:s', $time) . "$this->logSystem [$level] [$category] \n $message \n";
    }

    /**
     * 日志分类处理.
     *
     * @param mixed $message
     * @param mixed $level
     * @param mixed $category
     * @param mixed $flush
     */
    public function log($message, $level = 'info', $category = '', $flush = true)
    {
        if (empty($category)) {
            $category=$this->logSaveFileApp;
        }
        $this->logs[$category][]      = [$message, $level, $category, microtime(true)];
        $this->logCount++;
        if ($this->logCount >= self::MAX_LOGS || true == $flush) {
            $this->flush($category);
        }
    }

    /**
     * 日志分类处理.
     */
    public function processLogs()
    {
        $logsAll=[];
        foreach ((array) $this->logs as $key => $logs) {
            $logsAll[$key] = '';
            foreach ((array) $logs as $log) {
                $logsAll[$key] .= $this->formatLogMessage($log[0], $log[1], $log[2], $log[3]);
            }
        }

        return $logsAll;
    }

    /**
     * 写日志到文件.
     */
    public function flush()
    {
        if ($this->logCount <= 0) {
            return false;
        }
        $logsAll = $this->processLogs();
        $this->write($logsAll);
        $this->logs     = [];
        $this->logCount = 0;
    }

    /**
     * [write 根据日志类型写到不同的日志文件].
     *
     * @param $logsAll
     *
     * @throws \Exception
     */
    public function write($logsAll)
    {
        if (empty($logsAll)) {
            return;
        }
        //$this->logPath = ROOT_PATH . 'src/runtime/';
        if (!is_dir($this->logPath)) {
            self::mkdir($this->logPath, [], true);
        }
        foreach ($logsAll as $key => $value) {
            if (empty($key)) {
                continue;
            }
            $fileName = $this->logPath . '/' . $key;

            if (($fp = @fopen($fileName, 'a')) === false) {
                throw new \Exception("Unable to append to log file: {$fileName}");
            }
            @flock($fp, LOCK_EX);

            if (@filesize($fileName) > $this->maxFileSize * 1024 * 1024) {
                $this->rotateFiles($fileName);
            }
            @fwrite($fp, $value);
            @flock($fp, LOCK_UN);
            @fclose($fp);
        }
    }

    /**
     * Rotates log files.
     *
     * @param mixed $file
     */
    protected function rotateFiles($file)
    {
        for ($i = $this->maxLogFiles; $i >= 0; --$i) {
            // $i == 0 is the original log file
            $rotateFile = $file . ($i === 0 ? '' : '.' . $i);
            //var_dump($rotateFile);
            if (is_file($rotateFile)) {
                // suppress errors because it's possible multiple processes enter into this section
                if ($i === $this->maxLogFiles) {
                    @unlink($rotateFile);
                } else {
                    if ($this->rotateByCopy) {
                        @copy($rotateFile, $file . '.' . ($i + 1));
                        if ($fp = @fopen($rotateFile, 'a')) {
                            @ftruncate($fp, 0);
                            @fclose($fp);
                        }
                    } else {
                        @rename($rotateFile, $file . '.' . ($i + 1));
                    }
                }
            }
        }
    }

    /**
     * Shared environment safe version of mkdir. Supports recursive creation.
     * For avoidance of umask side-effects chmod is used.
     *
     * @param string $dst       path to be created
     * @param array  $options   newDirMode element used, must contain access bitmask
     * @param bool   $recursive whether to create directory structure recursive if parent dirs do not exist
     *
     * @return bool result of mkdir
     *
     * @see mkdir
     */
    private static function mkdir($dst, array $options, $recursive)
    {
        $prevDir = dirname($dst);
        if ($recursive && !is_dir($dst) && !is_dir($prevDir)) {
            self::mkdir(dirname($dst), $options, true);
        }
        $mode = isset($options['newDirMode']) ? $options['newDirMode'] : 0777;
        $res  = mkdir($dst, $mode);
        @chmod($dst, $mode);

        return $res;
    }
}
