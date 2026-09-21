<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration\Windows32\Gdi32;

enum NotifyIconNotification: int
{
    /**
     * No icon in the balloon.
     */
    case NIIF_NONE = 0x00000000;
    /**
     * Show the standard “information” icon.
     */
    case NIIF_INFO = 0x00000001;
    /**
     * Show a yellow warning icon.
     */
    case NIIF_WARNING = 0x00000002;
    /**
     * Show a red error icon.
     */
    case NIIF_ERROR = 0x00000003;
    /**
     * Use a custom icon from hBalloonIcon (Vista+).
     */
    case NIIF_USER = 0x00000004;
    /**
     * Suppress the notification sound.
     */
    case NIIF_NOSOUND = 0x00000010;
}