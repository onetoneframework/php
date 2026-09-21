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
 * HTTP Adapter Abstract Class
 *
 * Provides an abstract interface for HTTP server adapters.
 * Implementations should handle HTTP server lifecycle and request processing.
 */
abstract class HTTPAdapter
{
    /**
     * Register a callback for the start of the HTTP server.
     *
     * @param callable $callback The callback function to execute when the server starts.
     * @return void
     */
    abstract public function onStart(callable $callback);

    /**
     * Register a callback for incoming HTTP requests.
     *
     * @param callable $callback The callback function to execute when a request is received.
     * @return void
     */
    abstract public function onRequest(callable $callback);

    /**
     * Start the HTTP server.
     *
     * @return void
     */
    abstract public function start();
}