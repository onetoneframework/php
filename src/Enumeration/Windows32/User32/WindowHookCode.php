<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration\Windows32\User32;

abstract class WindowHookCode
{
    const HC_ACTION = 0;
    const HC_GETNEXT = 1;
    const HC_SKIP = 2;
    const HC_NOREMOVE = 3;
    const HC_NOREM = 4;
    const HC_SYSMODALON = 4;
    const HC_SYSMODALOFF = 5;
}
