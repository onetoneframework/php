<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Video;

use Clover\Classes\Video\H264\BitReader;
use PHPUnit\Framework\TestCase;

final class BitReaderTest extends TestCase
{
	public function testReadsFixedWidthValuesAndTracksPosition(): void
	{
		$reader = new BitReader("\xB2");

		$this->assertSame(8, $reader->length());
		$this->assertSame(5, $reader->read(3));
		$this->assertSame(3, $reader->position());
		$this->assertSame(18, $reader->read(5));
		$this->assertSame(8, $reader->position());
	}

	public function testReadsUnsignedAndSignedExpGolombValues(): void
	{
		$this->assertSame(0, (new BitReader("\x80"))->readUnsignedExpGolomb());
		$this->assertSame(1, (new BitReader("\x40"))->readUnsignedExpGolomb());
		$this->assertSame(2, (new BitReader("\x60"))->readUnsignedExpGolomb());
		$this->assertSame(1, (new BitReader("\x40"))->readSignedExpGolomb());
		$this->assertSame(-1, (new BitReader("\x60"))->readSignedExpGolomb());
	}

	public function testAlignAndRbspStopBitHandling(): void
	{
		$reader = new BitReader("\xA0");

		$this->assertTrue($reader->hasMoreData());
		$this->assertSame(2, $reader->read(2));
		$this->assertFalse($reader->hasMoreData());

		$reader = new BitReader("\xFF\x00");
		$reader->read(3);
		$reader->alignToByte();
		$this->assertSame(8, $reader->position());
	}

	public function testUnescapeRemovesNalEmulationPreventionBytes(): void
	{
		$this->assertSame(
			"\x00\x00\x01\x00\x00\x02",
			BitReader::unescape("\x00\x00\x03\x01\x00\x00\x03\x02")
		);
	}
}
