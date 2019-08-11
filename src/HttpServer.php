<?php

/*
 * This file is part of Swoole-jobs
 * (c) kcloze <pei.greet@qq.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Kcloze\Jobs;

class HttpServer
{
    public static $instance;

    public $http;
    public static $get;
    public static $post;
    public static $header;
    public static $server;
    public $response = null;
    private $application;

    public function __construct($config=[])
    {
        $host =$config['httpServer']['host'] ?? '0.0.0.0';
        $port =$config['httpServer']['port'] ?? 9501;
        $http = new \Swoole\Http\Server($host, $port);

        $http->set(
            $config['httpServer']['settings']
        );

        $http->on('WorkerStart', [$this, 'onWorkerStart']);

        $http->on('request', function ($request, $response) {
            date_default_timezone_set('Asia/Shanghai');

            //define('SWOOLE_JOBS_ROOT_PATH', __DIR__ . '/..');
            //捕获异常
            register_shutdown_function([$this, 'handleFatal']);
            //请求过滤
            if ('/favicon.ico' == $request->server['path_info'] || '/favicon.ico' == $request->server['request_uri']) {
                return $response->end();
            }
            $this->response = $response;
            if (isset($request->server)) {
                HttpServer::$server = $request->server;
                foreach ($request->server as $key => $value) {
                    $_SERVER[strtoupper($key)] = $value;
                }
            }
            if (isset($request->header)) {
                HttpServer::$header = $request->header;
            }
            if (isset($request->get)) {
                HttpServer::$get = $request->get;
                foreach ($request->get as $key => $value) {
                    $_GET[$key] = $value;
                }
            }
            if (isset($request->post)) {
                HttpServer::$post = $request->post;
                foreach ($request->post as $key => $value) {
                    $_POST[$key] = $value;
                }
            }
            ob_start();
            //实例化slim对象
            try {
                $router = new \Kcloze\Jobs\Router();
                $router->run();
            } catch (\Exception $e) {
                var_dump($e);
            }
            $result = ob_get_contents();
            ob_end_clean();
            $response->end($result);
            unset($result, $router,$_GET,$_POST,$_SERVER);
        });

        $http->start();
    }

    /**
     * Fatal Error的捕获.
     */
    public function handleFatal()
    {
        $error = error_get_last();
        if (!isset($error['type'])) {
            return;
        }

        switch ($error['type']) {
            case E_ERROR:
            case E_PARSE:
            case E_DEPRECATED:
            case E_CORE_ERROR:
            case E_COMPILE_ERROR:
                break;
            default:
                return;
        }
        $message = $error['message'];
        $file    = $error['file'];
        $line    = $error['line'];
        $log     = "\n异常提示：$message ($file:$line)\nStack trace:\n";
        $trace   = debug_backtrace(1);

        foreach ($trace as $i => $t) {
            if (!isset($t['file'])) {
                $t['file'] = 'unknown';
            }
            if (!isset($t['line'])) {
                $t['line'] = 0;
            }
            if (!isset($t['function'])) {
                $t['function'] = 'unknown';
            }
            $log .= "#$i {$t['file']}({$t['line']}): ";
            if (isset($t['object']) && is_object($t['object'])) {
                $log .= get_class($t['object']) . '->';
            }
            $log .= "{$t['function']}()\n";
        }
        if (isset($_SERVER['REQUEST_URI'])) {
            $log .= '[QUERY] ' . $_SERVER['REQUEST_URI'];
        }

        if ($this->response) {
            $this->response->status(500);
            $this->response->end($log);
        }

        unset($this->response);
    }

    public function onWorkerStart()
    {
        require __DIR__ . '/../vendor/autoload.php';
        // session_start();
    }

    public static function getInstance($config)
    {
        if (!self::$instance) {
            self::$instance = new self($config);
        }

        return self::$instance;
    }
}
