<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Data;

use Clover\Classes\Data\Unicode;
use PHPUnit\Framework\TestCase;

final class UnicodeTest extends TestCase
{
	public function testGetCodePointSupportsSingleByteCharacters(): void
	{
		self::assertSame(65, Unicode::getCodePoint('A'));
	}

	public function testGetCodePointSupportsMultibyteCharacters(): void
	{
		self::assertSame(0x1F642, Unicode::getCodePoint('🙂', true));
	}

	public function testSplitPreservesUnicodeCharacters(): void
	{
		self::assertSame(['A', '🙂', '한'], Unicode::split('A🙂한'));
	}
}
