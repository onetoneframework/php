<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration\Windows32\User32;

enum EditStyle : int
{
    case ES_MULTILINE = 0x0004;
    case ES_AUTOVSCROLL = 0x0040;
}