<?php
/*
job实体

*/

namespace Kcloze\Jobs;

class JobObject
{
    public $topic    ='';
    public $jobClass ='';
    public $jobMethod='';
    public $jobParams='';

    public function __construct($topic, $jobClass, $jobMethod, $jobParams)
    {
        $this->topic    =$topic;
        $this->jobClass =$jobClass;
        $this->jobMethod=$jobMethod;
        $this->jobParams=$jobParams;
    }
}
