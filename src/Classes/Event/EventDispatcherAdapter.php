<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Event;

final class EventDispatcherAdapter
{
	/** @var callable(object): void */
	private $dispatcher;

	/**
	 * EventDispatcherAdapter constructor.
	 * 
	 * @param callable(object): void $dispatcher
	 */
	public function __construct(callable $dispatcher)
	{
		$this->dispatcher = $dispatcher;
	}

	/**
	 * Create an EventDispatcherAdapter that dispatches events through the global EventManager singleton
	 * 
	 * @return self
	 */
	public static function fromEventManager(): self
	{
		return new self(static function (object $event): void {
			EventManager::getInstance()->dispatch($event);
		});
	}

	/**
	 * Create an EventDispatcherAdapter that dispatches events through a provided Dispatcher instance
	 * 
	 * @param Dispatcher $dispatcher The Dispatcher instance to use for dispatching events
	 * @return self
	 */
	public static function fromDispatcher(Dispatcher $dispatcher): self
	{
		return new self(static function (object $event) use ($dispatcher): void {
			$dispatcher->dispatch($event);
		});
	}

	/**
	 * Dispatch an event using the underlying dispatcher callable
	 * 
	 * @param object $event The event object to dispatch
	 * @return void
	 */
	public function dispatch(object $event): void
	{
		($this->dispatcher)($event);
	}
}
