<?php

use PHPUnit\Framework\TestCase;

class TopicConfigObjectTest extends TestCase
{
    public function testEmpty()
    {
        $config = [
            'params'=> [
                'a', 'b', 'c',
            ],
            'attribute'=> null,
        ];
        $object = new \Kcloze\Jobs\TopicConfigObject();
        $object->initAttributes($config);
        $this->assertSame('', $object->getName());
        $this->assertSame('', $object->getDefaultJobClass());
        $this->assertSame('', $object->getDefaultJobMethod());
        $this->assertNull($object->getWorkerMinNum());
        $this->assertNull($object->getWorkerMaxNum());
        $this->assertNull($object->getQueueMaxNum());
        $this->assertNull($object->getQueueMaxNumForProcess());
    }

    public function testAttributes()
    {
        $config = [
            'name'                  => 'nameValue',
            'defaultJobClass'       => 'jobClassValue',
            'defaultJobMethod'      => 'jobMethodValue',
            'workerMinNum'          => 1,
            'workerMaxNum'          => 3,
            'queueMaxNum'           => 10,
            'queueMaxNumForProcess' => 10,
            'params'                => [
                'a', 'b', 'c',
            ],
            'attribute'=> null,
        ];
        $object = new \Kcloze\Jobs\TopicConfigObject();
        $object->initAttributes($config);
        $this->assertSame($config['name'], $object->getName());
        $this->assertSame($config['defaultJobClass'], $object->getDefaultJobClass());
        $this->assertSame($config['defaultJobMethod'], $object->getDefaultJobMethod());
        $this->assertSame($config['workerMinNum'], $object->getWorkerMinNum());
        $this->assertSame($config['workerMaxNum'], $object->getWorkerMaxNum());
        $this->assertSame($config['queueMaxNum'], $object->getQueueMaxNum());
        $this->assertSame($config['queueMaxNumForProcess'], $object->getQueueMaxNumForProcess());
    }

    public function testConstruct()
    {
        $config = [
            'name'                  => 'nameValue',
            'defaultJobClass'       => 'jobClassValue',
            'defaultJobMethod'      => 'jobMethodValue',
            'workerMinNum'          => 1,
            'workerMaxNum'          => 3,
            'queueMaxNum'           => 10,
            'queueMaxNumForProcess' => 10,
            'params'                => [
                'a', 'b', 'c',
            ],
            'attribute'=> null,
        ];
        $object = new \Kcloze\Jobs\TopicConfigObject($config);
        $this->assertSame($config['name'], $object->getName());
        $this->assertSame($config['defaultJobClass'], $object->getDefaultJobClass());
        $this->assertSame($config['defaultJobMethod'], $object->getDefaultJobMethod());
        $this->assertSame($config['workerMinNum'], $object->getWorkerMinNum());
        $this->assertSame($config['workerMaxNum'], $object->getWorkerMaxNum());
        $this->assertSame($config['queueMaxNum'], $object->getQueueMaxNum());
        $this->assertSame($config['queueMaxNumForProcess'], $object->getQueueMaxNumForProcess());
    }
}
