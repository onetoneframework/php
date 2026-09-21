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

/**
 * Contract for broadcasting drivers.
 *
 * A broadcaster takes a {@see BroadcastMessage} and delivers it to the
 * underlying transport (SSE, WebSocket, event bus, external provider, ...).
 * Drivers must not mutate the incoming message; they may extract fields
 * from it but the domain-owned DTO is always the source of truth.
 */
interface BroadcasterInterface
{
	/**
	 * Deliver the message to the transport.
	 *
	 * Implementations must fail explicitly on unrecoverable errors
	 * (typed exceptions), never silently swallow faults.
	 */
	public function broadcast(BroadcastMessage $message): void;

	/**
	 * Short, stable identifier for this driver used in logs/config lookups.
	 * e.g. "null", "log", "sse", "event-bus".
	 */
	public function getName(): string;
}
