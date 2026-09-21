<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Component\Routing;

use Clover\Component\Contract\RequestHandlerInterface;
use Clover\Component\Http\Request;
use Clover\Component\Http\Response;

/**
 * Route Request Handler
 *
 * The bottom of the middleware pipeline: runs one already-matched route.
 *
 * Matching happened before the pipeline was built, because the route is what decides which
 * middleware the pipeline contains. This handler therefore does not match again - it runs the
 * route it was given, with the parameters that match produced.
 */
final class RouteRequestHandler implements RequestHandlerInterface
{
	/**
	 * @var Router The router that matched the route.
	 */
	private Router $router;

	/**
	 * @var Route The matched route.
	 */
	private Route $route;

	/**
	 * @var array<string, string|null> Parameters extracted from the URI.
	 */
	private array $parameters;

	/**
	 * @param Router                     $router     The router that matched the route.
	 * @param Route                      $route      The matched route.
	 * @param array<string, string|null> $parameters Parameters extracted from the URI.
	 */
	public function __construct(Router $router, Route $route, array $parameters)
	{
		$this->router = $router;
		$this->route = $route;
		$this->parameters = $parameters;
	}

	/**
	 * Run the matched route.
	 *
	 * @param Request $request The request that reached the end of the pipeline.
	 *
	 * @return Response
	 */
	public function handle(Request $request): Response
	{
		return $this->router->runRoute($this->route, $request, $this->parameters);
	}
}
