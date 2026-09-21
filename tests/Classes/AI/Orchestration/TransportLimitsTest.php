<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Tests\Classes\AI\Orchestration;

use Clover\Classes\AI\Orchestration\TransportLimits;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class TransportLimitsTest extends TestCase
{
	private const CUSTOM_CONNECTION_TIMEOUT_MILLISECONDS = 250;

	private const CUSTOM_RESPONSE_TIMEOUT_MILLISECONDS = 2_000;

	private const CUSTOM_MAXIMUM_RESPONSE_BYTES = 4_096;

	private const INVALID_MINIMUM_LIMIT = 0;

	public function testDefaultLimitsArePreserved(): void
	{
		$limits = new TransportLimits();

		self::assertSame(
			TransportLimits::DEFAULT_CONNECTION_TIMEOUT_MILLISECONDS,
			$limits->connectionTimeoutMilliseconds()
		);
		self::assertSame(
			TransportLimits::DEFAULT_RESPONSE_TIMEOUT_MILLISECONDS,
			$limits->responseTimeoutMilliseconds()
		);
		self::assertSame(
			TransportLimits::DEFAULT_MAXIMUM_RESPONSE_BYTES,
			$limits->maximumResponseBytes()
		);
	}

	public function testCustomLimitsArePreserved(): void
	{
		$limits = new TransportLimits(
			self::CUSTOM_CONNECTION_TIMEOUT_MILLISECONDS,
			self::CUSTOM_RESPONSE_TIMEOUT_MILLISECONDS,
			self::CUSTOM_MAXIMUM_RESPONSE_BYTES
		);

		self::assertSame(
			self::CUSTOM_CONNECTION_TIMEOUT_MILLISECONDS,
			$limits->connectionTimeoutMilliseconds()
		);
		self::assertSame(
			self::CUSTOM_RESPONSE_TIMEOUT_MILLISECONDS,
			$limits->responseTimeoutMilliseconds()
		);
		self::assertSame(
			self::CUSTOM_MAXIMUM_RESPONSE_BYTES,
			$limits->maximumResponseBytes()
		);
	}

	public function testConnectionTimeoutBelowMinimumIsRejected(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('connection timeout is outside the supported range');

		new TransportLimits(
			self::INVALID_MINIMUM_LIMIT,
			self::CUSTOM_RESPONSE_TIMEOUT_MILLISECONDS,
			self::CUSTOM_MAXIMUM_RESPONSE_BYTES
		);
	}

	public function testResponseTimeoutBelowConnectionTimeoutIsRejected(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('response timeout must include the connection timeout');

		new TransportLimits(
			self::CUSTOM_RESPONSE_TIMEOUT_MILLISECONDS,
			self::CUSTOM_CONNECTION_TIMEOUT_MILLISECONDS,
			self::CUSTOM_MAXIMUM_RESPONSE_BYTES
		);
	}

	public function testResponseSizeBelowMinimumIsRejected(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('response size limit is outside the supported range');

		new TransportLimits(
			self::CUSTOM_CONNECTION_TIMEOUT_MILLISECONDS,
			self::CUSTOM_RESPONSE_TIMEOUT_MILLISECONDS,
			self::INVALID_MINIMUM_LIMIT
		);
	}

	public function testTimeoutAboveMaximumIsRejected(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('connection timeout is outside the supported range');

		new TransportLimits(
			TransportLimits::MAXIMUM_TIMEOUT_MILLISECONDS + 1,
			TransportLimits::MAXIMUM_TIMEOUT_MILLISECONDS + 1,
			self::CUSTOM_MAXIMUM_RESPONSE_BYTES
		);
	}

	public function testResponseSizeAboveMaximumIsRejected(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('response size limit is outside the supported range');

		new TransportLimits(
			self::CUSTOM_CONNECTION_TIMEOUT_MILLISECONDS,
			self::CUSTOM_RESPONSE_TIMEOUT_MILLISECONDS,
			TransportLimits::MAXIMUM_RESPONSE_BYTES + 1
		);
	}
}
