<?php

/*
 * This file is part of Swoole-jobs
 * (c) kcloze <pei.greet@qq.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Kcloze\Jobs;

/*
 * This file is part of PHP CS Fixer.
 * (c) kcloze <pei.greet@qq.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
class Swoole
{
    public static $instance;

    public function __construct($config=[])
    {
        $host   =$config['server']['host'] ?? '0.0.0.0';
        $port   =$config['server']['port'] ?? 9502;
        $server = new \Swoole\Server($host, $port);

        isset($config['server']['settings']) ?? $server->set(
            $config['server']['settings']
        );

        $server->on('WorkerStart', [$this, 'onWorkerStart']);

        $server->on('receive', function (Swoole\Server $serv, $fd, $reactor_id, $data) {
            echo "[#".$serv->worker_id."]\tClient[$fd]: $data\n";
        });
        $server->start();
        
    }

    public function onWorkerStart()
    {
        
    }

    public static function getInstance($config)
    {
        if (!self::$instance) {
            self::$instance = new self($config);
        }

        return self::$instance;
    }
}
