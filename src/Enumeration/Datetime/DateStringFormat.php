<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Enumeration\DateTime;

enum DateStringFormat: string
{
    // Day
    case DayOfMonthWithLeadingZeros = 'd'; // 01 to 31
    case DayTextualThreeLetters = 'D'; // Mon through Sun
    case DayOfMonthWithoutLeadingZeros = 'j'; // 1 to 31
    case DayOfWeekFullTextual = 'l'; // Sunday through Saturday
    case DayOfWeekIso8601Numeric = 'N'; // 1 (Monday) through 7 (Sunday)
    case DayOfMonthOrdinalSuffix = 'S'; // st, nd, rd, th
    case DayOfWeekNumeric = 'w'; // 0 (Sunday) through 6 (Saturday)
    case DayOfYear = 'z'; // 0 through 365

    // Week
    case WeekNumberIso8601 = 'W'; // 42

    // Month
    case MonthFullTextual = 'F'; // January through December
    case MonthNumericWithLeadingZeros = 'm'; // 01 through 12
    case MonthShortThreeLetters = 'M'; // Jan through Dec
    case MonthNumericWithoutLeadingZeros = 'n'; // 1 through 12
    case MonthNumberOfDays = 't'; // 28 through 31

    // Year
    case YearIsLeapYear = 'L'; // 1 if leap year, 0 otherwise
    case YearIso8601WeekNumbering = 'o'; // 1999 or 2003
    case YearExpandedFullWithSign = 'X'; // -0055, +0787, +1999, +10191
    case YearExpandedIfRequired = 'x'; // -0055, 0787, 1999, +10191
    case YearFullNumeric = 'Y'; // -0055, 0787, 1999, 2003, 10191
    case YearTwoDigit = 'y'; // 99 or 03

    // Time
    case TimeMeridiemLowercase = 'a'; // am or pm
    case TimeMeridiemUppercase = 'A'; // AM or PM
    case TimeSwatchInternet = 'B'; // 000 through 999
    case TimeHour12WithoutLeadingZeros = 'g'; // 1 through 12
    case TimeHour24WithoutLeadingZeros = 'G'; // 0 through 23
    case TimeHour12WithLeadingZeros = 'h'; // 01 through 12
    case TimeHour24WithLeadingZeros = 'H'; // 00 through 23
    case TimeMinutesWithLeadingZeros = 'i'; // 00 to 59
    case TimeSecondsWithLeadingZeros = 's'; // 00 through 59
    case TimeMicroseconds = 'u'; // 654321
    case TimeMilliseconds = 'v'; // 654

    // Timezone
    case TimezoneIdentifier = 'e'; // UTC, GMT, Atlantic/Azores
    case TimezoneIsDaylightSaving = 'I'; // 1 if DST, 0 otherwise
    case TimezoneGmtOffsetNoColon = 'O'; // +0200
    case TimezoneGmtOffsetWithColon = 'P'; // +02:00
    case TimezoneGmtOffsetWithColonOrZ = 'p'; // Z or +02:00
    case TimezoneAbbreviation = 'T'; // EST, MDT, +05
    case TimezoneOffsetInSeconds = 'Z'; // -43200 through 50400

    // Full Date/Time
    case DateTimeIso8601 = 'c'; // 2004-02-12T15:19:21+00:00
    case DateTimeRfc2822 = 'r'; // Thu, 21 Dec 2000 16:01:07 +0200
    case DateTimeUnixEpochSeconds = 'U'; // seconds since 1970-01-01 00:00:00 GMT
}
