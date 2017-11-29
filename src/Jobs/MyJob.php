<?php

namespace Kcloze\Jobs\Jobs;

class MyJob
{
    public static function test1(array $params)
    {
        usleep(5);
        echo 'test1| title: ' . $params['title'] . ' time: ' . $params['time'];
    }

    public function test2(array $params)
    {
        usleep(5);
        echo 'test2| title: ' . $params['title'] . ' time: ' . $params['time'];
    }

    public function testError()
    {
        //随机故意构造错误，验证子进程推出情况
        $i = mt_rand(0, 5);
        if ($i == 3) {
            echo "出错误了!!!\n";
            try {
                $this->methodNoFind();
                new Abc();
            } catch (\Exception $e) {
                var_dump($e->getMessage());
            }
        }
    }
}
