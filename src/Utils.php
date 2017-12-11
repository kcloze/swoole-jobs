<?php

namespace Kcloze\Jobs;

class Utils
{
    /**
     * 循环创建目录.
     *
     * @param mixed $dir
     * @param mixed $mode
     */
    public static function mkdir($dir, $mode = 0777)
    {
        if (is_dir($dir) || @mkdir($dir, $mode)) {
            return true;
        }

        if (!mk_dir(dirname($dir), $mode)) {
            return false;
        }

        return @mkdir($dir, $mode);
    }
}
