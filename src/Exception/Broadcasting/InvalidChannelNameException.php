<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Exception\Broadcasting;

use InvalidArgumentException;

/**
 * Raised when a channel name does not satisfy the transport-neutral
 * naming rule enforced by {@see \Clover\Classes\Broadcasting\Channel}.
 */
final class InvalidChannelNameException extends InvalidArgumentException
{
	public function __construct(string $givenName)
	{
		parent::__construct(
			'Invalid broadcast channel name: "' . $givenName . '". '
			. 'Channel names must match [A-Za-z0-9_\-\.\:].'
		);
	}
}
