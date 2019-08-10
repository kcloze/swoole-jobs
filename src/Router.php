<?php
namespace Kcloze\Jobs;

class Router
{
    public function run()
    {
        $router = new \Bramus\Router\Router();
        // Static route: /hello
        // $router->get('/', function () {
        //     echo 'hello,swoole-jobs! '.microtime();
        // });
        $router->get('/hello', function () {
            echo 'hello,swoole-jobs! '.microtime();
        });
        $router->get('/pushJobs', function () {
            $object=new \Kcloze\Jobs\Api\Controller\Index();
            echo $object->push();
        });
        $router->get('/demo', function () {
            $object=new \Kcloze\Jobs\Api\Controller\Index();
            echo $object->demo();
        });
        $router->run();
        //unset($router);
    }
}
