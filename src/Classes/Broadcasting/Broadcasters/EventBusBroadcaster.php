<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Broadcasting\Broadcasters;

use Clover\Classes\Broadcasting\BroadcastMessage;
use Clover\Implement\BroadcasterInterface;
use Clover\Implement\EventBusInterface;

/**
 * Republishes a broadcast onto the internal event bus.
 *
 * Intended for "fan-out locally first" topologies: the same message
 * goes through the in-process bus so any PHP-side listener can react
 * in addition to the external transport driver. Keeps the broadcast
 * pipeline vendor-neutral — the event bus is the project's own
 * canonical implementation (AGENTS.md rule 11).
 */
final class EventBusBroadcaster implements BroadcasterInterface
{
	/** The name of the event to publish on the event bus. */
	public const EVENT_NAME = 'broadcasting.message';

	/**
	 * EventBusBroadcaster constructor.
	 *
	 * @param EventBusInterface $eventBus
	 */
	public function __construct(private readonly EventBusInterface $eventBus)
	{
	}

	/**
	 * Broadcast the given message.
	 *
	 * @param BroadcastMessage $message
	 */
	public function broadcast(BroadcastMessage $message): void
	{
		$this->eventBus->publish($message, self::EVENT_NAME);
	}

	/**
	 * Get the name of the broadcaster.
	 *
	 * @return string
	 */
	public function getName(): string
	{
		return 'event-bus';
	}
}
