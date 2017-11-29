<?php

/*
 * This file is part of PHP CS Fixer.
 * (c) kcloze <pei.greet@qq.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Kcloze\Jobs\Queue;

interface TopicQueueInterface
{
    /**
     * @return array a array of topics
     */
    public function getTopics();

    /**
     * @param array $topics
     */
    public function setTopics(array $topics);

    /**
     * @param $topic
     * @param $value
     */
    public function push($topic, $value);

    /**
     * @param $topic
     *
     * @return mixed
     */
    public function pop($topic);
}
