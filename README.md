#swoole-jobs
* 基于swoole的job调度组件

##使用场景
* web中较慢的逻辑，比如统计／email／短信
* 通过配置控制worker进程数

##设计文档
![架构图](jobs-archi.png)


##特性
* 基于swoole的job调度组件
* redis/rabbitmq/zeromq等任何一种做队列消息存储
* 利用swoole的process实现多进程管理
* 支持topic特性，不同的job绑定不同的topic
* 支持composer，可以跟任意框架集成


##示例


```
composer update

//启动多进程消化job
php test/testProcess.php

//往队列添加job
php test/testJobs.php

```


##压测




##感谢
* swoole
