<?php

/*
 * This file is part of PHP CS Fixer.
 * (c) kcloze <pei.greet@qq.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Kcloze\Jobs\Queue;

class Queue
{
    public static function getQueue($config)
    {
        if (isset($config['class']) && $config['class']) {
            if (is_callable([$config['class'], 'getConnection'])) {
                return $config['class']::getConnection($config);
            }
            echo 'you must add queue config' . PHP_EOL;
            exit;
        }
    }
}
