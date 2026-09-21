<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration\Windows32\WinUser;

abstract class MessageBox
{
    const MB_OK = 0x00000000;
    const MB_OKCANCEL = 0x00000001;
    const MB_YESNO = 0x00000004;
    const MB_ICONINFORMATION = 0x00000040;
    const MB_ICONWARNING = 0x00000030;
    const MB_ICONERROR = 0x00000010;
    const MB_ICONQUESTION = 0x00000020;
}
