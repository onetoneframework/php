<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration\Windows32\Gdi32;

enum NotifyIconField : int
{
    case NIF_MESSAGE = 0x00000001;
    case NIF_ICON = 0x00000002;
    case NIF_TIP = 0x00000004;
    case NIF_INFO = 0x00000010;
}