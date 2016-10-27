<?php

namespace Kcloze\Jobs;

use Kcloze\Queue;

class Jobs
{

    const MAX_POP = 100; //单个topic每次最多取多少次

    public function run($config)
    {

        $queue = new Queue($config);
        while (true) {
            $topics = $queue->getTopics();
            //遍历topic任务列表
            foreach ($topics as $jobAction => $data) {
                //每次最多取MAX_POP个任务执行
                for ($i = 0; $i < self::MAX_POP; $i++) {
                    $data = $queue->pop($jobAction);
                    if (!empty($data) && method_exists($this, $jobAction . 'Action')) {
                        $this->$jobAction . 'Action'($data);
                    } else {
                        echo "action not exists!\n";
                    }

                }
            }
            sleep(1);
        }

    }

    /**
     * job action
     * @param  [data] string
     * @return [type]
     */
    public function helloAction($data)
    {
        echo "hello, world\n";
    }
}
