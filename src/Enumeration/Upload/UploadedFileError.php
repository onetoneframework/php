<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration;

use Clover\Trait\PrototypeTrait;

abstract class UploadedFileError
{
    use PrototypeTrait;

    public const UPLOAD_FILE_IS_EMPTY = 'UPLOAD_FILE_IS_EMPTY';
    public const UPLOAD_IS_NOT_ALLOWED = 'UPLOAD_IS_NOT_ALLOWED';
}
