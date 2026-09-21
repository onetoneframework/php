<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Broadcasting;

use Clover\Implement\BroadcastManagerInterface;
use Clover\Implement\ShouldBroadcastInterface;
use function array_merge;
use function array_values;

/**
 * Fluent builder returned by {@see BroadcastManagerInterface::event()}.
 *
 * The builder is the single place that knows how to stitch an event's
 * declared channels together with callsite-specific overrides before
 * asking the manager to ship the message. Keeping this logic here
 * means callers never assemble channels manually and the manager's
 * public surface stays small (AGENTS.md rule 29).
 */
final class PendingBroadcast
{
	/** 
	 * @var array<int, Channel> $extraChannels Channels added via toOthers() calls, to be merged with the event's declared channels at dispatch time
	 **/
	private array $extraChannels = [];
	/** 
	 * @var array<string, string> $headers Headers added via withHeader() calls, to be included in the broadcast message at dispatch time
	 **/
	private array $headers = [];
	/** The name of the driver to use for this broadcast, if specified via via() */
	private ?string $driverName = null;

	/**
	 * The constructor is public to allow direct construction, but the {@see BroadcastManagerInterface::event()} factory method is the recommended way to create pending broadcasts, as it provides a clearer API and allows for future enhancements without breaking existing code.
	 * 
	 * @param BroadcastManagerInterface $manager The broadcast manager to use for dispatching the message
	 * @param ShouldBroadcastInterface $event The event that should be broadcasted
	 */
	public function __construct(
		private readonly BroadcastManagerInterface $manager,
		private readonly ShouldBroadcastInterface $event,
	) {
	}

	/**
	 * Fluent builder methods. Each returns $this for chaining.
	 * 
	 * @param Channel $channel The channel to add to the broadcast
	 * @return self Returns the current instance for method chaining
	 */
	public function toOthers(Channel $channel): self
	{
		$this->extraChannels[] = $channel;

		return $this;
	}

	/**
	 * Fluent builder methods. Each returns $this for chaining.
	 * 
	 * @param string $driverName The name of the driver to use for broadcasting
	 * @return self Returns the current instance for method chaining
	 */
	public function via(string $driverName): self
	{
		$this->driverName = $driverName;

		return $this;
	}

	/**
	 * Fluent builder methods. Each returns $this for chaining.
	 * 
	 * @param string $name The name of the header to add to the broadcast message
	 * @param string $value The value of the header to add to the broadcast message
	 * @return self Returns the current instance for method chaining
	 */
	public function withHeader(string $name, string $value): self
	{
		$this->headers[$name] = $value;

		return $this;
	}

	/**
	 * Dispatch the broadcast message using the manager. This method is idempotent; calling it multiple times will not result in duplicate messages.
	 */
	public function dispatch(): void
	{
		$channels = array_values(array_merge(
			$this->event->broadcastOn(),
			$this->extraChannels,
		));

		$message = BroadcastMessage::create(
			channels: $channels,
			eventName: $this->event->broadcastAs(),
			payload: $this->event->broadcastPayload(),
			headers: $this->headers,
		);

		$this->manager->broadcast($message, $this->driverName);
	}
}
