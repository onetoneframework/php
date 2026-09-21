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
 * Unicode Byte Enumeration
 */
abstract class UnicodeByte
{
    /**
     * U+200B
     * @var string
     */
    public const ZERO_WIDTH_SPACE = '\xe2\x80\x8b';
    /**
     * U+00A0
     * @var string
     */
    public const NON_BREAKING_SPACE = '\xc2\xa0';
    /**
     * U+200C
     * @var string
     */

    /**
     * U+200C
     * @var string
     */
    public const ZERO_WIDTH_NON_JOINER = '\xE2\x80\x8C';
    /**
     * U+200D
     * @var string
     */
    public const ZERO_WIDTH_JOINER = '\xE2\x80\x8D';
    /**
     * U+00AD	
     * @var string
     */
    public const SOFT_HYPHEN = '\xC2\xAD';
    /**
     * U+2009
     * @var string
     */
    public const THIN_SPACE = '\xE2\x80\x89';
    /**
     * U+200A
     * @var string
     */
    public const HAIR_SPACE = '\xE2\x80\x8A';
    /**
     * U+2003
     * @var string
     */
    public const EM_SPACE = '\xE2\x80\x83';
    /**
     * U+2002
     * @var string
     */
    public const EN_SPACE = '\xE2\x80\x82';
    /**
     * U+2007
     * @var string
     */
    public const FIGURE_SPACE = '\xE2\x80\x87';
    /**
     * U+2060
     * @var string
     */
    public const WORD_JOINER = '\xE2\x81\xA0';
}