<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Proxy;
use Exception;
use function get_class;

/**
 * Class BenchmarkProxy
 * 
 * A proxy class that benchmarks method calls to a target object.
 */
class BenchmarkProxy
{
    private object $target;

    /**
     * BenchmarkProxy constructor.
     *
     * @param object $target
     */
    public function __construct(object $target)
    {
        $this->target = $target;
    }

    /**
     * Magic method to intercept method calls and benchmark them.
     *
     * @param string $method
     * @param array  $args
     *
     * @return mixed
     * 
     * @throws Exception
     */
    public function __call(string $method, array $args): mixed
    {
        if (!method_exists($this->target, $method)) {
            throw new Exception("Method {$method} does not exist");
        }

        $start = microtime(true);
        $result = $this->target->$method(...$args);
        $elapsed = (microtime(true) - $start) * 1000;

        error_log(get_class($this->target) . "::$method took {$elapsed} ms");

        return $result;
    }
}