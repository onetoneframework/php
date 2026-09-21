<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Framework\Enumeration;

/**
 * Environment Enumeration
 */
enum Environment: string
{
    public const ALLOWED_SHORT_OPEN_TAG = 'allowed_short_open_tag';
    public const ALLOWED_UPLOAD_FILE = 'allowed_upload_file';
    public const BUILT_OPERATION_SYSTEM = 'built_operation_system';
    public const DISPLAY_ERRORS = 'display_errors';
    public const DISPLAY_STARTUP_ERRORS = 'display_startup_errors';
    public const ERROR_REPORTING_LEVEL = 'error_reporting_level';
    public const HOME_PATH = 'home_path';
    public const HYPERTEXT_PREPROCESSOR = 'hypertext_preprocessor';
    public const MAXIMUM_INTEGER_SIZE = 'maximum_integer_size';
    public const MAXIMUM_POST_SIZE = 'max_post_size';
    public const MAXIMUM_UPLOAD_FILE_SIZE = 'max_upload_file_size';
    public const SERVER = 'server';
    public const SESSION_USE_COOKIES = 'session_use_cookies';
    public const SOFTWARE = 'software';
    public const TIMEZONE_ID = 'timezone_id';
    public const VERSION = 'version';
}
