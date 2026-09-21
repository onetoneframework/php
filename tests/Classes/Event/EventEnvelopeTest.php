<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Tests\Classes\Event;

use Clover\Classes\Event\EventEnvelope;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class EventEnvelopeTest extends TestCase
{
	public function testWrapCreatesMetadataForPayload(): void
	{
		$payload = new EventEnvelopeFixture('created');
		$before = new DateTimeImmutable();

		$envelope = EventEnvelope::wrap($payload);
		$after = new DateTimeImmutable();

		self::assertSame($payload, $envelope->payload);
		self::assertSame(EventEnvelopeFixture::class, $envelope->name);
		self::assertMatchesRegularExpression('/\A[0-9a-f]{32}\z/', $envelope->eventId);
		self::assertGreaterThanOrEqual($before, $envelope->occurredAt);
		self::assertLessThanOrEqual($after, $envelope->occurredAt);
		self::assertNull($envelope->correlationId);
		self::assertSame([], $envelope->headers);
	}

	public function testWrapExtractsTracingHeadersAndKeepsAllHeaders(): void
	{
		$headers = [
			'correlation_id' => 'correlation-1',
			'causation_id' => 'cause-1',
			'tenant_id' => 'tenant-1',
			'content_type' => 'application/json',
		];

		$envelope = EventEnvelope::wrap(new EventEnvelopeFixture('updated'), 'fixture.updated', $headers);

		self::assertSame('fixture.updated', $envelope->name);
		self::assertSame('correlation-1', $envelope->correlationId);
		self::assertSame('cause-1', $envelope->causationId);
		self::assertSame('tenant-1', $envelope->tenantId);
		self::assertSame($headers, $envelope->headers);
	}

	public function testWithHeaderReturnsAnIndependentEnvelope(): void
	{
		$payload = new EventEnvelopeFixture('published');
		$occurredAt = new DateTimeImmutable('2026-07-21T12:00:00+00:00');
		$envelope = new EventEnvelope(
			$payload,
			'fixture.published',
			'event-1',
			$occurredAt,
			'correlation-1',
			null,
			null,
			['attempt' => 1]
		);

		$updated = $envelope->withHeader('attempt', 2);

		self::assertNotSame($envelope, $updated);
		self::assertSame(['attempt' => 1], $envelope->headers);
		self::assertSame(['attempt' => 2], $updated->headers);
		self::assertSame($payload, $updated->payload);
		self::assertSame('event-1', $updated->eventId);
		self::assertSame($occurredAt, $updated->occurredAt);
		self::assertSame('correlation-1', $updated->correlationId);
	}

	public function testWrappedEventsReceiveDifferentIdentifiers(): void
	{
		$payload = new EventEnvelopeFixture('created');

		$first = EventEnvelope::wrap($payload);
		$second = EventEnvelope::wrap($payload);

		self::assertNotSame($first->eventId, $second->eventId);
	}
}

final class EventEnvelopeFixture
{
	public function __construct(public readonly string $state)
	{
	}
}
