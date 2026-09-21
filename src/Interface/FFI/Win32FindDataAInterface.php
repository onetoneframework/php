<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Interface\FFI;

abstract class Win32FindDataAInterface
{
    public $cFileName;
    public $dwFileAttributes;
    public $nFileSizeHigh;
    public $nFileSizeLow;
}