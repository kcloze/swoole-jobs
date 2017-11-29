<?php

namespace Kcloze\Jobs\Action;

use Kcloze\Jobs\JobObject;

interface ActionInterface
{
    public function init();

    public function start(JobObject $jobData);
}
