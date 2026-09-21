<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration\Windows32\Comdlg32;

enum GetOpenFileName : int
{
    case EXPLORER = 0x00080000;
    case FILEMUSTEXIST = 0x00001000;
    case PATHMUSTEXIST = 0x00000800;
    case HIDEREADONLY = 0x00000004;
    case READONLY = 0x00000001;
    case ALLOWMULTISELECT = 0x00000200;
    case NOCHANGEDIR = 0x00000008;
    case ENABLEHOOK = 0x00000020;
    case ENABLETEMPLATE = 0x00000040;
    case ENABLETEMPLATEHANDLE = 0x00008000;
    case CREATEPROMPT = 0x00002000;
}