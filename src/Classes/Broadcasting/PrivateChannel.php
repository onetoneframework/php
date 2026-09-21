<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Broadcasting;

/**
 * Private channel — delivery requires the subscriber to pass an
 * authorization step on the server before the transport forwards
 * events to them.
 */
final class PrivateChannel extends Channel
{
	/** The prefix for private channels, used in transport naming. */
	public const PREFIX = 'private-';

	/**
	 * Get the transport name of the channel, which is prefixed with 'private-'.
	 *
	 * @return string The transport name of the channel
	 */
	public function getTransportName(): string
	{
		return self::PREFIX . $this->getName();
	}

	/**
	 * Get the tier of the channel, which is 'private' for this class.
	 *
	 * @return string The tier of the channel
	 */
	public function getTier(): string
	{
		return 'private';
	}
}
