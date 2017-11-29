<?php

namespace Kcloze\Jobs\Action;

class DemoAction extends BaseAction
{
    private $logger=null;

    public function init()
    {
        $this->logger = Logs::getLogger(Config::getConfig()['logPath'] ?? []);
    }

    public function start(JobObject $jobData)
    {
        $this->logger->log('Action has been done, action content: ' . json_encode($jobData));
    }
}
