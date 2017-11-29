<?php

/*
 * This file is part of PHP CS Fixer.
 * (c) kcloze <pei.greet@qq.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Kcloze\Jobs;

class Config
{
    private static $config=[];

    public static function setConfig($config)
    {
        self::$config=$config;
    }

    public static function getConfig()
    {
        return self::$config;
    }
}
