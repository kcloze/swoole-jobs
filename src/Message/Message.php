<?php

/*
 * This file is part of PHP CS Fixer.
 * (c) kcloze <pei.greet@qq.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Kcloze\Jobs\Message;

use Kcloze\Jobs\Config;
use Kcloze\Jobs\Logs;

class Message
{
    public static function getMessage(array $config)
    {
        $logger      = Logs::getLogger(Config::getConfig()['logPath'] ?? '', Config::getConfig()['logSaveFileApp'] ?? '');
        $classMessage=$config['class'] ?? '\Kcloze\Jobs\Message\DingMessage';
        try {
            $message = new $classMessage();
        } catch (\Throwable $e) {
            Utils::catchError($logger, $e);
        } catch (\Exception $e) {
            Utils::catchError($logger, $e);
        }

        return $message;
    }
}
