<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration\Windows32\User32;

enum StaticControl : int
{    
    case SS_LEFT = 0x00000000;
    case SS_CENTER = 0x00000001;
    case SS_RIGHT = 0x00000002;
    case SS_ICON = 0x00000003;
    case SS_BLACKFRAME = 0x00000007;
    case SS_BITMAP = 0x0000000E;
    case SS_NOTIFY = 0x00000040;
    case SS_REALSIZEIMAGE = 0x00200000;
    case SS_CENTERIMAGE = 0x00000200;
}