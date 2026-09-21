<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration\Windows32\Dialog;

enum MessageBoxDefaultButton : int
{
    case BUTTON1 = 0x00000000;
    case BUTTON2 = 0x00000100;
    case BUTTON3 = 0x00000200;
    case BUTTON4 = 0x00000300;
}