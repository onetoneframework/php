<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Debug;

use Clover\Framework\Event\KernelSpanFinished;
use Clover\Framework\Event\KernelSpanStarted;
use Clover\Framework\Event\ProfilerTimelineResetRequested;

final class ProfilerEventDispatcher
{
	public function dispatch(object $event): void
	{
		if (!Profiler::isEnabled()) {
			return;
		}
		if ($event instanceof KernelSpanStarted) {
			Profiler::startSpanToken($event->token, $event->call, $event->location);
			return;
		}
		if ($event instanceof KernelSpanFinished) {
			Profiler::finishSpanToken($event->token);
			return;
		}
		if ($event instanceof ProfilerTimelineResetRequested) {
			Profiler::resetTimeline();
		}
	}
}
