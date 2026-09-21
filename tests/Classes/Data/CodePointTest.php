<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Data;

use Clover\Classes\Data\CodePoint;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CodePointTest extends TestCase
{
	#[DataProvider('cjkBoundaryProvider')]
	public function testCjkClassificationAtRangeBoundaries(int $codePoint, bool $expected): void
	{
		self::assertSame($expected, CodePoint::isCJK($codePoint));
	}

	public static function cjkBoundaryProvider(): array
	{
		return [
			'before Hangul Jamo' => [0x10FF, false],
			'first Hangul Jamo' => [0x1100, true],
			'last Hangul Jamo' => [0x115F, true],
			'after Hangul Jamo' => [0x1160, false],
			'first CJK Radicals Supplement' => [0x2E80, true],
			'excluded ideographic closing mark' => [0x303F, false],
			'last Yi Radical' => [0xA4CF, true],
			'after Yi Radical' => [0xA4D0, false],
		];
	}

	#[DataProvider('fullWidthBoundaryProvider')]
	public function testFullWidthFormsClassificationAtRangeBoundaries(int $codePoint, bool $expected): void
	{
		self::assertSame($expected, CodePoint::isFullWidthForms($codePoint));
	}

	public static function fullWidthBoundaryProvider(): array
	{
		return [
			'before fullwidth forms' => [0xFEFF, false],
			'first fullwidth form' => [0xFF00, true],
			'last supported fullwidth form' => [0xFF60, true],
			'after supported fullwidth forms' => [0xFF61, false],
		];
	}
}
