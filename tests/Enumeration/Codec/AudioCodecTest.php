<?php

declare(strict_types=1);

namespace Clover\Tests\Enumeration\Codec;

use Clover\Enumeration\Codec\AudioCodec;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AudioCodecTest extends TestCase
{
	#[DataProvider('codecProvider')]
	public function testEveryCodecRoundTripsThroughItsHexRepresentation(AudioCodec $codec): void
	{
		$this->assertSame($codec, AudioCodec::fromHex($codec->hex()));
		$this->assertMatchesRegularExpression('/^0x[0-9A-F]{4}$/', $codec->hex());
		$this->assertSame(sprintf('audio/vnd.wave;codec=%X', $codec->value), $codec->ianaCodecId());
		$this->assertNotSame('', $codec->label());
	}

	public function testFromHexAcceptsPrefixlessAndUppercasePrefixValues(): void
	{
		$this->assertSame(AudioCodec::MICROSOFT_PCM, AudioCodec::fromHex('0001'));
		$this->assertSame(AudioCodec::MICROSOFT_PCM, AudioCodec::fromHex('0X0001'));
	}

	public function testUnknownHexValueReturnsNull(): void
	{
		$this->assertNull(AudioCodec::fromHex('0x0BAD'));
	}

	public static function codecProvider(): array
	{
		$result = [];

		foreach (AudioCodec::cases() as $codec) {
			$result[$codec->name] = [$codec];
		}

		return $result;
	}
}
