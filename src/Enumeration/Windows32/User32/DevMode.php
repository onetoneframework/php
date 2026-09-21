<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration\Windows32\User32;

enum DevMode : int
{
    case DM_PELSWIDTH = 0x00080000;
    case DM_PELSHEIGHT = 0x00100000;

}