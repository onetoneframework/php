<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration\Windows32\Gdi32;

enum NotifyIconMessage : int
{
    case NIM_ADD = 0x00000000;
    case NIM_MODIFY = 0x00000001;
}