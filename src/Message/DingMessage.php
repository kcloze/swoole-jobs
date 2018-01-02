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

class DingMessage
{
    private $apiUrl='https://oapi.dingtalk.com/robot/send';

    public function init()
    {
        $this->logger  = Logs::getLogger(Config::getConfig()['logPath'] ?? '', Config::getConfig()['logSaveFileApp'] ?? '');
    }

    public function send(string $content, string $token)
    {
        $this->init();
        if (!$token || !$content) {
            return false;
        }

        $message    = ['msgtype' => 'text', 'text' => ['content' => $content], 'at' => ['atMobiles' => [], 'isAtAll' => false]];

        $apiUrl      = $this->apiUrl . '?access_token=' . $token;
        $dataString  = json_encode($message);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json; charset=utf-8']);
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);
        $ret      = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $this->logger->log('[钉钉接口]请求自定义机器人消息接口,请求地址：' . var_export($apiUrl, true) . ',请求参数:' . var_export($message, true) . ',返回结果:' . var_export($ret, true) . '  httpcode: ' . $httpcode, 'info');

        return $ret;
    }
}
