<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Tests\Classes\AI\Orchestration;

use Clover\Classes\AI\Orchestration\WorkflowLimits;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class WorkflowLimitsTest extends TestCase
{
	private const CUSTOM_TIMEOUT_MILLISECONDS = 30_000;

	private const CUSTOM_MAXIMUM_RESPONSE_BYTES = 65_536;

	private const INVALID_MINIMUM_LIMIT = 0;

	public function testDefaultLimitsArePreserved(): void
	{
		$limits = new WorkflowLimits();

		self::assertSame(
			WorkflowLimits::DEFAULT_TIMEOUT_MILLISECONDS,
			$limits->timeoutMilliseconds()
		);
		self::assertSame(
			WorkflowLimits::DEFAULT_MAXIMUM_RESPONSE_BYTES,
			$limits->maximumResponseBytes()
		);
	}

	public function testCustomLimitsArePreserved(): void
	{
		$limits = new WorkflowLimits(
			self::CUSTOM_TIMEOUT_MILLISECONDS,
			self::CUSTOM_MAXIMUM_RESPONSE_BYTES
		);

		self::assertSame(self::CUSTOM_TIMEOUT_MILLISECONDS, $limits->timeoutMilliseconds());
		self::assertSame(self::CUSTOM_MAXIMUM_RESPONSE_BYTES, $limits->maximumResponseBytes());
	}

	public function testTimeoutBelowMinimumIsRejected(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('workflow timeout is outside the supported range');

		new WorkflowLimits(
			self::INVALID_MINIMUM_LIMIT,
			self::CUSTOM_MAXIMUM_RESPONSE_BYTES
		);
	}

	public function testTimeoutAboveMaximumIsRejected(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('workflow timeout is outside the supported range');

		new WorkflowLimits(
			WorkflowLimits::MAXIMUM_TIMEOUT_MILLISECONDS + 1,
			self::CUSTOM_MAXIMUM_RESPONSE_BYTES
		);
	}

	public function testResponseBudgetBelowMinimumIsRejected(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('workflow response budget is outside the supported range');

		new WorkflowLimits(
			self::CUSTOM_TIMEOUT_MILLISECONDS,
			self::INVALID_MINIMUM_LIMIT
		);
	}

	public function testResponseBudgetAboveMaximumIsRejected(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('workflow response budget is outside the supported range');

		new WorkflowLimits(
			self::CUSTOM_TIMEOUT_MILLISECONDS,
			WorkflowLimits::MAXIMUM_RESPONSE_BYTES + 1
		);
	}
}
