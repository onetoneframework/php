<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Implement;

use Clover\Classes\Broadcasting\Channel;

/**
 * Marks a domain event as broadcastable.
 *
 * The event declares:
 *  - which channels it publishes to
 *  - how it wants to be named on the wire
 *  - the serialized payload to carry (already safe for external consumers)
 *
 * Rationale: keeping this knowledge on the event itself (rule 29 in
 * AGENTS.md — behavior inside the domain-owned type) prevents callers
 * from duplicating channel/name logic at every dispatch site.
 */
interface ShouldBroadcastInterface
{
	/**
	 * @return array<int, Channel>
	 */
	public function broadcastOn(): array;

	/**
	 * Stable event name clients subscribe to. Defaults can be derived
	 * from the implementing class, but implementations must return a
	 * concrete string so the wire contract is explicit.
	 */
	public function broadcastAs(): string;

	/**
	 * Data payload to transmit. Must already exclude sensitive fields
	 * (AGENTS.md rule 69 — do not log sensitive data); the broadcaster
	 * treats whatever is returned here as public.
	 *
	 * @return array<string, mixed>
	 */
	public function broadcastPayload(): array;
}
