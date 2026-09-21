<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Debug;

use Clover\Classes\Debug\DebugSubscriberProvider;
use Clover\Classes\Event\Dispatcher;
use Clover\Framework\Event\KernelLifecycleEvent;
use Clover\Framework\Event\KernelSpanFinished;
use Clover\Framework\Event\KernelSpanStarted;
use Clover\Framework\Event\ProfilerTimelineResetRequested;
use PHPUnit\Framework\TestCase;

final class DebugSubscriberProviderTest extends TestCase
{
	protected function tearDown(): void
	{
		unset($_ENV['PROFILER_ENABLED']);
	}

	public function testDoesNotRegisterProfilerSubscriberWhenProfilerIsDisabled(): void
	{
		$_ENV['PROFILER_ENABLED'] = 'false';
		$dispatcher = new Dispatcher();

		DebugSubscriberProvider::register(false, $dispatcher);

		self::assertSame([], $dispatcher->getListeners(KernelSpanStarted::class));
		self::assertSame([], $dispatcher->getListeners(KernelSpanFinished::class));
		self::assertSame([], $dispatcher->getListeners(ProfilerTimelineResetRequested::class));
	}

	public function testRegistersProfilerSubscriberOncePerDispatcher(): void
	{
		$_ENV['PROFILER_ENABLED'] = 'true';
		$dispatcher = new Dispatcher();

		DebugSubscriberProvider::register(false, $dispatcher);
		DebugSubscriberProvider::register(false, $dispatcher);

		self::assertCount(1, $dispatcher->getListeners(KernelSpanStarted::class));
		self::assertCount(1, $dispatcher->getListeners(KernelSpanFinished::class));
		self::assertCount(1, $dispatcher->getListeners(ProfilerTimelineResetRequested::class));
	}

	public function testRegistersProfilerSubscriberOnEachIndependentDispatcher(): void
	{
		$_ENV['PROFILER_ENABLED'] = 'true';
		$firstDispatcher = new Dispatcher();
		$secondDispatcher = new Dispatcher();

		DebugSubscriberProvider::register(false, $firstDispatcher);
		DebugSubscriberProvider::register(false, $secondDispatcher);

		self::assertCount(1, $firstDispatcher->getListeners(KernelSpanStarted::class));
		self::assertCount(1, $secondDispatcher->getListeners(KernelSpanStarted::class));
	}

	public function testRegistersLifecycleLoggerOnceWithoutProfiler(): void
	{
		$_ENV['PROFILER_ENABLED'] = 'false';
		$dispatcher = new Dispatcher();

		DebugSubscriberProvider::register(true, $dispatcher);
		DebugSubscriberProvider::register(true, $dispatcher);

		self::assertCount(1, $dispatcher->getListeners(KernelLifecycleEvent::class));
		self::assertSame([], $dispatcher->getListeners(KernelSpanStarted::class));
	}
}
