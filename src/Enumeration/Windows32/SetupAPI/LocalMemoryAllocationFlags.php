<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration\Windows32\SetupAPI;

enum LocalMemoryAllocationFlags : int
{
    case LMEM_FIXED = 0x0000;
}