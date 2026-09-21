<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration\Windows32\User32;

enum SendMessage : int
{
    case LB_ADDSTRING = 0x0180;
    case LB_DELETESTRING = 0x0182;
    case LB_GETCURSEL = 0x0188;
    case LB_GETTEXT = 0x0189;
    case LBS_NOTIFY = 0x00000001;
    case LBS_SORT = 0x0002;
    case LBS_STANDARD = 0x00000001 | 0x000000A0;
}