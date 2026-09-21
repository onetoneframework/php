<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Event;

use DateTimeImmutable;
use function bin2hex;
use function random_bytes;

/**
 * EventEnvelope is a wrapper for event payloads that includes metadata such as event name, ID, timestamps, and headers
 */
final class EventEnvelope
{
	/**
	 * EventEnvelope constructor.
	 * 
	 * @param object $payload The actual event data being wrapped
	 * @param string $name The name of the event (typically the class name of the payload)
	 * @param string $eventId A unique identifier for this event instance
	 * @param DateTimeImmutable $occurredAt The timestamp when the event occurred
	 * @param string|null $correlationId Optional correlation ID for tracing related events across systems
	 * @param string|null $causationId Optional causation ID for linking this event to its cause
	 * @param string|null $tenantId Optional tenant ID for multi-tenant applications
	 * @param array $headers Optional additional headers to include with the event (e.g. for routing or processing metadata)
	 */
	public function __construct(
		public readonly object $payload,
		public readonly string $name,
		public readonly string $eventId,
		public readonly DateTimeImmutable $occurredAt,
		public readonly ?string $correlationId = null,
		public readonly ?string $causationId = null,
		public readonly ?string $tenantId = null,
		public readonly array $headers = [],
	) {
	}

	/**
	 * Wrap a raw event payload into an EventEnvelope, automatically generating metadata such as event ID and timestamp
	 * 
	 * @param object $payload The raw event payload to wrap
	 * @param string|null $name Optional explicit event name (if not provided, the class name of the payload will be used)
	 * @param array $headers Optional headers to include in the envelope (e.g. correlation_id, causation_id, tenant_id)
	 * @return self An EventEnvelope instance containing the wrapped event and its metadata
	 */
	public static function wrap(object $payload, ?string $name = null, array $headers = []): self
	{
		return new self(
			payload: $payload,
			name: $name ?? $payload::class,
			eventId: self::newEventId(),
			occurredAt: new DateTimeImmutable(),
			correlationId: $headers['correlation_id'] ?? null,
			causationId: $headers['causation_id'] ?? null,
			tenantId: $headers['tenant_id'] ?? null,
			headers: $headers
		);
	}

	/**
	 * Create a new EventEnvelope instance with an additional header, preserving immutability
	 * 
	 * @param string $key The header key to add or overwrite
	 * @param mixed $value The value of the header to set
	 * @return self A new EventEnvelope instance with the updated headers
	 */
	public function withHeader(string $key, mixed $value): self
	{
		$headers = $this->headers;
		$headers[$key] = $value;

		return new self(
			payload: $this->payload,
			name: $this->name,
			eventId: $this->eventId,
			occurredAt: $this->occurredAt,
			correlationId: $this->correlationId,
			causationId: $this->causationId,
			tenantId: $this->tenantId,
			headers: $headers
		);
	}

	/**
	 * Generate a new unique event ID using random bytes
	 * 
	 * @return string A new unique event ID as a hexadecimal string
	 */
	private static function newEventId(): string
	{
		return bin2hex(random_bytes(16));
	}
}
