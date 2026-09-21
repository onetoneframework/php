<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration\Windows32\Dialog;

enum ShowWindows : int
{
    case SHOWNORMAL = 1;
    case MAXIMIZE = 3;
    case SHOWMINIMIZED = 2;
    case SHOW = 5;
    case RESTORE = 9;
}