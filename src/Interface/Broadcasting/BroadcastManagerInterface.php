<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Implement;

use Clover\Classes\Broadcasting\BroadcastMessage;
use Clover\Classes\Broadcasting\PendingBroadcast;

/**
 * Orchestrator contract for the broadcasting subsystem.
 *
 * The manager is the single public surface the rest of the framework
 * should depend on. It routes messages to one or more registered
 * {@see BroadcasterInterface} drivers and exposes a fluent builder for
 * composing channels with a {@see ShouldBroadcastInterface} event.
 */
interface BroadcastManagerInterface
{
	/**
	 * Register a driver under a name. The first registered driver
	 * becomes the default unless {@see setDefaultDriver()} is called.
	 */
	public function registerDriver(BroadcasterInterface $driver): void;

	/**
	 * Switch the default driver used by {@see broadcast()} when no
	 * explicit driver name is provided.
	 */
	public function setDefaultDriver(string $name): void;

	/**
	 * Resolve a registered driver. Throws a typed exception when the
	 * driver does not exist.
	 */
	public function driver(?string $name = null): BroadcasterInterface;

	/**
	 * Push a concrete message to the selected driver (or the default).
	 */
	public function broadcast(BroadcastMessage $message, ?string $driverName = null): void;

	/**
	 * Begin a fluent build from a broadcastable event. The returned
	 * builder is the only thing that knows how to assemble channels
	 * and dispatch through the manager.
	 */
	public function event(ShouldBroadcastInterface $event): PendingBroadcast;
}
