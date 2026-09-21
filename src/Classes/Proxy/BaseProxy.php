<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Proxy;
use Clover\Abstract\Interceptor;
use Clover\Framework\Context\ApplicationContext;
use Exception;
use function is_array;
use function count;
use function sprintf;

/**
 * Class BaseProxy
 * 
 * A base proxy class that intercepts method calls to a target object.
 */
class BaseProxy
{
    /**
     * The target object being proxied.
     * 
     * @var object
     */
    private object $target;

    /**
     * Constructor
     * 
     * @param object $target The target object to proxy
     */
    public function __construct(object $target)
    {
        $this->target = $target;
    }

    /**
     * Static method call interceptor
     * 
     * @param string $method Method name
     * @param array $args Method arguments
     * 
     * @return mixed Result of the method call
     * 
     * @throws Exception If the method does not exist
     */
    public static function __callStatic(string $method, array $args): mixed
    {
        if (!isset(self::$method)) {
            throw new Exception(sprintf("Can not found `%s` method", $method));
        }

        $result = self::$method(...$args);

        return $result;
    }

    /**
     * Instance property setter
     * 
     * @param string $property Property name
     * @param mixed $value Property value
     * 
     * @return void
     */
    public function __set(string $property, mixed $value): void
    {
        $this->target[$property] = $value;
    }

    /**
     * Instance property getter
     * 
     * @param string $key Property name
     * 
     * @return mixed
     */
    public function __get(string $key): mixed
    {
        if (!isset($this->target[$key]) || function_exists($this->target[$key])) {
            return null;
        }

        return $this->target[$key];
    }

    /**
     * Instance method call interceptor
     * 
     * @param string $method Method name
     * @param array $args Method arguments
     * 
     * @return mixed Result of the method call
     */
    public function __call(string $method, array $args): mixed
    {
        $interceptors = ApplicationContext::getInterceptors();

        if (is_array($interceptors)) {
            /** @var Interceptor $interceptor */
            foreach ($interceptors as $interceptor) {
                if (!method_exists($interceptor, 'preHandle')) {
                    continue;
                }
                
                $interceptor->preHandle($this->target, $method, $args);
            }
        }

        if (!empty($args) && count($args) > 0) {
            $result = $this->target->$method(...$args);
        } else {
            $result = $this->target->$method();
        }

        if (is_array($interceptors)) {
            /** @var Interceptor $interceptor */
            foreach ($interceptors as $interceptor) {
                if (!method_exists($interceptor, 'postHandle')) {
                    continue;
                }

                $interceptor->postHandle($this->target, $method, $args, $result);
            }
        }

        return $result;
    }
}
