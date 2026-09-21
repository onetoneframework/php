<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration\Windows32\SetupAPI;

enum SetupAPIDeviceRegistryProperty: int
{
    case SPDRP_DEVICEDESC = 0x00000000;
    case SPDRP_HARDWAREID = 0x00000001;
    case SPDRP_COMPATIBLEIDS = 0x00000002;
    case SPDRP_SERVICE = 0x00000004;
    case SPDRP_CLASS = 0x00000007;
    case SPDRP_CLASSGUID = 0x00000008;
    case SPDRP_DRIVER = 0x00000009;
    case SPDRP_CONFIGFLAGS = 0x0000000A;
    case SPDRP_MFG = 0x0000000B;
    case SPDRP_FRIENDLYNAME = 0x0000000C;
    case SPDRP_LOCATION_INFORMATION = 0x0000000D;
    case SPDRP_ENUMERATOR_NAME = 0x00000016;
}