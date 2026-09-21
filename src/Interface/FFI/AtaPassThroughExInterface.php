<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Interface\FFI;

abstract class AtaPassThroughExInterface
{
    public $Length;
    public $AtaFlags;
    public $PathId;
    public $TargetId;
    public $Lun;
    public $ReservedAsUchar;
    public $DataTransferLength;
    public $TimeOutValue;
    public $ReservedAsUlong;
    public $DataBufferOffset;
    public $PreviousTaskFile;
    public $CurrentTaskFile;
}