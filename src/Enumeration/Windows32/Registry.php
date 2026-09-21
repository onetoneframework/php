<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration\Windows32;

enum Registry : int
{    
    case HKEY_LOCAL_MACHINE = 0x80000002;
    case HKEY_CURRENT_USER = 0x80000001;
    case REG_SZ = 1;
    case REG_EXPAND_SZ = 2;
    case KEY_ALL_ACCESS = 0xF003F;
    case KEY_READ = 0x20019;
}