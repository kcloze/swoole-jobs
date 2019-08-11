# swoole-jobs
 
## [中文介绍](https://github.com/kcloze/swoole-jobs/blob/master/README.zh.md)


* Distributed task processing system,similar to gearman,based on swoole
* High performance / dynamic multi woker process consumption queue to accelerate backend time consuming service
* There is no need to configure a crontab like gearman worker, swoole-jobs is responsible for managing all worker states
* Support for pushing queues by HTTP API（swoole http server） , does not depend on php-fpm




## 1. Explain

* Slower logic in web, such as statistical /email/ SMS / picture processing, etc.
* Support redis/rabbitmq/zeromq or any other queue message store.
* It is more stable and faster than the Yii / laravel framework itself.
* With yii2/phalcon/yaf/ThinkPHP5 integration example, other frameworks can refer to src/Action code.
* [yii2 demo](https://github.com/kcloze/swoole-jobs-yii2)
* [ThinkPHP5 demo](https://github.com/kcloze/swoole-jobs-tp5)


## 2. Architecture diagram

![Architecture diagram](docs/images/jobs-archi.png)
![Process model](docs/images/jobs-process.png)


## 3. Characteristic

* job scheduling component based on swoole; distributed task processing system similar to gearman;

* redis/rabbitmq/zeromq and any other queue message store (currently only redis/rabbitmq).

* use swoole process to realize multi process management, the number of processes can be configured, and the worker process will automatically pull up after exiting.

* the number of cycles of child processes can be configured to prevent memory leakage from business code; the default stop command will wait for the child process to exit smoothly.

* support topic features, different job binding different topic;

* each topic starts the corresponding number of sub processes to eliminate the interaction between different topic.

* according to the queue backlog, the sub process starts the process dynamically, and the number of the largest sub processes can be configured.

* support composer, which can be integrated with any framework;

* log file automatic cutting, default maximum 100M, up to 5 log files, prevent log brush full disk;

* backlog, support for nail robot and other news alerts.


## 4. Install

#### 4.1 composer
```
git clone https://github.com/kcloze/swoole-jobs.git
cd swoole-jobs

```


```
composer install
```
#### 4.2 docker
* git clone https://github.com/kcloze/swoole-jobs.git
* cd swoole-jobs and composer install
* Building a mirror based on the root directory Dockerfile
* docker build -t swoole-jobs .
* docker run  -it  -v ~/data/code/php:/data swoole-jobs /bin/bash
* After entering the docker container, enter the project directory:
  * `php ./bin/swoole-jobs.php start`

## 5. How to running

### 5.1 example
```
1.edit config.php

2.start service
php ./bin/swoole-jobs.php start >> log/system.log 2>&1

3.push jobs
php ./tests/testJobsSerialzie.php

4.start api server
php ./bin/swoole-jobs.php start http

5.stop api server
php ./bin/swoole-jobs.php stop http
```

### 5.2 Start parameter description
```
NAME
      - manage swoole-jobs

SYNOPSIS
      -php bin/swoole-jobs.php app [options]
        -Manage swoole-jobs daemons.

WORKFLOWS

      -help [command]
        -Show this help, or workflow help for command.

      -restart
        -Stop, then start swoole-jobs master and workers.

      -start
        -Start swoole-jobs master and workers.

      -stop
        -Wait all running workers smooth exit, please check swoole-jobs status for a while.

      -exit
        -Kill all running workers and master PIDs.

      -http start 
        -Start swoole http server for apis.
      
      -http stop
        -Stop swoole http server for api.

```
### 5.3 API parameter description

#### 5.3.1 api url
* http://localhost:9501/pushJobs

#### 5.3.2 api params:

| Params       | Type           | Demo  |
| ------------- |:-------------:| -----:|
|   jobData    | json | {"topic":"MyJob","jobClass":"\\Kcloze\\Jobs\\Jobs\\MyJob","jobMethod":"test2","jobParams":["kcloze",1532857253,"oop"],"jobExtras":[],"serializeFunc":"php"} |

## 6. Service management
### There are two ways to start and close the service online:

#### 6.1 The startup script is added to the crontab timing task, which is executed once a minute (swoole-jobs automatically checks if it is executing, avoiding repeated startup).

```
* * * * * /usr/local/bin/php /***/swoole-jobs.php start >> /***/log/system.log 2>&1

```



#### 6.2 Using SYSTEMd Management (failure restart, boot up)
[more](https://www.swoole.com/wiki/page/699.html)

```
1. According to your own project path, modify： docs/systemd/swoole-jobs.service
2. sudo cp -f systemd/swoole-jobs.service /etc/systemd/system/
3. sudo systemctl --system daemon-reload
4. Service management

```
#start service
sudo systemctl start swoole-jobs.service
#reload service
sudo systemctl reload swoole-jobs.service
#stop service
sudo systemctl stop swoole-jobs.service

```

## 7.System screenshot
#### htop
![demo](docs/images/demo.png)
#### status
![status](docs/images/status.png)
#### dingding message
![message](docs/images/dingding.png)



## 8. Change log
* [change log](docs/ChangeLog.md)

## 9. Matters needing attention
* If you embed your own framework, you can refer to src/Action code to inherit the abstract class Kcloze\Jobs\Action\BaseAction.
* Various framework services will start slightly different, for specific reference: Code for `example/bin` projects.
* swoole >=4 version,need to set php.ini, [disable coroutine ](https://github.com/swoole/swoole-src/issues/2716)

## 10. Pressure measurement
* Bottleneck: redis/rabbitmq queue storage itself and job execution speed

## 11. Thanks
* [swoole](http://www.swoole.com/)

## 12. Contact
qq group：141059677


## 13. Donation
* If this project really helps you, please click on the top right corner for a star.




