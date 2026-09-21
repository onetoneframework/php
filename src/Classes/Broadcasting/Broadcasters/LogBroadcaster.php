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
use Clover\Classes\Logging\Logger;
use Clover\Enumeration\LoggingLevel;
use Clover\Implement\BroadcasterInterface;
use function json_encode;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

/**
 * Driver that writes broadcasts to the internal {@see Logger}.
 * Useful for local development and for integration tests that care
 * about the wire payload but not the transport.
 */
final class LogBroadcaster implements BroadcasterInterface
{
	public function __construct(private readonly Logger $logger)
	{
	}

	public function broadcast(BroadcastMessage $message): void
	{
		$line = json_encode([
			'messageId' => $message->messageId,
			'event' => $message->eventName,
			'channels' => $message->getTransportChannelNames(),
			'payload' => $message->payload,
			'headers' => $message->headers,
			'occurredAt' => $message->occurredAt->format(DATE_ATOM),
		], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

		if ($line === false) {
			$line = '{"error":"broadcast_payload_not_json_encodable"}';
		}

		$this->logger->write($line, LoggingLevel::INFORMATION, 'broadcast');
	}

	public function getName(): string
	{
		return 'log';
	}
}
