<?php

declare(strict_types=1);

namespace Clover\Tests\Enumeration\Codec;

use Clover\Enumeration\Codec\VideoCodec;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class VideoCodecTest extends TestCase
{
	#[DataProvider('codecProvider')]
	public function testEveryVideoCodecHasDescriptionAndRoundTripsThroughFourCc(VideoCodec $codec): void
	{
		$this->assertNotSame('', $codec->label());
		$this->assertSame($codec, VideoCodec::fromFourCC($codec->value));
	}

	public function testShortFourCcIsPaddedBeforeLookup(): void
	{
		$this->assertSame(VideoCodec::DIB, VideoCodec::fromFourCC('DIB'));
		$this->assertSame(VideoCodec::RGB, VideoCodec::fromFourCC('RGB'));
	}

	public function testUnknownFourCcReturnsNull(): void
	{
		$this->assertNull(VideoCodec::fromFourCC('????'));
	}

	public static function codecProvider(): array
	{
		$result = [];

		foreach (VideoCodec::cases() as $codec) {
			$result[$codec->name] = [$codec];
		}

		return $result;
	}
}
