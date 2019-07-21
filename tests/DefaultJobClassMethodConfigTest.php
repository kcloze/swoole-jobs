<?php

define('SWOOLE_JOBS_ROOT_PATH', __DIR__ . '/..');
use PHPUnit\Framework\TestCase;

class DefaultJobClassMethodConfigTest extends TestCase
{
    private $queue =null;
    private $config=[];

    public function __construct()
    {
        $this->config= require SWOOLE_JOBS_ROOT_PATH . '/config.php';
        \Kcloze\Jobs\Config::setConfig($this->config);
        $logger      = \Kcloze\Jobs\Logs::getLogger($this->config['logPath'] ?? '', $this->config['logSaveFileApp'] ?? '');
        $this->queue =\Kcloze\Jobs\Queue\Queue::getQueue($this->config['job']['queue'], $logger);
    }

    public function testBase()
    {
        $this->assertSame(get_class($this->queue), 'Kcloze\Jobs\Queue\RedisTopicQueue');
        //$this->assertSame(get_class($this->queue), 'Kcloze\Jobs\Queue\RabbitmqTopicQueue');
        $topicName = 'DefaultClassMethod.test1';
        $this->queue->delete($topicName);
        $jobObject = new \Kcloze\Jobs\JobObject($topicName, '', '', ['functionName'=>__FUNCTION__, 'timestamp'=>time()]);
        $this->assertNotEmpty($this->queue->push($topicName, $jobObject, 1, 'json'));
        $this->assertSame(1, $this->queue->len($topicName));
        $messageBody = $this->queue->pop($topicName, 'json');
        $this->assertNotEmpty($messageBody);
        $this->assertSame($jobObject->topic, $messageBody['topic']);
        $this->assertSame($jobObject->jobClass, $messageBody['jobClass']);
        $this->assertSame($jobObject->jobMethod, $messageBody['jobMethod']);
        $this->assertSame($jobObject->jobParams, $messageBody['jobParams']);
        $this->assertSame($jobObject->jobExtras, $messageBody['jobExtras']);
    }

    public function testDefault()
    {
        $topicName = 'DefaultClassMethod.test1';
        $this->queue->delete($topicName);
        $jobParams   = ['orderNo'=>'12345678910', 'userId'=>'9527', 'userName'=>'凌凌漆', 'paymentTime'=>time()];
        $jobObject   = new \Kcloze\Jobs\JobObject(
            $topicName,
            '',
            '',
            $jobParams
        );
        $this->assertNotEmpty($this->queue->push($topicName, $jobObject, 1, 'json'));
        $this->assertSame(1, $this->queue->len($topicName));
        $messageBody = $this->queue->pop($topicName, 'json');
        $this->assertNotEmpty($messageBody);
        $this->assertSame($jobObject->topic, $messageBody['topic']);
        $this->assertSame($jobObject->jobClass, $messageBody['jobClass']);
        $this->assertSame($jobObject->jobMethod, $messageBody['jobMethod']);
        $this->assertSame($jobObject->jobParams, $messageBody['jobParams']);
        $this->assertSame($jobObject->jobParams, $jobParams);
        $this->assertSame($jobObject->jobExtras, $messageBody['jobExtras']);

        $job       = new \Kcloze\Jobs\Jobs('');
        $config    = $job->getConfigByTopic($topicName);
        $jobObject = $job->formatJobObjectByTopicConfig($jobObject, $topicName);
        $this->assertNotEmpty($jobObject->topic);
        $this->assertNotEmpty($jobObject->jobClass);
        $this->assertNotEmpty($jobObject->jobMethod);
        $this->assertSame($jobObject->topic, $topicName);
        $this->assertSame($jobObject->jobParams, $jobParams);
        $this->assertSame($jobObject->jobParams, $messageBody['jobParams']);
        $this->assertSame($jobObject->jobExtras, $messageBody['jobExtras']);
        $this->assertSame($jobObject->topic, $config['name']);
        $this->assertSame($jobObject->jobClass, $config['defaultJobClass']);
        $this->assertSame($jobObject->jobMethod, $config['defaultJobMethod']);
    }
}
