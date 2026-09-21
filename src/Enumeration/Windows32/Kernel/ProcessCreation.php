<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration\Windows32\Kernel;

enum ProcessCreation: int
{
    case CREATE_BREAKAWAY_FROM_JOB = 0x01000000;
    case CREATE_DEFAULT_ERROR_MODE = 0x04000000;
    case CREATE_NEW_CONSOLE = 0x00000010;
    case CREATE_NEW_PROCESS_GROUP = 0x00000200;
    case CREATE_NO_WINDOW = 0x08000000;
    case CREATE_PROTECTED_PROCESS = 0x00040000;
    case CREATE_PRESERVE_CODE_AUTHZ_LEVEL = 0x02000000;
    case CREATE_SEPARATE_WOW_VDM = 0x00000800;
    case CREATE_SHARED_WOW_VDM = 0x00001000;
    case CREATE_SUSPENDED = 0x00000004;
    case CREATE_UNICODE_ENVIRONMENT = 0x00000400;
    case DEBUG_ONLY_THIS_PROCESS = 0x00000002;
    case DEBUG_PROCESS = 0x00000001;
    case DETACHED_PROCESS = 0x00000008;
    case EXTENDED_STARTUPINFO_PRESENT = 0x00080000;
    case INHERIT_PARENT_AFFINITY = 0x00010000;
}
