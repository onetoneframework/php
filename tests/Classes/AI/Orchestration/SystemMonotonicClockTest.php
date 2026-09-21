<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Tests\Classes\AI\Orchestration;

use Clover\Classes\AI\Orchestration\SystemMonotonicClock;
use PHPUnit\Framework\TestCase;

final class SystemMonotonicClockTest extends TestCase
{
	public function testClockReturnsNonDecreasingFloatWithoutWaiting(): void
	{
		$clock = new SystemMonotonicClock();

		$firstReading = $clock->nowMilliseconds();
		$secondReading = $clock->nowMilliseconds();

		self::assertIsFloat($firstReading);
		self::assertIsFloat($secondReading);
		self::assertGreaterThanOrEqual(0.0, $firstReading);
		self::assertGreaterThanOrEqual($firstReading, $secondReading);
	}
}
