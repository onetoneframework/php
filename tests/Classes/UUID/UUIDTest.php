<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\UUID;

use Clover\Classes\UUID\UUID;
use PHPUnit\Framework\TestCase;

final class UUIDTest extends TestCase
{
	public function testValidationAndVersionRejectMalformedIdentifiers(): void
	{
		$this->assertTrue(UUID::isValid('21f7f8de-8051-5b89-8680-0195ef798b6a'));
		$this->assertSame(5, UUID::version('21f7f8de-8051-5b89-8680-0195ef798b6a'));
		$this->assertFalse(UUID::isValid('not-a-uuid'));
		$this->assertNull(UUID::version('not-a-uuid'));
	}

	public function testBinaryConversionRoundTripsUuid(): void
	{
		$uuid = '21f7f8de-8051-5b89-8680-0195ef798b6a';
		$binary = UUID::toBinary($uuid);

		$this->assertSame(16, strlen($binary));
		$this->assertSame($uuid, UUID::fromBinary($binary));
	}

	public function testVersionSevenEmbedsProvidedMillisecondTimestamp(): void
	{
		$milliseconds = 1_700_000_123_456;
		$uuid = UUID::v7($milliseconds);

		$this->assertTrue(UUID::isValid($uuid));
		$this->assertSame(7, UUID::version($uuid));
		$this->assertEqualsWithDelta($milliseconds / 1000, UUID::extractTimestamp($uuid), 0.000001);
	}

	public function testNonTimeBasedUuidHasNoExtractableTimestamp(): void
	{
		$this->assertNull(UUID::extractTimestamp('21f7f8de-8051-5b89-8680-0195ef798b6a'));
	}
}
