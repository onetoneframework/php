<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 *
 * Routes for the `application` entry point (`APP_ENTRY_POINT=application`).
 *
 * `RoutingServiceProvider::boot()` runs this once the container is complete, so an action may name
 * a controller with constructor dependencies - the router resolves it through the container.
 *
 * These are this stack's own routes. They are not the routes the `runtime` entry point serves:
 * those are discovered from controller annotations and route providers by
 * `Clover\Framework\Routing\RouteRegistry`, against a different `Router` class with a different
 * API. The two route tables do not share definitions today - see AGENTS.md "Entry point".
 */

use Clover\Component\Foundation\Application;
use Clover\Component\Http\Request;
use Clover\Component\Routing\Router;

return static function (Router $router, Application $application): void {
	$router->get('/health', static fn(): array => [
		'status' => 'ok',
		'entry_point' => 'application',
	])->name('health');

	$router->get('/', static fn(): string => 'Onetone application kernel')->name('home');

	$router->get('/echo/{value}', static fn(Request $request, string $value): array => [
		'value' => $value,
		'trimmed_query' => $request->getAttribute(App\Middleware\TrimStringsMiddleware::ATTRIBUTE, []),
	])->middleware('trim')->name('echo');
};
