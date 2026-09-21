<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Tests\Classes\AI\Orchestration;

use Clover\Contract\AI\MonotonicClockInterface;

/**
 * Deterministic monotonic clock for workflow deadline tests.
 */
final class FakeMonotonicClock implements MonotonicClockInterface
{
	private float $currentMilliseconds;

	public function __construct(float $currentMilliseconds = 0.0)
	{
		$this->currentMilliseconds = $currentMilliseconds;
	}

	public function nowMilliseconds(): float
	{
		return $this->currentMilliseconds;
	}

	public function advanceMilliseconds(float $milliseconds): void
	{
		$this->currentMilliseconds += $milliseconds;
	}
}
