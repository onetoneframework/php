<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Exception\Broadcasting;

use RuntimeException;

/**
 * Raised when a broadcaster driver is requested by name but was never
 * registered with the {@see \Clover\Classes\Broadcasting\BroadcastManager}.
 */
final class UnknownBroadcasterException extends RuntimeException
{
	public function __construct(string $driverName)
	{
		parent::__construct('Unknown broadcaster driver: "' . $driverName . '".');
	}
}
