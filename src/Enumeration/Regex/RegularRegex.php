<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration;

abstract class RegularRegex
{
    public const ALPHABET = '/^[A-Za-z]{1,}$/';
    public const ALPHABET_NUMBER = '/^[A-Za-z0-9]{1,}$/';
    public const AMERICAN_EXPRESS_CARD_NO = '/^3[47][0-9]{13}$/';
    public const BASE64 = '/^data:[^,]+,/';
    public const CSS_URL = '/url\("([^\)]+?\.([a-z0-9]{2,5}))/';
    public const DINNERS_CLUB_CARD_NO = '/^3(?:0[0-5]|[68][0-9])[0-9]{11}$/';
    public const DISCOVERY_CARD_NO = '/^6(?:011|5[0-9][0-9])[0-9]{12}$/';
    public const EMAIL = '/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})$/';
	public const ENGLISH_KOREAN = '/^[\x{AC00}-\x{D7A3}a-zA-Z]+$/u';
    public const HEX = '/^0x[0-9a-f_]++$/i';
	public const HIRAGANA = '/^[\x{3041}-\x{3096}]+$/u';
	public const JAPANESE = '/^[\x{3041}-\x{3096}\x{30A1}-\x{30F6}\x{30FC}\x{4E00}-\x{9FA0}]+$/u';
    public const JCB_CARD_NO = '/^(?:2131|1800|35\d{3})\d{11}$/';
	public const KANJI = '/^[\x{4E00}-\x{9FA0}]+$/u';
	public const KATAKANA = '/^[\x{30A1}-\x{30F6}\x{30FC}]+$/u';
	public const KOREAN = '/^[\x{AC00}-\x{D7A3}]+$/u';
    public const MACADDRESS = '/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/';
    public const MASTER_CARD_NO = '/^5[1-5][0-9]{14}$|^(222[1-9]|2[3-6]\d{2}|27[0-1]\d|2720)\d{12}$/';
    public const NUMBER = '/^[0-9]{1,}$/i';
    public const PHONE_NUMBER = '/^[0-9]{2,3}-[0-9]{3,4}-[0-9]{4}$/';
    public const VISA_CARD_NO = '/^4[0-9]{12}(?:[0-9]{3})?$/';
}
