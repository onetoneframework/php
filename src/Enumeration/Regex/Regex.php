<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration;

abstract class Regex
{
    public const ALPHABET = 'alphabet';
    public const ALPHABET_NUMBER = 'alphabet_number';
    public const BASE64 = 'base64';
    public const EMAIL = 'email';
    public const HIRAGANA = 'hiragana';
    public const JAPANESE = 'japanese';
    public const KANJI = 'kanji';
    public const KATAKANA = 'katakana';
    public const KOREAN = 'korean';
    public const KOREAN_ENGLISH = 'korean_english';
    public const NUMBER = 'number';
    public const PHONE_NUMBER = 'phone_number';
}
