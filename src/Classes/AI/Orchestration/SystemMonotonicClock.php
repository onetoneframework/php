<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\AI\Orchestration;

use Clover\Contract\AI\MonotonicClockInterface;

/**
 * Reads monotonic process time for production workflow deadlines.
 */
final class SystemMonotonicClock implements MonotonicClockInterface
{
	private const SECONDS_INDEX = 0;

	private const NANOSECONDS_INDEX = 1;

	private const MILLISECONDS_PER_SECOND = 1_000.0;

	private const NANOSECONDS_PER_MILLISECOND = 1_000_000.0;

	public function nowMilliseconds(): float
	{
		$time = hrtime();

		return ($time[self::SECONDS_INDEX] * self::MILLISECONDS_PER_SECOND)
			+ ($time[self::NANOSECONDS_INDEX] / self::NANOSECONDS_PER_MILLISECOND);
	}
}
