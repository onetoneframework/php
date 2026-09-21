<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration\Windows32\Dialog;

enum CommonConstraints : int
{
    case ERROR_NO_MORE_ITEMS = 259;
    case ERROR_INSUFFICIENT_BUFFER = 122;
    case INVALID_HANDLE_VALUE = -1;
    case FORMAT_MESSAGE_FROM_SYSTEM = 0x00001000;
    case GENERIC_READ = 0x80000000;
    case FILE_SHARE_READ = 1;
    case FILE_SHARE_WRITE = 0x00000002;
    case OPEN_EXISTING = 3;
    case IOCTL_STORAGE_QUERY_PROPERTY = 0x002D1400;
}