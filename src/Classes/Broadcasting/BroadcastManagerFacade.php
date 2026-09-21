<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Broadcasting;

use Clover\Classes\DependencyInjection\Container as LegacyContainer;
use Clover\Contract\ContainerInterface;
use Clover\Implement\BroadcastManagerInterface;
use Throwable;
use function method_exists;

/**
 * Container-aware static entry point for the broadcasting subsystem.
 *
 * Mirrors the {@see \Clover\Classes\Event\EventManager} pattern so the
 * two subsystems have consistent ergonomics:
 *  - when a DI container is registered the facade resolves the
 *    manager through it (keeping test-time wiring intact);
 *  - otherwise a process-local singleton is returned so callers do
 *    not have to thread the manager through every layer.
 */
final class BroadcastManagerFacade
{
	/** The singleton instance when no container is used. */
	private static ?BroadcastManagerInterface $instance = null;
	/** The container used for resolving the manager, if any. */
	private static ContainerInterface|LegacyContainer|null $container = null;

	/**
	 * Returns the active manager, resolving via container when present.
	 * 
	 * @return BroadcastManagerInterface
	 */
	public static function getManager(): BroadcastManagerInterface
	{
		if (self::$container !== null) {
			try {
				$resolved = self::resolveFromContainer();
				if ($resolved instanceof BroadcastManagerInterface) {
					return $resolved;
				}
			} catch (Throwable) {
				// Fallback to the singleton if the container cannot resolve yet.
			}
		}

		if (self::$instance === null) {
			self::$instance = new BroadcastManager();
		}

		return self::$instance;
	}

	/**
	 * Overwrite the singleton instance. Intended for bootstrap and tests.
	 * 
	 * @param BroadcastManagerInterface $manager The manager instance to set as the singleton
	 */
	public static function setInstance(BroadcastManagerInterface $manager): void
	{
		self::$instance = $manager;
	}

	/**
	 * Register the container used for lookups. Subsequent
	 * {@see getManager()} calls prefer the container result.
	 * 
	 * @param ContainerInterface|LegacyContainer $container The container to use for resolving the manager
	 */
	public static function setContainer(ContainerInterface|LegacyContainer $container): void
	{
		self::$container = $container;
	}

	/**
	 * Reset facade state. Intended for tests only; not a runtime API.
	 */
	public static function reset(): void
	{
		self::$instance = null;
		self::$container = null;
	}

	private static function resolveFromContainer(): mixed
	{
		if (self::$container === null) {
			return null;
		}

		if (method_exists(self::$container, 'make')) {
			return self::$container->make(BroadcastManagerInterface::class);
		}

		if (method_exists(self::$container, 'get')) {
			return self::$container->get(BroadcastManagerInterface::class);
		}

		return null;
	}
}
