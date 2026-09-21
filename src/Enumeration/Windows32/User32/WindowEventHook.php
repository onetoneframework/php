<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration\Windows32\User32;

abstract class WindowEventHook
{
    /**
     * no DLL injection required
     * @var int
     */
    const WINEVENT_OUTOFCONTEXT = 0x0000;
    const WINEVENT_SKIPOWNTHREAD = 0x0001;
    const WINEVENT_SKIPOWNPROCESS = 0x0002;
    const WINEVENT_INCONTEXT = 0x0004;
}
