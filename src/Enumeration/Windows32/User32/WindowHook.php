<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration\Windows32\User32;

abstract class WindowHook
{
    const WH_MIN = -1;
    const WH_MSGFILTER = -1;
    const WH_JOURNALRECORD = 0;
    const WH_JOURNALPLAYBACK = 1;
    const WH_KEYBOARD = 2;
    const WH_GETMESSAGE = 3; 
    const WH_CALLWNDPROC = 4;
    const WH_CBT = 5;
    const WH_SYSMSGFILTER = 6;
    const WH_MOUSE = 7;
    const WH_HARDWARE = 8;
    const WH_DEBUG = 9;
    const WH_SHELL = 10;
    const WH_FOREGROUNDIDLE = 11;
    const WH_MAX = 12;
    const WH_KEYBOARD_LL = 13;
    const WH_MOUSE_LL = 14;
}
