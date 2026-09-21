<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Implement;

use Clover\Classes\DependencyInjection\Container;
use Clover\Classes\Routing\Router;

/**
 * Contract for application feature modules.
 */
interface ModuleInterface
{
	/**
	 * Unique module identifier used for dependency resolution and cross-module calls.
	 */
	public function getName(): string;

	/**
	 * Module names that must be loaded before this module.
	 *
	 * @return string[]
	 */
	public function getDependencies(): array;

	/**
	 * Service identifiers that other modules may resolve through the module manager.
	 *
	 * @return string[]
	 */
	public function getExportedServiceIdentifiers(): array;

	/**
	 * Register module services in the container.
	 */
	public function register(Container $container): void;

	/**
	 * Bootstrap module services after all modules are registered.
	 */
	public function boot(Container $container): void;

	/**
	 * Register HTTP and CLI routes exposed by the module.
	 */
	public function registerRoutes(Router $router): void;
}
