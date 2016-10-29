#swoole-jobs

* 基于swoole的job调度组件

##使用场景

* web中较慢的逻辑，比如统计/email/短信/图片处理等
* 单机job任务并发数10000以内，但可以多机器部署

##架构图

![架构图](jobs-archi.png)


##特性

* 基于swoole的job调度组件
* redis/rabbitmq/zeromq等任何一种做队列消息存储(目前只实现redis)
* 利用swoole的process实现多进程管理，进程个数可配置，worker进程退出后会自动拉起
* 子进程循环次数可配置，防止业务代码内存泄漏
* 支持topic特性，不同的job绑定不同的topic
* 支持composer，可以跟任意框架集成
* 日志文件自动切割，默认最大100M，最多5个日志文件，防止日志刷满磁盘


##示例


```
composer install

//往队列添加job
php test/testJobs.php


chmod u+x server.sh
//启动和关闭服务
./server.sh start|stop|restart


```
![实例图](demo.png)


##注意事项

* 如果嵌入自己的框架，需要像test/jobs目录的文件一样，继承Jobs基类



##压测

* 瓶颈: redis/rabbitmq队列存储本身和job执行速度



##感谢

* [swoole](http://www.swoole.com/)

##联系

qq群：141059677


