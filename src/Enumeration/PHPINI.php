<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration;

abstract class PHPINI
{
    public const ALLOW_FILE_UPLOADS = 'file_uploads';
    public const ALLOW_INCLUDE_URL = 'allow_url_include';
    public const ALLOW_SHORT_OPEN_TAG = 'short_open_tag';
    public const ALLOW_URL_FILE_OPEN = 'allow_url_fopen';
    public const DEFAULT_CHARACTERSET = 'default_charset';
    public const DEFAULT_MIETYPE = 'default_mimetype';
    public const DEFAULT_SOCKET_TIMEOUT = 'default_socket_timeout';
    public const DEFAULT_USER_AGENT = 'user_agent';
    public const DISPLAY_ERRORS = 'display_errors';
    public const DISPLAY_STARTUP_ERRORS = 'display_startup_errors';
    public const ENABLE_POST_DATA_READING = 'enable_post_data_reading';
    public const ERROR_REPORTING_VALUES = 'error_reporting';
    public const IGNORE_REPEATED_ERRORS = 'ignore_repeated_errors';
    public const MAX_EXECUTION_TIME = 'max_execution_time';
    public const MAX_INPUT_VARIABLES = 'max_input_vars';
    public const MAX_MULTIPART_BODY_PARTS = 'max_multipart_body_parts';
    public const MAX_POST_DATA_SIZE = 'post_max_size';
    public const MAX_UPLOAD_FILE_NUMBER = 'max_file_uploads';
    public const MAX_UPLOAD_FILESIZE = 'upload_max_filesize';
    public const MEMORY_LIMIT = 'memory_limit';
    public const MULTIBYTE_STRING_INTERNAL_LANGUAGE = 'mbstring.language';
    public const REPORT_MEMORYLEAKS = 'report_memleaks';
    public const SESSION_AUTOMATIC_START = 'session.auto_start';
    public const SESSION_COOKIE_ADD_HTTPONLY_FLAG = 'session.cookie_httponly';
    public const SESSION_COOKIE_LIFETIME = 'session.cookie_lifetime';
    public const SESSION_COOKIE_NAME = 'session.name';
    public const SESSION_USE_COOKIES = 'session.use_cookies';
    public const UPLOAD_TEMPORARY_DIRECTORY = 'upload_tmp_dir';
}
