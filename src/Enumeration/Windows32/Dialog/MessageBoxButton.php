<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration\Windows32\Dialog;

enum MessageBoxButton : int
{
    case ABORT_RETRY_IGNORE = 0x00000002;
    case CANCEL_TRY_CONTINUE = 0x00000006;
    case HELP = 0x00004000;
    case OK = 0x00000000;
    case OK_CANCEL = 0x00000001;
    case RETRY_CANCEL = 0x00000005;
    case YES_NO = 0x00000004;
    case YES_NO_CANCEL = 0x00000003;
}