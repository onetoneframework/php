<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration\Windows32\User32;

enum KeyboardEvent : int
{
    case KEYDOWN = 0x0000;
    case EXTENDEDKEY = 0x0001;
    case KEYUP = 0x0002;
    case VK_A = 0x41;
    case VK_LWIN = 0x5B;
}
