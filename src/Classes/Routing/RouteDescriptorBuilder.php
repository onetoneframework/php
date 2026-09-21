<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Routing;

use Clover\Annotation\Route;

/**
 * Builder for route annotation descriptors.
 */
class RouteDescriptorBuilder
{
	private string $method = '*';
	private string $pattern = '*';
	private string $host = '*';
	private int $priority = 0;

	/**
	 * Static factory method to create a new builder instance.
	 * @return RouteDescriptorBuilder A new builder instance
	 */
	public static function create(): self
	{
		return new self();
	}

	/**
	 * Set the HTTP method for the route descriptor.
	 * 
	 * @param string $method The HTTP method to set for the route descriptor (e.g., "GET", "POST", "PUT", "DELETE", etc.); this will be used when matching incoming requests to determine if the route should be considered a match based on the request's HTTP method.
	 * @return self
	 */
	public function withMethod(string $method): self
	{
		$this->method = $method;
		return $this;
	}

	/**
	 * Set the URL pattern for the route descriptor.
	 * 
	 * @param string $pattern The URL pattern to set for the route descriptor; this can include static segments (e.g., "/users") and dynamic parameters (e.g., "/users/{id}"), allowing you to define how the route should match incoming request URLs when evaluating routes against requests.
	 * @return self
	 */
	public function withPattern(string $pattern): self
	{
		$this->pattern = $pattern;
		return $this;
	}

	/**
	 * Set the host for the route descriptor.
	 * 
	 * @param string $host The host value to set for the route descriptor; this can be a specific hostname (e.g., "example.com") or a wildcard ("*") to match any host, allowing you to specify which hosts the route should respond to when matching incoming requests.
	 * @return self
	 */
	public function withHost(string $host): self
	{
		$this->host = $host;
		return $this;
	}

	/**
	 * Set the priority for the route descriptor.
	 * 
	 * @param int $priority The priority value to set for the route descriptor; higher values indicate higher priority when matching routes, allowing you to control the order in which routes are evaluated and matched against incoming requests.
	 * @return self
	 */
	public function withPriority(int $priority): self
	{
		$this->priority = $priority;
		return $this;
	}

	/**
	 * Build and return a Route instance based on the builder's configuration.
	 * @return Route The built Route instance
	 */
	public function build(): Route
	{
		return new Route($this->method, $this->pattern, $this->host, $this->priority);
	}
}
