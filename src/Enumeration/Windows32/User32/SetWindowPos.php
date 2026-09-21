<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration\Windows32\User32;

enum SetWindowPos: int
{
    case SWP_NOSIZE = 0x0001;
    case SWP_NOMOVE = 0x0002;
    case SWP_NOZORDER = 0x0004;
    case SWP_NOREDRAW = 0x0008;
    case SWP_NOACTIVATE = 0x0010;
    case SWP_FRAMECHANGED = 0x0020;
    case SWP_SHOWWINDOW = 0x0040;
    case SWP_HIDEWINDOW = 0x0080;
    case SWP_NOCOPYBITS = 0x0100;
    case SWP_NOOWNERZORDER = 0x0200;
    case SWP_NOSENDCHANGING = 0x0400;
}