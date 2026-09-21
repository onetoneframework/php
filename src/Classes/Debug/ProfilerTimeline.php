<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Debug;

use function count;
use function memory_get_peak_usage;
use function memory_get_usage;
use function microtime;

final class ProfilerTimeline
{
	/** @var array<string, array{call:string,location:string,startedAt:float,startedMemory:int}> */
	private array $activeSpans = [];

	/** @var array<int, array{step:int,call:string,location:string,durationMs:float,memoryUsageBytes:int,memoryPeakBytes:int,memoryDeltaBytes:int}> */
	private array $timeline = [];

	public function reset(): void
	{
		$this->activeSpans = [];
		$this->timeline = [];
	}

	public function startSpanToken(string $token, string $call, string $location = ''): void
	{
		if ($token === '') {
			return;
		}

		$this->activeSpans[$token] = [
			'call' => $call,
			'location' => $location,
			'startedAt' => microtime(true),
			'startedMemory' => memory_get_usage(true),
		];
	}

	public function finishSpanToken(string $token): void
	{
		if (!isset($this->activeSpans[$token])) {
			return;
		}

		$span = $this->activeSpans[$token];
		unset($this->activeSpans[$token]);
		$memoryUsage = memory_get_usage(true);
		$memoryPeak = memory_get_peak_usage(true);
		$memoryDelta = $memoryUsage - (int) ($span['startedMemory'] ?? 0);

		$this->timeline[] = [
			'step' => count($this->timeline),
			'call' => $span['call'],
			'location' => $span['location'],
			'durationMs' => round((microtime(true) - $span['startedAt']) * 1000, 3),
			'memoryUsageBytes' => $memoryUsage,
			'memoryPeakBytes' => $memoryPeak,
			'memoryDeltaBytes' => $memoryDelta,
		];
	}

	/**
	 * @return array<int, array{step:int,call:string,location:string,durationMs:float,memoryUsageBytes:int,memoryPeakBytes:int,memoryDeltaBytes:int}>
	 */
	public function getTimelineFlow(): array
	{
		return $this->timeline;
	}
}
