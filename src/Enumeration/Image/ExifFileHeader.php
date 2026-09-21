<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration;

/**
 * Enumeration class for EXIF file headers.
 */
abstract class ExifFileHeader
{
    public const MAIN_IMAGE = '1FD0';

    public const THUMBNAIL_IMAGE = '1FD1';
}
