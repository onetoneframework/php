<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration\Windows32\Dialog;

enum GetSystemMetrics : int
{
    case CXSCREEN = 0;
    case CYSCREEN = 1;
    case XVIRTUALSCREEN = 76;
    case YVIRTUALSCREEN = 77;
    case CXVIRTUALSCREEN = 78;
    case CYVIRTUALSCREEN = 79;
}