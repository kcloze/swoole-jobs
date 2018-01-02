<?php

/*
 * This file is part of PHP CS Fixer.
 * (c) kcloze <pei.greet@qq.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Kcloze\Jobs\Action;

use Kcloze\Jobs\Config;
use Kcloze\Jobs\JobObject;
use Kcloze\Jobs\Logs;
use Kcloze\Jobs\Utils;

class YafAction
{
    private $logger=null;

    private static $application =null;

    public function init()
    {
        $this->logger  = Logs::getLogger(Config::getConfig()['logPath'] ?? '', Config::getConfig()['logSaveFileApp'] ?? '');
    }

    //yaf运行参数配置
    //module  模块
    //controller 控制器
    //method  函数名
    //$job         =new JobObject('MyJob', 'module\controller', 'method', ['kcloze', time()]);
    public function start(JobObject $JobObject)
    {
        $this->init();
        $urlInfo             = explode('\\', $JobObject->jobClass);
        if (empty($urlInfo)) {
            Utils::catchError($this->logger, 'Yaf class must be config, please check');
            die('Yaf class must be config, please check');
        }
        $module              = $urlInfo[0];
        $controller          = $urlInfo[1];
        $action              = $JobObject->jobMethod;
        $params              = $JobObject->jobParams;
        try {
            if (empty(self::$application)) {
                defined('APPLICATION_PATH') ? '' : define('APPLICATION_PATH', APP_PATH);
                \Yaf\Loader::import(APP_PATH . '/application/init.php');
                self::$application = new \Yaf\Application(APP_PATH . '/conf/application.ini', ini_get('yaf.environ'));
            }
            //此处params为固定参数名称，在yafAction里进行获取
            //public function methodAction($params){}
            $request  = new \Yaf\Request\Simple('CLI', $module, $controller, $action, ['params'=>$params]);
            $response = $app->bootstrap()->getDispatcher()->returnResponse(true)->dispatch($request);
            unset($params);
            $this->logger->log('Action has been done, action content: ' . json_encode($JobObject));
        } catch (\Throwable $e) {
            Utils::catchError($this->logger, $e);
        } catch (\Exception $e) {
            Utils::catchError($this->logger, $e);
        }
    }
}
