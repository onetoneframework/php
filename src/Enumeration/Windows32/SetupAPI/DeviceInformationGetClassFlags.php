<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration\Windows32\SetupAPI;

enum DeviceInformationGetClassFlags : int
{
    case DIGCF_PRESENT = 0x00000002;
    case DIGCF_ALLCLASSES = 0x00000004;
    case DIGCF_DEVICEINTERFACE = 0x00000010;
}