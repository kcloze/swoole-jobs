<?php

/*
 * This file is part of PHP CS Fixer.
 * (c) kcloze <pei.greet@qq.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Kcloze\Jobs;

class Serialize
{
    /**
     * 序列化.
     *
     * @param [type] $str
     * @param string $serializeFunc php json
     */
    public static function serialize($str, $serializeFunc='php')
    {
        switch ($serializeFunc) {
            case 'php':
                $str=serialize($str);
                break;
            case 'json':
                $str=json_encode($str);
                break;

            default:
                $str=serialize($str);
                break;
        }

        return $str;
    }

    /**
     * 反序列化.
     *
     * @param [type] $str
     * @param string $unSerializeFunc php  json
     */
    public static function unSerialize($str, $unSerializeFunc='php')
    {
        switch ($unSerializeFunc) {
            case 'php':
                $str=unserialize($str);
                break;
            case 'json':
                $str=json_decode($str);
                break;

            default:
                $str=unserialize($str);
                break;
        }

        return $str;
    }

    /**
     * Check if a string is serialized.
     *
     * @param mixed $str
     */
    public static function isSerial($str)
    {
        return   $str == serialize(false) || false !== @unserialize($str);
    }
}
