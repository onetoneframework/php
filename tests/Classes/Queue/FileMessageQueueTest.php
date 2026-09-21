<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Queue;

use Clover\Classes\Queue\FileMessageQueue;
use PHPUnit\Framework\TestCase;

final class FileMessageQueueTest extends TestCase
{
	private string $queueDirectory = '';

	protected function setUp(): void
	{
		$this->queueDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'clover-queue-' . uniqid('', true);
	}

	protected function tearDown(): void
	{
		if (!is_dir($this->queueDirectory)) {
			return;
		}

		$files = glob($this->queueDirectory . DIRECTORY_SEPARATOR . '*');
		if (is_array($files)) {
			foreach ($files as $file) {
				if (is_file($file)) {
					unlink($file);
				}
			}
		}

		rmdir($this->queueDirectory);
	}

	public function testPushPopAndSizeFollowQueueOrder(): void
	{
		$queue = new FileMessageQueue($this->queueDirectory);

		$this->assertTrue($queue->push('jobs', 'first'));
		$this->assertTrue($queue->push('jobs', 'second'));
		$this->assertSame(2, $queue->size('jobs'));
		$this->assertSame('first', $queue->pop('jobs'));
		$this->assertSame(1, $queue->size('jobs'));
		$this->assertSame('second', $queue->pop('jobs'));
		$this->assertNull($queue->pop('jobs'));
	}

	public function testQueuesAreIsolatedByNameAndClearOnlyTargetsOneQueue(): void
	{
		$queue = new FileMessageQueue($this->queueDirectory);
		$queue->push('email', 'one');
		$queue->push('email', 'two');
		$queue->push('notifications', 'three');

		$queue->clear('email');

		$this->assertSame(0, $queue->size('email'));
		$this->assertSame(1, $queue->size('notifications'));
		$this->assertSame('three', $queue->pop('notifications'));
	}
}
