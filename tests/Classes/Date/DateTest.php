<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Date;

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use Clover\Classes\Date\Date;
use Clover\Enumeration\TimeZone;
use DateTime;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use function function_exists;
use function strtotime;

class DateTest extends TestCase
{
	private const FIXTURE_DATE = '2024-05-07';
	private const FIXTURE_DATETIME = '2024-05-07 12:00:00';

	public function testToJulianAndJulianToGregorianRoundTrip(): void
	{
		$julian = Date::toJulian(2024, 5, 7);
		$this->assertEqualsWithDelta(2460437.5, $julian, 0.0001);

		$datetime = Date::julianToGregorian($julian);
		$this->assertSame('2024-05-07', $datetime->format('Y-m-d'));
	}

	public function testGetJulianCenturiesAtJ2000(): void
	{
		$jc = Date::getJulianCenturies(2451545.0);
		$this->assertIsFloat($jc);
		$this->assertLessThan(1.0, abs($jc));
	}

	public function testGetLastDayOfMonth(): void
	{
		$this->assertSame('29', (string) Date::getLastDayOfMonth(2024, 2));
		$this->assertSame('31', (string) Date::getLastDayOfMonth(2024, 1));
		$this->assertSame('28', (string) Date::getLastDayOfMonth(2023, 2));
	}

	public function testWeekBoundaries(): void
	{
		$this->assertSame('2024-05-06', Date::getStartOfWeek(self::FIXTURE_DATE));
		$this->assertSame('2024-05-12', Date::getEndOfWeek(self::FIXTURE_DATE));
	}

	public function testQuarterBoundaries(): void
	{
		$this->assertSame('2024-04-01', Date::getQuarterStart(self::FIXTURE_DATE));
		$this->assertSame('2024-06-30', Date::getQuarterEnd(self::FIXTURE_DATE));
		$this->assertSame(2, Date::getQuarterOfDate(self::FIXTURE_DATE));
	}

	public function testPeriodStartAndEndHelpers(): void
	{
		$this->assertSame('2024-05-01', Date::getStartOfMonth(self::FIXTURE_DATE));
		$this->assertSame('2024-05-31', Date::getEndOfMonth(self::FIXTURE_DATE));
		$this->assertSame('2024-01-01', Date::getStartOfYear(self::FIXTURE_DATE));
		$this->assertSame('2024-12-31', Date::getEndOfYear(self::FIXTURE_DATE));
		$this->assertSame('2024-05-07 00:00:00', Date::getStartOfDay(self::FIXTURE_DATE));
		$this->assertSame('2024-05-07 23:59:59', Date::getEndOfDay(self::FIXTURE_DATE));
	}

	public function testGetDatesBetween(): void
	{
		$dates = Date::getDatesBetween('2024-05-01', '2024-05-07');
		$this->assertCount(7, $dates);
		$this->assertSame('2024-05-01', $dates[0]);
		$this->assertSame('2024-05-07', $dates[6]);
	}

	public function testGetYearsBetween(): void
	{
		$years = Date::getYearsBetween('2000-01-01', '2024-05-07');
		$this->assertCount(25, $years);
		$this->assertSame('2000', $years[0]);
		$this->assertSame('2024', $years[24]);
	}

	public function testGetMonthsBetween(): void
	{
		$months = Date::getMonthsBetween('2024-03-15', '2024-05-10');
		$this->assertSame(['2024-03', '2024-04', '2024-05'], $months);
	}

	public function testGetWeeksBetween(): void
	{
		$weeks = Date::getWeeksBetween('2024-05-01', '2024-05-14');
		$this->assertNotEmpty($weeks);
		$this->assertArrayHasKey('start', $weeks[0]);
		$this->assertArrayHasKey('end', $weeks[0]);
		$this->assertArrayHasKey('week', $weeks[0]);
	}

	public function testGetRecurringDates(): void
	{
		$dates = Date::getRecurringDates('2024-05-01', '2024-05-15', '7 days');
		$this->assertContains('2024-05-01', $dates);
		$this->assertContains('2024-05-08', $dates);
		$this->assertContains('2024-05-15', $dates);
	}

	public function testGetDateRange(): void
	{
		$range = Date::getDateRange('2024-05-01', '2024-05-03');
		$this->assertCount(3, $range);
	}

	public function testGetQuartersBetween(): void
	{
		$quarters = Date::getQuartersBetween('2024-01-15', '2024-07-01');
		$this->assertContains('2024-Q1', $quarters);
		$this->assertContains('2024-Q2', $quarters);
		$this->assertContains('2024-Q3', $quarters);
	}

	public function testGetRelativeDayName(): void
	{
		$this->assertSame('Yesterday', Date::getRelativeDayName(Date::getYesterday()));
		$this->assertSame('Today', Date::getRelativeDayName(Date::getToday()));
		$this->assertSame('Tomorrow', Date::getRelativeDayName(Date::getTomorrow()));
		$this->assertSame('2020-01-01', Date::getRelativeDayName('2020-01-01'));
	}

	public function testGetTomorrowAndYesterdayMatchPhpDate(): void
	{
		$this->assertSame(date('Y-m-d', strtotime('+1 day')), Date::getTomorrow());
		$this->assertSame(date('Y-m-d', strtotime('-1 day')), Date::getYesterday());
	}

	public function testGetTodayAndNow(): void
	{
		$this->assertSame(date('Y-m-d'), Date::getToday());
		$this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', Date::now());
		$this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', Date::now('Y-m-d', 'UTC'));
	}

	public function testCalculateAgeFromBirthdate(): void
	{
		$age = Date::calculateAgeFromBirthdate('1990-01-01');
		$this->assertArrayHasKey('years', $age);
		$this->assertArrayHasKey('months', $age);
		$this->assertArrayHasKey('days', $age);
		$this->assertIsInt($age['years']);
		$this->assertGreaterThanOrEqual(0, $age['months']);
		$this->assertGreaterThanOrEqual(0, $age['days']);
	}

	public function testGetAge(): void
	{
		$age = Date::getAge('1990-06-15');
		$this->assertGreaterThan(30, $age);
	}

	public function testGetDateDiffInDays(): void
	{
		$this->assertSame(9, Date::getDateDiffInDays('2024-05-01', '2024-05-10'));
	}

	#[DataProvider('dateDiffUnitProvider')]
	public function testGetDateDiffUnits(string $unit, int|float $expected): void
	{
		$this->assertEquals($expected, Date::getDateDiff('2024-05-01 00:00:00', '2024-05-10 00:00:00', $unit));
	}

	public static function dateDiffUnitProvider(): array
	{
		return [
			'days' => ['days', 9],
			'weeks' => ['weeks', 1],
		];
	}

	public function testDiffForHumans(): void
	{
		$this->assertSame('4 months, 6 days', Date::diffForHumans('2024-01-01', '2024-05-07'));
	}

	public function testDiffInHoursMinutesSeconds(): void
	{
		$this->assertSame(24.0, Date::diffInHours('2024-05-01 00:00:00', '2024-05-02 00:00:00'));
		$this->assertSame(1440.0, Date::diffInMinutes('2024-05-01 00:00:00', '2024-05-02 00:00:00'));
		$this->assertSame(86400, Date::diffInSeconds('2024-05-01 00:00:00', '2024-05-02 00:00:00'));
		$this->assertSame(1, Date::diffInWeeks('2024-05-01', '2024-05-08'));
		$this->assertSame(1, Date::diffInMonths('2024-05-01', '2024-06-01'));
	}

	public function testGetRelativeTimeReturnsHumanReadableString(): void
	{
		$past = Date::getRelativeTime('2000-01-01 00:00:00');
		$this->assertMatchesRegularExpression('/ago$/', $past);

		$future = Date::getRelativeTime('2099-12-31 23:59:59');
		$this->assertMatchesRegularExpression('/^in /', $future);
	}

	public function testComparisonPredicates(): void
	{
		$this->assertTrue(Date::isBefore('2024-05-01', '2024-05-02'));
		$this->assertFalse(Date::isBefore('2024-05-02', '2024-05-01'));
		$this->assertTrue(Date::isAfter('2024-05-02', '2024-05-01'));
		$this->assertTrue(Date::isBetween('2024-05-05', '2024-05-01', '2024-05-10'));
		$this->assertFalse(Date::isBetween('2024-04-30', '2024-05-01', '2024-05-10'));
		$this->assertTrue(Date::isPast('1970-01-01'));
		$this->assertTrue(Date::isFuture('2099-12-31'));
		$this->assertTrue(Date::isSameDay('2024-05-07 08:00:00', '2024-05-07 20:00:00'));
		$this->assertFalse(Date::isSameDay('2024-05-07', '2024-05-08'));
	}

	public function testSameMonthYearWeekQuarter(): void
	{
		$this->assertTrue(Date::isSameMonth('2024-05-01', '2024-05-31'));
		$this->assertTrue(Date::isSameYear('2024-01-01', '2024-12-31'));
		$this->assertTrue(Date::isSameWeek('2024-05-06', '2024-05-07'));
		$this->assertTrue(Date::isSameQuarter('2024-05-01', '2024-05-31'));
	}

	public function testIsWeekendAndWeekday(): void
	{
		$this->assertTrue(Date::isWeekend('2024-05-11'));
		$this->assertFalse(Date::isWeekend('2024-05-06'));
		$this->assertTrue(Date::isWeekday('2024-05-07'));
		$this->assertFalse(Date::isWeekday('2024-05-11'));
	}

	public function testBusinessDays(): void
	{
		$this->assertTrue(Date::isBusinessDay('2024-05-07'));
		$this->assertFalse(Date::isBusinessDay('2024-05-11'));
		$this->assertFalse(Date::isBusinessDay('2024-05-07', ['2024-05-07']));

		$this->assertSame('2024-05-07', Date::addBusinessDays('2024-05-03', 2));
		$this->assertSame(5, Date::getBusinessDaysBetween('2024-05-06', '2024-05-10'));
		$this->assertSame('2024-05-07', Date::getSettlementDate('2024-05-03', 2));
	}

	public function testAddAndSubtract(): void
	{
		$this->assertSame('2024-05-10', Date::addDays(self::FIXTURE_DATE, 3));
		$this->assertSame('2024-06-07', Date::addMonths(self::FIXTURE_DATE, 1));
		$this->assertSame('2025-05-07', Date::addYears(self::FIXTURE_DATE, 1));
		$this->assertSame('2024-05-14', Date::addWeeks(self::FIXTURE_DATE, 1));
		$this->assertSame('2024-05-07 14:00:00', Date::addHours(self::FIXTURE_DATETIME, 2));
		$this->assertSame('2024-05-07 12:30:00', Date::addMinutes(self::FIXTURE_DATETIME, 30));
		$this->assertSame('2024-05-07 12:00:30', Date::addSeconds(self::FIXTURE_DATETIME, 30));

		$this->assertSame('2024-05-04', Date::subtractDays(self::FIXTURE_DATE, 3));
		$this->assertSame('2024-04-07', Date::subtractMonths(self::FIXTURE_DATE, 1));
		$this->assertSame('2023-05-07', Date::subtractYears(self::FIXTURE_DATE, 1));
	}

	public function testModify(): void
	{
		$this->assertSame('2024-05-14 12:00:00', Date::modify(self::FIXTURE_DATETIME, '+7 days'));
	}

	public function testMinMaxClamp(): void
	{
		$this->assertSame('2024-05-01 00:00:00', Date::min('2024-05-10', '2024-05-01', '2024-05-05'));
		$this->assertSame('2024-05-10 00:00:00', Date::max('2024-05-10', '2024-05-01', '2024-05-05'));
		$this->assertSame('2024-05-05 00:00:00', Date::clamp('2024-05-05', '2024-05-01', '2024-05-10'));
		$this->assertSame('2024-05-01 00:00:00', Date::clamp('2024-04-01', '2024-05-01', '2024-05-10'));
		$this->assertSame('2024-05-10 00:00:00', Date::clamp('2024-06-01', '2024-05-01', '2024-05-10'));
	}

	public function testOverlap(): void
	{
		$this->assertTrue(Date::overlap('2024-05-01', '2024-05-15', '2024-05-10', '2024-05-20'));
		$this->assertFalse(Date::overlap('2024-05-01', '2024-05-05', '2024-05-10', '2024-05-20'));

		$period = Date::getOverlapPeriod('2024-05-01', '2024-05-15', '2024-05-10', '2024-05-20');
		$this->assertIsArray($period);
		$this->assertSame('2024-05-10 00:00:00', $period['start']);
		$this->assertSame('2024-05-15 00:00:00', $period['end']);
		$this->assertNull(Date::getOverlapPeriod('2024-05-01', '2024-05-05', '2024-05-10', '2024-05-20'));
	}

	#[DataProvider('gregorianLeapYearProvider')]
	public function testIsLeapYearGregorian(int $year, bool $expected): void
	{
		$this->assertSame($expected, Date::isLeapYear($year));
	}

	public static function gregorianLeapYearProvider(): array
	{
		return [
			'divisible by 400' => [2000, true],
			'divisible by 4 not 100' => [2024, true],
			'motion century' => [1900, false],
			'common year' => [2023, false],
		];
	}

	public function testIsLeapYearFromDate(): void
	{
		$this->assertTrue(Date::isLeapYearFromDate('2024-02-29'));
		$this->assertFalse(Date::isLeapYearFromDate('2023-06-15'));
	}

	public function testGetDaysInYearAndMonth(): void
	{
		$this->assertSame(366, Date::getDaysInYear(2024));
		$this->assertSame(365, Date::getDaysInYear(2023));
		$this->assertSame(31, Date::getDaysInMonth('2024-05-07'));
		$this->assertSame(29, Date::getDaysInMonth('2024-02-15'));
	}

	public function testGetEasterDate(): void
	{
		$this->assertSame('2024-03-31', Date::getEasterDate(2024));
		$this->assertSame('2025-04-20', Date::getEasterDate(2025));
	}

	public function testGetNthWeekdayOfMonth(): void
	{
		$this->assertSame('2024-01-15', Date::getNthWeekdayOfMonth(2024, 1, 3, 1));
		$this->assertSame('2024-01-24', Date::getNthWeekdayOfMonth(2024, 1, -1, 3));
	}

	public function testGetNextAndPreviousWeekday(): void
	{
		$this->assertSame('2024-05-13', Date::getNextWeekday('2024-05-07', 1));
		$this->assertSame('2024-05-06', Date::getPreviousWeekday('2024-05-07', 1));
	}

	public function testGetClosestWeekday(): void
	{
		$this->assertSame('2024-05-10', Date::getClosestWeekday('2024-05-11'));
		$this->assertSame('2024-05-13', Date::getClosestWeekday('2024-05-12'));
		$this->assertSame('2024-05-07', Date::getClosestWeekday('2024-05-07'));
	}

	public function testDayOfYearAndRemaining(): void
	{
		$this->assertSame(128, Date::getDayOfYear(self::FIXTURE_DATE));
		$this->assertSame(24, Date::getRemainingDaysInMonth(self::FIXTURE_DATE));
		$this->assertSame(238, Date::getRemainingDaysInYear(self::FIXTURE_DATE));
		$this->assertSame(Date::getRemainingDaysInMonth(self::FIXTURE_DATE), Date::daysUntilEndOfMonth(self::FIXTURE_DATE));
		$this->assertSame(Date::getRemainingDaysInYear(self::FIXTURE_DATE), Date::daysUntilEndOfYear(self::FIXTURE_DATE));
	}

	public function testFiscalQuarterAndYear(): void
	{
		$this->assertSame(1, Date::getFiscalQuarter('2024-05-07', 4));
		$this->assertSame(4, Date::getFiscalQuarter('2024-02-15', 4));
		$this->assertSame(2024, Date::getFiscalYear('2024-05-07', 4));
		$this->assertSame(2023, Date::getFiscalYear('2024-02-15', 4));
	}

	public function testDateComponents(): void
	{
		$this->assertSame(2024, Date::getYearFromDate(self::FIXTURE_DATE));
		$this->assertSame(5, Date::getMonthFromDate(self::FIXTURE_DATE));
		$this->assertSame(7, Date::getDayFromDate(self::FIXTURE_DATE));
		$this->assertSame(2, Date::getDayOfWeekFromDate(self::FIXTURE_DATE));
		$this->assertSame('Tuesday', Date::getDayNameFromDate(self::FIXTURE_DATE));
		$this->assertSame('May', Date::getMonthName(self::FIXTURE_DATE));
		$this->assertSame('May', Date::getShortMonthName(self::FIXTURE_DATE));
		$this->assertSame(19, Date::getWeekNumber(self::FIXTURE_DATE));
		$this->assertSame('2024-19', Date::getISOWeek(self::FIXTURE_DATE));
	}

	public function testGetWeekOfQuarterAndWeeksInYear(): void
	{
		$this->assertGreaterThanOrEqual(1, Date::getWeekOfQuarter(self::FIXTURE_DATE));
		$this->assertContains(Date::getWeeksInYear(2024), [52, 53]);
	}

	public function testGetDayOfWeekWithTimestamp(): void
	{
		$ts = strtotime('2024-05-07 12:00:00');
		$this->assertSame('2', (string) Date::getDayOfWeek($ts));
		$this->assertSame('Tuesday', Date::getDayNameOfWeek($ts));
	}

	public function testTimestampToDateAndFromTimestamp(): void
	{
		$timestamp = strtotime(self::FIXTURE_DATETIME);
		$this->assertSame(self::FIXTURE_DATETIME, Date::timestampToDate($timestamp));
		$this->assertSame(self::FIXTURE_DATETIME, Date::fromTimestamp($timestamp));
	}

	public function testToUTC(): void
	{
		$this->assertSame('1970-01-01 00:00:00', Date::toUTC(0));
		$this->assertSame('1970-01-01', Date::toUTC(0, 'Y-m-d'));
	}

	public function testIsToday(): void
	{
		$this->assertTrue(Date::isToday(new DateTime('today')));
		$this->assertFalse(Date::isToday(new DateTime('2000-01-01')));
	}

	public function testToUnixTimestampAndFormat(): void
	{
		$ts = Date::toUnixTimestamp(self::FIXTURE_DATETIME);
		$this->assertSame(strtotime(self::FIXTURE_DATETIME), $ts);
		$this->assertSame('07', Date::format(self::FIXTURE_DATE, 'd'));
	}

	public function testSerializationFormats(): void
	{
		$this->assertStringContainsString('2024', Date::toISO8601(self::FIXTURE_DATETIME));
		$this->assertStringContainsString('2024', Date::toRFC2822(self::FIXTURE_DATETIME));
		$this->assertStringContainsString('2024', Date::toRFC3339(self::FIXTURE_DATETIME));
		$this->assertStringContainsString('2024', Date::toW3C(self::FIXTURE_DATETIME));
	}

	#[DataProvider('validDateProvider')]
	public function testIsValidDate(string $date, bool $expected): void
	{
		$this->assertSame($expected, Date::isValidDate($date));
	}

	public static function validDateProvider(): array
	{
		return [
			'valid' => ['2024-05-07', true],
			'invalid month' => ['2024-13-01', false],
			'invalid day' => ['2024-02-30', false],
			'garbage' => ['not-a-date', false],
		];
	}

	public function testCreateFromFormat(): void
	{
		$dt = Date::createFromFormat('Y-m-d', self::FIXTURE_DATE);
		$this->assertInstanceOf(DateTime::class, $dt);
		$this->assertSame(self::FIXTURE_DATE, $dt->format('Y-m-d'));
		$this->assertFalse(Date::createFromFormat('Y-m-d', 'invalid'));
	}

	public function testTimezoneHelpers(): void
	{
		$original = Date::getDefaultTimezone();
		$this->assertTrue(Date::setDefaultTimezone('UTC'));
		$this->assertSame('UTC', Date::getDefaultTimezone());
		Date::setDefaultTimezone($original);

		$this->assertIsInt(Date::getTimezoneOffset('UTC'));
		$this->assertContains('UTC', Date::getTimezoneList());
		$this->assertSame(0, Date::getOffsetFromTimezoneName(TimeZone::UTC));
	}

	public function testConvertTimezone(): void
	{
		$result = Date::convertTimezone(
			'2024-05-07 12:00:00',
			'UTC',
			'America/New_York',
			'Y-m-d H:i:s'
		);
		$this->assertMatchesRegularExpression('/^2024-05-07 \d{2}:\d{2}:\d{2}$/', $result);
	}

	public function testGetTimezoneAbbreviation(): void
	{
		$abbr = Date::getTimezoneAbbreviation('UTC', '2024-05-07');
		$this->assertNotEmpty($abbr);
	}

	public function testGetTimeAndStringToTime(): void
	{
		$this->assertIsInt(Date::getTime());
		$base = strtotime('2024-01-01');
		$this->assertSame(strtotime('+1 day', $base), Date::stringToTime('+1 day', $base));
	}

	public function testGetDateArray(): void
	{
		$ts = strtotime(self::FIXTURE_DATETIME);
		$parts = Date::getDateArray($ts);
		$this->assertSame(2024, $parts['year']);
		$this->assertSame(5, $parts['mon']);
		$this->assertSame(7, $parts['mday']);
	}

	public function testTimeToString(): void
	{
		$ts = strtotime('2024-05-07 12:00:00');
		$this->assertSame('2024', Date::timeToString('Y', $ts));
	}

	public function testParseStringSkipsWhenUnavailable(): void
	{
		if (!function_exists('strptime')) {
			$this->markTestSkipped('strptime() is not available on this platform');
		}

		$parsed = Date::parseString('2024-05-07', '%Y-%m-%d');
		$this->assertIsArray($parsed);
	}

	public function testTimeToSecondsAndSecondsToTime(): void
	{
		$this->assertSame(3661, Date::timeToSeconds('01:01:01'));
		$this->assertSame('01:01:01', Date::secondsToTime(3661));
		$this->assertSame('-01:01:01', Date::secondsToTime(-3661));
	}

	public function testGetQuarter(): void
	{
		$quarter = Date::getQuarter();
		$this->assertGreaterThanOrEqual(1, $quarter);
		$this->assertLessThanOrEqual(4, $quarter);
	}

	#[DataProvider('moonPhaseNameProvider')]
	public function testGetMoonPhaseName(string $date, string $expectedPhase): void
	{
		$this->assertSame($expectedPhase, Date::getMoonPhaseName($date));
	}

	public static function moonPhaseNameProvider(): array
	{
		return [
			'20210805' => ['20210805', 'Waning Crescent'],
			'20200702' => ['20200702', 'Waxing Gibbous'],
			'19920507' => ['19920507', 'Waxing Crescent'],
		];
	}

	public function testMoonCalculations(): void
	{
		$this->assertEqualsWithDelta(13.2996, Date::getMoonAge('20260501'), 0.0001);
		$this->assertEqualsWithDelta(29.530588853, (float) Date::getSynodicPeriod('MOON'), 0.0001);

		$illumination = Date::getMoonIllumination(self::FIXTURE_DATE);
		$this->assertGreaterThanOrEqual(0.0, $illumination);
		$this->assertLessThanOrEqual(100.0, $illumination);

		$this->assertIsBool(Date::isMoonWaxing(self::FIXTURE_DATE));
		$this->assertIsBool(Date::isMoonWaning(self::FIXTURE_DATE));
		$this->assertNotSame(Date::isMoonWaxing(self::FIXTURE_DATE), Date::isMoonWaning(self::FIXTURE_DATE));
	}

	public function testGetNextNewMoon(): void
	{
		$next = Date::getNextNewMoon('2024-05-01');
		$this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $next);
		$this->assertTrue(Date::isAfter($next, '2024-04-30') || Date::isSameDay($next, '2024-05-01'));
	}

	public function testLunarCalendar(): void
	{
		$this->assertSame('2024-02-10', Date::getLunarNewYearDate(2024));
		$this->assertSame(2, Date::getLunarLeapMonth(2023));
		$this->assertTrue(Date::isLunarLeapYear(2023));
		$this->assertFalse(Date::isLunarLeapYear(2024));
		$this->assertSame(13, Date::getLunarMonthCount(2023));
		$this->assertSame(12, Date::getLunarMonthCount(2024));
		$this->assertContains(Date::getLunarMonthDays(2024, 1), [29, 30]);
		$this->assertNotEmpty(Date::getLunarYearLabel(2024));
	}

	public function testGetLunarNewYearDateThrowsOutsideRange(): void
	{
		$this->expectException(InvalidArgumentException::class);
		Date::getLunarNewYearDate(1800);
	}

	public function testDatetimeExtensionHelpers(): void
	{
		$this->assertSame(12, Date::getHourFromDate(self::FIXTURE_DATETIME));
		$this->assertSame(0, Date::getMinuteFromDate(self::FIXTURE_DATETIME));
		$this->assertSame('2024-05-07', Date::extractDatePart(self::FIXTURE_DATETIME));
		$this->assertSame('12:00:00', Date::extractTimePart(self::FIXTURE_DATETIME));
		$this->assertSame(
			self::FIXTURE_DATETIME,
			Date::combineDateAndTime('2024-05-07', '12:00:00')
		);

		$this->assertSame('2024-05-07 09:00:00', Date::subtractHours(self::FIXTURE_DATETIME, 3));
		$this->assertSame('2024-04-23', Date::subtractWeeks(self::FIXTURE_DATE, 2));
		$this->assertSame('2024-05-07 12:00:00', Date::getStartOfHour(self::FIXTURE_DATETIME));
		$this->assertSame('2024-05-07 12:59:59', Date::getEndOfHour(self::FIXTURE_DATETIME));

		$this->assertSame(-1, Date::compareDates('2024-05-01', self::FIXTURE_DATE));
		$this->assertSame(0, Date::compareDates(self::FIXTURE_DATETIME, self::FIXTURE_DATETIME));
		$this->assertTrue(Date::isTodayString(Date::getToday()));
		$this->assertTrue(Date::isYesterdayString(Date::getYesterday()));
		$this->assertTrue(Date::isMidnight('2024-05-07 00:00:00'));
		$this->assertTrue(Date::isNoon('2024-05-07 12:00:00'));

		$this->assertSame(86400, Date::diffInSecondsSigned('2024-05-08 12:00:00', self::FIXTURE_DATETIME));
		$this->assertSame(-1, Date::diffInDaysSigned('2024-05-08', self::FIXTURE_DATE));
		$this->assertSame(1, Date::diffInDaysSigned(self::FIXTURE_DATE, '2024-05-08'));

		$floored = Date::floorDateTimeToMinutes('2024-05-07 12:07:30', 15);
		$this->assertSame('2024-05-07 12:00:00', $floored);
		$ceiled = Date::ceilDateTimeToMinutes('2024-05-07 12:07:30', 15);
		$this->assertSame('2024-05-07 12:15:00', $ceiled);

		$this->assertMatchesRegularExpression('/^2024-05-07T/', Date::toAtom(self::FIXTURE_DATETIME));
		$this->assertNotEmpty(Date::toHttpDate(self::FIXTURE_DATETIME));
		$this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', Date::utcNow());
		$this->assertSame('afternoon', Date::getTimeOfDayPeriod(self::FIXTURE_DATETIME));
		$this->assertSame(1, Date::getSemester('2024-05-07'));
		$this->assertSame(3, Date::getBimester('2024-05-07'));
		$this->assertSame(2024, Date::getIsoWeekYear(self::FIXTURE_DATE)['year']);

		$this->assertSame('2024-12-25', Date::getNextAnnualOccurrence('12-25', '2024-05-07'));
		$this->assertSame('2024-01-01', Date::getPreviousAnnualOccurrence('01-01', '2024-05-07'));

		$weekends = Date::getWeekendsBetween('2024-05-04', '2024-05-12');
		$this->assertContains('2024-05-04', $weekends);
		$this->assertGreaterThan(0, Date::countWeekendDaysBetween('2024-05-01', '2024-05-31'));

		$sorted = Date::sortDates(['2024-05-10', '2024-05-01', '2024-05-07']);
		$this->assertSame(['2024-05-01', '2024-05-07', '2024-05-10'], $sorted);
		$this->assertSame('2024-05-07', Date::medianDate(['2024-05-01', '2024-05-07', '2024-05-31']));

		$this->assertStringContainsString('day', Date::formatDurationBetween(self::FIXTURE_DATETIME, '2024-05-09 15:30:00'));
		$this->assertSame('PT1H2M3S', Date::formatIso8601Duration(3723));
		$this->assertMatchesRegularExpression('/\.\d{6}$/', Date::nowWithMicroseconds());
	}

	public function testLegacyDateSmoke(): void
	{
		$julian = Date::toJulian(2024, 5, 7);
		$this->assertEqualsWithDelta(2460437.5, (float) $julian, 0.0001);

		$datetime = Date::julianToGregorian($julian);
		$this->assertSame('2024-05-07', $datetime->format('Y-m-d'));

		$datesBetween = Date::getDatesBetween('2024-05-01', '2024-05-07');
		$this->assertCount(7, $datesBetween);

		$yearsBetween = Date::getYearsBetween('2000-01-01', '2024-05-07');
		$this->assertCount(25, $yearsBetween);
	}
}
