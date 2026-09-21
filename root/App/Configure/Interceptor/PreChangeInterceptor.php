<?php

namespace App\Interceptor;

use Clover\Classes\Proxy\BaseProxy;
use Clover\Classes\Routing\Router;
use Clover\Framework\Component\Response;
use Clover\Abstract\Interceptor;

class PreChangeInterceptor extends Interceptor
{
    private $start;

    public function preHandle(object &$handler, string &$method, array &$args)
    {
        return;
    }

    public function postHandle(object &$handler, string &$method, array &$args, mixed &$result)
    {
        return;
        if (!($handler instanceof Router || $handler instanceof BaseProxy)) {
            return;
        }

        /** @var Response $result */
        if ($result instanceof Response) {
            $result->clearResources();
            $result->setBody('postHandle');
        }
    }
}