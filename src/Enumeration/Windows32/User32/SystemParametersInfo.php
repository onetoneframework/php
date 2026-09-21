<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration\Windows32\User32;

enum SystemParametersInfo : int
{
    case UPDATEINIFILE = 0x01;
    case SENDWININICHANGE = 0x02;
    case SETDESKWALLPAPER = 0x0014;
    case SETDESKWALLPAPER_UNICODE = 0x0095;
}