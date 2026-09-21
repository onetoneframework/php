<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Broadcasting;

use Clover\Exception\Broadcasting\InvalidChannelNameException;
use function preg_match;

/**
 * Public broadcast channel.
 *
 * Acts as a value object: immutable, equality by name. Sub-types
 * (PrivateChannel, PresenceChannel) add an authorization prefix so
 * transports and authorizers can tell the tiers apart without
 * branching on class strings.
 */
class Channel
{
	/**
	 * Channel names must be URL-safe, transport-neutral identifiers.
	 * The pattern here is intentionally narrow to avoid ambiguity
	 * with transport-specific separators (Pusher, MQTT, NATS, ...).
	 */
	private const NAME_PATTERN = '/^[A-Za-z0-9_\-\.\:]+$/';

	/**
	 * The constructor is public to allow direct construction, but the {@see create()} factory is the recommended way to create channels, as it provides a clearer API and allows for future enhancements without breaking existing code.
	 * 
	 * @param string $name The raw channel name without any tier prefix
	 * @throws InvalidChannelNameException if the channel name does not match the required pattern
	 */
	public function __construct(private readonly string $name)
	{
		if (preg_match(self::NAME_PATTERN, $name) !== 1) {
			throw new InvalidChannelNameException($name);
		}
	}

	/**
	 * Raw channel name without any tier prefix. Sub-types override
	 * {@see getTransportName()} to add their prefix.
	 */
	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * Name emitted to the underlying transport (carries the tier
	 * prefix on private/presence channels).
	 */
	public function getTransportName(): string
	{
		return $this->name;
	}

	/**
	 * Tier identifier used by authorizers and loggers. Sub-types
	 * override this, keeping the dispatch logic free of class checks.
	 */
	public function getTier(): string
	{
		return 'public';
	}

	/**
	 * String representation of the channel, which is its transport name.
	 *
	 * @return string The transport name of the channel
	 */
	public function __toString(): string
	{
		return $this->getTransportName();
	}
}
