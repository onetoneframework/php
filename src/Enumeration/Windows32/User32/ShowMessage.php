<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration\Windows32\User32;

abstract class ShowMessage
{
    const SW_HIDE = 0;
    const SW_SHOWNORMAL = 1;
    const SW_SHOWMINIMIZED = 2;
    const SW_SHOWMAXIMIZED = 3;
    const SW_SHOW = 5;
    const SW_RESTORE = 9;
}
