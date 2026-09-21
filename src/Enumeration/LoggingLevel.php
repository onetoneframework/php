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
 * Logging Level Constants
 *
 * Defines standard logging level constants for the logging system.
 */
abstract class LoggingLevel
{
    public const ALERT = "alert";
    public const ASSERT = "assert";
    public const DEBUG = "debug";
    public const ERROR = "error";
    public const INFORMATION = "information";
    public const NOTICE = "notice";
    public const VERBOSE = "verbose";
    public const EMERGENCY = "emergency";
    public const CRITICAL = "critical";
    public const WARNING = "warning";
}
