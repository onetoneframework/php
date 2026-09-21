<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration\Windows32\Dialog;

enum ProtectionFlag : int
{
    case RO = 0x02;
    case RW = 0x04;
    case WC = 0x08;
    case XR = 0x20;
    case XRW = 0x40;
    case XRC = 0x80;
}
