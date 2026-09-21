<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration\Windows32\User32;

enum FlashWindowEx : int
{
    case STOP = 0;
    /**
     * Flash the window caption
     */
    case CAPTION = 0x00000001;
    /**
     * Flash the taskbar button
     */
    case TRAY = 0x00000002;
    case TIMER = 0x00000004;
    /**
     * Flash continuously until Stop or window comes to the foreground
     */
    case TIMERNOFG = 0x0000000C;
    /**
     * Flash both
     */
    const FLASHW_ALL = (self::CAPTION | self::TRAY);

}