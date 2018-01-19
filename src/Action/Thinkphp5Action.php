<?php

namespace Kcloze\Jobs\Action;

use Kcloze\Jobs\Config;
use Kcloze\Jobs\JobObject;
use Kcloze\Jobs\Logs;
use Kcloze\Jobs\Utils;

/**
 * thinkphp框架整合
 * @package Kcloze\Jobs\Action
 */
class Thinkphp5Action extends BaseAction
{
    private $logger = null;
    private static $application =null;

    public function init()
    {
        $this->logger = Logs::getLogger(Config::getConfig()['logPath'] ?? '', Config::getConfig()['logSaveFileApp'] ?? '');
    }

    public function start(JobObject $JobObject)
    {
        $this->init();
        $jobClass = $JobObject->jobClass;
        $jobMethod = $JobObject->jobMethod;
        $jobParams = $JobObject->jobParams;
        //载入框架
        //self::loadFrame();
        try {
            if (empty(self::$application)) {
                // 加载ThinkPHP基础文件
                require_once SWOOLE_JOBS_ROOT_PATH . '/thinkphp/base.php';
                // 定义应用目录
                defined('APP_PATH') ? '' : define('APP_PATH', SWOOLE_JOBS_ROOT_PATH . '/application/');
                // 执行应用
                \think\App::initCommon();
                self::$application = \think\Console::init(false);
            }

            $obj = new $jobClass();
            if (is_object($obj) && method_exists($obj, $jobMethod)) {
                call_user_func_array([$obj, $jobMethod], $jobParams);
            } else {
                $this->logger->log('Action obj not find: ' . json_encode($JobObject), 'error');
            }
        } catch (\Throwable $e) {
            Utils::catchError($this->logger, $e);
        } catch (\Exception $e) {
            Utils::catchError($this->logger, $e);
        }

        $this->logger->log('Action has been done, action content: ' . json_encode($JobObject));
    }
}
