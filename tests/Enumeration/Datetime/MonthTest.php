<?php

declare(strict_types=1);

namespace Clover\Tests\Enumeration\Datetime;

use Clover\Enumeration\Month;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class MonthTest extends TestCase
{
	#[DataProvider('monthSequenceProvider')]
	public function testPreviousAndNextMonthsWrapAcrossYearBoundary(
		Month $month,
		Month $previous,
		Month $next
	): void {
		$this->assertSame($previous, $month->getPrevious());
		$this->assertSame($next, $month->getNext());
	}

	#[DataProvider('monthDaysProvider')]
	public function testNonLeapYearDayCounts(Month $month, int $days): void
	{
		$this->assertSame($days, $month->getNonLeapYearDays());
	}

	#[DataProvider('monthLeapDaysProvider')]
	public function testLeapYearDayCounts(Month $month, int $days): void
	{
		$this->assertSame($days, $month->getLeapYearDays());
	}

	public static function monthSequenceProvider(): array
	{
		$cases = Month::cases();
		$result = [];
		$count = count($cases);

		foreach ($cases as $index => $month) {
			$result[$month->name] = [
				$month,
				$cases[($index - 1 + $count) % $count],
				$cases[($index + 1) % $count],
			];
		}

		return $result;
	}

	public static function monthDaysProvider(): array
	{
		return [
			'JANUARY' => [Month::JANUARY, 31],
			'FEBRUARY' => [Month::FEBRUARY, 28],
			'MARCH' => [Month::MARCH, 31],
			'APRIL' => [Month::APRIL, 30],
			'MAY' => [Month::MAY, 31],
			'JUNE' => [Month::JUNE, 30],
			'JULY' => [Month::JULY, 31],
			'AUGUST' => [Month::AUGUST, 31],
			'SEPTEMBER' => [Month::SEPTEMBER, 30],
			'OCTOBER' => [Month::OCTOBER, 31],
			'NOVEMBER' => [Month::NOVEMBER, 30],
			'DECEMBER' => [Month::DECEMBER, 31],
		];
	}

	public static function monthLeapDaysProvider(): array
	{
		$data = self::monthDaysProvider();
		$data['FEBRUARY'][1] = 29;

		return $data;
	}
}
