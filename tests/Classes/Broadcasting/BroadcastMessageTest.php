<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Broadcasting;

use Clover\Classes\Broadcasting\BroadcastMessage;
use Clover\Classes\Broadcasting\Channel;
use Clover\Classes\Broadcasting\PresenceChannel;
use Clover\Classes\Broadcasting\PrivateChannel;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

final class BroadcastMessageTest extends TestCase
{
	public function testCreateReindexesChannelsAndPreservesPayloadAndHeaders(): void
	{
		$public = new Channel('orders');
		$private = new PrivateChannel('user.42');

		$message = BroadcastMessage::create(
			channels: [4 => $public, 9 => $private],
			eventName: 'order.updated',
			payload: ['id' => 42, 'state' => 'ready'],
			headers: ['X-Trace-Id' => 'trace-1']
		);

		$this->assertSame([$public, $private], $message->channels);
		$this->assertSame('order.updated', $message->eventName);
		$this->assertSame(['id' => 42, 'state' => 'ready'], $message->payload);
		$this->assertSame(['X-Trace-Id' => 'trace-1'], $message->headers);
	}

	public function testCreateGeneratesHexIdentifierAndUtcTimestampWithinCallWindow(): void
	{
		$before = new DateTimeImmutable('now', new DateTimeZone('UTC'));
		$message = BroadcastMessage::create([new Channel('events')], 'event', []);
		$after = new DateTimeImmutable('now', new DateTimeZone('UTC'));

		$this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $message->messageId);
		$this->assertSame('UTC', $message->occurredAt->getTimezone()->getName());
		$this->assertGreaterThanOrEqual($before->getTimestamp(), $message->occurredAt->getTimestamp());
		$this->assertLessThanOrEqual($after->getTimestamp(), $message->occurredAt->getTimestamp());
	}

	public function testSeparateFactoryCallsGenerateDifferentMessageIds(): void
	{
		$first = BroadcastMessage::create([], 'event', []);
		$second = BroadcastMessage::create([], 'event', []);

		$this->assertNotSame($first->messageId, $second->messageId);
	}

	public function testTransportNamesRespectChannelTiersAndOrder(): void
	{
		$message = BroadcastMessage::create(
			[
				new Channel('public.room'),
				new PrivateChannel('private.room'),
				new PresenceChannel('presence.room'),
			],
			'room.updated',
			[]
		);

		$this->assertSame(
			['public.room', 'private-private.room', 'presence-presence.room'],
			$message->getTransportChannelNames()
		);
	}

	public function testChannelStringRepresentationUsesTransportName(): void
	{
		$this->assertSame('room', (string) new Channel('room'));
		$this->assertSame('private-room', (string) new PrivateChannel('room'));
		$this->assertSame('presence-room', (string) new PresenceChannel('room'));
	}

	public function testChannelAllowsEveryDocumentedTransportNeutralSeparator(): void
	{
		$channel = new Channel('tenant_42-orders.created:eu');

		$this->assertSame('tenant_42-orders.created:eu', $channel->getName());
		$this->assertSame('public', $channel->getTier());
	}
}
