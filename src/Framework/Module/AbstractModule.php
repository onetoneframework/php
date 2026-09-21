<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Framework\Module;

use Clover\Classes\DependencyInjection\Container;
use Clover\Classes\Directory\Handler as DirectoryHandler;
use Clover\Classes\File\Handler as FileHandler;
use Clover\Classes\OperationSystem;
use Clover\Classes\Routing\Router;
use Clover\Implement\ModuleInterface;
use RuntimeException;
use function is_callable;
use function sprintf;

/**
 * Base module with conventional directory layout for routes and dependencies.
 */
abstract class AbstractModule implements ModuleInterface
{
	public function __construct(protected readonly string $path)
	{
	}

	/**
	 * @return string[]
	 */
	public function getDependencies(): array
	{
		return [];
	}

	/**
	 * @return string[]
	 */
	public function getExportedServiceIdentifiers(): array
	{
		return [];
	}

	public function register(Container $container): void
	{
		$dependencyConfigurePath = $this->path . DIRECTORY_SEPARATOR . 'Configure' . DIRECTORY_SEPARATOR . 'dependencies.php';

		if (!FileHandler::isExists($dependencyConfigurePath)) {
			return;
		}

		/** @var mixed $dependencyConfigure */
		$dependencyConfigure = require $dependencyConfigurePath;

		if (!is_callable($dependencyConfigure)) {
			throw new RuntimeException(sprintf(
				'Module dependency configuration must return a callable: %s',
				$dependencyConfigurePath
			));
		}

		$dependencyConfigure($container);
	}

	public function boot(Container $container): void
	{
	}

	public function registerRoutes(Router $router): void
	{
		$controllerPath = $this->getControllerPath();

		if (DirectoryHandler::exists($controllerPath)) {
			$router->fromDirectory($controllerPath);
		}

		$commandPath = $this->getCommandPath();

		if (OperationSystem::isCommandLineInterface() && DirectoryHandler::exists($commandPath)) {
			$router->fromDirectory($commandPath);
		}
	}

	public function getControllerPath(): string
	{
		return $this->path . DIRECTORY_SEPARATOR . 'Controller';
	}

	public function getCommandPath(): string
	{
		return $this->path . DIRECTORY_SEPARATOR . 'Command';
	}
}
