<?php
namespace Kcloze\MyJob;

use Kcloze\Jobs\Jobs;

class MyJob extends Jobs
{

    public function helloAction($data)
    {
        usleep(5);
        echo "hello, world\n";
        $this->error();

    }

    private function error()
    {
        //随机故意构造错误，验证子进程推出情况
        $i = mt_rand(0, 5);
        if ($i == 3) {
            echo "出错误了!!!\n";
            try {
                $this->methodNoFind();
                new Abc();
            } catch (Exception $e) {
                var_dump($e->getMessage());
            }
        }

    }
}
