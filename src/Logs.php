<?php
namespace Kcloze\Jobs;

class Logs
{
    const LEVEL_TRACE   = 'trace';
    const LEVEL_WARNING = 'warning';
    const LEVEL_ERROR   = 'error';
    const LEVEL_INFO    = 'info';
    const LEVEL_PROFILE = 'profile';
    const MAX_LOGS      = 10000;
    //单个类型log
    private $logs        = array();
    private $logCount    = 0;
    private $logPath     = '';
    public $rotateByCopy = true;
    public $maxLogFiles  = 5;
    public $maxFileSize  = 100; // in MB

    public function __construct($logPath)
    {
        $this->logPath = $logPath;
    }
    public function formatLogMessage($message, $level, $category, $time)
    {
        return @date('Y/m/d H:i:s', $time) . " [$level] [$category] $message\n";
    }
    public function log($message, $level = 'info', $category = 'application', $flush = false)
    {
        $this->logs[$category][] = array($message, $level, $category, microtime(true));
        $this->logCount++;
        if ($this->logCount >= self::MAX_LOGS || true == $flush) {
            $this->flush($category);
        }
    }
    public function processLogs()
    {
        $logsAll["application"] = "[runing time]: " . microtime(true) . "\n";
        foreach ((array) $this->logs as $key => $logs) {
            $logsAll[$key] = '';
            foreach ((array) $logs as $log) {
                $logsAll[$key] .= $this->formatLogMessage($log[0], $log[1], $log[2], $log[3]);
            }
        }
        return $logsAll;
    }
    /**
     *
     * 写日志到文件
     */
    public function flush()
    {
        if ($this->logCount <= 0) {
            return false;
        }
        $logsAll = $this->processLogs();
        $this->write($logsAll);
        $this->logs     = array();
        $this->logCount = 0;
    }

    /**
     * [write 根据日志类型写到不同的日志文件]
     * @return [type] [description]
     */
    public function write($logsAll)
    {
        if (empty($logsAll)) {
            return;
        }
        //$this->logPath = ROOT_PATH . 'src/runtime/';
        if (!is_dir($this->logPath)) {
            self::mkdir($this->logPath, array(), true);
        }
        foreach ($logsAll as $key => $value) {
            if (empty($key)) {
                continue;
            }
            $fileName = $this->logPath . '/' . $key . '.log';

            if (($fp = @fopen($fileName, 'a')) === false) {
                throw new Exception("Unable to append to log file: {$fileName}");
            }
            @flock($fp, LOCK_EX);

            if (@filesize($fileName) > $this->maxFileSize * 1024 * 1024) {
                $this->rotateFiles($fileName);
                @flock($fp, LOCK_UN);
                @fclose($fp);
            } else {
                @fwrite($fp, $value);
                @flock($fp, LOCK_UN);
                @fclose($fp);
            }

        }
    }
    /**
     * Shared environment safe version of mkdir. Supports recursive creation.
     * For avoidance of umask side-effects chmod is used.
     *
     * @param string $dst path to be created
     * @param array $options newDirMode element used, must contain access bitmask
     * @param boolean $recursive whether to create directory structure recursive if parent dirs do not exist
     * @return boolean result of mkdir
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
    /**
     * Rotates log files.
     */
    protected function rotateFiles($file)
    {
        for ($i = $this->maxLogFiles; $i >= 0; --$i) {
            // $i == 0 is the original log file
            $rotateFile = $file . ($i === 0 ? '' : '.' . $i);
            var_dump($rotateFile);
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
}
