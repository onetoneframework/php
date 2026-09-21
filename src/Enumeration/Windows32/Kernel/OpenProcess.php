<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration\Windows32\Kernel;

enum OpenProcess : int
{
    case VM_READ = 0x0010;
    case VM_WRITE = 0x0020;
    case VM_OPERATION = 0x0008;
    case QUERY_INFORMATION = 0x0400;
    case ALL_ACCESS = 0x1F0FFF;
    case ERROR_INVALID_HANDLE = 6;
    case ERROR_ACCESS_DENIED = 5;
    case ERROR_PROCESS_NOT_FOUND = 1068;
}