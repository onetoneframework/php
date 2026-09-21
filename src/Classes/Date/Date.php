<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Date;

use Clover\Enumeration\DateTime\DateStringFormat;
use Clover\Enumeration\TimeZone;
use DateTime;
use DateTimeZone;
use DateTimeInterface;
use DateTimeImmutable;
use DateInterval;
use RuntimeException;
use InvalidArgumentException;
use Clover\Enumeration\DateTime\Date as DateEnum;
use function in_array;
use function sprintf;
use function array_slice;
use function count;

/**
 * Date Class
 *
 * Provides a comprehensive set of static utility methods for date/time
 * operations including arithmetic, comparison, formatting, timezone
 * conversion, business-day calculations, Julian/Gregorian conversions,
 * and human-readable representations.
 * 
 * @method static array calculateAgeFromBirthdate(string $birthdate)
 * @method static float getJulianCenturies(float $JD)
 * @method static string getTomorrow()
 * @method static string getYesterday()
 * @method static int|string getLastDayOfMonth(int $year, int $month)
 * @method static float getQuarter()
 * @method static string timestampToDate(int $timestamp)
 * @method static bool isWeekend(string $date)
 * @method static array getDatesBetween(string $start, string $end)
 * @method static mixed getDateDiffInDays(string $date1, string $date2)
 * @method static DateTime julianToGregorian(float $jd)
 * @method static float toJulian(int $year, int $month, float $day)
 * @method static string toUTC(int $seconds, string $format = 'Y-m-d H:i:s')
 * @method static bool isToday(DateTime $datetime)
 * @method static bool|int isLeapYear(int $year, bool $julian = false)
 * @method static bool setDefaultTimezone(string $timezoneId)
 * @method static string getDefaultTimezone()
 * @method static bool|int stringToTime(string $string, int $format)
 * @method static array getDateArray(int $date)
 * @method static array|bool parseString(string $date, string $format)
 * @method static bool|string timeToString(string $date, int|null $timestamp = null)
 * @method static int getTime()
 * @method static string getStartOfWeek(string $date)
 * @method static string getQuarterStart(string $date)
 * @method static string getQuarterEnd(string $date)
 * @method static string getEndOfWeek(string $date)
 * @method static int|string getDayOfWeek(?int $timestamp = null)
 * @method static int|string getDayNameOfWeek(?int $timestamp = null)
 * @method static string getISOWeek(string $date)
 * @method static float|int getWeekOfQuarter(string $date)
 * @method static array getYearsBetween(string $start, string $end)
 * @method static int getTimezoneOffset(string $timezone, ?int $timestamp = null)
 * @method static array getTimezoneList()
 * @method static int getOffsetFromTimezoneName(string $name)
 * @method static string getRelativeDayName(string $date)
 * @method static int|float getDateDiff(string $date1, string $date2, string $unit = 'days')
 * @method static string modify(string $date, string $modifier, string $format = 'Y-m-d H:i:s')
 * @method static bool isBefore(string $date1, string $date2)
 * @method static bool isAfter(string $date1, string $date2)
 * @method static bool isBetween(string $date, string $start, string $end)
 * @method static bool isPast(string $date)
 * @method static bool isFuture(string $date)
 * @method static bool isSameDay(string $date1, string $date2)
 * @method static bool isBusinessDay(string $date, array $holidays = [])
 * @method static string addBusinessDays(string $date, int $days, array $holidays = [])
 * @method static int getBusinessDaysBetween(string $start, string $end, array $holidays = [])
 * @method static string convertTimezone(string $date, string $fromTz, string $toTz, string $format = 'Y-m-d H:i:s')
 * @method static bool isValidDate(string $date, string $format = 'Y-m-d')
 * @method static DateTime|false createFromFormat(string $format, string $date, ?string $timezone = null)
 * @method static string toISO8601(string $date, ?string $timezone = null)
 * @method static string toRFC2822(string $date, ?string $timezone = null)
 * @method static string toRFC3339(string $date, ?string $timezone = null)
 * @method static int toUnixTimestamp(string $date)
 * @method static string format(string $date, string $format, ?string $timezone = null)
 * @method static string getRelativeTime(string $date)
 * @method static string getStartOfMonth(string $date)
 * @method static string getEndOfMonth(string $date)
 * @method static string getStartOfYear(string $date)
 * @method static string getEndOfYear(string $date)
 * @method static string getStartOfDay(string $date)
 * @method static string getEndOfDay(string $date)
 * @method static string getNthWeekdayOfMonth(int $year, int $month, int $nth, int $weekday)
 * @method static int getWeeksInYear(int $year)
 * @method static int getDaysInYear(int $year)
 * @method static string getEasterDate(int $year)
 * @method static array getMonthsBetween(string $start, string $end)
 * @method static array getWeeksBetween(string $start, string $end)
 * @method static int getFiscalQuarter(string $date, int $fiscalYearStartMonth = 4)
 * @method static int getFiscalYear(string $date, int $fiscalYearStartMonth = 4)
 * @method static string getSettlementDate(string $tradeDate, int $settlementDays = 2, array $holidays = [])
 * @method static array getRecurringDates(string $start, string $end, string $interval)
 * @method static int getDayOfYear(string $date)
 * @method static int getRemainingDaysInYear(string $date)
 * @method static int getRemainingDaysInMonth(string $date)
 * @method static string getToday(string $format = 'Y-m-d')
 * @method static string now(string $format = 'Y-m-d H:i:s', ?string $timezone = null)
 * @method static string clamp(string $date, string $min, string $max)
 * @method static string min(string ...$dates)
 * @method static string max(string ...$dates)
 * @method static bool overlap(string $start1, string $end1, string $start2, string $end2)
 * @method static ?array getOverlapPeriod(string $start1, string $end1, string $start2, string $end2)
 * @method static bool isWeekday(string $date)
 * @method static int getQuarterOfDate(string $date)
 * @method static string diffForHumans(string $date1, string $date2)
 * @method static string addDays(string $date, int $days, string $format = 'Y-m-d')
 * @method static string addMonths(string $date, int $months, string $format = 'Y-m-d')
 * @method static string addYears(string $date, int $years, string $format = 'Y-m-d')
 * @method static string addWeeks(string $date, int $weeks, string $format = 'Y-m-d')
 * @method static string addHours(string $date, int $hours, string $format = 'Y-m-d H:i:s')
 * @method static string addMinutes(string $date, int $minutes, string $format = 'Y-m-d H:i:s')
 * @method static string addSeconds(string $date, int $seconds, string $format = 'Y-m-d H:i:s')
 * @method static string subtractDays(string $date, int $days, string $format = 'Y-m-d')
 * @method static string subtractMonths(string $date, int $months, string $format = 'Y-m-d')
 * @method static string subtractYears(string $date, int $years, string $format = 'Y-m-d')
 * @method static string getMonthName(string $date)
 * @method static string getShortMonthName(string $date)
 * @method static int getDaysInMonth(string $date)
 * @method static int getWeekNumber(string $date)
 * @method static int getDayOfWeekFromDate(string $date)
 * @method static string getDayNameFromDate(string $date)
 * @method static int getYearFromDate(string $date)
 * @method static int getMonthFromDate(string $date)
 * @method static int getDayFromDate(string $date)
 * @method static bool isLeapYearFromDate(string $date)
 * @method static int getAge(string $birthdate)
 * @method static string getTimezoneAbbreviation(string $timezone, ?string $date = null)
 * @method static string fromTimestamp(int $timestamp, string $format = 'Y-m-d H:i:s')
 * @method static bool isSameMonth(string $date1, string $date2)
 * @method static bool isSameYear(string $date1, string $date2)
 * @method static bool isSameWeek(string $date1, string $date2)
 * @method static bool isSameQuarter(string $date1, string $date2)
 * @method static float diffInHours(string $date1, string $date2)
 * @method static float diffInMinutes(string $date1, string $date2)
 * @method static int diffInSeconds(string $date1, string $date2)
 * @method static int diffInWeeks(string $date1, string $date2)
 * @method static int diffInMonths(string $date1, string $date2)
 * @method static string getNextWeekday(string $date, int $weekday)
 * @method static string getPreviousWeekday(string $date, int $weekday)
 * @method static string getClosestWeekday(string $date)
 * @method static array getQuartersBetween(string $start, string $end)
 * @method static array getDateRange(string $start, string $end, string $interval = 'P1D', string $format = 'Y-m-d')
 * @method static int timeToSeconds(string $time)
 * @method static string secondsToTime(int $seconds)
 * @method static int daysUntilEndOfMonth(string $date)
 * @method static int daysUntilEndOfYear(string $date)
 * @method static string toW3C(string $date, ?string $timezone = null)
 * @method static string toCookieFormat(string $date)
 * @method static string toRSSFormat(string $date)
 * @method static int datetimeToFileTime(DateTimeInterface $dt)
 * @method static int toUnixMilliseconds(string $date)
 * @method static string fromUnixMilliseconds(int $milliseconds, string $format = 'Y-m-d H:i:s')
 * @method static bool isSameHour(string $date1, string $date2)
 * @method static bool isSameMinute(string $date1, string $date2)
 * @method static array getWeekdaysBetween(string $start, string $end)
 * @method static string getNextBusinessDay(string $date, array $holidays = [])
 * @method static string getPreviousBusinessDay(string $date, array $holidays = [])
 * @method static int getBusinessDaysCount(string $start, string $end, array $holidays = [], bool $includeBoundaries = false)
 * @method static float getBusinessWeeksBetween(string $start, string $end, array $holidays = [])
 * @method static string addBusinessWeeks(string $date, int $weeks, array $holidays = [])
 * @method static string getEndOfFiscalYear(string $date, int $fiscalYearStartMonth = 4)
 * @method static bool isWeekendInRange(string $start, string $end)
 * @method static string secondsToHumanReadable(int $seconds)
 * @method static float getAgeDecimal(string $birthdate)
 * @method static int getJulianDayNumber(string $date)
 * @method static DateTimeImmutable parseTarget(string $raw)
 * @method static bool isInDST(string $date, ?string $timezone = null)
 * @method static array getDaylightSavingsTransitions(int $year, string $timezone)
 * @method static string getDateFromDayOfYear(int $year, int $dayOfYear)
 * @method static string getDateFromIsoWeek(int $year, int $week, int $weekday = 1)
 * @method static array getIsoWeekYear(string $date)
 * @method static int getAgeInMonths(string $birthdate)
 * @method static string getFirstBusinessDayOfMonth(string $monthYear, array $holidays = [])
 * @method static string getLastBusinessDayOfMonth(string $monthYear, array $holidays = [])
 * @method static string getFiscalQuarterLabel(string $date, int $fiscalYearStartMonth = 4)
 * @method static float diffInYears(string $start, string $end)
 * @method static string normalizeDate(string $date)
 * @method static int daysUntilNextAnniversary(string $date)
 * @method static string dayWithSuffix(int $day)
 * @method static string getLocalizedMonthName(string $date, string $locale = 'en_US')
 * @method static array generateBusinessDates(string $start, string $end, array $holidays = [])
 * @method static string getDateFromIsoWeekNumber(int $year, int $week)
 * @method static string getWeekParity(string $date)
 * @method static string roundDateTimeToMinutes(string $date, int $minutes)
 * @method static int getBusinessDaysCountInclusive(string $start, string $end, array $holidays = [])
 * @method static bool isLastBusinessDayOfMonth(string $date, array $holidays = [])
 * @method static bool isFirstBusinessDayOfMonth(string $date, array $holidays = [])
 * @method static int secondsUntilEndOfDay(string $date)
 * @method static int secondsSinceStartOfDay(string $date)
 * @method static float getAgeFloat(string $birthdate)
 * @method static string getDateFromIsoWeekAndWeekday(int $year, int $week, int $weekday)
 * @method static array getDateRangeWithInterval(string $start, string $end, int $intervalDays = 1)
 * @method static string formatContextAware(string $date, string $format = 'Y-m-d')
 * @method static float getCurrentTime()
 * @method static float getMoonPhaseJD(float $yearFraction, int $phase)
 * @method static float getMoonAge(string $date)
 * @method static string getMoonPhaseName(string $date)
 * @method static float getMoonIllumination(string $date)
 * @method static bool isMoonWaxing(string $date)
 * @method static bool isMoonWaning(string $date)
 * @method static string getNextNewMoon(string $date)
 * @method static string getNextFullMoon(string $date)
 * @method static array solarToLunar(string $date)
 * @method static string lunarToSolar(int $lunarYear, int $lunarMonth, int $lunarDay, bool $isLeapMonth = false)
 * @method static string getLunarNewYearDate(int $year)
 * @method static int getLunarLeapMonth(int $lunarYear)
 * @method static bool isLunarLeapYear(int $lunarYear)
 * @method static int getLunarMonthCount(int $lunarYear)
 * @method static int getLunarMonthDays(int $lunarYear, int $lunarMonth, bool $isLeap = false)
 * @method static string getChineseZodiac(int $year)
 * @method static string getKoreanZodiac(int $year)
 * @method static string getHeavenlyStem(int $year)
 * @method static string getEarthlyBranch(int $year)
 * @method static string getStemBranch(int $year)
 * @method static string getLunarYearLabel(int $year)
 * @method static int getCountdownDays(string $targetDate)
 * @method static string getMidpointDate(string $date1, string $date2)
 * @method static float getDateProgressPercentage(string $start, string $end, string $current = 'now')
 * @method static array splitDateRangeIntoChunks(string $start, string $end, int $chunkDays = 7)
 * @method static string convertDateFormat(string $date, string $fromFormat, string $toFormat)
 * @method static array generateDateSequence(string $start, int $count, string $interval = '+1 day', string $format = 'Y-m-d')
 * @method static bool isFirstDayOfMonth(string $date)
 * @method static bool isLastDayOfMonth(string $date)
 * @method static int getCalendarWeeksInMonth(int $year, int $month)
 * @method static int getCentury(int $year)
 * @method static int getDecade(int $year)
 * @method static int getMillennium(int $year)
 * @method static bool doPeriodsShareADay(string $start1, string $end1, string $start2, string $end2)
 * @method static int getDayGapBetweenPeriods(string $end1, string $start2)
 * @method static array getWeekdayDatesInMonth(int $year, int $month, int $weekday)
 * @method static bool isValidCalendarDate(string $date, string $format = 'Y-m-d')
 * @method static string formatLocalized(string $date, string $locale = 'en_US', string $style = 'medium')
 * @method static int getAgeInCompletedMonths(string $birthdate, string $reference = 'today')
 * @method static string snapToNearestMinuteInterval(string $date, int $minutes)
 * @method static int diffInCompleteYears(string $date1, string $date2)
 * @method static bool isInAnnualWindow(string $date, string $mmddStart, string $mmddEnd)
 * @method static int getHourFromDate(string $date)
 * @method static int getMinuteFromDate(string $date)
 * @method static int getSecondFromDate(string $date)
 * @method static string extractDatePart(string $date)
 * @method static string extractTimePart(string $date)
 * @method static string combineDateAndTime(string $date, string $time, string $format = 'Y-m-d H:i:s')
 * @method static string setTimeOnDate(string $date, string $time, string $format = 'Y-m-d H:i:s')
 * @method static string subtractHours(string $date, int $hours, string $format = 'Y-m-d H:i:s')
 * @method static string subtractMinutes(string $date, int $minutes, string $format = 'Y-m-d H:i:s')
 * @method static string subtractSeconds(string $date, int $seconds, string $format = 'Y-m-d H:i:s')
 * @method static string subtractWeeks(string $date, int $weeks, string $format = 'Y-m-d')
 * @method static string getStartOfHour(string $date)
 * @method static string getEndOfHour(string $date)
 * @method static string getStartOfMinute(string $date)
 * @method static string getEndOfMinute(string $date)
 * @method static string truncateToDate(string $date, string $format = 'Y-m-d H:i:s')
 * @method static string truncateToHour(string $date, string $format = 'Y-m-d H:i:s')
 * @method static int compareDates(string $date1, string $date2)
 * @method static bool isEqual(string $date1, string $date2)
 * @method static bool isTodayString(string $date)
 * @method static bool isYesterdayString(string $date)
 * @method static bool isTomorrowString(string $date)
 * @method static bool isSameSecond(string $date1, string $date2)
 * @method static bool isMidnight(string $date)
 * @method static bool isNoon(string $date)
 * @method static bool isValidDateTime(string $date, string $format = 'Y-m-d H:i:s')
 * @method static int diffInSecondsSigned(string $date1, string $date2)
 * @method static float diffInHoursSigned(string $date1, string $date2)
 * @method static int diffInDaysSigned(string $date1, string $date2)
 * @method static string floorDateTimeToMinutes(string $date, int $minutes)
 * @method static string ceilDateTimeToMinutes(string $date, int $minutes)
 * @method static string toAtom(string $date, ?string $timezone = null)
 * @method static string toHttpDate(string $date)
 * @method static string utcNow(string $format = 'Y-m-d H:i:s')
 * @method static string getTimezoneOffsetString(string $date, ?string $timezone = null)
 * @method static string getTimeOfDayPeriod(string $date)
 * @method static int getSemester(string $date, int $academicYearStart = 3)
 * @method static int getBimester(string $date)
 * @method static string fromNow(string $modifier, string $format = 'Y-m-d H:i:s')
 * @method static int daysFromToday(string $targetDate)
 * @method static float hoursFromNow(string $targetDatetime)
 * @method static bool isWithinPastMinutes(string $date, int $minutes)
 * @method static bool isWithinNextHours(string $date, int $hours)
 * @method static string getNextAnnualOccurrence(string $mmdd, string $fromDate = 'today')
 * @method static string getPreviousAnnualOccurrence(string $mmdd, string $fromDate = 'today')
 * @method static array getWeekendsBetween(string $start, string $end)
 * @method static int countWeekendDaysBetween(string $start, string $end)
 * @method static array sortDates(array $dates)
 * @method static string medianDate(array $dates)
 * @method static string formatDurationBetween(string $start, string $end)
 * @method static string formatIso8601Duration(int $seconds)
 * @method static string nowWithMicroseconds(string $format = 'Y-m-d H:i:s.u')
 * @method static void solarCoordinates(float $T)
 * @method static void computeSiderealTime(float $jd)
 * @method static void convertEclipticLongitudeL(float $JD, float $L)
 * @method static void getSynodicPeriod(string $planet)
 * @method static string signedDiff(string $date1, string $date2, string $unit = 'days')
 * @method static array getSunriseSunset(string $date, float $latitude, float $longitude, string $timezone = 'UTC')
 * @method static array getDateRangeGaps(array $ranges, string $windowStart, string $windowEnd)
 * @method static array getQuarterDateRange(string $date)
 * @method static array getRecurringWeekdayDates(string $start, string $end, array $weekdays)
 * @method static DateTimeImmutable nowImmutable(?string $timezone = null)
 * @method static DateTimeImmutable toDateTimeImmutable(string $date, ?string $timezone = null)
 * @method static string averageDate(array $dates, string $format = 'Y-m-d H:i:s')
 * @method static string secondsToHHMM(int $seconds)
 * @method static array getCalendarGrid(int $year, int $month)
 * @method static float diffInMinutesSigned(string $date1, string $date2)
 * @method static string getNextAnnualDate(string $mmdd, string $fromDate = 'today')
 * @method static string getPreviousAnnualDate(string $mmdd, string $fromDate = 'today')
 * @method static string getNextNthWeekdayOccurrence(string $fromDate, int $weekday, int $nth = 1)
 * @method static string toReadableDate(string $date, string $format = 'F j, Y')
 * @method static string toOrdinalDate(string $date)
 * @method static int getOverlappingDays(string $start1, string $end1, string $start2, string $end2)
 * @method static float getMicroTimestamp()
 * @method static int getDaysSinceEpoch(string $date)
 * @method static string fromDaysSinceEpoch(int $days, string $format = 'Y-m-d')
 * @method static bool isValidDateRange(string $start, string $end)
 * @method static array normalizeDateRange(string $date1, string $date2)
 * @method static string setDateComponents(string $date, ?int $year = null, ?int $month = null, ?int $day = null, string $format = 'Y-m-d')
 * @method static string setTimeComponents(string $datetime, ?int $hour = null, ?int $minute = null, ?int $second = null, string $format = 'Y-m-d H:i:s')
 * @method static bool isInTimeRange(string $datetime, string $startTime, string $endTime)
 * @method static bool isDaytime(string $datetime, int $startHour = 6, int $endHour = 20)
 * @method static bool isNighttime(string $datetime, int $startHour = 20, int $endHour = 6)
 * @method static string getAgeCategory(string $birthdate)
 * @method static int getWeekOfMonth(string $date)
 * @method static array getFullDiffArray(string $date1, string $date2)
 * @method static float getTotalHoursInDay(string $date, string $timezone = 'UTC')
 * @method static float toExcelSerialDate(string $date)
 * @method static string fromExcelSerialDate(float $serial, string $format = 'Y-m-d')
 * @method static float getWeekProgress(string $date)
 * @method static float getMonthProgress(string $date)
 * @method static float getYearProgress(string $date)
 * @method static array getHolidaysInRange(string $start, string $end, array $holidays)
 * @method static int countHolidaysInRange(string $start, string $end, array $holidays)
 * @method static bool isPublicHoliday(string $date, array $holidays)
 * @method static string formatDateRangeLabel(string $start, string $end, string $format = 'M j')
 * @method static string getStartOfFiscalYear(string $date, int $fiscalYearStartMonth = 4)
 * @method static int countWeekdayOccurrences(string $start, string $end, int $weekday)
 * @method static bool isValidTimezone(string $timezone)
 * @method static float getTimezonesDiffInHours(string $timezone1, string $timezone2, ?string $date = null)
 * @method static string getTimeAgoShort(string $date)
 * @method static array mergeDateRanges(array $ranges)
 * @method static string getEarliestDate(array $dates)
 * @method static string getLatestDate(array $dates)
 * @method static bool isExpired(string $date)
 * @method static bool isUpcoming(string $date, int $withinDays = 7)
 * @method static float getDecimalHour(string $datetime)
 * @method static string fromDecimalHour(float $decimalHour, string $format = 'H:i:s')
 * @method static array getCountdownArray(string $targetDatetime)
 * @method static string addISO8601Duration(string $date, string $duration, string $format = 'Y-m-d H:i:s')
 * @method static string subtractISO8601Duration(string $date, string $duration, string $format = 'Y-m-d H:i:s')
 * @method static int parseHumanDuration(string $duration)
 * @method static string formatDurationSeconds(int $seconds, bool $showSeconds = true)
 * @method static array generateTimeSlots(string $date, int $intervalMinutes = 30, int $startHour = 0, int $endHour = 24, string $format = 'H:i')
 * @method static bool isWorkingHour(string $datetime, int $startHour = 9, int $endHour = 18)
 * @method static float getWorkingHoursBetween(string $start, string $end, int $workStart = 9, int $workEnd = 18, array $holidays = [])
 * @method static string getSeasonName(string $date, string $hemisphere = 'north')
 * @method static bool isMonday(string $date)
 * @method static bool isTuesday(string $date)
 * @method static bool isWednesday(string $date)
 * @method static bool isThursday(string $date)
 * @method static bool isFriday(string $date)
 * @method static bool isSaturday(string $date)
 * @method static bool isSunday(string $date)
 * @method static bool isJanuary(string $date)
 * @method static bool isFebruary(string $date)
 * @method static bool isMarch(string $date)
 * @method static bool isApril(string $date)
 * @method static bool isMay(string $date)
 * @method static bool isJune(string $date)
 * @method static bool isJuly(string $date)
 * @method static bool isAugust(string $date)
 * @method static bool isSeptember(string $date)
 * @method static bool isOctober(string $date)
 * @method static bool isNovember(string $date)
 * @method static bool isDecember(string $date)
 * @method static bool isCurrentYear(string $date)
 * @method static bool isCurrentMonth(string $date)
 * @method static bool isCurrentWeek(string $date)
 * @method static bool isCurrentQuarter(string $date)
 * @method static bool isCurrentDay(string $date)
 * @method static bool isCurrentHour(string $datetime)
 * @method static bool isCurrentMinute(string $datetime)
 * @method static string getWesternZodiacSign(string $date)
 * @method static string getJapaneseEra(string $date)
 * @method static string getBirthstone(int $month)
 * @method static string toISOWeekDate(string $date)
 * @method static string toCompactDate(string $date)
 * @method static string fromCompactDate(string $compact, string $format = 'Y-m-d')
 * @method static string toSwatchInternetTime(string $datetime, ?string $timezone = null)
 * @method static string toMilitaryTime(string $datetime)
 * @method static string getStartOfWeekSunday(string $date)
 * @method static string getEndOfWeekSaturday(string $date)
 * @method static string getAmPm(string $datetime)
 * @method static bool isMorning(string $datetime)
 * @method static bool isAfternoon(string $datetime)
 * @method static bool isEvening(string $datetime)
 * @method static int getMinuteOfDay(string $datetime)
 * @method static int getSecondOfDay(string $datetime)
 * @method static float toDecimalYear(string $date)
 * @method static int getISODayOfWeek(string $date)
 * @method static string getQuarterName(string $date)
 * @method static int getHalfYear(string $date)
 * @method static bool isStartOfQuarter(string $date)
 * @method static bool isEndOfQuarter(string $date)
 * @method static bool isPalindromicDate(string $date, string $format = 'mdY')
 * @method static bool isPeakHour(string $datetime, int $morningStart = 7, int $morningEnd = 9, int $eveningStart = 17, int $eveningEnd = 19)
 * @method static bool isLunchHour(string $datetime, int $startHour = 12, int $endHour = 13)
 * @method static array getTimezonesByCountry(string $countryCode)
 * @method static array getTimezonesByContinent(string $continent)
 * @method static bool isNthBusinessDayOfMonth(string $date, int $nth, array $holidays = [])
 * @method static ?string getNearestHoliday(string $date, array $holidays)
 * @method static bool isNearHoliday(string $date, array $holidays, int $withinDays = 3)
 * @method static int getAcademicYear(string $date, int $startMonth = 9)
 * @method static string roundToNearestDay(string $datetime)
 * @method static string roundToNearestHour(string $datetime)
 * @method static string toMySQLDatetime(string $date)
 * @method static string toPostgresTimestamp(string $date)
 * @method static int toUnixMicroseconds(string $date)
 * @method static string fromUnixMicroseconds(int $microseconds, string $format = 'Y-m-d H:i:s.u')
 * @method static string toBase36Timestamp(string $date)
 * @method static string fromBase36Timestamp(string $base36, string $format = 'Y-m-d H:i:s')
 * @method static array getWeekdayCountInMonth(int $year, int $month)
 * @method static int getDaysSinceStartOfWeek(string $date)
 * @method static int getDaysUntilEndOfWeek(string $date)
 * @method static string getLastWeekdayOfMonth(int $year, int $month, int $weekday)
 * @method static string getThanksgiving(int $year)
 * @method static string getLaborDay(int $year)
 * @method static string getMemorialDay(int $year)
 * @method static string getMLKDay(int $year)
 * @method static string getPresidentsDay(int $year)
 * @method static bool isBirthday(string $birthdate, ?string $referenceDate = null)
 * @method static bool isAnniversary(string $originalDate, ?string $referenceDate = null)
 * @method static string toDateString(string $datetime)
 * @method static string toTimeString(string $datetime)
 * @method static string toDayDateTimeString(string $datetime)
 * @method static string getStartOfDecade(int $year)
 * @method static string getEndOfDecade(int $year)
 * @method static string getStartOfCentury(int $year)
 * @method static string getEndOfCentury(int $year)
 * @method static float getDayPercentage(string $datetime)
 * @method static float floatDiffInDays(string $date1, string $date2)
 * @method static float floatDiffInWeeks(string $date1, string $date2)
 * @method static float floatDiffInMonths(string $date1, string $date2)
 * @method static string toJSON(string $date)
 * @method static array getDateComponents(string $datetime)
 * @method static ?string detectDateFormat(string $date)
 * @method static int getWeekYear(string $date)
 * @method static bool isStartOfYear(string $date)
 * @method static bool isEndOfYear(string $date)
 * @method static bool isStartOfMonth(string $date)
 * @method static bool isEndOfMonth(string $date)
 * @method static bool isStartOfWeek(string $date)
 * @method static bool isEndOfWeek(string $date)
 * @method static int getWorkweekNumber(string $date)
 * @method static string toFormattedDateString(string $date)
 * @method static string toLongDateString(string $date)
 * @method static string toShortDateString(string $date)
 * @method static string toShortTimeString(string $datetime)
 * @method static int getTimestamp(string $date)
 * @method static string getStartOfNextMonth(string $date)
 * @method static string getStartOfPreviousMonth(string $date)
 * @method static string getEndOfPreviousMonth(string $date)
 * @method static string getStartOfNextYear(string $date)
 * @method static string getStartOfPreviousYear(string $date)
 * @method static string getStartOfNextWeek(string $date)
 * @method static string getStartOfPreviousWeek(string $date)
 * @method static string getEndOfPreviousWeek(string $date)
 * @method static string getEndOfNextWeek(string $date)
 * @method static int getQuarterMonth(string $date)
 * @method static int getWeekdayOrdinalInMonth(string $date)
 * @method static string getOrdinalWeekdayLabel(string $date)
 * @method static bool isLongMonth(int $year, int $month)
 * @method static bool isShortMonth(int $year, int $month)
 * @method static string convertBetweenTimezones(string $datetime, string $fromTimezone, string $toTimezone, string $format = 'Y-m-d H:i:s')
 * @method static float diffInBusinessHours(string $start, string $end, int $workStart = 9, int $workEnd = 18, array $holidays = [])
 * @method static string addWorkingMinutes(string $datetime, int $minutes, int $workStart = 9, int $workEnd = 18, array $holidays = [])
 * @method static int getEpochDayOfWeek(int $daysSinceEpoch)
 * @method static string formatElapsed(int $seconds)
 * @method static array getDateBoundaries(string $date)
 * @method static string getRelativeCalendarDay(string $date)
 * @method static int diffInCalendarDays(string $date1, string $date2)
 * @method static int diffInCalendarMonths(string $date1, string $date2)
 * @method static int diffInCalendarYears(string $date1, string $date2)
 * @method static string toSQLDate(string $date)
 * @method static string toSQLTimestamp(string $date)
 * @method static bool isISO8601(string $date)
 * @method static bool isRFC2822(string $date)
 * @method static string createDate(int $year, int $month, int $day, string $format = 'Y-m-d')
 * @method static string createDateTime(int $year, int $month, int $day, int $hour = 0, int $minute = 0, int $second = 0, string $format = 'Y-m-d H:i:s')
 * @method static float getJulianDate(string $date)
 * @method static float getModifiedJulianDate(string $date)
 * @method static int getLilianDate(string $date)
 * @method static int getRataDie(string $date)
 * @method static float diffInDecimalDays(string $date1, string $date2)
 * @method static string getNextMonth(string $date, string $format = 'Y-m')
 * @method static string getPreviousMonth(string $date, string $format = 'Y-m')
 * @method static bool isDateInArray(string $date, array $dates)
 * @method static array uniqueDates(array $dates)
 * @method static array intersectDateRanges(array $range1, array $range2)
 * @method static int countDaysInDateRanges(array $ranges)
 * @method static string getMonthYearLabel(string $date)
 * @method static string getWeekLabel(string $date)
 * @method static array getMonthBoundaries(int $year, int $month)
 * @method static array getYearBoundaries(int $year)
 * @method static array getWeekBoundaries(string $date)
 * @method static string shiftDate(string $date, int $years = 0, int $months = 0, int $days = 0, int $hours = 0, int $minutes = 0, int $seconds = 0, string $format = 'Y-m-d H:i:s')
 */
class Date
{
	/**
	 * Gregorian dates of Lunar New Year (설날 / 春節).
	 * Format: solar_year => [month, day].
	 *
	 * The key is the Gregorian year in which that Lunar New Year falls.
	 * Because Lunar New Year always occurs in Jan or Feb (rarely Mar),
	 * the key equals the lunar year number for years in this range.
	 *
	 * Source: public-domain astronomical tables (Korean Astronomical Research Institute / 한국천문연구원 cross-verified data).
	 */
	private static array $lunarNewYearDates = [
		1900 => [1, 31],
		1901 => [2, 19],
		1902 => [2, 8],
		1903 => [1, 29],
		1904 => [2, 16],
		1905 => [2, 4],
		1906 => [1, 25],
		1907 => [2, 13],
		1908 => [2, 2],
		1909 => [1, 22],
		1910 => [2, 10],
		1911 => [1, 30],
		1912 => [2, 18],
		1913 => [2, 6],
		1914 => [1, 26],
		1915 => [2, 14],
		1916 => [2, 3],
		1917 => [1, 23],
		1918 => [2, 11],
		1919 => [2, 1],
		1920 => [2, 20],
		1921 => [2, 8],
		1922 => [1, 28],
		1923 => [2, 16],
		1924 => [2, 5],
		1925 => [1, 25],
		1926 => [2, 13],
		1927 => [2, 2],
		1928 => [1, 23],
		1929 => [2, 10],
		1930 => [1, 30],
		1931 => [2, 17],
		1932 => [2, 6],
		1933 => [1, 26],
		1934 => [2, 14],
		1935 => [2, 4],
		1936 => [1, 24],
		1937 => [2, 11],
		1938 => [1, 31],
		1939 => [2, 19],
		1940 => [2, 8],
		1941 => [1, 27],
		1942 => [2, 15],
		1943 => [2, 5],
		1944 => [1, 25],
		1945 => [2, 13],
		1946 => [2, 2],
		1947 => [1, 22],
		1948 => [2, 10],
		1949 => [1, 29],
		1950 => [2, 17],
		1951 => [2, 6],
		1952 => [1, 27],
		1953 => [2, 14],
		1954 => [2, 3],
		1955 => [1, 24],
		1956 => [2, 12],
		1957 => [1, 31],
		1958 => [2, 18],
		1959 => [2, 8],
		1960 => [1, 28],
		1961 => [2, 15],
		1962 => [2, 5],
		1963 => [1, 25],
		1964 => [2, 13],
		1965 => [2, 2],
		1966 => [1, 21],
		1967 => [2, 9],
		1968 => [1, 30],
		1969 => [2, 17],
		1970 => [2, 6],
		1971 => [1, 27],
		1972 => [2, 15],
		1973 => [2, 3],
		1974 => [1, 23],
		1975 => [2, 11],
		1976 => [1, 31],
		1977 => [2, 18],
		1978 => [2, 7],
		1979 => [1, 28],
		1980 => [2, 16],
		1981 => [2, 5],
		1982 => [1, 25],
		1983 => [2, 13],
		1984 => [2, 2],
		1985 => [2, 20],
		1986 => [2, 9],
		1987 => [1, 29],
		1988 => [2, 17],
		1989 => [2, 6],
		1990 => [1, 27],
		1991 => [2, 15],
		1992 => [2, 4],
		1993 => [1, 23],
		1994 => [2, 10],
		1995 => [1, 31],
		1996 => [2, 19],
		1997 => [2, 7],
		1998 => [1, 28],
		1999 => [2, 16],
		2000 => [2, 5],
		2001 => [1, 24],
		2002 => [2, 12],
		2003 => [2, 1],
		2004 => [1, 22],
		2005 => [2, 9],
		2006 => [1, 29],
		2007 => [2, 18],
		2008 => [2, 7],
		2009 => [1, 26],
		2010 => [2, 14],
		2011 => [2, 3],
		2012 => [1, 23],
		2013 => [2, 10],
		2014 => [1, 31],
		2015 => [2, 19],
		2016 => [2, 8],
		2017 => [1, 28],
		2018 => [2, 16],
		2019 => [2, 5],
		2020 => [1, 25],
		2021 => [2, 12],
		2022 => [2, 1],
		2023 => [1, 22],
		2024 => [2, 10],
		2025 => [1, 29],
		2026 => [2, 17],
		2027 => [2, 6],
		2028 => [1, 26],
		2029 => [2, 13],
		2030 => [2, 3],
		2031 => [1, 23],
		2032 => [2, 11],
		2033 => [1, 31],
		2034 => [2, 19],
		2035 => [2, 8],
		2036 => [1, 28],
		2037 => [2, 15],
		2038 => [2, 4],
		2039 => [1, 24],
		2040 => [2, 12],
		2041 => [2, 1],
		2042 => [1, 22],
		2043 => [2, 10],
		2044 => [1, 30],
		2045 => [2, 17],
		2046 => [2, 6],
		2047 => [1, 26],
		2048 => [2, 14],
		2049 => [2, 2],
		2050 => [1, 23],
		2051 => [2, 11],
		2052 => [2, 1],
		2053 => [2, 19],
		2054 => [2, 8],
		2055 => [1, 28],
		2056 => [2, 15],
		2057 => [2, 4],
		2058 => [1, 24],
		2059 => [2, 12],
		2060 => [2, 2],
		2061 => [1, 21],
		2062 => [2, 9],
		2063 => [1, 29],
		2064 => [2, 17],
		2065 => [2, 5],
		2066 => [1, 26],
		2067 => [2, 14],
		2068 => [2, 3],
		2069 => [1, 23],
		2070 => [2, 11],
		2071 => [1, 31],
		2072 => [2, 19],
		2073 => [2, 7],
		2074 => [1, 27],
		2075 => [2, 15],
		2076 => [2, 5],
		2077 => [1, 24],
		2078 => [2, 12],
		2079 => [2, 2],
		2080 => [1, 22],
		2081 => [2, 9],
		2082 => [1, 29],
		2083 => [2, 17],
		2084 => [2, 6],
		2085 => [1, 26],
		2086 => [2, 14],
		2087 => [2, 3],
		2088 => [1, 24],
		2089 => [2, 10],
		2090 => [1, 30],
		2091 => [2, 18],
		2092 => [2, 7],
		2093 => [1, 27],
		2094 => [2, 15],
		2095 => [2, 5],
		2096 => [1, 25],
		2097 => [2, 12],
		2098 => [2, 1],
		2099 => [1, 21],
		2100 => [2, 9],
	];

	/**
	 * Intercalary (leap / 윤달) month numbers.
	 * A value of n means a leap copy of month n is inserted after the
	 * regular month n of that lunar year.  Years absent from the map
	 * have no leap month.
	 *
	 * Source: traditional Chinese-calendar astronomical rules.
	 */
	private static array $lunarLeapMonths = [
		1900 => 8,
		1903 => 5,
		1906 => 4,
		1909 => 2,
		1911 => 6,
		1914 => 5,
		1917 => 2,
		1919 => 7,
		1922 => 5,
		1925 => 4,
		1928 => 2,
		1930 => 6,
		1933 => 5,
		1936 => 3,
		1938 => 7,
		1941 => 6,
		1944 => 4,
		1947 => 2,
		1949 => 7,
		1952 => 5,
		1955 => 3,
		1957 => 8,
		1960 => 6,
		1963 => 4,
		1966 => 3,
		1968 => 7,
		1971 => 5,
		1974 => 4,
		1976 => 8,
		1979 => 6,
		1982 => 4,
		1984 => 10,
		1987 => 6,
		1990 => 5,
		1993 => 3,
		1995 => 8,
		1998 => 5,
		2001 => 4,
		2004 => 2,
		2006 => 7,
		2009 => 5,
		2012 => 4,
		2014 => 9,
		2017 => 6,
		2020 => 4,
		2023 => 2,
		2025 => 6,
		2028 => 5,
		2031 => 3,
		2033 => 11,
		2036 => 6,
		2039 => 5,
		2042 => 2,
		2044 => 7,
		2047 => 5,
		2050 => 3,
		2052 => 8,
		2055 => 6,
		2058 => 4,
		2061 => 3,
		2063 => 7,
		2066 => 5,
		2069 => 4,
		2071 => 8,
		2074 => 6,
		2077 => 4,
		2080 => 3,
		2082 => 7,
		2085 => 5,
		2088 => 4,
		2090 => 8,
		2093 => 6,
		2096 => 4,
		2099 => 2,
	];

	/**
	 * Calculate age from birthdate.
	 *
	 * @param string $birthdate The birthdate in 'Y-m-d' format.
	 * 
	 * @return array{
	 * 	years: int,
	 * 	months: int,
	 * 	days: int
	 * } An associative array with keys 'years', 'months', and 'days'.
	 */
	public static function calculateAgeFromBirthdate(string $birthdate): array
	{
		$birthdate = new DateTime($birthdate);
		$now = new DateTime(date("Y-m-d"));
		$diff = $now->diff($birthdate);

		return [
			'years' => $diff->y,
			'months' => $diff->m,
			'days' => $diff->d,
		];
	}

	/**
	 * Get Julian Centuries from Julian Date.
	 *
	 * @param float $JD The Julian Date.
	 * 
	 * @return float The number of Julian Centuries since J2000.0.
	 */
	public static function getJulianCenturies(float $JD): float
	{
		return ($JD - DateEnum::JD_J2000) / DateEnum::DAYS_PER_JULIAN_CENTURY;
	}

	/**
	 * Get today's date in 'Y-m-d' format.
	 *
	 * @return string Today's date.
	 */
	public static function getTomorrow(): string
	{
		return date("Y-m-d", strtotime("+1 day"));
	}

	/**
	 * Get yesterday's date in 'Y-m-d' format.
	 *
	 * @return string Yesterday's date.
	 */
	public static function getYesterday(): string
	{
		return date("Y-m-d", strtotime("-1 day"));
	}

	/**
	 * Get the last day of a specific month in a given year.
	 *
	 * @param int $year The year.
	 * @param int $month The month.
	 * 
	 * @return int|string The last day of the month.
	 */
	public static function getLastDayOfMonth(int $year, int $month): int|string
	{
		return date("t", strtotime("$year-$month-01"));
	}

	/**
	 * Get the current quarter of the year.
	 *
	 * @return float The current quarter (1-4).
	 */
	public static function getQuarter(): float
	{
		return ceil(date("n") / 3);
	}

	/**
	 * Convert a timestamp to a formatted date string.
	 *
	 * @param int $timestamp The timestamp to convert.
	 * 
	 * @return string The formatted date string.
	 */
	public static function timestampToDate(int $timestamp): string
	{
		return date("Y-m-d H:i:s", $timestamp);
	}

	/**
	 * Check if a given date falls on a weekend.
	 *
	 * @param string $date The date to check.
	 * 
	 * @return bool True if the date is a weekend, false otherwise.
	 */
	public static function isWeekend(string $date): bool
	{
		$day = date("N", strtotime($date));
		return ($day >= 6);
	}

	/**
	 * Get all dates between two given dates.
	 *
	 * @param string $start The start date.
	 * @param string $end The end date.
	 * 
	 * @return array An array of dates between the start and end dates.
	 */
	public static function getDatesBetween(string $start, string $end): array
	{
		$dates = [];
		$current = strtotime($start);
		$end = strtotime($end);
		while ($current <= $end) {
			$dates[] = date("Y-m-d", $current);
			$current = strtotime("+1 day", $current);
		}

		return $dates;
	}

	/**
	 * Get the difference in days between two dates.
	 *
	 * @param string $date1 The first date.
	 * @param string $date2 The second date.
	 * 
	 * @return int The difference in days.
	 */
	public static function getDateDiffInDays(string $date1, string $date2): mixed
	{
		$d1 = new DateTime($date1);
		$d2 = new DateTime($date2);
		return $d1->diff($d2)->days;
	}

	/**
	 * Convert Julian Date to Gregorian Date.
	 *
	 * @param float $jd The Julian Date.
	 * 
	 * @return DateTime The corresponding Gregorian Date.
	 */
	public static function julianToGregorian(float $jd): DateTime
	{
		$dJDs = $jd + 0.5;
		$dJDi = floor($dJDs);
		$dJDf = $dJDs - $dJDi;

		if ($dJDi > DateEnum::JD_GREGORIAN_START) {
			$da = floor(($dJDi - DateEnum::JD_JULIAN_TO_GREG_OFFSET) / DateEnum::JULIAN_CENTURY_DAYS);
			$dJDi += (1.0 + $da - floor($da / 4.0));
		}

		$db = $dJDi + DateEnum::JD_EPOCH_OFFSET;
		$nY = (int) floor(($db - DateEnum::JD_DAY_OFFSET) / DateEnum::JULIAN_YEAR_DAYS);
		$dd = $db - floor($nY * DateEnum::JULIAN_YEAR_DAYS);
		$nM = (int) floor($dd / DateEnum::JULIAN_MONTH_DAYS);

		$day = $dd - floor($nM * DateEnum::JULIAN_MONTH_DAYS) + $dJDf;

		if ($nM < 14) {
			$month = $nM - 1;
		} else {
			$month = $nM - 13;
		}

		if ($month > 2) {
			$year = $nY - DateEnum::JD_YEAR_OFFSET_AFTER_FEB;
		} else {
			$year = $nY - DateEnum::JD_YEAR_OFFSET_BEFORE_MAR;
		}

		$date = new DateTime();
		return $date->setDate($year, $month, (int) $day);
	}

	/**
	 * Convert Gregorian Date to Julian Date.
	 *
	 * @param int $year The year.
	 * @param int $month The month.
	 * @param float $day The day.
	 * 
	 * @return float The corresponding Julian Date.
	 */
	public static function toJulian(int $year, int $month, float $day): float
	{
		if ($month < 3) {
			$month += DateEnum::JULIAN_MONTH_OFFSET;
			$year--;
		}

		$da = floor($year / DateEnum::JULIAN_CENTURY_DIVISOR);
		$db = DateEnum::JULIAN_GREGORIAN_CORR_BASE - $da + floor($da / DateEnum::JULIAN_GREGORIAN_CORR_DIV);

		$jd = floor(DateEnum::JULIAN_YEAR_DAYS_FACTOR * $year)
			+ floor(DateEnum::JULIAN_MONTH_DAYS_FACTOR * ($month + 1))
			+ $day + $db + DateEnum::JULIAN_EPOCH_OFFSET;

		if ($jd < DateEnum::JULIAN_GREGORIAN_START_JD) {
			$jd -= $db;
		}

		return $jd;
	}

	/**
	 * Convert seconds since epoch to UTC date string.
	 *
	 * @param int $seconds The seconds since epoch.
	 * @param string $format The date format (default is 'Y-m-d H:i:s').
	 * 
	 * @return string The formatted UTC date string.
	 */
	public static function toUTC(int $seconds, string $format = 'Y-m-d H:i:s'): string
	{
		$dt = new DateTime('@' . $seconds);
		$dt->setTimezone(new DateTimeZone('UTC'));

		return $dt->format($format);
	}

	/**
	 * Check if a given DateTime is today.
	 *
	 * @param DateTime $datetime The DateTime to check.
	 * 
	 * @return bool True if the DateTime is today, false otherwise.
	 */
	public static function isToday(DateTime $datetime): bool
	{
		$compare = $datetime->format('Y/m/d');
		$now = date('Y/m/d');

		return $compare === $now;
	}

	/**
	 * Check if a year is a leap year.
	 *
	 * @param int $year The year to check.
	 * @param bool $julian Whether to use Julian calendar rules (default is false).
	 * 
	 * @return bool|int True if the year is a leap year, false otherwise.
	 */
	public static function isLeapYear(int $year, bool $julian = false): bool|int
	{
		if ($julian || $year < 1583) {
			return $year % 4;
		} else if (($year % 400) == 0) {
			return true;
		} else if (($year % 4) == 0 && ($year % 100) != 0) {
			return true;
		}

		return false;
	}

	/**
	 * Set the default timezone.
	 *
	 * @param string $timezoneId The timezone identifier.
	 * 
	 * @return bool True on success, false on failure.
	 */
	public static function setDefaultTimezone(string $timezoneId): bool
	{
		return date_default_timezone_set($timezoneId);
	}

	/**
	 * Get the default timezone.
	 *
	 * @return string The default timezone identifier.
	 */
	public static function getDefaultTimezone(): string
	{
		return date_default_timezone_get();
	}

	/**
	 * Convert a date string to a timestamp.
	 *
	 * @param string $string The date string.
	 * @param int $format The base timestamp (default is current time).
	 * 
	 * @return bool|int The resulting timestamp.
	 */
	public static function stringToTime(string $string, int $format): bool|int
	{
		return strtotime($string, $format);
	}

	/**
	 * Get date components as an associative array.
	 *
	 * @param int $date The timestamp to convert.
	 * 
	 * @return array The date components.
	 */
	public static function getDateArray(int $date): array
	{
		return getdate($date);
	}

	/**
	 * Parse a date string according to a specified format.
	 *
	 * @param string $date The date string to parse.
	 * @param string $format The format to parse the date string.
	 * 
	 * @return array The parsed date components.
	 */
	public static function parseString(string $date, string $format): array|bool
	{
		return strptime($date, $format);
	}

	/**
	 * Format a date according to a specified format.
	 *
	 * @param string $date The format string.
	 * @param int|null $timestamp The timestamp to use.
	 * @return string The formatted date string.
	 */
	public static function timeToString(string $date, int|null $timestamp = null): bool|string
	{
		if (version_compare(phpversion(), '8.1', '<')) {
			// @phpstan-ignore-next-line
			return strftime($date, $timestamp);
		} else {
			return date($date, $timestamp);
		}
	}

	/**
	 * Get the current timestamp.
	 *
	 * @return int The current timestamp.
	 */
	public static function getTime(): int
	{
		return time();
	}

	/**
	 * Get the start date of the week for a given date.
	 *
	 * @param string $date The date to find the start of the week for.
	 * 
	 * @return string The start date of the week in 'Y-m-d' format.
	 */
	public static function getStartOfWeek(string $date): string
	{
		$dt = new DateTime($date);
		$dt->modify('monday this week');
		return $dt->format('Y-m-d');
	}

	/**
	 * Get the start date of the quarter for a given date.
	 *
	 * @param string $date The date to find the start of the quarter for.
	 * 
	 * @return string The start date of the quarter in 'Y-m-d' format.
	 */
	public static function getQuarterStart(string $date): string
	{
		$dt = new DateTime($date);
		$quarter = ceil($dt->format(DateStringFormat::MonthNumericWithoutLeadingZeros->value) / 3);
		return date("Y-m-d", strtotime($dt->format("Y") . "-" . (($quarter - 1) * 3 + 1) . "-01"));
	}

	/**
	 * Get the end date of the quarter for a given date.
	 *
	 * @param string $date The date to find the end of the quarter for.
	 * 
	 * @return string The end date of the quarter in 'Y-m-d' format.
	 */
	public static function getQuarterEnd(string $date): string
	{
		$start = new DateTime(self::getQuarterStart($date));
		$start->modify('+2 months')->modify('last day of this month');
		return $start->format('Y-m-d');
	}

	/**
	 * Get the end date of the week for a given date.
	 *
	 * @param string $date The date to find the end of the week for.
	 * 
	 * @return string The end date of the week in 'Y-m-d' format.
	 */
	public static function getEndOfWeek(string $date): string
	{
		$dt = new DateTime($date);
		$dt->modify('sunday this week');
		return $dt->format('Y-m-d');
	}

	/**
	 * Get the day of the week for a given date.
	 *
	 * @param int $timestamp The timestamp to check.
	 * 
	 * @return int|string The day of the week (0 for Sunday, 6 for Saturday).
	 */
	public static function getDayOfWeek(?int $timestamp = null): int|string
	{
		$dayofweek = date('w', $timestamp ?? self::getTime());

		return $dayofweek;
	}

	/**
	 * Get the day name of the week for a given date.
	 *
	 * @param int $timestamp The timestamp to check.
	 * 
	 * @return int|string The day name of the week (0 for Sunday, 6 for Saturday).
	 */
	public static function getDayNameOfWeek(?int $timestamp = null): int|string
	{
		$dayofweek = date('w', $timestamp ?? self::getTime());

		return ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"][$dayofweek];
	}

	/**
	 * Get the ISO week number for a given date.
	 *
	 * @param string $date The date to check.
	 * 
	 * @return string The ISO week number in 'o-W' format.
	 */
	public static function getISOWeek(string $date): string
	{
		return date("o-W", strtotime($date));
	}

	/**
	 * Get the week number of the quarter for a given date.
	 *
	 * @param string $date The date to check.
	 * 
	 * @return float|int The week number of the quarter.
	 */
	public static function getWeekOfQuarter(string $date): float|int
	{
		$start = new DateTime(self::getQuarterStart($date));
		$dt = new DateTime($date);
		return floor($start->diff($dt)->days / 7) + 1;
	}

	/**
	 * Get all years between two dates.
	 *
	 * @param string $start The start date.
	 * @param string $end The end date.
	 * 
	 * @return array<string> An array of years between the start and end dates.
	 */
	public static function getYearsBetween(string $start, string $end): array
	{
		$start = new DateTime($start);
		$end = new DateTime($end);
		$years = [];
		while ($start <= $end) {
			$years[] = $start->format("Y");
			$start->modify('+1 year');
		}

		return $years;
	}

	/**
	 * Get the timezone offset in seconds for a given timezone and timestamp.
	 *
	 * @param string   $timezone  The timezone identifier.
	 * @param int|null $timestamp The timestamp (default is current time).
	 * 
	 * @return int The timezone offset in seconds.
	 */
	public static function getTimezoneOffset(string $timezone, ?int $timestamp = null): int
	{
		$dateTimezone = new DateTimeZone($timezone);
		$datetime = new DateTime();
		$datetime->setTimestamp($timestamp ?: self::getTime());
		$datetime->setTimezone($dateTimezone);
		return $datetime->getOffset();
	}

	/**
	 * Get a list of all available timezone identifiers.
	 *
	 * @return array An array of timezone identifiers.
	 */
	public static function getTimezoneList(): array
	{
		return DateTimeZone::listIdentifiers();
	}

	/**
	 * Get the timezone offset in seconds from a timezone name.
	 *
	 * @param string $name The timezone name.
	 * 
	 * @return int The timezone offset in seconds.
	 */
	public static function getOffsetFromTimezoneName(string $name): int
	{
		return match ($name) {
			TimeZone::AFRICA_ABIDJAN => 0,
			TimeZone::AFRICA_ACCRA => 0,
			TimeZone::AFRICA_ADDIS_ABABA => +10800,
			TimeZone::AFRICA_ALGIERS => +3600,
			TimeZone::AFRICA_ASMARA => +10800,
			TimeZone::AFRICA_BAMAKO => 0,
			TimeZone::AFRICA_BANGUI => +3600,
			TimeZone::AFRICA_BANJUL => 0,
			TimeZone::AFRICA_BISSAU => 0,
			TimeZone::AFRICA_BLANTYRE => +7200,
			TimeZone::AFRICA_BRAZZAVILLE => +3600,
			TimeZone::AFRICA_BUJUMBURA => +7200,
			TimeZone::AFRICA_CAIRO => +10800,
			TimeZone::AFRICA_CASABLANCA => +3600,
			TimeZone::AFRICA_CEUTA => +7200,
			TimeZone::AFRICA_CONAKRY => 0,
			TimeZone::AFRICA_DAKAR => 0,
			TimeZone::AFRICA_DAR_ES_SALAAM => +10800,
			TimeZone::AFRICA_DJIBOUTI => +10800,
			TimeZone::AFRICA_DOUALA => +3600,
			TimeZone::AFRICA_EL_AAIUN => +3600,
			TimeZone::AFRICA_FREETOWN => 0,
			TimeZone::AFRICA_GABORONE => +7200,
			TimeZone::AFRICA_HARARE => +7200,
			TimeZone::AFRICA_JOHANNESBURG => +7200,
			TimeZone::AFRICA_JUBA => +7200,
			TimeZone::AFRICA_KAMPALA => +10800,
			TimeZone::AFRICA_KHARTOUM => +7200,
			TimeZone::AFRICA_KIGALI => +7200,
			TimeZone::AFRICA_KINSHASA => +3600,
			TimeZone::AFRICA_LAGOS => +3600,
			TimeZone::AFRICA_LIBREVILLE => +3600,
			TimeZone::AFRICA_LOME => 0,
			TimeZone::AFRICA_LUANDA => +3600,
			TimeZone::AFRICA_LUBUMBASHI => +7200,
			TimeZone::AFRICA_LUSAKA => +7200,
			TimeZone::AFRICA_MALABO => +3600,
			TimeZone::AFRICA_MAPUTO => +7200,
			TimeZone::AFRICA_MASERU => +7200,
			TimeZone::AFRICA_MBABANE => +7200,
			TimeZone::AFRICA_MOGADISHU => +10800,
			TimeZone::AFRICA_MONROVIA => 0,
			TimeZone::AFRICA_NAIROBI => +10800,
			TimeZone::AFRICA_NDJAMENA => +3600,
			TimeZone::AFRICA_NIAMEY => +3600,
			TimeZone::AFRICA_NOUAKCHOTT => 0,
			TimeZone::AFRICA_OUAGADOUGOU => 0,
			TimeZone::AFRICA_PORTO_NOVO => +3600,
			TimeZone::AFRICA_SAO_TOME => 0,
			TimeZone::AFRICA_TRIPOLI => +7200,
			TimeZone::AFRICA_TUNIS => +3600,
			TimeZone::AFRICA_WINDHOEK => +7200,
			TimeZone::AMERICA_ADAK => -32400,
			TimeZone::AMERICA_ANCHORAGE => -28800,
			TimeZone::AMERICA_ANGUILLA => -14400,
			TimeZone::AMERICA_ANTIGUA => -14400,
			TimeZone::AMERICA_ARAGUAINA => -10800,
			TimeZone::AMERICA_ARGENTINA_BUENOS_AIRES => -10800,
			TimeZone::AMERICA_ARGENTINA_CATAMARCA => -10800,
			TimeZone::AMERICA_ARGENTINA_CORDOBA => -10800,
			TimeZone::AMERICA_ARGENTINA_JUJUY => -10800,
			TimeZone::AMERICA_ARGENTINA_LA_RIOJA => -10800,
			TimeZone::AMERICA_ARGENTINA_MENDOZA => -10800,
			TimeZone::AMERICA_ARGENTINA_RIO_GALLEGOS => -10800,
			TimeZone::AMERICA_ARGENTINA_SALTA => -10800,
			TimeZone::AMERICA_ARGENTINA_SAN_JUAN => -10800,
			TimeZone::AMERICA_ARGENTINA_SAN_LUIS => -10800,
			TimeZone::AMERICA_ARGENTINA_TUCUMAN => -10800,
			TimeZone::AMERICA_ARGENTINA_USHUAIA => -10800,
			TimeZone::AMERICA_ARUBA => -14400,
			TimeZone::AMERICA_ASUNCION => -10800,
			TimeZone::AMERICA_ATIKOKAN => -18000,
			TimeZone::AMERICA_BAHIA => -10800,
			TimeZone::AMERICA_BAHIA_BANDERAS => -21600,
			TimeZone::AMERICA_BARBADOS => -14400,
			TimeZone::AMERICA_BELEM => -10800,
			TimeZone::AMERICA_BELIZE => -21600,
			TimeZone::AMERICA_BLANC_SABLON => -14400,
			TimeZone::AMERICA_BOA_VISTA => -14400,
			TimeZone::AMERICA_BOGOTA => -18000,
			TimeZone::AMERICA_BOISE => -21600,
			TimeZone::AMERICA_CAMBRIDGE_BAY => -21600,
			TimeZone::AMERICA_CAMPO_GRANDE => -14400,
			TimeZone::AMERICA_CANCUN => -18000,
			TimeZone::AMERICA_CARACAS => -14400,
			TimeZone::AMERICA_CAYENNE => -10800,
			TimeZone::AMERICA_CAYMAN => -18000,
			TimeZone::AMERICA_CHICAGO => -18000,
			TimeZone::AMERICA_CHIHUAHUA => -21600,
			TimeZone::AMERICA_CIUDAD_JUAREZ => -21600,
			TimeZone::AMERICA_COSTA_RICA => -21600,
			TimeZone::AMERICA_COYHAIQUE => -10800,
			TimeZone::AMERICA_CRESTON => -25200,
			TimeZone::AMERICA_CUIABA => -14400,
			TimeZone::AMERICA_CURACAO => -14400,
			TimeZone::AMERICA_DANMARKSHAVN => 0,
			TimeZone::AMERICA_DAWSON => -25200,
			TimeZone::AMERICA_DAWSON_CREEK => -25200,
			TimeZone::AMERICA_DENVER => -21600,
			TimeZone::AMERICA_DETROIT => -14400,
			TimeZone::AMERICA_DOMINICA => -14400,
			TimeZone::AMERICA_EDMONTON => -21600,
			TimeZone::AMERICA_EIRUNEPE => -18000,
			TimeZone::AMERICA_EL_SALVADOR => -21600,
			TimeZone::AMERICA_FORT_NELSON => -25200,
			TimeZone::AMERICA_FORTALEZA => -10800,
			TimeZone::AMERICA_GLACE_BAY => -10800,
			TimeZone::AMERICA_GOOSE_BAY => -10800,
			TimeZone::AMERICA_GRAND_TURK => -14400,
			TimeZone::AMERICA_GRENADA => -14400,
			TimeZone::AMERICA_GUADELOUPE => -14400,
			TimeZone::AMERICA_GUATEMALA => -21600,
			TimeZone::AMERICA_GUAYAQUIL => -18000,
			TimeZone::AMERICA_GUYANA => -14400,
			TimeZone::AMERICA_HALIFAX => -10800,
			TimeZone::AMERICA_HAVANA => -14400,
			TimeZone::AMERICA_HERMOSILLO => -25200,
			TimeZone::AMERICA_INDIANA_INDIANAPOLIS => -14400,
			TimeZone::AMERICA_INDIANA_KNOX => -18000,
			TimeZone::AMERICA_INDIANA_MARENGO => -14400,
			TimeZone::AMERICA_INDIANA_PETERSBURG => -14400,
			TimeZone::AMERICA_INDIANA_TELL_CITY => -18000,
			TimeZone::AMERICA_INDIANA_VEVAY => -14400,
			TimeZone::AMERICA_INDIANA_VINCENNES => -14400,
			TimeZone::AMERICA_INDIANA_WINAMAC => -14400,
			TimeZone::AMERICA_INUVIK => -21600,
			TimeZone::AMERICA_IQALUIT => -14400,
			TimeZone::AMERICA_JAMAICA => -18000,
			TimeZone::AMERICA_JUNEAU => -28800,
			TimeZone::AMERICA_KENTUCKY_LOUISVILLE => -14400,
			TimeZone::AMERICA_KENTUCKY_MONTICELLO => -14400,
			TimeZone::AMERICA_KRALENDIJK => -14400,
			TimeZone::AMERICA_LA_PAZ => -14400,
			TimeZone::AMERICA_LIMA => -18000,
			TimeZone::AMERICA_LOS_ANGELES => -25200,
			TimeZone::AMERICA_LOWER_PRINCES => -14400,
			TimeZone::AMERICA_MACEIO => -10800,
			TimeZone::AMERICA_MANAGUA => -21600,
			TimeZone::AMERICA_MANAUS => -14400,
			TimeZone::AMERICA_MARIGOT => -14400,
			TimeZone::AMERICA_MARTINIQUE => -14400,
			TimeZone::AMERICA_MATAMOROS => -18000,
			TimeZone::AMERICA_MAZATLAN => -25200,
			TimeZone::AMERICA_MENOMINEE => -18000,
			TimeZone::AMERICA_MERIDA => -21600,
			TimeZone::AMERICA_METLAKATLA => -28800,
			TimeZone::AMERICA_MEXICO_CITY => -21600,
			TimeZone::AMERICA_MIQUELON => -7200,
			TimeZone::AMERICA_MONCTON => -10800,
			TimeZone::AMERICA_MONTERREY => -21600,
			TimeZone::AMERICA_MONTEVIDEO => -10800,
			TimeZone::AMERICA_MONTSERRAT => -14400,
			TimeZone::AMERICA_NASSAU => -14400,
			TimeZone::AMERICA_NEW_YORK => -14400,
			TimeZone::AMERICA_NOME => -28800,
			TimeZone::AMERICA_NORONHA => -7200,
			TimeZone::AMERICA_NORTH_DAKOTA_BEULAH => -18000,
			TimeZone::AMERICA_NORTH_DAKOTA_CENTER => -18000,
			TimeZone::AMERICA_NORTH_DAKOTA_NEW_SALEM => -18000,
			TimeZone::AMERICA_NUUK => -3600,
			TimeZone::AMERICA_OJINAGA => -18000,
			TimeZone::AMERICA_PANAMA => -18000,
			TimeZone::AMERICA_PARAMARIBO => -10800,
			TimeZone::AMERICA_PHOENIX => -25200,
			TimeZone::AMERICA_PORT_AU_PRINCE => -14400,
			TimeZone::AMERICA_PORT_OF_SPAIN => -14400,
			TimeZone::AMERICA_PORTO_VELHO => -14400,
			TimeZone::AMERICA_PUERTO_RICO => -14400,
			TimeZone::AMERICA_PUNTA_ARENAS => -10800,
			TimeZone::AMERICA_RANKIN_INLET => -18000,
			TimeZone::AMERICA_RECIFE => -10800,
			TimeZone::AMERICA_REGINA => -21600,
			TimeZone::AMERICA_RESOLUTE => -18000,
			TimeZone::AMERICA_RIO_BRANCO => -18000,
			TimeZone::AMERICA_SANTAREM => -10800,
			TimeZone::AMERICA_SANTIAGO => -10800,
			TimeZone::AMERICA_SANTO_DOMINGO => -14400,
			TimeZone::AMERICA_SAO_PAULO => -10800,
			TimeZone::AMERICA_SCORESBYSUND => -3600,
			TimeZone::AMERICA_SITKA => -28800,
			TimeZone::AMERICA_ST_BARTHELEMY => -14400,
			TimeZone::AMERICA_ST_JOHNS => -9000,
			TimeZone::AMERICA_ST_KITTS => -14400,
			TimeZone::AMERICA_ST_LUCIA => -14400,
			TimeZone::AMERICA_ST_THOMAS => -14400,
			TimeZone::AMERICA_ST_VINCENT => -14400,
			TimeZone::AMERICA_SWIFT_CURRENT => -21600,
			TimeZone::AMERICA_TEGUCIGALPA => -21600,
			TimeZone::AMERICA_THULE => -10800,
			TimeZone::AMERICA_TIJUANA => -25200,
			TimeZone::AMERICA_TORONTO => -14400,
			TimeZone::AMERICA_TORTOLA => -14400,
			TimeZone::AMERICA_VANCOUVER => -25200,
			TimeZone::AMERICA_WHITEHORSE => -25200,
			TimeZone::AMERICA_WINNIPEG => -18000,
			TimeZone::AMERICA_YAKUTAT => -28800,
			TimeZone::ANTARCTICA_CASEY => +28800,
			TimeZone::ANTARCTICA_DAVIS => +25200,
			TimeZone::ANTARCTICA_DUMONTDURVILLE => +36000,
			TimeZone::ANTARCTICA_MACQUARIE => +39600,
			TimeZone::ANTARCTICA_MAWSON => +18000,
			TimeZone::ANTARCTICA_MCMURDO => +46800,
			TimeZone::ANTARCTICA_PALMER => -10800,
			TimeZone::ANTARCTICA_ROTHERA => -10800,
			TimeZone::ANTARCTICA_SYOWA => +10800,
			TimeZone::ANTARCTICA_TROLL => +7200,
			TimeZone::ANTARCTICA_VOSTOK => +18000,
			TimeZone::ARCTIC_Longyearbyen => +7200,
			TimeZone::ASIA_ADEN => +10800,
			TimeZone::ASIA_ALMATY => +18000,
			TimeZone::ASIA_AMMAN => +10800,
			TimeZone::ASIA_ANADYR => +43200,
			TimeZone::ASIA_AQTAU => +18000,
			TimeZone::ASIA_AQTOBE => +18000,
			TimeZone::ASIA_ASHGABAT => +18000,
			TimeZone::ASIA_ATYRAU => +18000,
			TimeZone::ASIA_BAGHDAD => +10800,
			TimeZone::ASIA_BAHRAIN => +10800,
			TimeZone::ASIA_BAKU => +14400,
			TimeZone::ASIA_BANGKOK => +25200,
			TimeZone::ASIA_BARNAUL => +25200,
			TimeZone::ASIA_BEIRUT => +10800,
			TimeZone::ASIA_BISHKEK => +21600,
			TimeZone::ASIA_BRUNEI => +28800,
			TimeZone::ASIA_CHITA => +32400,
			TimeZone::ASIA_COLOMBO => +19800,
			TimeZone::ASIA_DAMASCUS => +10800,
			TimeZone::ASIA_DHAKA => +21600,
			TimeZone::ASIA_DILI => +32400,
			TimeZone::ASIA_DUBAI => +14400,
			TimeZone::ASIA_DUSHANBE => +18000,
			TimeZone::ASIA_FAMAGUSTA => +10800,
			TimeZone::ASIA_GAZA => +10800,
			TimeZone::ASIA_HEBRON => +10800,
			TimeZone::ASIA_HO_CHI_MINH => +25200,
			TimeZone::ASIA_HONG_KONG => +28800,
			TimeZone::ASIA_HOVD => +25200,
			TimeZone::ASIA_IRKUTSK => +28800,
			TimeZone::ASIA_JAKARTA => +25200,
			TimeZone::ASIA_JAYAPURA => +32400,
			TimeZone::ASIA_JERUSALEM => +10800,
			TimeZone::ASIA_KABUL => +16200,
			TimeZone::ASIA_KAMCHATKA => +43200,
			TimeZone::ASIA_KARACHI => +18000,
			TimeZone::ASIA_KATHMANDU => +20700,
			TimeZone::ASIA_KHANDYGA => +32400,
			TimeZone::ASIA_KOLKATA => +19800,
			TimeZone::ASIA_KRASNOYARSK => +25200,
			TimeZone::ASIA_KUALA_LUMPUR => +28800,
			TimeZone::ASIA_KUCHING => +28800,
			TimeZone::ASIA_KUWAIT => +10800,
			TimeZone::ASIA_MACAU => +28800,
			TimeZone::ASIA_MAGADAN => +39600,
			TimeZone::ASIA_MAKASSAR => +28800,
			TimeZone::ASIA_MANILA => +28800,
			TimeZone::ASIA_MUSCAT => +14400,
			TimeZone::ASIA_NICOSIA => +10800,
			TimeZone::ASIA_NOVOKUZNETSK => +25200,
			TimeZone::ASIA_NOVOSIBIRSK => +25200,
			TimeZone::ASIA_OMSK => +21600,
			TimeZone::ASIA_ORAL => +18000,
			TimeZone::ASIA_PHNOM_PENH => +25200,
			TimeZone::ASIA_PONTIANAK => +25200,
			TimeZone::ASIA_PYONGYANG => +32400,
			TimeZone::ASIA_QATAR => +10800,
			TimeZone::ASIA_QOSTANAY => +18000,
			TimeZone::ASIA_QYZYLORDA => +18000,
			TimeZone::ASIA_RIYADH => +10800,
			TimeZone::ASIA_SAKHALIN => +39600,
			TimeZone::ASIA_SAMARKAND => +18000,
			TimeZone::ASIA_SEOUL => +32400,
			TimeZone::ASIA_SHANGHAI => +28800,
			TimeZone::ASIA_SINGAPORE => +28800,
			TimeZone::ASIA_SREDNEKOLYMSK => +39600,
			TimeZone::ASIA_TAIPEI => +28800,
			TimeZone::ASIA_TASHKENT => +18000,
			TimeZone::ASIA_TBILISI => +14400,
			TimeZone::ASIA_TEHRAN => +12600,
			TimeZone::ASIA_THIMPHU => +21600,
			TimeZone::ASIA_TOKYO => +32400,
			TimeZone::ASIA_TOMSK => +25200,
			TimeZone::ASIA_ULAANBAATAR => +28800,
			TimeZone::ASIA_URUMQI => +21600,
			TimeZone::ASIA_UST_NERA => +36000,
			TimeZone::ASIA_VIENTIANE => +25200,
			TimeZone::ASIA_VLADIVOSTOK => +36000,
			TimeZone::ASIA_YAKUTSK => +32400,
			TimeZone::ASIA_YANGON => +23400,
			TimeZone::ASIA_YEKATERINBURG => +18000,
			TimeZone::ASIA_YEREVAN => +14400,
			TimeZone::ATLANTIC_AZORES => 0,
			TimeZone::ATLANTIC_BERMUDA => -10800,
			TimeZone::ATLANTIC_CANARY => +3600,
			TimeZone::ATLANTIC_CAPE_VERDE => -3600,
			TimeZone::ATLANTIC_FAROE => +3600,
			TimeZone::ATLANTIC_MADEIRA => +3600,
			TimeZone::ATLANTIC_REYKJAVIK => 0,
			TimeZone::ATLANTIC_SOUTH_GEORGIA => -7200,
			TimeZone::ATLANTIC_ST_HELENA => 0,
			TimeZone::ATLANTIC_STANLEY => -10800,
			TimeZone::AUSTRALIA_ADELAIDE => +37800,
			TimeZone::AUSTRALIA_BRISBANE => +36000,
			TimeZone::AUSTRALIA_BROKEN_HILL => +37800,
			TimeZone::AUSTRALIA_DARWIN => +34200,
			TimeZone::AUSTRALIA_EUCLA => +31500,
			TimeZone::AUSTRALIA_HOBART => +39600,
			TimeZone::AUSTRALIA_LINDEMAN => +36000,
			TimeZone::AUSTRALIA_LORD_HOWE => +39600,
			TimeZone::AUSTRALIA_MELBOURNE => +39600,
			TimeZone::AUSTRALIA_PERTH => +28800,
			TimeZone::AUSTRALIA_SYDNEY => +39600,
			TimeZone::EUROPE_AMSTERDAM => +7200,
			TimeZone::EUROPE_ANDORRA => +7200,
			TimeZone::EUROPE_ASTRAKHAN => +14400,
			TimeZone::EUROPE_ATHENS => +10800,
			TimeZone::EUROPE_BELGRADE => +7200,
			TimeZone::EUROPE_BERLIN => +7200,
			TimeZone::EUROPE_BRATISLAVA => +7200,
			TimeZone::EUROPE_BRUSSELS => +7200,
			TimeZone::EUROPE_BUCHAREST => +10800,
			TimeZone::EUROPE_BUDAPEST => +7200,
			TimeZone::EUROPE_BUSINGEN => +7200,
			TimeZone::EUROPE_CHISINAU => +10800,
			TimeZone::EUROPE_COPENHAGEN => +7200,
			TimeZone::EUROPE_DUBLIN => +3600,
			TimeZone::EUROPE_GIBRALTAR => +7200,
			TimeZone::EUROPE_GUERNSEY => +3600,
			TimeZone::EUROPE_HELSINKI => +10800,
			TimeZone::EUROPE_ISLE_OF_MAN => +3600,
			TimeZone::EUROPE_ISTANBUL => +10800,
			TimeZone::EUROPE_JERSEY => +3600,
			TimeZone::EUROPE_KALININGRAD => +7200,
			TimeZone::EUROPE_KIROV => +10800,
			TimeZone::EUROPE_KYIV => +10800,
			TimeZone::EUROPE_LISBON => +3600,
			TimeZone::EUROPE_LJUBLJANA => +7200,
			TimeZone::EUROPE_LONDON => +3600,
			TimeZone::EUROPE_LUXEMBOURG => +7200,
			TimeZone::EUROPE_MADRID => +7200,
			TimeZone::EUROPE_MALTA => +7200,
			TimeZone::EUROPE_MARIEHAMN => +10800,
			TimeZone::EUROPE_MINSK => +10800,
			TimeZone::EUROPE_MONACO => +7200,
			TimeZone::EUROPE_MOSCOW => +10800,
			TimeZone::EUROPE_OSLO => +7200,
			TimeZone::EUROPE_PARIS => +7200,
			TimeZone::EUROPE_PODGORICA => +7200,
			TimeZone::EUROPE_PRAGUE => +7200,
			TimeZone::EUROPE_RIGA => +10800,
			TimeZone::EUROPE_ROME => +7200,
			TimeZone::EUROPE_SAMARA => +14400,
			TimeZone::EUROPE_SAN_MARINO => +7200,
			TimeZone::EUROPE_SARAJEVO => +7200,
			TimeZone::EUROPE_SARATOV => +14400,
			TimeZone::EUROPE_SIMFEROPOL => +10800,
			TimeZone::EUROPE_SKOPJE => +7200,
			TimeZone::EUROPE_SOFIA => +10800,
			TimeZone::EUROPE_STOCKHOLM => +7200,
			TimeZone::EUROPE_TALLINN => +10800,
			TimeZone::EUROPE_TIRANE => +7200,
			TimeZone::EUROPE_ULYANOVSK => +14400,
			TimeZone::EUROPE_VADUZ => +7200,
			TimeZone::EUROPE_VATICAN => +7200,
			TimeZone::EUROPE_VIENNA => +7200,
			TimeZone::EUROPE_VILNIUS => +10800,
			TimeZone::EUROPE_VOLGOGRAD => +10800,
			TimeZone::EUROPE_WARSAW => +7200,
			TimeZone::EUROPE_ZAGREB => +7200,
			TimeZone::EUROPE_ZURICH => +7200,
			TimeZone::INDIAN_ANTANANARIVO => +10800,
			TimeZone::INDIAN_CHAGOS => +21600,
			TimeZone::INDIAN_CHRISTMAS => +25200,
			TimeZone::INDIAN_COCOS => +23400,
			TimeZone::INDIAN_COMORO => +10800,
			TimeZone::INDIAN_KERGUELEN => +18000,
			TimeZone::INDIAN_MAHE => +14400,
			TimeZone::INDIAN_MALDIVES => +18000,
			TimeZone::INDIAN_MAURITIUS => +14400,
			TimeZone::INDIAN_MAYOTTE => +10800,
			TimeZone::INDIAN_REUNION => +14400,
			TimeZone::PACIFIC_APIA => +46800,
			TimeZone::PACIFIC_AUCKLAND => +46800,
			TimeZone::PACIFIC_BOUGAINVILLE => +39600,
			TimeZone::PACIFIC_CHATHAM => +49500,
			TimeZone::PACIFIC_CHUUK => +36000,
			TimeZone::PACIFIC_EASTER => -18000,
			TimeZone::PACIFIC_EFATE => +39600,
			TimeZone::PACIFIC_FAKAOFO => +46800,
			TimeZone::PACIFIC_FIJI => +43200,
			TimeZone::PACIFIC_FUNAFUTI => +43200,
			TimeZone::PACIFIC_GALAPAGOS => -21600,
			TimeZone::PACIFIC_GAMBIER => 32400,
			TimeZone::PACIFIC_GUADALCANAL => +39600,
			TimeZone::PACIFIC_GUAM => +36000,
			TimeZone::PACIFIC_HONOLULU => 36000,
			TimeZone::PACIFIC_KANTON => +46800,
			TimeZone::PACIFIC_KIRITIMATI => +50400,
			TimeZone::PACIFIC_KOSRAE => +39600,
			TimeZone::PACIFIC_KWAJALEIN => +43200,
			TimeZone::PACIFIC_MAJURO => +43200,
			TimeZone::PACIFIC_MARQUESAS => 34200,
			TimeZone::PACIFIC_MIDWAY => 39600,
			TimeZone::PACIFIC_NAURU => +43200,
			TimeZone::PACIFIC_NIUE => 39600,
			TimeZone::PACIFIC_NORFOLK => +43200,
			TimeZone::PACIFIC_NOUMEA => +39600,
			TimeZone::PACIFIC_PAGO_PAGO => 39600,
			TimeZone::PACIFIC_PALAU => +32400,
			TimeZone::PACIFIC_PITCAIRN => 28800,
			TimeZone::PACIFIC_POHNPEI => +39600,
			TimeZone::PACIFIC_PORT_MORESBY => +36000,
			TimeZone::PACIFIC_RAROTONGA => 36000,
			TimeZone::PACIFIC_SAIPAN => +36000,
			TimeZone::PACIFIC_TAHITI => 36000,
			TimeZone::PACIFIC_TARAWA => +43200,
			TimeZone::PACIFIC_TONGATAPU => +46800,
			TimeZone::PACIFIC_WAKE => +43200,
			TimeZone::PACIFIC_WALLIS => +43200,
			TimeZone::UTC => 0,
		};
	}

	/**
	 * Get a relative day name for a given date.
	 *
	 * @param string $date The date to check.
	 * 
	 * @return string The relative day name ("Today", "Yesterday", "Tomorrow") or the date in 'Y-m-d' format.
	 */
	public static function getRelativeDayName(string $date): string
	{
		$today = date("Y-m-d");
		$target = date("Y-m-d", strtotime($date));
		if ($target === $today) {
			return "Today";
		} else if ($target === date("Y-m-d", strtotime("-1 day"))) {
			return "Yesterday";
		} else if ($target === date("Y-m-d", strtotime("+1 day"))) {
			return "Tomorrow";
		}

		return $target;
	}

	/**
	 * Get the difference between two dates in a specific unit.
	 *
	 * @param string $date1 The first date.
	 * @param string $date2 The second date.
	 * @param string $unit  The time unit ('seconds', 'minutes', 'hours', 'days', 'weeks', 'months', 'years').
	 * 
	 * @return int|float The calculated difference.
	 */
	public static function getDateDiff(string $date1, string $date2, string $unit = 'days'): int|float
	{
		$d1 = new DateTime($date1);
		$d2 = new DateTime($date2);
		$diff = $d1->diff($d2);

		return match ($unit) {
			'seconds' => abs($d2->getTimestamp() - $d1->getTimestamp()),
			'minutes' => abs($d2->getTimestamp() - $d1->getTimestamp()) / 60,
			'hours' => abs($d2->getTimestamp() - $d1->getTimestamp()) / 3600,
			'days' => $diff->days,
			'weeks' => floor($diff->days / 7),
			'months' => ($diff->y * 12) + $diff->m,
			'years' => $diff->y,
			default => $diff->days,
		};
	}

	/**
	 * Modify a date using a given modifier string.
	 *
	 * @param string $date     The original date string.
	 * @param string $modifier The modification relative format (e.g., '+1 day').
	 * @param string $format   The output date format.
	 * 
	 * @return string The modified date string.
	 */
	public static function modify(string $date, string $modifier, string $format = 'Y-m-d H:i:s'): string
	{
		$dt = new DateTime($date);
		$dt->modify($modifier);
		return $dt->format($format);
	}

	/**
	 * Check if the first date is before the second date.
	 *
	 * @param string $date1 The first date.
	 * @param string $date2 The second date.
	 * 
	 * @return bool True if $date1 is strictly before $date2.
	 */
	public static function isBefore(string $date1, string $date2): bool
	{
		return new DateTime($date1) < new DateTime($date2);
	}

	/**
	 * Check if the first date is after the second date.
	 *
	 * @param string $date1 The first date.
	 * @param string $date2 The second date.
	 * 
	 * @return bool True if $date1 is strictly after $date2.
	 */
	public static function isAfter(string $date1, string $date2): bool
	{
		return new DateTime($date1) > new DateTime($date2);
	}

	/**
	 * Check if a date falls between a start and end date (inclusive).
	 *
	 * @param string $date  The date to check.
	 * @param string $start The start date.
	 * @param string $end   The end date.
	 * 
	 * @return bool True if $date is between $start and $end.
	 */
	public static function isBetween(string $date, string $start, string $end): bool
	{
		$dt = new DateTime($date);
		return $dt >= new DateTime($start) && $dt <= new DateTime($end);
	}

	/**
	 * Check if a date is in the past compared to the current date and time.
	 *
	 * @param string $date The date to check.
	 * 
	 * @return bool True if the date is in the past.
	 */
	public static function isPast(string $date): bool
	{
		return new DateTime($date) < new DateTime();
	}

	/**
	 * Check if a date is in the future compared to the current date and time.
	 *
	 * @param string $date The date to check.
	 * 
	 * @return bool True if the date is in the future.
	 */
	public static function isFuture(string $date): bool
	{
		return new DateTime($date) > new DateTime();
	}

	/**
	 * Check if two dates represent the same calendar day.
	 *
	 * @param string $date1 The first date.
	 * @param string $date2 The second date.
	 * 
	 * @return bool True if both dates fall on the same day.
	 */
	public static function isSameDay(string $date1, string $date2): bool
	{
		return (new DateTime($date1))->format('Y-m-d') === (new DateTime($date2))->format('Y-m-d');
	}

	/**
	 * Check if a date is a business day (not a weekend and not a holiday).
	 *
	 * @param string $date     The date to check.
	 * @param array  $holidays An array of holiday dates in 'Y-m-d' format.
	 * 
	 * @return bool True if the date is a business day.
	 */
	public static function isBusinessDay(string $date, array $holidays = []): bool
	{
		$dt = new DateTime($date);
		$dayOfWeek = (int) $dt->format(DateStringFormat::DayOfWeekIso8601Numeric->value);
		if ($dayOfWeek >= 6) {
			return false;
		}

		return !in_array($dt->format('Y-m-d'), $holidays, true);
	}

	/**
	 * Add or subtract a specific number of business days to/from a date.
	 *
	 * @param string $date     The starting date.
	 * @param int    $days     The number of business days to add (can be negative).
	 * @param array  $holidays An array of holiday dates in 'Y-m-d' format.
	 * 
	 * @return string The resulting date after adding business days.
	 */
	public static function addBusinessDays(string $date, int $days, array $holidays = []): string
	{
		$dt = new DateTime($date);
		$added = 0;
		$direction = $days >= 0 ? '+1 day' : '-1 day';
		$remaining = abs($days);

		while ($added < $remaining) {
			$dt->modify($direction);
			if (self::isBusinessDay($dt->format('Y-m-d'), $holidays)) {
				$added++;
			}
		}

		return $dt->format('Y-m-d');
	}

	/**
	 * Get the number of business days between two dates.
	 *
	 * @param string $start    The start date.
	 * @param string $end      The end date.
	 * @param array  $holidays An array of holiday dates in 'Y-m-d' format.
	 * 
	 * @return int The count of business days.
	 */
	public static function getBusinessDaysBetween(string $start, string $end, array $holidays = []): int
	{
		$count = 0;
		$current = new DateTime($start);
		$endDt = new DateTime($end);

		if ($current > $endDt) {
			throw new RuntimeException('End date must be greater than start date.');
		}

		while ($current <= $endDt) {
			if (self::isBusinessDay($current->format('Y-m-d'), $holidays)) {
				$count++;
			}
			$current->modify('+1 day');
		}

		return $count;
	}

	/**
	 * Convert a date from one timezone to another.
	 *
	 * @param string $date   The original date string.
	 * @param string $fromTz The source timezone identifier.
	 * @param string $toTz   The target timezone identifier.
	 * @param string $format The output format.
	 * 
	 * @return string The converted date string.
	 */
	public static function convertTimezone(string $date, string $fromTz, string $toTz, string $format = 'Y-m-d H:i:s'): string
	{
		$dt = new DateTime($date, new DateTimeZone($fromTz));
		$dt->setTimezone(new DateTimeZone($toTz));
		return $dt->format($format);
	}

	/**
	 * Check if a given string is a valid date according to a format.
	 *
	 * @param string $date   The date string to check.
	 * @param string $format The expected date format.
	 * 
	 * @return bool True if valid, false otherwise.
	 */
	public static function isValidDate(string $date, string $format = 'Y-m-d'): bool
	{
		$dt = DateTime::createFromFormat($format, $date);
		return $dt !== false && $dt->format($format) === $date;
	}

	/**
	 * Create a DateTime object from a specific format.
	 *
	 * @param string      $format   The expected date format.
	 * @param string      $date     The input date string.
	 * @param string|null $timezone The optional timezone identifier.
	 * 
	 * @return DateTime|false The created DateTime object or false on failure.
	 */
	public static function createFromFormat(string $format, string $date, ?string $timezone = null): DateTime|false
	{
		$tz = $timezone ? new DateTimeZone($timezone) : null;
		return DateTime::createFromFormat($format, $date, $tz);
	}

	/**
	 * Convert a date string to an ISO-8601 formatted string.
	 *
	 * @param string      $date     The input date string.
	 * @param string|null $timezone The optional timezone identifier.
	 * 
	 * @return string The ISO-8601 formatted date string.
	 */
	public static function toISO8601(string $date, ?string $timezone = null): string
	{
		$dt = new DateTime($date);
		if ($timezone) {
			$dt->setTimezone(new DateTimeZone($timezone));
		}

		return $dt->format(DateTime::ATOM);
	}

	/**
	 * Convert a date string to an RFC 2822 formatted string.
	 *
	 * @param string      $date     The input date string.
	 * @param string|null $timezone The optional timezone identifier.
	 * 
	 * @return string The RFC 2822 formatted date string.
	 */
	public static function toRFC2822(string $date, ?string $timezone = null): string
	{
		$dt = new DateTime($date);
		if ($timezone) {
			$dt->setTimezone(new DateTimeZone($timezone));
		}

		return $dt->format(DateTime::RFC2822);
	}

	/**
	 * Convert a date string to an RFC 3339 formatted string.
	 *
	 * @param string      $date     The input date string.
	 * @param string|null $timezone The optional timezone identifier.
	 * 
	 * @return string The RFC 3339 formatted date string.
	 */
	public static function toRFC3339(string $date, ?string $timezone = null): string
	{
		$dt = new DateTime($date);
		if ($timezone) {
			$dt->setTimezone(new DateTimeZone($timezone));
		}

		return $dt->format(DateTime::RFC3339_EXTENDED);
	}

	/**
	 * Convert a date string to a Unix timestamp.
	 *
	 * @param string $date The input date string.
	 * 
	 * @return int The Unix timestamp.
	 */
	public static function toUnixTimestamp(string $date): int
	{
		return (new DateTime($date))->getTimestamp();
	}

	/**
	 * Format a date string into a specific format.
	 *
	 * @param string      $date     The input date string.
	 * @param string      $format   The specified format.
	 * @param string|null $timezone The optional timezone identifier.
	 * 
	 * @return string The formatted date string.
	 */
	public static function format(string $date, string $format, ?string $timezone = null): string
	{
		$dt = new DateTime($date);
		if ($timezone) {
			$dt->setTimezone(new DateTimeZone($timezone));
		}

		return $dt->format($format);
	}

	/**
	 * Get a human-readable relative time string (e.g., "2 hours ago", "in 3 days").
	 *
	 * @param string $date The target date string.
	 * 
	 * @return string The relative time string.
	 */
	public static function getRelativeTime(string $date): string
	{
		$now = new DateTime();
		$target = new DateTime($date);
		$diff = $now->diff($target);
		$isPast = $target < $now;

		$units = [
			'y' => ['year', 'years'],
			'm' => ['month', 'months'],
			'd' => ['day', 'days'],
			'h' => ['hour', 'hours'],
			'i' => ['minute', 'minutes'],
			's' => ['second', 'seconds'],
		];

		foreach ($units as $key => $labels) {
			$value = $diff->$key;
			if ($value > 0) {
				$label = $value === 1 ? $labels[0] : $labels[1];
				return $isPast ? "{$value} {$label} ago" : "in {$value} {$label}";
			}
		}

		return 'just now';
	}

	/**
	 * Get the start date of the month for a given date.
	 *
	 * @param string $date The input date string.
	 * 
	 * @return string The start date of the month (Y-m-d).
	 */
	public static function getStartOfMonth(string $date): string
	{
		return (new DateTime($date))->modify('first day of this month')->format('Y-m-d');
	}

	/**
	 * Get the end date of the month for a given date.
	 *
	 * @param string $date The input date string.
	 * 
	 * @return string The end date of the month (Y-m-d).
	 */
	public static function getEndOfMonth(string $date): string
	{
		return (new DateTime($date))->modify('last day of this month')->format('Y-m-d');
	}

	/**
	 * Get the start date of the year for a given date.
	 *
	 * @param string $date The input date string.
	 * 
	 * @return string The start date of the year (Y-01-01).
	 */
	public static function getStartOfYear(string $date): string
	{
		return (new DateTime($date))->format(DateStringFormat::YearFullNumeric->value) . '-01-01';
	}

	/**
	 * Get the end date of the year for a given date.
	 *
	 * @param string $date The input date string.
	 * 
	 * @return string The end date of the year (Y-12-31).
	 */
	public static function getEndOfYear(string $date): string
	{
		return (new DateTime($date))->format(DateStringFormat::YearFullNumeric->value) . '-12-31';
	}

	/**
	 * Get the datetime representing the start of the day.
	 *
	 * @param string $date The input date string.
	 * 
	 * @return string The start of the day datetime (Y-m-d 00:00:00).
	 */
	public static function getStartOfDay(string $date): string
	{
		return (new DateTime($date))->format('Y-m-d') . ' 00:00:00';
	}

	/**
	 * Get the datetime representing the end of the day.
	 *
	 * @param string $date The input date string.
	 * 
	 * @return string The end of the day datetime (Y-m-d 23:59:59).
	 */
	public static function getEndOfDay(string $date): string
	{
		return (new DateTime($date))->format('Y-m-d') . ' 23:59:59';
	}

	/**
	 * Get the date of the Nth specific weekday of a given month and year.
	 * For example, the 2nd Monday of January 2024.
	 *
	 * @param int $year    The year.
	 * @param int $month   The month.
	 * @param int $nth     Which occurrence (e.g., 1 for first, 2 for second). Negative gets last.
	 * @param int $weekday The target weekday (0 for Sunday).
	 * 
	 * @return string The resulting date in 'Y-m-d' format.
	 */
	public static function getNthWeekdayOfMonth(int $year, int $month, int $nth, int $weekday): string
	{
		$dayNames = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
		$dayName = $dayNames[$weekday % 7];

		if ($nth > 0) {
			$dt = new DateTime("{$year}-{$month}-01");
			$ordinal = match ($nth) {
				1 => 'first',
				2 => 'second',
				3 => 'third',
				4 => 'fourth',
				5 => 'fifth',
				default => 'first',
			};
			$dt->modify("{$ordinal} {$dayName} of this month");
		} else {
			$dt = new DateTime("{$year}-{$month}-01");
			$dt->modify('last day of this month');
			$dt->modify("last {$dayName}");
		}

		return $dt->format('Y-m-d');
	}

	/**
	 * Get the total number of ISO weeks in a specific year.
	 *
	 * @param int $year The year to check.
	 * 
	 * @return int The number of weeks (usually 52 or 53).
	 */
	public static function getWeeksInYear(int $year): int
	{
		$dt = new DateTime("{$year}-12-28");
		return (int) $dt->format(DateStringFormat::WeekNumberIso8601->value);
	}

	/**
	 * Get the total number of days in a given year.
	 *
	 * @param int $year The year to check.
	 * 
	 * @return int The number of days (365 or 366).
	 */
	public static function getDaysInYear(int $year): int
	{
		return self::isLeapYear($year) === true ? 366 : 365;
	}

	/**
	 * Calculate the date of Easter Sunday for a given year.
	 * Uses the Computus algorithm.
	 *
	 * @param int $year The year to calculate for.
	 * 
	 * @return string The date of Easter in 'Y-m-d' format.
	 */
	public static function getEasterDate(int $year): string
	{
		$a = $year % 19;
		$b = intdiv($year, 100);
		$c = $year % 100;
		$d = intdiv($b, 4);
		$e = $b % 4;
		$f = intdiv($b + 8, 25);
		$g = intdiv($b - $f + 1, 3);
		$h = (19 * $a + $b - $d - $g + 15) % 30;
		$i = intdiv($c, 4);
		$k = $c % 4;
		$l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
		$m = intdiv($a + 11 * $h + 22 * $l, 451);
		$month = intdiv($h + $l - 7 * $m + 114, 31);
		$day = (($h + $l - 7 * $m + 114) % 31) + 1;

		return sprintf('%04d-%02d-%02d', $year, $month, $day);
	}

	/**
	 * Get a list of all months between two dates.
	 *
	 * @param string $start The start date.
	 * @param string $end   The end date.
	 * 
	 * @return array List of months in 'Y-m' format.
	 */
	public static function getMonthsBetween(string $start, string $end): array
	{
		$startDt = new DateTime(self::getStartOfMonth($start));
		$endDt = new DateTime(self::getStartOfMonth($end));
		$months = [];

		while ($startDt <= $endDt) {
			$months[] = $startDt->format('Y-m');
			$startDt->modify('+1 month');
		}

		return $months;
	}

	/**
	 * Get a list of all weeks between two dates.
	 *
	 * @param string $start The start date.
	 * @param string $end   The end date.
	 * 
	 * @return array List of arrays containing 'start', 'end', and 'week'.
	 */
	public static function getWeeksBetween(string $start, string $end): array
	{
		$current = new DateTime(self::getStartOfWeek($start));
		$endDt = new DateTime($end);
		$weeks = [];

		while ($current <= $endDt) {
			$weekEnd = clone $current;
			$weekEnd->modify('+6 days');
			$weeks[] = [
				'start' => $current->format('Y-m-d'),
				'end' => $weekEnd->format('Y-m-d'),
				'week' => $current->format('o-W'),
			];
			$current->modify('+7 days');
		}

		return $weeks;
	}

	/**
	 * Get the fiscal quarter for a given date.
	 *
	 * @param string $date                 The input date string.
	 * @param int    $fiscalYearStartMonth The month the fiscal year starts (default 4 for April).
	 * 
	 * @return int The fiscal quarter (1-4).
	 */
	public static function getFiscalQuarter(string $date, int $fiscalYearStartMonth = 4): int
	{
		$month = (int) (new DateTime($date))->format(DateStringFormat::MonthNumericWithoutLeadingZeros->value);
		$adjusted = ($month - $fiscalYearStartMonth + 12) % 12;
		return (int) floor($adjusted / 3) + 1;
	}

	/**
	 * Get the fiscal year for a given date.
	 *
	 * @param string $date                 The input date string.
	 * @param int    $fiscalYearStartMonth The month the fiscal year starts (default 4 for April).
	 * 
	 * @return int The fiscal year.
	 */
	public static function getFiscalYear(string $date, int $fiscalYearStartMonth = 4): int
	{
		$dt = new DateTime($date);
		$year = (int) $dt->format(DateStringFormat::YearFullNumeric->value);
		$month = (int) $dt->format(DateStringFormat::MonthNumericWithoutLeadingZeros->value);
		return $month >= $fiscalYearStartMonth ? $year : $year - 1;
	}

	/**
	 * Calculate a settlement date by adding business days to a trade date.
	 *
	 * @param string $tradeDate      The trade date.
	 * @param int    $settlementDays The number of days to add (default 2).
	 * @param array  $holidays       Optional holiday dates to skip.
	 * 
	 * @return string The calculated settlement date in 'Y-m-d' format.
	 */
	public static function getSettlementDate(string $tradeDate, int $settlementDays = 2, array $holidays = []): string
	{
		return self::addBusinessDays($tradeDate, $settlementDays, $holidays);
	}

	/**
	 * Generate recurring dates within a specific period.
	 *
	 * @param string $start    The start date.
	 * @param string $end      The end date.
	 * @param string $interval The interval modification string (e.g., '1 month', '2 weeks').
	 * 
	 * @return array An array of recurring dates in 'Y-m-d' format.
	 */
	public static function getRecurringDates(string $start, string $end, string $interval): array
	{
		$dates = [];
		$current = new DateTime($start);
		$endDt = new DateTime($end);

		while ($current <= $endDt) {
			$dates[] = $current->format('Y-m-d');
			$current->modify("+{$interval}");
		}

		return $dates;
	}

	/**
	 * Get the day of the year (1-365/366) for a given date.
	 *
	 * @param string $date The input date string.
	 * 
	 * @return int The day of the year.
	 */
	public static function getDayOfYear(string $date): int
	{
		return (int) (new DateTime($date))->format(DateStringFormat::DayOfYear->value) + 1;
	}

	/**
	 * Get the number of remaining days in the year for a given date.
	 *
	 * @param string $date The input date string.
	 * 
	 * @return int The number of remaining days.
	 */
	public static function getRemainingDaysInYear(string $date): int
	{
		$dt = new DateTime($date);
		$year = (int) $dt->format(DateStringFormat::YearFullNumeric->value);
		return self::getDaysInYear($year) - self::getDayOfYear($date);
	}

	/**
	 * Get the number of remaining days in the month for a given date.
	 *
	 * @param string $date The input date string.
	 * 
	 * @return int The number of remaining days.
	 */
	public static function getRemainingDaysInMonth(string $date): int
	{
		$dt = new DateTime($date);
		return (int) $dt->format(DateStringFormat::MonthNumberOfDays->value) - (int) $dt->format(DateStringFormat::DayOfMonthWithoutLeadingZeros->value);
	}

	/**
	 * Get today's date formatted as a string.
	 *
	 * @param string $format The output format (default 'Y-m-d').
	 * 
	 * @return string Today's date.
	 */
	public static function getToday(string $format = 'Y-m-d'): string
	{
		return date($format);
	}

	/**
	 * Get the current date and time.
	 *
	 * @param string      $format   The output format (default 'Y-m-d H:i:s').
	 * @param string|null $timezone The optional timezone identifier.
	 * 
	 * @return string The current date and time.
	 */
	public static function now(string $format = 'Y-m-d H:i:s', ?string $timezone = null): string
	{
		$dt = new DateTime('now');
		if ($timezone) {
			$dt->setTimezone(new DateTimeZone($timezone));
		}

		return $dt->format($format);
	}

	/**
	 * Clamp a given date between a minimum and maximum date.
	 *
	 * @param string $date The input date string.
	 * @param string $min  The minimum allowed date.
	 * @param string $max  The maximum allowed date.
	 * 
	 * @return string The clamped date in 'Y-m-d H:i:s' format.
	 */
	public static function clamp(string $date, string $min, string $max): string
	{
		$dt = new DateTime($date);
		$minDt = new DateTime($min);
		$maxDt = new DateTime($max);

		if ($dt < $minDt) {
			return $minDt->format('Y-m-d H:i:s');
		}

		if ($dt > $maxDt) {
			return $maxDt->format('Y-m-d H:i:s');
		}

		return $dt->format('Y-m-d H:i:s');
	}

	/**
	 * Get the earliest (minimum) date among the given dates.
	 *
	 * @param string ...$dates A variable number of date strings.
	 * 
	 * @return string The earliest date in 'Y-m-d H:i:s' format.
	 */
	public static function min(string ...$dates): string
	{
		$timestamps = array_map(fn(string $d) => (new DateTime($d))->getTimestamp(), $dates);
		$minIndex = array_keys($timestamps, min($timestamps))[0];
		return (new DateTime($dates[$minIndex]))->format('Y-m-d H:i:s');
	}

	/**
	 * Get the latest (maximum) date among the given dates.
	 *
	 * @param string ...$dates A variable number of date strings.
	 * 
	 * @return string The latest date in 'Y-m-d H:i:s' format.
	 */
	public static function max(string ...$dates): string
	{
		$timestamps = array_map(fn(string $d) => (new DateTime($d))->getTimestamp(), $dates);
		$maxIndex = array_keys($timestamps, max($timestamps))[0];
		return (new DateTime($dates[$maxIndex]))->format('Y-m-d H:i:s');
	}

	/**
	 * Check if two date periods overlap.
	 *
	 * @param string $start1 The start date of the first period.
	 * @param string $end1   The end date of the first period.
	 * @param string $start2 The start date of the second period.
	 * @param string $end2   The end date of the second period.
	 * 
	 * @return bool True if the periods overlap, false otherwise.
	 */
	public static function overlap(string $start1, string $end1, string $start2, string $end2): bool
	{
		return new DateTime($start1) <= new DateTime($end2) && new DateTime($start2) <= new DateTime($end1);
	}

	/**
	 * Get the overlapping period between two date ranges.
	 *
	 * @param string $start1 The start date of the first period.
	 * @param string $end1   The end date of the first period.
	 * @param string $start2 The start date of the second period.
	 * @param string $end2   The end date of the second period.
	 * 
	 * @return array|null An array with 'start' and 'end' keys representing the overlap, or null if no overlap.
	 */
	public static function getOverlapPeriod(string $start1, string $end1, string $start2, string $end2): ?array
	{
		if (!self::overlap($start1, $end1, $start2, $end2)) {
			return null;
		}

		return [
			'start' => self::max($start1, $start2),
			'end' => self::min($end1, $end2),
		];
	}

	/**
	 * Check if a date falls on a weekday (Monday to Friday).
	 *
	 * @param string $date The input date string.
	 * 
	 * @return bool True if it's a weekday, false if it's a weekend.
	 */
	public static function isWeekday(string $date): bool
	{
		return !self::isWeekend($date);
	}

	/**
	 * Get the quarter of the year for a given date.
	 *
	 * @param string $date The input date string.
	 * 
	 * @return int The quarter (1-4).
	 */
	public static function getQuarterOfDate(string $date): int
	{
		return (int) ceil((int) (new DateTime($date))->format(DateStringFormat::MonthNumericWithoutLeadingZeros->value) / 3);
	}

	/**
	 * Produce a descriptive human-readable difference between two dates.
	 * For example, "1 year, 2 months, 5 days".
	 *
	 * @param string $date1 The first date.
	 * @param string $date2 The second date.
	 * 
	 * @return string The human-readable difference string.
	 */
	public static function diffForHumans(string $date1, string $date2): string
	{
		$d1 = new DateTime($date1);
		$d2 = new DateTime($date2);
		$diff = $d1->diff($d2);
		$parts = [];

		if ($diff->y > 0) {
			$parts[] = $diff->y . ($diff->y === 1 ? ' year' : ' years');
		}

		if ($diff->m > 0) {
			$parts[] = $diff->m . ($diff->m === 1 ? ' month' : ' months');
		}

		if ($diff->d > 0) {
			$parts[] = $diff->d . ($diff->d === 1 ? ' day' : ' days');
		}

		if ($diff->h > 0) {
			$parts[] = $diff->h . ($diff->h === 1 ? ' hour' : ' hours');
		}

		if ($diff->i > 0) {
			$parts[] = $diff->i . ($diff->i === 1 ? ' minute' : ' minutes');
		}

		if (empty($parts)) {
			return $diff->s . ($diff->s === 1 ? ' second' : ' seconds');
		}

		return implode(', ', $parts);
	}

	/**
	 * Add a given number of days to a date.
	 *
	 * @param string $date   The starting date string.
	 * @param int    $days   Number of days to add (negative to subtract).
	 * @param string $format The output date format (default 'Y-m-d').
	 * 
	 * @return string The resulting date.
	 */
	public static function addDays(string $date, int $days, string $format = 'Y-m-d'): string
	{
		$dt = new DateTime($date);
		$dt->modify("{$days} days");
		return $dt->format($format);
	}

	/**
	 * Add a given number of months to a date.
	 *
	 * @param string $date   The starting date string.
	 * @param int    $months Number of months to add (negative to subtract).
	 * @param string $format The output date format (default 'Y-m-d').
	 * 
	 * @return string The resulting date.
	 */
	public static function addMonths(string $date, int $months, string $format = 'Y-m-d'): string
	{
		$dt = new DateTime($date);
		$dt->modify("{$months} months");
		return $dt->format($format);
	}

	/**
	 * Add a given number of years to a date.
	 *
	 * @param string $date  The starting date string.
	 * @param int    $years Number of years to add (negative to subtract).
	 * @param string $format The output date format (default 'Y-m-d').
	 * 
	 * @return string The resulting date.
	 */
	public static function addYears(string $date, int $years, string $format = 'Y-m-d'): string
	{
		$dt = new DateTime($date);
		$dt->modify("{$years} years");
		return $dt->format($format);
	}

	/**
	 * Add a given number of weeks to a date.
	 *
	 * @param string $date   The starting date string.
	 * @param int    $weeks  Number of weeks to add (negative to subtract).
	 * @param string $format The output date format (default 'Y-m-d').
	 * 
	 * @return string The resulting date.
	 */
	public static function addWeeks(string $date, int $weeks, string $format = 'Y-m-d'): string
	{
		return self::addDays($date, $weeks * 7, $format);
	}

	/**
	 * Add a given number of hours to a datetime.
	 *
	 * @param string $date   The starting datetime string.
	 * @param int    $hours  Number of hours to add (negative to subtract).
	 * @param string $format The output format (default 'Y-m-d H:i:s').
	 * 
	 * @return string The resulting datetime.
	 */
	public static function addHours(string $date, int $hours, string $format = 'Y-m-d H:i:s'): string
	{
		$dt = new DateTime($date);
		$dt->modify("{$hours} hours");
		return $dt->format($format);
	}

	/**
	 * Add a given number of minutes to a datetime.
	 *
	 * @param string $date    The starting datetime string.
	 * @param int    $minutes Number of minutes to add (negative to subtract).
	 * @param string $format  The output format (default 'Y-m-d H:i:s').
	 * 
	 * @return string The resulting datetime.
	 */
	public static function addMinutes(string $date, int $minutes, string $format = 'Y-m-d H:i:s'): string
	{
		$dt = new DateTime($date);
		$dt->modify("{$minutes} minutes");
		return $dt->format($format);
	}

	/**
	 * Add a given number of seconds to a datetime.
	 *
	 * @param string $date    The starting datetime string.
	 * @param int    $seconds Number of seconds to add (negative to subtract).
	 * @param string $format  The output format (default 'Y-m-d H:i:s').
	 * 
	 * @return string The resulting datetime.
	 */
	public static function addSeconds(string $date, int $seconds, string $format = 'Y-m-d H:i:s'): string
	{
		$dt = new DateTime($date);
		$dt->modify("{$seconds} seconds");
		return $dt->format($format);
	}

	/**
	 * Subtract a given number of days from a date.
	 *
	 * @param string $date   The starting date string.
	 * @param int    $days   Number of days to subtract.
	 * @param string $format The output date format (default 'Y-m-d').
	 * 
	 * @return string The resulting date.
	 */
	public static function subtractDays(string $date, int $days, string $format = 'Y-m-d'): string
	{
		return self::addDays($date, -$days, $format);
	}

	/**
	 * Subtract a given number of months from a date.
	 *
	 * @param string $date   The starting date string.
	 * @param int    $months Number of months to subtract.
	 * @param string $format The output date format (default 'Y-m-d').
	 * 
	 * @return string The resulting date.
	 */
	public static function subtractMonths(string $date, int $months, string $format = 'Y-m-d'): string
	{
		return self::addMonths($date, -$months, $format);
	}

	/**
	 * Subtract a given number of years from a date.
	 *
	 * @param string $date  The starting date string.
	 * @param int    $years Number of years to subtract.
	 * @param string $format The output date format (default 'Y-m-d').
	 * 
	 * @return string The resulting date.
	 */
	public static function subtractYears(string $date, int $years, string $format = 'Y-m-d'): string
	{
		return self::addYears($date, -$years, $format);
	}

	/**
	 * Get the full month name for a given date (e.g. "January").
	 *
	 * @param string $date The input date string.
	 * 
	 * @return string The full English month name.
	 */
	public static function getMonthName(string $date): string
	{
		return (new DateTime($date))->format(DateStringFormat::MonthFullTextual->value);
	}

	/**
	 * Get the abbreviated month name for a given date (e.g. "Jan").
	 *
	 * @param string $date The input date string.
	 * 
	 * @return string The 3-letter English month name.
	 */
	public static function getShortMonthName(string $date): string
	{
		return (new DateTime($date))->format(DateStringFormat::MonthShortThreeLetters->value);
	}

	/**
	 * Get the number of days in the month of a given date.
	 *
	 * @param string $date The input date string.
	 * 
	 * @return int The number of days (28-31).
	 */
	public static function getDaysInMonth(string $date): int
	{
		return (int) (new DateTime($date))->format(DateStringFormat::MonthNumberOfDays->value);
	}

	/**
	 * Get the ISO week number for a given date as an integer.
	 *
	 * @param string $date The input date string.
	 * 
	 * @return int The ISO week number (1-53).
	 */
	public static function getWeekNumber(string $date): int
	{
		return (int) (new DateTime($date))->format(DateStringFormat::WeekNumberIso8601->value);
	}

	/**
	 * Get the day of the week (0=Sunday, 6=Saturday) for a given date string.
	 *
	 * @param string $date The input date string.
	 * 
	 * @return int The day of the week.
	 */
	public static function getDayOfWeekFromDate(string $date): int
	{
		return (int) (new DateTime($date))->format(DateStringFormat::DayOfWeekNumeric->value);
	}

	/**
	 * Get the full weekday name for a given date (e.g. "Monday").
	 *
	 * @param string $date The input date string.
	 * 
	 * @return string The full English weekday name.
	 */
	public static function getDayNameFromDate(string $date): string
	{
		return (new DateTime($date))->format(DateStringFormat::DayOfWeekFullTextual->value);
	}

	/**
	 * Get the year component from a date string.
	 *
	 * @param string $date The input date string.
	 * 
	 * @return int The year.
	 */
	public static function getYearFromDate(string $date): int
	{
		return (int) (new DateTime($date))->format(DateStringFormat::YearFullNumeric->value);
	}

	/**
	 * Get the month component from a date string.
	 *
	 * @param string $date The input date string.
	 * 
	 * @return int The month (1-12).
	 */
	public static function getMonthFromDate(string $date): int
	{
		return (int) (new DateTime($date))->format(DateStringFormat::MonthNumericWithoutLeadingZeros->value);
	}

	/**
	 * Get the day component from a date string.
	 *
	 * @param string $date The input date string.
	 * 
	 * @return int The day of the month (1-31).
	 */
	public static function getDayFromDate(string $date): int
	{
		return (int) (new DateTime($date))->format(DateStringFormat::DayOfMonthWithoutLeadingZeros->value);
	}

	/**
	 * Check if a given date's year is a leap year.
	 *
	 * @param string $date The input date string.
	 * 
	 * @return bool True if the year of the given date is a leap year.
	 */
	public static function isLeapYearFromDate(string $date): bool
	{
		$year = (int) (new DateTime($date))->format(DateStringFormat::YearFullNumeric->value);
		return self::isLeapYear($year) === true;
	}

	/**
	 * Get a person's age in complete years from their birthdate.
	 *
	 * @param string $birthdate The birthdate in any parseable format.
	 * 
	 * @return int The age in full years.
	 */
	public static function getAge(string $birthdate): int
	{
		return (new DateTime($birthdate))->diff(new DateTime())->y;
	}

	/**
	 * Get the timezone abbreviation for a given IANA timezone identifier.
	 *
	 * @param string      $timezone  The IANA timezone identifier (e.g. "America/New_York").
	 * @param string|null $date      Optional date context for DST-aware abbreviation.
	 * 
	 * @return string The timezone abbreviation (e.g. "EST", "PDT").
	 */
	public static function getTimezoneAbbreviation(string $timezone, ?string $date = null): string
	{
		$dt = new DateTime($date ?? 'now', new DateTimeZone($timezone));
		return $dt->format(DateStringFormat::TimezoneAbbreviation->value);
	}

	/**
	 * Create a formatted date string from a Unix timestamp.
	 *
	 * @param int    $timestamp The Unix timestamp.
	 * @param string $format    The output format (default 'Y-m-d H:i:s').
	 * 
	 * @return string The formatted date.
	 */
	public static function fromTimestamp(int $timestamp, string $format = 'Y-m-d H:i:s'): string
	{
		return (new DateTime('@' . $timestamp))->format($format);
	}

	/**
	 * Check if two dates fall within the same month and year.
	 *
	 * @param string $date1 The first date.
	 * @param string $date2 The second date.
	 * 
	 * @return bool True if both dates are in the same month and year.
	 */
	public static function isSameMonth(string $date1, string $date2): bool
	{
		$d1 = new DateTime($date1);
		$d2 = new DateTime($date2);
		return $d1->format('Y-m') === $d2->format('Y-m');
	}

	/**
	 * Check if two dates fall within the same year.
	 *
	 * @param string $date1 The first date.
	 * @param string $date2 The second date.
	 * 
	 * @return bool True if both dates are in the same year.
	 */
	public static function isSameYear(string $date1, string $date2): bool
	{
		return (new DateTime($date1))->format(DateStringFormat::YearFullNumeric->value) === (new DateTime($date2))->format(DateStringFormat::YearFullNumeric->value);
	}

	/**
	 * Check if two dates fall within the same ISO week.
	 *
	 * @param string $date1 The first date.
	 * @param string $date2 The second date.
	 * 
	 * @return bool True if both dates are in the same ISO week.
	 */
	public static function isSameWeek(string $date1, string $date2): bool
	{
		return (new DateTime($date1))->format('o-W') === (new DateTime($date2))->format('o-W');
	}

	/**
	 * Check if two dates fall within the same quarter of the same year.
	 *
	 * @param string $date1 The first date.
	 * @param string $date2 The second date.
	 * 
	 * @return bool True if both dates are in the same quarter.
	 */
	public static function isSameQuarter(string $date1, string $date2): bool
	{
		$d1 = new DateTime($date1);
		$d2 = new DateTime($date2);
		return $d1->format(DateStringFormat::YearFullNumeric->value) === $d2->format(DateStringFormat::YearFullNumeric->value) && self::getQuarterOfDate($date1) === self::getQuarterOfDate($date2);
	}

	/**
	 * Get the difference between two dates in hours.
	 *
	 * @param string $date1 The first datetime.
	 * @param string $date2 The second datetime.
	 * 
	 * @return float The absolute difference in hours.
	 */
	public static function diffInHours(string $date1, string $date2): float
	{
		return abs((new DateTime($date1))->getTimestamp() - (new DateTime($date2))->getTimestamp()) / 3600;
	}

	/**
	 * Get the difference between two dates in minutes.
	 *
	 * @param string $date1 The first datetime.
	 * @param string $date2 The second datetime.
	 * 
	 * @return float The absolute difference in minutes.
	 */
	public static function diffInMinutes(string $date1, string $date2): float
	{
		return abs((new DateTime($date1))->getTimestamp() - (new DateTime($date2))->getTimestamp()) / 60;
	}

	/**
	 * Get the difference between two dates in seconds.
	 *
	 * @param string $date1 The first datetime.
	 * @param string $date2 The second datetime.
	 * 
	 * @return int The absolute difference in seconds.
	 */
	public static function diffInSeconds(string $date1, string $date2): int
	{
		return abs((new DateTime($date1))->getTimestamp() - (new DateTime($date2))->getTimestamp());
	}

	/**
	 * Get the difference between two dates in weeks.
	 *
	 * @param string $date1 The first date.
	 * @param string $date2 The second date.
	 * 
	 * @return int The number of complete weeks between the two dates.
	 */
	public static function diffInWeeks(string $date1, string $date2): int
	{
		return (int) floor(self::getDateDiffInDays($date1, $date2) / 7);
	}

	/**
	 * Get the difference between two dates in months.
	 *
	 * @param string $date1 The first date.
	 * @param string $date2 The second date.
	 * 
	 * @return int The total number of months difference.
	 */
	public static function diffInMonths(string $date1, string $date2): int
	{
		$diff = (new DateTime($date1))->diff(new DateTime($date2));
		return ($diff->y * 12) + $diff->m;
	}

	/**
	 * Get the next occurrence of a specific weekday on or after a given date.
	 *
	 * @param string $date    The starting date.
	 * @param int    $weekday Target weekday (0=Sunday, 6=Saturday).
	 * 
	 * @return string The next occurrence in 'Y-m-d' format.
	 */
	public static function getNextWeekday(string $date, int $weekday): string
	{
		$dayNames = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
		$dt = new DateTime($date);

		// If the current day matches, move to next week
		if ((int) $dt->format(DateStringFormat::DayOfWeekNumeric->value) === $weekday) {
			$dt->modify('+7 days');
		} else {
			$dt->modify("next {$dayNames[$weekday]}");
		}

		return $dt->format('Y-m-d');
	}

	/**
	 * Get the previous occurrence of a specific weekday before a given date.
	 *
	 * @param string $date    The starting date.
	 * @param int    $weekday Target weekday (0=Sunday, 6=Saturday).
	 * 
	 * @return string The previous occurrence in 'Y-m-d' format.
	 */
	public static function getPreviousWeekday(string $date, int $weekday): string
	{
		$dayNames = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
		$dt = new DateTime($date);

		// If the current day matches, move to previous week
		if ((int) $dt->format(DateStringFormat::DayOfWeekNumeric->value) === $weekday) {
			$dt->modify('-7 days');
		} else {
			$dt->modify("last {$dayNames[$weekday]}");
		}

		return $dt->format('Y-m-d');
	}

	/**
	 * Get the closest weekday (Mon-Fri) to a given date.
	 * If the date is a Saturday, returns the previous Friday.
	 * If the date is a Sunday, returns the next Monday.
	 *
	 * @param string $date The input date string.
	 * 
	 * @return string The closest weekday in 'Y-m-d' format.
	 */
	public static function getClosestWeekday(string $date): string
	{
		$dt = new DateTime($date);
		$dow = (int) $dt->format(DateStringFormat::DayOfWeekIso8601Numeric->value);

		if ($dow === 6) {
			// Saturday -> move to Friday
			$dt->modify('-1 day');
		} elseif ($dow === 7) {
			// Sunday -> move to Monday
			$dt->modify('+1 day');
		}

		return $dt->format('Y-m-d');
	}

	/**
	 * Get all quarters between two dates as an array of "YYYY-QN" strings.
	 *
	 * @param string $start The start date.
	 * @param string $end   The end date.
	 * 
	 * @return array<string> e.g. ["2025-Q1", "2025-Q2", ...]
	 */
	public static function getQuartersBetween(string $start, string $end): array
	{
		$current = new DateTime(self::getQuarterStart($start));
		$endDt = new DateTime($end);
		$quarters = [];

		while ($current <= $endDt) {
			$year = (int) $current->format(DateStringFormat::YearFullNumeric->value);
			$quarter = (int) ceil((int) $current->format(DateStringFormat::MonthNumericWithoutLeadingZeros->value) / 3);
			$key = "{$year}-Q{$quarter}";
			if (!in_array($key, $quarters, true)) {
				$quarters[] = $key;
			}
			$current->modify('+3 months');
		}

		return $quarters;
	}

	/**
	 * Generate an array of dates at regular intervals between two dates.
	 *
	 * @param string $start    The start date.
	 * @param string $end      The end date.
	 * @param string $interval The PHP DateInterval spec (e.g. 'P1D', 'P1W', 'P1M').
	 * @param string $format   The output date format.
	 * 
	 * @return array<string> Dates in the specified format.
	 */
	public static function getDateRange(string $start, string $end, string $interval = 'P1D', string $format = 'Y-m-d'): array
	{
		$startDt = new DateTime($start);
		$endDt = new DateTime($end);
		$endDt->setTime(23, 59, 59);
		$period = new \DatePeriod($startDt, new DateInterval($interval), $endDt);

		$dates = [];
		foreach ($period as $dt) {
			$dates[] = $dt->format($format);
		}

		return $dates;
	}

	/**
	 * Calculate the number of total seconds in a given time string (HH:MM:SS).
	 *
	 * @param string $time The time string in "H:i:s" format.
	 * 
	 * @return int The total seconds.
	 */
	public static function timeToSeconds(string $time): int
	{
		$parts = explode(':', $time);
		$hours = (int) ($parts[0] ?? 0);
		$minutes = (int) ($parts[1] ?? 0);
		$seconds = (int) ($parts[2] ?? 0);

		return ($hours * 3600) + ($minutes * 60) + $seconds;
	}

	/**
	 * Convert a number of seconds to a "H:i:s" formatted time string.
	 *
	 * @param int $seconds The total number of seconds.
	 * 
	 * @return string Formatted as "HH:MM:SS".
	 */
	public static function secondsToTime(int $seconds): string
	{
		$hours = intdiv(abs($seconds), 3600);
		$minutes = intdiv(abs($seconds) % 3600, 60);
		$secs = abs($seconds) % 60;
		$sign = $seconds < 0 ? '-' : '';

		return sprintf('%s%02d:%02d:%02d', $sign, $hours, $minutes, $secs);
	}

	/**
	 * Get the number of days between a date and the end of its month.
	 * Alias of getRemainingDaysInMonth for discoverability.
	 *
	 * @param string $date The input date.
	 * 
	 * @return int Days remaining in the month.
	 */
	public static function daysUntilEndOfMonth(string $date): int
	{
		return self::getRemainingDaysInMonth($date);
	}

	/**
	 * Get the number of days between a date and the end of its year.
	 * Alias of getRemainingDaysInYear for discoverability.
	 *
	 * @param string $date The input date.
	 * 
	 * @return int Days remaining in the year.
	 */
	public static function daysUntilEndOfYear(string $date): int
	{
		return self::getRemainingDaysInYear($date);
	}

	/**
	 * Convert a date to W3C (ISO 8601) format with timezone.
	 *
	 * @param string      $date     The input date string.
	 * @param string|null $timezone Optional timezone identifier.
	 * 
	 * @return string W3C formatted date string.
	 */
	public static function toW3C(string $date, ?string $timezone = null): string
	{
		$dt = new DateTime($date);
		if ($timezone) {
			$dt->setTimezone(new DateTimeZone($timezone));
		}

		return $dt->format(DateTime::W3C);
	}

	/**
	 * Convert a date to a cookie-compatible string (RFC 850).
	 *
	 * @param string $date The input date string.
	 * 
	 * @return string The cookie-formatted date string.
	 */
	public static function toCookieFormat(string $date): string
	{
		return (new DateTime($date))->format(DateTime::COOKIE);
	}

	/**
	 * Convert a date to RSS 2.0 compatible format (RFC 2822).
	 *
	 * @param string $date The input date string.
	 * 
	 * @return string The RSS-formatted date string.
	 */
	public static function toRSSFormat(string $date): string
	{
		return (new DateTime($date))->format(DateTime::RSS);
	}

	/**
	 * Convert a PHP DateTimeInterface to a Windows FILETIME expressed as a
	 * LARGE_INTEGER in 100-nanosecond intervals since 1601-01-01 UTC.
	 * A negative value means "relative from now"; a positive value is absolute.
	 * We always pass an absolute time (positive).
	 * 
	 * @param DateTimeInterface $dt The input date and time.
	 * @return int The corresponding FILETIME as a 64-bit integer.
	 * @throws InvalidArgumentException If the date is before the Windows epoch (1601-01-01).
	 */
	public static function datetimeToFileTime(DateTimeInterface $dt): int
	{
		// Windows epoch starts 1601-01-01; Unix epoch 1970-01-01.
		// Difference in 100-ns intervals:
		//   116444736000000000  =  (1970-1601) years * 365.2425 days * 86400 s * 10_000_000 ticks
		$unixEpochOffset = 116_444_736_000_000_000;
		$unixTimestamp = (int) $dt->format(DateStringFormat::DateTimeUnixEpochSeconds->value); // seconds
		$ticks = $unixTimestamp * 10_000_000; // to 100-ns units
		return $ticks + $unixEpochOffset;
	}

	/**
	 * Convert DateTime to milliseconds since the Unix epoch.
	 *
	 * @param string $date The input date string.
	 * @return int Milliseconds since Unix epoch.
	 */
	public static function toUnixMilliseconds(string $date): int
	{
		return (int) floor((new DateTime($date))->format('U.u') * 1000);
	}

	/**
	 * Create a date string from milliseconds since the Unix epoch.
	 *
	 * @param int $milliseconds Milliseconds since Unix epoch.
	 * @param string $format Output format (default 'Y-m-d H:i:s').
	 * @return string Formatted date string.
	 */
	public static function fromUnixMilliseconds(int $milliseconds, string $format = 'Y-m-d H:i:s'): string
	{
		$seconds = intdiv($milliseconds, 1000);
		$microseconds = ($milliseconds % 1000) * 1000;
		$dt = (new DateTime('@' . $seconds))->setTimezone(new DateTimeZone(date_default_timezone_get()));
		$dt = DateTime::createFromFormat('U.u', sprintf("%d.%06d", $seconds, $microseconds));
		if ($dt === false) {
			return (new DateTime('@' . $seconds))->format($format);
		}
		return $dt->format($format);
	}

	/**
	 * Check if two dates are in the same hour.
	 *
	 * @param string $date1 The first date string.
	 * @param string $date2 The second date string.
	 * @return bool True if both dates are in the same hour.
	 */
	public static function isSameHour(string $date1, string $date2): bool
	{
		return (new DateTime($date1))->format('Y-m-d H') === (new DateTime($date2))->format('Y-m-d H');
	}

	/**
	 * Check if two dates are in the same minute.
	 *
	 * @param string $date1 The first date string.
	 * @param string $date2 The second date string.
	 * @return bool True if both dates are in the same minute.
	 */
	public static function isSameMinute(string $date1, string $date2): bool
	{
		return (new DateTime($date1))->format('Y-m-d H:i') === (new DateTime($date2))->format('Y-m-d H:i');
	}

	/**
	 * Get all weekdays (Monday to Friday) between two dates.
	 *
	 * @param string $start The start date.
	 * @param string $end The end date.
	 * @return array Dates in Y-m-d format.
	 */
	public static function getWeekdaysBetween(string $start, string $end): array
	{
		$dates = [];
		$current = new DateTime($start);
		$endDt = new DateTime($end);

		if ($current > $endDt) {
			throw new RuntimeException('End date must be greater than start date.');
		}

		while ($current <= $endDt) {
			$dayOfWeek = (int) $current->format(DateStringFormat::DayOfWeekIso8601Numeric->value);
			if ($dayOfWeek < 6) {
				$dates[] = $current->format('Y-m-d');
			}
			$current->modify('+1 day');
		}

		return $dates;
	}

	/**
	 * Get the next business day after a given date.
	 *
	 * @param string $date The date to start from.
	 * @param array $holidays Optional holiday list in Y-m-d format.
	 * @return string The next business day.
	 */
	public static function getNextBusinessDay(string $date, array $holidays = []): string
	{
		$dt = new DateTime($date);
		$dt->modify('+1 day');

		while (!self::isBusinessDay($dt->format('Y-m-d'), $holidays)) {
			$dt->modify('+1 day');
		}

		return $dt->format('Y-m-d');
	}

	/**
	 * Get the previous business day before a given date.
	 *
	 * @param string $date The date to start from.
	 * @param array $holidays Optional holiday list in Y-m-d format.
	 * @return string The previous business day.
	 */
	public static function getPreviousBusinessDay(string $date, array $holidays = []): string
	{
		$dt = new DateTime($date);
		$dt->modify('-1 day');

		while (!self::isBusinessDay($dt->format('Y-m-d'), $holidays)) {
			$dt->modify('-1 day');
		}

		return $dt->format('Y-m-d');
	}

	/**
	 * Get the number of business days between two dates, exclusive of start and end by default.
	 *
	 * @param string $start The start date.
	 * @param string $end The end date.
	 * @param array $holidays Optional holiday list in Y-m-d format.
	 * @param bool $includeBoundaries Include start and end in the count (default false).
	 * @return int Business day count.
	 */
	public static function getBusinessDaysCount(string $start, string $end, array $holidays = [], bool $includeBoundaries = false): int
	{
		$count = 0;
		$current = new DateTime($start);
		$endDt = new DateTime($end);

		if ($current > $endDt) {
			throw new RuntimeException('End date must be greater than start date.');
		}

		if (!$includeBoundaries) {
			$current->modify('+1 day');
			$endDt->modify('-1 day');
		}

		while ($current <= $endDt) {
			if (self::isBusinessDay($current->format('Y-m-d'), $holidays)) {
				$count++;
			}
			$current->modify('+1 day');
		}

		return $count;
	}

	/**
	 * Get the number of business weeks between two dates.
	 * Weeks are considered 5 business days each.
	 *
	 * @param string $start Start date.
	 * @param string $end End date.
	 * @param array $holidays Optional holiday dates.
	 * @return float Business weeks count.
	 */
	public static function getBusinessWeeksBetween(string $start, string $end, array $holidays = []): float
	{
		$businessDays = self::getBusinessDaysCount($start, $end, $holidays, true);
		return $businessDays / 5.0;
	}

	/**
	 * Add a given number of business weeks to a date.
	 *
	 * @param string $date Starting date.
	 * @param int $weeks Number of business weeks to add.
	 * @param array $holidays Optional holiday dates.
	 * @return string Resulting date in Y-m-d format.
	 */
	public static function addBusinessWeeks(string $date, int $weeks, array $holidays = []): string
	{
		return self::addBusinessDays($date, $weeks * 5, $holidays);
	}

	/**
	 * Determine the last day of the fiscal year for a given date and fiscal year start month.
	 *
	 * @param string $date Input date.
	 * @param int $fiscalYearStartMonth Fiscal year start month (1-12, default 4).
	 * @return string Last day of fiscal year in Y-m-d format.
	 */
	public static function getEndOfFiscalYear(string $date, int $fiscalYearStartMonth = 4): string
	{
		$fiscalYear = self::getFiscalYear($date, $fiscalYearStartMonth);
		$endMonth = $fiscalYearStartMonth - 1;
		$endYear = $fiscalYear + ($endMonth === 0 ? 1 : 0);
		$endMonth = $endMonth === 0 ? 12 : $endMonth;
		$dt = new DateTime("{$endYear}-{$endMonth}-01");
		$dt->modify('last day of this month');
		return $dt->format('Y-m-d');
	}

	/**
	 * Check if any day in a date range is a weekend.
	 *
	 * @param string $start Start date.
	 * @param string $end End date.
	 * @return bool True if there is at least one weekend day.
	 */
	public static function isWeekendInRange(string $start, string $end): bool
	{
		$current = new DateTime($start);
		$endDt = new DateTime($end);
		if ($current > $endDt) {
			throw new RuntimeException('End date must be greater than start date.');
		}

		while ($current <= $endDt) {
			if (self::isWeekend($current->format('Y-m-d'))) {
				return true;
			}
			$current->modify('+1 day');
		}

		return false;
	}

	/**
	 * Convert seconds to a human-readable duration string (e.g., "3h 15m 20s").
	 *
	 * @param int $seconds Total seconds.
	 * @return string Human friendly duration.
	 */
	public static function secondsToHumanReadable(int $seconds): string
	{
		$sign = $seconds < 0 ? '-' : '';
		$absSeconds = abs($seconds);
		$hours = intdiv($absSeconds, 3600);
		$minutes = intdiv($absSeconds % 3600, 60);
		$secs = $absSeconds % 60;

		$parts = [];
		if ($hours > 0) {
			$parts[] = "{$hours}h";
		}
		if ($minutes > 0 || $hours > 0) {
			$parts[] = "{$minutes}m";
		}
		$parts[] = "{$secs}s";

		return $sign . implode(' ', $parts);
	}

	/**
	 * Estimate age with decimal precision from birthdate.
	 *
	 * @param string $birthdate Birthdate in parseable format.
	 * @return float Age in years with one decimal.
	 */
	public static function getAgeDecimal(string $birthdate): float
	{
		$birth = new DateTime($birthdate);
		$now = new DateTime();
		$diff = $birth->diff($now);

		$years = $diff->y;
		$months = $diff->m;
		$days = $diff->d;

		$age = $years + ($months / 12) + ($days / 365.25);
		return round($age, 1);
	}

	/**
	 * Get Julian Day Number from a date string.
	 *
	 * @param string $date Input date.
	 * @return int Julian Day Number.
	 */
	public static function getJulianDayNumber(string $date): int
	{
		$dt = new DateTime($date);
		$year = (int) $dt->format(DateStringFormat::YearFullNumeric->value);
		$month = (int) $dt->format(DateStringFormat::MonthNumericWithoutLeadingZeros->value);
		$day = (int) $dt->format(DateStringFormat::DayOfMonthWithoutLeadingZeros->value);

		if ($month <= 2) {
			$year--;
			$month += 12;
		}
		$A = intdiv($year, 100);
		$B = 2 - $A + intdiv($A, 4);
		$jd = (int) (floor(365.25 * ($year + 4716)) + floor(DateEnum::JULIAN_MONTH_DAYS_FACTOR * ($month + 1)) + $day + $B - 1524.5);
		return $jd;
	}

	/**
	 * Parse a date string with a few friendly formats.
	 * 
	 * Supported formats include:
	 * - "Y-m-d H:i:s"
	 * - "Y-m-d H:i"
	 * - "Y-m-d"
	 * - "d/m/Y H:i:s"
	 * - "d/m/Y H:i"
	 * - Any format recognized by strtotime (as a fallback)
	 * @param string $raw The raw date string to parse.
	 * @return DateTimeImmutable The parsed date as a DateTimeImmutable object.
	 * @throws InvalidArgumentException If the date cannot be parsed.
	 */
	public static function parseTarget(string $raw): DateTimeImmutable
	{
		$formats = [
			'Y-m-d H:i:s',
			'Y-m-d H:i',
			'Y-m-d',
			'd/m/Y H:i:s',
			'd/m/Y H:i',
		];

		foreach ($formats as $fmt) {
			$dt = DateTimeImmutable::createFromFormat($fmt, $raw);
			if ($dt !== false) {
				return $dt;
			}
		}

		// Try strtotime as fallback
		$ts = strtotime($raw);
		if ($ts !== false) {
			return new DateTimeImmutable('@' . $ts);
		}

		throw new InvalidArgumentException("Cannot parse date: \"$raw\"");
	}

	/**
	 * Check if a date is in daylight saving time for the given timezone.
	 *
	 * @param string      $date     The input date string.
	 * @param string|null $timezone The timezone identifier (default system timezone).
	 * @return bool True if in DST, false otherwise.
	 */
	public static function isInDST(string $date, ?string $timezone = null): bool
	{
		$tz = new DateTimeZone($timezone ?? date_default_timezone_get());
		$dt = new DateTime($date, $tz);
		return (bool) $tz->getTransitions($dt->getTimestamp(), $dt->getTimestamp())[0]['isdst'];
	}

	/**
	 * Get the DST start and end dates for a year in a timezone.
	 *
	 * @param int $year The year.
	 * @param string $timezone The timezone identifier.
	 * @return array{start: ?string, end: ?string} Associative array with DST start and end in Y-m-d H:i:s or null if none.
	 */
	public static function getDaylightSavingsTransitions(int $year, string $timezone): array
	{
		$tz = new DateTimeZone($timezone);
		$transitions = $tz->getTransitions(strtotime("{$year}-01-01"), strtotime("{$year}-12-31 23:59:59"));
		$dstStart = null;
		$dstEnd = null;

		foreach ($transitions as $transition) {
			if (isset($transition['isdst'])) {
				if ($transition['isdst'] && $dstStart === null) {
					$dstStart = (new DateTime('@' . $transition['ts']))->setTimezone($tz)->format('Y-m-d H:i:s');
				} elseif (!$transition['isdst'] && $dstStart !== null && $dstEnd === null) {
					$dstEnd = (new DateTime('@' . $transition['ts']))->setTimezone($tz)->format('Y-m-d H:i:s');
				}
			}
		}

		return ['start' => $dstStart, 'end' => $dstEnd];
	}

	/**
	 * Get the date for a specific day-of-year value.
	 *
	 * @param int $year The year.
	 * @param int $dayOfYear The day number in year (1-365/366).
	 * @return string Date in Y-m-d format.
	 */
	public static function getDateFromDayOfYear(int $year, int $dayOfYear): string
	{
		$dt = new DateTime("{$year}-01-01");
		$dt->modify(($dayOfYear - 1) . ' days');
		return $dt->format('Y-m-d');
	}

	/**
	 * Get the date of a specific ISO week and weekday.
	 *
	 * @param int $year The ISO week-numbering year.
	 * @param int $week The ISO week number (1-53).
	 * @param int $weekday The weekday (1=Monday, 7=Sunday).
	 * @return string Date in Y-m-d format.
	 */
	public static function getDateFromIsoWeek(int $year, int $week, int $weekday = 1): string
	{
		$dt = new DateTime();
		$dt->setISODate($year, $week, $weekday);
		return $dt->format('Y-m-d');
	}

	/**
	 * Get week number and corresponding year for a given date.
	 *
	 * @param string $date The input date string.
	 * @return array{year: int, week: int} ISO year and week number.
	 */
	public static function getIsoWeekYear(string $date): array
	{
		$dt = new DateTime($date);
		return [
			'year' => (int) $dt->format(DateStringFormat::YearIso8601WeekNumbering->value),
			'week' => (int) $dt->format(DateStringFormat::WeekNumberIso8601->value),
		];
	}

	/**
	 * Get age in total months from birthdate.
	 *
	 * @param string $birthdate The birthdate string.
	 * @return int Total months age.
	 */
	public static function getAgeInMonths(string $birthdate): int
	{
		$birth = new DateTime($birthdate);
		$now = new DateTime();
		$diff = $birth->diff($now);
		return ($diff->y * 12) + $diff->m;
	}

	/**
	 * Get the first business day of a month.
	 *
	 * @param string $monthYear Month and year in Y-m format (2026-03).
	 * @param array $holidays Optional holiday list.
	 * @return string Date in Y-m-d format.
	 */
	public static function getFirstBusinessDayOfMonth(string $monthYear, array $holidays = []): string
	{
		$dt = new DateTime("{$monthYear}-01");
		while (!self::isBusinessDay($dt->format('Y-m-d'), $holidays)) {
			$dt->modify('+1 day');
		}
		return $dt->format('Y-m-d');
	}

	/**
	 * Get the last business day of a month.
	 *
	 * @param string $monthYear Month and year in Y-m format (2026-03).
	 * @param array $holidays Optional holiday list.
	 * @return string Date in Y-m-d format.
	 */
	public static function getLastBusinessDayOfMonth(string $monthYear, array $holidays = []): string
	{
		$dt = new DateTime($monthYear . '-01');
		$dt->modify('last day of this month');
		while (!self::isBusinessDay($dt->format('Y-m-d'), $holidays)) {
			$dt->modify('-1 day');
		}
		return $dt->format('Y-m-d');
	}

	/**
	 * Get the fiscal quarter label for a date (e.g., "Q1 2026").
	 *
	 * @param string $date The input date.
	 * @param int $fiscalYearStartMonth Month the fiscal year begins (default 4 for April).
	 * @return string Fiscal quarter label.
	 */
	public static function getFiscalQuarterLabel(string $date, int $fiscalYearStartMonth = 4): string
	{
		$quarter = self::getFiscalQuarter($date, $fiscalYearStartMonth);
		$year = self::getFiscalYear($date, $fiscalYearStartMonth);
		return "Q{$quarter} {$year}";
	}

	/**
	 * Get number of years between two dates with decimal precision.
	 *
	 * @param string $start The start date.
	 * @param string $end The end date.
	 * @return float Years difference with precision.
	 */
	public static function diffInYears(string $start, string $end): float
	{
		$startDate = new DateTime($start);
		$endDate = new DateTime($end);
		$diff = $startDate->diff($endDate);
		$years = $diff->y;
		$months = $diff->m;
		$days = $diff->d;
		return $years + ($months / 12) + ($days / 365.25);
	}

	/**
	 * Normalize a date string by converting it to ISO format.
	 *
	 * @param string $date Input date string.
	 * @return string Normalized ISO 8601 date string.
	 */
	public static function normalizeDate(string $date): string
	{
		$dt = new DateTime($date);
		return $dt->format('Y-m-d');
	}

	/**
	 * Get the number of days until the next anniversary date.
	 *
	 * @param string $date Date to check anniversary for.
	 * @return int Days until next anniversary.
	 */
	public static function daysUntilNextAnniversary(string $date): int
	{
		$dt = new DateTime($date);
		$now = new DateTime();
		$anniversary = new DateTime($now->format('Y-') . $dt->format('m-d'));
		if ($anniversary < $now) {
			$anniversary->modify('+1 year');
		}
		return (int) $now->diff($anniversary)->format('%a');
	}

	/**
	 * Get the ordinal suffix for a day (e.g., 1st, 2nd, 3rd, 4th).
	 *
	 * @param int $day Day of the month.
	 * @return string The day with ordinal suffix.
	 */
	public static function dayWithSuffix(int $day): string
	{
		if ($day % 100 >= 11 && $day % 100 <= 13) {
			return $day . 'th';
		}

		switch ($day % 10) {
			case 1:
				return $day . 'st'; // Fir`st`
			case 2:
				return $day . 'nd'; // Seco`nd`
			case 3:
				return $day . 'rd'; // Thi`rd`
			default:
				return $day . 'th'; // Four`th`
		}
	}

	/**
	 * Get a localized month name for a date using locale.
	 *
	 * @param string $date Input date string.
	 * @param string $locale Locale code (e.g. en_US, ko_KR).
	 * @return string Localized month name if available, otherwise English.
	 */
	public static function getLocalizedMonthName(string $date, string $locale = 'en_US'): string
	{
		$previousLocale = setlocale(LC_TIME, null);
		setlocale(LC_TIME, $locale . '.UTF-8');
		$result = self::timeToString('%B', strtotime($date));
		setlocale(LC_TIME, $previousLocale);
		return $result ?: (new DateTime($date))->format(DateStringFormat::MonthFullTextual->value);
	}

	/**
	 * Generate a sequence of business dates (excluding weekends and holidays) between two dates.
	 *
	 * @param string $start Start date.
	 * @param string $end End date.
	 * @param array $holidays Optional holiday list.
	 * @return array Business dates.
	 */
	public static function generateBusinessDates(string $start, string $end, array $holidays = []): array
	{
		$dates = [];
		$current = new DateTime($start);
		$endDt = new DateTime($end);
		if ($current > $endDt) {
			throw new RuntimeException('End date must be greater than start date.');
		}

		while ($current <= $endDt) {
			$formatted = $current->format('Y-m-d');
			if (self::isBusinessDay($formatted, $holidays)) {
				$dates[] = $formatted;
			}
			$current->modify('+1 day');
		}

		return $dates;
	}

	/**
	 * Get the first day of the given week number in a year (ISO week date).
	 *
	 * @param int $year The year.
	 * @param int $week The ISO week number.
	 * @return string Date in Y-m-d format.
	 */
	public static function getDateFromIsoWeekNumber(int $year, int $week): string
	{
		$dt = new DateTime();
		$dt->setISODate($year, $week);
		return $dt->format('Y-m-d');
	}

	/**
	 * Get whether a date is on an odd or even calendar week.
	 *
	 * @param string $date Input date.
	 * @return string "odd" or "even".
	 */
	public static function getWeekParity(string $date): string
	{
		$week = (int) (new DateTime($date))->format(DateStringFormat::WeekNumberIso8601->value);
		return $week % 2 === 0 ? 'even' : 'odd';
	}

	/**
	 * Round a datetime to the nearest interval in minutes.
	 *
	 * @param string $date Input datetime string.
	 * @param int $minutes Interval in minutes.
	 * @return string Rounded datetime in Y-m-d H:i:s format.
	 */
	public static function roundDateTimeToMinutes(string $date, int $minutes): string
	{
		$dt = new DateTime($date);
		$timestamp = $dt->getTimestamp();
		$roundSeconds = $minutes * 60;
		$rounded = (int) (round($timestamp / $roundSeconds) * $roundSeconds);
		return (new DateTime())->setTimestamp($rounded)->format('Y-m-d H:i:s');
	}

	/**
	 * Count business days for a date range with inclusive boundaries.
	 *
	 * @param string $start Start date.
	 * @param string $end End date.
	 * @param array $holidays Optional holiday list in Y-m-d.
	 * @return int Business day count inclusive.
	 */
	public static function getBusinessDaysCountInclusive(string $start, string $end, array $holidays = []): int
	{
		return self::getBusinessDaysCount($start, $end, $holidays, true);
	}

	/**
	 * Determine if given date is on the last business day of the month.
	 *
	 * @param string $date Date string.
	 * @param array $holidays Optional holidays.
	 * @return bool
	 */
	public static function isLastBusinessDayOfMonth(string $date, array $holidays = []): bool
	{
		$monthYear = (new DateTime($date))->format('Y-m');
		return self::getLastBusinessDayOfMonth($monthYear, $holidays) === (new DateTime($date))->format('Y-m-d');
	}

	/**
	 * Determine if given date is on the first business day of the month.
	 *
	 * @param string $date Date string.
	 * @param array $holidays Optional holidays.
	 * @return bool
	 */
	public static function isFirstBusinessDayOfMonth(string $date, array $holidays = []): bool
	{
		$monthYear = (new DateTime($date))->format('Y-m');
		return self::getFirstBusinessDayOfMonth($monthYear, $holidays) === (new DateTime($date))->format('Y-m-d');
	}

	/**
	 * Get the number of seconds until the end of the day.
	 *
	 * @param string $date Input datetime string.
	 * @return int Seconds until 23:59:59.
	 */
	public static function secondsUntilEndOfDay(string $date): int
	{
		$dt = new DateTime($date);
		$end = (clone $dt)->setTime(23, 59, 59);
		return max(0, (int) $end->getTimestamp() - (int) $dt->getTimestamp());
	}

	/**
	 * Get the elapsed seconds since the start of the day.
	 *
	 * @param string $date Input datetime string.
	 * @return int Seconds since 00:00:00.
	 */
	public static function secondsSinceStartOfDay(string $date): int
	{
		$dt = new DateTime($date);
		$start = (clone $dt)->setTime(0, 0, 0);
		return max(0, (int) $dt->getTimestamp() - (int) $start->getTimestamp());
	}

	/**
	 * Get a floating age (years with 2 decimals) from birthdate.
	 *
	 * @param string $birthdate Birthdate in parseable format.
	 * @return float Age in years.
	 */
	public static function getAgeFloat(string $birthdate): float
	{
		$dt = new DateTime($birthdate);
		$now = new DateTime();
		$diff = $dt->diff($now);
		$years = $diff->y;
		$months = $diff->m;
		$days = $diff->d;
		return round($years + ($months / 12) + ($days / 365.25), 2);
	}

	/**
	 * Convert a week number and weekday to date string.
	 *
	 * @param int $year Year for ISO week.
	 * @param int $week ISO week number.
	 * @param int $weekday ISO weekday (1=Mon..7=Sun).
	 * @return string Date in Y-m-d.
	 */
	public static function getDateFromIsoWeekAndWeekday(int $year, int $week, int $weekday): string
	{
		$dt = new DateTime();
		$dt->setISODate($year, $week, $weekday);
		return $dt->format('Y-m-d');
	}

	/**
	 * Create a date range array aligned to a given interval in days.
	 *
	 * @param string $start Start date.
	 * @param string $end End date.
	 * @param int $intervalDays Interval step in days.
	 * @return array Date strings in Y-m-d.
	 */
	public static function getDateRangeWithInterval(string $start, string $end, int $intervalDays = 1): array
	{
		$dates = [];
		$current = new DateTime($start);
		$endDt = new DateTime($end);

		if ($current > $endDt) {
			throw new RuntimeException('End date must be greater than start date.');
		}

		while ($current <= $endDt) {
			$dates[] = $current->format('Y-m-d');
			$current->modify("+{$intervalDays} days");
		}

		return $dates;
	}

	/**
	 * Format a date with a context-aware style.
	 *
	 * @param string $date Input date string.
	 * @param string $format Output format string (default Y-m-d).
	 * @return string Formatted date.
	 */
	public static function formatContextAware(string $date, string $format = 'Y-m-d'): string
	{
		$dt = new DateTime($date);
		if ($format === 'relative') {
			return self::getRelativeDayName($date);
		}
		return $dt->format($format);
	}

	/**
	 * Get the current time in seconds with microsecond precision.
	 *
	 * @return float Current time in seconds (with microseconds).
	 */
	public static function getCurrentTime(): float
	{
		return (float) function_exists('hrtime') ? hrtime(true) / 1e9 : microtime(true);
	}

	/**
	 * Get the Julian date of a requested moon phase for a given year fraction.
	 *
	 * @param float $yearFraction Year plus fractional portion (e.g. 2024.5).
	 * @param int $phase 0=new, 1=first quarter, 2=full, 3=last quarter.
	 * @return float Julian date of the nearest requested phase moment.
	 */
	public static function getMoonPhaseJD(float $yearFraction, int $phase): float
	{
		$realPhase = $phase * 0.25;
		$k = (int) floor(($yearFraction - 2000.0) * 12.3685);
		$k += $realPhase;

		$T = $k / 1236.85;
		$T2 = $T ** 2;
		$T3 = $T ** 3;
		$T4 = $T ** 4;

		$JDE = DateEnum::MEAN_NEW_MOON_BASE_JD + DateEnum::MEAN_NEW_MOON_SYNODIC_COEFF * $k
			+ 0.00015437 * $T2
			- 0.000000150 * $T3
			+ 0.00000000073 * $T4;

		$M = deg2rad(fmod(2.5534 + 29.10535670 * $k - 0.0000014 * $T2 - 0.00000011 * $T3, 360.0));
		$Mp = deg2rad(fmod(201.5643 + 385.81693528 * $k + 0.0107582 * $T2 + 0.00001238 * $T3 - 0.000000058 * $T4, 360.0));
		$F = deg2rad(fmod(160.7108 + 390.67050284 * $k - 0.0016118 * $T2 - 0.00000227 * $T3 + 0.000000011 * $T4, 360.0));
		$omega = deg2rad(fmod(124.7746 - 1.56375588 * $k + 0.0020672 * $T2 + 0.00000215 * $T3, 360.0));

		$a1 = deg2rad(fmod(299.77 + 0.107408 * $k - 0.009173 * $T3, 360.0));
		$a2 = deg2rad(fmod(251.88 + 0.016321 * $k, 360.0));
		$a3 = deg2rad(fmod(251.83 + 26.651886 * $k, 360.0));
		$a4 = deg2rad(fmod(349.42 + 36.412478 * $k, 360.0));
		$a5 = deg2rad(fmod(84.66 + 18.206239 * $k, 360.0));
		$a6 = deg2rad(fmod(141.74 + 53.303771 * $k, 360.0));
		$a7 = deg2rad(fmod(207.14 + 2.453732 * $k, 360.0));
		$a8 = deg2rad(fmod(154.84 + 7.306860 * $k, 360.0));
		$a9 = deg2rad(fmod(34.52 + 27.261239 * $k, 360.0));
		$a10 = deg2rad(fmod(207.19 + 0.121824 * $k, 360.0));
		$a11 = deg2rad(fmod(291.34 + 1.844379 * $k, 360.0));
		$a12 = deg2rad(fmod(161.72 + 24.198154 * $k, 360.0));
		$a13 = deg2rad(fmod(239.56 + 25.513099 * $k, 360.0));
		$a14 = deg2rad(fmod(331.55 + 3.592518 * $k, 360.0));

		$E = 1.0 - 0.002516 * $T - 0.0000074 * $T2;
		$corr = 0.0;

		if ($realPhase === 0.0) {
			$corr = -0.40720 * sin($Mp);
			$corr += 0.17241 * $E * sin($M);
			$corr += 0.01608 * sin(2.0 * $Mp);
			$corr += 0.01039 * sin(2.0 * $F);
			$corr += 0.00739 * $E * sin($Mp - $M);
			$corr -= 0.00514 * $E * sin($Mp + $M);
			$corr += 0.00208 * $E * $E * sin(2.0 * $M);
		} elseif ($realPhase === 0.5) {
			$corr = -0.40614 * sin($Mp);
			$corr += 0.17302 * $E * sin($M);
			$corr += 0.01614 * sin(2.0 * $Mp);
			$corr += 0.01043 * sin(2.0 * $F);
			$corr += 0.00734 * $E * sin($Mp - $M);
			$corr -= 0.00515 * $E * sin($Mp + $M);
			$corr += 0.00209 * $E * $E * sin(2.0 * $M);
		} elseif ($realPhase === 0.25 || $realPhase === 0.75) {
			$corr = -0.62801 * sin($Mp);
			$corr += 0.17172 * $E * sin($M);
			$corr -= 0.01183 * $E * sin($Mp + $M);
			$corr += 0.00862 * sin(2.0 * $Mp);
			$corr += 0.00804 * sin(2.0 * $F);
			$corr += 0.00454 * $E * sin($Mp - $M);
			$corr += 0.00204 * $E * $E * sin(2.0 * $M);
			$corr -= 0.00108 * sin($Mp - 2.0 * $F);
			$corr -= 0.00070 * sin($Mp + 2.0 * $F);
			$corr -= 0.00040 * sin(3.0 * $Mp);
			$corr -= 0.00034 * $E * sin(2.0 * $Mp - $M);
			$corr += 0.00032 * $E * sin($M + 2.0 * $F);
			$corr += 0.00032 * $E * sin($M - 2.0 * $F);
			$corr += 0.00028 * $E * $E * sin($Mp + 2.0 * $M);
			$corr += 0.00027 * $E * sin(2.0 * $Mp + $M);
			$corr -= 0.00017 * sin($omega);
			$corr -= 0.00005 * sin($Mp - $M - 2.0 * $F);
			$corr += 0.00004 * sin(2.0 * $Mp + 2.0 * $F);
			$corr -= 0.00004 * sin($Mp + $M + 2.0 * $F);
			$corr += 0.00004 * sin($Mp - 2.0 * $M);
			$corr += 0.00003 * sin($Mp + $M - 2.0 * $F);
			$corr += 0.00003 * sin(3.0 * $M);
			$corr += 0.00002 * sin(2.0 * $Mp - 2.0 * $F);
			$corr += 0.00002 * sin($Mp - $M + 2.0 * $F);
			$corr -= 0.00002 * sin(3.0 * $Mp + $M);

			$W = 0.00306
				- 0.00038 * $E * cos($M)
				+ 0.00026 * cos($Mp)
				- 0.00002 * cos($Mp - $M)
				+ 0.00002 * cos($Mp + $M)
				+ 0.00002 * cos(2.0 * $F);

			if ($realPhase === 0.25) {
				$corr += $W;
			} else {
				$corr -= $W;
			}
		}

		$corr += 0.000325 * sin($a1);
		$corr += 0.000165 * sin($a2);
		$corr += 0.000164 * sin($a3);
		$corr += 0.000126 * sin($a4);
		$corr += 0.000110 * sin($a5);
		$corr += 0.000062 * sin($a6);
		$corr += 0.000060 * sin($a7);
		$corr += 0.000056 * sin($a8);
		$corr += 0.000047 * sin($a9);
		$corr += 0.000042 * sin($a10);
		$corr += 0.000040 * sin($a11);
		$corr += 0.000037 * sin($a12);
		$corr += 0.000035 * sin($a13);
		$corr += 0.0000325 * sin($a14);

		return $JDE + $corr;
	}

	/**
	 * Compute the Julian Day (Terrestrial Time) of the k-th new moon
	 * measured from the reference new moon at 2000-01-06 18:14 UTC (k = 0).
	 *
	 * Algorithm: Jean Meeus, "Astronomical Algorithms" 2nd ed., Ch. 49.
	 * Accuracy: < 2 minutes for dates 1900–2100.
	 *
	 * @param float $k New-moon index (may be non-integer for other phases,
	 *                 but only k = integer gives a new moon here).
	 * @return float Julian Day Number in TT.
	 */
	private static function computeNewMoonJD(float $k): float
	{
		$T = $k / 1236.85; // time in Julian centuries since the epoch 2000
		$T2 = $T ** 2;
		$T3 = $T ** 3;
		$T4 = $T ** 4;

		// Mean Julian Day of the new moon (from ELP-2000/82 theory)
		// J. Chapront and G. Francou,“The lunar theory ELP revisited. Introduction of new planetary perturbations,”Astron. & Astrophys. 404
		$JDE = DateEnum::MEAN_NEW_MOON_BASE_JD
			+ DateEnum::MEAN_NEW_MOON_SYNODIC_COEFF * $k
			+ 0.00015437 * $T2
			- 0.000000150 * $T3
			+ 0.00000000073 * $T4;

		// Eccentricity of Earth's orbit
		$E = 1.0 - 0.002516 * $T - 0.0000074 * $T2;

		// Sun's mean anomaly (M), Moon's mean anomaly (Mp),
		// Moon's argument of latitude (F), longitude of ascending node (Om)
		$M = deg2rad(fmod(2.5534 + 29.10535670 * $k - 0.0000014 * $T2 - 0.00000011 * $T3, 360.0));
		$Mp = deg2rad(fmod(201.5643 + 385.81693528 * $k + 0.0107582 * $T2 + 0.00001238 * $T3 - 0.000000058 * $T4, 360.0));
		$F = deg2rad(fmod(160.7108 + 390.67050284 * $k - 0.0016118 * $T2 - 0.00000227 * $T3 + 0.000000011 * $T4, 360.0));
		$Om = deg2rad(fmod(124.7746 - 1.56375588 * $k + 0.0020672 * $T2 + 0.00000215 * $T3, 360.0));

		// Planetary terms & main correction series
		$corr = -0.40720 * sin($Mp)
			+ 0.17241 * $E * sin($M)
			+ 0.01608 * sin(2.0 * $Mp)
			+ 0.01039 * sin(2.0 * $F)
			+ 0.00739 * $E * sin($Mp - $M)
			- 0.00514 * $E * sin($Mp + $M)
			+ 0.00208 * $E * $E * sin(2.0 * $M)
			- 0.00111 * sin($Mp - 2.0 * $F)
			- 0.00057 * sin($Mp + 2.0 * $F)
			+ 0.00056 * $E * sin(2.0 * $Mp + $M)
			- 0.00042 * sin(3.0 * $Mp)
			+ 0.00042 * $E * sin($M + 2.0 * $F)
			+ 0.00038 * $E * sin($M - 2.0 * $F)
			- 0.00024 * $E * sin(2.0 * $Mp - $M)
			- 0.00017 * sin($Om)
			- 0.00007 * sin($Mp + 2.0 * $M)
			+ 0.00004 * sin(2.0 * $Mp - 2.0 * $F)
			+ 0.00004 * sin(3.0 * $M)
			+ 0.00003 * sin($Mp + $M - 2.0 * $F)
			+ 0.00003 * sin(2.0 * $Mp + 2.0 * $F)
			- 0.00003 * sin($Mp + $M + 2.0 * $F)
			+ 0.00003 * sin($Mp - $M + 2.0 * $F)
			- 0.00002 * sin($Mp - $M - 2.0 * $F)
			- 0.00002 * sin(3.0 * $Mp + $M)
			+ 0.00002 * sin(4.0 * $Mp);

		return $JDE + $corr;
	}

	/**
	 * Find the largest new-moon index k such that the calendar date of
	 * newMoon(k) is on or before the given Julian Day Number (JDN).
	 *
	 * The calendar date of a new moon whose JD is X is floor(X + 0.5),
	 * which converts the TT Julian Day to the integer JDN of that day.
	 *
	 * @param int $jdn Integer Julian Day Number (= JD at noon).
	 * @return int The new-moon index k.
	 */
	private static function findKOnOrBefore(int $jdn): int
	{
		// Initial estimate using the mean synodic month
		$k = (int) round(($jdn - 2451550) / DateEnum::MEAN_NEW_MOON_SYNODIC_COEFF);

		// Walk backwards if the estimate overshot
		while ((int) floor(self::computeNewMoonJD($k) + 0.5) > $jdn) {
			$k--;
		}
		// Walk forwards to consume any extra full months before $jdn
		while ((int) floor(self::computeNewMoonJD($k + 1) + 0.5) <= $jdn) {
			$k++;
		}
		return $k;
	}

	/**
	 * Given a lunar year number and a month offset from the start of
	 * that year (0-indexed, counting leap months as extra slots),
	 * return the lunar month number and whether the month is a leap month.
	 *
	 * Leap-month layout example for a year whose leapMonth = 2:
	 *   offset 0 → month 1 (regular)
	 *   offset 1 → month 2 (regular)
	 *   offset 2 → month 2 (LEAP)
	 *   offset 3 → month 3 (regular)
	 *   …
	 *   offset 12 → month 12 (regular)
	 *
	 * @param int $lunarYear   The lunar year.
	 * @param int $monthOffset 0-based index of the new moon in that year.
	 * @return array{month: int, isLeap: bool}
	 */
	private static function offsetToLunarMonth(int $lunarYear, int $monthOffset): array
	{
		$leapMonth = self::$lunarLeapMonths[$lunarYear] ?? 0;

		if ($leapMonth > 0 && $monthOffset >= $leapMonth) {
			if ($monthOffset === $leapMonth) {
				return ['month' => $leapMonth, 'isLeap' => true];
			}
			// After the leap slot: month number = offset (not offset+1)
			return ['month' => $monthOffset, 'isLeap' => false];
		}
		return ['month' => $monthOffset + 1, 'isLeap' => false];
	}

	/**
	 * Given a lunar year, month, and isLeap flag, return the 0-based
	 * month offset from that year's Lunar New Year new moon.
	 *
	 * @param int  $lunarYear  The lunar year.
	 * @param int  $lunarMonth 1-based lunar month number.
	 * @param bool $isLeap     Whether this is the leap copy of the month.
	 * @return int 0-based month offset.
	 */
	private static function lunarMonthToOffset(int $lunarYear, int $lunarMonth, bool $isLeap): int
	{
		$leapMonth = self::$lunarLeapMonths[$lunarYear] ?? 0;

		if ($isLeap && $leapMonth > 0) {
			return $leapMonth; // The leap slot sits exactly at index leapMonth
		}
		if ($leapMonth > 0 && $lunarMonth > $leapMonth) {
			return $lunarMonth; // Shifted by one because the leap slot preceded this month
		}
		return $lunarMonth - 1;
	}

	/**
	 * Get the Moon's current age in days since the last new moon.
	 *
	 * Uses a reference new moon at 2000-01-06 18:14 UTC (JD 2451550.259).
	 * Returned value is in the range [0, 29.53).
	 *
	 * @param string $date The date to query (any parseable format).
	 * @return float Moon age in days, rounded to 4 decimal places.
	 */
	public static function getMoonAge(string $date): float
	{
		// JD of the given date at noon
		$jdn = (float) self::getJulianDayNumber($date);
		$refNewMoon = 2451550.259; // 2000-01-06 18:14 UTC
		$synodic = DateEnum::MEAN_NEW_MOON_SYNODIC_COEFF;

		$age = fmod($jdn - $refNewMoon, $synodic);
		if ($age < 0.0) {
			$age += $synodic;
		}
		return round($age, 4);
	}

	/**
	 * Get a named description of the current moon phase.
	 *
	 * Phases (each ≈ 1/8 of the synodic month):
	 *   New Moon · Waxing Crescent · First Quarter · Waxing Gibbous
	 *   Full Moon · Waning Gibbous · Last Quarter  · Waning Crescent
	 *
	 * @param string $date The date to query.
	 * @return string One of the eight phase names above.
	 */
	public static function getMoonPhaseName(string $date): string
	{
		$age = self::getMoonAge($date);
		$synodic = DateEnum::MEAN_NEW_MOON_SYNODIC_COEFF;
		$eighth = $synodic / 8.0;   // ≈ 3.69 days per phase segment
		$half = $synodic / 2.0;
		$quarter = $synodic / 4.0;

		return match (true) {
			$age < $eighth => 'New Moon',
			$age < $quarter - $eighth / 2.0 => 'Waxing Crescent',
			$age < $quarter + $eighth / 2.0 => 'First Quarter',
			$age < $half - $eighth / 2.0 => 'Waxing Gibbous',
			$age < $half + $eighth / 2.0 => 'Full Moon',
			$age < $quarter * 3 - $eighth / 2.0 => 'Waning Gibbous',
			$age < $quarter * 3 + $eighth / 2.0 => 'Last Quarter',
			default => 'Waning Crescent',
		};
	}

	/**
	 * Get the approximate Moon illumination percentage for a given date.
	 *
	 * Computed via a cosine formula from the moon's phase angle.
	 * 0 % = new moon, 100 % = full moon.
	 *
	 * @param string $date The date to query.
	 * @return float Illumination as a percentage (0.00–100.00).
	 */
	public static function getMoonIllumination(string $date): float
	{
		$age = self::getMoonAge($date);
		$synodic = DateEnum::MEAN_NEW_MOON_SYNODIC_COEFF;
		$phase = $age / $synodic;                            // 0..1
		$illum = (1.0 - cos(2.0 * M_PI * $phase)) / 2.0;  // 0..1
		return round($illum * 100.0, 2);
	}

	/**
	 * Check whether the Moon is currently waxing (growing brighter).
	 *
	 * The Moon is waxing from new moon (age 0) to full moon (age ≈ 14.77).
	 *
	 * @param string $date The date to query.
	 * @return bool True if waxing, false if waning or full.
	 */
	public static function isMoonWaxing(string $date): bool
	{
		return self::getMoonAge($date) < (DateEnum::MEAN_NEW_MOON_SYNODIC_COEFF / 2.0);
	}

	/**
	 * Check whether the Moon is currently waning (growing dimmer).
	 *
	 * @param string $date The date to query.
	 * @return bool True if waning, false if waxing or new.
	 */
	public static function isMoonWaning(string $date): bool
	{
		return !self::isMoonWaxing($date);
	}

	/**
	 * Get the Gregorian date of the next new moon on or after a given date.
	 *
	 * @param string $date Starting date (any parseable format).
	 * @return string Gregorian date of the next new moon in 'Y-m-d' format.
	 */
	public static function getNextNewMoon(string $date): string
	{
		$jdn = self::getJulianDayNumber($date);
		$k = self::findKOnOrBefore($jdn);

		// If today is already a new-moon day, advance to the next one
		$currentNMjdn = (int) floor(self::computeNewMoonJD($k) + 0.5);
		$nextK = ($currentNMjdn === $jdn) ? $k + 1 : $k + 1;
		$nextJDN = (int) floor(self::computeNewMoonJD($nextK) + 0.5);

		return self::julianToGregorian((float) $nextJDN)->format('Y-m-d');
	}

	/**
	 * Get the Gregorian date of the next full moon on or after a given date.
	 *
	 * Full-moon JD ≈ new-moon JD + half synodic month.
	 *
	 * @param string $date Starting date (any parseable format).
	 * @return string Gregorian date of the next full moon in 'Y-m-d' format.
	 */
	public static function getNextFullMoon(string $date): string
	{
		$jdn = self::getJulianDayNumber($date);
		$synodic = DateEnum::MEAN_NEW_MOON_SYNODIC_COEFF;

		// Start from the new moon before $jdn, then add half a month
		$k = self::findKOnOrBefore($jdn);
		$fullMoonJD = self::computeNewMoonJD($k) + $synodic / 2.0;

		// Advance until the full moon is on or after $jdn
		while ((int) floor($fullMoonJD + 0.5) < $jdn) {
			$k++;
			$fullMoonJD = self::computeNewMoonJD($k) + $synodic / 2.0;
		}

		$fullMoonJDN = (int) floor($fullMoonJD + 0.5);
		return self::julianToGregorian((float) $fullMoonJDN)->format('Y-m-d');
	}

	/**
	 * Convert a Gregorian date to the Korean/Chinese Lunisolar (음력) date.
	 *
	 * Supported range: 1900-01-31 through 2050-12-31.
	 *
	 * Returned array keys:
	 *   'year'       (int)  – Lunar year number
	 *   'month'      (int)  – Lunar month (1–12)
	 *   'day'        (int)  – Lunar day  (1–30)
	 *   'is_leap'    (bool) – True when the month is an intercalary (윤달) month
	 *   'leap_month' (int)  – The leap-month number of that year (0 = none)
	 *   'zodiac'     (string) – Chinese zodiac animal in English
	 *   'stem_branch'(string) – 간지 (干支) notation
	 *
	 * @param string $date Gregorian date in any parseable format.
	 * @return array{
	 * 	day: int, 
	 * 	is_leap: bool, 
	 * 	leap_month: mixed, 
	 * 	month: int, 
	 * 	stem_branch: string, 
	 * 	year: int, 
	 * 	zodiac: string
	 * }
	 * @throws InvalidArgumentException If the date is outside the supported range.
	 */
	public static function solarToLunar(string $date): array
	{
		$dt = new DateTime($date);
		$solarYear = (int) $dt->format(DateStringFormat::YearFullNumeric->value);
		$solarJDN = self::getJulianDayNumber($date);

		// Determine which lunar year the solar date belongs to.
		// A solar date before this year's LNY belongs to the PREVIOUS lunar year.
		$lunarYear = $solarYear;
		if (!isset(self::$lunarNewYearDates[$solarYear])) {
			throw new InvalidArgumentException("Date '{$date}' is outside the supported range (1900–2050).");
		}

		[$lnyMonth, $lnyDay] = self::$lunarNewYearDates[$solarYear];
		$lnyJDN = self::getJulianDayNumber(sprintf('%04d-%02d-%02d', $solarYear, $lnyMonth, $lnyDay));

		if ($solarJDN < $lnyJDN) {
			// Before this Gregorian year's LNY → previous lunar year
			$lunarYear--;
			if (!isset(self::$lunarNewYearDates[$lunarYear])) {
				throw new InvalidArgumentException("Date '{$date}' is outside the supported range (1900–2050).");
			}
			[$lnyMonth, $lnyDay] = self::$lunarNewYearDates[$lunarYear];
			$lnyJDN = self::getJulianDayNumber(sprintf('%04d-%02d-%02d', $lunarYear, $lnyMonth, $lnyDay));
		}

		// k of the new moon that started the lunar year (= LNY new moon)
		$kStart = self::findKOnOrBefore($lnyJDN);

		// k of the new moon that started the current lunar month
		$kTarget = self::findKOnOrBefore($solarJDN);

		// How many new moons have elapsed since LNY (0 = still in month 1)
		$monthOffset = $kTarget - $kStart;

		// Map the offset to a month number, handling the leap month
		['month' => $lunarMonth, 'isLeap' => $isLeap] = self::offsetToLunarMonth($lunarYear, $monthOffset);

		// Lunar day = days since the start of this lunar month + 1
		$currentMonthNewMoonJDN = (int) floor(self::computeNewMoonJD($kTarget) + 0.5);
		$lunarDay = $solarJDN - $currentMonthNewMoonJDN + 1;

		return [
			'year' => $lunarYear,
			'month' => $lunarMonth,
			'day' => $lunarDay,
			'is_leap' => $isLeap,
			'leap_month' => self::$lunarLeapMonths[$lunarYear] ?? 0,
			'zodiac' => self::getChineseZodiac($lunarYear),
			'stem_branch' => self::getStemBranch($lunarYear),
		];
	}

	/**
	 * Convert a Korean/Chinese Lunisolar (음력) date to a Gregorian date.
	 *
	 * Supported range: lunar years 1900–2050.
	 *
	 * @param int  $lunarYear  Lunar year (e.g. 2024).
	 * @param int  $lunarMonth Lunar month (1–12).
	 * @param int  $lunarDay   Lunar day  (1–30).
	 * @param bool $isLeapMonth True to request the intercalary copy of
	 *                          $lunarMonth (윤달). Ignored if that year
	 *                          has no intercalary month for $lunarMonth.
	 * @return string Gregorian date in 'Y-m-d' format.
	 * @throws InvalidArgumentException If the year is outside the supported range.
	 */
	public static function lunarToSolar(int $lunarYear, int $lunarMonth, int $lunarDay, bool $isLeapMonth = false): string
	{
		if (!isset(self::$lunarNewYearDates[$lunarYear])) {
			throw new InvalidArgumentException("Lunar year {$lunarYear} is outside the supported range (1900–2050).");
		}

		[$lnyMonth, $lnyDay] = self::$lunarNewYearDates[$lunarYear];
		$lnyJDN = self::getJulianDayNumber(sprintf('%04d-%02d-%02d', $lunarYear, $lnyMonth, $lnyDay));

		// k of the LNY new moon
		$kStart = self::findKOnOrBefore($lnyJDN);

		// Offset of the requested month within this lunar year
		$offset = self::lunarMonthToOffset($lunarYear, $lunarMonth, $isLeapMonth);
		$kTarget = $kStart + $offset;

		// JD of the first day of the requested lunar month
		$monthStartJDN = (int) floor(self::computeNewMoonJD($kTarget) + 0.5);

		// Add the (day – 1) offset to get the Julian Day of the target date
		$targetJDN = $monthStartJDN + $lunarDay - 1;

		return self::julianToGregorian((float) $targetJDN)->format('Y-m-d');
	}

	/**
	 * Get the Gregorian date on which Lunar New Year (설날 / 春節) falls
	 * for a given Gregorian year.
	 *
	 * Supported range: 1900–2050.
	 *
	 * @param int $year The Gregorian year.
	 * @return string Lunar New Year date in 'Y-m-d' format.
	 * @throws InvalidArgumentException If the year is outside the supported range.
	 */
	public static function getLunarNewYearDate(int $year): string
	{
		if (!isset(self::$lunarNewYearDates[$year])) {
			throw new InvalidArgumentException("Year {$year} is outside the supported range (1900–2050).");
		}
		[$month, $day] = self::$lunarNewYearDates[$year];
		return sprintf('%04d-%02d-%02d', $year, $month, $day);
	}

	/**
	 * Get the intercalary (leap / 윤달) month number for a given lunar year.
	 *
	 * Returns 0 if the year has no intercalary month.
	 *
	 * @param int $lunarYear The lunar year.
	 * @return int Leap month (1–12), or 0 if none.
	 */
	public static function getLunarLeapMonth(int $lunarYear): int
	{
		return self::$lunarLeapMonths[$lunarYear] ?? 0;
	}

	/**
	 * Check whether a given lunar year contains an intercalary (윤달) month.
	 *
	 * @param int $lunarYear The lunar year to check.
	 * @return bool True if the year has a leap month.
	 */
	public static function isLunarLeapYear(int $lunarYear): bool
	{
		return isset(self::$lunarLeapMonths[$lunarYear]);
	}

	/**
	 * Get the number of months in a given lunar year.
	 *
	 * A regular lunar year has 12 months; a year with an intercalary
	 * month has 13 months.
	 *
	 * @param int $lunarYear The lunar year.
	 * @return int 12 or 13.
	 */
	public static function getLunarMonthCount(int $lunarYear): int
	{
		return self::isLunarLeapYear($lunarYear) ? 13 : 12;
	}

	/**
	 * Get the number of days in a specific lunar month.
	 *
	 * The exact length is derived by computing the Julian Day of the
	 * current month's new moon and the next new moon, then taking the
	 * difference (either 29 or 30 days).
	 *
	 * Supported range: lunar years 1900–2050.
	 *
	 * @param int  $lunarYear  The lunar year.
	 * @param int  $lunarMonth The lunar month (1–12).
	 * @param bool $isLeap     True to query the intercalary copy.
	 * @return int 29 or 30.
	 */
	public static function getLunarMonthDays(int $lunarYear, int $lunarMonth, bool $isLeap = false): int
	{
		if (!isset(self::$lunarNewYearDates[$lunarYear])) {
			throw new InvalidArgumentException("Year {$lunarYear} is outside the supported range (1900–2050).");
		}

		[$lnyMonth, $lnyDay] = self::$lunarNewYearDates[$lunarYear];
		$lnyJDN = self::getJulianDayNumber(sprintf('%04d-%02d-%02d', $lunarYear, $lnyMonth, $lnyDay));
		$kStart = self::findKOnOrBefore($lnyJDN);
		$offset = self::lunarMonthToOffset($lunarYear, $lunarMonth, $isLeap);
		$kTarget = $kStart + $offset;

		$startJDN = (int) floor(self::computeNewMoonJD($kTarget) + 0.5);
		$nextJDN = (int) floor(self::computeNewMoonJD($kTarget + 1.0) + 0.5);

		return $nextJDN - $startJDN; // always 29 or 30
	}

	/**
	 * Get the Chinese/Korean zodiac animal (띠) for a given year in English.
	 *
	 * The 12-year animal cycle is anchored to year 4 CE (甲子 / Rat).
	 * Call with the *lunar* year for a culturally precise result; calling
	 * with the solar year is accurate for most of the year (dates after
	 * Lunar New Year).
	 *
	 * @param int $year The (lunar) year.
	 * @return string Animal name in English (e.g. 'Dragon').
	 */
	public static function getChineseZodiac(int $year): string
	{
		static $animals = [
		'Rat',
		'Ox',
		'Tiger',
		'Rabbit',
		'Dragon',
		'Snake',
		'Horse',
		'Goat',
		'Monkey',
		'Rooster',
		'Dog',
		'Pig',
		];
		$index = (($year - 4) % 12 + 12) % 12;
		return $animals[$index];
	}

	/**
	 * Get the Korean zodiac animal name (띠) in Korean for a given year.
	 *
	 * @param int $year The (lunar) year.
	 * @return string Korean animal name (e.g. '용').
	 */
	public static function getKoreanZodiac(int $year): string
	{
		static $animals = [
		'쥐',
		'소',
		'범',
		'토끼',
		'용',
		'뱀',
		'말',
		'양',
		'원숭이',
		'닭',
		'개',
		'돼지',
		];
		$index = (($year - 4) % 12 + 12) % 12;
		return $animals[$index];
	}

	/**
	 * Get the Heavenly Stem (천간 / 天干) for a given year.
	 *
	 * The ten stems cycle with period 10 anchored to 4 CE (甲).
	 * Returned as "Korean(Hanja)" notation, e.g. "갑(甲)".
	 *
	 * @param int $year The (lunar) year.
	 * @return string Heavenly stem, e.g. '갑(甲)'.
	 */
	public static function getHeavenlyStem(int $year): string
	{
		static $stems = [
		'갑(甲)',
		'을(乙)',
		'병(丙)',
		'정(丁)',
		'무(戊)',
		'기(己)',
		'경(庚)',
		'신(辛)',
		'임(壬)',
		'계(癸)',
		];
		$index = (($year - 4) % 10 + 10) % 10;
		return $stems[$index];
	}

	/**
	 * Get the Earthly Branch (지지 / 地支) for a given year.
	 *
	 * The twelve branches cycle with period 12, each corresponding to a
	 * zodiac animal.  Returned as "Korean(Hanja)" notation, e.g. "자(子)".
	 *
	 * @param int $year The (lunar) year.
	 * @return string Earthly branch, e.g. '자(子)'.
	 */
	public static function getEarthlyBranch(int $year): string
	{
		static $branches = [
		'자(子)',
		'축(丑)',
		'인(寅)',
		'묘(卯)',
		'진(辰)',
		'사(巳)',
		'오(午)',
		'미(未)',
		'신(申)',
		'유(酉)',
		'술(戌)',
		'해(亥)',
		];
		$index = (($year - 4) % 12 + 12) % 12;
		return $branches[$index];
	}

	/**
	 * Get the full Stem-Branch (간지 / 干支) year name for a given year.
	 *
	 * Combines a Heavenly Stem and an Earthly Branch to form a name in
	 * the 60-year sexagenary cycle (예: "갑진(甲辰)").
	 *
	 * @param int $year The (lunar) year.
	 * @return string Stem-branch name, e.g. '갑진(甲辰)'.
	 */
	public static function getStemBranch(int $year): string
	{
		static $stems = ['갑', '을', '병', '정', '무', '기', '경', '신', '임', '계'];
		static $branches = ['자', '축', '인', '묘', '진', '사', '오', '미', '신', '유', '술', '해'];
		static $stemsCh = ['甲', '乙', '丙', '丁', '戊', '己', '庚', '辛', '壬', '癸'];
		static $branchesCh = ['子', '丑', '寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥'];

		$si = (($year - 4) % 10 + 10) % 10;
		$bi = (($year - 4) % 12 + 12) % 12;

		return "{$stems[$si]}{$branches[$bi]}({$stemsCh[$si]}{$branchesCh[$bi]})";
	}

	/**
	 * Get a human-readable lunar year label combining the stem-branch name
	 * and the zodiac animal in both English and Korean.
	 *
	 * Example: "갑진(甲辰)년 – Dragon / 용띠"
	 *
	 * @param int $year The (lunar) year.
	 * @return string Descriptive year label.
	 */
	public static function getLunarYearLabel(int $year): string
	{
		$stemBranch = self::getStemBranch($year);
		$zodiacEn = self::getChineseZodiac($year);
		$zodiacKo = self::getKoreanZodiac($year);
		return "{$stemBranch}년 – {$zodiacEn} / {$zodiacKo}띠";
	}

	/**
	 * Get the number of days from today until a future date.
	 *
	 * Returns a negative value if the target date is in the past.
	 *
	 * @param string $targetDate The target date string.
	 * @return int Days remaining (positive = future, negative = past).
	 */
	public static function getCountdownDays(string $targetDate): int
	{
		$now = new DateTime('today');
		$target = new DateTime((new DateTime($targetDate))->format('Y-m-d'));
		$diff = $now->diff($target);
		return $diff->invert === 0 ? (int) $diff->days : -(int) $diff->days;
	}

	/**
	 * Get the calendar date exactly halfway between two dates.
	 *
	 * The midpoint is computed at the second level and returned as
	 * 'Y-m-d H:i:s'.
	 *
	 * @param string $date1 The first date string.
	 * @param string $date2 The second date string.
	 * @return string Midpoint datetime in 'Y-m-d H:i:s' format.
	 */
	public static function getMidpointDate(string $date1, string $date2): string
	{
		$ts1 = (new DateTime($date1))->getTimestamp();
		$ts2 = (new DateTime($date2))->getTimestamp();
		$midTs = (int) (($ts1 + $ts2) / 2);
		return (new DateTime('@' . $midTs))->setTimezone(new DateTimeZone(date_default_timezone_get()))->format('Y-m-d H:i:s');
	}

	/**
	 * Calculate how far a date has progressed through a period, as a
	 * percentage between 0.0 (at start) and 100.0 (at or past end).
	 *
	 * @param string $start   Period start date.
	 * @param string $end     Period end date.
	 * @param string $current Date to evaluate (defaults to 'now').
	 * @return float Progress percentage, clamped to [0.0, 100.0].
	 */
	public static function getDateProgressPercentage(string $start, string $end, string $current = 'now'): float
	{
		$tsStart = (new DateTime($start))->getTimestamp();
		$tsEnd = (new DateTime($end))->getTimestamp();
		$tsCurrent = (new DateTime($current))->getTimestamp();

		if ($tsEnd <= $tsStart) {
			return 0.0;
		}

		$pct = ($tsCurrent - $tsStart) / ($tsEnd - $tsStart) * 100.0;
		return round(min(100.0, max(0.0, $pct)), 2);
	}

	/**
	 * Split a date range into chunks of a fixed number of days.
	 *
	 * Each chunk is an array with 'start' and 'end' keys.
	 * The final chunk may be shorter than the requested chunk size.
	 *
	 * @param string $start     Range start date (inclusive).
	 * @param string $end       Range end date (inclusive).
	 * @param int    $chunkDays Number of days per chunk (≥ 1).
	 * @return array<int, array{start: string, end: string}>
	 */
	public static function splitDateRangeIntoChunks(string $start, string $end, int $chunkDays = 7): array
	{
		if ($chunkDays < 1) {
			throw new InvalidArgumentException('chunkDays must be at least 1.');
		}

		$chunks = [];
		$current = new DateTime($start);
		$endDt = new DateTime($end);

		while ($current <= $endDt) {
			$chunkStart = clone $current;
			$current->modify('+' . ($chunkDays - 1) . ' days');
			$chunkEnd = clone (($current <= $endDt) ? $current : $endDt);
			$chunks[] = [
				'start' => $chunkStart->format('Y-m-d'),
				'end' => $chunkEnd->format('Y-m-d'),
			];
			$current->modify('+1 day');
		}

		return $chunks;
	}

	/**
	 * Re-format a date string from one format to another.
	 *
	 * @param string $date       The input date string.
	 * @param string $fromFormat The format of the input (as per PHP date format).
	 * @param string $toFormat   The desired output format.
	 * @return string The reformatted date string, or empty string on failure.
	 */
	public static function convertDateFormat(string $date, string $fromFormat, string $toFormat): string
	{
		$dt = DateTime::createFromFormat($fromFormat, $date);
		return $dt !== false ? $dt->format($toFormat) : '';
	}

	/**
	 * Generate a fixed-length sequence of dates starting from a given date.
	 *
	 * @param string $start    Starting date.
	 * @param int    $count    Number of dates to generate (including start).
	 * @param string $interval A PHP relative format modifier, e.g. '+1 day',
	 *                         '+2 weeks', '+1 month'.
	 * @param string $format   Output format for each date (default 'Y-m-d').
	 * @return array<int, string> Array of formatted date strings.
	 */
	public static function generateDateSequence(string $start, int $count, string $interval = '+1 day', string $format = 'Y-m-d'): array
	{
		$dates = [];
		$current = new DateTime($start);
		for ($i = 0; $i < $count; $i++) {
			$dates[] = $current->format($format);
			$current->modify($interval);
		}
		return $dates;
	}

	/**
	 * Check whether a given date is the first day of its month.
	 *
	 * @param string $date The date string to check.
	 * @return bool True if the date is the 1st of the month.
	 */
	public static function isFirstDayOfMonth(string $date): bool
	{
		return (int) (new DateTime($date))->format(DateStringFormat::DayOfMonthWithoutLeadingZeros->value) === 1;
	}

	/**
	 * Check whether a given date is the last day of its month.
	 *
	 * @param string $date The date string to check.
	 * @return bool True if the date is the last day of the month.
	 */
	public static function isLastDayOfMonth(string $date): bool
	{
		$dt = new DateTime($date);
		return (int) $dt->format(DateStringFormat::DayOfMonthWithoutLeadingZeros->value) === (int) $dt->format(DateStringFormat::MonthNumberOfDays->value);
	}

	/**
	 * Count how many full or partial calendar weeks a given month spans.
	 *
	 * A week is counted if any day of that month falls within it.
	 * The week is assumed to start on Monday (ISO 8601).
	 *
	 * @param int $year  The Gregorian year.
	 * @param int $month The month (1–12).
	 * @return int Number of calendar weeks (typically 4 or 5, sometimes 6).
	 */
	public static function getCalendarWeeksInMonth(int $year, int $month): int
	{
		$firstDay = new DateTime("{$year}-{$month}-01");
		$lastDay = new DateTime("{$year}-{$month}-" . $firstDay->format(DateStringFormat::MonthNumberOfDays->value));

		$startWeek = (int) $firstDay->format(DateStringFormat::WeekNumberIso8601->value);
		$endWeek = (int) $lastDay->format(DateStringFormat::WeekNumberIso8601->value);

		// Handle year-boundary wrapping (e.g. Dec 31 in week 1 of next year)
		if ($endWeek < $startWeek) {
			// The month crosses into the next ISO year
			$weeksInYear = (int) (new DateTime("{$year}-12-28"))->format(DateStringFormat::WeekNumberIso8601->value);
			return ($weeksInYear - $startWeek + 1) + $endWeek;
		}

		return $endWeek - $startWeek + 1;
	}

	/**
	 * Get the century number for a given year.
	 *
	 * Years 1–100 are the 1st century, 101–200 the 2nd, etc.
	 *
	 * @param int $year The Gregorian year (> 0).
	 * @return int The century number.
	 */
	public static function getCentury(int $year): int
	{
		return (int) ceil($year / 100);
	}

	/**
	 * Get the decade number (decade × 10) for a given year.
	 *
	 * E.g. 2024 → 2020, 1995 → 1990.
	 *
	 * @param int $year The Gregorian year.
	 * @return int Start year of the decade.
	 */
	public static function getDecade(int $year): int
	{
		return (int) floor($year / 10) * 10;
	}

	/**
	 * Get the millennium number for a given year.
	 *
	 * Years 1–1000 are millennium 1, 1001–2000 are millennium 2, etc.
	 *
	 * @param int $year The Gregorian year (> 0).
	 * @return int The millennium number.
	 */
	public static function getMillennium(int $year): int
	{
		return (int) ceil($year / 1000);
	}

	/**
	 * Check whether two date ranges share at least one common day
	 * (i.e. the gap between them is zero or negative).
	 *
	 * @param string $start1 First period start.
	 * @param string $end1   First period end.
	 * @param string $start2 Second period start.
	 * @param string $end2   Second period end.
	 * @return bool True if there is at least one overlapping day.
	 */
	public static function doPeriodsShareADay(string $start1, string $end1, string $start2, string $end2): bool
	{
		$s1 = new DateTime($start1);
		$e1 = new DateTime($end1);
		$s2 = new DateTime($start2);
		$e2 = new DateTime($end2);
		return $s1 <= $e2 && $s2 <= $e1;
	}

	/**
	 * Return the gap (in days) between two non-overlapping date ranges.
	 *
	 * Returns 0 if the ranges touch or overlap.
	 *
	 * @param string $end1   End of the first period.
	 * @param string $start2 Start of the second period.
	 * @return int Gap in days (≥ 0).
	 */
	public static function getDayGapBetweenPeriods(string $end1, string $start2): int
	{
		$e1 = new DateTime($end1);
		$s2 = new DateTime($start2);
		if ($s2 <= $e1) {
			return 0; // Overlapping or touching – no gap
		}
		return (int) $e1->diff($s2)->days;
	}

	/**
	 * Get all dates in a given month that fall on a specific weekday.
	 *
	 * @param int $year    The year.
	 * @param int $month   The month (1–12).
	 * @param int $weekday Target weekday (0 = Sunday, 6 = Saturday).
	 * @return array<int, string> Array of 'Y-m-d' strings.
	 */
	public static function getWeekdayDatesInMonth(int $year, int $month, int $weekday): array
	{
		$dates = [];
		$current = new DateTime("{$year}-{$month}-01");
		$endDay = (int) $current->format(DateStringFormat::MonthNumberOfDays->value);

		for ($d = 1; $d <= $endDay; $d++) {
			$current->setDate($year, $month, $d);
			if ((int) $current->format(DateStringFormat::DayOfWeekNumeric->value) === $weekday) {
				$dates[] = $current->format('Y-m-d');
			}
		}
		return $dates;
	}

	/**
	 * Check whether a given date string represents a valid calendar date
	 * in the specified format.
	 *
	 * Delegates to the existing isValidDate() method with a default format.
	 *
	 * @param string $date   The date string to validate.
	 * @param string $format Expected format (default 'Y-m-d').
	 * @return bool True if the date is both parseable and semantically valid.
	 */
	public static function isValidCalendarDate(string $date, string $format = 'Y-m-d'): bool
	{
		// Re-uses the existing isValidDate() from the class
		return self::isValidDate($date, $format);
	}

	/**
	 * Return a locale-aware, human-friendly date string.
	 *
	 * Examples (format = 'full'):
	 *   en_US → "Tuesday, May 20, 2025"
	 *   ko_KR → "2025년 5월 20일 화요일"
	 *
	 * Falls back to 'Y-m-d' when IntlDateFormatter is unavailable.
	 *
	 * @param string $date   The date string to format.
	 * @param string $locale An IETF locale tag (e.g. 'en_US', 'ko_KR').
	 * @param string $style  'full', 'long', 'medium', or 'short'.
	 * @return string Locale-formatted date string.
	 */
	public static function formatLocalized(string $date, string $locale = 'en_US', string $style = 'medium'): string
	{
		if (!class_exists('\IntlDateFormatter')) {
			// Fallback when the intl extension is not loaded
			return (new DateTime($date))->format('Y-m-d');
		}

		$styleMap = [
			'full' => \IntlDateFormatter::FULL,
			'long' => \IntlDateFormatter::LONG,
			'medium' => \IntlDateFormatter::MEDIUM,
			'short' => \IntlDateFormatter::SHORT,
		];
		$intlStyle = $styleMap[$style] ?? \IntlDateFormatter::MEDIUM;

		$formatter = new \IntlDateFormatter($locale, $intlStyle, \IntlDateFormatter::NONE, null, null);

		$ts = (new DateTime($date))->getTimestamp();
		return $formatter->format($ts) ?: (new DateTime($date))->format('Y-m-d');
	}

	/**
	 * Calculate the number of complete calendar months a person has lived,
	 * treating partial months as complete only when the day-of-month in
	 * the reference date is ≥ the day-of-month of the birthdate.
	 *
	 * @param string $birthdate  Birthdate in any parseable format.
	 * @param string $reference  Reference date (defaults to today).
	 * @return int Total months of age.
	 */
	public static function getAgeInCompletedMonths(string $birthdate, string $reference = 'today'): int
	{
		$birth = new DateTime($birthdate);
		$ref = new DateTime($reference);
		$diff = $birth->diff($ref);
		return $diff->y * 12 + $diff->m;
	}

	/**
	 * Snap a datetime to the nearest multiple of $minutes minutes.
	 *
	 * This method is an alias / upgrade of the existing
	 * roundDateTimeToMinutes() with the same semantics.
	 *
	 * @param string $date    Input datetime string.
	 * @param int    $minutes Interval in minutes (must be > 0).
	 * @return string Rounded datetime in 'Y-m-d H:i:s' format.
	 */
	public static function snapToNearestMinuteInterval(string $date, int $minutes): string
	{
		if ($minutes <= 0) {
			throw new InvalidArgumentException('minutes must be greater than 0.');
		}
		$ts = (new DateTime($date))->getTimestamp();
		$interval = $minutes * 60;
		$snapped = (int) round($ts / $interval) * $interval;
		return (new DateTime('@' . $snapped))
			->setTimezone(new DateTimeZone(date_default_timezone_get()))
			->format('Y-m-d H:i:s');
	}

	/**
	 * Get the number of complete years between two dates,
	 * taking leap years and exact calendar days into account.
	 *
	 * A negative value is returned when $date1 is after $date2.
	 *
	 * @param string $date1 The first date string.
	 * @param string $date2 The second date string.
	 * @return int Complete years (may be negative).
	 */
	public static function diffInCompleteYears(string $date1, string $date2): int
	{
		$d1 = new DateTime($date1);
		$d2 = new DateTime($date2);
		$diff = $d1->diff($d2);
		return $diff->invert ? -$diff->y : $diff->y;
	}

	/**
	 * Determine whether a date falls inside (inclusive) a recurring annual
	 * window defined by a month-day start and month-day end.
	 *
	 * Useful for checking seasons, fiscal-window rules, subscription
	 * renewal windows, etc.
	 *
	 * Example: isInAnnualWindow('2024-08-15', '07-01', '08-31') → true
	 *
	 * @param string $date      The date to test.
	 * @param string $mmddStart Window start as 'MM-DD'.
	 * @param string $mmddEnd   Window end   as 'MM-DD'.
	 * @return bool True if $date falls within the annual window.
	 */
	public static function isInAnnualWindow(string $date, string $mmddStart, string $mmddEnd): bool
	{
		$dt = new DateTime($date);
		$year = $dt->format(DateStringFormat::YearFullNumeric->value);
		$md = $dt->format('m-d');

		// Windows that do not cross a year boundary
		if ($mmddStart <= $mmddEnd) {
			return $md >= $mmddStart && $md <= $mmddEnd;
		}

		// Windows that cross Dec 31 / Jan 1 (e.g. Nov 01 – Feb 28)
		return $md >= $mmddStart || $md <= $mmddEnd;
	}

	/**
	 * Get the hour component (0–23) from a datetime string.
	 *
	 * @param string $date The input datetime string.
	 * @return int Hour of day.
	 */
	public static function getHourFromDate(string $date): int
	{
		return (int) (new DateTime($date))->format(DateStringFormat::TimeHour24WithoutLeadingZeros->value);
	}

	/**
	 * Get the minute component (0–59) from a datetime string.
	 *
	 * @param string $date The input datetime string.
	 * @return int Minute of hour.
	 */
	public static function getMinuteFromDate(string $date): int
	{
		return (int) (new DateTime($date))->format(DateStringFormat::TimeMinutesWithLeadingZeros->value);
	}

	/**
	 * Get the second component (0–59) from a datetime string.
	 *
	 * @param string $date The input datetime string.
	 * @return int Second of minute.
	 */
	public static function getSecondFromDate(string $date): int
	{
		return (int) (new DateTime($date))->format(DateStringFormat::TimeSecondsWithLeadingZeros->value);
	}

	/**
	 * Extract the calendar date portion (Y-m-d) from a datetime string.
	 *
	 * @param string $date The input datetime string.
	 * @return string Date in Y-m-d format.
	 */
	public static function extractDatePart(string $date): string
	{
		return (new DateTime($date))->format('Y-m-d');
	}

	/**
	 * Extract the time portion (H:i:s) from a datetime string.
	 *
	 * @param string $date The input datetime string.
	 * @return string Time in H:i:s format.
	 */
	public static function extractTimePart(string $date): string
	{
		return (new DateTime($date))->format('H:i:s');
	}

	/**
	 * Combine a calendar date and a time string into one datetime.
	 *
	 * @param string $date     Date in Y-m-d (or any parseable date-only string).
	 * @param string $time     Time in H:i:s or H:i format.
	 * @param string $format   Output format (default Y-m-d H:i:s).
	 * @return string Combined datetime.
	 */
	public static function combineDateAndTime(string $date, string $time, string $format = 'Y-m-d H:i:s'): string
	{
		$dateOnly = self::extractDatePart($date);
		$dt = new DateTime("{$dateOnly} {$time}");
		return $dt->format($format);
	}

	/**
	 * Replace the time portion of a datetime while keeping the calendar date.
	 *
	 * @param string $date   Base datetime or date string.
	 * @param string $time   New time in H:i:s or H:i format.
	 * @param string $format Output format (default Y-m-d H:i:s).
	 * @return string Datetime with updated time.
	 */
	public static function setTimeOnDate(string $date, string $time, string $format = 'Y-m-d H:i:s'): string
	{
		return self::combineDateAndTime($date, $time, $format);
	}

	/**
	 * Subtract hours from a datetime string.
	 *
	 * @param string $date   Input datetime.
	 * @param int    $hours  Hours to subtract.
	 * @param string $format Output format.
	 * @return string Resulting datetime.
	 */
	public static function subtractHours(string $date, int $hours, string $format = 'Y-m-d H:i:s'): string
	{
		return self::addHours($date, -$hours, $format);
	}

	/**
	 * Subtract minutes from a datetime string.
	 *
	 * @param string $date    Input datetime.
	 * @param int    $minutes Minutes to subtract.
	 * @param string $format  Output format.
	 * @return string Resulting datetime.
	 */
	public static function subtractMinutes(string $date, int $minutes, string $format = 'Y-m-d H:i:s'): string
	{
		return self::addMinutes($date, -$minutes, $format);
	}

	/**
	 * Subtract seconds from a datetime string.
	 *
	 * @param string $date    Input datetime.
	 * @param int    $seconds Seconds to subtract.
	 * @param string $format  Output format.
	 * @return string Resulting datetime.
	 */
	public static function subtractSeconds(string $date, int $seconds, string $format = 'Y-m-d H:i:s'): string
	{
		return self::addSeconds($date, -$seconds, $format);
	}

	/**
	 * Subtract weeks from a date string.
	 *
	 * @param string $date   Input date.
	 * @param int    $weeks  Weeks to subtract.
	 * @param string $format Output format.
	 * @return string Resulting date.
	 */
	public static function subtractWeeks(string $date, int $weeks, string $format = 'Y-m-d'): string
	{
		return self::addWeeks($date, -$weeks, $format);
	}

	/**
	 * Get the start of the hour for a datetime (Y-m-d H:00:00).
	 *
	 * @param string $date Input datetime string.
	 * @return string Start of hour.
	 */
	public static function getStartOfHour(string $date): string
	{
		$dt = new DateTime($date);
		$dt->setTime((int) $dt->format(DateStringFormat::TimeHour24WithoutLeadingZeros->value), 0, 0);
		return $dt->format('Y-m-d H:i:s');
	}

	/**
	 * Get the end of the hour for a datetime (Y-m-d H:59:59).
	 *
	 * @param string $date Input datetime string.
	 * @return string End of hour.
	 */
	public static function getEndOfHour(string $date): string
	{
		$dt = new DateTime($date);
		$dt->setTime((int) $dt->format(DateStringFormat::TimeHour24WithoutLeadingZeros->value), 59, 59);
		return $dt->format('Y-m-d H:i:s');
	}

	/**
	 * Get the start of the minute for a datetime (Y-m-d H:i:00).
	 *
	 * @param string $date Input datetime string.
	 * @return string Start of minute.
	 */
	public static function getStartOfMinute(string $date): string
	{
		$dt = new DateTime($date);
		$dt->setTime((int) $dt->format(DateStringFormat::TimeHour24WithoutLeadingZeros->value), (int) $dt->format(DateStringFormat::TimeMinutesWithLeadingZeros->value), 0);
		return $dt->format('Y-m-d H:i:s');
	}

	/**
	 * Get the end of the minute for a datetime (Y-m-d H:i:59).
	 *
	 * @param string $date Input datetime string.
	 * @return string End of minute.
	 */
	public static function getEndOfMinute(string $date): string
	{
		$dt = new DateTime($date);
		$dt->setTime((int) $dt->format(DateStringFormat::TimeHour24WithoutLeadingZeros->value), (int) $dt->format(DateStringFormat::TimeMinutesWithLeadingZeros->value), 59);
		return $dt->format('Y-m-d H:i:s');
	}

	/**
	 * Truncate a datetime to midnight (start of calendar day).
	 *
	 * @param string $date   Input datetime.
	 * @param string $format Output format.
	 * @return string Truncated datetime.
	 */
	public static function truncateToDate(string $date, string $format = 'Y-m-d H:i:s'): string
	{
		return self::getStartOfDay($date);
	}

	/**
	 * Truncate a datetime to the start of its hour.
	 *
	 * @param string $date   Input datetime.
	 * @param string $format Output format.
	 * @return string Truncated datetime.
	 */
	public static function truncateToHour(string $date, string $format = 'Y-m-d H:i:s'): string
	{
		$dt = new DateTime(self::getStartOfHour($date));
		return $dt->format($format);
	}

	/**
	 * Compare two datetimes: -1 if $date1 < $date2, 0 if equal, 1 if greater.
	 *
	 * @param string $date1 First datetime.
	 * @param string $date2 Second datetime.
	 * @return int Comparison result.
	 */
	public static function compareDates(string $date1, string $date2): int
	{
		$ts1 = (new DateTime($date1))->getTimestamp();
		$ts2 = (new DateTime($date2))->getTimestamp();
		return $ts1 <=> $ts2;
	}

	/**
	 * Check whether two datetimes represent the same instant.
	 *
	 * @param string $date1 First datetime.
	 * @param string $date2 Second datetime.
	 * @return bool True when timestamps are equal.
	 */
	public static function isEqual(string $date1, string $date2): bool
	{
		return self::compareDates($date1, $date2) === 0;
	}

	/**
	 * Check whether a date string falls on today (calendar day).
	 *
	 * @param string $date Input date or datetime string.
	 * @return bool True if the calendar day is today.
	 */
	public static function isTodayString(string $date): bool
	{
		return self::isSameDay($date, self::getToday());
	}

	/**
	 * Check whether a date string falls on yesterday.
	 *
	 * @param string $date Input date or datetime string.
	 * @return bool True if the calendar day is yesterday.
	 */
	public static function isYesterdayString(string $date): bool
	{
		return self::isSameDay($date, self::getYesterday());
	}

	/**
	 * Check whether a date string falls on tomorrow.
	 *
	 * @param string $date Input date or datetime string.
	 * @return bool True if the calendar day is tomorrow.
	 */
	public static function isTomorrowString(string $date): bool
	{
		return self::isSameDay($date, self::getTomorrow());
	}

	/**
	 * Check whether two datetimes share the same second.
	 *
	 * @param string $date1 First datetime.
	 * @param string $date2 Second datetime.
	 * @return bool True when both resolve to the same second.
	 */
	public static function isSameSecond(string $date1, string $date2): bool
	{
		return (new DateTime($date1))->format('Y-m-d H:i:s') === (new DateTime($date2))->format('Y-m-d H:i:s');
	}

	/**
	 * Check whether a datetime is exactly midnight (00:00:00).
	 *
	 * @param string $date Input datetime string.
	 * @return bool True at midnight.
	 */
	public static function isMidnight(string $date): bool
	{
		$dt = new DateTime($date);
		return $dt->format('H:i:s') === '00:00:00';
	}

	/**
	 * Check whether a datetime is exactly noon (12:00:00).
	 *
	 * @param string $date Input datetime string.
	 * @return bool True at noon.
	 */
	public static function isNoon(string $date): bool
	{
		$dt = new DateTime($date);
		return $dt->format('H:i:s') === '12:00:00';
	}

	/**
	 * Validate a datetime string against an explicit format.
	 *
	 * @param string $date   Input string.
	 * @param string $format Expected format (default Y-m-d H:i:s).
	 * @return bool True when parseable and semantically valid.
	 */
	public static function isValidDateTime(string $date, string $format = 'Y-m-d H:i:s'): bool
	{
		return self::isValidDate($date, $format);
	}

	/**
	 * Signed difference in seconds ($date1 − $date2).
	 *
	 * @param string $date1 Minuend datetime.
	 * @param string $date2 Subtrahend datetime.
	 * @return int Signed seconds.
	 */
	public static function diffInSecondsSigned(string $date1, string $date2): int
	{
		return (new DateTime($date1))->getTimestamp() - (new DateTime($date2))->getTimestamp();
	}

	/**
	 * Signed difference in hours ($date1 − $date2).
	 *
	 * @param string $date1 Minuend datetime.
	 * @param string $date2 Subtrahend datetime.
	 * @return float Signed hours.
	 */
	public static function diffInHoursSigned(string $date1, string $date2): float
	{
		return self::diffInSecondsSigned($date1, $date2) / 3600;
	}

	/**
	 * Signed difference in days ($date1 − $date2), based on calendar midnights.
	 *
	 * @param string $date1 Minuend date.
	 * @param string $date2 Subtrahend date.
	 * @return int Signed whole days.
	 */
	public static function diffInDaysSigned(string $date1, string $date2): int
	{
		$d1 = new DateTime(self::extractDatePart($date1));
		$d2 = new DateTime(self::extractDatePart($date2));
		$diff = $d1->diff($d2);
		$days = (int) $diff->days;
		return $diff->invert ? -$days : $days;
	}

	/**
	 * Floor a datetime to the nearest lower multiple of $minutes.
	 *
	 * @param string $date    Input datetime.
	 * @param int    $minutes Interval in minutes (must be > 0).
	 * @return string Floored datetime in Y-m-d H:i:s format.
	 */
	public static function floorDateTimeToMinutes(string $date, int $minutes): string
	{
		if ($minutes <= 0) {
			throw new InvalidArgumentException('minutes must be greater than 0.');
		}
		$ts = (new DateTime($date))->getTimestamp();
		$interval = $minutes * 60;
		$floored = (int) (floor($ts / $interval) * $interval);
		return (new DateTime('@' . $floored))
			->setTimezone(new DateTimeZone(date_default_timezone_get()))
			->format('Y-m-d H:i:s');
	}

	/**
	 * Ceil a datetime to the nearest higher multiple of $minutes.
	 *
	 * @param string $date    Input datetime.
	 * @param int    $minutes Interval in minutes (must be > 0).
	 * @return string Ceiled datetime in Y-m-d H:i:s format.
	 */
	public static function ceilDateTimeToMinutes(string $date, int $minutes): string
	{
		if ($minutes <= 0) {
			throw new InvalidArgumentException('minutes must be greater than 0.');
		}
		$ts = (new DateTime($date))->getTimestamp();
		$interval = $minutes * 60;
		$ceiled = (int) (ceil($ts / $interval) * $interval);
		return (new DateTime('@' . $ceiled))
			->setTimezone(new DateTimeZone(date_default_timezone_get()))
			->format('Y-m-d H:i:s');
	}

	/**
	 * Format a datetime as an Atom/RFC 3339 extended string.
	 *
	 * @param string      $date     Input datetime.
	 * @param string|null $timezone Optional IANA timezone.
	 * @return string Atom-formatted string.
	 */
	public static function toAtom(string $date, ?string $timezone = null): string
	{
		$dt = new DateTime($date);
		if ($timezone !== null) {
			$dt->setTimezone(new DateTimeZone($timezone));
		}
		return $dt->format(DateTime::ATOM);
	}

	/**
	 * Format a datetime for HTTP headers (RFC 7231 / IMF-fixdate).
	 *
	 * @param string $date Input datetime.
	 * @return string HTTP date string.
	 */
	public static function toHttpDate(string $date): string
	{
		return (new DateTime($date))->format(DateTime::RFC7231);
	}

	/**
	 * Get the current UTC datetime string.
	 *
	 * @param string $format Output format (default Y-m-d H:i:s).
	 * @return string Current UTC time.
	 */
	public static function utcNow(string $format = 'Y-m-d H:i:s'): string
	{
		return self::now($format, 'UTC');
	}

	/**
	 * Get a timezone offset label such as "+09:00" or "Z" for UTC.
	 *
	 * @param string      $date     Context datetime.
	 * @param string|null $timezone IANA timezone (default system timezone).
	 * @return string Offset string.
	 */
	public static function getTimezoneOffsetString(string $date, ?string $timezone = null): string
	{
		$tz = new DateTimeZone($timezone ?? date_default_timezone_get());
		$dt = new DateTime($date, $tz);
		return $dt->format(DateStringFormat::TimezoneGmtOffsetWithColon->value);
	}

	/**
	 * Classify the time-of-day period for a datetime.
	 *
	 * Returns one of: night, morning, afternoon, evening.
	 *
	 * @param string $date Input datetime.
	 * @return string Period name.
	 */
	public static function getTimeOfDayPeriod(string $date): string
	{
		$hour = self::getHourFromDate($date);
		return match (true) {
			$hour >= 5 && $hour < 12 => 'morning',
			$hour >= 12 && $hour < 17 => 'afternoon',
			$hour >= 17 && $hour < 21 => 'evening',
			default => 'night',
		};
	}

	/**
	 * Get the semester number (1 or 2) for a date using a configurable start month.
	 *
	 * @param string $date              Input date.
	 * @param int    $academicYearStart First month of academic year (default 3 = March).
	 * @return int 1 or 2.
	 */
	public static function getSemester(string $date, int $academicYearStart = 3): int
	{
		$month = self::getMonthFromDate($date);
		$offset = (($month - $academicYearStart) % 12 + 12) % 12;
		return $offset < 6 ? 1 : 2;
	}

	/**
	 * Get the bimester index (1–6) within a calendar year.
	 *
	 * @param string $date Input date.
	 * @return int Bimester 1–6.
	 */
	public static function getBimester(string $date): int
	{
		return (int) ceil(self::getMonthFromDate($date) / 2);
	}

	/**
	 * Add a relative interval to the current moment.
	 *
	 * @param string $modifier PHP relative format (e.g. '+3 days', 'next monday').
	 * @param string $format   Output format.
	 * @return string Resulting datetime.
	 */
	public static function fromNow(string $modifier, string $format = 'Y-m-d H:i:s'): string
	{
		return self::modify('now', $modifier, $format);
	}

	/**
	 * Calendar days from today until a target date (signed).
	 *
	 * @param string $targetDate Target date string.
	 * @return int Positive = future, negative = past.
	 */
	public static function daysFromToday(string $targetDate): int
	{
		return self::diffInDaysSigned($targetDate, self::getToday());
	}

	/**
	 * Whole hours from now until a target datetime (signed).
	 *
	 * @param string $targetDatetime Target datetime string.
	 * @return float Signed hours.
	 */
	public static function hoursFromNow(string $targetDatetime): float
	{
		return self::diffInHoursSigned($targetDatetime, self::now());
	}

	/**
	 * Check whether a datetime lies within the past N minutes from now.
	 *
	 * @param string $date    Datetime to test.
	 * @param int    $minutes Window size in minutes.
	 * @return bool True when $date is between now−$minutes and now.
	 */
	public static function isWithinPastMinutes(string $date, int $minutes): bool
	{
		$now = new DateTime();
		$dt = new DateTime($date);
		$start = (clone $now)->modify("-{$minutes} minutes");
		return $dt >= $start && $dt <= $now;
	}

	/**
	 * Check whether a datetime lies within the next N hours from now.
	 *
	 * @param string $date  Datetime to test.
	 * @param int    $hours Window size in hours.
	 * @return bool True when $date is between now and now+$hours.
	 */
	public static function isWithinNextHours(string $date, int $hours): bool
	{
		$now = new DateTime();
		$dt = new DateTime($date);
		$end = (clone $now)->modify("+{$hours} hours");
		return $dt >= $now && $dt <= $end;
	}

	/**
	 * Next calendar occurrence of an annual MM-DD pattern on or after $fromDate.
	 *
	 * @param string $mmdd     Month-day as MM-DD.
	 * @param string $fromDate Starting date (default today).
	 * @return string Next occurrence in Y-m-d format.
	 */
	public static function getNextAnnualOccurrence(string $mmdd, string $fromDate = 'today'): string
	{
		$from = new DateTime($fromDate);
		$candidate = new DateTime($from->format(DateStringFormat::YearFullNumeric->value) . '-' . $mmdd);
		if ($candidate < $from) {
			$candidate->modify('+1 year');
		}
		return $candidate->format('Y-m-d');
	}

	/**
	 * Previous calendar occurrence of an annual MM-DD pattern on or before $fromDate.
	 *
	 * @param string $mmdd     Month-day as MM-DD.
	 * @param string $fromDate Reference date (default today).
	 * @return string Previous occurrence in Y-m-d format.
	 */
	public static function getPreviousAnnualOccurrence(string $mmdd, string $fromDate = 'today'): string
	{
		$from = new DateTime($fromDate);
		$candidate = new DateTime($from->format(DateStringFormat::YearFullNumeric->value) . '-' . $mmdd);
		if ($candidate > $from) {
			$candidate->modify('-1 year');
		}
		return $candidate->format('Y-m-d');
	}

	/**
	 * List all Saturday/Sunday dates between two dates (inclusive).
	 *
	 * @param string $start Start date.
	 * @param string $end   End date.
	 * @return array<int, string> Weekend dates in Y-m-d format.
	 */
	public static function getWeekendsBetween(string $start, string $end): array
	{
		$weekends = [];
		$current = new DateTime($start);
		$endDt = new DateTime($end);
		if ($current > $endDt) {
			throw new RuntimeException('End date must be greater than start date.');
		}
		while ($current <= $endDt) {
			$formatted = $current->format('Y-m-d');
			if (self::isWeekend($formatted)) {
				$weekends[] = $formatted;
			}
			$current->modify('+1 day');
		}
		return $weekends;
	}

	/**
	 * Count weekend days between two dates (inclusive).
	 *
	 * @param string $start Start date.
	 * @param string $end   End date.
	 * @return int Number of weekend days.
	 */
	public static function countWeekendDaysBetween(string $start, string $end): int
	{
		return count(self::getWeekendsBetween($start, $end));
	}

	/**
	 * Sort an array of date strings ascending.
	 *
	 * @param array<int, string> $dates Date strings.
	 * @return array<int, string> Sorted copy.
	 */
	public static function sortDates(array $dates): array
	{
		$sorted = $dates;
		usort($sorted, static fn(string $a, string $b): int => self::compareDates($a, $b));
		return $sorted;
	}

	/**
	 * Return the median date from an array of date strings.
	 *
	 * @param array<int, string> $dates Non-empty date strings.
	 * @return string Median date in Y-m-d H:i:s format.
	 */
	public static function medianDate(array $dates): string
	{
		if ($dates === []) {
			throw new InvalidArgumentException('dates must not be empty.');
		}
		$sorted = self::sortDates($dates);
		$middle = (int) floor(count($sorted) / 2);
		if (count($sorted) % 2 === 1) {
			return $sorted[$middle];
		}
		return self::getMidpointDate($sorted[$middle - 1], $sorted[$middle]);
	}

	/**
	 * Human-readable duration between two datetimes (largest units first).
	 *
	 * @param string $start Start datetime.
	 * @param string $end   End datetime.
	 * @return string e.g. "2 days 3 hours 15 minutes".
	 */
	public static function formatDurationBetween(string $start, string $end): string
	{
		$diff = (new DateTime($start))->diff(new DateTime($end));
		$parts = [];
		if ($diff->y > 0) {
			$parts[] = $diff->y . ' year' . ($diff->y === 1 ? '' : 's');
		}
		if ($diff->m > 0) {
			$parts[] = $diff->m . ' month' . ($diff->m === 1 ? '' : 's');
		}
		if ($diff->d > 0) {
			$parts[] = $diff->d . ' day' . ($diff->d === 1 ? '' : 's');
		}
		if ($diff->h > 0) {
			$parts[] = $diff->h . ' hour' . ($diff->h === 1 ? '' : 's');
		}
		if ($diff->i > 0) {
			$parts[] = $diff->i . ' minute' . ($diff->i === 1 ? '' : 's');
		}
		if ($diff->s > 0 || $parts === []) {
			$parts[] = $diff->s . ' second' . ($diff->s === 1 ? '' : 's');
		}
		$sign = $diff->invert ? '-' : '';
		return $sign . implode(' ', $parts);
	}

	/**
	 * Format a second count as an ISO-8601 duration (PT…).
	 *
	 * @param int $seconds Total seconds (may be negative).
	 * @return string ISO-8601 duration, e.g. PT1H2M3S.
	 */
	public static function formatIso8601Duration(int $seconds): string
	{
		$sign = $seconds < 0 ? '-' : '';
		$abs = abs($seconds);
		$hours = intdiv($abs, 3600);
		$minutes = intdiv($abs % 3600, 60);
		$secs = $abs % 60;
		$result = 'PT';
		if ($hours > 0) {
			$result .= $hours . 'H';
		}
		if ($minutes > 0) {
			$result .= $minutes . 'M';
		}
		if ($secs > 0 || ($hours === 0 && $minutes === 0)) {
			$result .= $secs . 'S';
		}
		return $sign . $result;
	}

	/**
	 * Get microtime as a string with microsecond precision.
	 *
	 * @param string $format Output format supporting 'u' for microseconds.
	 * @return string Formatted datetime with microseconds when requested.
	 */
	public static function nowWithMicroseconds(string $format = 'Y-m-d H:i:s.u'): string
	{
		return (new DateTime())->format($format);
	}

	/**
	 * Calculate the Sun's true ecliptic longitude (in degrees) at a given
	 * number of Julian centuries since J2000.0.
	 *
	 * This method implements a simplified version of the solar position
	 * calculation, suitable for general use but not for high-precision
	 * astronomical applications.
	 *
	 * @param float $T Julian centuries since J2000.0.
	 * @return float Sun's true ecliptic longitude in degrees.
	 */
	public static function solarCoordinates(float $T)
	{
		$k = 2 * M_PI / 360;
		$M = 357.52910 + 35999.05030 * $T - 0.0001559 * $T * $T - 0.00000048 * $T * $T * $T; //mean anomaly, degree
		$L0 = DateEnum::SUN_MEAN_LONGITUDE_J2000_DEG + DateEnum::SOLAR_L0_RATE_DEG_PER_CY * $T + 0.0003032 * $T * $T; // mean longitude, degree
		$DL = (1.914600 - 0.004817 * $T - 0.000014 * $T * $T) * sin($k * $M) + (0.019993 - 0.000101 * $T) * sin($k * 2 * $M) + 0.000290 * sin($k * 3 * $M); // Sun's equation of center

		return $L0 + $DL; // true longitude, degree
	}

	/**
	 * Compute the Greenwich Mean Sidereal Time (GMST) in degrees for a given
	 * Julian Day.
	 *
	 * This method uses a simplified formula based on the J2000.0 epoch and
	 * is suitable for general use but not for high-precision astronomical
	 * applications.
	 *
	 * @param float $jd Julian Day.
	 * @return float GMST in degrees (0° ≤ GMST < 360°).
	 */
	public static function computeSiderealTime(float $jd)
	{
		$T = ($jd - DateEnum::JD_J2000) / DateEnum::DAYS_PER_JULIAN_CENTURY;

		return DateEnum::GMST_J2000_DEG + DateEnum::GMST_DEG_PER_DAY * ($jd - DateEnum::JD_J2000) + 0.000387933 * $T * $T - $T * $T * $T / DateEnum::GMST_CENTURY3_DENOM;
	}

	/**
	 * Convert the Sun's ecliptic longitude to equatorial coordinates (declination and right ascension).
	 *
	 * This method calculates the Sun's declination (δ) and right ascension (RA)
	 * based on its ecliptic longitude (L) and the obliquity of the ecliptic (ε).
	 *
	 * @param float $JD Julian Day for which to perform the conversion.
	 * @param float $L  Sun's true ecliptic longitude in degrees.
	 * @return array{0: float, 1: float} Array containing [declination δ in degrees, right ascension RA in hours].
	 */
	public static function convertEclipticLongitudeL(float $JD, float $L)
	{
		$T = ($JD - DateEnum::JD_J2000) / DateEnum::DAYS_PER_JULIAN_CENTURY;

		// obliquity eps of ecliptic:
		$eps = 23.0 + 26.0 / 60.0 + 21.448 / 3600.0 - (46.8150 * $T + 0.00059 * $T * $T - 0.001813 * $T * $T * $T) / 3600;
		$X = cos($L);
		$Y = cos($eps) * sin($L);
		$Z = sin($eps) * sin($L);
		$R = sqrt(1.0 - $Z * $Z);

		$delta = atan($Z / $R); // in degrees
		$RA = (24 / 180) * atan($Y / ($X + $R)); // in hours

		return [$delta, $RA];
	}

	/**
	 * Get the synodic period (in days) of a given planet.
	 *
	 * The synodic period is the time it takes for a planet to return to the
	 * same position relative to the Sun as seen from Earth, which is relevant
	 * for lunar month calculations and other astronomical phenomena.
	 *
	 * @param string $planet The name of the planet (e.g. 'MOON', 'MERCURY').
	 * @return float Synodic period in days, or 366 if unknown.
	 */
	public static function getSynodicPeriod(string $planet)
	{
		switch (strtoupper($planet)) {
			case "MOON":
				return DateEnum::MOON_SYNODIC_PERIOD;
			case "MERCURY":
				return DateEnum::MERCURY_SYNODIC_PERIOD;
			case "VENUS":
				return DateEnum::VENUS_SYNODIC_PERIOD;
			case "MARS":
				return DateEnum::MARS_SYNODIC_PERIOD;
			case "JUPITER":
				return DateEnum::JUPITER_SYNODIC_PERIOD;
			case "SATURN":
				return DateEnum::SATURN_SYNODIC_PERIOD;
			case "URANUS":
				return DateEnum::URANUS_SYNODIC_PERIOD;
			case "NEPTUNE":
				return DateEnum::NEPTUNE_SYNODIC_PERIOD;
			case "PLUTO":
				return DateEnum::PLUTO_SYNODIC_PERIOD;
		}

		return 366;
	}

	/**
	 * Get a signed human-readable diff such as "+2 days" or "−3 hours".
	 *
	 * The sign reflects whether $date2 is after (+) or before (−) $date1.
	 *
	 * @param string $date1 Reference (origin) datetime.
	 * @param string $date2 Target datetime.
	 * @param string $unit  Unit: 'seconds', 'minutes', 'hours', 'days', 'weeks', 'months', 'years'.
	 * @return string Signed diff string.
	 */
	public static function signedDiff(string $date1, string $date2, string $unit = 'days'): string
	{
		$raw = self::getDateDiff($date1, $date2, $unit);
		$sign = self::isAfter($date2, $date1) ? '+' : '-';
		return "{$sign}{$raw} {$unit}";
	}

	/**
	 * Calculate approximate sunrise and sunset times for a given date and location.
	 *
	 * Uses PHP's built-in date_sunrise() / date_sunset() functions (NOAA algorithm).
	 * Times are returned in the specified timezone.
	 *
	 * @param string $date      Calendar date in any parseable format.
	 * @param float  $latitude  Geographic latitude in decimal degrees (+N, −S).
	 * @param float  $longitude Geographic longitude in decimal degrees (+E, −W).
	 * @param string $timezone  IANA timezone for the output times (default 'UTC').
	 * @return array{sunrise: string, sunset: string} Times in 'H:i:s' format.
	 */
	public static function getSunriseSunset(string $date, float $latitude, float $longitude, string $timezone = 'UTC'): array
	{
		$ts = (new DateTime($date))->getTimestamp();
		$tz = new DateTimeZone($timezone);
		$zenith = 90.833; // Official zenith angle
		$offset = $tz->getOffset(new DateTime($date, $tz)) / 3600;

		$sunriseTs = date_sunrise($ts, SUNFUNCS_RET_TIMESTAMP, $latitude, $longitude, $zenith, $offset);
		$sunsetTs = date_sunset($ts, SUNFUNCS_RET_TIMESTAMP, $latitude, $longitude, $zenith, $offset);

		$fmt = static fn(int|float|false $t): string => $t === false ? 'N/A' : (new DateTime('@' . (int) $t))->setTimezone($tz)->format('H:i:s');

		return [
			'sunrise' => $fmt($sunriseTs),
			'sunset' => $fmt($sunsetTs),
		];
	}

	/**
	 * Find all gaps between a list of date ranges within a bounding window.
	 *
	 * Input elements must have 'start' and 'end' keys ('Y-m-d' strings).
	 * Ranges are merged before gap detection.
	 *
	 * @param array<int, array{start: string, end: string}> $ranges   Date ranges.
	 * @param string                                         $windowStart Bounding window start.
	 * @param string                                         $windowEnd   Bounding window end.
	 * @return array<int, array{start: string, end: string}> Gap ranges.
	 */
	public static function getDateRangeGaps(array $ranges, string $windowStart, string $windowEnd): array
	{
		$merged = self::mergeDateRanges($ranges);
		$gaps = [];
		$cursor = $windowStart;

		foreach ($merged as $range) {
			if ($range['start'] > $windowEnd) {
				break;
			}

			$rangeStart = max($range['start'], $windowStart);
			$rangeEnd = min($range['end'], $windowEnd);

			if ($cursor < $rangeStart) {
				// There is a gap from $cursor to the day before $rangeStart
				$gapEnd = (new DateTime($rangeStart))->modify('-1 day')->format('Y-m-d');
				$gaps[] = ['start' => $cursor, 'end' => $gapEnd];
			}

			$cursor = (new DateTime($rangeEnd))->modify('+1 day')->format('Y-m-d');
		}

		// Trailing gap
		if ($cursor <= $windowEnd) {
			$gaps[] = ['start' => $cursor, 'end' => $windowEnd];
		}

		return $gaps;
	}

	/**
	 * Get the full date range (start and end) of the quarter that contains a date.
	 *
	 * @param string $date Input date.
	 * @return array{start: string, end: string} Quarter boundaries in 'Y-m-d' format.
	 */
	public static function getQuarterDateRange(string $date): array
	{
		return [
			'start' => self::getQuarterStart($date),
			'end' => self::getQuarterEnd($date),
		];
	}

	/**
	 * Expand a recurring date pattern into individual dates.
	 *
	 * Generates every date that matches a specific weekday mask within a range.
	 *
	 * @param string            $start    Range start date.
	 * @param string            $end      Range end date.
	 * @param array<int, int>   $weekdays Weekdays to include (0=Sun…6=Sat).
	 * @return array<int, string> Matching dates in 'Y-m-d' format.
	 */
	public static function getRecurringWeekdayDates(string $start, string $end, array $weekdays): array
	{
		if ($weekdays === []) {
			return [];
		}

		$dates = [];
		$current = new DateTime($start);
		$endDt = new DateTime($end);

		if ($current > $endDt) {
			throw new RuntimeException('End date must be greater than start date.');
		}

		while ($current <= $endDt) {
			if (in_array((int) $current->format(DateStringFormat::DayOfWeekNumeric->value), $weekdays, true)) {
				$dates[] = $current->format('Y-m-d');
			}
			$current->modify('+1 day');
		}

		return $dates;
	}

	/**
	 * Get the current datetime as a DateTimeImmutable instance.
	 *
	 * @param string|null $timezone Optional IANA timezone identifier.
	 * @return DateTimeImmutable Current moment.
	 */
	public static function nowImmutable(?string $timezone = null): DateTimeImmutable
	{
		$tz = $timezone ? new DateTimeZone($timezone) : null;
		return $tz ? new DateTimeImmutable('now', $tz) : new DateTimeImmutable();
	}

	/**
	 * Convert a date string to a DateTimeImmutable object.
	 *
	 * @param string      $date     Input date or datetime string.
	 * @param string|null $timezone Optional IANA timezone identifier.
	 * @return DateTimeImmutable The corresponding immutable object.
	 */
	public static function toDateTimeImmutable(string $date, ?string $timezone = null): DateTimeImmutable
	{
		$tz = $timezone ? new DateTimeZone($timezone) : null;
		return $tz
			? new DateTimeImmutable($date, $tz)
			: new DateTimeImmutable($date);
	}

	/**
	 * Compute the average (arithmetic mean) of an array of datetimes.
	 *
	 * The average is derived from Unix timestamps and returned as a
	 * formatted datetime string.
	 *
	 * @param array<int, string> $dates Non-empty array of datetime strings.
	 * @param string             $format Output format (default 'Y-m-d H:i:s').
	 * @return string Average datetime.
	 * @throws InvalidArgumentException If the array is empty.
	 */
	public static function averageDate(array $dates, string $format = 'Y-m-d H:i:s'): string
	{
		if ($dates === []) {
			throw new InvalidArgumentException('dates must not be empty.');
		}

		$sum = array_sum(array_map(static fn(string $d): int => (new DateTime($d))->getTimestamp(), $dates));
		$avg = (int) round($sum / count($dates));

		return (new DateTime('@' . $avg))
			->setTimezone(new DateTimeZone(date_default_timezone_get()))
			->format($format);
	}

	/**
	 * Format a number of seconds as "HH:MM" (without seconds).
	 *
	 * Handles negative values and totals exceeding 24 hours.
	 *
	 * @param int $seconds Total seconds (may be negative).
	 * @return string Formatted string, e.g. "01:30" or "-00:45".
	 */
	public static function secondsToHHMM(int $seconds): string
	{
		$sign = $seconds < 0 ? '-' : '';
		$abs = abs($seconds);
		$hours = intdiv($abs, 3600);
		$minutes = intdiv($abs % 3600, 60);
		return sprintf('%s%02d:%02d', $sign, $hours, $minutes);
	}

	/**
	 * Build a full calendar grid (ISO Mon–Sun weeks) for a given month.
	 *
	 * Each element is an associative array:
	 *   'date'          (string|null) – 'Y-m-d' or null for padding cells.
	 *   'in_month'      (bool)        – False for cells outside the target month.
	 *   'day_of_week'   (int)         – ISO weekday 1 (Mon) – 7 (Sun).
	 *
	 * @param int $year  The Gregorian year.
	 * @param int $month The month (1–12).
	 * @return array<int, array<int, array{date: string|null, in_month: bool, day_of_week: int}>>
	 *         Outer array = weeks; inner array = 7 days each.
	 */
	public static function getCalendarGrid(int $year, int $month): array
	{
		$firstDay = new DateTime("{$year}-{$month}-01");
		$lastDay = (clone $firstDay)->modify('last day of this month');

		// Start from the Monday on or before the 1st
		$gridStart = clone $firstDay;
		$dow = (int) $gridStart->format(DateStringFormat::DayOfWeekIso8601Numeric->value); // 1=Mon
		if ($dow > 1) {
			$gridStart->modify('-' . ($dow - 1) . ' days');
		}

		// End at the Sunday on or after the last day
		$gridEnd = clone $lastDay;
		$dowEnd = (int) $gridEnd->format(DateStringFormat::DayOfWeekIso8601Numeric->value);
		if ($dowEnd < 7) {
			$gridEnd->modify('+' . (7 - $dowEnd) . ' days');
		}

		$grid = [];
		$current = clone $gridStart;
		$week = [];

		while ($current <= $gridEnd) {
			$inMonth = ((int) $current->format(DateStringFormat::MonthNumericWithoutLeadingZeros->value) === $month);
			$week[] = [
				'date' => $current->format('Y-m-d'),
				'in_month' => $inMonth,
				'day_of_week' => (int) $current->format(DateStringFormat::DayOfWeekIso8601Numeric->value),
			];

			if (count($week) === 7) {
				$grid[] = $week;
				$week = [];
			}

			$current->modify('+1 day');
		}

		return $grid;
	}

	/**
	 * Signed difference in minutes ($date1 − $date2).
	 *
	 * @param string $date1 Minuend datetime.
	 * @param string $date2 Subtrahend datetime.
	 * @return float Signed minutes.
	 */
	public static function diffInMinutesSigned(string $date1, string $date2): float
	{
		return self::diffInSecondsSigned($date1, $date2) / 60;
	}

	/**
	 * Get the next calendar occurrence of an annual MM-DD pattern on or after $fromDate.
	 *
	 * @param string $mmdd     Month-day as 'MM-DD' (e.g. '12-25' for Christmas).
	 * @param string $fromDate Reference date (default 'today').
	 * @return string Next occurrence in 'Y-m-d' format.
	 */
	public static function getNextAnnualDate(string $mmdd, string $fromDate = 'today'): string
	{
		$from = new DateTime($fromDate);
		$candidate = new DateTime($from->format(DateStringFormat::YearFullNumeric->value) . '-' . $mmdd);

		if ($candidate < $from) {
			$candidate->modify('+1 year');
		}

		return $candidate->format('Y-m-d');
	}

	/**
	 * Get the most recent past occurrence of an annual MM-DD pattern on or before $fromDate.
	 *
	 * @param string $mmdd     Month-day as 'MM-DD'.
	 * @param string $fromDate Reference date (default 'today').
	 * @return string Previous occurrence in 'Y-m-d' format.
	 */
	public static function getPreviousAnnualDate(string $mmdd, string $fromDate = 'today'): string
	{
		$from = new DateTime($fromDate);
		$candidate = new DateTime($from->format(DateStringFormat::YearFullNumeric->value) . '-' . $mmdd);

		if ($candidate > $from) {
			$candidate->modify('-1 year');
		}

		return $candidate->format('Y-m-d');
	}

	/**
	 * Get the next occurrence of a specific Nth weekday of a month,
	 * on or after a given date.
	 *
	 * Example: next 2nd Monday on or after 2026-05-20.
	 *
	 * @param string $fromDate Starting date (any parseable format).
	 * @param int    $weekday  Target weekday (0 = Sunday … 6 = Saturday).
	 * @param int    $nth      Which occurrence in the month (1–5, default 1).
	 * @return string Date in 'Y-m-d' format.
	 * @throws RuntimeException If no occurrence is found within 13 months.
	 */
	public static function getNextNthWeekdayOccurrence(string $fromDate, int $weekday, int $nth = 1): string
	{
		$dt = new DateTime($fromDate);

		for ($i = 0; $i < 13; $i++) {
			$year = (int) $dt->format(DateStringFormat::YearFullNumeric->value);
			$month = (int) $dt->format(DateStringFormat::MonthNumericWithoutLeadingZeros->value);
			$candidate = self::getNthWeekdayOfMonth($year, $month, $nth, $weekday);

			if ($candidate >= $dt->format('Y-m-d')) {
				return $candidate;
			}

			$dt->modify('first day of next month');
		}

		throw new RuntimeException('Could not find next Nth weekday occurrence within 13 months.');
	}

	/**
	 * Format a date as a human-readable English string (default "May 20, 2026").
	 *
	 * @param string $date   Input date string.
	 * @param string $format PHP date format (default 'F j, Y').
	 * @return string Formatted date.
	 */
	public static function toReadableDate(string $date, string $format = 'F j, Y'): string
	{
		return (new DateTime($date))->format($format);
	}

	/**
	 * Format a date with the day expressed as an ordinal (e.g. "May 20th, 2026").
	 *
	 * @param string $date Input date string.
	 * @return string Date string with ordinal day suffix.
	 */
	public static function toOrdinalDate(string $date): string
	{
		$dt = new DateTime($date);
		$day = (int) $dt->format(DateStringFormat::DayOfMonthWithoutLeadingZeros->value);
		return $dt->format('F ') . self::dayWithSuffix($day) . $dt->format(', Y');
	}

	/**
	 * Count the number of calendar days that two date periods share.
	 *
	 * Returns 0 when the periods do not overlap.
	 *
	 * @param string $start1 Start of the first period.
	 * @param string $end1   End of the first period.
	 * @param string $start2 Start of the second period.
	 * @param string $end2   End of the second period.
	 * @return int Number of shared calendar days (inclusive).
	 */
	public static function getOverlappingDays(string $start1, string $end1, string $start2, string $end2): int
	{
		$overlap = self::getOverlapPeriod($start1, $end1, $start2, $end2);

		if ($overlap === null) {
			return 0;
		}

		return (int) self::getDateDiffInDays($overlap['start'], $overlap['end']) + 1;
	}

	/**
	 * Get the current Unix timestamp with microsecond precision.
	 *
	 * @return float Seconds since epoch with fractional microseconds.
	 */
	public static function getMicroTimestamp(): float
	{
		return microtime(true);
	}

	/**
	 * Get the number of days since the Unix epoch (1970-01-01) for a given date.
	 *
	 * Returns a negative value for dates before 1970.
	 *
	 * @param string $date Input date string.
	 * @return int Days since epoch.
	 */
	public static function getDaysSinceEpoch(string $date): int
	{
		$epoch = new DateTime('1970-01-01');
		$target = new DateTime((new DateTime($date))->format('Y-m-d'));
		$diff = $epoch->diff($target);
		return $diff->invert ? -(int) $diff->days : (int) $diff->days;
	}

	/**
	 * Convert a days-since-epoch count back to a date string.
	 *
	 * @param int    $days   Days since 1970-01-01 (may be negative).
	 * @param string $format Output format (default 'Y-m-d').
	 * @return string Resulting date string.
	 */
	public static function fromDaysSinceEpoch(int $days, string $format = 'Y-m-d'): string
	{
		return (new DateTime('1970-01-01'))->modify("{$days} days")->format($format);
	}

	/**
	 * Check whether a date range is valid (start is on or before end).
	 *
	 * @param string $start Range start date.
	 * @param string $end   Range end date.
	 * @return bool True if start ≤ end.
	 */
	public static function isValidDateRange(string $start, string $end): bool
	{
		return new DateTime($start) <= new DateTime($end);
	}

	/**
	 * Normalise a pair of dates so that the earlier one is always returned as 'start'.
	 *
	 * @param string $date1 One boundary of the range.
	 * @param string $date2 Other boundary of the range.
	 * @return array{start: string, end: string}
	 */
	public static function normalizeDateRange(string $date1, string $date2): array
	{
		if (self::isBefore($date1, $date2)) {
			return ['start' => $date1, 'end' => $date2];
		}
		return ['start' => $date2, 'end' => $date1];
	}

	/**
	 * Override individual calendar components (year / month / day) of a date.
	 *
	 * Pass null for any component to keep the original value.
	 *
	 * @param string   $date   Base date in any parseable format.
	 * @param int|null $year   Replacement year (null = keep original).
	 * @param int|null $month  Replacement month 1–12 (null = keep original).
	 * @param int|null $day    Replacement day 1–31 (null = keep original).
	 * @param string   $format Output format (default 'Y-m-d').
	 * @return string Modified date string.
	 */
	public static function setDateComponents(string $date, ?int $year = null, ?int $month = null, ?int $day = null, string $format = 'Y-m-d'): string
	{
		$dt = new DateTime($date);
		$dt->setDate($year ?? (int) $dt->format(DateStringFormat::YearFullNumeric->value), $month ?? (int) $dt->format(DateStringFormat::MonthNumericWithoutLeadingZeros->value), $day ?? (int) $dt->format(DateStringFormat::DayOfMonthWithoutLeadingZeros->value));
		return $dt->format($format);
	}

	/**
	 * Override the time components (hour / minute / second) of a datetime.
	 *
	 * Pass null for any component to keep the original value.
	 *
	 * @param string   $datetime Base datetime in any parseable format.
	 * @param int|null $hour     Replacement hour 0–23 (null = keep original).
	 * @param int|null $minute   Replacement minute 0–59 (null = keep original).
	 * @param int|null $second   Replacement second 0–59 (null = keep original).
	 * @param string   $format   Output format (default 'Y-m-d H:i:s').
	 * @return string Modified datetime string.
	 */
	public static function setTimeComponents(string $datetime, ?int $hour = null, ?int $minute = null, ?int $second = null, string $format = 'Y-m-d H:i:s'): string
	{
		$dt = new DateTime($datetime);
		$dt->setTime($hour ?? (int) $dt->format(DateStringFormat::TimeHour24WithoutLeadingZeros->value), $minute ?? (int) $dt->format(DateStringFormat::TimeMinutesWithLeadingZeros->value), $second ?? (int) $dt->format(DateStringFormat::TimeSecondsWithLeadingZeros->value));
		return $dt->format($format);
	}

	/**
	 * Check whether the time portion of a datetime falls within a clock-time range.
	 *
	 * Both boundary strings are plain time values ('09:00', '17:30:00').
	 * The range is inclusive on both ends.
	 * Overnight ranges (e.g. '22:00' – '06:00') are supported.
	 *
	 * @param string $datetime  Datetime to evaluate.
	 * @param string $startTime Range start time (H:i or H:i:s).
	 * @param string $endTime   Range end time   (H:i or H:i:s).
	 * @return bool True when the time component falls within the range.
	 */
	public static function isInTimeRange(string $datetime, string $startTime, string $endTime): bool
	{
		$fmt = 'H:i:s';
		$time = (new DateTime($datetime))->format($fmt);
		$start = (new DateTime("2000-01-01 {$startTime}"))->format($fmt);
		$end = (new DateTime("2000-01-01 {$endTime}"))->format($fmt);

		if ($start <= $end) {
			return $time >= $start && $time <= $end;
		}

		// Overnight range (wraps past midnight)
		return $time >= $start || $time <= $end;
	}

	/**
	 * Check whether a datetime falls in daytime hours (default 06:00–20:00).
	 *
	 * @param string $datetime  Input datetime string.
	 * @param int    $startHour Start of daytime (0–23, default 6).
	 * @param int    $endHour   End of daytime, exclusive (0–24, default 20).
	 * @return bool True during daytime.
	 */
	public static function isDaytime(string $datetime, int $startHour = 6, int $endHour = 20): bool
	{
		$hour = self::getHourFromDate($datetime);
		return $hour >= $startHour && $hour < $endHour;
	}

	/**
	 * Check whether a datetime falls in nighttime hours (default 20:00–06:00).
	 *
	 * @param string $datetime  Input datetime string.
	 * @param int    $startHour Start of nighttime (0–23, default 20).
	 * @param int    $endHour   End of nighttime, exclusive (0–24, default 6).
	 * @return bool True during nighttime.
	 */
	public static function isNighttime(string $datetime, int $startHour = 20, int $endHour = 6): bool
	{
		return !self::isDaytime($datetime, $endHour, $startHour);
	}

	/**
	 * Classify a person's age into a human-readable life-stage category.
	 *
	 * Ranges: infant (0–1) · toddler (2–3) · child (4–12) · teenager (13–17) ·
	 *         young adult (18–25) · adult (26–59) · senior (60+).
	 *
	 * @param string $birthdate Birthdate in any parseable format.
	 * @return string Life-stage label.
	 */
	public static function getAgeCategory(string $birthdate): string
	{
		$age = self::getAge($birthdate);

		return match (true) {
			$age <= 1 => 'infant',
			$age <= 3 => 'toddler',
			$age <= 12 => 'child',
			$age <= 17 => 'teenager',
			$age <= 25 => 'young adult',
			$age <= 59 => 'adult',
			default => 'senior',
		};
	}

	/**
	 * Determine which calendar week of the month (1–6) a date falls in.
	 *
	 * Week boundaries follow ISO 8601 (weeks start on Monday).
	 *
	 * @param string $date Input date string.
	 * @return int Week-of-month index, starting at 1.
	 */
	public static function getWeekOfMonth(string $date): int
	{
		$dt = new DateTime($date);
		$firstOfMonth = clone $dt;
		$firstOfMonth->modify('first day of this month');
		$firstDow = (int) $firstOfMonth->format(DateStringFormat::DayOfWeekIso8601Numeric->value); // 1 = Mon, 7 = Sun
		$dayOfMonth = (int) $dt->format(DateStringFormat::DayOfMonthWithoutLeadingZeros->value);
		return (int) ceil(($dayOfMonth + $firstDow - 1) / 7);
	}

	/**
	 * Get a complete diff breakdown between two datetimes as an associative array.
	 *
	 * Keys: years, months, days, hours, minutes, seconds, total_days, is_negative.
	 *
	 * @param string $date1 First datetime string.
	 * @param string $date2 Second datetime string.
	 * @return array<string, int|bool>
	 */
	public static function getFullDiffArray(string $date1, string $date2): array
	{
		$diff = (new DateTime($date1))->diff(new DateTime($date2));

		return [
			'years' => $diff->y,
			'months' => $diff->m,
			'days' => $diff->d,
			'hours' => $diff->h,
			'minutes' => $diff->i,
			'seconds' => $diff->s,
			'total_days' => (int) $diff->days,
			'is_negative' => (bool) $diff->invert,
		];
	}

	/**
	 * Get the total number of clock hours in a calendar day for a given timezone.
	 *
	 * Due to DST transitions, some days have 23 or 25 hours.
	 *
	 * @param string $date     Calendar date in any parseable format.
	 * @param string $timezone IANA timezone identifier (default 'UTC').
	 * @return float Total hours in the day (typically 23.0, 24.0, or 25.0).
	 */
	public static function getTotalHoursInDay(string $date, string $timezone = 'UTC'): float
	{
		$tz = new DateTimeZone($timezone);
		$dateStr = (new DateTime($date))->format('Y-m-d');
		$start = new DateTime("{$dateStr} 00:00:00", $tz);
		$end = clone $start;
		$end->modify('+1 day');
		return ($end->getTimestamp() - $start->getTimestamp()) / 3600;
	}

	/**
	 * Convert a Gregorian date to a Microsoft Excel serial date number.
	 *
	 * Excel's epoch is 1900-01-00 (serial = 0), with the classic but
	 * intentional 1900 leap-year bug: 1900-02-29 is treated as valid (serial 60).
	 * Dates from 1900-03-01 onwards are offset by 1 to compensate.
	 *
	 * @param string $date Input date in any parseable format.
	 * @return float Excel serial date number.
	 */
	public static function toExcelSerialDate(string $date): float
	{
		$epoch = new DateTime('1899-12-31');
		$target = new DateTime((new DateTime($date))->format('Y-m-d'));
		$days = (int) $epoch->diff($target)->days;
		return (float) ($days + 1); // +1 for the phantom 1900-02-29
	}

	/**
	 * Convert a Microsoft Excel serial date number back to a Gregorian date string.
	 *
	 * Accounts for Excel's historical 1900 leap-year bug.
	 *
	 * @param float  $serial Excel serial date number.
	 * @param string $format Output format (default 'Y-m-d').
	 * @return string Gregorian date string.
	 */
	public static function fromExcelSerialDate(float $serial, string $format = 'Y-m-d'): string
	{
		// Serials ≤ 60 include the phantom day; subtract 1 instead of 2.
		$adjusted = (int) $serial <= 60 ? (int) $serial - 1 : (int) $serial - 2;
		$dt = new DateTime('1900-01-01');
		$dt->modify("+{$adjusted} days");
		return $dt->format($format);
	}

	/**
	 * Calculate how far a datetime has progressed through its ISO week (Mon–Sun).
	 *
	 * Monday 00:00:00 = 0 %, Sunday 23:59:59 ≈ 100 %.
	 *
	 * @param string $date Input datetime string.
	 * @return float Progress percentage (0.00–100.00).
	 */
	public static function getWeekProgress(string $date): float
	{
		$dt = new DateTime($date);
		$dayOffset = ((int) $dt->format(DateStringFormat::DayOfWeekIso8601Numeric->value) - 1) * 86400; // ISO weekday 1–7, convert to 0-based seconds
		$timeOffset = (int) $dt->format(DateStringFormat::TimeHour24WithoutLeadingZeros->value) * 3600 + (int) $dt->format(DateStringFormat::TimeMinutesWithLeadingZeros->value) * 60 + (int) $dt->format(DateStringFormat::TimeSecondsWithLeadingZeros->value);
		return round(($dayOffset + $timeOffset) / (7 * 86400) * 100, 2);
	}

	/**
	 * Calculate how far a datetime has progressed through its calendar month.
	 *
	 * 1st 00:00:00 = 0 %, last day 23:59:59 ≈ 100 %.
	 *
	 * @param string $date Input datetime string.
	 * @return float Progress percentage (0.00–100.00).
	 */
	public static function getMonthProgress(string $date): float
	{
		$dt = new DateTime($date);
		$daysInMonth = (int) $dt->format(DateStringFormat::MonthNumberOfDays->value);
		$dayOffset = ((int) $dt->format(DateStringFormat::DayOfMonthWithoutLeadingZeros->value) - 1) * 86400;
		$timeOffset = (int) $dt->format(DateStringFormat::TimeHour24WithoutLeadingZeros->value) * 3600 + (int) $dt->format(DateStringFormat::TimeMinutesWithLeadingZeros->value) * 60 + (int) $dt->format(DateStringFormat::TimeSecondsWithLeadingZeros->value);
		return round(($dayOffset + $timeOffset) / ($daysInMonth * 86400) * 100, 2);
	}

	/**
	 * Calculate how far a datetime has progressed through its calendar year.
	 *
	 * Jan 1 00:00:00 = 0 %, Dec 31 23:59:59 ≈ 100 %.
	 *
	 * @param string $date Input datetime string.
	 * @return float Progress percentage (0.00–100.00).
	 */
	public static function getYearProgress(string $date): float
	{
		$dt = new DateTime($date);
		$year = (int) $dt->format(DateStringFormat::YearFullNumeric->value);
		$daysInYear = self::getDaysInYear($year);
		$dayOfYear = (int) $dt->format(DateStringFormat::DayOfYear->value); // 0-based
		$timeOffset = (int) $dt->format(DateStringFormat::TimeHour24WithoutLeadingZeros->value) * 3600 + (int) $dt->format(DateStringFormat::TimeMinutesWithLeadingZeros->value) * 60 + (int) $dt->format(DateStringFormat::TimeSecondsWithLeadingZeros->value);
		return round(($dayOfYear * 86400 + $timeOffset) / ($daysInYear * 86400) * 100, 2);
	}

	/**
	 * Get all holidays from a provided list that fall within a date range (inclusive).
	 *
	 * @param string             $start    Range start date.
	 * @param string             $end      Range end date.
	 * @param array<int, string> $holidays Holiday dates in 'Y-m-d' format.
	 * @return array<int, string> Matched holidays, values preserved.
	 */
	public static function getHolidaysInRange(string $start, string $end, array $holidays): array
	{
		$s = (new DateTime($start))->format('Y-m-d');
		$e = (new DateTime($end))->format('Y-m-d');
		return array_values(array_filter($holidays, static fn(string $h): bool => $h >= $s && $h <= $e));
	}

	/**
	 * Count how many holidays from a provided list fall within a date range (inclusive).
	 *
	 * @param string             $start    Range start date.
	 * @param string             $end      Range end date.
	 * @param array<int, string> $holidays Holiday dates in 'Y-m-d' format.
	 * @return int Number of holidays in the range.
	 */
	public static function countHolidaysInRange(string $start, string $end, array $holidays): int
	{
		return count(self::getHolidaysInRange($start, $end, $holidays));
	}

	/**
	 * Check whether a given date is in the provided holiday list.
	 *
	 * @param string             $date     Date to check.
	 * @param array<int, string> $holidays Holiday dates in 'Y-m-d' format.
	 * @return bool True if the date is a listed holiday.
	 */
	public static function isPublicHoliday(string $date, array $holidays): bool
	{
		return in_array((new DateTime($date))->format('Y-m-d'), $holidays, true);
	}

	/**
	 * Format a date range as a compact human-readable label.
	 *
	 * Same month & year   → "May 20–25, 2026"
	 * Same year, diff month → "Apr 28 – May 5, 2026"
	 * Different years     → "Dec 28, 2025 – Jan 4, 2026"
	 *
	 * @param string $start  Range start date.
	 * @param string $end    Range end date.
	 * @param string $format Base format for month + day portion (default 'M j').
	 * @return string Human-readable range label.
	 */
	public static function formatDateRangeLabel(string $start, string $end, string $format = 'M j'): string
	{
		$startDt = new DateTime($start);
		$endDt = new DateTime($end);

		if ($startDt->format('Y-m') === $endDt->format('Y-m')) {
			return $startDt->format($format) . '–' . $endDt->format('j, Y');
		}

		if ($startDt->format(DateStringFormat::YearFullNumeric->value) === $endDt->format(DateStringFormat::YearFullNumeric->value)) {
			return $startDt->format($format) . ' – ' . $endDt->format($format . ', Y');
		}

		return $startDt->format($format . ', Y') . ' – ' . $endDt->format($format . ', Y');
	}

	/**
	 * Get the first date of the fiscal year that contains the given date.
	 *
	 * @param string $date                 Input date.
	 * @param int    $fiscalYearStartMonth Month the fiscal year begins (default 4 = April).
	 * @return string Start date in 'Y-m-d' format.
	 */
	public static function getStartOfFiscalYear(string $date, int $fiscalYearStartMonth = 4): string
	{
		$fiscalYear = self::getFiscalYear($date, $fiscalYearStartMonth);
		return sprintf('%04d-%02d-01', $fiscalYear, $fiscalYearStartMonth);
	}

	/**
	 * Count the number of times a specific weekday occurs between two dates (inclusive).
	 *
	 * @param string $start   Start date (inclusive).
	 * @param string $end     End date (inclusive).
	 * @param int    $weekday Target weekday (0 = Sunday … 6 = Saturday).
	 * @return int Number of occurrences.
	 */
	public static function countWeekdayOccurrences(string $start, string $end, int $weekday): int
	{
		$current = new DateTime($start);
		$endDt = new DateTime($end);

		if ($current > $endDt) {
			throw new RuntimeException('End date must be greater than start date.');
		}

		$count = 0;
		while ($current <= $endDt) {
			if ((int) $current->format(DateStringFormat::DayOfWeekNumeric->value) === $weekday) {
				$count++;
			}
			$current->modify('+1 day');
		}

		return $count;
	}

	/**
	 * Check whether a string is a valid IANA timezone identifier.
	 *
	 * @param string $timezone The identifier to validate.
	 * @return bool True if PHP recognises the timezone.
	 */
	public static function isValidTimezone(string $timezone): bool
	{
		return in_array($timezone, DateTimeZone::listIdentifiers(), true);
	}

	/**
	 * Get the hour offset difference between two IANA timezones.
	 *
	 * A positive result means $timezone1 is ahead of $timezone2.
	 *
	 * @param string      $timezone1 First timezone identifier.
	 * @param string      $timezone2 Second timezone identifier.
	 * @param string|null $date      Context date for DST-aware calculation (default 'now').
	 * @return float Difference in hours (tz1 offset − tz2 offset).
	 */
	public static function getTimezonesDiffInHours(string $timezone1, string $timezone2, ?string $date = null): float
	{
		$dt = new DateTime($date ?? 'now');
		$off1 = (new DateTimeZone($timezone1))->getOffset($dt);
		$off2 = (new DateTimeZone($timezone2))->getOffset($dt);
		return ($off1 - $off2) / 3600;
	}

	/**
	 * Get a compact relative time descriptor (e.g. "2h ago", "in 3d").
	 *
	 * Unit selection (largest non-zero wins):
	 *   y = years · mo = months · w = weeks · d = days · h = hours · m = minutes · s = seconds
	 *
	 * @param string $date Target date string.
	 * @return string Compact relative time string.
	 */
	public static function getTimeAgoShort(string $date): string
	{
		$now = new DateTime();
		$target = new DateTime($date);
		$diff = $now->diff($target);
		$isPast = $target < $now;
		$totalDays = (int) $diff->days;

		[$value, $unit] = match (true) {
			$diff->y > 0 => [$diff->y, 'y'],
			$diff->m > 0 => [$diff->m, 'mo'],
			$totalDays >= 7 => [intdiv($totalDays, 7), 'w'],
			$totalDays > 0 => [$totalDays, 'd'],
			$diff->h > 0 => [$diff->h, 'h'],
			$diff->i > 0 => [$diff->i, 'm'],
			default => [$diff->s, 's'],
		};

		return $isPast ? "{$value}{$unit} ago" : "in {$value}{$unit}";
	}

	/**
	 * Merge a list of possibly-overlapping date ranges into the minimal set
	 * of non-overlapping, sorted ranges.
	 *
	 * Input / output element format: ['start' => 'Y-m-d', 'end' => 'Y-m-d'].
	 * Adjacent ranges (end of one = start of next) are also merged.
	 *
	 * @param array<int, array{start: string, end: string}> $ranges
	 * @return array<int, array{start: string, end: string}>
	 */
	public static function mergeDateRanges(array $ranges): array
	{
		if ($ranges === []) {
			return [];
		}

		usort($ranges, static fn(array $a, array $b): int => strcmp($a['start'], $b['start']));

		$merged = [];
		$current = $ranges[0];

		foreach (array_slice($ranges, 1) as $range) {
			if ($range['start'] <= $current['end']) {
				$current['end'] = max($current['end'], $range['end']);
			} else {
				$merged[] = $current;
				$current = $range;
			}
		}

		$merged[] = $current;
		return $merged;
	}

	/**
	 * Return the earliest date from a non-empty array of date strings.
	 *
	 * @param array<int, string> $dates Non-empty array of date strings.
	 * @return string Earliest date in 'Y-m-d H:i:s' format.
	 * @throws InvalidArgumentException If the array is empty.
	 */
	public static function getEarliestDate(array $dates): string
	{
		if ($dates === []) {
			throw new InvalidArgumentException('dates must not be empty.');
		}
		return self::min(...$dates);
	}

	/**
	 * Return the latest date from a non-empty array of date strings.
	 *
	 * @param array<int, string> $dates Non-empty array of date strings.
	 * @return string Latest date in 'Y-m-d H:i:s' format.
	 * @throws InvalidArgumentException If the array is empty.
	 */
	public static function getLatestDate(array $dates): string
	{
		if ($dates === []) {
			throw new InvalidArgumentException('dates must not be empty.');
		}
		return self::max(...$dates);
	}

	/**
	 * Check whether a datetime is expired (already in the past).
	 *
	 * Useful for token, coupon, or session expiry checks.
	 *
	 * @param string $date Expiry datetime string.
	 * @return bool True if the datetime has already passed.
	 */
	public static function isExpired(string $date): bool
	{
		return new DateTime($date) < new DateTime();
	}

	/**
	 * Check whether a date falls within the next N calendar days from now.
	 *
	 * @param string $date       Target date string.
	 * @param int    $withinDays Look-ahead window in days (default 7).
	 * @return bool True if the date is in (now, now + $withinDays].
	 */
	public static function isUpcoming(string $date, int $withinDays = 7): bool
	{
		$now = new DateTime();
		$target = new DateTime($date);
		$limit = (clone $now)->modify("+{$withinDays} days");
		return $target > $now && $target <= $limit;
	}

	/**
	 * Convert a datetime to a decimal hour value.
	 *
	 * 09:30:00 → 9.5 · 13:45:00 → 13.75 · 00:00:00 → 0.0
	 *
	 * @param string $datetime Input datetime string.
	 * @return float Decimal hour in [0.0, 24.0).
	 */
	public static function getDecimalHour(string $datetime): float
	{
		$dt = new DateTime($datetime);
		return (int) $dt->format(DateStringFormat::TimeHour24WithoutLeadingZeros->value) + (int) $dt->format(DateStringFormat::TimeMinutesWithLeadingZeros->value) / 60.0 + (int) $dt->format(DateStringFormat::TimeSecondsWithLeadingZeros->value) / 3600.0;
	}

	/**
	 * Convert a decimal hour value back to a time string.
	 *
	 * 9.5 → "09:30:00" · 13.75 → "13:45:00"
	 *
	 * @param float  $decimalHour Decimal hour (0.0–23.9̄).
	 * @param string $format      Output format (default 'H:i:s').
	 * @return string Formatted time string.
	 */
	public static function fromDecimalHour(float $decimalHour, string $format = 'H:i:s'): string
	{
		$totalSeconds = (int) round(abs($decimalHour) * 3600);
		$h = intdiv($totalSeconds, 3600) % 24;
		$m = intdiv($totalSeconds % 3600, 60);
		$s = $totalSeconds % 60;
		return (new DateTime('today'))->setTime($h, $m, $s)->format($format);
	}

	/**
	 * Get a detailed countdown breakdown from now until a target datetime.
	 *
	 * Returned keys:
	 *   'total_seconds' (int)  – Absolute difference in seconds.
	 *   'days'          (int)  – Complete days.
	 *   'hours'         (int)  – Remaining hours after full days.
	 *   'minutes'       (int)  – Remaining minutes after full hours.
	 *   'seconds'       (int)  – Remaining seconds after full minutes.
	 *   'is_past'       (bool) – True when the target is already in the past.
	 *
	 * @param string $targetDatetime Target datetime string.
	 * @return array{days: int, hours: int, is_past: bool, minutes: int, seconds: int, total_seconds: float|int}
	 */
	public static function getCountdownArray(string $targetDatetime): array
	{
		$now = new DateTime();
		$target = new DateTime($targetDatetime);
		$diff = abs($target->getTimestamp() - $now->getTimestamp());

		return [
			'total_seconds' => $diff,
			'days' => intdiv($diff, 86400),
			'hours' => intdiv($diff % 86400, 3600),
			'minutes' => intdiv($diff % 3600, 60),
			'seconds' => $diff % 60,
			'is_past' => $target < $now,
		];
	}

	/**
	 * Add an ISO 8601 duration to a date or datetime.
	 *
	 * Examples: 'P1Y' = +1 year · 'PT2H30M' = +2 h 30 min · 'P1Y2M3DT4H' = combined.
	 *
	 * @param string $date     Base date or datetime string.
	 * @param string $duration ISO 8601 duration string.
	 * @param string $format   Output format (default 'Y-m-d H:i:s').
	 * @return string Resulting datetime string.
	 */
	public static function addISO8601Duration(string $date, string $duration, string $format = 'Y-m-d H:i:s'): string
	{
		$dt = new DateTime($date);
		$dt->add(new DateInterval($duration));
		return $dt->format($format);
	}

	/**
	 * Subtract an ISO 8601 duration from a date or datetime.
	 *
	 * @param string $date     Base date or datetime string.
	 * @param string $duration ISO 8601 duration string.
	 * @param string $format   Output format (default 'Y-m-d H:i:s').
	 * @return string Resulting datetime string.
	 */
	public static function subtractISO8601Duration(string $date, string $duration, string $format = 'Y-m-d H:i:s'): string
	{
		$dt = new DateTime($date);
		$dt->sub(new DateInterval($duration));
		return $dt->format($format);
	}

	/**
	 * Parse a human-readable duration string into a total number of seconds.
	 *
	 * Recognised unit tokens (case-insensitive):
	 *   d / day / days              → × 86 400
	 *   h / hr / hour / hours       → × 3 600
	 *   m / min / minute / minutes  → × 60
	 *   s / sec / second / seconds  → × 1
	 *
	 * Examples:
	 *   "2h 30m"         → 9 000
	 *   "1 day 2 hours"  → 93 600
	 *   "90 minutes"     → 5 400
	 *
	 * @param string $duration Human-readable duration string.
	 * @return int Total seconds.
	 * @throws InvalidArgumentException If no recognisable tokens are found.
	 */
	public static function parseHumanDuration(string $duration): int
	{
		$pattern = '/(\d+)\s*(d(?:ays?)?|h(?:r|ours?)?|m(?:in(?:utes?)?)?|s(?:ec(?:onds?)?)?)/i';

		if (!preg_match_all($pattern, $duration, $matches, PREG_SET_ORDER)) {
			throw new InvalidArgumentException("Cannot parse duration string: \"{$duration}\"");
		}

		$total = 0;

		foreach ($matches as $match) {
			$value = (int) $match[1];
			$unit = strtolower($match[2]);

			$total += match (true) {
				str_starts_with($unit, 'd') => $value * 86400,
				str_starts_with($unit, 'h') => $value * 3600,
				str_starts_with($unit, 'm') => $value * 60,
				default => $value,
			};
		}

		return $total;
	}

	/**
	 * Format a number of seconds as a compact duration string (e.g. "1h 30m 45s").
	 *
	 * Hours and minutes are always shown when non-zero; seconds are optional.
	 * A negative value is prefixed with '-'.
	 *
	 * @param int  $seconds     Total seconds (may be negative).
	 * @param bool $showSeconds Whether to include seconds in the output (default true).
	 * @return string Compact duration string.
	 */
	public static function formatDurationSeconds(int $seconds, bool $showSeconds = true): string
	{
		$sign = $seconds < 0 ? '-' : '';
		$abs = abs($seconds);
		$hours = intdiv($abs, 3600);
		$minutes = intdiv($abs % 3600, 60);
		$secs = $abs % 60;

		$parts = [];
		if ($hours > 0) {
			$parts[] = "{$hours}h";
		}
		if ($minutes > 0 || $hours > 0) {
			$parts[] = "{$minutes}m";
		}
		if ($showSeconds) {
			$parts[] = "{$secs}s";
		}

		return $sign . (implode(' ', $parts) ?: ($showSeconds ? '0s' : '0m'));
	}

	/**
	 * Generate an array of time slot strings for a calendar day at a fixed interval.
	 *
	 * Example: generateTimeSlots('2026-05-20', 30, 9, 17) → ['09:00', '09:30', …, '16:30']
	 *
	 * @param string $date            Calendar date in any parseable format.
	 * @param int    $intervalMinutes Slot interval in minutes (default 30).
	 * @param int    $startHour       Hour to begin generating (0–23, default 0).
	 * @param int    $endHour         Hour to stop, exclusive (1–24, default 24).
	 * @param string $format          Output format for each slot (default 'H:i').
	 * @return array<int, string> Ordered array of time strings.
	 * @throws InvalidArgumentException If intervalMinutes ≤ 0.
	 */
	public static function generateTimeSlots(string $date, int $intervalMinutes = 30, int $startHour = 0, int $endHour = 24, string $format = 'H:i'): array
	{
		if ($intervalMinutes <= 0) {
			throw new InvalidArgumentException('intervalMinutes must be greater than 0.');
		}

		$slots = [];
		$dateStr = (new DateTime($date))->format('Y-m-d');
		$current = new DateTime(sprintf('%s %02d:00:00', $dateStr, $startHour));
		$stop = new DateTime(sprintf('%s %02d:00:00', $dateStr, $endHour));

		while ($current < $stop) {
			$slots[] = $current->format($format);
			$current->modify("+{$intervalMinutes} minutes");
		}

		return $slots;
	}

	/**
	 * Check whether a given datetime falls within working hours on that day.
	 *
	 * The check is purely time-based; it does NOT verify weekday or holidays.
	 *
	 * @param string $datetime  Input datetime string.
	 * @param int    $startHour Start of the working day, inclusive (0–23, default 9).
	 * @param int    $endHour   End of the working day, exclusive (0–24, default 18).
	 * @return bool True if the time component is in [$startHour, $endHour).
	 */
	public static function isWorkingHour(string $datetime, int $startHour = 9, int $endHour = 18): bool
	{
		$hour = self::getHourFromDate($datetime);
		return $hour >= $startHour && $hour < $endHour;
	}

	/**
	 * Calculate the total working hours between two datetimes, honouring a
	 * daily working-hour window and an optional holiday list.
	 *
	 * Only business days (Mon–Fri, excluding $holidays) count.
	 * The working window is [$workStart, $workEnd) each qualifying day.
	 *
	 * @param string $start     Start datetime.
	 * @param string $end       End datetime.
	 * @param int    $workStart Start hour of the working day (default 9).
	 * @param int    $workEnd   End hour of the working day (default 18).
	 * @param array  $holidays  Holiday dates in 'Y-m-d' format.
	 * @return float Total working hours (rounded to 4 decimal places).
	 */
	public static function getWorkingHoursBetween(string $start, string $end, int $workStart = 9, int $workEnd = 18, array $holidays = []): float
	{
		$startDt = new DateTime($start);
		$endDt = new DateTime($end);

		if ($startDt > $endDt) {
			throw new RuntimeException('End date must be greater than start date');
		}

		$total = 0.0;
		$current = clone $startDt;
		$current->setTime(0, 0, 0);

		while ($current <= $endDt) {
			$date = $current->format('Y-m-d');

			if (self::isBusinessDay($date, $holidays)) {
				$dayStart = (clone $current)->setTime($workStart, 0, 0);
				$dayEnd = (clone $current)->setTime($workEnd, 0, 0);

				$effectiveStart = max($startDt->getTimestamp(), $dayStart->getTimestamp());
				$effectiveEnd = min($endDt->getTimestamp(), $dayEnd->getTimestamp());

				if ($effectiveEnd > $effectiveStart) {
					$total += ($effectiveEnd - $effectiveStart) / 3600;
				}
			}

			$current->modify('+1 day');
		}

		return round($total, 4);
	}

	/**
	 * Get the meteorological season name for a given date.
	 *
	 * Northern hemisphere grouping (calendar months):
	 *   Spring = Mar–May · Summer = Jun–Aug · Autumn = Sep–Nov · Winter = Dec–Feb
	 * Southern hemisphere seasons are the inverse.
	 *
	 * @param string $date       Input date string.
	 * @param string $hemisphere 'north' (default) or 'south'.
	 * @return string One of: 'Spring', 'Summer', 'Autumn', 'Winter'.
	 */
	public static function getSeasonName(string $date, string $hemisphere = 'north'): string
	{
		$month = self::getMonthFromDate($date);
		$north = match (true) {
			$month >= 3 && $month <= 5 => 'Spring',
			$month >= 6 && $month <= 8 => 'Summer',
			$month >= 9 && $month <= 11 => 'Autumn',
			default => 'Winter',
		};

		if (strtolower($hemisphere) === 'south') {
			return ['Spring' => 'Autumn', 'Summer' => 'Winter', 'Autumn' => 'Spring', 'Winter' => 'Summer'][$north];
		}

		return $north;
	}

	/**
	 * @param string $date The date string to check.
	 * @return bool True if the given date falls on Monday.
	 */
	public static function isMonday(string $date): bool
	{
		return (int) (new DateTime($date))->format('N') === 1;
	}

	/**
	 * @param string $date The date string to check.
	 * @return bool True if the given date falls on Tuesday.
	 */
	public static function isTuesday(string $date): bool
	{
		return (int) (new DateTime($date))->format('N') === 2;
	}

	/**
	 * @param string $date The date string to check.
	 * @return bool True if the given date falls on Wednesday.
	 */
	public static function isWednesday(string $date): bool
	{
		return (int) (new DateTime($date))->format('N') === 3;
	}

	/**
	 * @param string $date The date string to check.
	 * @return bool True if the given date falls on Thursday.
	 */
	public static function isThursday(string $date): bool
	{
		return (int) (new DateTime($date))->format('N') === 4;
	}

	/**
	 * @param string $date The date string to check.
	 * @return bool True if the given date falls on Friday.
	 */
	public static function isFriday(string $date): bool
	{
		return (int) (new DateTime($date))->format('N') === 5;
	}

	/**
	 * @param string $date The date string to check.
	 * @return bool True if the given date falls on Saturday.
	 */
	public static function isSaturday(string $date): bool
	{
		return (int) (new DateTime($date))->format('N') === 6;
	}

	/**
	 * @param string $date The date string to check.
	 * @return bool True if the given date falls on Sunday.
	 */
	public static function isSunday(string $date): bool
	{
		return (int) (new DateTime($date))->format('N') === 7;
	}

	/**
	 * @param string $date The date string to check.
	 * @return bool True if the date is in January.
	 */
	public static function isJanuary(string $date): bool
	{
		return self::getMonthFromDate($date) === 1;
	}

	/**
	 * @param string $date The date string to check.
	 * @return bool True if the date is in February.
	 */
	public static function isFebruary(string $date): bool
	{
		return self::getMonthFromDate($date) === 2;
	}

	/**
	 * @param string $date The date string to check.
	 * @return bool True if the date is in March.
	 */
	public static function isMarch(string $date): bool
	{
		return self::getMonthFromDate($date) === 3;
	}

	/**
	 * @param string $date The date string to check.
	 * @return bool True if the date is in April.
	 */
	public static function isApril(string $date): bool
	{
		return self::getMonthFromDate($date) === 4;
	}

	/**
	 * @param string $date The date string to check.
	 * @return bool True if the date is in May.
	 */
	public static function isMay(string $date): bool
	{
		return self::getMonthFromDate($date) === 5;
	}

	/**
	 * @param string $date The date string to check.
	 * @return bool True if the date is in June.
	 */
	public static function isJune(string $date): bool
	{
		return self::getMonthFromDate($date) === 6;
	}

	/**
	 * @param string $date The date string to check.
	 * @return bool True if the date is in July.
	 */
	public static function isJuly(string $date): bool
	{
		return self::getMonthFromDate($date) === 7;
	}

	/**
	 * @param string $date The date string to check.
	 * @return bool True if the date is in August.
	 */
	public static function isAugust(string $date): bool
	{
		return self::getMonthFromDate($date) === 8;
	}

	/**
	 * @param string $date The date string to check.
	 * @return bool True if the date is in September.
	 */
	public static function isSeptember(string $date): bool
	{
		return self::getMonthFromDate($date) === 9;
	}

	/**
	 * @param string $date The date string to check.
	 * @return bool True if the date is in October.
	 */
	public static function isOctober(string $date): bool
	{
		return self::getMonthFromDate($date) === 10;
	}

	/**
	 * @param string $date The date string to check.
	 * @return bool True if the date is in November.
	 */
	public static function isNovember(string $date): bool
	{
		return self::getMonthFromDate($date) === 11;
	}

	/**
	 * @param string $date The date string to check.
	 * @return bool True if the date is in December.
	 */
	public static function isDecember(string $date): bool
	{
		return self::getMonthFromDate($date) === 12;
	}

	/**
	 * Check whether a date falls within the current calendar year.
	 *
	 * @param string $date The date string.
	 * @return bool
	 */
	public static function isCurrentYear(string $date): bool
	{
		return self::getYearFromDate($date) === (int) date('Y');
	}

	/**
	 * Check whether a date falls within the current calendar month.
	 *
	 * @param string $date The date string.
	 * @return bool
	 */
	public static function isCurrentMonth(string $date): bool
	{
		$dt = new DateTime($date);
		return $dt->format('Y-m') === date('Y-m');
	}

	/**
	 * Check whether a date falls within the current ISO week.
	 *
	 * @param string $date The date string.
	 * @return bool
	 */
	public static function isCurrentWeek(string $date): bool
	{
		$dt = new DateTime($date);
		$now = new DateTime();
		return $dt->format('o-W') === $now->format('o-W');
	}

	/**
	 * Check whether a date falls within the current quarter.
	 *
	 * @param string $date The date string.
	 * @return bool
	 */
	public static function isCurrentQuarter(string $date): bool
	{
		return self::isSameQuarter($date, date('Y-m-d'));
	}

	/**
	 * Check whether a date is today.
	 *
	 * @param string $date The date string.
	 * @return bool
	 */
	public static function isCurrentDay(string $date): bool
	{
		return (new DateTime($date))->format('Y-m-d') === date('Y-m-d');
	}

	/**
	 * Check whether a datetime falls within the current clock hour.
	 *
	 * @param string $datetime The datetime string.
	 * @return bool
	 */
	public static function isCurrentHour(string $datetime): bool
	{
		$dt = new DateTime($datetime);
		return $dt->format('Y-m-d H') === date('Y-m-d H');
	}

	/**
	 * Check whether a datetime falls within the current clock minute.
	 *
	 * @param string $datetime The datetime string.
	 * @return bool
	 */
	public static function isCurrentMinute(string $datetime): bool
	{
		$dt = new DateTime($datetime);
		return $dt->format('Y-m-d H:i') === date('Y-m-d H:i');
	}

	/**
	 * Get the Western (tropical) zodiac sign for a given date.
	 *
	 * @param string $date The date string.
	 * @return string The zodiac sign name (e.g. 'Aries', 'Taurus').
	 */
	public static function getWesternZodiacSign(string $date): string
	{
		$month = self::getMonthFromDate($date);
		$day = self::getDayFromDate($date);

		$signs = [
			[1, 20, 'Capricorn'],
			[2, 19, 'Aquarius'],
			[3, 20, 'Pisces'],
			[4, 20, 'Aries'],
			[5, 21, 'Taurus'],
			[6, 21, 'Gemini'],
			[7, 22, 'Cancer'],
			[8, 23, 'Leo'],
			[9, 23, 'Virgo'],
			[10, 23, 'Libra'],
			[11, 22, 'Scorpio'],
			[12, 22, 'Sagittarius'],
		];

		foreach ($signs as [$endMonth, $endDay, $sign]) {
			if ($month === $endMonth && $day <= $endDay) {
				return $sign;
			}
		}

		return match ($month) {
			1 => 'Capricorn', 2 => 'Aquarius', 3 => 'Pisces',
			4 => 'Aries', 5 => 'Taurus', 6 => 'Gemini',
			7 => 'Cancer', 8 => 'Leo', 9 => 'Virgo',
			10 => 'Libra', 11 => 'Scorpio', default => 'Sagittarius',
		};
	}

	/**
	 * Get the Japanese era name (元号) for a given Gregorian date.
	 *
	 * Covers: Meiji (1868), Taisho (1912), Showa (1926), Heisei (1989), Reiwa (2019).
	 *
	 * @param string $date The Gregorian date string.
	 * @return string Japanese era name or 'Unknown' for dates before 1868-01-25.
	 */
	public static function getJapaneseEra(string $date): string
	{
		$dt = new DateTime($date);
		$ymd = $dt->format('Ymd');

		return match (true) {
			$ymd >= '20190501' => 'Reiwa',
			$ymd >= '19890108' => 'Heisei',
			$ymd >= '19261225' => 'Showa',
			$ymd >= '19120730' => 'Taisho',
			$ymd >= '18680125' => 'Meiji',
			default => 'Unknown',
		};
	}

	/**
	 * Get the traditional birthstone for a given month number.
	 *
	 * @param int $month Month number (1–12).
	 * @return string Birthstone name.
	 * @throws InvalidArgumentException If month is out of range.
	 */
	public static function getBirthstone(int $month): string
	{
		return match ($month) {
			1 => 'Garnet', 2 => 'Amethyst', 3 => 'Aquamarine',
			4 => 'Diamond', 5 => 'Emerald', 6 => 'Pearl',
			7 => 'Ruby', 8 => 'Peridot', 9 => 'Sapphire',
			10 => 'Opal', 11 => 'Topaz', 12 => 'Tanzanite',
			default => throw new InvalidArgumentException("Month must be 1–12, got {$month}."),
		};
	}

	/**
	 * Format a date as ISO 8601 week date (e.g. "2026-W22-2").
	 *
	 * @param string $date The date string.
	 * @return string ISO week date string.
	 */
	public static function toISOWeekDate(string $date): string
	{
		$dt = new DateTime($date);
		return $dt->format('o-\\WW-N');
	}

	/**
	 * Format a date as a compact 8-digit string (YYYYMMDD).
	 *
	 * @param string $date The date string.
	 * @return string Compact date (e.g. "20260602").
	 */
	public static function toCompactDate(string $date): string
	{
		return (new DateTime($date))->format('Ymd');
	}

	/**
	 * Parse a compact 8-digit date string (YYYYMMDD) into the given format.
	 *
	 * @param string $compact 8-digit date string.
	 * @param string $format  Output format (default 'Y-m-d').
	 * @return string Formatted date.
	 */
	public static function fromCompactDate(string $compact, string $format = 'Y-m-d'): string
	{
		$dt = DateTime::createFromFormat('Ymd', $compact);
		if ($dt === false) {
			throw new InvalidArgumentException("Cannot parse compact date: {$compact}");
		}
		return $dt->format($format);
	}

	/**
	 * Get the Swatch Internet Time (.beats) for a given datetime.
	 *
	 * Swatch time divides the day into 1000 .beats (each = 86.4 seconds),
	 * measured from BMT (Biel Mean Time = UTC+1).
	 *
	 * @param string      $datetime The datetime string.
	 * @param string|null $timezone The source timezone (default system timezone).
	 * @return string Swatch beat string (e.g. "@456.78").
	 */
	public static function toSwatchInternetTime(string $datetime, ?string $timezone = null): string
	{
		$dt = new DateTime($datetime, $timezone ? new DateTimeZone($timezone) : null);
		$dt->setTimezone(new DateTimeZone('Europe/Zurich'));
		$beats = ((int) $dt->format('G') * 3600 + (int) $dt->format('i') * 60 + (int) $dt->format('s')) / 86.4;
		return '@' . number_format(fmod($beats + 1000, 1000), 2, '.', '');
	}

	/**
	 * Format a datetime as military/24-hour time (e.g. "0930", "1745").
	 *
	 * @param string $datetime The datetime string.
	 * @return string 4-digit military time string.
	 */
	public static function toMilitaryTime(string $datetime): string
	{
		return (new DateTime($datetime))->format('Hi');
	}

	/**
	 * Encode a Unix timestamp as a base-36 string.
	 *
	 * @param string $date The date string.
	 * @return string Base-36 encoded timestamp.
	 */
	public static function toBase36Timestamp(string $date): string
	{
		return base_convert((string) self::toUnixTimestamp($date), 10, 36);
	}

	/**
	 * Decode a base-36 encoded timestamp back to a formatted date string.
	 *
	 * @param string $base36 Base-36 encoded timestamp.
	 * @param string $format Output format (default 'Y-m-d H:i:s').
	 * @return string Formatted date string.
	 */
	public static function fromBase36Timestamp(string $base36, string $format = 'Y-m-d H:i:s'): string
	{
		$timestamp = (int) base_convert($base36, 36, 10);
		return self::fromTimestamp($timestamp, $format);
	}

	/**
	 * Get the Unix timestamp in microseconds for a given date.
	 *
	 * @param string $date The date string.
	 * @return int Microseconds since epoch.
	 */
	public static function toUnixMicroseconds(string $date): int
	{
		$dt = new DateTime($date);
		return (int) ($dt->format('U') * 1000000 + (int) $dt->format('u'));
	}

	/**
	 * Create a date string from a Unix microsecond timestamp.
	 *
	 * @param int    $microseconds Microseconds since epoch.
	 * @param string $format       Output format (default 'Y-m-d H:i:s.u').
	 * @return string Formatted date string.
	 */
	public static function fromUnixMicroseconds(int $microseconds, string $format = 'Y-m-d H:i:s.u'): string
	{
		$seconds = intdiv($microseconds, 1000000);
		$micro = $microseconds % 1000000;
		$dt = DateTime::createFromFormat('U u', "{$seconds} {$micro}");
		return $dt->format($format);
	}

	/**
	 * Get the start of the week using Sunday as the first day.
	 *
	 * @param string $date The date string.
	 * @return string The Sunday that begins the week, in 'Y-m-d' format.
	 */
	public static function getStartOfWeekSunday(string $date): string
	{
		$dt = new DateTime($date);
		$dow = (int) $dt->format('w');
		if ($dow !== 0) {
			$dt->modify("-{$dow} days");
		}
		return $dt->format('Y-m-d');
	}

	/**
	 * Get the end of the week using Saturday as the last day.
	 *
	 * @param string $date The date string.
	 * @return string The Saturday that ends the week, in 'Y-m-d' format.
	 */
	public static function getEndOfWeekSaturday(string $date): string
	{
		$dt = new DateTime($date);
		$dow = (int) $dt->format('w');
		$daysToSat = 6 - $dow;
		if ($daysToSat > 0) {
			$dt->modify("+{$daysToSat} days");
		}
		return $dt->format('Y-m-d');
	}

	/**
	 * Get "AM" or "PM" for a given datetime.
	 *
	 * @param string $datetime The datetime string.
	 * @return string "AM" or "PM".
	 */
	public static function getAmPm(string $datetime): string
	{
		return (new DateTime($datetime))->format('A');
	}

	/**
	 * Check whether a datetime is in the morning (00:00–11:59).
	 *
	 * @param string $datetime The datetime string.
	 * @return bool
	 */
	public static function isMorning(string $datetime): bool
	{
		return self::getHourFromDate($datetime) < 12;
	}

	/**
	 * Check whether a datetime is in the afternoon (12:00–17:59).
	 *
	 * @param string $datetime The datetime string.
	 * @return bool
	 */
	public static function isAfternoon(string $datetime): bool
	{
		$hour = self::getHourFromDate($datetime);
		return $hour >= 12 && $hour < 18;
	}

	/**
	 * Check whether a datetime is in the evening (18:00–23:59).
	 *
	 * @param string $datetime The datetime string.
	 * @return bool
	 */
	public static function isEvening(string $datetime): bool
	{
		return self::getHourFromDate($datetime) >= 18;
	}

	/**
	 * Get the minute of the day (0–1439) for a given datetime.
	 *
	 * @param string $datetime The datetime string.
	 * @return int Minute offset from midnight.
	 */
	public static function getMinuteOfDay(string $datetime): int
	{
		return self::getHourFromDate($datetime) * 60 + self::getMinuteFromDate($datetime);
	}

	/**
	 * Get the second of the day (0–86399) for a given datetime.
	 *
	 * @param string $datetime The datetime string.
	 * @return int Second offset from midnight.
	 */
	public static function getSecondOfDay(string $datetime): int
	{
		return self::getHourFromDate($datetime) * 3600
			+ self::getMinuteFromDate($datetime) * 60
			+ self::getSecondFromDate($datetime);
	}

	/**
	 * Convert a date to a decimal year representation.
	 *
	 * E.g. 2026-07-02 ≈ 2026.5 (halfway through the year).
	 *
	 * @param string $date The date string.
	 * @return float Decimal year value.
	 */
	public static function toDecimalYear(string $date): float
	{
		$dt = new DateTime($date);
		$year = (int) $dt->format('Y');
		$dayOfYear = (int) $dt->format('z');
		$daysInYear = self::getDaysInYear($year);
		return $year + ($dayOfYear / $daysInYear);
	}

	/**
	 * Get the ISO 8601 numeric day of the week (1 = Monday, 7 = Sunday).
	 *
	 * @param string $date The date string.
	 * @return int ISO day of week (1–7).
	 */
	public static function getISODayOfWeek(string $date): int
	{
		return (int) (new DateTime($date))->format('N');
	}

	/**
	 * Get the quarter label for a date (e.g. "Q1", "Q2", "Q3", "Q4").
	 *
	 * @param string $date The date string.
	 * @return string Quarter label.
	 */
	public static function getQuarterName(string $date): string
	{
		return 'Q' . self::getQuarterOfDate($date);
	}

	/**
	 * Get which half of the year a date falls in (1 = Jan–Jun, 2 = Jul–Dec).
	 *
	 * @param string $date The date string.
	 * @return int 1 or 2.
	 */
	public static function getHalfYear(string $date): int
	{
		return self::getMonthFromDate($date) <= 6 ? 1 : 2;
	}

	/**
	 * Check whether a date is the first day of a calendar quarter.
	 *
	 * @param string $date The date string.
	 * @return bool
	 */
	public static function isStartOfQuarter(string $date): bool
	{
		$dt = new DateTime($date);
		$month = (int) $dt->format('n');
		$day = (int) $dt->format('j');
		return in_array($month, [1, 4, 7, 10], true) && $day === 1;
	}

	/**
	 * Check whether a date is the last day of a calendar quarter.
	 *
	 * @param string $date The date string.
	 * @return bool
	 */
	public static function isEndOfQuarter(string $date): bool
	{
		$dt = new DateTime($date);
		return $dt->format('Y-m-d') === self::getQuarterEnd($date);
	}

	/**
	 * Check whether a date reads the same forward and backward.
	 *
	 * By default uses 'mdY' format (e.g. 02-02-2020 → "02022020").
	 * Pass 'Ymd' or another format to change the representation.
	 *
	 * @param string $date   The date string.
	 * @param string $format Date format to test palindrome against (default 'mdY').
	 * @return bool
	 */
	public static function isPalindromicDate(string $date, string $format = 'mdY'): bool
	{
		$formatted = (new DateTime($date))->format($format);
		return $formatted === strrev($formatted);
	}

	/**
	 * Check whether a datetime falls within rush-hour / peak-hour windows.
	 *
	 * Default windows: 07:00–09:00 (morning) and 17:00–19:00 (evening).
	 *
	 * @param string $datetime     The datetime string.
	 * @param int    $morningStart Morning peak start hour (default 7).
	 * @param int    $morningEnd   Morning peak end hour, exclusive (default 9).
	 * @param int    $eveningStart Evening peak start hour (default 17).
	 * @param int    $eveningEnd   Evening peak end hour, exclusive (default 19).
	 * @return bool
	 */
	public static function isPeakHour(string $datetime, int $morningStart = 7, int $morningEnd = 9, int $eveningStart = 17, int $eveningEnd = 19): bool
	{
		$hour = self::getHourFromDate($datetime);
		return ($hour >= $morningStart && $hour < $morningEnd)
			|| ($hour >= $eveningStart && $hour < $eveningEnd);
	}

	/**
	 * Check whether a datetime falls within the lunch-hour window.
	 *
	 * @param string $datetime  The datetime string.
	 * @param int    $startHour Lunch start hour (default 12).
	 * @param int    $endHour   Lunch end hour, exclusive (default 13).
	 * @return bool
	 */
	public static function isLunchHour(string $datetime, int $startHour = 12, int $endHour = 13): bool
	{
		$hour = self::getHourFromDate($datetime);
		return $hour >= $startHour && $hour < $endHour;
	}

	/**
	 * Get all IANA timezone identifiers for a given ISO 3166-1 alpha-2 country code.
	 *
	 * @param string $countryCode Two-letter country code (e.g. "US", "KR", "JP").
	 * @return array Array of timezone identifiers.
	 */
	public static function getTimezonesByCountry(string $countryCode): array
	{
		return DateTimeZone::listIdentifiers(DateTimeZone::PER_COUNTRY, strtoupper($countryCode));
	}

	/**
	 * Get all IANA timezone identifiers for a given continent.
	 *
	 * @param string $continent Continent name: 'Africa','America','Antarctica','Arctic',
	 *                          'Asia','Atlantic','Australia','Europe','Indian','Pacific'.
	 * @return array Array of timezone identifiers.
	 */
	public static function getTimezonesByContinent(string $continent): array
	{
		$map = [
			'Africa' => DateTimeZone::AFRICA,
			'America' => DateTimeZone::AMERICA,
			'Antarctica' => DateTimeZone::ANTARCTICA,
			'Arctic' => DateTimeZone::ARCTIC,
			'Asia' => DateTimeZone::ASIA,
			'Atlantic' => DateTimeZone::ATLANTIC,
			'Australia' => DateTimeZone::AUSTRALIA,
			'Europe' => DateTimeZone::EUROPE,
			'Indian' => DateTimeZone::INDIAN,
			'Pacific' => DateTimeZone::PACIFIC,
		];

		$key = ucfirst(strtolower($continent));
		if (!isset($map[$key])) {
			throw new InvalidArgumentException("Unknown continent: {$continent}");
		}

		return DateTimeZone::listIdentifiers($map[$key]);
	}

	/**
	 * Check whether a date is the Nth business day of its month.
	 *
	 * @param string $date     The date string.
	 * @param int    $nth      The ordinal business day number (1-based).
	 * @param array  $holidays Holiday dates in 'Y-m-d' format.
	 * @return bool
	 */
	public static function isNthBusinessDayOfMonth(string $date, int $nth, array $holidays = []): bool
	{
		$dt = new DateTime($date);
		$year = (int) $dt->format('Y');
		$month = (int) $dt->format('n');
		$target = $dt->format('Y-m-d');

		$current = new DateTime("{$year}-{$month}-01");
		$count = 0;

		while ((int) $current->format('n') === $month) {
			if (self::isBusinessDay($current->format('Y-m-d'), $holidays)) {
				$count++;
				if ($count === $nth) {
					return $current->format('Y-m-d') === $target;
				}
			}
			$current->modify('+1 day');
		}

		return false;
	}

	/**
	 * Find the nearest holiday to a given date from a list.
	 *
	 * @param string $date     The reference date.
	 * @param array  $holidays Array of holiday date strings ('Y-m-d').
	 * @return string|null The nearest holiday date, or null if the list is empty.
	 */
	public static function getNearestHoliday(string $date, array $holidays): ?string
	{
		if ($holidays === []) {
			return null;
		}

		$ref = new DateTime($date);
		$nearest = null;
		$minDiff = PHP_INT_MAX;

		foreach ($holidays as $holiday) {
			$diff = abs($ref->diff(new DateTime($holiday))->days);
			if ($diff < $minDiff) {
				$minDiff = $diff;
				$nearest = $holiday;
			}
		}

		return $nearest;
	}

	/**
	 * Check whether a date is within N days of any holiday in a list.
	 *
	 * @param string $date       The reference date.
	 * @param array  $holidays   Array of holiday date strings ('Y-m-d').
	 * @param int    $withinDays Maximum distance in days (default 3).
	 * @return bool
	 */
	public static function isNearHoliday(string $date, array $holidays, int $withinDays = 3): bool
	{
		$nearest = self::getNearestHoliday($date, $holidays);
		if ($nearest === null) {
			return false;
		}
		return abs((new DateTime($date))->diff(new DateTime($nearest))->days) <= $withinDays;
	}

	/**
	 * Get the academic year for a given date.
	 *
	 * The academic year starts in $startMonth (default September). Dates before
	 * that month belong to the previous academic year.
	 *
	 * @param string $date       The date string.
	 * @param int    $startMonth The month the academic year begins (default 9).
	 * @return int The starting calendar year of the academic year.
	 */
	public static function getAcademicYear(string $date, int $startMonth = 9): int
	{
		$dt = new DateTime($date);
		$year = (int) $dt->format('Y');
		$month = (int) $dt->format('n');
		return $month >= $startMonth ? $year : $year - 1;
	}

	/**
	 * Round a datetime to the nearest calendar day (noon threshold).
	 *
	 * Before noon → same day 00:00:00; noon or after → next day 00:00:00.
	 *
	 * @param string $datetime The datetime string.
	 * @return string Rounded date in 'Y-m-d' format.
	 */
	public static function roundToNearestDay(string $datetime): string
	{
		$dt = new DateTime($datetime);
		if ((int) $dt->format('G') >= 12) {
			$dt->modify('+1 day');
		}
		return $dt->format('Y-m-d');
	}

	/**
	 * Round a datetime to the nearest full hour (30-minute threshold).
	 *
	 * @param string $datetime The datetime string.
	 * @return string Rounded datetime in 'Y-m-d H:00:00' format.
	 */
	public static function roundToNearestHour(string $datetime): string
	{
		$dt = new DateTime($datetime);
		if ((int) $dt->format('i') >= 30) {
			$dt->modify('+1 hour');
		}
		$dt->setTime((int) $dt->format('G'), 0, 0);
		return $dt->format('Y-m-d H:i:s');
	}

	/**
	 * Format a date for MySQL DATETIME column (Y-m-d H:i:s).
	 *
	 * @param string $date The date string.
	 * @return string MySQL-compatible datetime string.
	 */
	public static function toMySQLDatetime(string $date): string
	{
		return (new DateTime($date))->format('Y-m-d H:i:s');
	}

	/**
	 * Format a date for PostgreSQL TIMESTAMP WITH TIME ZONE.
	 *
	 * @param string $date The date string.
	 * @return string PostgreSQL-compatible timestamp string.
	 */
	public static function toPostgresTimestamp(string $date): string
	{
		return (new DateTime($date))->format('Y-m-d H:i:s.uP');
	}

	/**
	 * Format a date for SQL DATE column (Y-m-d).
	 *
	 * @param string $date The date string.
	 * @return string SQL DATE string.
	 */
	public static function toSQLDate(string $date): string
	{
		return (new DateTime($date))->format('Y-m-d');
	}

	/**
	 * Format a date for SQL TIMESTAMP column (Y-m-d H:i:s).
	 *
	 * @param string $date The date string.
	 * @return string SQL TIMESTAMP string.
	 */
	public static function toSQLTimestamp(string $date): string
	{
		return (new DateTime($date))->format('Y-m-d H:i:s');
	}

	/**
	 * Count the number of each weekday in a given month.
	 *
	 * @param int $year  The Gregorian year.
	 * @param int $month The month (1–12).
	 * @return array<string, int> Associative array [Monday => 5, Tuesday => 4, …].
	 */
	public static function getWeekdayCountInMonth(int $year, int $month): array
	{
		$counts = ['Monday' => 0, 'Tuesday' => 0, 'Wednesday' => 0, 'Thursday' => 0, 'Friday' => 0, 'Saturday' => 0, 'Sunday' => 0];
		$dt = new DateTime("{$year}-{$month}-01");
		$daysInMonth = (int) $dt->format('t');

		for ($d = 1; $d <= $daysInMonth; $d++) {
			$dt->setDate($year, $month, $d);
			$counts[$dt->format('l')]++;
		}

		return $counts;
	}

	/**
	 * Get how many days have passed since the start of the ISO week (Monday = 0).
	 *
	 * @param string $date The date string.
	 * @return int Days since Monday (0–6).
	 */
	public static function getDaysSinceStartOfWeek(string $date): int
	{
		return self::getISODayOfWeek($date) - 1;
	}

	/**
	 * Get how many days remain until the end of the ISO week (Sunday).
	 *
	 * @param string $date The date string.
	 * @return int Days until Sunday (0–6).
	 */
	public static function getDaysUntilEndOfWeek(string $date): int
	{
		return 7 - self::getISODayOfWeek($date);
	}

	/**
	 * Get the date of the last occurrence of a given weekday in a month.
	 *
	 * @param int $year    The Gregorian year.
	 * @param int $month   The month (1–12).
	 * @param int $weekday ISO weekday number (1 = Monday, 7 = Sunday).
	 * @return string Date in 'Y-m-d' format.
	 */
	public static function getLastWeekdayOfMonth(int $year, int $month, int $weekday): string
	{
		$dt = new DateTime("{$year}-{$month}-" . (new DateTime("{$year}-{$month}-01"))->format('t'));
		while ((int) $dt->format('N') !== $weekday) {
			$dt->modify('-1 day');
		}
		return $dt->format('Y-m-d');
	}

	/**
	 * Get US Thanksgiving Day (4th Thursday of November) for a given year.
	 *
	 * @param int $year The Gregorian year.
	 * @return string Date in 'Y-m-d' format.
	 */
	public static function getThanksgiving(int $year): string
	{
		return self::getNthWeekdayOfMonth($year, 11, 4, 4);
	}

	/**
	 * Get US Labor Day (1st Monday of September) for a given year.
	 *
	 * @param int $year The Gregorian year.
	 * @return string Date in 'Y-m-d' format.
	 */
	public static function getLaborDay(int $year): string
	{
		return self::getNthWeekdayOfMonth($year, 9, 1, 1);
	}

	/**
	 * Get US Memorial Day (last Monday of May) for a given year.
	 *
	 * @param int $year The Gregorian year.
	 * @return string Date in 'Y-m-d' format.
	 */
	public static function getMemorialDay(int $year): string
	{
		return self::getLastWeekdayOfMonth($year, 5, 1);
	}

	/**
	 * Get Martin Luther King Jr. Day (3rd Monday of January) for a given year.
	 *
	 * @param int $year The Gregorian year.
	 * @return string Date in 'Y-m-d' format.
	 */
	public static function getMLKDay(int $year): string
	{
		return self::getNthWeekdayOfMonth($year, 1, 3, 1);
	}

	/**
	 * Get US Presidents' Day (3rd Monday of February) for a given year.
	 *
	 * @param int $year The Gregorian year.
	 * @return string Date in 'Y-m-d' format.
	 */
	public static function getPresidentsDay(int $year): string
	{
		return self::getNthWeekdayOfMonth($year, 2, 3, 1);
	}

	/**
	 * Check whether today (or a given reference date) is someone's birthday.
	 *
	 * Compares month and day only.
	 *
	 * @param string      $birthdate     The birthdate string.
	 * @param string|null $referenceDate The reference date (default 'today').
	 * @return bool
	 */
	public static function isBirthday(string $birthdate, ?string $referenceDate = null): bool
	{
		$birth = new DateTime($birthdate);
		$ref = new DateTime($referenceDate ?? 'today');
		return $birth->format('m-d') === $ref->format('m-d');
	}

	/**
	 * Check whether today (or a given reference date) is the anniversary of a past date.
	 *
	 * Compares month and day only.
	 *
	 * @param string      $originalDate  The original event date.
	 * @param string|null $referenceDate The reference date (default 'today').
	 * @return bool
	 */
	public static function isAnniversary(string $originalDate, ?string $referenceDate = null): bool
	{
		return self::isBirthday($originalDate, $referenceDate);
	}

	/**
	 * Extract only the date portion as 'Y-m-d'.
	 *
	 * @param string $datetime The datetime string.
	 * @return string Date part.
	 */
	public static function toDateString(string $datetime): string
	{
		return (new DateTime($datetime))->format('Y-m-d');
	}

	/**
	 * Extract only the time portion as 'H:i:s'.
	 *
	 * @param string $datetime The datetime string.
	 * @return string Time part.
	 */
	public static function toTimeString(string $datetime): string
	{
		return (new DateTime($datetime))->format('H:i:s');
	}

	/**
	 * Format a datetime as "Mon, Jun 2, 2026 3:45 PM".
	 *
	 * @param string $datetime The datetime string.
	 * @return string Readable day-date-time string.
	 */
	public static function toDayDateTimeString(string $datetime): string
	{
		return (new DateTime($datetime))->format('D, M j, Y g:i A');
	}

	/**
	 * Format a date as "Jun 2, 2026".
	 *
	 * @param string $date The date string.
	 * @return string
	 */
	public static function toFormattedDateString(string $date): string
	{
		return (new DateTime($date))->format('M j, Y');
	}

	/**
	 * Format a date as "June 2nd, 2026".
	 *
	 * @param string $date The date string.
	 * @return string
	 */
	public static function toLongDateString(string $date): string
	{
		return (new DateTime($date))->format('F jS, Y');
	}

	/**
	 * Format a date as "6/2/26".
	 *
	 * @param string $date The date string.
	 * @return string
	 */
	public static function toShortDateString(string $date): string
	{
		return (new DateTime($date))->format('n/j/y');
	}

	/**
	 * Format a time as "3:45 PM".
	 *
	 * @param string $datetime The datetime string.
	 * @return string
	 */
	public static function toShortTimeString(string $datetime): string
	{
		return (new DateTime($datetime))->format('g:i A');
	}

	/**
	 * Format a date as a JSON-compatible ISO 8601 string with timezone designator.
	 *
	 * @param string $date The date string.
	 * @return string ISO 8601 string (e.g. "2026-06-02T15:30:00+00:00").
	 */
	public static function toJSON(string $date): string
	{
		return (new DateTime($date))->format(DateTimeInterface::ATOM);
	}

	/**
	 * Get the first day of the decade for a given year.
	 *
	 * @param int $year The Gregorian year.
	 * @return string Date in 'Y-m-d' format (e.g. "2020-01-01" for year 2026).
	 */
	public static function getStartOfDecade(int $year): string
	{
		return self::getDecade($year) . '-01-01';
	}

	/**
	 * Get the last day of the decade for a given year.
	 *
	 * @param int $year The Gregorian year.
	 * @return string Date in 'Y-m-d' format (e.g. "2029-12-31" for year 2026).
	 */
	public static function getEndOfDecade(int $year): string
	{
		return (self::getDecade($year) + 9) . '-12-31';
	}

	/**
	 * Get the first day of the century for a given year.
	 *
	 * @param int $year The Gregorian year.
	 * @return string Date in 'Y-m-d' format (e.g. "2001-01-01" for year 2026).
	 */
	public static function getStartOfCentury(int $year): string
	{
		$centuryStart = ((self::getCentury($year) - 1) * 100) + 1;
		return $centuryStart . '-01-01';
	}

	/**
	 * Get the last day of the century for a given year.
	 *
	 * @param int $year The Gregorian year.
	 * @return string Date in 'Y-m-d' format (e.g. "2100-12-31" for year 2026).
	 */
	public static function getEndOfCentury(int $year): string
	{
		$centuryEnd = self::getCentury($year) * 100;
		return $centuryEnd . '-12-31';
	}

	/**
	 * Get what percentage of the day has elapsed at the given datetime.
	 *
	 * @param string $datetime The datetime string.
	 * @return float Percentage (0.0–100.0).
	 */
	public static function getDayPercentage(string $datetime): float
	{
		return round(self::getSecondOfDay($datetime) / 864, 4);
	}

	/**
	 * Get the difference between two dates in fractional days.
	 *
	 * @param string $date1 First date.
	 * @param string $date2 Second date.
	 * @return float Absolute difference in days with decimal.
	 */
	public static function floatDiffInDays(string $date1, string $date2): float
	{
		return abs((new DateTime($date1))->getTimestamp() - (new DateTime($date2))->getTimestamp()) / 86400;
	}

	/**
	 * Get the difference between two dates in fractional weeks.
	 *
	 * @param string $date1 First date.
	 * @param string $date2 Second date.
	 * @return float Absolute difference in weeks with decimal.
	 */
	public static function floatDiffInWeeks(string $date1, string $date2): float
	{
		return self::floatDiffInDays($date1, $date2) / 7;
	}

	/**
	 * Get the difference between two dates in approximate fractional months (30.4375 days).
	 *
	 * @param string $date1 First date.
	 * @param string $date2 Second date.
	 * @return float Approximate difference in months.
	 */
	public static function floatDiffInMonths(string $date1, string $date2): float
	{
		return self::floatDiffInDays($date1, $date2) / 30.4375;
	}

	/**
	 * Decompose a datetime string into all its individual components.
	 *
	 * @param string $datetime The datetime string.
	 * @return array{year: int, month: int, day: int, hour: int, minute: int, second: int,
	 *               dayOfWeek: int, dayOfYear: int, weekNumber: int, daysInMonth: int,
	 *               timestamp: int, quarter: int, isLeapYear: bool}
	 */
	public static function getDateComponents(string $datetime): array
	{
		$dt = new DateTime($datetime);
		return [
			'year' => (int) $dt->format('Y'),
			'month' => (int) $dt->format('n'),
			'day' => (int) $dt->format('j'),
			'hour' => (int) $dt->format('G'),
			'minute' => (int) $dt->format('i'),
			'second' => (int) $dt->format('s'),
			'dayOfWeek' => (int) $dt->format('N'),
			'dayOfYear' => (int) $dt->format('z') + 1,
			'weekNumber' => (int) $dt->format('W'),
			'daysInMonth' => (int) $dt->format('t'),
			'timestamp' => $dt->getTimestamp(),
			'quarter' => (int) ceil((int) $dt->format('n') / 3),
			'isLeapYear' => (bool) $dt->format('L'),
		];
	}

	/**
	 * Attempt to detect the date format of a given string.
	 *
	 * Checks common patterns: 'Y-m-d H:i:s', 'Y-m-d', 'm/d/Y', 'd/m/Y',
	 * 'd.m.Y', 'Y/m/d', 'M j, Y', 'F j, Y', 'Ymd\THis', etc.
	 *
	 * @param string $date The date string to inspect.
	 * @return string|null The detected format string, or null if no match.
	 */
	public static function detectDateFormat(string $date): ?string
	{
		$patterns = [
			'/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/' => 'Y-m-d H:i:s',
			'/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/' => DateTimeInterface::ATOM,
			'/^\d{4}-\d{2}-\d{2}$/' => 'Y-m-d',
			'/^\d{2}\/\d{2}\/\d{4}$/' => 'm/d/Y',
			'/^\d{2}\.\d{2}\.\d{4}$/' => 'd.m.Y',
			'/^\d{4}\/\d{2}\/\d{2}$/' => 'Y/m/d',
			'/^\d{8}$/' => 'Ymd',
			'/^\d{2}-\d{2}-\d{4}$/' => 'm-d-Y',
			'/^[A-Z][a-z]{2} \d{1,2}, \d{4}$/' => 'M j, Y',
			'/^[A-Z][a-z]+ \d{1,2}, \d{4}$/' => 'F j, Y',
			'/^\d{1,2} [A-Z][a-z]+ \d{4}$/' => 'j F Y',
			'/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/' => 'Y-m-d H:i',
		];

		foreach ($patterns as $pattern => $format) {
			if (preg_match($pattern, $date)) {
				$parsed = DateTime::createFromFormat($format, $date);
				if ($parsed !== false) {
					return $format;
				}
			}
		}

		return null;
	}

	/**
	 * Get the ISO 8601 week-numbering year for a given date.
	 *
	 * This may differ from the calendar year at year boundaries.
	 *
	 * @param string $date The date string.
	 * @return int The ISO week-numbering year.
	 */
	public static function getWeekYear(string $date): int
	{
		return (int) (new DateTime($date))->format('o');
	}

	/**
	 * Check whether a date is the first day of its year (January 1st).
	 *
	 * @param string $date The date string.
	 * @return bool
	 */
	public static function isStartOfYear(string $date): bool
	{
		$dt = new DateTime($date);
		return $dt->format('m-d') === '01-01';
	}

	/**
	 * Check whether a date is the last day of its year (December 31st).
	 *
	 * @param string $date The date string.
	 * @return bool
	 */
	public static function isEndOfYear(string $date): bool
	{
		$dt = new DateTime($date);
		return $dt->format('m-d') === '12-31';
	}

	/**
	 * Check whether a date is the first day of its month.
	 *
	 * @param string $date The date string.
	 * @return bool
	 */
	public static function isStartOfMonth(string $date): bool
	{
		return self::isFirstDayOfMonth($date);
	}

	/**
	 * Check whether a date is the last day of its month.
	 *
	 * @param string $date The date string.
	 * @return bool
	 */
	public static function isEndOfMonth(string $date): bool
	{
		return self::isLastDayOfMonth($date);
	}

	/**
	 * Check whether a date is the start of its ISO week (Monday).
	 *
	 * @param string $date The date string.
	 * @return bool
	 */
	public static function isStartOfWeek(string $date): bool
	{
		return self::isMonday($date);
	}

	/**
	 * Check whether a date is the end of its ISO week (Sunday).
	 *
	 * @param string $date The date string.
	 * @return bool
	 */
	public static function isEndOfWeek(string $date): bool
	{
		return self::isSunday($date);
	}

	/**
	 * Get the business/work week number within a year, counting only
	 * weeks that contain at least one business day.
	 *
	 * @param string $date The date string.
	 * @return int Work week number (typically same as ISO week).
	 */
	public static function getWorkweekNumber(string $date): int
	{
		return (int) (new DateTime($date))->format('W');
	}

	/**
	 * Get the Unix timestamp for a given date string.
	 *
	 * @param string $date The date string.
	 * @return int Unix timestamp.
	 */
	public static function getTimestamp(string $date): int
	{
		return (new DateTime($date))->getTimestamp();
	}

	/**
	 * Get the first day of the next month.
	 *
	 * @param string $date The date string.
	 * @return string Date in 'Y-m-d' format.
	 */
	public static function getStartOfNextMonth(string $date): string
	{
		$dt = new DateTime($date);
		$dt->modify('first day of next month');
		return $dt->format('Y-m-d');
	}

	/**
	 * Get the first day of the previous month.
	 *
	 * @param string $date The date string.
	 * @return string Date in 'Y-m-d' format.
	 */
	public static function getStartOfPreviousMonth(string $date): string
	{
		$dt = new DateTime($date);
		$dt->modify('first day of last month');
		return $dt->format('Y-m-d');
	}

	/**
	 * Get the last day of the previous month.
	 *
	 * @param string $date The date string.
	 * @return string Date in 'Y-m-d' format.
	 */
	public static function getEndOfPreviousMonth(string $date): string
	{
		$dt = new DateTime($date);
		$dt->modify('last day of last month');
		return $dt->format('Y-m-d');
	}

	/**
	 * Get January 1st of the next year.
	 *
	 * @param string $date The date string.
	 * @return string Date in 'Y-m-d' format.
	 */
	public static function getStartOfNextYear(string $date): string
	{
		return (self::getYearFromDate($date) + 1) . '-01-01';
	}

	/**
	 * Get January 1st of the previous year.
	 *
	 * @param string $date The date string.
	 * @return string Date in 'Y-m-d' format.
	 */
	public static function getStartOfPreviousYear(string $date): string
	{
		return (self::getYearFromDate($date) - 1) . '-01-01';
	}

	/**
	 * Get the Monday of the next ISO week.
	 *
	 * @param string $date The date string.
	 * @return string Date in 'Y-m-d' format.
	 */
	public static function getStartOfNextWeek(string $date): string
	{
		$dt = new DateTime($date);
		$dt->modify('monday next week');
		return $dt->format('Y-m-d');
	}

	/**
	 * Get the Monday of the previous ISO week.
	 *
	 * @param string $date The date string.
	 * @return string Date in 'Y-m-d' format.
	 */
	public static function getStartOfPreviousWeek(string $date): string
	{
		$dt = new DateTime($date);
		$dt->modify('monday last week');
		return $dt->format('Y-m-d');
	}

	/**
	 * Get the Sunday of the previous ISO week.
	 *
	 * @param string $date The date string.
	 * @return string Date in 'Y-m-d' format.
	 */
	public static function getEndOfPreviousWeek(string $date): string
	{
		$dt = new DateTime($date);
		$dt->modify('sunday last week');
		return $dt->format('Y-m-d');
	}

	/**
	 * Get the Sunday of the next ISO week.
	 *
	 * @param string $date The date string.
	 * @return string Date in 'Y-m-d' format.
	 */
	public static function getEndOfNextWeek(string $date): string
	{
		$dt = new DateTime($date);
		$dt->modify('sunday next week');
		return $dt->format('Y-m-d');
	}

	/**
	 * Get which month (1–3) of the quarter a date is in.
	 *
	 * @param string $date The date string.
	 * @return int 1, 2, or 3.
	 */
	public static function getQuarterMonth(string $date): int
	{
		$month = self::getMonthFromDate($date);
		return (($month - 1) % 3) + 1;
	}

	/**
	 * Get the ordinal occurrence of the date's weekday within its month.
	 *
	 * E.g. if date is the 2nd Tuesday → returns 2.
	 *
	 * @param string $date The date string.
	 * @return int The ordinal (1–5).
	 */
	public static function getWeekdayOrdinalInMonth(string $date): int
	{
		$day = self::getDayFromDate($date);
		return (int) ceil($day / 7);
	}

	/**
	 * Get a human-readable ordinal weekday label (e.g. "2nd Tuesday of June").
	 *
	 * @param string $date The date string.
	 * @return string The ordinal weekday label.
	 */
	public static function getOrdinalWeekdayLabel(string $date): string
	{
		$nth = self::getWeekdayOrdinalInMonth($date);
		$dt = new DateTime($date);
		$dayName = $dt->format('l');
		$monthName = $dt->format('F');
		$suffix = self::dayWithSuffix($nth);
		return "{$suffix} {$dayName} of {$monthName}";
	}

	/**
	 * Check whether a month has 31 days.
	 *
	 * @param int $year  The Gregorian year.
	 * @param int $month The month (1–12).
	 * @return bool
	 */
	public static function isLongMonth(int $year, int $month): bool
	{
		return (int) (new DateTime("{$year}-{$month}-01"))->format('t') === 31;
	}

	/**
	 * Check whether a month has fewer than 31 days.
	 *
	 * @param int $year  The Gregorian year.
	 * @param int $month The month (1–12).
	 * @return bool
	 */
	public static function isShortMonth(int $year, int $month): bool
	{
		return !self::isLongMonth($year, $month);
	}

	/**
	 * Calculate the total working hours between two datetimes (alias with different return).
	 *
	 * @param string $start     Start datetime.
	 * @param string $end       End datetime.
	 * @param int    $workStart Start of business day (default 9).
	 * @param int    $workEnd   End of business day (default 18).
	 * @param array  $holidays  Holiday dates in 'Y-m-d' format.
	 * @return float Total business hours.
	 */
	public static function diffInBusinessHours(string $start, string $end, int $workStart = 9, int $workEnd = 18, array $holidays = []): float
	{
		return self::getWorkingHoursBetween($start, $end, $workStart, $workEnd, $holidays);
	}

	/**
	 * Add a number of working minutes to a datetime, respecting business hours.
	 *
	 * @param string $datetime  Start datetime.
	 * @param int    $minutes   Working minutes to add.
	 * @param int    $workStart Business day start hour (default 9).
	 * @param int    $workEnd   Business day end hour (default 18).
	 * @param array  $holidays  Holiday dates in 'Y-m-d' format.
	 * @return string Resulting datetime in 'Y-m-d H:i:s' format.
	 */
	public static function addWorkingMinutes(string $datetime, int $minutes, int $workStart = 9, int $workEnd = 18, array $holidays = []): string
	{
		$dt = new DateTime($datetime);
		$remainingSeconds = $minutes * 60;
		$workDaySeconds = ($workEnd - $workStart) * 3600;

		$hour = (int) $dt->format('G');
		if ($hour < $workStart) {
			$dt->setTime($workStart, 0, 0);
		} elseif ($hour >= $workEnd) {
			$dt->modify('+1 day');
			$dt->setTime($workStart, 0, 0);
		}

		while ($remainingSeconds > 0) {
			$dateStr = $dt->format('Y-m-d');
			if (!self::isBusinessDay($dateStr, $holidays)) {
				$dt->modify('+1 day');
				$dt->setTime($workStart, 0, 0);
				continue;
			}

			$dayEnd = (clone $dt)->setTime($workEnd, 0, 0);
			$availableSeconds = $dayEnd->getTimestamp() - $dt->getTimestamp();

			if ($availableSeconds <= 0) {
				$dt->modify('+1 day');
				$dt->setTime($workStart, 0, 0);
				continue;
			}

			if ($remainingSeconds <= $availableSeconds) {
				$dt->modify("+{$remainingSeconds} seconds");
				$remainingSeconds = 0;
			} else {
				$remainingSeconds -= $availableSeconds;
				$dt->modify('+1 day');
				$dt->setTime($workStart, 0, 0);
			}
		}

		return $dt->format('Y-m-d H:i:s');
	}

	/**
	 * Get the ISO day-of-week from a day-count since the Unix epoch.
	 *
	 * Day 0 (1970-01-01) is Thursday (4).
	 *
	 * @param int $daysSinceEpoch Days since 1970-01-01.
	 * @return int ISO day of week (1 = Monday, 7 = Sunday).
	 */
	public static function getEpochDayOfWeek(int $daysSinceEpoch): int
	{
		$dow = (($daysSinceEpoch + 3) % 7) + 1;
		return $dow <= 0 ? $dow + 7 : $dow;
	}

	/**
	 * Format a number of seconds as a verbose elapsed string.
	 *
	 * E.g. 90061 → "1 day, 1 hour, 1 minute, 1 second".
	 *
	 * @param int $seconds Total seconds.
	 * @return string Human-readable elapsed string.
	 */
	public static function formatElapsed(int $seconds): string
	{
		$abs = abs($seconds);
		$d = intdiv($abs, 86400);
		$h = intdiv($abs % 86400, 3600);
		$m = intdiv($abs % 3600, 60);
		$s = $abs % 60;

		$parts = [];
		if ($d > 0)
			$parts[] = $d . ' day' . ($d !== 1 ? 's' : '');
		if ($h > 0)
			$parts[] = $h . ' hour' . ($h !== 1 ? 's' : '');
		if ($m > 0)
			$parts[] = $m . ' minute' . ($m !== 1 ? 's' : '');
		if ($s > 0 || $parts === [])
			$parts[] = $s . ' second' . ($s !== 1 ? 's' : '');

		$result = implode(', ', $parts);
		return $seconds < 0 ? "-{$result}" : $result;
	}

	/**
	 * Get the start-of-day and end-of-day datetimes for a given date.
	 *
	 * @param string $date The date string.
	 * @return array{start: string, end: string}
	 */
	public static function getDateBoundaries(string $date): array
	{
		return [
			'start' => self::getStartOfDay($date),
			'end' => self::getEndOfDay($date),
		];
	}

	/**
	 * Get the month boundaries (first and last day) for a given year and month.
	 *
	 * @param int $year  The Gregorian year.
	 * @param int $month The month (1–12).
	 * @return array{start: string, end: string}
	 */
	public static function getMonthBoundaries(int $year, int $month): array
	{
		$first = new DateTime("{$year}-{$month}-01");
		return [
			'start' => $first->format('Y-m-d'),
			'end' => $first->format('Y-m-t'),
		];
	}

	/**
	 * Get the year boundaries (Jan 1 – Dec 31) for a given year.
	 *
	 * @param int $year The Gregorian year.
	 * @return array{start: string, end: string}
	 */
	public static function getYearBoundaries(int $year): array
	{
		return [
			'start' => "{$year}-01-01",
			'end' => "{$year}-12-31",
		];
	}

	/**
	 * Get the ISO week boundaries (Monday – Sunday) for a given date.
	 *
	 * @param string $date The date string.
	 * @return array{start: string, end: string}
	 */
	public static function getWeekBoundaries(string $date): array
	{
		return [
			'start' => self::getStartOfWeek($date),
			'end' => self::getEndOfWeek($date),
		];
	}

	/**
	 * Get a relative calendar day label: "Today", "Yesterday", "Tomorrow", or the date.
	 *
	 * @param string $date The date string.
	 * @return string
	 */
	public static function getRelativeCalendarDay(string $date): string
	{
		$dateStr = (new DateTime($date))->format('Y-m-d');
		$today = date('Y-m-d');
		$yesterday = date('Y-m-d', strtotime('-1 day'));
		$tomorrow = date('Y-m-d', strtotime('+1 day'));

		return match ($dateStr) {
			$today => 'Today',
			$yesterday => 'Yesterday',
			$tomorrow => 'Tomorrow',
			default => $dateStr,
		};
	}

	/**
	 * Get the difference in calendar days (ignoring time) between two dates.
	 *
	 * @param string $date1 First date.
	 * @param string $date2 Second date.
	 * @return int Absolute number of calendar days.
	 */
	public static function diffInCalendarDays(string $date1, string $date2): int
	{
		$d1 = new DateTime((new DateTime($date1))->format('Y-m-d'));
		$d2 = new DateTime((new DateTime($date2))->format('Y-m-d'));
		return (int) $d1->diff($d2)->days;
	}

	/**
	 * Get the difference in calendar months between two dates.
	 *
	 * @param string $date1 First date.
	 * @param string $date2 Second date.
	 * @return int Absolute number of calendar months.
	 */
	public static function diffInCalendarMonths(string $date1, string $date2): int
	{
		$d1 = new DateTime($date1);
		$d2 = new DateTime($date2);
		return abs(((int) $d1->format('Y') * 12 + (int) $d1->format('n'))
			- ((int) $d2->format('Y') * 12 + (int) $d2->format('n')));
	}

	/**
	 * Get the difference in calendar years between two dates.
	 *
	 * @param string $date1 First date.
	 * @param string $date2 Second date.
	 * @return int Absolute number of calendar years.
	 */
	public static function diffInCalendarYears(string $date1, string $date2): int
	{
		return abs(self::getYearFromDate($date1) - self::getYearFromDate($date2));
	}

	/**
	 * Get the exact difference between two datetimes in fractional days.
	 *
	 * Includes time in the calculation (unlike diffInCalendarDays).
	 *
	 * @param string $date1 First datetime.
	 * @param string $date2 Second datetime.
	 * @return float Exact decimal days.
	 */
	public static function diffInDecimalDays(string $date1, string $date2): float
	{
		return self::floatDiffInDays($date1, $date2);
	}

	/**
	 * Check whether a date string is valid ISO 8601 format.
	 *
	 * @param string $date The date string.
	 * @return bool
	 */
	public static function isISO8601(string $date): bool
	{
		return (bool) preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}([+-]\d{2}:\d{2}|Z)$/', $date);
	}

	/**
	 * Check whether a date string is valid RFC 2822 format.
	 *
	 * @param string $date The date string.
	 * @return bool
	 */
	public static function isRFC2822(string $date): bool
	{
		return DateTime::createFromFormat(DateTimeInterface::RFC2822, $date) !== false;
	}

	/**
	 * Create a date string from individual year, month, day components.
	 *
	 * @param int    $year   The year.
	 * @param int    $month  The month (1–12).
	 * @param int    $day    The day (1–31).
	 * @param string $format Output format (default 'Y-m-d').
	 * @return string Formatted date.
	 */
	public static function createDate(int $year, int $month, int $day, string $format = 'Y-m-d'): string
	{
		$dt = new DateTime();
		$dt->setDate($year, $month, $day);
		$dt->setTime(0, 0, 0);
		return $dt->format($format);
	}

	/**
	 * Create a datetime string from individual components.
	 *
	 * @param int    $year   The year.
	 * @param int    $month  The month (1–12).
	 * @param int    $day    The day (1–31).
	 * @param int    $hour   The hour (0–23, default 0).
	 * @param int    $minute The minute (0–59, default 0).
	 * @param int    $second The second (0–59, default 0).
	 * @param string $format Output format (default 'Y-m-d H:i:s').
	 * @return string Formatted datetime.
	 */
	public static function createDateTime(int $year, int $month, int $day, int $hour = 0, int $minute = 0, int $second = 0, string $format = 'Y-m-d H:i:s'): string
	{
		$dt = new DateTime();
		$dt->setDate($year, $month, $day);
		$dt->setTime($hour, $minute, $second);
		return $dt->format($format);
	}

	/**
	 * Get the Julian Date (JD) for a given date string.
	 *
	 * @param string $date The date string.
	 * @return float Julian Date.
	 */
	public static function getJulianDate(string $date): float
	{
		$dt = new DateTime($date);
		return self::toJulian(
			(int) $dt->format('Y'),
			(int) $dt->format('n'),
			(float) $dt->format('j') + ((int) $dt->format('G') - 12) / 24.0
			+ (int) $dt->format('i') / 1440.0
			+ (int) $dt->format('s') / 86400.0
		);
	}

	/**
	 * Get the Modified Julian Date (MJD = JD − 2400000.5).
	 *
	 * @param string $date The date string.
	 * @return float Modified Julian Date.
	 */
	public static function getModifiedJulianDate(string $date): float
	{
		return self::getJulianDate($date) - 2400000.5;
	}

	/**
	 * Get the Lilian Date (days since October 15, 1582 Gregorian calendar adoption).
	 *
	 * @param string $date The date string.
	 * @return int Lilian day number.
	 */
	public static function getLilianDate(string $date): int
	{
		return (int) floor(self::getJulianDate($date) - 2299159.5);
	}

	/**
	 * Get the Rata Die day number (days since January 1, AD 1).
	 *
	 * @param string $date The date string.
	 * @return int Rata Die day number.
	 */
	public static function getRataDie(string $date): int
	{
		return (int) floor(self::getJulianDate($date) - 1721424.5);
	}

	/**
	 * Get the next month as 'Y-m' (or custom format).
	 *
	 * @param string $date   The date string.
	 * @param string $format Output format (default 'Y-m').
	 * @return string
	 */
	public static function getNextMonth(string $date, string $format = 'Y-m'): string
	{
		$dt = new DateTime($date);
		$dt->modify('first day of next month');
		return $dt->format($format);
	}

	/**
	 * Get the previous month as 'Y-m' (or custom format).
	 *
	 * @param string $date   The date string.
	 * @param string $format Output format (default 'Y-m').
	 * @return string
	 */
	public static function getPreviousMonth(string $date, string $format = 'Y-m'): string
	{
		$dt = new DateTime($date);
		$dt->modify('first day of last month');
		return $dt->format($format);
	}

	/**
	 * Check whether a date exists in an array of date strings.
	 *
	 * Compares 'Y-m-d' representations to avoid time/format mismatches.
	 *
	 * @param string $date  The date to look for.
	 * @param array  $dates Array of date strings.
	 * @return bool
	 */
	public static function isDateInArray(string $date, array $dates): bool
	{
		$target = (new DateTime($date))->format('Y-m-d');
		foreach ($dates as $d) {
			if ((new DateTime($d))->format('Y-m-d') === $target) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Remove duplicate dates from an array, comparing 'Y-m-d' only.
	 *
	 * @param array $dates Array of date strings.
	 * @return array De-duplicated array of 'Y-m-d' date strings.
	 */
	public static function uniqueDates(array $dates): array
	{
		$seen = [];
		$result = [];
		foreach ($dates as $d) {
			$key = (new DateTime($d))->format('Y-m-d');
			if (!isset($seen[$key])) {
				$seen[$key] = true;
				$result[] = $key;
			}
		}
		return $result;
	}

	/**
	 * Find the intersection of two date ranges.
	 *
	 * @param array $range1 ['start' => string, 'end' => string]
	 * @param array $range2 ['start' => string, 'end' => string]
	 * @return array Intersecting range ['start' => string, 'end' => string] or empty array.
	 */
	public static function intersectDateRanges(array $range1, array $range2): array
	{
		$overlapStart = max($range1['start'], $range2['start']);
		$overlapEnd = min($range1['end'], $range2['end']);

		if ($overlapStart > $overlapEnd) {
			return [];
		}

		return ['start' => $overlapStart, 'end' => $overlapEnd];
	}

	/**
	 * Count the total number of distinct days covered by an array of date ranges.
	 *
	 * Ranges are merged first to avoid double-counting overlaps.
	 *
	 * @param array<int, array{start: string, end: string}> $ranges
	 * @return int Total number of days.
	 */
	public static function countDaysInDateRanges(array $ranges): int
	{
		$merged = self::mergeDateRanges($ranges);
		$total = 0;
		foreach ($merged as $range) {
			$total += self::diffInCalendarDays($range['start'], $range['end']) + 1;
		}
		return $total;
	}

	/**
	 * Get a "Month Year" label (e.g. "June 2026").
	 *
	 * @param string $date The date string.
	 * @return string
	 */
	public static function getMonthYearLabel(string $date): string
	{
		return (new DateTime($date))->format('F Y');
	}

	/**
	 * Get a human-readable ISO week label (e.g. "Week 22, 2026").
	 *
	 * @param string $date The date string.
	 * @return string
	 */
	public static function getWeekLabel(string $date): string
	{
		$dt = new DateTime($date);
		return 'Week ' . $dt->format('W') . ', ' . $dt->format('o');
	}

	/**
	 * Shift a date by arbitrary combinations of years, months, days, hours,
	 * minutes, and seconds in a single call.
	 *
	 * Negative values subtract.
	 *
	 * @param string $date    The starting datetime.
	 * @param int    $years   Years to add (default 0).
	 * @param int    $months  Months to add (default 0).
	 * @param int    $days    Days to add (default 0).
	 * @param int    $hours   Hours to add (default 0).
	 * @param int    $minutes Minutes to add (default 0).
	 * @param int    $seconds Seconds to add (default 0).
	 * @param string $format  Output format (default 'Y-m-d H:i:s').
	 * @return string Resulting datetime string.
	 */
	public static function shiftDate(string $date, int $years = 0, int $months = 0, int $days = 0, int $hours = 0, int $minutes = 0, int $seconds = 0, string $format = 'Y-m-d H:i:s'): string
	{
		$dt = new DateTime($date);

		$ySign = $years >= 0 ? '+' : '-';
		$mSign = $months >= 0 ? '+' : '-';
		$dSign = $days >= 0 ? '+' : '-';
		$hSign = $hours >= 0 ? '+' : '-';
		$miSign = $minutes >= 0 ? '+' : '-';
		$sSign = $seconds >= 0 ? '+' : '-';

		$dt->modify("{$ySign}" . abs($years) . " years");
		$dt->modify("{$mSign}" . abs($months) . " months");
		$dt->modify("{$dSign}" . abs($days) . " days");
		$dt->modify("{$hSign}" . abs($hours) . " hours");
		$dt->modify("{$miSign}" . abs($minutes) . " minutes");
		$dt->modify("{$sSign}" . abs($seconds) . " seconds");

		return $dt->format($format);
	}

	/**
	 * Convert a datetime between two explicit IANA timezones.
	 *
	 * @param string $datetime     The datetime string.
	 * @param string $fromTimezone Source timezone.
	 * @param string $toTimezone   Target timezone.
	 * @param string $format       Output format (default 'Y-m-d H:i:s').
	 * @return string Converted datetime string.
	 */
	public static function convertBetweenTimezones(string $datetime, string $fromTimezone, string $toTimezone, string $format = 'Y-m-d H:i:s'): string
	{
		return self::convertTimezone($datetime, $fromTimezone, $toTimezone, $format);
	}

    public function getDosTimeFromUnixTime(int $unixtime = 0)
    {
		$timearray = ($unixtime === 0) ? getdate() : getdate($unixtime);

		if ($timearray['year'] < 1980) {
			$timearray['year'] = 1980;
			$timearray['mon'] = 1;
			$timearray['mday'] = 1;
			$timearray['hours'] = 0;
			$timearray['minutes'] = 0;
			$timearray['seconds'] = 0;
        }

        return (($timearray['year'] - 1980) << 25) | ($timearray['mon'] << 21) | ($timearray['mday'] << 16) | ($timearray['hours'] << 11) | ($timearray['minutes'] << 5) | ($timearray['seconds'] >> 1);
	}
}
