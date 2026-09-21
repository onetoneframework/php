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
 * Enumeration class for JSON error messages.
 */
abstract class JsonErrorMessage
{
    public const JSON_ERROR_CTRL_CHAR      = "Unexpected control character found";
    public const JSON_ERROR_DEPTH          = "Maximum stack depth exceeded";
    public const JSON_ERROR_STATE_MISMATCH = "Underflow or the modes mismatch";
    public const JSON_ERROR_SYNTAX         = "Syntax error, malformed JSON";
    public const JSON_ERROR_UNKNOWN        = "Unknown error";
    public const JSON_ERROR_UTF8           = "Malformed UTF-8 characters, possibly incorrectly encoded";
}
