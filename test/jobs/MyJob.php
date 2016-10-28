<?php
namespace Kcloze\MyJob;

use Kcloze\Jobs\Jobs;

class MyJob extends Jobs
{

    public function helloAction($data)
    {
        sleep(1);
        echo "hello, world\n";
    }
}
