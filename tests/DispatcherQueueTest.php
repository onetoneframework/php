<?php

declare(strict_types=1);

namespace Clover\Tests;

use Clover\Classes\Event\Dispatcher;
use Clover\Classes\Event\EventEnvelope;
use Clover\Classes\Event\EventBus;
use Clover\Classes\Event\Middleware\CorrelationMiddleware;
use Clover\Implement\EventBusInterface;
use PHPUnit\Framework\TestCase;

final class DispatcherQueueTest extends TestCase
{
	public function testListenerFailureIsIsolated(): void
	{
		$dispatcher = new Dispatcher();
		$eventName = \stdClass::class;
		$hit = 0;

		$dispatcher->addListener($eventName, static function (): void {
			throw new \RuntimeException('listener failed');
		});
		$dispatcher->addListener($eventName, static function () use (&$hit): void {
			$hit++;
		});

		$dispatcher->dispatch(new \stdClass());

		$this->assertSame(1, $hit);
		$this->assertCount(1, $dispatcher->getListenerFailures());
	}

	public function testQueueRetryAndDeadLetter(): void
	{
		$dispatcher = new Dispatcher();
		$dispatcher->setMaxRetryAttempts(1);
		$eventName = \stdClass::class;

		$dispatcher->addListener($eventName, static function (): void {
			throw new \RuntimeException('always fails');
		});

		$dispatcher->queue(new \stdClass());
		$dispatcher->processQueue();

		$deadLetters = $dispatcher->getDeadLetterQueue();
		$this->assertCount(1, $deadLetters);
		$this->assertSame($eventName, $deadLetters[0]['eventName']);
		$this->assertSame(2, $deadLetters[0]['attempts']);
	}

	public function testQueuePersistenceAndDeadLetterLogging(): void
	{
		$queueFile = sys_get_temp_dir() . '/onetone_dispatcher_queue_test.json';
		$dlqFile = sys_get_temp_dir() . '/onetone_dispatcher_dlq_test.jsonl';
		@unlink($queueFile);
		@unlink($dlqFile);

		$writer = new Dispatcher();
		$writer->setQueueStoragePath($queueFile);
		$writer->setDeadLetterLogPath($dlqFile);
		$writer->setMaxRetryAttempts(0);
		$writer->addListener(\stdClass::class, static function (): void {
			throw new \RuntimeException('persistent failure');
		});
		$writer->queue(new \stdClass());
		$this->assertFileExists($queueFile);

		$reader = new Dispatcher();
		$reader->setQueueStoragePath($queueFile);
		$reader->setDeadLetterLogPath($dlqFile);
		$reader->setMaxRetryAttempts(0);
		$reader->addListener(\stdClass::class, static function (): void {
			throw new \RuntimeException('persistent failure');
		});
		$this->assertSame(1, $reader->getQueueCount());
		$reader->processQueue();

		$this->assertCount(1, $reader->getDeadLetterQueue());
		$this->assertFileExists($dlqFile);

		$log = file_get_contents($dlqFile);
		$this->assertNotFalse($log);
		$this->assertStringContainsString('"eventName":"stdClass"', (string) $log);

		@unlink($queueFile);
		@unlink($dlqFile);
	}

	public function testEventBusPublishAndAsyncFlow(): void
	{
		$dispatcher = new Dispatcher();
		$bus = new EventBus($dispatcher);
		$hits = 0;

		$bus->subscribe(\stdClass::class, static function () use (&$hits): void {
			$hits++;
		});

		$bus->publish(new \stdClass());
		$bus->publishAsync(new \stdClass());
		$bus->processAsync();

		$this->assertSame(2, $hits);
	}

	public function testEventBusEnvelopeChannelAndCorrelationMiddleware(): void
	{
		$dispatcher = new Dispatcher();
		$bus = new EventBus($dispatcher);
		$bus->addMiddleware(new CorrelationMiddleware());
		$capturedEnvelope = null;

		$dispatcher->addListener(EventBusInterface::ENVELOPE_EVENT_NAME, static function (EventEnvelope $envelope) use (&$capturedEnvelope): void {
			$capturedEnvelope = $envelope;
		});

		$bus->publish(new \stdClass());

		$this->assertInstanceOf(EventEnvelope::class, $capturedEnvelope);
		$this->assertNotEmpty($capturedEnvelope->eventId);
		$this->assertArrayHasKey('correlation_id', $capturedEnvelope->headers);
	}
}
