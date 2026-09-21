<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Implement;

use Clover\Implement\RequestInterface;

/**
 * Middleware Interface
 *
 * Defines the contract for HTTP middleware.
 * Middleware can intercept and modify requests and responses.
 */
interface MiddlewareInterface
{
    /**
     * Handle the incoming request.
     *
     * @param RequestInterface $request The HTTP request object.
     * @param callable $next The next middleware or route handler in the chain.
     * @return mixed The response from the next handler or middleware.
     */
    public function handle(RequestInterface $request, callable $next);
}
