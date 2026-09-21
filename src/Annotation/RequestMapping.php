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
 * Class RequestMapping
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
final class RequestMapping
{
    /**
     * @var string $method The HTTP method for the route (e.g., "GET", "POST", "PUT", "DELETE", or "*" to match any method).
     */
    public string $method;

    /**
     * @var string $value The path pattern for the route (e.g., "/users/{id}"). Use "*" to match any path.
     */
    public string $value;

    /**
     * @var string $host The host pattern for the route (default is "*", which matches any host). You can specify a specific host or use patterns like "*.example.com".
     */
    public string $host;

    /**
     * @var int $priority The priority of the route (higher values are matched first, default is 0). This can be used to control the order in which routes are evaluated when multiple routes match a request.
     */
    public int $priority;

    /**
     * GET parameter name for virtual path when the request URI is only the entry script (no URL rewrite).
     */
    public string $pathQueryKey = '';

    /**
     * @var array<string, string> $query Array of query parameters to match for this route. Each key is the parameter name and the value is the expected value (use "*" to match any value). This allows you to define routes that only match when certain query parameters are present with specific values.
     */
    public array $query = [];

    /**
     * RequestMapping constructor.
     *
     * @param string $value The path pattern for the route (e.g., "/users/{id}").
     * @param string $method The HTTP method for the route (e.g., "GET", "POST", "PUT", "DELETE", or "*" to match any method).
     * @param string $host The host pattern for the route (default is "*", which matches any host).
     * @param int $priority The priority of the route (higher values are matched first, default is 0).
     * @param string $pathQueryKey Optional key to extract from the path and add to the query parameters.
     * @param array<string, string> $query Optional array of query parameters to match for this route.
     */
    public function __construct(string $value = "*", string $method = "*", string $host = "*", int $priority = 0, string $pathQueryKey = "", array $query = [])
    {
        $this->value = $value;
        $this->method = $method;
        $this->host = $host;
        $this->priority = $priority;
        $this->pathQueryKey = $pathQueryKey;
        $this->query = $query;
    }
}
