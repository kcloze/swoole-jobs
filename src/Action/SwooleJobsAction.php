<?php

namespace Kcloze\Jobs\Action;

class SwooleJobsAction extends BaseAction
{
    private $logger=null;

    public function init()
    {
        $this->logger = Logs::getLogger(Config::getConfig()['logPath'] ?? []);
    }

    public function start(JobObject $jobData)
    {
        $jobClass =$jobData->jobClass;
        $jobMethod=$jobData->jobMethod;
        $jobParams=$jobData->jobParams;
        try {
            $obj      =new $jobClass();
            if (is_object($obj) && method_exists($obj, $jobParams)) {
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
