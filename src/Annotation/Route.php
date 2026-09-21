<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Annotation;

use Attribute;

/**
 * Class Route
 *
 * Annotation to define a route for a controller method or class.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
final class Route
{
    /**
     * @var string|array|null Middleware class name(s) to apply to this route.
     */
    public $middleware;

    /**
     * @var string|null Route holder identifier.
     */
    public $holder;

    /**
     * @var string HTTP method for this route (e.g., "GET", "POST", "*" for all methods).
     */
    public $method = "GET";

    /**
     * @var string Route pattern/URL path.
     */
    public $pattern = "*";

    /**
     * @var string|null Not found handler class name.
     */
    public $notFoundHandler;

    /**
     * @var string|null Content type for the route response.
     */
    public $contentType;

    /**
     * @var string Host name for this route ("*" for all hosts).
     */
    public $host;

    /**
     * @var int Priority of the route (higher value means higher priority).
     */
    public $priority;

    /**
     * When set (e.g. "url"), if the request hits only the front controller script, path segments are read from that query parameter instead of the URL path (no rewrite required).
     *
     * @var string
     */
    public string $pathQueryKey = '';

    /**
     * Required query string parameters (exact string match) for this route to match, e.g. ["action" => "list"].
     *
     * @var array<string, string>
     */
    public array $query = [];

    /**
     * Route constructor.
     *
     * @param string $method The HTTP method (default: "*").
     * @param string $pattern The route pattern (default: "*").
     * @param string $host The host name (default: "*").
     * @param int $priority The priority of the route (default: 0).
     * @param string $pathQueryKey GET parameter name holding the virtual path when rewrite is unavailable.
     * @param array<string, string> $query Required query parameters for matching.
     */
    public function __construct(string $method = "*", string $pattern = "*", string $host = "*", int $priority = 0, string $pathQueryKey = "", array $query = [])
    {
        $this->method = $method;
        $this->pattern = $pattern;
        $this->host = $host;
        $this->priority = $priority;
        $this->pathQueryKey = $pathQueryKey;
        $this->query = $query;
    }
}
