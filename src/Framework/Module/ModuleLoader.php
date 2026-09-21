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
use Clover\Framework\Module\Exception\ModuleCircularDependencyException;
use Clover\Implement\ModuleInterface;
use RuntimeException;
use function array_key_exists;
use function in_array;
use function is_array;
use function sprintf;
use function str_replace;
use function ucwords;

/**
 * Discovers, orders, registers, and boots application modules.
 */
final class ModuleLoader
{
	public function __construct(
		private readonly string $modulesDirectory,
		private readonly string $configurationFilePath,
	) {
	}

	public function load(Container $container): ModuleRegistry
	{
		$configuration = $this->loadConfiguration();
		$discoveredModules = $this->discoverModules();
		$enabledModules = $this->filterEnabledModules($discoveredModules, $configuration);
		$orderedModules = $this->sortByDependencies($enabledModules, $configuration);
		$registry = new ModuleRegistry();

		foreach ($orderedModules as $module) {
			$registry->register($module);
		}

		if (!$container->set(ModuleRegistry::class, $registry)) {
			throw new RuntimeException('Module registry could not be registered in the container.');
		}

		if (!$container->set(ModuleManager::class, new ModuleManager($registry, $container))) {
			throw new RuntimeException('Module manager could not be registered in the container.');
		}

		foreach ($orderedModules as $module) {
			$module->register($container);
		}

		foreach ($orderedModules as $module) {
			$module->boot($container);
		}

		return $registry;
	}

	private function loadConfiguration(): ModuleConfiguration
	{
		if (!FileHandler::isExists($this->configurationFilePath)) {
			return ModuleConfiguration::fromArray([]);
		}

		/** @var mixed $configuration */
		$configuration = require $this->configurationFilePath;

		if (!is_array($configuration)) {
			throw new RuntimeException(sprintf(
				'Module configuration must return an array: %s',
				$this->configurationFilePath
			));
		}

		return ModuleConfiguration::fromArray($configuration);
	}

	/**
	 * @return array<string, ModuleInterface>
	 */
	private function discoverModules(): array
	{
		if (!DirectoryHandler::exists($this->modulesDirectory)) {
			return [];
		}

		$discoveredModules = [];
		$moduleDirectories = DirectoryHandler::getList($this->modulesDirectory, 'path', true, false);

		if (!is_array($moduleDirectories)) {
			throw new RuntimeException(sprintf('Module directory could not be read: %s', $this->modulesDirectory));
		}

		foreach ($moduleDirectories as $moduleDirectory) {
			$moduleName = basename($moduleDirectory);
			$moduleClassName = $this->resolveModuleClassName($moduleName);
			$moduleClassFilePath = $moduleDirectory
				. DIRECTORY_SEPARATOR
				. $this->resolveModuleClassShortName($moduleName)
				. '.php';

			if (!FileHandler::isExists($moduleClassFilePath) || !class_exists($moduleClassName)) {
				continue;
			}

			$module = new $moduleClassName($moduleDirectory);

			if (!$module instanceof ModuleInterface) {
				throw new RuntimeException(sprintf(
					'Module class "%s" must implement ModuleInterface.',
					$moduleClassName
				));
			}

			$moduleName = $module->getName();

			if (array_key_exists($moduleName, $discoveredModules)) {
				throw new RuntimeException(sprintf('Module "%s" was discovered more than once.', $moduleName));
			}

			$discoveredModules[$moduleName] = $module;
		}

		return $discoveredModules;
	}

	/**
	 * @param array<string, ModuleInterface> $discoveredModules
	 * @return array<string, ModuleInterface>
	 */
	private function filterEnabledModules(array $discoveredModules, ModuleConfiguration $configuration): array
	{
		$enabledModules = [];

		foreach ($discoveredModules as $moduleName => $module) {
			if ($configuration->isEnabled($moduleName)) {
				$enabledModules[$moduleName] = $module;
			}
		}

		return $enabledModules;
	}

	/**
	 * @param array<string, ModuleInterface> $modules
	 * @return ModuleInterface[]
	 */
	private function sortByDependencies(array $modules, ModuleConfiguration $configuration): array
	{
		$sortedModules = [];
		$visitedModules = [];
		$visitStack = [];

		foreach ($configuration->order as $orderedModuleName) {
			if (!array_key_exists($orderedModuleName, $modules)) {
				continue;
			}

			$this->visitModule(
				$orderedModuleName,
				$modules,
				$visitedModules,
				$visitStack,
				$sortedModules
			);
		}

		foreach (array_keys($modules) as $moduleName) {
			$this->visitModule(
				$moduleName,
				$modules,
				$visitedModules,
				$visitStack,
				$sortedModules
			);
		}

		return $sortedModules;
	}

	/**
	 * @param array<string, ModuleInterface> $modules
	 * @param array<string, bool> $visitedModules
	 * @param string[] $visitStack
	 * @param ModuleInterface[] $sortedModules
	 */
	private function visitModule(
		string $moduleName,
		array $modules,
		array &$visitedModules,
		array &$visitStack,
		array &$sortedModules,
	): void {
		if (array_key_exists($moduleName, $visitedModules)) {
			return;
		}

		if (!array_key_exists($moduleName, $modules)) {
			throw new RuntimeException(sprintf(
				'Module "%s" depends on a module that is not registered or enabled.',
				$moduleName
			));
		}

		if (in_array($moduleName, $visitStack, true)) {
			$dependencyChain = array_merge($visitStack, [$moduleName]);
			throw new ModuleCircularDependencyException($dependencyChain);
		}

		$visitStack[] = $moduleName;

		foreach ($modules[$moduleName]->getDependencies() as $dependencyName) {
			if (!array_key_exists($dependencyName, $modules)) {
				throw new RuntimeException(sprintf(
					'Module "%s" requires module "%s", which is not registered or enabled.',
					$moduleName,
					$dependencyName
				));
			}

			$this->visitModule(
				$dependencyName,
				$modules,
				$visitedModules,
				$visitStack,
				$sortedModules
			);
		}

		array_pop($visitStack);
		$visitedModules[$moduleName] = true;
		$sortedModules[] = $modules[$moduleName];
	}

	private function resolveModuleClassName(string $moduleDirectoryName): string
	{
		$namespaceSegment = $this->resolveModuleNamespaceSegment($moduleDirectoryName);

		return sprintf('App\\Modules\\%s\\%s', $namespaceSegment, $this->resolveModuleClassShortName($moduleDirectoryName));
	}

	private function resolveModuleClassShortName(string $moduleDirectoryName): string
	{
		return $this->resolveModuleNamespaceSegment($moduleDirectoryName) . 'Module';
	}

	private function resolveModuleNamespaceSegment(string $moduleDirectoryName): string
	{
		$normalizedName = str_replace(['-', '_'], ' ', $moduleDirectoryName);
		$pascalCaseName = str_replace(' ', '', ucwords($normalizedName));

		return $pascalCaseName !== '' ? $pascalCaseName : ucfirst($moduleDirectoryName);
	}
}
