<?php

namespace App\Interceptor;

use Clover\Abstract\Interceptor;
use Clover\Classes\Reflection\Handler as ReflectionHandler;

class BenchmarkInterceptor extends Interceptor
{
    private static $totalTime = 0;
    private $start;

    public function preHandle(object &$handler, string &$method, array &$args)
    {
        $this->start = microtime(true);
    }

    public function postHandle(object &$handler, string &$method, array &$args, mixed &$result)
    {
        return;
        $elapsed = (microtime(true) - $this->start) * 1000;
        if (get_class($handler) === get_class($this)) {
            $target = ReflectionHandler::getCalledClass();
        } else {
            $target = get_class($handler);
        }
        $returnType = getType($result) === 'object' ? get_class($result) : getType($result);

        self::$totalTime += $elapsed;

        error_log($target . "::$method($returnType) took {$elapsed} ms / ".self::$totalTime);
    }
}