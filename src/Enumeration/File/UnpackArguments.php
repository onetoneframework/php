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

/**
 * Unpack Arguments Enumeration
 */
abstract class UnpackArguments
{
    use PrototypeTrait;
    
    public const SIGNED_CHARACTER = 'c';
    public const UNSIGNED_CHARACTER = 'C';
    public const SIGNED_SHORT = 's';
    public const UNSIGNED_SHORT = 'S';
    public const BIG_ENDIAN_UNSIGNED_SHORT = 'n';    
    public const LITTLE_ENDIAN_UNSIGNED_SHORT = 'v';
    public const SIGNED_INTEGER = 'i';
    public const UNSIGNED_INTEGER = 'I';
    public const SIGNED_LONG = 'l';
    public const UNSIGNED_LONG = 'L';
    public const BIG_ENDIAN_UNSIGNED_LONG = 'N';
    public const LITTLE_ENDIAN_UNSIGNED_LONG = 'V';
    public const SIGNED_LONGLONG = 'q';
    public const UNSIGNED_LONGLONG = 'Q';
    public const BIG_ENDIAN_UNSIGNED_LONGLONG = 'J';
    public const LITTLE_ENDIAN_UNSIGNED_LONGLONG = 'P';
    public const FLOAT = 'f';
    public const DOUBLE = 'd';

    /**
     * IEEE 754
     */
    public const BIG_ENDIAN_FLOAT = 'G';
    /**
     * IEEE 754
     */
    public const LITTLE_ENDIAN_FLOAT = 'g';
    /**
     * IEEE 754
     */
    public const LITTLE_ENDIAN_DOUBLE = 'e';
    public const NULL_BYTE = 'x';
    public const SEEK = '@';
    public const NULL_PADDED = 'a';
    public const SPACE_PADDED = 'A';
}
