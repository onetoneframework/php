<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\File;

use Clover\Classes\File\SteganographyProcessor;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SteganographyProcessorTest extends TestCase
{
	#[DataProvider('embeddedBitProvider')]
	public function testEmbedBitInChannelChangesOnlyTheLeastSignificantBit(
		int $channelValue,
		int $bit,
		int $expected
	): void {
		self::assertSame($expected, SteganographyProcessor::embedBitInChannel($channelValue, $bit));
	}

	public static function embeddedBitProvider(): array
	{
		return [
			'clear zero' => [0, 0, 0],
			'set zero' => [0, 1, 1],
			'clear odd value' => [127, 0, 126],
			'set even value' => [128, 1, 129],
			'clear maximum value' => [255, 0, 254],
			'set maximum value' => [255, 1, 255],
		];
	}

	#[DataProvider('extractedBitProvider')]
	public function testExtractBitFromChannelReturnsLeastSignificantBit(int $channelValue, int $expected): void
	{
		self::assertSame($expected, SteganographyProcessor::extractBitFromChannel($channelValue));
	}

	public static function extractedBitProvider(): array
	{
		return [
			'zero' => [0, 0],
			'one' => [1, 1],
			'even value' => [254, 0],
			'odd value' => [255, 1],
		];
	}
}
