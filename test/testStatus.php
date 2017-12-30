<?php

/*
 * This file is part of PHP CS Fixer.
 *  * (c) kcloze <pei.greet@qq.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

$pid =112;
$status_str .= "$pid\t" . str_pad('N/A', 7) . ' '
                    //. str_pad($info['listen'], static::$_maxSocketNameLength) . ' '
                    //. str_pad($info['name'], static::$_maxWorkerNameLength) . ' '
                    . str_pad('N/A', 11) . ' ' . str_pad('N/A', 9) . ' '
                    . str_pad('N/A', 7) . ' ' . str_pad('N/A', 13) . " N/A    [busy] \n";

echo $status_str;
