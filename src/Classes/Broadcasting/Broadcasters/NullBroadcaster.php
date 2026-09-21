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
use Clover\Implement\BroadcasterInterface;

/**
 * No-op driver used in tests and environments where broadcasting is
 * disabled. Counts messages so tests can assert dispatch happened
 * without wiring a full transport.
 */
final class NullBroadcaster implements BroadcasterInterface
{
	private int $dispatchCount = 0;
	/** @var array<int, BroadcastMessage> */
	private array $lastMessages = [];

	public function broadcast(BroadcastMessage $message): void
	{
		$this->dispatchCount++;
		$this->lastMessages[] = $message;
	}

	public function getName(): string
	{
		return 'null';
	}

	public function getDispatchCount(): int
	{
		return $this->dispatchCount;
	}

	/**
	 * @return array<int, BroadcastMessage>
	 */
	public function getLastMessages(): array
	{
		return $this->lastMessages;
	}
}
