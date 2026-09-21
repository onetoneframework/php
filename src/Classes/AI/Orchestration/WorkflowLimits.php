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
 * Defines total time and normalized output budgets for one workflow run.
 */
final class WorkflowLimits
{
	public const DEFAULT_TIMEOUT_MILLISECONDS = 900_000;

	public const DEFAULT_MAXIMUM_RESPONSE_BYTES = 52_428_800;

	public const MAXIMUM_TIMEOUT_MILLISECONDS = 86_400_000;

	public const MAXIMUM_RESPONSE_BYTES = 1_073_741_824;

	private const MINIMUM_LIMIT = 1;

	private int $timeoutMilliseconds;

	private int $maximumResponseBytes;

	public function __construct(
		int $timeoutMilliseconds = self::DEFAULT_TIMEOUT_MILLISECONDS,
		int $maximumResponseBytes = self::DEFAULT_MAXIMUM_RESPONSE_BYTES
	) {
		if ($timeoutMilliseconds < self::MINIMUM_LIMIT || $timeoutMilliseconds > self::MAXIMUM_TIMEOUT_MILLISECONDS) {
			throw new InvalidArgumentException('The AI workflow timeout is outside the supported range.');
		}

		if ($maximumResponseBytes < self::MINIMUM_LIMIT || $maximumResponseBytes > self::MAXIMUM_RESPONSE_BYTES) {
			throw new InvalidArgumentException('The AI workflow response budget is outside the supported range.');
		}

		$this->timeoutMilliseconds = $timeoutMilliseconds;
		$this->maximumResponseBytes = $maximumResponseBytes;
	}

	public function timeoutMilliseconds(): int
	{
		return $this->timeoutMilliseconds;
	}

	public function maximumResponseBytes(): int
	{
		return $this->maximumResponseBytes;
	}
}
