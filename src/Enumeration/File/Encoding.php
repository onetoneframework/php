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
 * Encoding Enumeration
 */
abstract class Encoding
{
    public const ASCII = 'ASCII';
    public const CP936 = 'CP936';
    public const EUC_JP = 'EUC-JP';
    public const EUC_KR = 'EUC-KR';
    public const HTML_ENTITIES = 'HTML-ENTITIES';
    public const JIS = 'JIS';
    public const KOI8_R = 'KOI8-R';
    public const UTF_16_BIG_ENDIAN = 'UTF-16BE';
    public const UTF_16_LITTLE_ENDIAN = 'UTF-16LE';
    public const UTF_32_BIG_ENDIAN = 'UTF-32BE';
    public const UTF_32_LITTLE_ENDIAN = 'UTF-32LE';
    public const UTF_7 = 'UTF-7';
    public const UTF_8 = 'UTF-8';
    public const WINDOWS_1251 = 'Windows-1251';
}
