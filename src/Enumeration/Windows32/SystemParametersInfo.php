<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration\Windows32\Dialog;

enum SystemParametersInfo : int
{
    case SETSCREENSAVEACTIVE = 0x0011;
    case GETSCREENSAVEACTIVE = 0x0010;
    case UPDATEINIFILE = 0x01;
}