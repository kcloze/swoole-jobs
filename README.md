#swoole-jobs

* 基于swoole的job调度组件

##使用场景

* web中较慢的逻辑，比如统计／email／短信
* job任务并发数10000以内

##设计文档
![架构图](jobs-archi.png)


##特性

* 基于swoole的job调度组件
* redis/rabbitmq/zeromq等任何一种做队列消息存储(目前只实现redis)
* 利用swoole的process实现多进程管理，进程个数可配置
* 支持topic特性，不同的job绑定不同的topic
* 支持composer，可以跟任意框架集成


##示例


```
composer update

//往队列添加job
php test/testJobs.php


chmod u+x server.sh
//启动和关闭服务(目前mac下可能有问题)
./server.sh start|stop


```
![实例图](demo.png)



##压测

* 瓶颈: redis/rabbitmq队列存储本身和job执行速度



##感谢

* [swoole](http://www.swoole.com/) 

