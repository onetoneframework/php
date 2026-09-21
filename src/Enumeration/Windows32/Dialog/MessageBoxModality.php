<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration\Windows32\Dialog;

enum MessageBoxModality : int
{
    case APPLICATION = 0x00000000;
    case SYSTEM = 0x00001000;
    case TASK = 0x00002000;
}