<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Interface\FFI;

abstract class SystemInfoInterface
{
    public $wProcessorArchitecture;
    public $dwNumberOfProcessors;
    public $dwProcessorType;
    public $dwPageSize;
    public $dwAllocationGranularity;
    public $wProcessorLevel;
    public $wProcessorRevision;
}