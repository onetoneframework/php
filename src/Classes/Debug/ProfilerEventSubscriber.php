<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Debug;

use Clover\Classes\Event\AbstractSubscriber;
use Clover\Framework\Event\KernelSpanFinished;
use Clover\Framework\Event\KernelSpanStarted;
use Clover\Framework\Event\ProfilerTimelineResetRequested;

/**
 * Symfony-style subscriber that translates framework events into profiler state changes.
 */
final class ProfilerEventSubscriber extends AbstractSubscriber
{
	private ProfilerEventDispatcher $profilerEventDispatcher;

	public function __construct(?ProfilerEventDispatcher $profilerEventDispatcher = null)
	{
		$this->profilerEventDispatcher = $profilerEventDispatcher ?? new ProfilerEventDispatcher();
	}

	public static function getSubscribedEvents(): array
	{
		return [
			KernelSpanStarted::class => 'onKernelSpanStarted',
			KernelSpanFinished::class => 'onKernelSpanFinished',
			ProfilerTimelineResetRequested::class => 'onTimelineResetRequested',
		];
	}

	public function onKernelSpanStarted(object $event, ?string $eventName = null, mixed $dispatcher = null): void
	{
		$this->profilerEventDispatcher->dispatch($event);
	}

	public function onKernelSpanFinished(object $event, ?string $eventName = null, mixed $dispatcher = null): void
	{
		$this->profilerEventDispatcher->dispatch($event);
	}

	public function onTimelineResetRequested(object $event, ?string $eventName = null, mixed $dispatcher = null): void
	{
		$this->profilerEventDispatcher->dispatch($event);
	}
}
