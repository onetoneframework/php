<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Enumeration\DateTime;

/**
 * Date Format Enumeration
 */
enum DateFormat: string
{
    case RFC2822 = 'EEE, dd MMM yyyy HH:mm:ss Z';
    case ISO8601 = "yyyy-MM-dd'T'HH:mm:ss.SSSXXX";
    case ISO8601_WITHOUT_MICROSECONDS = "yyyy-MM-dd'T'HH:mm:ssXXX";
    case HTTP = 'EEE, dd MMM yyyy HH:mm:ss zzz';
    case COOKIE = 'EEEE, dd-MMM-yyyy HH:mm:ss zzz';
    case SQL_DATE = 'yyyy-MM-dd';
    case SQL_DATE_TIME = 'yyyy-MM-dd HH:mm:ss';
    case XML_RPC = "yyyyMMdd'T'HH:mm:ss";
    case ISO_WEEK_DATE = 'Y-ww-E';
    case ISO_ORDINAL_DATE = 'yyyy-DDD';
    case JULIAN_DAY = 'yyyy DDD';
    case RFC3339 = "yyyy-MM-dd'T'HH:mm:ss.SSSZZZZZ";
    case RFC3339_WITHOUT_MICROSECONDS = "yyyy-MM-dd'T'HH:mm:ssZZZZZ";
    case UNIX_TIMESTAMP = 'U';
    case SIMPLE_DATE = 'dd/MM/yyyy';
    case AMERICAN = 'MM/dd/yyyy';
    case WEEKDAY_MONTH_DAY_YEAR = 'EEE, MMM dd, yyyy';
    case EMAIL = 'EEE, dd MMM yyyy HH:mm:ss ZZZZ';
    case LOG_TIMESTAMP = 'yyyy-MM-dd HH:mm:ss,SSS';
    case FULL_DATE_TIME = 'EEEE, MMMM dd, yyyy HH:mm:ss';
    case SHORT_DATE_WITH_TIME = 'd MMM y, HH:mm:ss';
    case JAVASCRIPT = "EEE MMM dd yyyy HH:mm:ss 'GMT'Z";

    public static function default(): static
    {
        return static::ISO8601;
    }
}