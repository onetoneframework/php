<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Compression;

use Clover\Classes\Compression\GZip;
use PHPUnit\Framework\TestCase;

final class GzipTest extends TestCase
{
	public function testEncodeAndDecodeRoundTripText(): void
	{
		$input = 'Clover compression payload with UTF-8: 테스트';

		$encoded = GZip::encode($input);

		$this->assertIsString($encoded);
		$this->assertNotSame($input, $encoded);
		$this->assertSame($input, GZip::decode($encoded));
	}

	public function testDeflateAndInflateRoundTripBinaryData(): void
	{
		$input = "\0\x01\x02binary\xFFdata";

		$deflated = GZip::deflate($input, 6, ZLIB_ENCODING_RAW);

		$this->assertIsString($deflated);
		$this->assertSame($input, GZip::inflate($deflated));
	}

	public function testCompressAndUncompressRoundTripWithZlibEncoding(): void
	{
		$input = str_repeat('repeatable-', 32);

		$compressed = GZip::compress($input, 9, ZLIB_ENCODING_DEFLATE);

		$this->assertIsString($compressed);
		$this->assertSame($input, GZip::uncompress($compressed));
	}
}
