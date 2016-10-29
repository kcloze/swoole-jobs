<?php
namespace Kcloze\MyJob;

use Kcloze\Jobs\Jobs;

class MyJob extends Jobs
{

    public function helloAction($data)
    {
        usleep(5);
        echo "hello, world\n";
    }
}
