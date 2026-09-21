<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration\Windows32\Kernel;

enum PlaySound : int
{
    /**
     * Play from filename
     */
    case FILENAME = 0x00020000;
    /**
     * Play synchronously (block until finished)
     */
    case ASYN = 0x00000000;
    /**
     * Play asynchronously (non-blocking)
     */
    case ASYNC = 0x00000001;
    /**
     * Don't play the default sound if file not found
     */
    case NODEFAULT = 0x00000002;
    /**
     * Loop the sound until StopSound called
     */
    case LOOP = 0x00000008;
    /**
     * Play from memory instead of file
     */
    case MEMORY = 0x0004;
    /**
     * pszSound is a resource identifier
     */
    case RESOURCE = 0x00040004;
    /**
     * Stop all playing sounds for the calling task
     */
    case PURGE = 0x0040;
    /**
     * Return immediately if sound driver is busy
     */
    case NOWAIT = 0x00002000;
}