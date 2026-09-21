<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration\Windows32\User32;

enum LoadResource : int
{
    /**
     * Load the image from a file path instead of from a resource
     */
    case LR_LOADFROMFILE = 0x00000010;
    /**
     * Use the system metric for icon/cursor dimensions if cx/cy are zero
     */
    case LR_DEFAULTSIZE = 0x00000040;
    /**
     * Create a monochrome (1-bit) bitmap
     */
    case LR_MONOCHROME = 0x00000001;
    /**
     * Return a device-independent bitmap (DIB) section
     */
    case LR_CREATEDIBSECTION = 0x00002000;
    /**
     * Share the loaded image handle if it already exists in memory
     */
    case LR_SHARED = 0x00008000;
}