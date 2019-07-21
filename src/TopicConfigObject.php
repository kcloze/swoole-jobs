<?php

namespace Kcloze\Jobs;

class TopicConfigObject
{
    private $name = '';
    private $workerMinNum;
    private $workerMaxNum;
    private $queueMaxNum;
    private $defaultJobClass  = '';
    private $defaultJobMethod = '';

    /**
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        if ($config != []) {
            $this->initAttributes($config);
        }
    }

    /**
     * @param array $config
     */
    public function initAttributes(array $config)
    {
        $class = new \ReflectionClass($this);
        foreach ($class->getProperties() as $property) {
            if (isset($config[$property->getName()])) {
                $method = 'set' . $property->getName();
                if (method_exists($this, $method)) {
                    $reflectionMethod = new \ReflectionMethod($this, $method);
                    $reflectionMethod->setAccessible(true);
                    $reflectionMethod->invoke($this, $config[$property->getName()]);
                }
            }
        }
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return int
     */
    public function getWorkerMinNum()
    {
        return $this->workerMinNum;
    }

    /**
     * @param int $workerMinNum
     */
    public function setWorkerMinNum($workerMinNum)
    {
        $this->workerMinNum = $workerMinNum;
    }

    /**
     * @return int
     */
    public function getWorkerMaxNum()
    {
        return $this->workerMaxNum;
    }

    /**
     * @param int $workerMaxNum
     */
    public function setWorkerMaxNum($workerMaxNum)
    {
        $this->workerMaxNum = $workerMaxNum;
    }

    /**
     * @return int
     */
    public function getQueueMaxNum()
    {
        return $this->queueMaxNum;
    }

    /**
     * @param int $queueMaxNum
     */
    public function setQueueMaxNum($queueMaxNum)
    {
        $this->queueMaxNum = $queueMaxNum;
    }

    /**
     * @return string
     */
    public function getDefaultJobClass()
    {
        return $this->defaultJobClass;
    }

    /**
     * @param string $defaultJobClass
     */
    public function setDefaultJobClass($defaultJobClass)
    {
        $this->defaultJobClass = $defaultJobClass;
    }

    /**
     * @return string
     */
    public function getDefaultJobMethod()
    {
        return $this->defaultJobMethod;
    }

    /**
     * @param string $defaultJobMethod
     */
    public function setDefaultJobMethod($defaultJobMethod)
    {
        $this->defaultJobMethod = $defaultJobMethod;
    }
}
