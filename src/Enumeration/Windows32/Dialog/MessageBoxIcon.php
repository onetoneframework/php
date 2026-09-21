<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration\Windows32\Dialog;

enum MessageBoxIcon : int
{
    case WARNING = 0x00000030;
    case INFORMATION = 0x00000040;
    case QUESTION = 0x00000020;
    case ERROR = 0x00000010;
}