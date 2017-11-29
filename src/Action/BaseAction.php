<?php

namespace Kcloze\Jobs\Action;

use Kcloze\Jobs\JobObject;

abstract class BaseAction
{
    public function init()
    {
    }

    public function start(JobObject $jobData)
    {
    }
}
