<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration\Windows32\Kernel;

enum SmartFlags : int
{
    case SMART_FEATURE_CODE = 0xD0;
    case SMART_LBA_MID = 0x4F;
    case SMART_LBA_HIGH = 0xC2;
}