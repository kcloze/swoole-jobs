<?php

namespace Kcloze\Jobs\Api\Controller;

use Kcloze\Jobs\Api\Services\PushJobs;

class Index
{


    public function push()
    {
        $data=$_GET['jobData'] ?? $_POST['jobData'] ?? '';
        $pushJobs=new PushJobs();
        return $pushJobs->pushSimple($data);
    }

    public function demo()
    {
        $data['topic'] = 'MyJob';
        $data['jobClass']  = '\Kcloze\Jobs\Jobs\MyJob';
        $data['jobMethod']= 'test2';
        $data['jobParams']=['kcloze', time(), 'oop'];
        $data['jobExtras']=[];
        $data['serializeFunc']='php';

        $dataJob=json_encode($data);
        $pushJobs=new PushJobs();
        return $pushJobs->pushSimple($dataJob);
    }
}
