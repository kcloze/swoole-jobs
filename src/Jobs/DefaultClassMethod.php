<?php

namespace Kcloze\Jobs\Jobs;

class DefaultClassMethod
{
    public function test1(...$args)
    {
        echo __CLASS__,'->',__FUNCTION__,'(' . var_export($args, true) . ')',PHP_EOL;
    }

    public function test2(...$args)
    {
        echo __CLASS__,'->',__FUNCTION__,'(' . var_export($args, true) . ')',PHP_EOL;
    }

    public static function test3(...$args)
    {
        echo __CLASS__,'::',__FUNCTION__,'(' . var_export($args, true) . ')',PHP_EOL;
    }
}
