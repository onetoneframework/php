<?php

declare(strict_types=1);

namespace Clover\Tests;

use Clover\Classes\Data\StringObject;
use Clover\Classes\TraceableClientURL;
use Clover\Framework\Event\HttpClientLifecycleEvent;
use Clover\Framework\Event\KernelSpanFinished;
use Clover\Framework\Event\KernelSpanStarted;
use Clover\Implement\ClientURLInterface;
use Exception;
use PHPUnit\Framework\TestCase;

final class TraceableClientURLTest extends TestCase
{
	protected function setUp(): void
	{
		$_ENV['PROFILER_ENABLED'] = 'true';
	}

	protected function tearDown(): void
	{
		unset($_ENV['PROFILER_ENABLED']);
	}

	public function testExecuteDispatchesStartSpanAndFinishEvents(): void
	{
		$events = [];
		$client = new TraceableClientURL(
			new TraceableClientURLTestDouble('ok', 200),
			function (object $event) use (&$events): void {
				$events[] = $event;
			}
		);

		$result = $client->execute();

		self::assertSame('ok', $result);
		self::assertCount(4, $events);
		self::assertInstanceOf(HttpClientLifecycleEvent::class, $events[0]);
		self::assertInstanceOf(KernelSpanStarted::class, $events[1]);
		self::assertInstanceOf(KernelSpanFinished::class, $events[2]);
		self::assertInstanceOf(HttpClientLifecycleEvent::class, $events[3]);
	}

	public function testExecuteDispatchesFailedEventWhenInnerClientThrows(): void
	{
		$events = [];
		$client = new TraceableClientURL(
			new TraceableClientURLTestDouble('', 0, true),
			function (object $event) use (&$events): void {
				$events[] = $event;
			}
		);

		$this->expectException(Exception::class);

		try {
			$client->execute();
		} finally {
			self::assertCount(4, $events);
			self::assertInstanceOf(HttpClientLifecycleEvent::class, $events[0]);
			self::assertInstanceOf(KernelSpanStarted::class, $events[1]);
			self::assertInstanceOf(KernelSpanFinished::class, $events[2]);
			self::assertInstanceOf(HttpClientLifecycleEvent::class, $events[3]);
		}
	}

	public function testExecuteDoesNotDispatchProfilerSpansWhenProfilerIsDisabled(): void
	{
		$_ENV['PROFILER_ENABLED'] = 'false';
		$events = [];
		$client = new TraceableClientURL(
			new TraceableClientURLTestDouble('ok', 200),
			static function (object $event) use (&$events): void {
				$events[] = $event;
			}
		);

		$result = $client->execute();

		self::assertSame('ok', $result);
		self::assertCount(2, $events);
		self::assertInstanceOf(HttpClientLifecycleEvent::class, $events[0]);
		self::assertInstanceOf(HttpClientLifecycleEvent::class, $events[1]);
	}
}

final class TraceableClientURLTestDouble implements ClientURLInterface
{
	private string $responseBody;
	private int $statusCode;
	private bool $shouldThrow;
	private TraceableClientURLTestOption $option;
	private TraceableClientURLTestInformation $information;

	public function __construct(string $responseBody, int $statusCode, bool $shouldThrow = false)
	{
		$this->responseBody = $responseBody;
		$this->statusCode = $statusCode;
		$this->shouldThrow = $shouldThrow;
		$this->option = new TraceableClientURLTestOption();
		$this->information = new TraceableClientURLTestInformation($statusCode);
	}

	public function close()
	{
		return null;
	}

	public function execute(): mixed
	{
		if ($this->shouldThrow) {
			throw new Exception('network failed');
		}

		return $this->responseBody;
	}

	public function getLastErrorMessage(): string
	{
		return $this->shouldThrow ? 'network failed' : '';
	}

	public function getLastErrorNumber(): int
	{
		return $this->shouldThrow ? 7 : 0;
	}

	public function getSession()
	{
		return null;
	}

	public function information()
	{
		return $this->information;
	}

	public function initialize(string|StringObject|null $instance = null): mixed
	{
		return null;
	}

	public function option()
	{
		return $this->option;
	}

	public function reset()
	{
		return null;
	}

	public function setOption(int $option, $value)
	{
		return true;
	}
}

final class TraceableClientURLTestOption
{
	public static array $curlOptions = [
		CURLOPT_URL => 'https://api.example.com/users',
		CURLOPT_CUSTOMREQUEST => 'GET',
	];
}

final class TraceableClientURLTestInformation
{
	private int $statusCode;

	public function __construct(int $statusCode)
	{
		$this->statusCode = $statusCode;
	}

	public function getEffectiveURL(): string
	{
		return 'https://api.example.com/users';
	}

	public function getStatusCode(): int
	{
		return $this->statusCode;
	}
}
