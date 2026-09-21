<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Broadcasting;

use Clover\Classes\Broadcasting\BroadcastMessage;
use Clover\Classes\Broadcasting\Broadcasters\NullBroadcaster;
use Clover\Classes\Broadcasting\Channel;
use PHPUnit\Framework\TestCase;

final class NullBroadcasterTest extends TestCase
{
	public function testFreshDriverReportsItsStableNameAndEmptyHistory(): void
	{
		$driver = new NullBroadcaster();

		$this->assertSame('null', $driver->getName());
		$this->assertSame(0, $driver->getDispatchCount());
		$this->assertSame([], $driver->getLastMessages());
	}

	public function testBroadcastCountsAndRetainsMessagesInDispatchOrder(): void
	{
		$driver = new NullBroadcaster();
		$first = BroadcastMessage::create([new Channel('one')], 'first', ['id' => 1]);
		$second = BroadcastMessage::create([new Channel('two')], 'second', ['id' => 2]);

		$driver->broadcast($first);
		$driver->broadcast($second);

		$this->assertSame(2, $driver->getDispatchCount());
		$this->assertSame([$first, $second], $driver->getLastMessages());
	}

	public function testReturnedHistoryArrayDoesNotExposeInternalArrayForMutation(): void
	{
		$driver = new NullBroadcaster();
		$message = BroadcastMessage::create([], 'event', []);
		$driver->broadcast($message);

		$history = $driver->getLastMessages();
		$history[] = BroadcastMessage::create([], 'other', []);

		$this->assertSame(1, $driver->getDispatchCount());
		$this->assertSame([$message], $driver->getLastMessages());
	}
}
