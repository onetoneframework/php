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
 * Presence channel — a private channel that additionally tracks the
 * roster of subscribed members. The transport is expected to gossip
 * join/leave events and expose the member list through its own API.
 */
final class PresenceChannel extends Channel
{
	/** The prefix for presence channels, used in transport naming. */
	public const PREFIX = 'presence-';

	/**
	 * Get the transport name of the channel, which is prefixed with 'presence-'.
	 *
	 * @return string The transport name of the channel
	 */
	public function getTransportName(): string
	{
		return self::PREFIX . $this->getName();
	}

	/**
	 * Get the tier of the channel, which is 'presence' for this class.
	 *
	 * @return string The tier of the channel
	 */
	public function getTier(): string
	{
		return 'presence';
	}
}
