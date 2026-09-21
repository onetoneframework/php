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
 * Enumeration class for HTTP character sets.
 */
abstract class CharacterSet
{
    public const UTF_8 = 'UTF-8';

    public const UTF_16_LE = 'UTF-16-LE';

    public const UTF_16_BE = 'UTF-16-BE';

    public const WINDOWS_1252 = 'WINDOWS_1252';

    public const ISO8859_1 = 'ISO8859-1';

    public const ISO8859_3 = 'ISO8859-3';

    public const ISO8859_15 = 'ISO8859-15';

    public const CP437 = 'CP437';

    /**
     * Korean
     */
    public const EUC_KR = 'EUC_KR';

    /**
     * Japanese
     */
    public const EUC_JP = 'EUC_JP';

    /**
     * Simplified Chinese
     */
    public const GBK = 'GBK';

    /**
     * Simplified Chinese
     */
    public const GB18030 = 'GB18030';

    /**
     * Simplified Chinese
     */
    public const GB2312 = 'GB2312';

    /**
     * Traditional Chinese
     */
    public const CP950 = 'CP950';

    /**
     * Traditional Chinese
     */
    public const BIG5_HKSCS = 'BIG5-HKSCS';

    /**
     * Japanese
     */
    public const SHIFT_JIS = 'SHIFT_JIS';

    /**
     * Nordic DOS
     */
    public const CP865 = 'CP865';

    /**
     * Thai
     */
    public const WINDOWS_874 = 'WINDOWS-874';

    /**
     * Latin/Thai
     */
    public const ISO8859_11 = 'ISO8859-11';

    /**
     * Cyrillic
     */
    public const KOI8_RU = 'KOI8-RU';

    /**
     * Tajik
     */
    public const KOI8_T = 'KOI8-T';

    /**
     * Western European DOS
     */
    public const CP850 = 'CP850';

    public const PLAIN = 'PLAIN';
}
