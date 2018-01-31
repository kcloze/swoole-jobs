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
use Kcloze\Jobs\Utils;

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
        try{
            $message      = ['msgtype' => 'text', 'text' => ['content' => $content], 'at' => ['atMobiles' => [], 'isAtAll' => false]];
            $apiUrl       = $this->apiUrl . '?access_token=' . $token;
            $client       = new \GuzzleHttp\Client();
            $res          = $client->request('POST', $apiUrl, ['json' => $message, 'timeout' => 5]);
            $httpCode     =$res->getStatusCode();
            $body         =$res->getBody();

        } catch (\Throwable $e) {
            Utils::catchError($this->logger, $e);
        } catch (\Exception $e) {
            Utils::catchError($this->logger, $e);
        }


        $this->logger->log('[钉钉接口]请求自定义机器人消息接口,请求地址：' . var_export($apiUrl, true) . ',请求参数:' . var_export($message, true) . ',返回结果:' . $body . '  httpcode: ' . $httpCode, 'info');

        return $body;
    }
}
