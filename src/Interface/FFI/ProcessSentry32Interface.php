<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Interface\FFI;

abstract class ProcessSentry32Interface
{
    public $th32ProcessID;
    public $cntThreads;
    public $th32ParentProcessID;
    public $pcPriClassBase;
    public $szExeFile;
}