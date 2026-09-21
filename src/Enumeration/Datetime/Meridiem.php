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
 * Meridiem Enumeration
 */
enum Meridiem: string
{
    case ANTE_MERIDIEM = 'AM';
    case POST_MERIDIEM = 'PM';

    public static function fromHour(int $hour): Meridiem
    {
        return $hour < 12 ? self::ANTE_MERIDIEM : self::POST_MERIDIEM;
    }

    public function toggle(): Meridiem
    {
        return $this === self::ANTE_MERIDIEM ? self::POST_MERIDIEM : self::ANTE_MERIDIEM;
    }
}