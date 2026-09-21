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
use Clover\Implement\ModuleInterface;
use RuntimeException;
use function class_exists;
use function in_array;
use function interface_exists;
use function is_object;
use function sprintf;

/**
 * Resolves services and module instances across module boundaries.
 */
final class ModuleManager
{
	public function __construct(
		private readonly ModuleRegistry $moduleRegistry,
		private readonly Container $container,
	) {
	}

	public function has(string $moduleName): bool
	{
		return $this->moduleRegistry->has($moduleName);
	}

	public function get(string $moduleName): ModuleInterface
	{
		return $this->moduleRegistry->get($moduleName);
	}

	/**
	 * Resolve a service registered by the given module.
	 */
	public function service(string $moduleName, string $serviceIdentifier): object
	{
		$module = $this->moduleRegistry->get($moduleName);

		if (!in_array($serviceIdentifier, $module->getExportedServiceIdentifiers(), true)) {
			throw new RuntimeException(sprintf(
				'Service "%s" is not exported by module "%s".',
				$serviceIdentifier,
				$moduleName
			));
		}

		if (!$this->container->has($serviceIdentifier)) {
			throw new RuntimeException(sprintf(
				'Service "%s" is not registered for module "%s".',
				$serviceIdentifier,
				$moduleName
			));
		}

		$service = $this->container->get($serviceIdentifier);

		if (!is_object($service)) {
			throw new RuntimeException(sprintf(
				'Service "%s" resolved to a non-object value.',
				$serviceIdentifier
			));
		}

		if (
			(class_exists($serviceIdentifier) || interface_exists($serviceIdentifier))
			&& !$service instanceof $serviceIdentifier
		) {
			throw new RuntimeException(sprintf(
				'Service "%s" resolved to an incompatible object for module "%s".',
				$serviceIdentifier,
				$moduleName
			));
		}

		return $service;
	}
}
