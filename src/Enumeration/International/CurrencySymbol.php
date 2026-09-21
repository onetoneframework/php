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
 * Enumeration class for currency symbols.
 */
abstract class CurrencySymbol
{
    public const AED = 'د.إ';     // UAE Dirham
    public const AFN = '؋';       // Afghan Afghani
    public const ALL = 'L';       // Albanian Lek
    public const AMD = '֏';       // Armenian Dram
    public const AOA = 'Kz';      // Angolan Kwanza
    public const ARS = '$';       // Argentine Peso
    public const AUD = 'A$';      // Australian Dollar
    public const AWG = 'ƒ';       // Aruban Florin
    public const AZN = '₼';       // Azerbaijani Manat
    public const BAM = 'KM';      // Bosnian Convertible Mark
    public const BBD = '$';       // Barbadian Dollar
    public const BDT = '৳';       // Bangladeshi Taka
    public const BGN = 'лв';      // Bulgarian Lev
    public const BHD = '.د.ب';    // Bahraini Dinar
    public const BIF = 'FBu';     // Burundian Franc
    public const BMD = '$';       // Bermudian Dollar
    public const BND = '$';       // Brunei Dollar
    public const BOB = 'Bs';      // Bolivian Boliviano
    public const BRL = 'R$';      // Brazilian Real
    public const BSD = '$';       // Bahamian Dollar
    public const BTN = 'Nu';      // Bhutanese Ngultrum
    public const BWP = 'P';       // Botswana Pula
    public const BYN = 'Br';      // Belarusian Ruble
    public const BZD = '$';       // Belize Dollar
    public const CAD = 'C$';      // Canadian Dollar
    public const CDF = 'FC';      // Congolese Franc
    public const CHF = 'CHF';     // Swiss Franc
    public const CLP = '$';       // Chilean Peso
    public const CNY = '¥';       // Chinese Yuan
    public const COP = '$';       // Colombian Peso
    public const CRC = '₡';       // Costa Rican Colón
    public const CUP = '$';       // Cuban Peso
    public const CVE = '$';       // Cape Verdean Escudo
    public const CZK = 'Kč';      // Czech Koruna
    public const DJF = 'Fdj';     // Djiboutian Franc
    public const DKK = 'kr';      // Danish Krone
    public const DOP = 'RD$';     // Dominican Peso
    public const DZD = 'دج';      // Algerian Dinar
    public const EGP = '£';       // Egyptian Pound
    public const ERN = 'Nfk';     // Eritrean Nakfa
    public const ETB = 'Br';      // Ethiopian Birr
    public const EUR = '€';       // Euro
    public const FJD = '$';       // Fijian Dollar
    public const FKP = '£';       // Falkland Islands Pound
    public const GBP = '£';       // British Pound
    public const GEL = '₾';       // Georgian Lari
    public const GHS = '₵';       // Ghanaian Cedi
    public const GIP = '£';       // Gibraltar Pound
    public const GMD = 'D';       // Gambian Dalasi
    public const GNF = 'FG';      // Guinean Franc
    public const GTQ = 'Q';       // Guatemalan Quetzal
    public const GYD = '$';       // Guyanese Dollar
    public const HKD = 'HK$';     // Hong Kong Dollar
    public const HNL = 'L';       // Honduran Lempira
    public const HTG = 'G';       // Haitian Gourde
    public const HUF = 'Ft';      // Hungarian Forint
    public const IDR = 'Rp';      // Indonesian Rupiah
    public const ILS = '₪';       // Israeli Shekel
    public const INR = '₹';       // Indian Rupee
    public const IQD = 'ع.د';     // Iraqi Dinar
    public const IRR = '﷼';       // Iranian Rial
    public const ISK = 'kr';      // Icelandic Krona
    public const JMD = 'J$';      // Jamaican Dollar
    public const JOD = 'د.ا';     // Jordanian Dinar
    public const JPY = '¥';       // Japanese Yen
    public const KES = 'KSh';     // Kenyan Shilling
    public const KGS = 'лв';      // Kyrgyzstani Som
    public const KHR = '៛';       // Cambodian Riel
    public const KMF = 'CF';      // Comorian Franc
    public const KPW = '₩';       // North Korean Won
    public const KRW = '₩';       // South Korean Won
    public const KWD = 'د.ك';     // Kuwaiti Dinar
    public const KYD = '$';       // Cayman Islands Dollar
    public const KZT = '₸';       // Kazakhstani Tenge
    public const LAK = '₭';       // Lao Kip
    public const LBP = 'ل.ل';     // Lebanese Pound
    public const LKR = 'Rs';      // Sri Lankan Rupee
    public const LRD = '$';       // Liberian Dollar
    public const LSL = 'L';       // Lesotho Loti
    public const LYD = 'ل.د';     // Libyan Dinar
    public const MAD = 'د.م.';    // Moroccan Dirham
    public const MDL = 'L';       // Moldovan Leu
    public const MGA = 'Ar';      // Malagasy Ariary
    public const MKD = 'ден';     // Macedonian Denar
    public const MMK = 'K';       // Myanmar Kyat
    public const MNT = '₮';       // Mongolian Tögrög
    public const MOP = 'P';       // Macanese Pataca
    public const MRU = 'UM';      // Mauritanian Ouguiya
    public const MUR = '₨';       // Mauritian Rupee
    public const MVR = 'Rf';      // Maldivian Rufiyaa
    public const MWK = 'MK';      // Malawian Kwacha
    public const MXN = '$';       // Mexican Peso
    public const MYR = 'RM';      // Malaysian Ringgit
    public const MZN = 'MT';      // Mozambican Metical
    public const NAD = '$';       // Namibian Dollar
    public const NGN = '₦';       // Nigerian Naira
    public const NIO = 'C$';      // Nicaraguan Córdoba
    public const NOK = 'kr';      // Norwegian Krone
    public const NPR = '₨';       // Nepalese Rupee
    public const NZD = 'NZ$';     // New Zealand Dollar
    public const OMR = 'ر.ع.';    // Omani Rial
    public const PAB = 'B/.';     // Panamanian Balboa
    public const PEN = 'S/';      // Peruvian Sol
    public const PGK = 'K';       // Papua New Guinean Kina
    public const PHP = '₱';       // Philippine Peso
    public const PKR = '₨';       // Pakistani Rupee
    public const PLN = 'zł';      // Polish Zloty
    public const PYG = '₲';       // Paraguayan Guarani
    public const QAR = 'ر.ق';     // Qatari Riyal
    public const RON = 'lei';     // Romanian Leu
    public const RSD = 'дин';     // Serbian Dinar
    public const RUB = '₽';       // Russian Ruble
    public const RWF = 'FRw';     // Rwandan Franc
    public const SAR = 'ر.س';     // Saudi Riyal
    public const SBD = '$';       // Solomon Islands Dollar
    public const SCR = '₨';       // Seychellois Rupee
    public const SDG = 'ج.س';     // Sudanese Pound
    public const SEK = 'kr';      // Swedish Krona
    public const SGD = 'S$';      // Singapore Dollar
    public const SHP = '£';       // Saint Helena Pound
    public const SLE = 'Le';      // Sierra Leonean Leone
    public const SOS = 'Sh';      // Somali Shilling
    public const SRD = '$';       // Surinamese Dollar
    public const SSP = '£';       // South Sudanese Pound
    public const STN = 'Db';      // São Tomé and Príncipe Dobra
    public const SVC = '$';       // Salvadoran Colón
    public const SYP = '£';       // Syrian Pound
    public const SZL = 'L';       // Swazi Lilangeni
    public const THB = '฿';       // Thai Baht
    public const TJS = 'ЅМ';      // Tajikistani Somoni
    public const TMT = 'm';       // Turkmenistani Manat
    public const TND = 'د.ت';     // Tunisian Dinar
    public const TOP = 'T$';       // Tongan Paʻanga
    public const TRY = '₺';        // Turkish Lira
    public const TTD = 'TT$';      // Trinidad and Tobago Dollar
    public const TWD = 'NT$';      // New Taiwan Dollar
    public const TZS = 'TSh';      // Tanzanian Shilling
    public const UAH = '₴';        // Ukrainian Hryvnia
    public const UGX = 'USh';      // Ugandan Shilling
    public const USD = '$';        // US Dollar
    public const UYU = '$U';       // Uruguayan Peso
    public const UYW = 'UYW';      // Uruguayan Nominal Wage Index Unit (no symbol)
    public const UZS = 'soʻm';     // Uzbekistani Som
    public const VED = 'Bs.D';     // Venezuelan Digital Bolívar
    public const VES = 'Bs.S';     // Venezuelan Sovereign Bolívar
    public const VND = '₫';        // Vietnamese Dong
    public const VUV = 'VT';       // Vanuatu Vatu
    public const WST = 'WS$';      // Samoan Tala
    public const XAF = 'FCFA';     // Central African CFA Franc
    public const XCD = 'EC$';      // East Caribbean Dollar
    public const XCG = 'XCG';      // (Custom or unknown currency)
    public const XOF = 'CFA';      // West African CFA Franc
    public const XPF = '₣';        // CFP Franc
    public const YER = '﷼';        // Yemeni Rial
    public const ZAR = 'R';        // South African Rand
    public const ZMW = 'ZK';       // Zambian Kwacha
    public const ZWG = 'ZWG';      // Zimbabwe Gold (no official symbol)
}