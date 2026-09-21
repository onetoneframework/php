<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Enumeration;

/**
 * Month Enumeration
 */
enum Month: int
{
    case JANUARY = 1;
    case FEBRUARY = 2;
    case MARCH = 3;
    case APRIL = 4;
    case MAY = 5;
    case JUNE = 6;
    case JULY = 7;
    case AUGUST = 8;
    case SEPTEMBER = 9;
    case OCTOBER = 10;
    case NOVEMBER = 11;
    case DECEMBER = 12;

    public function getPrevious(): Month
    {
        return match ($this) {
            self::JANUARY => self::DECEMBER,
            self::FEBRUARY => self::JANUARY,
            self::MARCH => self::FEBRUARY,
            self::APRIL => self::MARCH,
            self::MAY => self::APRIL,
            self::JUNE => self::MAY,
            self::JULY => self::JUNE,
            self::AUGUST => self::JULY,
            self::SEPTEMBER => self::AUGUST,
            self::OCTOBER => self::SEPTEMBER,
            self::NOVEMBER => self::OCTOBER,
            self::DECEMBER => self::NOVEMBER,
        };
    }

    public function getNext(): Month
    {
        return match ($this) {
            self::JANUARY => self::FEBRUARY,
            self::FEBRUARY => self::MARCH,
            self::MARCH => self::APRIL,
            self::APRIL => self::MAY,
            self::MAY => self::JUNE,
            self::JUNE => self::JULY,
            self::JULY => self::AUGUST,
            self::AUGUST => self::SEPTEMBER,
            self::SEPTEMBER => self::OCTOBER,
            self::OCTOBER => self::NOVEMBER,
            self::NOVEMBER => self::DECEMBER,
            self::DECEMBER => self::JANUARY,
        };
    }

    public function getNonLeapYearDays(): int
    {
        return match ($this) {
            self::FEBRUARY => 28,
            self::APRIL, self::JUNE, self::SEPTEMBER, self::NOVEMBER => 30,
            self::JANUARY, self::MARCH, self::MAY, self::JULY, self::AUGUST, self::OCTOBER, self::DECEMBER => 31,
        };
    }

    public function getLeapYearDays(): int
    {
        return match ($this) {
            self::FEBRUARY => 29,
            self::APRIL, self::JUNE, self::SEPTEMBER, self::NOVEMBER => 30,
            self::JANUARY, self::MARCH, self::MAY, self::JULY, self::AUGUST, self::OCTOBER, self::DECEMBER => 31,
        };
    }
}