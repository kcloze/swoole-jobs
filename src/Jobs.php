<?php

namespace Kcloze\Jobs;

class Jobs
{

    const MAX_POP = 10; //单个topic每次最多取多少次

    public function run($config)
    {
        $queue = new Redis($config);
        while (true) {

            $topics = $queue->getTopics();
            if ($topics) {

                //遍历topic任务列表
                foreach ($topics as $key => $jobName) {
                    //每次最多取MAX_POP个任务执行
                    for ($i = 0; $i < self::MAX_POP; $i++) {
                        $data = $queue->pop($jobName);

                        if (!empty($data) && isset($data['jobAction'])) {
                            $jobName   = "Kcloze\MyJob\\" . ucfirst($jobName);
                            $jobAction = $data['jobAction'];
                            var_dump($jobName, $jobAction);
                            if (method_exists($jobName, $jobAction)) {
                                try {
                                    $job = new $jobName();
                                    $job->$jobAction($data);
                                    var_dump($data);
                                    echo ("one job has been done!\n");
                                } catch (Exception $e) {
                                    var_dump($e);
                                }
                            } else {
                                echo ("action not find\n");
                            }

                        } else {
                            break;
                        }

                    }
                }
            } else {
                echo "no work to do!\n";
            }
            echo "sleep one second!\n";
            sleep(2);
        }

    }

}
