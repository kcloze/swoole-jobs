<?php
namespace Kcloze\Jobs;

class Router
{
    public function run()
    {
        $router = new \Bramus\Router\Router();
        // Static route: /hello
        $router->get('/hello', function () {
            echo 'hello,swoole-jobs!';
        });
        $router->get('/pushJobs', function () {
            $object=new \Kcloze\Jobs\Api\Controller\Index();
            $object->push();
        });
        $router->get('/demo', function () {
            $object=new \Kcloze\Jobs\Api\Controller\Index();
            $object->demo();
        });
        $router->run();
    }
}
