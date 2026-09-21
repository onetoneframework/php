<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Broadcasting\Broadcasters;

use Clover\Classes\Broadcasting\BroadcastMessage;
use Clover\Classes\SSE\SseServer;
use Clover\Implement\BroadcasterInterface;
use function in_array;
use function json_encode;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

/**
 * Bridges a broadcast message onto the project's SSE transport.
 *
 * Channel subscription is advisory: each session carries a list of
 * channel names, and the broadcaster only forwards to sessions that
 * have joined at least one of the message's channels. When no
 * per-session roster has been registered the message is delivered
 * to every open session (fan-out), which matches the default SSE
 * "everyone on the endpoint" semantics.
 */
final class SseBroadcaster implements BroadcasterInterface
{
	/** @var array<string, array<int, string>> sessionId => list of transport channel names */
	private array $subscriptions = [];

	/**
	 * SseBroadcaster constructor.
	 *
	 * @param SseServer $server
	 */
	public function __construct(private readonly SseServer $server)
	{
	}

	/**
	 * Register (or replace) the channels a session is subscribed to.
	 *
	 * @param string $sessionId The ID of the session to subscribe
	 * @param array<int, string> $transportChannelNames The list of channel names the session is subscribing to
	 * @return void
	 */
	public function subscribe(string $sessionId, array $transportChannelNames): void
	{
		$this->subscriptions[$sessionId] = $transportChannelNames;
	}

	/**
	 * Unregister a session from the broadcaster.
	 *
	 * @param string $sessionId The ID of the session to unsubscribe
	 * @return void
	 */
	public function unsubscribe(string $sessionId): void
	{
		unset($this->subscriptions[$sessionId]);
	}

	/**
	 * Broadcast the given message.
	 *
	 * @param BroadcastMessage $message
	 * @return void
	 * @noinspection PhpDocMissingThrowsInspection
	 */
	public function broadcast(BroadcastMessage $message): void
	{
		$payload = json_encode([
			'messageId' => $message->messageId,
			'channels' => $message->getTransportChannelNames(),
			'payload' => $message->payload,
		], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

		if ($payload === false) {
			return;
		}

		$messageChannels = $message->getTransportChannelNames();
		foreach ($this->server->getSessionIds() as $sessionId) {
			if (!$this->shouldDeliver($sessionId, $messageChannels)) {
				continue;
			}
			$this->server->emitTo($sessionId, $message->eventName, $payload);
		}
	}

	/**
	 * Get the name of the broadcaster.
	 *
	 * @return string
	 */
	public function getName(): string
	{
		return 'sse';
	}

	/**
	 * Determine whether a message should be delivered to a session based on the session's subscribed channels.
	 * 
	 * @param string $sessionId The ID of the session to check
	 * @param array<int, string> $messageChannels The channels associated with the message
	 * @return bool True if the message should be delivered to the session, false otherwise
	 */
	private function shouldDeliver(string $sessionId, array $messageChannels): bool
	{
		if (!isset($this->subscriptions[$sessionId])) {
			return true;
		}
		foreach ($this->subscriptions[$sessionId] as $subscribed) {
			if (in_array($subscribed, $messageChannels, true)) {
				return true;
			}
		}

		return false;
	}
}
