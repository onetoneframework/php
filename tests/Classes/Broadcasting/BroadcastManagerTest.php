<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Broadcasting;

use Clover\Classes\Broadcasting\BroadcastManager;
use Clover\Classes\Broadcasting\BroadcastMessage;
use Clover\Classes\Broadcasting\Broadcasters\NullBroadcaster;
use Clover\Classes\Broadcasting\Channel;
use Clover\Implement\BroadcasterInterface;
use Clover\Classes\Broadcasting\PrivateChannel;
use Clover\Classes\Broadcasting\PresenceChannel;
use Clover\Exception\Broadcasting\InvalidChannelNameException;
use Clover\Exception\Broadcasting\UnknownBroadcasterException;
use Clover\Implement\ShouldBroadcastInterface;
use PHPUnit\Framework\TestCase;

final class BroadcastManagerTest extends TestCase
{
	public function testRegisterAndBroadcastReachesDefaultDriver(): void
	{
		$manager = new BroadcastManager();
		$driver = new NullBroadcaster();
		$manager->registerDriver($driver);

		$manager->broadcast(BroadcastMessage::create(
			channels: [new Channel('orders')],
			eventName: 'order.created',
			payload: ['id' => 1],
		));

		$this->assertSame(1, $driver->getDispatchCount());
		$lastMessages = $driver->getLastMessages();
		$this->assertSame('order.created', $lastMessages[0]->eventName);
		$this->assertSame(['orders'], $lastMessages[0]->getTransportChannelNames());
	}

	public function testChannelTierPrefixesAreApplied(): void
	{
		$public = new Channel('room.1');
		$private = new PrivateChannel('chat.1');
		$presence = new PresenceChannel('chat.1');

		$this->assertSame('room.1', $public->getTransportName());
		$this->assertSame('public', $public->getTier());
		$this->assertSame('private-chat.1', $private->getTransportName());
		$this->assertSame('private', $private->getTier());
		$this->assertSame('presence-chat.1', $presence->getTransportName());
		$this->assertSame('presence', $presence->getTier());
	}

	public function testInvalidChannelNameThrows(): void
	{
		$this->expectException(InvalidChannelNameException::class);
		new Channel('invalid name with spaces');
	}

	public function testPendingBroadcastBuilderFlow(): void
	{
		$manager = new BroadcastManager();
		$driver = new NullBroadcaster();
		$manager->registerDriver($driver);

		$event = new class implements ShouldBroadcastInterface {
			public function broadcastOn(): array
			{
				return [new Channel('notifications')];
			}

			public function broadcastAs(): string
			{
				return 'user.notified';
			}

			public function broadcastPayload(): array
			{
				return ['to' => 'alice'];
			}
		};

		$manager->event($event)
			->toOthers(new PrivateChannel('user.alice'))
			->withHeader('X-Correlation-Id', 'abc123')
			->dispatch();

		$lastMessages = $driver->getLastMessages();
		$this->assertCount(1, $lastMessages);

		$message = $lastMessages[0];
		$this->assertSame('user.notified', $message->eventName);
		$this->assertSame(['notifications', 'private-user.alice'], $message->getTransportChannelNames());
		$this->assertSame(['to' => 'alice'], $message->payload);
		$this->assertSame('abc123', $message->headers['X-Correlation-Id']);
	}

	public function testUnknownDriverThrowsTypedException(): void
	{
		$manager = new BroadcastManager();
		$this->expectException(UnknownBroadcasterException::class);
		$manager->driver('missing');
	}

	public function testSwitchingDefaultDriverRoutesBroadcast(): void
	{
		$manager = new BroadcastManager();

		$first = new NullBroadcaster();
		$second = new class implements BroadcasterInterface {
			public int $count = 0;

			public function broadcast(BroadcastMessage $message): void
			{
				$this->count++;
			}

			public function getName(): string
			{
				return 'secondary';
			}
		};

		$manager->registerDriver($first);
		$manager->registerDriver($second);
		$manager->setDefaultDriver('secondary');

		$manager->broadcast(BroadcastMessage::create(
			channels: [new Channel('room')],
			eventName: 'x',
			payload: [],
		));

		$this->assertSame(0, $first->getDispatchCount());
		$this->assertSame(1, $second->count);
	}
}
