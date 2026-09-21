<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace App\Http;

use App\Middleware\TrimStringsMiddleware;
use Clover\Component\Kernel\HttpKernel;

/**
 * Application HTTP Kernel
 *
 * The application's own kernel. `App/Configure/kernel.php` binds it over the framework default,
 * so everything below is this application's answer rather than the framework's.
 *
 * Override the lists, not the methods. The framework kernel already knows how to expand a group,
 * resolve an alias and build the pipeline; what it cannot know is which layers this application
 * wants and in what order.
 */
class Kernel extends HttpKernel
{
	/**
	 * @var array<int, class-string> Layers every request passes through, outermost first.
	 */
	protected array $middleware = [
		TrimStringsMiddleware::class,
	];

	/**
	 * @var array<string, array<int, string>> Named bundles a route can ask for.
	 */
	protected array $middlewareGroups = [
		'web' => [],
		'api' => [],
	];

	/**
	 * @var array<string, class-string> Aliases a route can ask for by name.
	 */
	protected array $routeMiddleware = [
		'trim' => TrimStringsMiddleware::class,
	];
}
