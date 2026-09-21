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

abstract class UploadedFile
{
    use PrototypeTrait;
    
    public const ERROR = 'error';
    public const FULL_PATH = 'full_path';
    public const FULLY_DATA = 'fully_data';
    public const NAME = 'name';
    public const SIZE = 'size';
    public const TEMPORARY_NAME = 'tmp_name';
    public const TYPE = 'type';
}
