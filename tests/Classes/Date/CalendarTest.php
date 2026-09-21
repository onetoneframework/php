<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Date;

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use Clover\Classes\Date\Calendar;
use DateTime;
use DateTimeZone;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use function json_decode;

class CalendarTest extends TestCase
{
	private const FIXTURE_DATE = '2024-06-15';

	private function calendar(string $date = self::FIXTURE_DATE, ?string $timezone = null): Calendar
	{
		return new Calendar($date, $timezone);
	}

	// -------------------------------------------------------------------------
	// Basic properties
	// -------------------------------------------------------------------------

	public function testBasicPropertiesForJune2024(): void
	{
		$calendar = $this->calendar();

		$this->assertSame(2024, $calendar->getYear());
		$this->assertSame(6, $calendar->getMonth());
		$this->assertSame(15, $calendar->getDay());
		$this->assertSame(6, $calendar->getFirstDayOfWeek());
		$this->assertSame(30, $calendar->getLastDay());
		$this->assertSame(31, $calendar->getLastDayOfPreviousMonth());
		$this->assertSame('June', $calendar->getTextureNameOfMonth());
		$this->assertSame(366, $calendar->getDaysInYear());
		$this->assertSame(12, $calendar->getCountOfMonths());
		$this->assertSame('2024-06-15', $calendar->getDate());
	}

	#[DataProvider('monthFixtureProvider')]
	public function testMonthBoundaries(
		string $date,
		int $firstDow,
		int $lastDay,
		int $daysInYear,
		string $monthName
	): void {
		$calendar = $this->calendar($date);

		$this->assertSame($firstDow, $calendar->getFirstDayOfWeek());
		$this->assertSame($lastDay, $calendar->getLastDay());
		$this->assertSame($daysInYear, $calendar->getDaysInYear());
		$this->assertSame($monthName, $calendar->getTextureNameOfMonth());
	}

	public static function monthFixtureProvider(): array
	{
		return [
			'june 2024' => ['2024-06-15', 6, 30, 366, 'June'],
			'february 2024 leap' => ['2024-02-15', 4, 29, 366, 'February'],
			'february 2023' => ['2023-02-15', 3, 28, 365, 'February'],
		];
	}

	// -------------------------------------------------------------------------
	// Setters (in-place mutation)
	// -------------------------------------------------------------------------

	public function testSettersUpdateDateInPlace(): void
	{
		$calendar = $this->calendar();

		$calendar->setYear(2025);
		$this->assertSame(2025, $calendar->getYear());
		$this->assertSame(6, $calendar->getMonth());

		$calendar->setMonth(3);
		$this->assertSame(3, $calendar->getMonth());
		$this->assertSame(31, $calendar->getLastDay());
		// textureNameOfMonth is refreshed only by setDate(), not setMonth()
		$this->assertSame('June', $calendar->getTextureNameOfMonth());

		$calendar->setDay(1);
		$this->assertSame(1, $calendar->getDay());

		$calendar->setDate(2024, 12, 25);
		$this->assertSame('2024-12-25', $calendar->getDate());
		$this->assertSame('December', $calendar->getTextureNameOfMonth());
	}

	// -------------------------------------------------------------------------
	// Navigation (immutable-style: returns new instances)
	// -------------------------------------------------------------------------

	public function testNavigationMethodsReturnNewInstances(): void
	{
		$original = $this->calendar();

		$this->assertNotSame($original, $original->nextDay());
		$this->assertSame('2024-06-15', $original->getDate());
		$this->assertSame('2024-06-16', $original->nextDay()->getDate());
		$this->assertSame('2024-06-14', $original->previousDay()->getDate());
		$this->assertSame('2024-07-01', $original->nextMonth()->getDate());
		$this->assertSame('2024-05-01', $original->previousMonth()->getDate());
		$this->assertSame('2025-06-15', $original->nextYear()->getDate());
		$this->assertSame('2023-06-15', $original->previousYear()->getDate());
		$this->assertSame('2024-06-22', $original->nextWeek()->getDate());
		$this->assertSame('2024-06-08', $original->previousWeek()->getDate());
	}

	public function testAddMethods(): void
	{
		$calendar = $this->calendar();

		$this->assertSame('2024-06-20', $calendar->addDays(5)->getDate());
		$this->assertSame('2024-08-15', $calendar->addMonths(2)->getDate());
		$this->assertSame('2026-06-15', $calendar->addYears(2)->getDate());
		$this->assertSame('2024-06-29', $calendar->addWeeks(2)->getDate());
	}

	public function testGoToClampsInvalidDay(): void
	{
		$calendar = $this->calendar('2024-01-15');

		$this->assertSame('2024-02-29', $calendar->goTo(2024, 2, 31)->getDate());
		$this->assertSame('2024-03-10', $calendar->goTo(2024, 3, 10)->getDate());
	}

	public function testBoundaryShortcuts(): void
	{
		$calendar = $this->calendar();

		$this->assertSame('2024-06-01', $calendar->firstOfMonth()->getDate());
		$this->assertSame('2024-06-30', $calendar->lastOfMonth()->getDate());
		$this->assertSame('2024-01-01', $calendar->firstOfYear()->getDate());
		$this->assertSame('2024-12-31', $calendar->lastOfYear()->getDate());
		$this->assertSame('2024-06-10', $calendar->startOfWeek()->getDate());
		$this->assertSame('2024-06-16', $calendar->endOfWeek()->getDate());
		$this->assertSame('2024-04-01', $calendar->startOfQuarter()->getDate());
		$this->assertSame('2024-06-30', $calendar->endOfQuarter()->getDate());
	}

	public function testTodayReturnsCurrentDate(): void
	{
		$today = $this->calendar()->today();
		$this->assertSame(date('Y-m-d'), $today->getDate());
	}

	public function testCopyCreatesIndependentInstance(): void
	{
		$original = $this->calendar();
		$copy = $original->copy();

		$this->assertNotSame($original, $copy);
		$this->assertTrue($original->isSameDay($copy));
		$this->assertSame('2024-07-01', $copy->nextMonth()->getDate());
		$this->assertSame('2024-06-15', $original->getDate());
	}

	// -------------------------------------------------------------------------
	// Conversion and formatting
	// -------------------------------------------------------------------------

	public function testToDateTimeAndFormatting(): void
	{
		$calendar = $this->calendar();

		$dt = $calendar->toDateTime();
		$this->assertInstanceOf(DateTime::class, $dt);
		$this->assertSame('2024-06-15', $dt->format('Y-m-d'));

		$this->assertSame('15', $calendar->format('d'));
		$this->assertSame('June 2024', (string) $calendar);
		$this->assertStringStartsWith('2024-06-15', $calendar->getISODate());
	}

	public function testMonthDateHelpers(): void
	{
		$calendar = $this->calendar();

		$this->assertSame('2024-06-01', $calendar->getFirstDateOfMonth());
		$this->assertSame('2024-06-30', $calendar->getLastDateOfMonth());
		$this->assertSame(
			strtotime('2024-06-15 00:00:00'),
			$calendar->getTimestamp()
		);
	}

	// -------------------------------------------------------------------------
	// Quarter, ISO, day-of-year
	// -------------------------------------------------------------------------

	public function testQuarterAndIsoMetrics(): void
	{
		$calendar = $this->calendar();

		$this->assertSame(2, $calendar->getQuarter());
		$this->assertFalse($calendar->isFirstMonthOfQuarter());
		$this->assertTrue($calendar->isLastMonthOfQuarter());
		$this->assertGreaterThanOrEqual(1, $calendar->getISOWeekNumber());
		$this->assertSame(167, $calendar->getDayOfYear());
	}

	public function testMarchIsLastMonthOfQuarter(): void
	{
		$calendar = $this->calendar('2024-03-15');

		$this->assertFalse($calendar->isFirstMonthOfQuarter());
		$this->assertTrue($calendar->isLastMonthOfQuarter());
		$this->assertSame(1, $calendar->getQuarter());
	}

	// -------------------------------------------------------------------------
	// Leap year and weekday labels
	// -------------------------------------------------------------------------

	public function testLeapYearDetection(): void
	{
		$this->assertTrue($this->calendar('2024-06-15')->isLeapYear());
		$this->assertFalse($this->calendar('2023-06-15')->isLeapYear());
	}

	public function testWeekdayLabels(): void
	{
		$saturday = $this->calendar('2024-06-15');
		$this->assertSame(6, $saturday->getDayOfWeek());
		$this->assertSame(6, $saturday->getISODayOfWeek());
		$this->assertSame('Sat', $saturday->getDayName());
		$this->assertSame('Saturday', $saturday->getFullDayName());
		$this->assertSame('Jun', $saturday->getShortMonthName());

		$monday = $this->calendar('2024-06-17');
		$this->assertSame('Mon', $monday->getDayName());
		$this->assertSame('Monday', $monday->getFullDayName());
	}

	public function testGetDaysArray(): void
	{
		$days = $this->calendar()->getDays();
		$this->assertCount(7, $days);
		$this->assertSame('Sun', $days[0]);
		$this->assertSame('Sat', $days[6]);
	}

	// -------------------------------------------------------------------------
	// Temporal predicates
	// -------------------------------------------------------------------------

	public function testIsTodayPastFuture(): void
	{
		$this->assertTrue($this->calendar(date('Y-m-d'))->isToday());
		$this->assertFalse($this->calendar('2000-01-01')->isToday());
		$this->assertTrue($this->calendar('2000-01-01')->isPast());
		$this->assertFalse($this->calendar('2000-01-01')->isFuture());
		$this->assertTrue($this->calendar('2099-12-31')->isFuture());
		$this->assertFalse($this->calendar('2099-12-31')->isPast());
	}

	public function testWeekendWeekdayAndBusinessDay(): void
	{
		$saturday = $this->calendar('2024-06-15');
		$monday = $this->calendar('2024-06-17');

		$this->assertTrue($saturday->isWeekend());
		$this->assertFalse($saturday->isWeekday());
		$this->assertFalse($saturday->isBusinessDay());
		$this->assertTrue($monday->isWeekday());
		$this->assertTrue($monday->isBusinessDay());
		$this->assertFalse($monday->isBusinessDay(['2024-06-17']));
	}

	// -------------------------------------------------------------------------
	// Comparison between calendars
	// -------------------------------------------------------------------------

	public function testCalendarComparisons(): void
	{
		$a = $this->calendar('2024-06-15');
		$b = $this->calendar('2024-06-20');
		$c = $this->calendar('2024-07-01');

		$this->assertTrue($a->isBefore($b));
		$this->assertTrue($b->isAfter($a));
		$this->assertTrue($b->isBetween($a, $c));
		$this->assertFalse($c->isBetween($a, $b));
		$this->assertTrue($a->isSameDay($this->calendar('2024-06-15 18:00:00')));
		$this->assertTrue($a->isSameMonth($b));
		$this->assertFalse($a->isSameMonth($c));
		$this->assertTrue($a->isSameYear($c));
		$this->assertSame(5, $a->diffInDays($b));
		$this->assertSame(0, $a->diffInMonths($a));
		$this->assertSame(0, $a->diffInYears($a));
	}

	// -------------------------------------------------------------------------
	// Month metrics
	// -------------------------------------------------------------------------

	public function testMonthAndYearMetrics(): void
	{
		$calendar = $this->calendar();

		$this->assertSame(6, $calendar->getWeekCountInMonth());
		$this->assertSame(15, $calendar->getRemainingDaysInMonth());
		$this->assertSame(366 - 167, $calendar->getRemainingDaysInYear());
		$this->assertSame(15, $calendar->getElapsedDaysInMonth());
		$this->assertEqualsWithDelta(0.5, $calendar->getMonthProgress(), 0.0001);
		$this->assertGreaterThan(0.0, $calendar->getYearProgress());
		$this->assertLessThanOrEqual(1.0, $calendar->getYearProgress());
		$this->assertSame(3, $calendar->getWeekOfMonth());
		$this->assertSame(31, $calendar->getDaysInMonth(1));
		$this->assertSame(29, $calendar->getDaysInMonth(2));
	}

	// -------------------------------------------------------------------------
	// Name listings
	// -------------------------------------------------------------------------

	public function testNameListings(): void
	{
		$calendar = $this->calendar();

		$monthNames = $calendar->getMonthNames();
		$this->assertCount(12, $monthNames);
		$this->assertSame('January', $monthNames[1]);
		$this->assertSame('December', $monthNames[12]);

		$shortMonths = $calendar->getShortMonthNames();
		$this->assertSame('Jun', $shortMonths[6]);

		$fullDays = $calendar->getFullDayNames();
		$this->assertCount(7, $fullDays);
		$this->assertSame('Sunday', $fullDays[0]);
		$this->assertSame('Saturday', $fullDays[6]);
	}

	// -------------------------------------------------------------------------
	// Grid and date listings
	// -------------------------------------------------------------------------

	public function testGetDatesInMonth(): void
	{
		$dates = $this->calendar()->getDatesInMonth();
		$this->assertCount(30, $dates);
		$this->assertSame('2024-06-01', $dates[0]);
		$this->assertSame('2024-06-30', $dates[29]);
	}

	public function testGetMonthGridWithPadding(): void
	{
		$grid = $this->calendar()->getMonthGrid(true);

		$this->assertCount(6, $grid);
		$this->assertCount(7, $grid[0]);

		$currentCells = 0;
		foreach ($grid as $row) {
			foreach ($row as $cell) {
				if ($cell !== null && $cell['current'] === true) {
					$currentCells++;
				}
			}
		}
		$this->assertSame(30, $currentCells);
	}

	public function testGetMonthGridWithoutPadding(): void
	{
		$grid = $this->calendar()->getMonthGrid(false);
		$this->assertCount(6, $grid);
		$this->assertNull($grid[0][0]);
	}

	public function testWeekendsAndWeekdaysInMonth(): void
	{
		$calendar = $this->calendar();

		$weekends = $calendar->getWeekendsInMonth();
		$this->assertCount(10, $weekends);
		$this->assertContains('2024-06-01', $weekends);
		$this->assertContains('2024-06-30', $weekends);

		$weekdays = $calendar->getWeekdaysInMonth();
		$this->assertCount(20, $weekdays);
		$this->assertContains('2024-06-17', $weekdays);

		$counts = $calendar->getWeekdayCountsInMonth();
		$this->assertSame(5, $counts['Sat']);
		$this->assertSame(5, $counts['Sun']);
		$this->assertSame(4, $counts['Mon']);
	}

	public function testGetDatesForWeekdayAndIsoWeeks(): void
	{
		$calendar = $this->calendar();

		$saturdays = $calendar->getDatesForWeekday(6);
		$this->assertCount(5, $saturdays);
		$this->assertContains('2024-06-15', $saturdays);

		$isoWeeks = $calendar->getISOWeeksInMonth();
		$this->assertNotEmpty($isoWeeks);
		$this->assertContains(24, $isoWeeks);
	}

	public function testGetNthWeekday(): void
	{
		$calendar = $this->calendar();

		$this->assertSame('2024-06-15', $calendar->getNthWeekday(3, 6));
		$this->assertNull($calendar->getNthWeekday(6, 6));
		$this->assertSame('2024-06-24', $calendar->getNthWeekday(-1, 1));
	}

	public function testGetRecurringDatesInMonth(): void
	{
		$calendar = $this->calendar();
		$weekly = $calendar->getRecurringDatesInMonth('2024-06-01', 'P7D');

		$this->assertContains('2024-06-01', $weekly);
		$this->assertContains('2024-06-08', $weekly);
		$this->assertContains('2024-06-29', $weekly);
		$this->assertNotContains('2024-07-01', $weekly);
	}

	// -------------------------------------------------------------------------
	// Astronomical and special dates
	// -------------------------------------------------------------------------

	public function testGetEasterDate(): void
	{
		$this->assertSame('2024-03-31', $this->calendar()->getEasterDate());
		$this->assertSame('2025-04-20', $this->calendar('2025-01-01')->getEasterDate());
	}

	public function testGetLunarPhase(): void
	{
		$phase = $this->calendar()->getLunarPhase();

		$this->assertArrayHasKey('phase', $phase);
		$this->assertArrayHasKey('age', $phase);
		$this->assertArrayHasKey('illumination', $phase);
		$this->assertSame('First Quarter', $phase['phase']);
		$this->assertEqualsWithDelta(8.5, $phase['age'], 0.1);
		$this->assertGreaterThanOrEqual(0.0, $phase['illumination']);
		$this->assertLessThanOrEqual(1.0, $phase['illumination']);
	}

	public function testGetSunTimes(): void
	{
		$sun = $this->calendar()->getSunTimes(37.5665, 126.9780);

		$this->assertArrayHasKey('sunrise', $sun);
		$this->assertArrayHasKey('sunset', $sun);
		$this->assertArrayHasKey('daylight_hours', $sun);
		$this->assertMatchesRegularExpression('/^\d{2}:\d{2}:\d{2}$/', $sun['sunrise']);
		$this->assertMatchesRegularExpression('/^\d{2}:\d{2}:\d{2}$/', $sun['sunset']);
		$this->assertGreaterThan(0.0, $sun['daylight_hours']);
	}

	// -------------------------------------------------------------------------
	// Holidays and business days in month
	// -------------------------------------------------------------------------

	public function testHolidayHelpers(): void
	{
		$calendar = $this->calendar();
		$holidays = ['2024-06-06', '2024-07-01', '2024-06-15'];

		$this->assertTrue($calendar->isHoliday(6, $holidays));
		$this->assertFalse($calendar->isHoliday(7, $holidays));
		$this->assertSame(['2024-06-06', '2024-06-15'], $calendar->getHolidaysInMonth($holidays));
		$this->assertSame(19, $calendar->getBusinessDaysInMonth(['2024-06-06']));
	}

	public function testNextAndPreviousBusinessDay(): void
	{
		$friday = $this->calendar('2024-06-14');
		$this->assertSame('2024-06-17', $friday->getNextBusinessDay());

		$monday = $this->calendar('2024-06-17');
		$this->assertSame('2024-06-14', $monday->getPreviousBusinessDay());

		$withHoliday = $this->calendar('2024-06-13');
		$this->assertSame('2024-06-17', $withHoliday->getNextBusinessDay(['2024-06-14']));
	}

	// -------------------------------------------------------------------------
	// Timezone
	// -------------------------------------------------------------------------

	public function testTimezoneConstructor(): void
	{
		$utc = $this->calendar(self::FIXTURE_DATE, 'UTC');
		$this->assertInstanceOf(DateTimeZone::class, $utc->getTimezone());
		$this->assertSame('UTC', $utc->getTimezoneName());

		$default = $this->calendar();
		$this->assertNull($default->getTimezone());
		$this->assertNull($default->getTimezoneName());

		$navigated = $utc->nextMonth();
		$this->assertSame('UTC', $navigated->getTimezoneName());
	}

	// -------------------------------------------------------------------------
	// Serialization
	// -------------------------------------------------------------------------

	public function testToArrayAndToJson(): void
	{
		$calendar = $this->calendar();
		$array = $calendar->toArray();

		$this->assertSame(2024, $array['year']);
		$this->assertSame(6, $array['month']);
		$this->assertSame(15, $array['day']);
		$this->assertSame('June', $array['monthName']);
		$this->assertSame(30, $array['lastDay']);
		$this->assertSame(6, $array['firstDayOfWeek']);
		$this->assertSame(2, $array['quarter']);
		$this->assertTrue($array['isLeapYear']);

		$decoded = json_decode($calendar->toJSON(), true, 512, JSON_THROW_ON_ERROR);
		$this->assertSame($array['year'], $decoded['year']);
		$this->assertSame($array['month'], $decoded['month']);
	}
}
