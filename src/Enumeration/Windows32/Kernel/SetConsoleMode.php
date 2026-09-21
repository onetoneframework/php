<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration\Windows32\Kernel;

enum SetConsoleMode : int
{
    case ENABLE_ECHO_INPUT = 0x0004;
    case ENABLE_PROCESSED_INPUT = 0x0001;
    case ENABLE_WINDOW_INPUT = 0x0008;
}