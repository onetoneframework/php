<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Tests\Classes\Event;

use Clover\Classes\Event\Dispatcher;
use Clover\Classes\Event\EventBus;
use Clover\Classes\Event\EventEnvelope;
use Clover\Implement\EventBusInterface;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class EventBusTest extends TestCase
{
	public function testPublishDispatchesEnvelopeAndPayload(): void
	{
		$bus = new EventBus(new Dispatcher());
		$envelopes = [];
		$payloads = [];
		$bus->subscribe(EventBusInterface::ENVELOPE_EVENT_NAME, static function (EventEnvelope $envelope) use (&$envelopes): void {
			$envelopes[] = $envelope;
		});
		$bus->subscribe('fixture.created', static function (EventBusFixture $event) use (&$payloads): void {
			$payloads[] = $event;
		});
		$payload = new EventBusFixture('fixture-1');

		$bus->publish($payload, 'fixture.created');

		self::assertCount(1, $envelopes);
		self::assertCount(1, $payloads);
		self::assertSame($payload, $envelopes[0]->payload);
		self::assertSame('fixture.created', $envelopes[0]->name);
		self::assertSame($payload, $payloads[0]);
	}

	public function testMiddlewareRunsInRegistrationOrderAndCanReplaceEnvelope(): void
	{
		$bus = new EventBus(new Dispatcher());
		$order = [];
		$receivedEnvelope = null;
		$bus->addMiddleware(static function (EventEnvelope $envelope, callable $next) use (&$order): void {
			$order[] = 'first.before';
			$next($envelope->withHeader('processed_by', 'first'));
			$order[] = 'first.after';
		});
		$bus->addMiddleware(static function (EventEnvelope $envelope, callable $next) use (&$order): void {
			$order[] = 'second.before';
			$next($envelope);
			$order[] = 'second.after';
		});
		$bus->subscribe(EventBusInterface::ENVELOPE_EVENT_NAME, static function (EventEnvelope $envelope) use (&$order, &$receivedEnvelope): void {
			$order[] = 'listener';
			$receivedEnvelope = $envelope;
		});

		$bus->publish(new EventBusFixture('fixture-1'));

		self::assertSame(
			['first.before', 'second.before', 'listener', 'second.after', 'first.after'],
			$order
		);
		self::assertInstanceOf(EventEnvelope::class, $receivedEnvelope);
		self::assertSame('first', $receivedEnvelope->headers['processed_by']);
	}

	public function testMiddlewareCanStopPublication(): void
	{
		$bus = new EventBus(new Dispatcher());
		$listenerCalls = 0;
		$bus->addMiddleware(static function (EventEnvelope $envelope, callable $next): void {
		});
		$bus->subscribe(EventBusFixture::class, static function () use (&$listenerCalls): void {
			$listenerCalls++;
		});

		$bus->publish(new EventBusFixture('fixture-1'));

		self::assertSame(0, $listenerCalls);
	}

	public function testStrictModeReportsNewListenerFailureAfterOtherListenersRun(): void
	{
		$bus = new EventBus(new Dispatcher());
		$successfulListenerCalls = 0;
		$bus->subscribe(EventBusFixture::class, static function (): void {
			throw new RuntimeException('listener failed');
		}, 10);
		$bus->subscribe(EventBusFixture::class, static function () use (&$successfulListenerCalls): void {
			$successfulListenerCalls++;
		});
		$bus->setStrictListenerFailure(true);

		try {
			$bus->publish(new EventBusFixture('fixture-1'));
			self::fail('Strict mode must report listener failures.');
		} catch (RuntimeException $exception) {
			self::assertStringContainsString('listener failed', $exception->getMessage());
		}

		self::assertSame(1, $successfulListenerCalls);
	}
}

final class EventBusFixture
{
	public function __construct(public readonly string $identifier)
	{
	}
}
