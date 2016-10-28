<?php

namespace Kcloze\Jobs;

use Kcloze\Jobs\Logs;
use Kcloze\Jobs\Redis;

class Jobs
{

    const MAX_POP = 10; //单个topic每次最多取多少次

    public function run($config)
    {
        $queue = new Redis($config['queue']);
        $log   = new Logs($config['logPath']);
        while (true) {

            $topics = $queue->getTopics();
            if ($topics) {
                //遍历topic任务列表
                foreach ($topics as $key => $jobName) {
                    //每次最多取MAX_POP个任务执行
                    for ($i = 0; $i < self::MAX_POP; $i++) {
                        $data = $queue->pop($jobName);
                        $log->log(print_r($data, true), 'info');
                        if (!empty($data) && isset($data['jobAction'])) {
                            //注意如果嵌入自己的框架，可以修改这个路径
                            $jobName   = "Kcloze\MyJob\\" . ucfirst($jobName);
                            $jobAction = $data['jobAction'];
                            $log->log(print_r([$jobName, $jobAction], true), 'info');
                            if (method_exists($jobName, $jobAction)) {
                                try {
                                    $job = new $jobName();
                                    $job->$jobAction($data);
                                    $log->log("one job has been done!", 'info');
                                } catch (Exception $e) {
                                    $log->log($e->getMessage(), 'error');
                                }
                            } else {
                                $log->log($jobAction . " action not find!", 'warning');
                            }

                        } else {
                            $log->log($jobName . " no work to do!", 'info');
                            break;
                        }

                    }
                }
            } else {
                $log->log("All no work to do!", 'info');
            }
            $log->log("sleep two second!", 'info');
            $log->flush();
            usleep(100);
        }

    }

}
