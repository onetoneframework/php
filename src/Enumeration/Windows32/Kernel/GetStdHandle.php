<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration\Windows32\Kernel;

enum GetStdHandle : int
{
    /**
     * Standard input handle (console input).
     * See: GetStdHandle(STD_INPUT_HANDLE)
     */
    case STD_INPUT_HANDLE = -10;

    /**
     * Standard output handle (console output).
     * See: GetStdHandle(STD_OUTPUT_HANDLE)
     */
    case STD_OUTPUT_HANDLE = -11;

    /**
     * Standard error handle (console error output).
     * See: GetStdHandle(STD_ERROR_HANDLE)
     */
    case STD_ERROR_HANDLE = -12;

    /**
     * Commonly used sentinel for invalid handles.
     * Matches INVALID_HANDLE_VALUE in Win32 (typically (HANDLE)-1).
     */
    case INVALID_HANDLE_VALUE = -1;

    /**
     * Explicit zero handle value (not returned by GetStdHandle,
     * but sometimes useful for comparisons).
     */
    case NULL_HANDLE = 0;
}
