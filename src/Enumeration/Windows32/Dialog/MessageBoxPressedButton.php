<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration\Windows32\Dialog;

enum MessageBoxPressedButton : int
{
    case ABORT = 3;
    case CANCEL = 2;
    case CONTINUE = 11;
    case IGNORE = 5;
    case NO = 7;
    case OK = 1;
    case RETRY = 4;
    case TRY_AGAIN = 10;
    case YES = 6;
}