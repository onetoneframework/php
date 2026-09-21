<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Interface\FFI;

abstract class SystemTimeInterface
{
    public $wYear;
    public $wMonth;
    public $wDayOfWeek;
    public $wDay;
    public $wHour;
    public $wMinute;
    public $wSecond;
    public $wMilliseconds;
}