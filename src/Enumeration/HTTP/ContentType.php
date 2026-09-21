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
 * Enumeration class for HTTP content types.
 */
abstract class ContentType
{
    public const APPLICATION_WWW_FORM_URLENCODED = 'application/x-www-form-urlencoded';
    public const MULTIPART_FORM_DATA = 'multipart/form-data';
    public const TEXT_PLAIN = 'text/plain';
}
