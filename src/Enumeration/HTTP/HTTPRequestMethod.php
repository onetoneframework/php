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
 * Enumeration class for HTTP request methods.
 */
abstract class HTTPRequestMethod
{
    public const CONNECT = 'CONNECT';
    public const DELETE = 'DELETE';
    public const GET = 'GET';
    public const HEAD = 'HEAD';
    public const OPTIONS = 'OPTIONS';
    public const PATCH = 'PATCH';
    public const POST = 'POST';
    public const PURGE = 'PURGE';
    public const PUT = 'PUT';
    public const TRACE = 'TRACE';
}
