<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Broadcasting;

use Clover\Exception\Broadcasting\UnknownBroadcasterException;
use Clover\Implement\BroadcasterInterface;
use Clover\Implement\BroadcastManagerInterface;
use Clover\Implement\ShouldBroadcastInterface;
use function array_key_first;
use function array_keys;

/**
 * Default orchestrator for the broadcasting subsystem.
 *
 * Holds the driver registry, picks the right one for each message,
 * and hands out {@see PendingBroadcast} builders for fluent dispatch.
 * The manager is intentionally dumb about transports — all transport
 * specifics live inside the {@see BroadcasterInterface} drivers.
 */
final class BroadcastManager implements BroadcastManagerInterface
{
	/** 
	 * @var array<string, BroadcasterInterface> $drivers Registered broadcaster drivers, keyed by name
	 **/
	private array $drivers = [];
	/** The name of the default driver to use when no driver is specified. */
	private ?string $defaultDriver = null;

	/**
	 * Register a driver with the manager.
	 *
	 * The first registered driver becomes the default if no default is
	 * explicitly set.
	 * 
	 * @param BroadcasterInterface $driver The driver instance to register
	 * @return void
	 */
	public function registerDriver(BroadcasterInterface $driver): void
	{
		$name = $driver->getName();
		$this->drivers[$name] = $driver;

		if ($this->defaultDriver === null) {
			$this->defaultDriver = $name;
		}
	}

	/**
	 * Set the default driver by name.
	 * 
	 * @param string $name The name of the driver to set as default
	 * @throws UnknownBroadcasterException if the driver name is not registered
	 */
	public function setDefaultDriver(string $name): void
	{
		if (!isset($this->drivers[$name])) {
			throw new UnknownBroadcasterException($name);
		}
		$this->defaultDriver = $name;
	}

	/**
	 * Get a driver instance by name, or the default if no name is given.
	 * 
	 * @param string|null $name The name of the driver to retrieve, or null for the default
	 * @throws UnknownBroadcasterException if the resolved driver name is not registered
	 */
	public function driver(?string $name = null): BroadcasterInterface
	{
		$resolved = $name ?? $this->defaultDriver ?? array_key_first($this->drivers);
		if ($resolved === null || !isset($this->drivers[$resolved])) {
			throw new UnknownBroadcasterException((string) $resolved);
		}

		return $this->drivers[$resolved];
	}

	/**
	 * Broadcast a message using the specified driver or the default if no driver name is given.
	 * 
	 * @param BroadcastMessage $message The message to broadcast
	 * @param string|null $driverName The name of the driver to use, or null for the default
	 * @throws UnknownBroadcasterException if the resolved driver name is not registered
	 */
	public function broadcast(BroadcastMessage $message, ?string $driverName = null): void
	{
		$this->driver($driverName)->broadcast($message);
	}

	/**
	 * Create a pending broadcast for the given event, which can be dispatched later.
	 * 
	 * @param ShouldBroadcastInterface $event The event to broadcast
	 * @return PendingBroadcast A pending broadcast instance for the given event
	 */
	public function event(ShouldBroadcastInterface $event): PendingBroadcast
	{
		return new PendingBroadcast($this, $event);
	}

	/**
	 * Get a list of registered driver names.
	 * 
	 * @return array<int, string> An array of registered driver names
	 */
	public function getRegisteredDriverNames(): array
	{
		return array_keys($this->drivers);
	}
}
