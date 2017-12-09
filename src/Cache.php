<?php

/*
 * This file is part of PHP CS Fixer.
 * (c) php-team@yaochufa <php-team@yaochufa.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Kcloze\Jobs;

use Exception;
use Redis;

class Cache
{
    /**
     * @var Redis
     */
    private $handler;
    private $config;

    /**
     * @param $config
     */
    public function __construct($config)
    {
        $this->config = $config;
        $this->connect();
    }

    /**
     * 调用redis.
     *
     * @param $method
     * @param $arguments
     *
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        if (!$this->handler) {
            $this->connect();
        }

        return call_user_func_array([$this->handler, $method], $arguments);
    }

    public function get($key, $serialize = false)
    {
        if (!$this->handler) {
            $this->connect();
        }
        if ($serialize === false) {
            isset($this->config['serialize']) && $serialize = $this->config['serialize'];
        }

        return $serialize ? unserialize($this->handler->get($key)) : $this->handler->get($key);
    }

    public function set($key, $value, $timeout = 0, $serialize = false)
    {
        if (!$this->handler) {
            $this->connect();
        }
        if ($serialize === false) {
            isset($this->config['serialize']) && $serialize = $this->config['serialize'];
        }
        $value = $serialize ? serialize($value) : $value;

        return $this->handler->set($key, $value, $timeout);
    }

    public function hget($key, $hash, $serialize = false)
    {
        if (!$this->handler) {
            $this->connect();
        }
        if ($serialize === false) {
            isset($this->config['serialize']) && $serialize = $this->config['serialize'];
        }

        return $serialize ? unserialize($this->handler->hget($key, $hash)) : $this->handler->hget($key, $hash);
    }

    public function hset($key, $hash, $value, $serialize = false)
    {
        if (!$this->handler) {
            $this->connect();
        }
        if ($serialize === false) {
            isset($this->config['serialize']) && $serialize = $this->config['serialize'];
        }
        $value = $serialize ? serialize($value) : $value;

        return $this->handler->hset($key, $hash, $value);
    }

    /**
     * 创建handler.
     *
     * @throws Exception
     */
    private function connect()
    {
        $this->handler = new Redis();
        if (isset($this->config['keep-alive']) && $this->config['keep-alive']) {
            $fd = $this->handler->pconnect($this->config['host'], $this->config['port'], 60);
        } else {
            $fd = $this->handler->connect($this->config['host'], $this->config['port']);
        }
        if (isset($this->config['password'])) {
            $this->handler->auth($this->config['password']);
        }
        if (!$fd) {
            throw new Exception("Unable to connect to redis host: {$this->config['host']},port: {$this->config['port']}");
        }
        //统一key前缀
        if (isset($this->config['preKey']) && !empty($this->config['preKey'])) {
            $this->handler->setOption(Redis::OPT_PREFIX, $this->config['preKey']);
        }
    }
}
