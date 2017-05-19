# swoole-jobs

* 基于swoole的job调度组件

## 使用场景

* web中较慢的逻辑，比如统计/email/短信/图片处理等
* 单机job任务并发数10000以内，但可以多机器部署

## 架构图

![架构图](jobs-archi.png)


## 特性

* 基于swoole的job调度组件
* redis/rabbitmq/zeromq等任何一种做队列消息存储(目前只实现redis/rabbitmq)
* 利用swoole的process实现多进程管理，进程个数可配置，worker进程退出后会自动拉起
* 子进程循环次数可配置，防止业务代码内存泄漏
* 支持topic特性，不同的job绑定不同的topic
* 支持composer，可以跟任意框架集成
* 日志文件自动切割，默认最大100M，最多5个日志文件，防止日志刷满磁盘


## 安装


```
git clone https://github.com/kcloze/swoole-jobs.git
cd swoole-jobs
composer install

//往队列添加job
php test/testJobs.php

```
## 服务管理
### 启动和关闭服务,有两种方式:

#### 1. shell脚本(主进程挂了之后,需要手动启动)
```
chmod u+x server.sh
./server.sh start|stop|restart
```
#### 2. 使用systemd管理(故障重启、开机自启动)
[更多systemd介绍](https://www.swoole.com/wiki/page/699.html)

```
1. 根据自己项目路径,修改 systemd/swoole-jobs.service
2. sudo cp -f systemd/swoole-jobs.service /etc/systemd/system/
3. sudo systemctl --system daemon-reload
4. 服务管理
#启动服务
sudo systemctl start swoole-jobs.service
#reload服务
sudo systemctl reload swoole-jobs.service
#关闭服务
sudo systemctl stop swoole-jobs.service
```


![实例图](demo.png)



## change log
* 增加使用systemd管理swoole服务,实现故障重启、开机自启动等功能

## 注意事项

* 如果嵌入自己的框架，jobs类可以自己根据框架路径自由定义，详情看src/Jobs.php



## 压测

* 瓶颈: redis/rabbitmq队列存储本身和job执行速度



## 感谢

* [swoole](http://www.swoole.com/)

## 联系

qq群：141059677





