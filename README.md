##swoole-jobs

##使用场景
* web中较慢的逻辑，比如统计／email／短信
* 可以接受适当延迟

##目标
* 基于swoole的job调度组件
* 利用swoole的process实现多进程
* 支持topic特性，不同的job绑定不同的topic
* 支持composer，可以跟任意框架集成



##设计文档
* [架构图](https://github.com/kcloze/swoole-jobs/blob/master/jobs.png)


##示例

```
//启动多进程消化job
php test/testJobs.php

//往队列添加job
php test/testJobs.php

```


##压测




##感谢
* swoole
