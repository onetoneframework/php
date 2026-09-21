<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Framework\Module;

use Clover\Framework\Module\Exception\ModuleNotFoundException;
use Clover\Implement\ModuleInterface;
use InvalidArgumentException;
use RuntimeException;
use function sprintf;

/**
 * Registry of loaded application modules keyed by module name.
 */
final class ModuleRegistry
{
	/** @var array<string, ModuleInterface> */
	private array $modules = [];

	public function register(ModuleInterface $module): void
	{
		$moduleName = $module->getName();

		if ($moduleName === '') {
			throw new InvalidArgumentException('A module name must not be empty.');
		}

		if ($this->has($moduleName)) {
			throw new RuntimeException(sprintf('Module "%s" is already registered.', $moduleName));
		}

		$this->modules[$moduleName] = $module;
	}

	public function has(string $moduleName): bool
	{
		return array_key_exists($moduleName, $this->modules);
	}

	public function get(string $moduleName): ModuleInterface
	{
		if (!$this->has($moduleName)) {
			throw new ModuleNotFoundException($moduleName);
		}

		return $this->modules[$moduleName];
	}

	/**
	 * @return ModuleInterface[]
	 */
	public function all(): array
	{
		return array_values($this->modules);
	}

	/**
	 * @return string[]
	 */
	public function names(): array
	{
		return array_keys($this->modules);
	}
}
