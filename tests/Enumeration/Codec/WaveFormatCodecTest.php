<?php

declare(strict_types=1);

namespace Clover\Tests\Enumeration\Codec;

use Clover\Enumeration\Codec\WaveFormatCodec;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class WaveFormatCodecTest extends TestCase
{
	#[DataProvider('codecProvider')]
	public function testEveryWaveFormatRoundTripsThroughItsHexRepresentation(WaveFormatCodec $codec): void
	{
		$this->assertSame($codec, WaveFormatCodec::fromHex($codec->hex()));
		$this->assertMatchesRegularExpression('/^0x[0-9A-F]{4}$/', $codec->hex());
		$this->assertSame(sprintf('audio/vnd.wave;codec=%X', $codec->value), $codec->ianaCodecId());
	}

	public function testFromHexAcceptsPrefixlessValues(): void
	{
		$this->assertSame(WaveFormatCodec::WAVE_FORMAT_PCM, WaveFormatCodec::fromHex('0001'));
	}

	public function testUnknownHexValueReturnsNull(): void
	{
		$this->assertNull(WaveFormatCodec::fromHex('FFFF'));
	}

	public static function codecProvider(): array
	{
		$result = [];

		foreach (WaveFormatCodec::cases() as $codec) {
			$result[$codec->name] = [$codec];
		}

		return $result;
	}
}
