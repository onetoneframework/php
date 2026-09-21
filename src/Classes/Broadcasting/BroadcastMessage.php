<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Broadcasting;

use DateTimeImmutable;
use DateTimeZone;
use function array_values;
use function bin2hex;
use function random_bytes;

/**
 * Immutable value object carrying one broadcast towards the drivers.
 *
 * A message is the single argument a driver receives; this keeps the
 * {@see \Clover\Implement\BroadcasterInterface} method signature
 * parameter-lean (AGENTS.md rule 56) and guarantees callers cannot
 * mutate the payload mid-flight.
 */
final class BroadcastMessage
{
	/**
	 * The constructor is public to allow direct construction, but the Direct construction is allowed but the {@see create()} factory is the
	 * @param array<int, Channel>   $channels The list of channels the message should be broadcast on
	 * @param array<string, mixed>  $payload The message payload, an arbitrary associative array
	 * @param array<string, string> $headers Optional headers to send with the message, keyed by header name
	 * @param string $eventName The name of the event being broadcast
	 * @param string $messageId A unique ID for the message, used for deduplication and tracing. The factory method fills this in automatically, but it can be set manually if the caller needs to correlate the broadcast with an external event or source record.
	 * @param DateTimeImmutable $occurredAt The timestamp of when the event occurred.
	 */
	public function __construct(
		public readonly array $channels,
		public readonly string $eventName,
		public readonly array $payload,
		public readonly string $messageId,
		public readonly DateTimeImmutable $occurredAt,
		public readonly array $headers = [],
	) {
	}

	/**
	 * Preferred construction path. Fills in id + timestamp so callers
	 * do not rewrite that boilerplate at every dispatch site.
	 *
	 * @param array<int, Channel>   $channels The list of channels the message should be broadcast on
	 * @param string $eventName The name of the event being broadcast
	 * @param array<string, mixed>  $payload The message payload, an arbitrary associative array
	 * @param array<string, string> $headers Optional headers to send with the message, keyed by header name
	 */
	public static function create(array $channels, string $eventName, array $payload, array $headers = []): self
	{
		return new self(
			channels: array_values($channels),
			eventName: $eventName,
			payload: $payload,
			messageId: bin2hex(random_bytes(16)),
			occurredAt: new DateTimeImmutable('now', new DateTimeZone('UTC')),
			headers: $headers,
		);
	}

	/**
	 * Transport-ready channel names (with tier prefixes already applied).
	 *
	 * @return array<int, string>
	 */
	public function getTransportChannelNames(): array
	{
		$out = [];
		foreach ($this->channels as $channel) {
			$out[] = $channel->getTransportName();
		}

		return $out;
	}
}
