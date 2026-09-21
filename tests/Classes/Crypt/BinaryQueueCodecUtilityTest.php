<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Crypt;

use Clover\Classes\Crypt\BinaryQueueCodec;
use PHPUnit\Framework\TestCase;

final class BinaryQueueCodecUtilityTest extends TestCase
{
	public function testHexAndBinaryConversionsNormalizeLeadingZeros(): void
	{
		$this->assertSame('11111111', BinaryQueueCodec::hexToBin('00Ff'));
		$this->assertSame('f', BinaryQueueCodec::binToHex('00001111'));
		$this->assertSame('0', BinaryQueueCodec::hexToBin('0000'));
		$this->assertSame('0', BinaryQueueCodec::binToHex('0000'));
		$this->assertSame('', BinaryQueueCodec::hexToBin(''));
		$this->assertSame('', BinaryQueueCodec::binToHex(''));
	}

	public function testDecimalAndOctalConversionsSupportValuesBeyondNativeIntegerRange(): void
	{
		$decimal = '123456789012345678901234567890';

		$octal = BinaryQueueCodec::decimalStringToOctal($decimal);

		$this->assertNotSame('', $octal);
		$this->assertMatchesRegularExpression('/^[0-7]+$/', $octal);
		$this->assertSame($decimal, BinaryQueueCodec::octalToDecimalString($octal));
	}

	public function testHexEncodingRoundTripsArbitraryBytes(): void
	{
		$input = "A\0B\xFF";
		$hex = BinaryQueueCodec::toHex($input);

		$this->assertGreaterThanOrEqual(40, strlen($hex));
		$this->assertSame($input, BinaryQueueCodec::fromHex($hex));
		$this->assertSame("\0", BinaryQueueCodec::fromHex('0000'));
	}

	public function testCompatibilityHelpersExposeDocumentedConversions(): void
	{
		$this->assertSame('417A', BinaryQueueCodec::asciiToHex('Az'));
		$this->assertSame(45, BinaryQueueCodec::binaryStringToDecimal('101101'));
	}

	public function testQueueDispatcherRoundTripsBinaryData(): void
	{
		$binary = '111100001110001010100001111';

		$encoded = BinaryQueueCodec::queue($binary, 'Encrypt', 4);

		$this->assertNotSame($binary, $encoded);
		$this->assertSame($binary, BinaryQueueCodec::queue($encoded, 'Decrypt'));
		$this->assertSame($binary, BinaryQueueCodec::queue($binary, 'Unknown'));
	}

	public function testPrefixCompatibilityDispatcherIsReversible(): void
	{
		$encoded = BinaryQueueCodec::makePrefix('1231', 'Encrypt');

		$this->assertSame('5123', $encoded);
		$this->assertSame('1231', BinaryQueueCodec::makePrefix($encoded, 'Decrypt'));
		$this->assertSame('1231', BinaryQueueCodec::makePrefix('1231', 'Unknown'));
	}
}
