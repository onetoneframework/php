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
 * Era Enumeration
 */
enum Era: string
{
    case ANNO_DOMINI = 'AD';
    case BEFORE_CHRIST = 'BC';

    public static function fromYear(int $year): Era
    {
        return $year > 0 ? self::ANNO_DOMINI : self::BEFORE_CHRIST;
    }

    public function toggle(): Era
    {
        return $this === self::ANNO_DOMINI ? self::BEFORE_CHRIST : self::ANNO_DOMINI;
    }
}