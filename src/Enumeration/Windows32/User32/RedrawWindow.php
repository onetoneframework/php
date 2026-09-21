<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration\Windows32\User32;

enum RedrawWindow: int
{
    case RDW_INVALIDATE = 0x0001;
    case RDW_UPDATENOW = 0x0002;
    case RDW_ERASE = 0x0020;
}