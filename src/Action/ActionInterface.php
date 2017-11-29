<?php

namespace Kcloze\Jobs\Action;

interface ActionInterface
{
    public function init();

    public function start(JobObject $jobData);
}
