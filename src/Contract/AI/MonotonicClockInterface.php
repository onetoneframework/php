<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Contract\AI;

/**
 * Provides elapsed-time measurements that are not affected by wall-clock changes.
 */
interface MonotonicClockInterface
{
	public function nowMilliseconds(): float;
}
