<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Abstract;

/**
 * Interceptor Abstract Class
 *
 * Provides an abstract interface for intercepting method calls.
 * Implementations can modify handler behavior before and after method execution.
 */
abstract class Interceptor
{
    /**
     * Pre-handle method called before the target method execution.
     *
     * @param object $handler The handler object containing the method to be called.
     * @param string $method The method name to be called.
     * @param array $args The arguments to be passed to the method.
     * @return void
     */
    abstract function preHandle(object &$handler, string &$method, array &$args);

    /**
     * Post-handle method called after the target method execution.
     *
     * @param object $handler The handler object that was called.
     * @param string $method The method name that was called.
     * @param array $args The arguments that were passed to the method.
     * @param mixed $result The result returned by the method.
     * @return void
     */
    abstract function postHandle(object &$handler, string &$method, array &$args, mixed &$result);
}