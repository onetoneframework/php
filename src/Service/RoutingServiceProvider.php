<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Service;

use Clover\Classes\DependencyInjection\Container as BaseContainer;
use Clover\Component\Foundation\Application;
use Clover\Component\Routing\Router;
use Clover\Support\ServiceProvider;
use RuntimeException;
use function is_callable;
use function is_file;
use function sprintf;
use const DIRECTORY_SEPARATOR;

/**
 * Routing Service Provider
 *
 * Registers the router and loads the application's route definitions into it.
 *
 * Registration binds the router; loading routes happens in `boot()`, because a route's action may
 * name a controller whose dependencies other providers are still registering.
 */
class RoutingServiceProvider extends ServiceProvider
{
	/**
	 * File under the configuration directory that declares the application's routes.
	 */
	public const ROUTE_FILE = 'routes.php';

	/**
	 * Register the router service.
	 *
	 * @return void
	 */
	public function register(): void
	{
		if ($this->app instanceof BaseContainer && $this->app->has(Router::class)) {
			return;
		}

		$container = $this->app;

		$this->app->singleton(Router::class, static function () use ($container): Router {
			// The router resolves controllers through the container, so a controller can declare
			// its dependencies in its constructor like any other service.
			return new Router($container);
		});
	}

	/**
	 * Load the application's routes.
	 *
	 * The route file returns `callable(Router, Application): void`, matching how
	 * `dependencies.php` and `schedule.php` are written in this tree. An application with no route
	 * file is left with an empty router rather than an error - that is a new application, not a
	 * broken one.
	 *
	 * @throws RuntimeException When the route file exists but does not return a callable.
	 *
	 * @return void
	 */
	public function boot(): void
	{
		if (!$this->app instanceof Application) {
			return;
		}

		$routeFile = $this->app->getConfigurePath() . DIRECTORY_SEPARATOR . static::ROUTE_FILE;

		if (!is_file($routeFile)) {
			return;
		}

		$defineRoutes = require $routeFile;

		if (!is_callable($defineRoutes)) {
			throw new RuntimeException(sprintf(
				'`%s` must return a callable(Router, Application): void.',
				$routeFile
			));
		}

		$router = $this->app->make(Router::class);

		if (!$router instanceof Router) {
			throw new RuntimeException(sprintf('The resolved router must be an instance of %s.', Router::class));
		}

		$defineRoutes($router, $this->app);
	}
}
