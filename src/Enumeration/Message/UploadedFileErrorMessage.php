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
 * Enumeration class for uploaded file error messages.
 */
abstract class UploadedFileErrorMessage
{
    public const UPLOAD_ERR_CANT_WRITE     = 'Failed to write file to disk. Introduced in PHP 5.1.0.';
    public const UPLOAD_ERR_EMPTY          = 'The uploaded file was empty.';
    public const UPLOAD_ERR_EXTENSION      = 'File upload is disabled.';
    public const UPLOAD_ERR_FORM_SIZE      = 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.';
    public const UPLOAD_ERR_INI_SIZE       = 'The uploaded file exceeds the upload_max_filesize directive in php.ini.';
    public const UPLOAD_ERR_NO_FILE        = 'No file was uploaded.';
    public const UPLOAD_ERR_NO_TMP_DIR     = 'Missing a temporary folder. Introduced in PHP 4.3.10 and PHP 5.0.3.';
    public const UPLOAD_ERR_OK             = 'There is no error, the file uploaded with success.';
    public const UPLOAD_ERR_PARTIAL        = 'The uploaded file was only partially uploaded.';
    public const UPLOAD_ERR_UNKNOWN        = 'File upload stopped by extension. Introduced in PHP 5.2.0.';
}
