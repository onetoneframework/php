<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Interface\FFI;

abstract class MemoryStatusExInterface
{
    public $dwMemoryLoad;
    public $ullTotalPhys;
    public $ullAvailPhys;
    public $ullTotalPageFile;
    public $ullAvailPageFile;
    public $ullTotalVirtual;
    public $ullAvailVirtual;
}