<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Routing;

use Clover\Classes\DataStructor\Queue;
use Clover\Classes\Reflection\Handler as ReflectionHandler;
use function is_string;

/**
 * Class QueueableRequestHandler
 * 
 * Handles a queue of middleware for processing HTTP requests.
 */
class QueueableRequestHandler extends Queue
{
    /**
     * Add middleware on queue stack
     * 
     * @param mixed $middlewares
     * @param mixed $container
     * 
     * @return void
     */
    public function addMiddlewares($middlewares, $container): void
    {
        foreach ($middlewares as $middleware) {
            $next = parent::$stock->top();

            $closure = function () use ($middleware, $next, $container) {
                if (!empty($middleware) && is_string($middleware)) {
                    $instance = ReflectionHandler::getNewInstance($middleware);

                    return ReflectionHandler::invoke($instance, 'handle', [$next], $container);
                }

                return ReflectionHandler::callMethod($middleware, $next);
            };

            parent::$stock->enqueue($closure);
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
