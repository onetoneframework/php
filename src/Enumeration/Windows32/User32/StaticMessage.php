<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration\Windows32\User32;

enum StaticMessage : int
{    
    case STM_SETIMAGE = 0x0172;
    case STM_GETIMAGE = 0x0173;
    case STM_MSGMAX = 0x0174;
}