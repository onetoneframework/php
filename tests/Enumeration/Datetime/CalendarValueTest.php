<?php

declare(strict_types=1);

namespace Clover\Tests\Enumeration\Datetime;

use Clover\Enumeration\DateTime\DateFormat;
use Clover\Enumeration\Era;
use Clover\Enumeration\Meridiem;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CalendarValueTest extends TestCase
{
	#[DataProvider('hourProvider')]
	public function testMeridiemIsDerivedFromHour(int $hour, Meridiem $expected): void
	{
		$this->assertSame($expected, Meridiem::fromHour($hour));
	}

	public function testMeridiemToggleIsReversible(): void
	{
		foreach (Meridiem::cases() as $meridiem) {
			$this->assertNotSame($meridiem, $meridiem->toggle());
			$this->assertSame($meridiem, $meridiem->toggle()->toggle());
		}
	}

	#[DataProvider('yearProvider')]
	public function testEraIsDerivedFromYear(int $year, Era $expected): void
	{
		$this->assertSame($expected, Era::fromYear($year));
	}

	public function testEraToggleIsReversible(): void
	{
		foreach (Era::cases() as $era) {
			$this->assertNotSame($era, $era->toggle());
			$this->assertSame($era, $era->toggle()->toggle());
		}
	}

	public function testDefaultDateFormatIsIso8601(): void
	{
		$this->assertSame(DateFormat::ISO8601, DateFormat::default());
	}

	public static function hourProvider(): array
	{
		return [
			'midnight' => [0, Meridiem::ANTE_MERIDIEM],
			'late morning' => [11, Meridiem::ANTE_MERIDIEM],
			'noon' => [12, Meridiem::POST_MERIDIEM],
			'late evening' => [23, Meridiem::POST_MERIDIEM],
		];
	}

	public static function yearProvider(): array
	{
		return [
			'positive year' => [2026, Era::ANNO_DOMINI],
			'year one' => [1, Era::ANNO_DOMINI],
			'year zero' => [0, Era::BEFORE_CHRIST],
			'negative year' => [-44, Era::BEFORE_CHRIST],
		];
	}
}
