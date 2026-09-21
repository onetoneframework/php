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
 * Class PostMapping
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class PostMapping
{
    /** 
     * @var string $value The path pattern for the route (e.g., "/users/{id}"). Use "*" to match any path.
     **/
    public string $value;
    /** 
     * @var string $host The host pattern for the route (default is "*", which matches any host). Use "*" to match any host.
     **/
    public string $host;
    /** 
     * @var int $priority The priority of the route (higher values are matched first, default is 0). 
     **/
    public int $priority;
    /** 
     * @var string $pathQueryKey Optional key to extract from the path and add to the query parameters. If specified, the value will be extracted from the path and added to the query parameters with this key.
     **/
    public string $pathQueryKey = '';

    /** 
     * @var array<string, string> $query Array of query parameters to match for this route. Each key is the parameter name and the value is the expected value (use "*" to match any value).
     **/
    public array $query = [];

    /**
     * PostMapping constructor.
     *
     * @param string $value The path pattern for the route (e.g., "/users/{id}").
     * @param string $host The host pattern for the route (default is "*", which matches any host).
     * @param int $priority The priority of the route (higher values are matched first, default is 0).
     * @param string $pathQueryKey Optional key to extract from the path and add to the query parameters.
     * @param array<string, string> $query Optional array of query parameters to match for this route.
     */
    public function __construct(string $value = "*", string $host = "*", int $priority = 0, string $pathQueryKey = "", array $query = [])
    {
        $this->value = $value;
        $this->host = $host;
        $this->priority = $priority;
        $this->pathQueryKey = $pathQueryKey;
        $this->query = $query;
    }
}
