<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration\Windows32\User32;

enum LayeredWindowAttribute : int
{
    case COLORKEY = 0x00000001;
    case ALPHA = 0x00000002;
}