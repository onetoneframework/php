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
 * Byte Order Mark Enumeration
 */
abstract class ByteOrderMark
{
    public const UTF16_BOM_BE = "\x00\x00\xfe\xff";
    public const UTF16_BOM_BE_WINDOWS_1252 = "\xfe\xff";
    public const UTF16_BOM_LE = "\xff\xfe";
    public const UTF16_BOM_LE_WINDOWS_1252 = 'ÿþ';
    public const UTF32_BOM_BE = "\x00\x00\xfe\xff";
    public const UTF32_BOM_BE_WINDOWS_1252 = '  þÿ';
    public const UTF8_BOM = "\xef\xbb\xbf";
    public const UTF8_BOM_WINDOWS_1252 = 'ï»¿';
}