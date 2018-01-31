### 1.composer加载swoole-jobs
命令: composer require kcloze/swoole-jobs
### 2、配置调整
拷贝`vendor\kcloze\swoole-jobs\config.php`文件到`application\extra\swoole-jobs.php`，并将日志及swoole主进程文件目录调整到runtime目录下：
调整如下：
```
[
        'logPath'           => APP_PATH . '../runtime/swoole-jobs/log',//swoole-jobs运行日志目录
        ........
        'pidPath'           => APP_PATH . '../runtime/swoole-jobs/pid',//swoole主进程id记录文件
        'framework' => [
            'class'=> '\Kcloze\Jobs\Action\Thinkphp5Action',//更改为Thinkphp的控制类
    ],
]
```
### 2.调整启动命令
拷贝`vendor\kcloze\swoole-jobs\swoole-jobs`(或swoole-jobs.php)文件到项目根目录，并调整代码为：
````

define('SWOOLE_JOBS_ROOT_PATH', __DIR__);
//为了支持tp框架应用程序定义thinkphp应用目录常量
define('APP_PATH', SWOOLE_JOBS_ROOT_PATH . '/application/');
//ini_set('default_socket_timeout', -1);
date_default_timezone_set('Asia/Shanghai');

// 改为加载ThinkPHP引导文件，如果在Action中加载会和composer的autoload.php冲突，造成执行失败
require_once SWOOLE_JOBS_ROOT_PATH . '/thinkphp/base.php';
//require SWOOLE_JOBS_ROOT_PATH . '/vendor/autoload.php';
//引入配置调整到对应目录
$config = require_once APP_PATH . '/extra/swoole-jobs.php';
$console = new Kcloze\Jobs\Console($config);
$console->run();
````

### 4、任务实现
在application目录下新增jobs目录，命名空间为`app\jobs`，作用同`swoole-jobs\src\Jobs`目录，用来编写任务实现的具体代码。
### 5、封装任务处理类为
在application目录下新增service目录,命名空间为`app\service`，，用来保存业务逻辑代码，此处新增SwooleJob.php业务处理类用来简化swoole-jobs的任务入队操作。
```
<?php
namespace app\service;

use Kcloze\Jobs\JobObject;
use Kcloze\Jobs\Logs;
use Kcloze\Jobs\Queue\BaseTopicQueue;
use Kcloze\Jobs\Queue\Queue;

/**
 * 异步任务操作类
 * 开发步骤：
 * 1、在application\jobs目录中编写任务处理类，可以一个类中编写多个处理方法，每个方法设置不同参数
 * 2、在application\extra\swoole-jobs.php文件中编辑job->topics处理进程任务数
 * 3、在其他地方调用\app\service\SwooleJob::push()方法，并设置对应参数即可
 *
 *
 * @package app\service
 */
class SwooleJob
{
    /**
     * 私有构造函数，防止外界实例化对象
     */
    private function __construct()
    {

    }

    /**
     * 私有克隆函数，防止外办克隆对象
     */
    private function __clone()
    {
    }

    /**
     * 推送swoole异步任务
     * @param string $jobName 任务名称,对应application/jobs/目录中的类名
     * @param string $method 类方法名
     * @param array $params 方法参数，类型为数组，数组值顺序对应方法参数顺序
     * @param array $jobExt 任务附加参数['delay'=>'延迟毫秒数','priority'=>'任务权重,数字类型,范围：1-5']
     * @throws \Exception
     */
    public static function push($jobName, $method = '', $params = [], $jobExt = [])
    {
        if (empty($jobName)){
            throw new \Exception('异步任务名不能为空');
        }
        $config = config('swoole-jobs');
        $logger = Logs::getLogger($config['logPath'] ?? '', $config['logSaveFileApp'] ?? '');
        $queue = Queue::getQueue($config['job']['queue'], $logger);
        //设置工作进程参数
        $queue->setTopics($config['job']['topics']);

        $jobExtras['delay'] = isset($jobExt['delay']) ? $jobExt['delay'] : 0;
        $jobExtras['priority'] = isset($jobExt['priority']) ? $jobExt['priority'] : BaseTopicQueue::HIGH_LEVEL_1;
        //任务类名称
        $jobClass = '\app\jobs\\' . $jobName;
        $job = new JobObject($jobName, $jobClass, $method, $params, $jobExtras);
        $result = $queue->push($jobName, $job);
        return $result;
    }
}
```
然后可以在框架任意地方使用下列代码新增异步任务
```
$name = \app\service\SwooleJob::push('MyJob','MyMethod', ['方法参数1值','方法参数2值','...'], ['任务其他参数...']);
```

队列消费服务操作与swoole-jobs一致。

