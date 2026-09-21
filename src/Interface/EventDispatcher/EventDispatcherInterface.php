<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Implement;

/**
 * Event Dispatcher Interface
 *
 * Defines the contract for event dispatching and listener management.
 * Provides methods for registering, dispatching, and managing event listeners.
 */
interface EventDispatcherInterface
{
	/**
	 * Add an event listener for a specific event name.
	 *
	 * @param string $eventName The name of the event to listen for.
	 * @param callable $listener The callback function to execute when the event is dispatched.
	 * @return void
	 */
	public function addListener(string $eventName, callable $listener): void;

	/**
	 * Dispatch an event to all registered listeners.
	 *
	 * @param object $event The event object to dispatch.
	 * @param string|null $eventName Optional event name. If not provided, the event class name is used.
	 * @return mixed The result of the event dispatch.
	 */
	public function dispatch(object $event, ?string $eventName = null);

	/**
	 * Emit an event and return the event object or boolean result.
	 *
	 * @param object $event The event object to emit.
	 * @return object|bool The event object or boolean result.
	 */
	public function emit(object $event): object|bool;

	/**
	 * Get all listeners for a specific event name.
	 *
	 * @param string $eventName The event name. Empty string returns all listeners.
	 * @return array|callable[] Array of listener callbacks.
	 */
	public function getListeners(string $eventName = '');

	/**
	 * Get the count of listeners.
	 *
	 * @param iterable|null $listeners Optional iterable of listeners to count.
	 * @return int The number of listeners.
	 */
	public function getListenersCount(?iterable $listeners = array()): int;

	/**
	 * Check if a listener exists for a specific event name.
	 *
	 * @param string $eventName The event name to check.
	 * @return bool True if listeners exist, false otherwise.
	 */
	public function hasListener(string $eventName): bool;

	/**
	 * Remove a specific listener for an event name.
	 *
	 * @param string $eventName The event name.
	 * @param callable $listener The listener callback to remove.
	 * @return bool True if the listener was removed, false otherwise.
	 */
	public function removeListener(string $eventName, callable $listener);

	/**
	 * Queue an event for asynchronous processing.
	 *
	 * @param object $event The event object to queue.
	 * @param string|null $eventName Optional event name override.
	 * @return void
	 */
	public function queue(object $event, ?string $eventName = null): void;

	/**
	 * Process queued async events.
	 *
	 * @param int|null $maxMessages Optional max number of messages to process in this call.
	 * @return void
	 */
	public function processQueue(?int $maxMessages = null): void;
}
