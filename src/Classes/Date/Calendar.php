<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Date;

use DateTime;
use DateTimeZone;
use DateInterval;
use DatePeriod;

use function intval;
use function sprintf;
use function in_array;
use function is_int;

/**
 * Calendar Class
 *
 * Provides comprehensive calendar operations including date navigation,
 * week/month/year boundary calculations, recurring event generation,
 * lunar phase approximation, and calendar grid rendering.
 */
class Calendar
{
    /** @var int The current year */
    private int $year;

    /** @var int The current month */
    private int $month;

    /** @var int The current day */
    private int $day;

    /** @var array<int, string> Short names of the days of the week */
    private array $days;

    /** @var int The last day of the previous month */
    private int $lastDayOfPreviousMonth;

    /** @var int The last day of the current month */
    private int $lastDay;

    /** @var string The full English name of the current month */
    private string $textureNameOfMonth;

    /** @var int The weekday index (0=Sun..6=Sat) of the 1st of the current month */
    private int $firstDayOfWeek;

    /** @var DateTimeZone|null Optional timezone context */
    private ?DateTimeZone $timezone;

    /**
     * @param string      $date     Any strtotime-compatible date string (default 'now').
     * @param string|null $timezone IANA timezone identifier. Null uses the system default.
     */
    public function __construct(string $date = 'now', ?string $timezone = null)
    {
        if ($timezone !== null) {
            $this->timezone = new DateTimeZone($timezone);
        } else {
            $this->timezone = null;
        }

        $current = strtotime($date, time());
        $this->days = [0 => 'Sun', 1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat'];

        $this->year = intval(date('Y', $current));
        $this->month = intval(date('m', $current));
        $this->day = intval(date('d', $current));
        $this->textureNameOfMonth = date('F', $current);

        $this->setAttributes();
    }

    /**
     * Recalculate derived attributes (last day of previous/current month,
     * first weekday of the month) based on the current year and month.
     *
     * @return void
     */
    private function setAttributes()
    {
        $date = sprintf("01-%d-%d", $this->month, $this->year);

        $this->lastDayOfPreviousMonth = intval(date('j', strtotime('last day of previous month', strtotime($date))));
        $this->lastDay = intval(date('t', strtotime($date)));

        $this->firstDayOfWeek = array_search(date('D', strtotime($date . '-1')), $this->days);
    }

    /**
     * Set the day and recalculate derived attributes.
     *
     * @param int $day The day of the month (1-31).
     * @return void
     */
    public function setDay(int $day): void
    {
        $this->day = $day;

        $this->setAttributes();
    }

    /**
     * Set the year and recalculate derived attributes.
     *
     * @param int $year The four-digit year.
     * @return void
     */
    public function setYear(int $year): void
    {
        $this->year = $year;

        $this->setAttributes();
    }

    /**
     * Set the month and recalculate derived attributes.
     *
     * @param int $month The month number (1-12).
     * @return void
     */
    public function setMonth(int $month): void
    {
        $this->month = $month;

        $this->setAttributes();
    }

    /**
     * Set the complete date (year, month, day) at once and recalculate attributes.
     *
     * @param int $year  The four-digit year.
     * @param int $month The month number (1-12).
     * @param int $day   The day of the month (1-31).
     * @return void
     */
    public function setDate(int $year, int $month, int $day): void
    {
        $this->year = $year;
        $this->month = $month;
        $this->day = $day;
        $this->textureNameOfMonth = date('F', mktime(0, 0, 0, $month, 1, $year));

        $this->setAttributes();
    }

    // ========================================================================
    // Getters – Basic Properties
    // ========================================================================

    /**
     * Get the number of days in the current year.
     *
     * @return int 365 or 366 for leap years.
     */
    public function getDaysInYear(): int
    {
        return date('L', mktime(0, 0, 0, 1, 1, $this->getYear())) ? 366 : 365;
    }

    /**
     * Get the count of months in a year.
     *
     * @return int Always 12 for the Gregorian calendar.
     */
    public function getCountOfMonths(): int
    {
        return 12;
    }

    /**
     * Get the last day of the current month.
     *
     * @return int Day number (28-31).
     */
    public function getLastDay(): int
    {
        return $this->lastDay;
    }

    /**
     * Get the last day of the previous month.
     *
     * @return int Day number (28-31).
     */
    public function getLastDayOfPreviousMonth(): int
    {
        return $this->lastDayOfPreviousMonth;
    }

    /**
     * Get the year.
     * 
     * @return int The year component.
     */
    public function getYear(): int
    {
        return $this->year;
    }

    /**
     * Get the month.
     * 
     * @return int The month component (1-12).
     */
    public function getMonth(): int
    {
        return $this->month;
    }

    /**
     * Get the day.
     * 
     * @return int The day component (1-31).
     */
    public function getDay(): int
    {
        return $this->day;
    }

    /**
     * Get the days of the week.
     * 
     * @return array<int, string> Weekday abbreviations indexed 0 (Sun) through 6 (Sat).
     */
    public function getDays(): array
    {
        return $this->days;
    }

    /**
     * Get the texture name of the current month.
     * 
     * @return string Full English month name (e.g. "January").
     */
    public function getTextureNameOfMonth(): string
    {
        return $this->textureNameOfMonth;
    }

    /**
     * Get the weekday index of the first day of the current month.
     *
     * @return int 0 for Sunday, 6 for Saturday.
     */
    public function getFirstDayOfWeek(): int
    {
        return $this->firstDayOfWeek;
    }

    /**
     * Get the timezone object associated with this calendar, if any.
     *
     * @return DateTimeZone|null The timezone or null if using system default.
     */
    public function getTimezone(): ?DateTimeZone
    {
        return $this->timezone;
    }

    /**
     * Get the IANA timezone name, or null if using system default.
     *
     * @return string|null e.g. "Asia/Seoul" or null.
     */
    public function getTimezoneName(): ?string
    {
        return $this->timezone?->getName();
    }

    // ========================================================================
    // Navigation
    // ========================================================================

    /**
     * Return a new Calendar instance set to the previous month.
     *
     * @return self
     */
    public function previousMonth(): self
    {
        $dt = $this->toDateTime();
        $dt->modify('first day of previous month');
        return new self($dt->format('Y-m-d'), $this->timezone?->getName());
    }

    /**
     * Return a new Calendar instance set to the next month.
     *
     * @return self
     */
    public function nextMonth(): self
    {
        $dt = $this->toDateTime();
        $dt->modify('first day of next month');
        return new self($dt->format('Y-m-d'), $this->timezone?->getName());
    }

    /**
     * Return a new Calendar instance set to the previous year (same month/day).
     *
     * @return self
     */
    public function previousYear(): self
    {
        $dt = $this->toDateTime();
        $dt->modify('-1 year');
        return new self($dt->format('Y-m-d'), $this->timezone?->getName());
    }

    /**
     * Return a new Calendar instance set to the next year (same month/day).
     *
     * @return self
     */
    public function nextYear(): self
    {
        $dt = $this->toDateTime();
        $dt->modify('+1 year');
        return new self($dt->format('Y-m-d'), $this->timezone?->getName());
    }

    /**
     * Return a new Calendar instance set to the previous day.
     *
     * @return self
     */
    public function previousDay(): self
    {
        $dt = $this->toDateTime();
        $dt->modify('-1 day');
        return new self($dt->format('Y-m-d'), $this->timezone?->getName());
    }

    /**
     * Return a new Calendar instance set to the next day.
     *
     * @return self
     */
    public function nextDay(): self
    {
        $dt = $this->toDateTime();
        $dt->modify('+1 day');
        return new self($dt->format('Y-m-d'), $this->timezone?->getName());
    }

    /**
     * Return a new Calendar instance offset by a given number of days.
     *
     * @param int $days Positive to move forward, negative to move backward.
     * @return self
     */
    public function addDays(int $days): self
    {
        $dt = $this->toDateTime();
        $dt->modify("{$days} days");
        return new self($dt->format('Y-m-d'), $this->timezone?->getName());
    }

    /**
     * Return a new Calendar instance offset by a given number of months.
     *
     * @param int $months Positive to move forward, negative to move backward.
     * @return self
     */
    public function addMonths(int $months): self
    {
        $dt = $this->toDateTime();
        $dt->modify("{$months} months");
        return new self($dt->format('Y-m-d'), $this->timezone?->getName());
    }

    /**
     * Return a new Calendar instance offset by a given number of years.
     *
     * @param int $years Positive to move forward, negative to move backward.
     * @return self
     */
    public function addYears(int $years): self
    {
        $dt = $this->toDateTime();
        $dt->modify("{$years} years");
        return new self($dt->format('Y-m-d'), $this->timezone?->getName());
    }

    /**
     * Return a new Calendar instance offset by a given number of weeks.
     *
     * @param int $weeks Positive to move forward, negative to move backward.
     * @return self
     */
    public function addWeeks(int $weeks): self
    {
        return $this->addDays($weeks * 7);
    }

    /**
     * Return a new Calendar instance set to the previous week (same weekday).
     *
     * @return self
     */
    public function previousWeek(): self
    {
        return $this->addDays(-7);
    }

    /**
     * Return a new Calendar instance set to the next week (same weekday).
     *
     * @return self
     */
    public function nextWeek(): self
    {
        return $this->addDays(7);
    }

    /**
     * Navigate to an arbitrary year/month combination.
     *
     * @param int      $year
     * @param int      $month
     * @param int|null $day  Clamped to the valid range for the target month.
     * @return self
     */
    public function goTo(int $year, int $month, ?int $day = null): self
    {
        $day = $day ?? 1;
        $maxDay = (int) date('t', mktime(0, 0, 0, $month, 1, $year));
        $day = min($day, $maxDay);
        return new self(sprintf('%04d-%02d-%02d', $year, $month, $day), $this->timezone?->getName());
    }

    /**
     * Return a new Calendar instance set to today's date.
     *
     * @return self
     */
    public function today(): self
    {
        return new self('now', $this->timezone?->getName());
    }

    /**
     * Return a new Calendar instance set to the first day of the current month.
     *
     * @return self
     */
    public function firstOfMonth(): self
    {
        return new self(sprintf('%04d-%02d-01', $this->year, $this->month), $this->timezone?->getName());
    }

    /**
     * Return a new Calendar instance set to the last day of the current month.
     *
     * @return self
     */
    public function lastOfMonth(): self
    {
        return new self(sprintf('%04d-%02d-%02d', $this->year, $this->month, $this->lastDay), $this->timezone?->getName());
    }

    /**
     * Return a new Calendar instance set to the first day of the current year.
     *
     * @return self
     */
    public function firstOfYear(): self
    {
        return new self(sprintf('%04d-01-01', $this->year), $this->timezone?->getName());
    }

    /**
     * Return a new Calendar instance set to the last day of the current year.
     *
     * @return self
     */
    public function lastOfYear(): self
    {
        return new self(sprintf('%04d-12-31', $this->year), $this->timezone?->getName());
    }

    /**
     * Return a new Calendar instance set to the start (Monday) of the current ISO week.
     *
     * @return self
     */
    public function startOfWeek(): self
    {
        $dt = $this->toDateTime();
        $dt->modify('monday this week');
        return new self($dt->format('Y-m-d'), $this->timezone?->getName());
    }

    /**
     * Return a new Calendar instance set to the end (Sunday) of the current ISO week.
     *
     * @return self
     */
    public function endOfWeek(): self
    {
        $dt = $this->toDateTime();
        $dt->modify('sunday this week');
        return new self($dt->format('Y-m-d'), $this->timezone?->getName());
    }

    /**
     * Return a new Calendar instance set to the first day of the current quarter.
     *
     * @return self
     */
    public function startOfQuarter(): self
    {
        $quarterStartMonth = ($this->getQuarter() - 1) * 3 + 1;

        return new self(sprintf('%04d-%02d-01', $this->year, $quarterStartMonth), $this->timezone?->getName());
    }

    /**
     * Return a new Calendar instance set to the last day of the current quarter.
     *
     * @return self
     */
    public function endOfQuarter(): self
    {
        $quarterEndMonth = $this->getQuarter() * 3;
        $lastDay = (int) date('t', mktime(0, 0, 0, $quarterEndMonth, 1, $this->year));

        return new self(sprintf('%04d-%02d-%02d', $this->year, $quarterEndMonth, $lastDay), $this->timezone?->getName());
    }

    /**
     * Create an independent copy of this Calendar instance.
     *
     * @return self
     */
    public function copy(): self
    {
        return new self(sprintf('%04d-%02d-%02d', $this->year, $this->month, $this->day), $this->timezone?->getName());
    }

    // ========================================================================
    // Conversion & Core Properties
    // ========================================================================

    /**
     * Build a DateTime object representing the current calendar date.
     *
     * @return DateTime
     */
    public function toDateTime(): DateTime
    {
        $dt = new DateTime(sprintf('%04d-%02d-%02d', $this->year, $this->month, $this->day));
        if ($this->timezone !== null) {
            $dt->setTimezone($this->timezone);
        }

        return $dt;
    }

    /**
     * Get the ISO-8601 week number of the current date.
     *
     * @return int 1-53
     */
    public function getISOWeekNumber(): int
    {
        return (int) $this->toDateTime()->format('W');
    }

    /**
     * Get the day-of-year number (1-indexed).
     *
     * @return int 1-366
     */
    public function getDayOfYear(): int
    {
        return (int) $this->toDateTime()->format('z') + 1;
    }

    /**
     * Determine the quarter of the current month (1-4).
     *
     * @return int
     */
    public function getQuarter(): int
    {
        return (int) ceil($this->month / 3);
    }

    /**
     * Check whether the current year is a leap year.
     *
     * @return bool
     */
    public function isLeapYear(): bool
    {
        return (bool) date('L', mktime(0, 0, 0, 1, 1, $this->year));
    }

    /**
     * Check whether the current date falls on a weekend (Saturday or Sunday).
     *
     * @return bool
     */
    public function isWeekend(): bool
    {
        $dow = (int) $this->toDateTime()->format('N');
        return $dow >= 6;
    }

    /**
     * Check whether the current date falls on a weekday (Monday-Friday).
     *
     * @return bool
     */
    public function isWeekday(): bool
    {
        return !$this->isWeekend();
    }

    /**
     * Check whether the current calendar date is today.
     *
     * @return bool
     */
    public function isToday(): bool
    {
        return $this->getDate() === date('Y-m-d');
    }

    /**
     * Check whether the current calendar date is in the past (before today).
     *
     * @return bool
     */
    public function isPast(): bool
    {
        return $this->toDateTime() < new DateTime('today');
    }

    /**
     * Check whether the current calendar date is in the future (after today).
     *
     * @return bool
     */
    public function isFuture(): bool
    {
        return $this->toDateTime() > new DateTime('today');
    }

    /**
     * Check whether the current date is a business day (Mon-Fri, not in holiday list).
     *
     * @param array<string> $holidays Array of 'Y-m-d' holiday strings.
     * @return bool
     */
    public function isBusinessDay(array $holidays = []): bool
    {
        return $this->isWeekday() && !in_array($this->getDate(), $holidays, true);
    }

    /**
     * Check whether the current month is the first month of a quarter.
     *
     * @return bool True for January, April, July, October.
     */
    public function isFirstMonthOfQuarter(): bool
    {
        return ($this->month - 1) % 3 === 0;
    }

    /**
     * Check whether the current month is the last month of a quarter.
     *
     * @return bool True for March, June, September, December.
     */
    public function isLastMonthOfQuarter(): bool
    {
        return $this->month % 3 === 0;
    }

    /**
     * Get the numeric day-of-week for the current date (PHP 'w' format).
     *
     * @return int 0=Sunday, 6=Saturday.
     */
    public function getDayOfWeek(): int
    {
        return (int) $this->toDateTime()->format('w');
    }

    /**
     * Get the ISO-8601 numeric day-of-week for the current date (PHP 'N' format).
     *
     * @return int 1=Monday, 7=Sunday.
     */
    public function getISODayOfWeek(): int
    {
        return (int) $this->toDateTime()->format('N');
    }

    /**
     * Get the short name of the current weekday (e.g. "Mon", "Tue").
     *
     * @return string Three-letter abbreviation.
     */
    public function getDayName(): string
    {
        return $this->days[$this->getDayOfWeek()];
    }

    /**
     * Get the full name of the current weekday (e.g. "Monday", "Tuesday").
     *
     * @return string Full English weekday name.
     */
    public function getFullDayName(): string
    {
        return $this->toDateTime()->format('l');
    }

    /**
     * Get the short (3-letter) name of the current month (e.g. "Jan", "Feb").
     *
     * @return string Abbreviated English month name.
     */
    public function getShortMonthName(): string
    {
        return date('M', mktime(0, 0, 0, $this->month, 1, $this->year));
    }

    /**
     * Get the current date formatted as 'Y-m-d'.
     *
     * @return string e.g. "2026-03-17".
     */
    public function getDate(): string
    {
        return sprintf('%04d-%02d-%02d', $this->year, $this->month, $this->day);
    }

    /**
     * Get the current date formatted as an ISO-8601 string.
     *
     * @return string e.g. "2026-03-17T00:00:00+09:00".
     */
    public function getISODate(): string
    {
        return $this->toDateTime()->format(DateTime::ATOM);
    }

    /**
     * Get the first date of the current month as a formatted string.
     *
     * @param string $format Date format (default 'Y-m-d').
     * @return string
     */
    public function getFirstDateOfMonth(string $format = 'Y-m-d'): string
    {
        return date($format, mktime(0, 0, 0, $this->month, 1, $this->year));
    }

    /**
     * Get the last date of the current month as a formatted string.
     *
     * @param string $format Date format (default 'Y-m-d').
     * @return string
     */
    public function getLastDateOfMonth(string $format = 'Y-m-d'): string
    {
        return date($format, mktime(0, 0, 0, $this->month, $this->lastDay, $this->year));
    }

    /**
     * Get the Unix timestamp for the current calendar date at midnight.
     *
     * @return int
     */
    public function getTimestamp(): int
    {
        return $this->toDateTime()->getTimestamp();
    }

    /**
     * Calculate the difference in days between this calendar and another.
     *
     * @param Calendar $other The other Calendar instance.
     * @return int Absolute number of days between the two dates.
     */
    public function diffInDays(Calendar $other): int
    {
        return (int) $this->toDateTime()->diff($other->toDateTime())->days;
    }

    /**
     * Calculate the difference in months between this calendar and another.
     *
     * @param Calendar $other The other Calendar instance.
     * @return int Total number of months difference.
     */
    public function diffInMonths(Calendar $other): int
    {
        $diff = $this->toDateTime()->diff($other->toDateTime());
        return ($diff->y * 12) + $diff->m;
    }

    /**
     * Calculate the difference in years between this calendar and another.
     *
     * @param Calendar $other The other Calendar instance.
     * @return int Number of full years difference.
     */
    public function diffInYears(Calendar $other): int
    {
        return $this->toDateTime()->diff($other->toDateTime())->y;
    }

    /**
     * Check whether this calendar shares the same month and year as another.
     *
     * @param Calendar $other The other Calendar instance.
     * @return bool
     */
    public function isSameMonth(Calendar $other): bool
    {
        return $this->year === $other->getYear() && $this->month === $other->getMonth();
    }

    /**
     * Check whether this calendar shares the same year as another.
     *
     * @param Calendar $other The other Calendar instance.
     * @return bool
     */
    public function isSameYear(Calendar $other): bool
    {
        return $this->year === $other->getYear();
    }

    /**
     * Check whether this calendar represents the exact same date as another.
     *
     * @param Calendar $other The other Calendar instance.
     * @return bool
     */
    public function isSameDay(Calendar $other): bool
    {
        return $this->year === $other->getYear()
            && $this->month === $other->getMonth()
            && $this->day === $other->getDay();
    }

    /**
     * Check whether this date is before another calendar date.
     *
     * @param Calendar $other The other Calendar instance.
     * @return bool
     */
    public function isBefore(Calendar $other): bool
    {
        return $this->toDateTime() < $other->toDateTime();
    }

    /**
     * Check whether this date is after another calendar date.
     *
     * @param Calendar $other The other Calendar instance.
     * @return bool
     */
    public function isAfter(Calendar $other): bool
    {
        return $this->toDateTime() > $other->toDateTime();
    }

    /**
     * Check whether this date falls between two other calendar dates (inclusive).
     *
     * @param Calendar $start The start boundary.
     * @param Calendar $end   The end boundary.
     * @return bool
     */
    public function isBetween(Calendar $start, Calendar $end): bool
    {
        $dt = $this->toDateTime();
        return $dt >= $start->toDateTime() && $dt <= $end->toDateTime();
    }

    // ========================================================================
    // Week & Month Metrics
    // ========================================================================

    /**
     * Get the number of weeks (rows) required to display the current month grid.
     *
     * @return int Typically 4-6.
     */
    public function getWeekCountInMonth(): int
    {
        return (int) ceil(($this->firstDayOfWeek + $this->lastDay) / 7);
    }

    /**
     * Get the number of remaining days from the current date to end of month.
     *
     * @return int
     */
    public function getRemainingDaysInMonth(): int
    {
        return $this->lastDay - $this->day;
    }

    /**
     * Get the number of remaining days from the current date to end of year.
     *
     * @return int
     */
    public function getRemainingDaysInYear(): int
    {
        return $this->getDaysInYear() - $this->getDayOfYear();
    }

    /**
     * Get the number of days elapsed in the current month (including today).
     *
     * @return int
     */
    public function getElapsedDaysInMonth(): int
    {
        return $this->day;
    }

    /**
     * Get the progress through the current month as a fraction (0.0 - 1.0).
     *
     * @return float
     */
    public function getMonthProgress(): float
    {
        return round($this->day / $this->lastDay, 4);
    }

    /**
     * Get the progress through the current year as a fraction (0.0 - 1.0).
     *
     * @return float
     */
    public function getYearProgress(): float
    {
        return round($this->getDayOfYear() / $this->getDaysInYear(), 4);
    }

    /**
     * Get the week number within the current month (1-based).
     *
     * @return int 1-6
     */
    public function getWeekOfMonth(): int
    {
        return (int) ceil(($this->firstDayOfWeek + $this->day) / 7);
    }

    /**
     * Get the number of days in a specific month of the current year.
     *
     * @param int $month The month (1-12).
     * @return int 28-31
     */
    public function getDaysInMonth(int $month): int
    {
        return (int) date('t', mktime(0, 0, 0, $month, 1, $this->year));
    }

    // ========================================================================
    // Name Listings
    // ========================================================================

    /**
     * Get the full month names for the entire year.
     *
     * @return array<int, string> 1-indexed month number => English month name.
     */
    public function getMonthNames(): array
    {
        $names = [];
        for ($m = 1; $m <= 12; $m++) {
            $names[$m] = date('F', mktime(0, 0, 0, $m, 1, $this->year));
        }

        return $names;
    }

    /**
     * Get the short (3-letter) month names for the entire year.
     *
     * @return array<int, string> 1-indexed month number => abbreviated month name.
     */
    public function getShortMonthNames(): array
    {
        $names = [];
        for ($m = 1; $m <= 12; $m++) {
            $names[$m] = date('M', mktime(0, 0, 0, $m, 1, $this->year));
        }

        return $names;
    }

    /**
     * Get the full weekday names for the entire week.
     *
     * @return array<int, string> 0-indexed (0=Sunday) => Full English weekday name.
     */
    public function getFullDayNames(): array
    {
        $names = [];
        // 2006-01-01 was a Sunday; offset to get Sun(0)..Sat(6)
        for ($i = 0; $i < 7; $i++) {
            $names[$i] = date('l', strtotime("2006-01-0" . ($i + 1)));
        }

        return $names;
    }

    // ========================================================================
    // Grid & Date Listings
    // ========================================================================

    /**
     * Generate a 2D grid representing the month view of a traditional calendar.
     *
     * Each cell is either null (empty) or an associative array:
     *   - day:     int   Day number
     *   - month:   int   Month number the day belongs to
     *   - year:    int   Year the day belongs to
     *   - current: bool  True if this is the current month
     *   - today:   bool  True if this matches today's date
     *   - weekend: bool  True if Saturday or Sunday
     *
     * @param bool $padWithAdjacentDays Fill leading/trailing cells with prev/next month days.
     * @return array<int, array<int, array|null>> Rows (weeks) x columns (days 0=Sun..6=Sat).
     */
    public function getMonthGrid(bool $padWithAdjacentDays = true): array
    {
        $grid = [];
        $weekCount = $this->getWeekCountInMonth();
        $todayStr = date('Y-m-d');
        $nextMonthDay = 1;

        for ($week = 0; $week < $weekCount; $week++) {
            $row = [];
            for ($dow = 0; $dow < 7; $dow++) {
                $cellIndex = $week * 7 + $dow;
                $offset = $cellIndex - $this->firstDayOfWeek;

                if ($offset < 0) {
                    // Cell belongs to the previous month
                    if ($padWithAdjacentDays) {
                        $prevDay = $this->lastDayOfPreviousMonth + $offset + 1;
                        $prevMonth = $this->month - 1;
                        $prevYear = $this->year;
                        if ($prevMonth < 1) {
                            $prevMonth = 12;
                            $prevYear--;
                        }
                        $row[] = [
                            'day' => $prevDay,
                            'month' => $prevMonth,
                            'year' => $prevYear,
                            'current' => false,
                            'today' => sprintf('%04d-%02d-%02d', $prevYear, $prevMonth, $prevDay) === $todayStr,
                            'weekend' => $dow === 0 || $dow === 6,
                        ];
                    } else {
                        $row[] = null;
                    }
                } elseif ($offset >= $this->lastDay) {
                    // Cell belongs to the next month
                    if ($padWithAdjacentDays) {
                        $nextMonth = $this->month + 1;
                        $nextYear = $this->year;
                        if ($nextMonth > 12) {
                            $nextMonth = 1;
                            $nextYear++;
                        }
                        $row[] = [
                            'day' => $nextMonthDay,
                            'month' => $nextMonth,
                            'year' => $nextYear,
                            'current' => false,
                            'today' => sprintf('%04d-%02d-%02d', $nextYear, $nextMonth, $nextMonthDay) === $todayStr,
                            'weekend' => $dow === 0 || $dow === 6,
                        ];
                        $nextMonthDay++;
                    } else {
                        $row[] = null;
                    }
                } else {
                    // Cell belongs to the current month
                    $d = $offset + 1;
                    $row[] = [
                        'day' => $d,
                        'month' => $this->month,
                        'year' => $this->year,
                        'current' => true,
                        'today' => sprintf('%04d-%02d-%02d', $this->year, $this->month, $d) === $todayStr,
                        'weekend' => $dow === 0 || $dow === 6,
                    ];
                }
            }
            $grid[] = $row;
        }

        return $grid;
    }

    /**
     * Generate a flat list of all dates in the current month.
     *
     * @param string $format Date format string (default 'Y-m-d').
     * @return array<string>
     */
    public function getDatesInMonth(string $format = 'Y-m-d'): array
    {
        $dates = [];
        for ($d = 1; $d <= $this->lastDay; $d++) {
            $dates[] = date($format, mktime(0, 0, 0, $this->month, $d, $this->year));
        }

        return $dates;
    }

    /**
     * Get all weekends (Saturday and Sunday) in the current month.
     *
     * @return array<string> Dates in 'Y-m-d' format.
     */
    public function getWeekendsInMonth(): array
    {
        $weekends = [];
        for ($d = 1; $d <= $this->lastDay; $d++) {
            $ts = mktime(0, 0, 0, $this->month, $d, $this->year);
            $dow = (int) date('N', $ts);
            if ($dow >= 6) {
                $weekends[] = date('Y-m-d', $ts);
            }
        }

        return $weekends;
    }

    /**
     * Get all weekdays (Monday-Friday) in the current month.
     *
     * @return array<string> Dates in 'Y-m-d' format.
     */
    public function getWeekdaysInMonth(): array
    {
        $weekdays = [];
        for ($d = 1; $d <= $this->lastDay; $d++) {
            $ts = mktime(0, 0, 0, $this->month, $d, $this->year);
            $dow = (int) date('N', $ts);
            if ($dow < 6) {
                $weekdays[] = date('Y-m-d', $ts);
            }
        }

        return $weekdays;
    }

    /**
     * Count occurrences of each weekday in the current month.
     *
     * @return array<string, int> e.g. ['Mon' => 5, 'Tue' => 4, ...]
     */
    public function getWeekdayCountsInMonth(): array
    {
        $counts = array_fill_keys(array_values($this->days), 0);
        for ($d = 1; $d <= $this->lastDay; $d++) {
            $dow = (int) date('w', mktime(0, 0, 0, $this->month, $d, $this->year));
            $counts[$this->days[$dow]]++;
        }

        return $counts;
    }

    /**
     * Get all dates in the current month that fall on a specific weekday.
     *
     * @param int $weekday 0=Sunday, 6=Saturday.
     * @return array<string> Dates in 'Y-m-d' format.
     */
    public function getDatesForWeekday(int $weekday): array
    {
        $dates = [];
        for ($d = 1; $d <= $this->lastDay; $d++) {
            $ts = mktime(0, 0, 0, $this->month, $d, $this->year);
            if ((int) date('w', $ts) === $weekday) {
                $dates[] = date('Y-m-d', $ts);
            }
        }

        return $dates;
    }

    /**
     * Get the ISO week numbers that overlap with the current month.
     *
     * @return array<int> ISO week numbers.
     */
    public function getISOWeeksInMonth(): array
    {
        $weeks = [];
        for ($d = 1; $d <= $this->lastDay; $d++) {
            $w = (int) date('W', mktime(0, 0, 0, $this->month, $d, $this->year));
            if (!in_array($w, $weeks, true)) {
                $weeks[] = $w;
            }
        }
        return $weeks;
    }

    /**
     * Get the date of the N-th occurrence of a specific weekday in the current month.
     *
     * @param int $nth     Positive for forward count (1=first), -1 for last occurrence.
     * @param int $weekday 0=Sunday, 6=Saturday.
     * @return string|null 'Y-m-d' or null if the occurrence does not exist.
     */
    public function getNthWeekday(int $nth, int $weekday): ?string
    {
        $dayNames = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
        $dayName = $dayNames[$weekday % 7];

        $dt = new DateTime(sprintf('%04d-%02d-01', $this->year, $this->month));

        if ($nth > 0) {
            $ordinals = [1 => 'first', 2 => 'second', 3 => 'third', 4 => 'fourth', 5 => 'fifth'];
            if (!isset($ordinals[$nth])) {
                return null;
            }
            $dt->modify("{$ordinals[$nth]} {$dayName} of this month");
        } elseif ($nth === -1) {
            $dt->modify('last day of this month');
            $dt->modify("last {$dayName}");
        } else {
            return null;
        }

        // Verify the result is still within the current month
        if ((int) $dt->format('m') !== $this->month) {
            return null;
        }

        return $dt->format('Y-m-d');
    }

    // ========================================================================
    // Recurring Events
    // ========================================================================

    /**
     * Generate recurring dates within the current month based on a DateInterval.
     *
     * @param string $startDate   Start date (Y-m-d). If before month start, the first hit within the month is used.
     * @param string $intervalSpec ISO 8601 interval (e.g. 'P7D' for weekly, 'P1M' for monthly).
     * @return array<string> Matching dates in 'Y-m-d' format.
     */
    public function getRecurringDatesInMonth(string $startDate, string $intervalSpec): array
    {
        $monthStart = new DateTime(sprintf('%04d-%02d-01', $this->year, $this->month));
        $monthEnd = (clone $monthStart)->modify('last day of this month')->setTime(23, 59, 59);
        $start = new DateTime($startDate);
        $interval = new DateInterval($intervalSpec);

        $period = new DatePeriod($start, $interval, $monthEnd);
        $results = [];

        foreach ($period as $date) {
            if ($date >= $monthStart && $date <= $monthEnd) {
                $results[] = $date->format('Y-m-d');
            }
        }

        return $results;
    }

    // ========================================================================
    // Astronomical & Special Dates
    // ========================================================================

    /**
     * Approximate the lunar phase for the current calendar date.
     *
     * Uses the simple synodic-period method (accuracy ~1 day).
     * Reference new moon: 2000-01-06 18:14 UTC (JD 2451550.1).
     *
     * @return array{phase: string, age: float, illumination: float}
     *   - phase:        One of 'New Moon','Waxing Crescent','First Quarter','Waxing Gibbous',
     *                   'Full Moon','Waning Gibbous','Last Quarter','Waning Crescent'
     *   - age:          Days since last new moon (0-29.53)
     *   - illumination: Approximate illumination fraction (0.0-1.0)
     */
    public function getLunarPhase(): array
    {
        $synodicMonth = 29.53058770576;
        $referenceNewMoon = mktime(18, 14, 0, 1, 6, 2000);
        $current = mktime(12, 0, 0, $this->month, $this->day, $this->year);

        $daysSinceRef = ($current - $referenceNewMoon) / 86400;
        $age = fmod($daysSinceRef, $synodicMonth);
        if ($age < 0) {
            $age += $synodicMonth;
        }

        $illumination = (1 - cos(2 * M_PI * $age / $synodicMonth)) / 2;

        $phaseIndex = (int) floor(($age / $synodicMonth) * 8) % 8;
        $phaseNames = [
            'New Moon',
            'Waxing Crescent',
            'First Quarter',
            'Waxing Gibbous',
            'Full Moon',
            'Waning Gibbous',
            'Last Quarter',
            'Waning Crescent',
        ];

        return [
            'phase' => $phaseNames[$phaseIndex],
            'age' => round($age, 2),
            'illumination' => round($illumination, 4),
        ];
    }

    /**
     * Compute Easter Sunday for the current year using the Anonymous Gregorian algorithm.
     *
     * @return string 'Y-m-d'
     */
    public function getEasterDate(): string
    {
        $y = $this->year;
        $a = $y % 19;
        $b = intdiv($y, 100);
        $c = $y % 100;
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

        return sprintf('%04d-%02d-%02d', $y, $month, $day);
    }

    /**
     * Get the approximate sunrise and sunset times for the current date.
     *
     * Uses PHP's built-in date_sun_info(). Requires latitude and longitude.
     *
     * @param float  $latitude  Latitude in decimal degrees.
     * @param float  $longitude Longitude in decimal degrees.
     * @param string $format    Time format (default 'H:i:s').
     * @return array{sunrise: string, sunset: string, daylight_hours: float}
     */
    public function getSunTimes(float $latitude, float $longitude, string $format = 'H:i:s'): array
    {
        $ts = mktime(12, 0, 0, $this->month, $this->day, $this->year);
        $info = date_sun_info($ts, $latitude, $longitude);

        $sunrise = is_int($info['sunrise']) ? date($format, $info['sunrise']) : 'N/A';
        $sunset = is_int($info['sunset']) ? date($format, $info['sunset']) : 'N/A';
        $daylight = (is_int($info['sunrise']) && is_int($info['sunset']))
            ? round(($info['sunset'] - $info['sunrise']) / 3600, 2)
            : 0.0;

        return [
            'sunrise' => $sunrise,
            'sunset' => $sunset,
            'daylight_hours' => $daylight,
        ];
    }

    // ========================================================================
    // Holiday Helpers
    // ========================================================================

    /**
     * Check whether a specific date in the current month is present in a holiday list.
     *
     * @param int            $day      Day of the month.
     * @param array<string>  $holidays Array of 'Y-m-d' strings.
     * @return bool
     */
    public function isHoliday(int $day, array $holidays): bool
    {
        $date = sprintf('%04d-%02d-%02d', $this->year, $this->month, $day);
        return in_array($date, $holidays, true);
    }

    /**
     * Filter a holiday list to only those falling within the current month.
     *
     * @param array<string> $holidays Array of 'Y-m-d' strings.
     * @return array<string>
     */
    public function getHolidaysInMonth(array $holidays): array
    {
        $prefix = sprintf('%04d-%02d-', $this->year, $this->month);
        return array_values(array_filter($holidays, fn(string $d) => str_starts_with($d, $prefix)));
    }

    /**
     * Count the number of business days (Mon-Fri, excluding holidays) in the current month.
     *
     * @param array<string> $holidays Array of 'Y-m-d' holiday strings to exclude.
     * @return int
     */
    public function getBusinessDaysInMonth(array $holidays = []): int
    {
        $count = 0;
        for ($d = 1; $d <= $this->lastDay; $d++) {
            $ts = mktime(0, 0, 0, $this->month, $d, $this->year);
            $dow = (int) date('N', $ts);
            $dateStr = date('Y-m-d', $ts);
            if ($dow < 6 && !in_array($dateStr, $holidays, true)) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Get the next business day after the current date.
     *
     * @param array<string> $holidays Array of 'Y-m-d' holiday strings to skip.
     * @return string 'Y-m-d'
     */
    public function getNextBusinessDay(array $holidays = []): string
    {
        $dt = $this->toDateTime();
        do {
            $dt->modify('+1 day');
            $dow = (int) $dt->format('N');
        } while ($dow >= 6 || in_array($dt->format('Y-m-d'), $holidays, true));

        return $dt->format('Y-m-d');
    }

    /**
     * Get the previous business day before the current date.
     *
     * @param array<string> $holidays Array of 'Y-m-d' holiday strings to skip.
     * @return string 'Y-m-d'
     */
    public function getPreviousBusinessDay(array $holidays = []): string
    {
        $dt = $this->toDateTime();
        do {
            $dt->modify('-1 day');
            $dow = (int) $dt->format('N');
        } while ($dow >= 6 || in_array($dt->format('Y-m-d'), $holidays, true));

        return $dt->format('Y-m-d');
    }

    // ========================================================================
    // Serialization
    // ========================================================================

    /**
     * Export the calendar's internal state as an associative array.
     *
     * @return array{
     *  year: int, 
     *  month: int, 
     *  day: int, 
     *  monthName: string, 
     *  lastDay: int,
     *  firstDayOfWeek: int, 
     *  quarter: int, 
     *  isLeapYear: bool, 
     *  isoWeek: int,
     *  dayOfYear: int, 
     *  weekCount: int
     * }
     */
    public function toArray(): array
    {
        return [
            'year' => $this->year,
            'month' => $this->month,
            'day' => $this->day,
            'monthName' => $this->textureNameOfMonth,
            'lastDay' => $this->lastDay,
            'firstDayOfWeek' => $this->firstDayOfWeek,
            'quarter' => $this->getQuarter(),
            'isLeapYear' => $this->isLeapYear(),
            'isoWeek' => $this->getISOWeekNumber(),
            'dayOfYear' => $this->getDayOfYear(),
            'weekCount' => $this->getWeekCountInMonth(),
        ];
    }

    /**
     * Serialize the calendar state to JSON.
     *
     * @param int $flags json_encode flags.
     * @return string
     */
    public function toJSON(int $flags = 0): string
    {
        return json_encode($this->toArray(), $flags | JSON_THROW_ON_ERROR);
    }

    /**
     * Format the current date using a custom format string.
     *
     * @param string $format Any PHP date() format string.
     * @return string
     */
    public function format(string $format): string
    {
        return $this->toDateTime()->format($format);
    }

    /**
     * Human-readable representation (e.g. "February 2026").
     *
     * @return string
     */
    public function __toString(): string
    {
        return "{$this->textureNameOfMonth} {$this->year}";
    }
}
