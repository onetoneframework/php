<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\AI\Orchestration;

use InvalidArgumentException;

/**
 * Defines bounded transport settings for one agent invocation.
 */
final class TransportLimits
{
	public const DEFAULT_CONNECTION_TIMEOUT_MILLISECONDS = 5_000;

	public const DEFAULT_RESPONSE_TIMEOUT_MILLISECONDS = 120_000;

	public const DEFAULT_MAXIMUM_RESPONSE_BYTES = 10_485_760;

	public const MAXIMUM_TIMEOUT_MILLISECONDS = 3_600_000;

	public const MAXIMUM_RESPONSE_BYTES = 104_857_600;

	private const MINIMUM_LIMIT = 1;

	private int $connectionTimeoutMilliseconds;

	private int $responseTimeoutMilliseconds;

	private int $maximumResponseBytes;

	public function __construct(
		int $connectionTimeoutMilliseconds = self::DEFAULT_CONNECTION_TIMEOUT_MILLISECONDS,
		int $responseTimeoutMilliseconds = self::DEFAULT_RESPONSE_TIMEOUT_MILLISECONDS,
		int $maximumResponseBytes = self::DEFAULT_MAXIMUM_RESPONSE_BYTES
	) {
		if (
			$connectionTimeoutMilliseconds < self::MINIMUM_LIMIT
			|| $connectionTimeoutMilliseconds > self::MAXIMUM_TIMEOUT_MILLISECONDS
		) {
			throw new InvalidArgumentException('The AI connection timeout is outside the supported range.');
		}

		if (
			$responseTimeoutMilliseconds < $connectionTimeoutMilliseconds
			|| $responseTimeoutMilliseconds > self::MAXIMUM_TIMEOUT_MILLISECONDS
		) {
			throw new InvalidArgumentException('The AI response timeout must include the connection timeout and remain within the supported range.');
		}

		if ($maximumResponseBytes < self::MINIMUM_LIMIT || $maximumResponseBytes > self::MAXIMUM_RESPONSE_BYTES) {
			throw new InvalidArgumentException('The AI response size limit is outside the supported range.');
		}

		$this->connectionTimeoutMilliseconds = $connectionTimeoutMilliseconds;
		$this->responseTimeoutMilliseconds = $responseTimeoutMilliseconds;
		$this->maximumResponseBytes = $maximumResponseBytes;
	}

	public function connectionTimeoutMilliseconds(): int
	{
		return $this->connectionTimeoutMilliseconds;
	}

	public function responseTimeoutMilliseconds(): int
	{
		return $this->responseTimeoutMilliseconds;
	}

	public function maximumResponseBytes(): int
	{
		return $this->maximumResponseBytes;
	}
}
