<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration\Windows32\Kernel;

enum StorageProperty : int
{
    case StorageDeviceProperty = 0;
    case StorageDeviceSeekPenaltyProperty = 7;
}