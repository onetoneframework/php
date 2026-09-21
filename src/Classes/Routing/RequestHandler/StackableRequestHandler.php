<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Routing;

use Clover\Classes\DataStructor\Stack;
use Clover\Classes\Reflection\Handler as ReflectionHandler;
use function is_string;

/**
 * Class StackableRequestHandler
 * 
 * Handles a stack of middleware for processing HTTP requests.
 */
class StackableRequestHandler extends Stack
{
    /**
     * Add middleware on stack
     * 
     * @param mixed $middlewares
     * @param mixed $container
     * 
     * @return void
     */
    public function addMiddlewares($middlewares, $container): void
    {
        foreach ($middlewares as $middleware) {
            // Preve stock in stack
            $next = parent::$stock->top();

            $closure = function () use ($middleware, $next, $container) {
                if (!empty($middleware) && is_string($middleware)) {
                    $instance = ReflectionHandler::getNewInstance($middleware);

                    return ReflectionHandler::invoke($instance, 'handle', [$next], $container);
                }

                return ReflectionHandler::callMethod($middleware, $next);
            };

            parent::$stock[] = $closure;
        }
    }

    /**
     * Call a top callback of last node
     * 
     * @return mixed
     */
    public function handle(): mixed
    {
        $top = parent::$stock->top();

        if (!is_callable($top)) {
            return $top;
        }

        return $top();
    }
}
