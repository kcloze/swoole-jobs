<?php

namespace Kcloze\Jobs\Action;

use Kcloze\Jobs\Config;
use Kcloze\Jobs\JobObject;
use Kcloze\Jobs\Logs;

class SwooleJobsAction extends BaseAction
{
    private $logger=null;

    public function init()
    {
        $this->logger = Logs::getLogger(Config::getConfig()['logPath'] ?? []);
    }

    public function start(JobObject $jobData)
    {
        $this->init();
        $jobClass =$jobData->jobClass;
        $jobMethod=$jobData->jobMethod;
        $jobParams=$jobData->jobParams;
        try {
            $obj      =new $jobClass();
            if (is_object($obj) && method_exists($obj, $jobMethod)) {
                $obj->$jobMethod($jobParams);
            } else {
                $this->logger->log('Action obj not find: ' . json_encode($jobData), 'error');
            }
        } catch (\Exception $e) {
            $this->logger->log($e->getMessage(), 'error');
        }

        $this->logger->log('Action has been done, action content: ' . json_encode($jobData));
    }
}
