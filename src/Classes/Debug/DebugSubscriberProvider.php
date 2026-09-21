<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Debug;

use Clover\Classes\Event\EventManager;
use Clover\Classes\Event\Dispatcher;
use Clover\Framework\Event\KernelLifecycleEvent;
use Clover\Framework\Event\KernelSpanStarted;
use function is_array;
use function is_object;

/**
 * Central provider that registers debug-related subscribers once.
 */
final class DebugSubscriberProvider
{
	public static function register(bool $enableLifecycleLogger = false, ?Dispatcher $dispatcher = null): void
	{
		$dispatcher ??= EventManager::getInstance();

		if (Profiler::isEnabled()) {
			self::registerSubscriberOnce($dispatcher, ProfilerEventSubscriber::class, KernelSpanStarted::class);
		}

		if ($enableLifecycleLogger) {
			self::registerSubscriberOnce($dispatcher, KernelLifecycleLoggerListener::class, KernelLifecycleEvent::class);
		}
	}

	/**
	 * @param class-string<AbstractSubscriber> $subscriberClass
	 */
	private static function registerSubscriberOnce(
		Dispatcher $dispatcher,
		string $subscriberClass,
		string $probeEvent
	): void
	{
		foreach ($dispatcher->getListeners($probeEvent) as $listener) {
			if (
				is_array($listener)
				&& isset($listener[0])
				&& is_object($listener[0])
				&& $listener[0] instanceof $subscriberClass
			) {
				return;
			}
		}

		$dispatcher->addSubscriber(new $subscriberClass());
	}
}
