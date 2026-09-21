<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration\Windows32\User32;

enum MouseEvent : int
{
    case LEFTDOWN = 0x0002;
    case LEFTUP = 0x0004;
    /**
     * Absolute coordinates (0-65535)
     */
    case ABSOLUTE = 0x8000;
    case MOVE = 0x0001;
    case MIDDLEDOWN = 0x0020;
    case MIDDLEUP = 0x0040;
    case RIGHTDOWN = 0x0008;
    case RIGHTUP = 0x0010;
    case WHEEL = 0x0800;
    case XDOWN = 0x0080;
    case XUP = 0x0100;
    case HWHEEL = 0x1000;
}