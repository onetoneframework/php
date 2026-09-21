<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Barcode;

#region use

use Clover\Classes\BaseClass;
use Clover\Classes\OperationSystem;
use RuntimeException;
use InvalidArgumentException;
use Exception;
use function chr;
use function count;
use function in_array;
use function ord;
use function preg_match;
use function str_split;
use function strlen;
use function substr;

#endregion

/**
 * Class BarcodeHandler
 *
 * A comprehensive barcode generation library supporting multiple symbologies.
 *
 * Each encoder returns a binary string of '1' (bar) and '0' (space) characters
 * that can be rendered via the drawBarcode() helper into a PNG image.
 */
class BarcodeHandler extends BaseClass
{
    #region properties

    /**
     * Code 128 bar patterns indexed by printable character or special symbol.
     *
     * Each pattern is an 11-module string of '1's and '0's representing
     * alternating bars and spaces.  The table covers the full Code 128B
     * printable range (space 0x20 through tilde 0x7E) plus special control
     * symbols used by all three Code 128 subsets (A, B, C).
     *
     * @var array<string, string>
     */
    private static array $code128Patterns = [
        // Printable ASCII characters (Code 128B values 0-94)
        ' ' => '11011001100',  // SP   – value 0
        '!' => '11001101100',  // !    – value 1
        '"' => '11001100110',  // "    – value 2
        '#' => '10010011000',  // #    – value 3
        '$' => '10010001100',  // $    – value 4
        '%' => '10001001100',  // %    – value 5
        '&' => '10011001000',  // &    – value 6
        "'" => '10011000100',  // '    – value 7
        '(' => '10001100100',  // (    – value 8
        ')' => '11001001000',  // )    – value 9
        '*' => '11001000100',  // *    – value 10
        '+' => '11000100100',  // +    – value 11
        ',' => '10110011100',  // ,    – value 12
        '-' => '10011011100',  // -    – value 13
        '.' => '10011001110',  // .    – value 14
        '/' => '10111001100',  // /    – value 15
        '0' => '10011101100',  // 0    – value 16
        '1' => '10011100110',  // 1    – value 17
        '2' => '11001110010',  // 2    – value 18
        '3' => '11001011100',  // 3    – value 19
        '4' => '11001001110',  // 4    – value 20
        '5' => '11011100100',  // 5    – value 21
        '6' => '11001110100',  // 6    – value 22
        '7' => '11101101110',  // 7    – value 23
        '8' => '11101001100',  // 8    – value 24
        '9' => '11100101100',  // 9    – value 25
        ':' => '11100100110',  // :    – value 26
        ';' => '11101100100',  // ;    – value 27
        '<' => '11100110100',  // <    – value 28
        '=' => '11100110010',  // =    – value 29
        '>' => '11011011000',  // >    – value 30
        '?' => '11011000110',  // ?    – value 31
        '@' => '11000110110',  // @    – value 32
        'A' => '10100011000',  // A    – value 33
        'B' => '10001011000',  // B    – value 34
        'C' => '10001000110',  // C    – value 35
        'D' => '10110001000',  // D    – value 36
        'E' => '10001101000',  // E    – value 37
        'F' => '10001100010',  // F    – value 38
        'G' => '11010001000',  // G    – value 39
        'H' => '11000101000',  // H    – value 40
        'I' => '11000100010',  // I    – value 41
        'J' => '10110111000',  // J    – value 42
        'K' => '10110001110',  // K    – value 43
        'L' => '10001101110',  // L    – value 44
        'M' => '10111011000',  // M    – value 45
        'N' => '10111000110',  // N    – value 46
        'O' => '10001110110',  // O    – value 47
        'P' => '11101110110',  // P    – value 48
        'Q' => '11010001110',  // Q    – value 49
        'R' => '11000101110',  // R    – value 50
        'S' => '11011101000',  // S    – value 51
        'T' => '11011100010',  // T    – value 52
        'U' => '11011101110',  // U    – value 53
        'V' => '11101011000',  // V    – value 54
        'W' => '11101000110',  // W    – value 55
        'X' => '11100010110',  // X    – value 56
        'Y' => '11101101000',  // Y    – value 57
        'Z' => '11101100010',  // Z    – value 58
        '[' => '11100011010',  // [    – value 59
        '\\' => '11101111010',  // \    – value 60
        ']' => '11001000010',  // ]    – value 61
        '^' => '11110001010',  // ^    – value 62
        '_' => '10100110000',  // _    – value 63
        '`' => '10100001100',  // `    – value 64
        'a' => '10010110000',  // a    – value 65
        'b' => '10010000110',  // b    – value 66
        'c' => '10000101100',  // c    – value 67
        'd' => '10000100110',  // d    – value 68
        'e' => '10110010000',  // e    – value 69
        'f' => '10110000100',  // f    – value 70
        'g' => '10011010000',  // g    – value 71
        'h' => '10011000010',  // h    – value 72
        'i' => '10000110100',  // i    – value 73
        'j' => '10000110010',  // j    – value 74
        'k' => '11000010010',  // k    – value 75
        'l' => '11001010000',  // l    – value 76
        'm' => '11110111010',  // m    – value 77
        'n' => '11000010100',  // n    – value 78
        'o' => '10001111010',  // o    – value 79
        'p' => '10100111100',  // p    – value 80
        'q' => '10010111100',  // q    – value 81
        'r' => '10010011110',  // r    – value 82
        's' => '10111100100',  // s    – value 83
        't' => '10011110100',  // t    – value 84
        'u' => '10011110010',  // u    – value 85
        'v' => '11110100100',  // v    – value 86
        'w' => '11110010100',  // w    – value 87
        'x' => '11110010010',  // x    – value 88
        'y' => '11011011110',  // y    – value 89
        'z' => '11011110110',  // z    – value 90
        '{' => '11110110110',  // {    – value 91
        '|' => '10101111000',  // |    – value 92
        '}' => '10100011110',  // }    – value 93
        '~' => '10001011110',  // ~    – value 94

        // Special / control symbols
        'DEL' => '10111101000',  // DEL    – value 95
        'FNC 3' => '10111100010',  // FNC 3  – value 96
        'FNC 2' => '11110101000',  // FNC 2  – value 97
        'SHIFT' => '11110100010',  // SHIFT  – value 98
        'CODE C' => '10111011110',  // Switch to Code C – value 99
        'CODE B' => '10111101110',  // Switch to Code B – value 100
        'CODE A' => '11101011110',  // Switch to Code A – value 101
        'FNC 1' => '11110101110',  // FNC 1  – value 102

        // Start / Stop symbols
        'Start A' => '11010000100',  // Start Code A – start value 103
        'Start B' => '11010010000',  // Start Code B – start value 104
        'Start C' => '11010011100',  // Start Code C – start value 105
        'Stop' => '11000111010',  // Stop symbol (followed by 2-module termination bar)
    ];

    /**
     * Full Code 128 value-indexed pattern array (values 0-106).
     *
     * This flat array maps each numeric Code 128 value directly to its
     * 11-module bit pattern, making it easy to look up the checksum
     * character and Code 128C digit-pair patterns.
     *
     * @var array<int, string>
     */
    private static array $code128ValuePatterns = [
        0 => '11011001100',
        1 => '11001101100',
        2 => '11001100110',
        3 => '10010011000',
        4 => '10010001100',
        5 => '10001001100',
        6 => '10011001000',
        7 => '10011000100',
        8 => '10001100100',
        9 => '11001001000',
        10 => '11001000100',
        11 => '11000100100',
        12 => '10110011100',
        13 => '10011011100',
        14 => '10011001110',
        15 => '10111001100',
        16 => '10011101100',
        17 => '10011100110',
        18 => '11001110010',
        19 => '11001011100',
        20 => '11001001110',
        21 => '11011100100',
        22 => '11001110100',
        23 => '11101101110',
        24 => '11101001100',
        25 => '11100101100',
        26 => '11100100110',
        27 => '11101100100',
        28 => '11100110100',
        29 => '11100110010',
        30 => '11011011000',
        31 => '11011000110',
        32 => '11000110110',
        33 => '10100011000',
        34 => '10001011000',
        35 => '10001000110',
        36 => '10110001000',
        37 => '10001101000',
        38 => '10001100010',
        39 => '11010001000',
        40 => '11000101000',
        41 => '11000100010',
        42 => '10110111000',
        43 => '10110001110',
        44 => '10001101110',
        45 => '10111011000',
        46 => '10111000110',
        47 => '10001110110',
        48 => '11101110110',
        49 => '11010001110',
        50 => '11000101110',
        51 => '11011101000',
        52 => '11011100010',
        53 => '11011101110',
        54 => '11101011000',
        55 => '11101000110',
        56 => '11100010110',
        57 => '11101101000',
        58 => '11101100010',
        59 => '11100011010',
        60 => '11101111010',
        61 => '11001000010',
        62 => '11110001010',
        63 => '10100110000',
        64 => '10100001100',
        65 => '10010110000',
        66 => '10010000110',
        67 => '10000101100',
        68 => '10000100110',
        69 => '10110010000',
        70 => '10110000100',
        71 => '10011010000',
        72 => '10011000010',
        73 => '10000110100',
        74 => '10000110010',
        75 => '11000010010',
        76 => '11001010000',
        77 => '11110111010',
        78 => '11000010100',
        79 => '10001111010',
        80 => '10100111100',
        81 => '10010111100',
        82 => '10010011110',
        83 => '10111100100',
        84 => '10011110100',
        85 => '10011110010',
        86 => '11110100100',
        87 => '11110010100',
        88 => '11110010010',
        89 => '11011011110',
        90 => '11011110110',
        91 => '11110110110',
        92 => '10101111000',
        93 => '10100011110',
        94 => '10001011110',
        95 => '10111101000',
        96 => '10111100010',
        97 => '11110101000',
        98 => '11110100010',
        99 => '10111011110',
        100 => '10111101110',
        101 => '11101011110',
        102 => '11110101110',
        103 => '11010000100',  // Start A
        104 => '11010010000',  // Start B
        105 => '11010011100',  // Start C
        106 => '11000111010',  // Stop
    ];

    /**
     * Code 39 encoding patterns.
     *
     * Each character maps to a 9-element pattern of narrow (1) and wide (3)
     * bars/spaces.  The encoding alternates bar, space, bar, space … bar
     * (5 bars + 4 spaces = 9 elements).
     *
     * @var array<string, string>
     */
    private static array $code39Patterns = [
        '0' => '101001101101',
        '1' => '110100101011',
        '2' => '101100101011',
        '3' => '110110010101',
        '4' => '101001101011',
        '5' => '110100110101',
        '6' => '101100110101',
        '7' => '101001011011',
        '8' => '110100101101',
        '9' => '101100101101',
        'A' => '110101001011',
        'B' => '101101001011',
        'C' => '110110100101',
        'D' => '101011001011',
        'E' => '110101100101',
        'F' => '101101100101',
        'G' => '101010011011',
        'H' => '110101001101',
        'I' => '101101001101',
        'J' => '101011001101',
        'K' => '110101010011',
        'L' => '101101010011',
        'M' => '110110101001',
        'N' => '101011010011',
        'O' => '110101101001',
        'P' => '101101101001',
        'Q' => '101010110011',
        'R' => '110101011001',
        'S' => '101101011001',
        'T' => '101011011001',
        'U' => '110010101011',
        'V' => '100110101011',
        'W' => '110011010101',
        'X' => '100101101011',
        'Y' => '110010110101',
        'Z' => '100110110101',
        '-' => '100101011011',
        '.' => '110010101101',
        ' ' => '100110101101',
        '$' => '100100100101',
        '/' => '100100101001',
        '+' => '100101001001',
        '%' => '101001001001',
        '*' => '100101101101',
    ];

    /**
     * Code 93 encoding patterns.
     *
     * Each value is a 9-module binary pattern.  Code 93 supports the same
     * character set as Code 39 but is more compact because it uses a
     * continuous (non-discrete) symbology.
     *
     * @var array<string, string>
     */
    private static array $code93Patterns = [
        '0' => '100010100',
        '1' => '101001000',
        '2' => '101000100',
        '3' => '101000010',
        '4' => '100101000',
        '5' => '100100100',
        '6' => '100100010',
        '7' => '101010000',
        '8' => '100010010',
        '9' => '100001010',
        'A' => '110101000',
        'B' => '110100100',
        'C' => '110100010',
        'D' => '110010100',
        'E' => '110010010',
        'F' => '110001010',
        'G' => '101101000',
        'H' => '101100100',
        'I' => '101100010',
        'J' => '100110100',
        'K' => '100011010',
        'L' => '101011000',
        'M' => '101001100',
        'N' => '101000110',
        'O' => '100101100',
        'P' => '100010110',
        'Q' => '110110100',
        'R' => '110110010',
        'S' => '110101100',
        'T' => '110100110',
        'U' => '110010110',
        'V' => '110011010',
        'W' => '101101100',
        'X' => '101100110',
        'Y' => '100110110',
        'Z' => '100111010',
        '-' => '100101110',
        '.' => '111010100',
        ' ' => '111010010',
        '$' => '111001010',
        '/' => '101101110',
        '+' => '101110110',
        '%' => '110101110',
        // Special shift characters used for extended ASCII (($), (%), (/), (+))
        '($)' => '100100110',
        '(%)' => '111011010',
        '(/)' => '111010110',
        '(+)' => '100110010',
    ];

    /** @var string Code 93 start/stop pattern */
    private const CODE93_START_STOP = '101011110';

    /** @var string Code 93 termination bar */
    private const CODE93_TERMINATOR = '1';

    /**
     * Code 11 encoding patterns.
     *
     * Code 11 is a high-density numeric barcode used primarily for
     * labeling telecommunications equipment.  It encodes digits 0-9
     * and the dash character.
     *
     * @var array<string, string>
     */
    private static array $code11Patterns = [
        '0' => '101011',
        '1' => '1101011',
        '2' => '1001011',
        '3' => '1100101',
        '4' => '1011011',
        '5' => '1101101',
        '6' => '1001101',
        '7' => '1010011',
        '8' => '1101001',
        '9' => '1101001',
        '-' => '1011001',
    ];

    /** @var string Code 11 start/stop pattern */
    private const CODE11_START_STOP = '1011001';

    /**
     * Codabar (NW-7) encoding patterns.
     *
     * Codabar is used in libraries, blood banks, and parcel delivery.
     * It encodes digits 0-9, six special characters (- $ : / . +),
     * and four start/stop characters (A, B, C, D).
     *
     * @var array<string, string>
     */
    private static array $codabarPatterns = [
        '0' => '1010100110',
        '1' => '1010110010',
        '2' => '1010010110',
        '3' => '1100101010',
        '4' => '1011010010',
        '5' => '1101010010',
        '6' => '1001010110',
        '7' => '1001011010',
        '8' => '1001101010',
        '9' => '1101001010',
        '-' => '1010011010',
        '$' => '1011001010',
        ':' => '11010110110',
        '/' => '11011010110',
        '.' => '11011011010',
        '+' => '10110110110',
        'A' => '10110010010',
        'B' => '10010010110',
        'C' => '10100010110',
        'D' => '10100110010',
    ];

    /**
     * Interleaved 2 of 5 (ITF) bar and space width patterns.
     *
     * ITF is a high-density numeric symbology commonly used on
     * shipping cartons and in the distribution industry.  Each
     * pair of digits is interleaved: the first digit is encoded
     * in the bar widths, the second in the space widths.
     *
     * 'n' = narrow, 'w' = wide
     *
     * @var array<string, string>
     */
    private static array $itfPatterns = [
        '0' => 'nnwwn',
        '1' => 'wnnnw',
        '2' => 'nwnnw',
        '3' => 'wwnnn',
        '4' => 'nnwnw',
        '5' => 'wnwnn',
        '6' => 'nwwnn',
        '7' => 'nnnww',
        '8' => 'wnnwn',
        '9' => 'nwnwn',
    ];

    /** @var string ITF start pattern */
    private const ITF_START = '1010';

    /** @var string ITF stop pattern  */
    private const ITF_STOP = '11101';

    /**
     * Standard 2 of 5 bar-width patterns.
     *
     * Standard 2 of 5 (also called Industrial 2 of 5) encodes only
     * the bars; the spaces are always narrow.  It is an older, lower-
     * density symbology still found on airline tickets and warehouse
     * applications.
     *
     * 'n' = narrow, 'w' = wide
     *
     * @var array<string, string>
     */
    private static array $standard2of5Patterns = [
        '0' => 'nnwwn',
        '1' => 'wnnnw',
        '2' => 'nwnnw',
        '3' => 'wwnnn',
        '4' => 'nnwnw',
        '5' => 'wnwnn',
        '6' => 'nwwnn',
        '7' => 'nnnww',
        '8' => 'wnnwn',
        '9' => 'nwnwn',
    ];

    /** @var string Standard 2 of 5 start sentinel */
    private const S25_START = '11011010';

    /** @var string Standard 2 of 5 stop sentinel */
    private const S25_STOP = '11010110';

    /**
     * EAN-13 first-digit parity encoding table.
     *
     * The first (country/system) digit of an EAN-13 code is not
     * represented by bars directly; instead it determines the
     * parity pattern (L = odd, G = even) used for digits 2-7.
     *
     * @var array<int, string>
     */
    private static array $eanParityPatterns = [
        0 => 'LLLLLL',
        1 => 'LLGLGG',
        2 => 'LLGGLG',
        3 => 'LLGGGL',
        4 => 'LGLLGG',
        5 => 'LGGLLG',
        6 => 'LGGGLL',
        7 => 'LGLGLG',
        8 => 'LGLGGL',
        9 => 'LGGLGL',
    ];

    /**
     * EAN/UPC L-code (odd parity) patterns for digits 0-9.
     *
     * Used on the left side of UPC-A/EAN-8 and for L-parity
     * positions in EAN-13.
     *
     * @var array<int, string>
     */
    private static array $eanLPatterns = [
        0 => '0001101',
        1 => '0011001',
        2 => '0010011',
        3 => '0111101',
        4 => '0100011',
        5 => '0110001',
        6 => '0101111',
        7 => '0111011',
        8 => '0110111',
        9 => '0001011',
    ];

    /**
     * EAN/UPC G-code (even parity) patterns for digits 0-9.
     *
     * Used for G-parity positions in EAN-13 left half.
     *
     * @var array<int, string>
     */
    private static array $eanGPatterns = [
        0 => '0100111',
        1 => '0110011',
        2 => '0011011',
        3 => '0100001',
        4 => '0011101',
        5 => '0111001',
        6 => '0000101',
        7 => '0010001',
        8 => '0001001',
        9 => '0010111',
    ];

    /**
     * EAN/UPC R-code patterns for digits 0-9.
     *
     * Used on the right side of UPC-A, EAN-13, and EAN-8.
     *
     * @var array<int, string>
     */
    private static array $eanRPatterns = [
        0 => '1110010',
        1 => '1100110',
        2 => '1101100',
        3 => '1000010',
        4 => '1011100',
        5 => '1001110',
        6 => '1010000',
        7 => '1000100',
        8 => '1001000',
        9 => '1110100',
    ];

    /**
     * UPC-E parity patterns indexed by the UPC-A check digit.
     *
     * Each string has 6 characters ('E' = even/G-parity, 'O' = odd/L-parity)
     * that determine how the 6 UPC-E digits are encoded.
     *
     * @var array<int, string>
     */
    private static array $upceParityPatterns = [
        0 => 'EEEOOO',
        1 => 'EEOEOO',
        2 => 'EEOOEO',
        3 => 'EEOOOE',
        4 => 'EOEEOO',
        5 => 'EOOEEO',
        6 => 'EOOOEE',
        7 => 'EOEOEO',
        8 => 'EOEOOE',
        9 => 'EOOEOE',
    ];

    /** @var string EAN/UPC left guard bars (start) */
    private const EAN_START = '101';

    /** @var string EAN/UPC centre guard bars */
    private const EAN_MIDDLE = '01010';

    /** @var string EAN/UPC right guard bars (end) */
    private const EAN_END = '101';

    /**
     * MSI (Modified Plessey) encoding patterns for digits 0-9.
     *
     * MSI is used primarily for inventory control and marking storage
     * shelves in warehouses.
     *
     * @var array<int, string>
     */
    private static array $msiPatterns = [
        0 => '100100100100',
        1 => '100100100110',
        2 => '100100110100',
        3 => '100100110110',
        4 => '100110100100',
        5 => '100110100110',
        6 => '100110110100',
        7 => '100110110110',
        8 => '110100100100',
        9 => '110100100110',
    ];

    /** @var string MSI start sentinel */
    private const MSI_START = '110';

    /** @var string MSI stop sentinel */
    private const MSI_STOP = '1001';

    /**
     * POSTNET encoding patterns.
     *
     * POSTNET (Postal Numeric Encoding Technique) encodes ZIP codes
     * using tall (1) and short (0) bars.  Each digit is represented
     * by 5 bars: exactly 2 tall and 3 short.
     *
     * @var array<int, string>
     */
    private static array $postnetPatterns = [
        0 => '11000',
        1 => '00011',
        2 => '00101',
        3 => '00110',
        4 => '01001',
        5 => '01010',
        6 => '01100',
        7 => '10001',
        8 => '10010',
        9 => '10100',
    ];

    /**
     * PLANET (Postal Alpha Numeric Encoding Technique) patterns.
     *
     * PLANET is similar to POSTNET but inverts the tall/short ratio:
     * each digit uses 3 tall bars and 2 short bars.
     *
     * @var array<int, string>
     */
    private static array $planetPatterns = [
        0 => '00111',
        1 => '11100',
        2 => '11010',
        3 => '11001',
        4 => '10110',
        5 => '10101',
        6 => '10011',
        7 => '01110',
        8 => '01101',
        9 => '01011',
    ];

    #endregion

    #region function

    /**
     * Generate a Code 128 Subset B barcode bit-pattern.
     *
     * Code 128B covers all standard printable ASCII characters
     * (space 0x20 through tilde 0x7E).  The checksum is computed
     * as: (start value + sum of value_i * position_i) mod 103.
     *
     * @param  string $input  The ASCII text to encode
     * @return string         Binary pattern string ('1' = bar, '0' = space)
     *
     * @throws Exception If a character outside the Code 128B range is encountered
     */
    public static function makeCode128B(string $input): string
    {
        // Build a map of printable chars to their Code 128B numeric values.
        $charValues = [];
        $val = 32;
        foreach (array_keys(self::$code128Patterns) as $k) {
            $k = (string) $k;
            if (strlen($k) === 1 && ord($k) >= 32 && ord($k) <= 126) {
                $charValues[$k] = $val - 32;
                $val++;
            }
        }

        // Start with the Code 128B start symbol (value 104).
        $bits = self::$code128Patterns['Start B'];
        $checksum = 104;

        // Encode each character and accumulate the weighted checksum.
        $length = strlen($input);
        for ($i = 0; $i < $length; $i++) {
            $ch = $input[$i];
            if (!isset($charValues[$ch])) {
                throw new Exception("Unsupported char: $ch");
            }
            $val = $charValues[$ch];
            $bits .= self::$code128Patterns[$ch];
            $checksum += $val * ($i + 1);
        }

        // Append checksum character (mod 103).
        $checksumVal = $checksum % 103;
        $checksumChar = array_search($checksumVal, $charValues);
        if ($checksumChar === false) {
            throw new Exception("Checksum lookup failed");
        }
        $bits .= self::$code128Patterns[$checksumChar];

        // Append stop symbol plus the mandatory 2-module termination bar.
        $bits .= self::$code128Patterns['Stop'] . '11';

        return $bits;
    }

    /**
     * Generate a Code 128 Subset A barcode bit-pattern.
     *
     * Code 128A includes uppercase letters, digits, ASCII control
     * characters (0x00-0x1F), and selected special characters.
     * It is commonly used for alphanumeric data containing
     * control characters.
     *
     * @param  string $input  The text to encode (printable ASCII + control chars 0x00-0x1F)
     * @return string         Binary pattern string
     *
     * @throws Exception If a character is not encodable in Code 128A
     */
    public static function makeCode128A(string $input): string
    {
        $bits = self::$code128ValuePatterns[103]; // Start A
        $checksum = 103;

        $length = strlen($input);
        for ($i = 0; $i < $length; $i++) {
            $ascii = ord($input[$i]);

            // Code 128A values:
            //   ASCII 32-95  → values 0-63  (space through underscore)
            //   ASCII  0-31  → values 64-95 (control characters)
            if ($ascii >= 32 && $ascii <= 95) {
                $value = $ascii - 32;
            } elseif ($ascii >= 0 && $ascii <= 31) {
                $value = $ascii + 64;
            } else {
                throw new Exception("Character not encodable in Code 128A: " . $input[$i]);
            }

            $bits .= self::$code128ValuePatterns[$value];
            $checksum += $value * ($i + 1);
        }

        // Checksum and stop
        $checksumVal = $checksum % 103;
        $bits .= self::$code128ValuePatterns[$checksumVal];
        $bits .= self::$code128ValuePatterns[106] . '11'; // Stop + termination bar

        return $bits;
    }

    /**
     * Generate a Code 128 Subset C barcode bit-pattern.
     *
     * Code 128C encodes pairs of digits (00-99) very efficiently,
     * producing the shortest barcode for purely numeric data with
     * an even number of digits.
     *
     * @param  string $input  An even-length string of digits
     * @return string         Binary pattern string
     *
     * @throws InvalidArgumentException If input length is odd or contains non-digits
     */
    public static function makeCode128C(string $input): string
    {
        if (!preg_match('/^\d*$/', $input) || strlen($input) % 2 !== 0) {
            throw new InvalidArgumentException("Code 128C requires an even-length digit-only string. Received: '$input'");
        }

        $bits = self::$code128ValuePatterns[105]; // Start C
        $checksum = 105;

        // Process two digits at a time
        $length = strlen($input);
        $pos = 1; // position weight starts at 1
        for ($i = 0; $i < $length; $i += 2) {
            $pair = (int) substr($input, $i, 2);
            $bits .= self::$code128ValuePatterns[$pair];
            $checksum += $pair * $pos;
            $pos++;
        }

        // Checksum and stop
        $checksumVal = $checksum % 103;
        $bits .= self::$code128ValuePatterns[$checksumVal];
        $bits .= self::$code128ValuePatterns[106] . '11'; // Stop + termination bar

        return $bits;
    }

    /**
     * Generate a Code 128 barcode with automatic subset switching.
     *
     * This encoder selects the most efficient Code 128 subset for each
     * segment of the input string:
     *   - Runs of 4+ digits are encoded with Code C (pairs)
     *   - Remaining characters use Code B
     *
     * This produces the shortest possible Code 128 barcode for mixed data.
     *
     * @param  string $input  The text to encode
     * @return string         Binary pattern string
     *
     * @throws Exception If a character cannot be encoded
     */
    public static function makeCode128Auto(string $input): string
    {
        $length = strlen($input);
        if ($length === 0) {
            throw new InvalidArgumentException("Input must not be empty.");
        }

        // Build value map for Code B characters
        $charValuesB = [];
        $v = 0;
        foreach (array_keys(self::$code128Patterns) as $k) {
            $k = (string) $k;
            if (strlen($k) === 1 && ord($k) >= 32 && ord($k) <= 126) {
                $charValuesB[$k] = $v;
                $v++;
            }
        }

        // Determine whether to start in mode C (if input begins with 4+ digits)
        $mode = 'B';
        $bits = '';
        $checksum = 0;
        $pos = 0; // weighted position (starts at 1 for first data symbol)

        // Count leading digits
        $leadingDigits = 0;
        for ($i = 0; $i < $length; $i++) {
            if (ctype_digit($input[$i])) {
                $leadingDigits++;
            } else {
                break;
            }
        }

        // Choose initial mode
        if ($leadingDigits >= 4) {
            $bits = self::$code128ValuePatterns[105]; // Start C
            $checksum = 105;
            $mode = 'C';
        } else {
            $bits = self::$code128ValuePatterns[104]; // Start B
            $checksum = 104;
            $mode = 'B';
        }

        $i = 0;
        while ($i < $length) {
            // Count upcoming digits from current position
            $digitRun = 0;
            for ($j = $i; $j < $length; $j++) {
                if (ctype_digit($input[$j])) {
                    $digitRun++;
                } else {
                    break;
                }
            }

            if ($mode === 'C') {
                if ($digitRun >= 2) {
                    // Encode digit pair in Code C
                    $pair = (int) substr($input, $i, 2);
                    $pos++;
                    $bits .= self::$code128ValuePatterns[$pair];
                    $checksum += $pair * $pos;
                    $i += 2;
                } else {
                    // Switch to Code B
                    $pos++;
                    $bits .= self::$code128ValuePatterns[100]; // CODE B
                    $checksum += 100 * $pos;
                    $mode = 'B';
                }
            } else {
                // Mode B
                if ($digitRun >= 4) {
                    // Make digit run even length if necessary for Code C
                    if ($digitRun % 2 !== 0) {
                        // Encode one digit in B first
                        $ch = $input[$i];
                        $val = $charValuesB[$ch];
                        $pos++;
                        $bits .= self::$code128ValuePatterns[$val];
                        $checksum += $val * $pos;
                        $i++;
                    }
                    // Switch to Code C
                    $pos++;
                    $bits .= self::$code128ValuePatterns[99]; // CODE C
                    $checksum += 99 * $pos;
                    $mode = 'C';
                } else {
                    // Encode single character in Code B
                    $ch = $input[$i];
                    if (!isset($charValuesB[$ch])) {
                        throw new Exception("Character not encodable: $ch");
                    }
                    $val = $charValuesB[$ch];
                    $pos++;
                    $bits .= self::$code128ValuePatterns[$val];
                    $checksum += $val * $pos;
                    $i++;
                }
            }
        }

        // Checksum and stop
        $checksumVal = $checksum % 103;
        $bits .= self::$code128ValuePatterns[$checksumVal];
        $bits .= self::$code128ValuePatterns[106] . '11';

        return $bits;
    }

    /**
     * Generate a Code 39 barcode bit-pattern.
     *
     * Code 39 is a variable-length alphanumeric symbology widely
     * used in non-retail environments (military, healthcare, automotive).
     * It supports uppercase A-Z, digits 0-9, and characters - . $ / + % SPACE.
     *
     * The asterisk (*) is used internally as the start/stop character
     * and must not appear in the input data.
     *
     * @param  string $input  The text to encode (auto-uppercased)
     * @return string         Binary pattern string
     *
     * @throws InvalidArgumentException If input contains unsupported characters
     */
    public static function makeCode39(string $input): string
    {
        $input = strtoupper($input);

        if (strpos($input, '*') !== false) {
            throw new InvalidArgumentException("Code 39 input must not contain the start/stop character '*'.");
        }

        // Start delimiter
        $bits = self::$code39Patterns['*'] . '0'; // inter-character gap

        // Encode each character separated by a narrow space
        $length = strlen($input);
        for ($i = 0; $i < $length; $i++) {
            $ch = $input[$i];
            if (!isset(self::$code39Patterns[$ch])) {
                throw new InvalidArgumentException("Code 39 does not support character: '$ch'");
            }
            $bits .= self::$code39Patterns[$ch] . '0';
        }

        // Stop delimiter
        $bits .= self::$code39Patterns['*'];

        return $bits;
    }

    /**
     * Mapping of full ASCII characters to Code 39 Extended multi-character sequences.
     *
     * Code 39 Extended uses pairs of Code 39 characters to represent the full
     * 128-character ASCII set.  Characters already in the base Code 39 set
     * map to themselves.
     *
     * @return array<string, string>
     */
    private static function code39ExtendedMap(): array
    {
        static $map = null;
        if ($map !== null) {
            return $map;
        }

        $map = [];

        // Control characters 0x00 - 0x1A  →  %U, $A-$Z
        $map[chr(0)] = '%U';
        for ($i = 1; $i <= 26; $i++) {
            $map[chr($i)] = '$' . chr(64 + $i);
        }
        // 0x1B-0x1F
        $esc = ['%A', '%B', '%C', '%D', '%E'];
        for ($i = 27; $i <= 31; $i++) {
            $map[chr($i)] = $esc[$i - 27];
        }

        // Characters already in base Code 39 (SP through Z, some specials) map to themselves
        $base39 = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ-. $/+%';
        for ($i = 0, $len = strlen($base39); $i < $len; $i++) {
            $map[$base39[$i]] = $base39[$i];
        }

        // Additional printable characters via shift sequences
        $map['!'] = '/A';
        $map['"'] = '/B';
        $map['#'] = '/C';
        $map['&'] = '/F';
        $map["'"] = '/G';
        $map['('] = '/H';
        $map[')'] = '/I';
        $map['*'] = '/J';
        $map[','] = '/L';
        $map[':'] = '/Z';
        $map[';'] = '%F';
        $map['<'] = '%G';
        $map['='] = '%H';
        $map['>'] = '%I';
        $map['?'] = '%J';
        $map['@'] = '%V';
        $map['['] = '%K';
        $map['\\'] = '%L';
        $map[']'] = '%M';
        $map['^'] = '%N';
        $map['_'] = '%O';
        $map['`'] = '%W';

        // Lowercase a-z  →  +A through +Z
        for ($i = 97; $i <= 122; $i++) {
            $map[chr($i)] = '+' . chr($i - 32);
        }

        $map['{'] = '%P';
        $map['|'] = '%Q';
        $map['}'] = '%R';
        $map['~'] = '%S';
        $map[chr(127)] = '%T'; // DEL

        return $map;
    }

    /**
     * Generate a Code 39 Extended barcode bit-pattern.
     *
     * Code 39 Extended supports the full 128-character ASCII set by
     * encoding non-base characters as two-character sequences from the
     * base Code 39 alphabet.
     *
     * @param  string $input  The text to encode
     * @return string         Binary pattern string
     *
     * @throws InvalidArgumentException If input contains characters outside ASCII 0-127
     */
    public static function makeCode39Extended(string $input): string
    {
        $extMap = self::code39ExtendedMap();
        $encoded = '';

        $length = strlen($input);
        for ($i = 0; $i < $length; $i++) {
            $ch = $input[$i];
            if (!isset($extMap[$ch])) {
                throw new InvalidArgumentException("Code 39 Extended cannot encode character: 0x" . dechex(ord($ch)));
            }
            $encoded .= $extMap[$ch];
        }

        // Delegate to the standard Code 39 encoder
        return self::makeCode39($encoded);
    }

    /**
     * Generate a Code 93 barcode bit-pattern.
     *
     * Code 93 is a more compact alternative to Code 39 that uses two
     * check characters (C and K) for enhanced data integrity.  It
     * supports uppercase A-Z, digits 0-9, and - . $ / + % SPACE.
     *
     * @param  string $input  The text to encode (auto-uppercased)
     * @return string         Binary pattern string
     *
     * @throws InvalidArgumentException If unsupported characters are found
     */
    public static function makeCode93(string $input): string
    {
        $input = strtoupper($input);

        // Character set with numeric weight values
        $charset = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ-. $/+%';
        $charMap = [];
        for ($i = 0, $len = strlen($charset); $i < $len; $i++) {
            $charMap[$charset[$i]] = $i;
        }
        // Add the four special shift characters
        $charMap['($)'] = 43;
        $charMap['(%)'] = 44;
        $charMap['(/)'] = 45;
        $charMap['(+)'] = 46;

        // Validate input
        $values = [];
        $length = strlen($input);
        for ($i = 0; $i < $length; $i++) {
            $ch = $input[$i];
            if (!isset($charMap[$ch])) {
                throw new InvalidArgumentException("Code 93 does not support character: '$ch'");
            }
            $values[] = $charMap[$ch];
        }

        // Calculate check digit C (modulo 47, weight cycles 1-20 from right)
        $sum = 0;
        $weight = 1;
        for ($i = count($values) - 1; $i >= 0; $i--) {
            $sum += $values[$i] * $weight;
            $weight++;
            if ($weight > 20) {
                $weight = 1;
            }
        }
        $checkC = $sum % 47;
        $values[] = $checkC;

        // Calculate check digit K (modulo 47, weight cycles 1-15 from right)
        $sum = 0;
        $weight = 1;
        for ($i = count($values) - 1; $i >= 0; $i--) {
            $sum += $values[$i] * $weight;
            $weight++;
            if ($weight > 15) {
                $weight = 1;
            }
        }
        $checkK = $sum % 47;
        $values[] = $checkK;

        // Build the binary pattern
        $bits = self::CODE93_START_STOP;

        // Map value back to character for pattern lookup
        $reverseMap = array_flip($charMap);
        foreach ($values as $v) {
            $ch = $reverseMap[$v];
            $bits .= self::$code93Patterns[$ch];
        }

        $bits .= self::CODE93_START_STOP;
        $bits .= self::CODE93_TERMINATOR;

        return $bits;
    }

    /**
     * Generate a Code 11 barcode bit-pattern.
     *
     * Code 11 is a high-density numeric symbology used mainly for
     * labeling telecommunications equipment.  It encodes digits 0-9
     * and the dash (-) character, with one or two check digits
     * (C and K) depending on data length.
     *
     * Check digit rules:
     *   - C check digit is always appended.
     *   - K check digit is appended when the original data has 10+ characters.
     *
     * @param  string $input  Digits and dashes only
     * @return string         Binary pattern string
     *
     * @throws InvalidArgumentException If input contains invalid characters
     */
    public static function makeCode11(string $input): string
    {
        if (!preg_match('/^[\d\-]+$/', $input)) {
            throw new InvalidArgumentException("Code 11 only supports digits 0-9 and dash. Received: '$input'");
        }

        $charset = '0123456789-';
        $charMap = [];
        for ($i = 0, $len = strlen($charset); $i < $len; $i++) {
            $charMap[$charset[$i]] = $i;
        }

        $values = [];
        $length = strlen($input);
        for ($i = 0; $i < $length; $i++) {
            $values[] = $charMap[$input[$i]];
        }

        // C check digit: weighted sum mod 11, weights cycle 1-10 from right
        $sum = 0;
        $weight = 1;
        for ($i = count($values) - 1; $i >= 0; $i--) {
            $sum += $values[$i] * $weight;
            $weight++;
            if ($weight > 10) {
                $weight = 1;
            }
        }
        $checkC = $sum % 11;
        $values[] = $checkC;

        // K check digit: only if original data is 10+ characters
        if ($length >= 10) {
            $sum = 0;
            $weight = 1;
            for ($i = count($values) - 1; $i >= 0; $i--) {
                $sum += $values[$i] * $weight;
                $weight++;
                if ($weight > 9) {
                    $weight = 1;
                }
            }
            $checkK = $sum % 11;
            $values[] = $checkK;
        }

        // Build binary pattern with start/stop
        $reverseMap = array_flip($charMap);
        $bits = self::CODE11_START_STOP . '0'; // start + gap

        foreach ($values as $v) {
            $ch = ($v <= 9) ? (string) $v : '-';
            $bits .= self::$code11Patterns[$ch] . '0';
        }

        $bits .= self::CODE11_START_STOP;

        return $bits;
    }

    /**
     * Generate a Codabar (NW-7) barcode bit-pattern.
     *
     * Codabar is used by libraries, blood banks, and FedEx.  It encodes
     * digits 0-9 and the symbols - $ : / . +  The data must be
     * enclosed by one of four start/stop characters: A, B, C, or D.
     *
     * If the input does not already begin/end with a valid start/stop
     * character, 'A' is automatically prepended and appended.
     *
     * @param  string $input  The data to encode
     * @return string         Binary pattern string
     *
     * @throws InvalidArgumentException If input contains invalid characters
     */
    public static function makeCodabar(string $input): string
    {
        $input = strtoupper($input);

        // Auto-add start/stop if missing
        $startStopChars = ['A', 'B', 'C', 'D'];
        if (!in_array($input[0] ?? '', $startStopChars, true)) {
            $input = 'A' . $input;
        }
        if (!in_array($input[strlen($input) - 1] ?? '', $startStopChars, true)) {
            $input .= 'A';
        }

        // Validate characters
        $length = strlen($input);
        for ($i = 0; $i < $length; $i++) {
            if (!isset(self::$codabarPatterns[$input[$i]])) {
                throw new InvalidArgumentException("Codabar does not support character: '{$input[$i]}'");
            }
        }

        // Build binary pattern
        $bits = '';
        for ($i = 0; $i < $length; $i++) {
            if ($i > 0) {
                $bits .= '0'; // inter-character gap
            }
            $bits .= self::$codabarPatterns[$input[$i]];
        }

        return $bits;
    }

    /**
     * Generate an Interleaved 2 of 5 (ITF) barcode bit-pattern.
     *
     * ITF is a high-density numeric symbology that encodes digit pairs.
     * In each pair, the first digit controls bar widths and the second
     * controls space widths.  The input must have an even number of digits
     * (a leading zero is prepended automatically if necessary).
     *
     * @param  string $input  Numeric string
     * @return string         Binary pattern string
     *
     * @throws InvalidArgumentException If input contains non-digits
     */
    public static function makeITF(string $input): string
    {
        if (!preg_match('/^\d+$/', $input)) {
            throw new InvalidArgumentException("ITF only supports digits. Received: '$input'");
        }

        // Pad to even length
        if (strlen($input) % 2 !== 0) {
            $input = '0' . $input;
        }

        $narrow = '1';
        $wide = '111';

        // Start pattern
        $bits = self::ITF_START;

        // Process digit pairs
        $length = strlen($input);
        for ($i = 0; $i < $length; $i += 2) {
            $barPattern = self::$itfPatterns[$input[$i]];
            $spacePattern = self::$itfPatterns[$input[$i + 1]];

            // Interleave: bar, space, bar, space, bar
            for ($j = 0; $j < 5; $j++) {
                // Bar
                $bits .= ($barPattern[$j] === 'w') ? $wide : $narrow;
                // Space
                $bits .= ($spacePattern[$j] === 'w') ? '000' : '0';
            }
        }

        // Stop pattern
        $bits .= self::ITF_STOP;

        return $bits;
    }

    /**
     * Generate a Standard 2 of 5 (Industrial) barcode bit-pattern.
     *
     * Standard 2 of 5 encodes information only in the bars (spaces are
     * always narrow).  It is a lower-density numeric symbology still
     * found on airline tickets and warehouse applications.
     *
     * @param  string $input  Numeric string
     * @return string         Binary pattern string
     *
     * @throws InvalidArgumentException If input contains non-digits
     */
    public static function makeStandard2of5(string $input): string
    {
        if (!preg_match('/^\d+$/', $input)) {
            throw new InvalidArgumentException("Standard 2 of 5 only supports digits. Received: '$input'");
        }

        $narrow = '1';
        $wide = '111';

        // Start sentinel
        $bits = self::S25_START;

        $length = strlen($input);
        for ($i = 0; $i < $length; $i++) {
            $pattern = self::$standard2of5Patterns[$input[$i]];
            for ($j = 0; $j < 5; $j++) {
                // Bar (narrow or wide)
                $bits .= ($pattern[$j] === 'w') ? $wide : $narrow;
                // Space is always narrow
                $bits .= '0';
            }
        }

        // Stop sentinel
        $bits .= self::S25_STOP;

        return $bits;
    }

    /**
     * Calculate the EAN/UPC check digit (modulo 10 algorithm).
     *
     * @param  string $digits  The digit string (without check digit)
     * @return int             The check digit (0-9)
     */
    private static function calculateEanCheckDigit(string $digits): int
    {
        $sum = 0;
        $length = strlen($digits);
        for ($i = 0; $i < $length; $i++) {
            $digit = (int) $digits[$i];
            // Alternate weighting: positions from the right get weights 1, 3, 1, 3…
            $weight = (($length - $i) % 2 === 0) ? 1 : 3;
            $sum += $digit * $weight;
        }
        $mod = $sum % 10;
        return ($mod === 0) ? 0 : (10 - $mod);
    }

    /**
     * Generate an EAN-13 barcode bit-pattern.
     *
     * EAN-13 is the European Article Number used worldwide in retail.
     * The input must be 12 digits (the check digit is computed) or
     * 13 digits (the check digit is validated).
     *
     * Structure: [Start Guard] [Left 6 digits] [Centre Guard] [Right 6 digits] [End Guard]
     *
     * @param  string $input  12 or 13 digit string
     * @return string         Binary pattern string
     *
     * @throws InvalidArgumentException If input is not valid
     */
    public static function makeEAN13(string $input): string
    {
        if (!preg_match('/^\d{12,13}$/', $input)) {
            throw new InvalidArgumentException("EAN-13 requires 12 or 13 digits. Received: '$input'");
        }

        if (strlen($input) === 12) {
            $input .= (string) self::calculateEanCheckDigit($input);
        } else {
            // Validate provided check digit
            $expected = self::calculateEanCheckDigit(substr($input, 0, 12));
            if ((int) $input[12] !== $expected) {
                throw new InvalidArgumentException("EAN-13 check digit mismatch: expected $expected, got {$input[12]}");
            }
        }

        $digits = str_split($input);
        $first = (int) $digits[0];
        $parity = self::$eanParityPatterns[$first];

        // Start guard
        $bits = self::EAN_START;

        // Left side: digits 2-7 (indices 1-6) using L or G parity
        for ($i = 0; $i < 6; $i++) {
            $d = (int) $digits[$i + 1];
            if ($parity[$i] === 'L') {
                $bits .= self::$eanLPatterns[$d];
            } else {
                $bits .= self::$eanGPatterns[$d];
            }
        }

        // Centre guard
        $bits .= self::EAN_MIDDLE;

        // Right side: digits 8-13 (indices 7-12) using R-code
        for ($i = 7; $i <= 12; $i++) {
            $d = (int) $digits[$i];
            $bits .= self::$eanRPatterns[$d];
        }

        // End guard
        $bits .= self::EAN_END;

        return $bits;
    }

    /**
     * Generate an EAN-8 barcode bit-pattern.
     *
     * EAN-8 is the short-form EAN used for small packages.  The input
     * must be 7 digits (check digit is computed) or 8 digits (check
     * digit is validated).
     *
     * Structure: [Start Guard] [Left 4 digits (L-code)] [Centre Guard] [Right 4 digits (R-code)] [End Guard]
     *
     * @param  string $input  7 or 8 digit string
     * @return string         Binary pattern string
     *
     * @throws InvalidArgumentException If input is not valid
     */
    public static function makeEAN8(string $input): string
    {
        if (!preg_match('/^\d{7,8}$/', $input)) {
            throw new InvalidArgumentException("EAN-8 requires 7 or 8 digits. Received: '$input'");
        }

        if (strlen($input) === 7) {
            $input .= (string) self::calculateEanCheckDigit($input);
        } else {
            $expected = self::calculateEanCheckDigit(substr($input, 0, 7));
            if ((int) $input[7] !== $expected) {
                throw new InvalidArgumentException("EAN-8 check digit mismatch: expected $expected, got {$input[7]}");
            }
        }

        $digits = str_split($input);

        // Start guard
        $bits = self::EAN_START;

        // Left side: 4 digits using L-code
        for ($i = 0; $i < 4; $i++) {
            $bits .= self::$eanLPatterns[(int) $digits[$i]];
        }

        // Centre guard
        $bits .= self::EAN_MIDDLE;

        // Right side: 4 digits using R-code
        for ($i = 4; $i < 8; $i++) {
            $bits .= self::$eanRPatterns[(int) $digits[$i]];
        }

        // End guard
        $bits .= self::EAN_END;

        return $bits;
    }

    /**
     * Generate a UPC-A barcode bit-pattern.
     *
     * UPC-A is the 12-digit Universal Product Code used in North American
     * retail.  The input must be 11 digits (check digit is computed)
     * or 12 digits (check digit is validated).
     *
     * Structure: [Start Guard] [Left 6 digits (L-code)] [Centre Guard] [Right 6 digits (R-code)] [End Guard]
     *
     * @param  string $input  11 or 12 digit string
     * @return string         Binary pattern string
     *
     * @throws InvalidArgumentException If input is not valid
     */
    public static function makeUPCA(string $input): string
    {
        if (!preg_match('/^\d{11,12}$/', $input)) {
            throw new InvalidArgumentException("UPC-A requires 11 or 12 digits. Received: '$input'");
        }

        if (strlen($input) === 11) {
            $input .= (string) self::calculateUpcCheckDigit($input);
        } else {
            $expected = self::calculateUpcCheckDigit(substr($input, 0, 11));
            if ((int) $input[11] !== $expected) {
                throw new InvalidArgumentException("UPC-A check digit mismatch: expected $expected, got {$input[11]}");
            }
        }

        $digits = str_split($input);

        // Start guard
        $bits = self::EAN_START;

        // Left side: first 6 digits using L-code (odd parity)
        for ($i = 0; $i < 6; $i++) {
            $bits .= self::$eanLPatterns[(int) $digits[$i]];
        }

        // Centre guard
        $bits .= self::EAN_MIDDLE;

        // Right side: last 6 digits using R-code (even parity)
        for ($i = 6; $i < 12; $i++) {
            $bits .= self::$eanRPatterns[(int) $digits[$i]];
        }

        // End guard
        $bits .= self::EAN_END;

        return $bits;
    }

    /**
     * Calculate UPC-A check digit.
     *
     * The algorithm sums odd-position digits × 3 + even-position digits,
     * then the check digit is (10 - (sum mod 10)) mod 10.
     *
     * @param  string $digits  The first 11 digits
     * @return int             Check digit (0-9)
     */
    private static function calculateUpcCheckDigit(string $digits): int
    {
        $sum = 0;
        for ($i = 0; $i < 11; $i++) {
            $d = (int) $digits[$i];
            $sum += ($i % 2 === 0) ? $d * 3 : $d;
        }
        $mod = $sum % 10;
        return ($mod === 0) ? 0 : (10 - $mod);
    }

    /**
     * Generate a UPC-E barcode bit-pattern.
     *
     * UPC-E is a zero-suppressed version of UPC-A for small packages.
     * It uses 6 encoded digits (plus an implied number system digit 0
     * and a check digit derived from the full UPC-A expansion).
     *
     * The input should be either:
     *   - 6 digits (the compressed code; system digit 0 and check digit are computed)
     *   - 8 digits (system digit + 6 compressed digits + check digit)
     *
     * Structure: [Start Guard: 101] [6 encoded digits] [End Guard: 010101]
     *
     * @param  string $input  6 or 8 digit string
     * @return string         Binary pattern string
     *
     * @throws InvalidArgumentException If input is not valid
     */
    public static function makeUPCE(string $input): string
    {
        if (!preg_match('/^\d{6}$|^\d{8}$/', $input)) {
            throw new InvalidArgumentException("UPC-E requires 6 or 8 digits. Received: '$input'");
        }

        if (strlen($input) === 8) {
            // Extract system digit, 6-digit code, and check digit
            $sixDigits = substr($input, 1, 6);
            $checkDigit = (int) $input[7];
        } else {
            $sixDigits = $input;
            // Expand UPC-E to UPC-A to compute check digit
            $upcA = self::expandUPCE($sixDigits);
            $checkDigit = self::calculateUpcCheckDigit(substr($upcA, 0, 11));
        }

        // Get parity pattern based on check digit
        $parity = self::$upceParityPatterns[$checkDigit];

        // Start guard (UPC-E uses 101 as start)
        $bits = '101';

        // Encode 6 digits with parity
        for ($i = 0; $i < 6; $i++) {
            $d = (int) $sixDigits[$i];
            if ($parity[$i] === 'O') {
                $bits .= self::$eanLPatterns[$d]; // Odd = L-code
            } else {
                $bits .= self::$eanGPatterns[$d]; // Even = G-code
            }
        }

        // End guard (UPC-E uses 010101)
        $bits .= '010101';

        return $bits;
    }

    /**
     * Expand a 6-digit UPC-E code to a full 12-digit UPC-A code.
     *
     * This is needed to compute the UPC-A check digit for a UPC-E barcode.
     *
     * @param  string $sixDigits  The 6 compressed digits
     * @return string             The 12-digit UPC-A string (without check digit at this stage)
     */
    private static function expandUPCE(string $sixDigits): string
    {
        $d = str_split($sixDigits);
        $lastDigit = $d[5];

        // Expansion rules depend on the 6th digit
        switch ($lastDigit) {
            case '0':
            case '1':
            case '2':
                // Manufacturer: d0 d1 {last} 00, Product: 00 d3 d4
                $upcA = '0' . $d[0] . $d[1] . $lastDigit . '0000' . $d[2] . $d[3] . $d[4];
                break;
            case '3':
                $upcA = '0' . $d[0] . $d[1] . $d[2] . '00000' . $d[3] . $d[4];
                break;
            case '4':
                $upcA = '0' . $d[0] . $d[1] . $d[2] . $d[3] . '00000' . $d[4];
                break;
            default: // 5-9
                $upcA = '0' . $d[0] . $d[1] . $d[2] . $d[3] . $d[4] . '0000' . $lastDigit;
                break;
        }

        // The result is 11 digits; the caller appends the check digit
        return $upcA;
    }

    /**
     * Generate an MSI Plessey (Mod 10) barcode bit-pattern.
     *
     * MSI is used for inventory control, warehouse shelf marking, and
     * container identification.  This implementation uses the Luhn (Mod 10)
     * check digit algorithm.
     *
     * @param  string $input  Numeric string
     * @return string         Binary pattern string
     *
     * @throws InvalidArgumentException If input contains non-digits
     */
    public static function makeMSI(string $input): string
    {
        if (!preg_match('/^\d+$/', $input)) {
            throw new InvalidArgumentException("MSI only supports digits. Received: '$input'");
        }

        // Calculate Luhn Mod 10 check digit
        $checkDigit = self::calculateLuhnCheckDigit($input);
        $data = $input . (string) $checkDigit;

        // Start sentinel
        $bits = self::MSI_START;

        // Encode each digit
        $length = strlen($data);
        for ($i = 0; $i < $length; $i++) {
            $d = (int) $data[$i];
            $bits .= self::$msiPatterns[$d];
        }

        // Stop sentinel
        $bits .= self::MSI_STOP;

        return $bits;
    }

    /**
     * Calculate a Luhn (Mod 10) check digit for MSI Plessey.
     *
     * Algorithm:
     *   1. Concatenate every other digit (from right) into a number, double it.
     *   2. Sum the digits of the doubled number plus the remaining digits.
     *   3. Check digit = (10 - (sum mod 10)) mod 10.
     *
     * @param  string $input  The digit string
     * @return int            Check digit (0-9)
     */
    private static function calculateLuhnCheckDigit(string $input): int
    {
        $length = strlen($input);

        // Collect odd and even position digits (from the right, 1-indexed)
        $oddDigits = '';
        $evenSum = 0;
        for ($i = $length - 1; $i >= 0; $i--) {
            $pos = $length - $i; // 1-indexed position from right
            if ($pos % 2 === 1) {
                // Odd positions (from right) get concatenated then doubled
                $oddDigits = $input[$i] . $oddDigits;
            } else {
                $evenSum += (int) $input[$i];
            }
        }

        // Double the concatenated odd-position number and sum its digits
        $doubled = (string) ((int) $oddDigits * 2);
        $doubledSum = 0;
        for ($i = 0, $len = strlen($doubled); $i < $len; $i++) {
            $doubledSum += (int) $doubled[$i];
        }

        $total = $doubledSum + $evenSum;
        $mod = $total % 10;
        return ($mod === 0) ? 0 : (10 - $mod);
    }

    /**
     * Generate a Pharmacode barcode bit-pattern.
     *
     * Pharmacode is a binary barcode used in the pharmaceutical industry
     * for packaging control.  It encodes integers from 3 to 131070.
     * Thick bars represent binary '1' (value × 2 + 1) and thin bars
     * represent binary '0' (value × 2).
     *
     * @param  int $number  The integer to encode (3 – 131070)
     * @return string       Binary pattern string
     *
     * @throws InvalidArgumentException If the number is out of range
     */
    public static function makePharmacode(int $number): string
    {
        if ($number < 3 || $number > 131070) {
            throw new InvalidArgumentException("Pharmacode supports values 3–131070. Received: $number");
        }

        $bars = [];
        $n = $number;

        // Pharmacode encoding: divide by 2 repeatedly;
        // if odd → thick bar, if even → thin bar
        while ($n > 0) {
            if ($n % 2 === 0) {
                // Even → thin bar
                $bars[] = '10';   // thin bar + space
                $n = ($n - 2) / 2;
            } else {
                // Odd → thick bar
                $bars[] = '1110'; // thick bar + space
                $n = ($n - 1) / 2;
            }
        }

        // Bars are generated LSB-first, so reverse
        $bars = array_reverse($bars);

        // Concatenate, removing the trailing space from the last bar
        $bits = implode('00', $bars); // inter-bar gap

        return $bits;
    }

    /**
     * Generate a POSTNET barcode bit-pattern.
     *
     * POSTNET (Postal Numeric Encoding Technique) encodes 5, 9, or 11
     * digit ZIP codes using tall and short bars.  A check digit is
     * appended so that all digit values sum to a multiple of 10.
     *
     * In the output: '1' = tall bar, '0' = short bar.  The rendering
     * method should draw tall bars at full height and short bars at
     * approximately 40% height.
     *
     * @param  string $input  5, 9, or 11 digit ZIP code (hyphens are stripped)
     * @return string         Bar-height pattern string ('1' = tall, '0' = short)
     *
     * @throws InvalidArgumentException If input length is not valid
     */
    public static function makePostnet(string $input): string
    {
        // Strip hyphens
        $input = str_replace('-', '', $input);

        if (!preg_match('/^\d+$/', $input)) {
            throw new InvalidArgumentException("POSTNET only supports digits. Received: '$input'");
        }

        $length = strlen($input);
        if (!in_array($length, [5, 9, 11], true)) {
            throw new InvalidArgumentException("POSTNET requires 5, 9, or 11 digits. Received $length digits.");
        }

        // Calculate check digit: (10 - (sum of digits mod 10)) mod 10
        $sum = 0;
        for ($i = 0; $i < $length; $i++) {
            $sum += (int) $input[$i];
        }
        $checkDigit = (10 - ($sum % 10)) % 10;

        // Frame bar (tall)
        $bits = '1';

        // Encode each digit
        for ($i = 0; $i < $length; $i++) {
            $bits .= self::$postnetPatterns[(int) $input[$i]];
        }

        // Append check digit
        $bits .= self::$postnetPatterns[$checkDigit];

        // Frame bar (tall)
        $bits .= '1';

        return $bits;
    }

    /**
     * Generate a PLANET barcode bit-pattern.
     *
     * PLANET (Postal Alpha Numeric Encoding Technique) is similar to
     * POSTNET but inverts the bar heights: each digit uses 3 tall bars
     * and 2 short bars (compared to POSTNET's 2 tall + 3 short).
     *
     * PLANET codes are 12 or 14 digits (including check digit).
     *
     * @param  string $input  11 or 13 digit string (check digit is computed)
     * @return string         Bar-height pattern string
     *
     * @throws InvalidArgumentException If input is not valid
     */
    public static function makePlanet(string $input): string
    {
        $input = str_replace('-', '', $input);

        if (!preg_match('/^\d+$/', $input)) {
            throw new InvalidArgumentException("PLANET only supports digits. Received: '$input'");
        }

        $length = strlen($input);
        if (!in_array($length, [11, 13], true)) {
            throw new InvalidArgumentException("PLANET requires 11 or 13 digits. Received $length digits.");
        }

        // Check digit: same algorithm as POSTNET
        $sum = 0;
        for ($i = 0; $i < $length; $i++) {
            $sum += (int) $input[$i];
        }
        $checkDigit = (10 - ($sum % 10)) % 10;

        // Frame bar (tall)
        $bits = '1';

        // Encode each digit
        for ($i = 0; $i < $length; $i++) {
            $bits .= self::$planetPatterns[(int) $input[$i]];
        }

        // Append check digit
        $bits .= self::$planetPatterns[$checkDigit];

        // Frame bar (tall)
        $bits .= '1';

        return $bits;
    }

    /**
     * Draw any barcode from a binary pattern and save it as a PNG file.
     *
     * This is a unified rendering method that converts a string of '1'
     * (bar) and '0' (space) characters into a raster image.  Each module
     * is drawn as a 1-pixel-wide vertical line at the specified height.
     *
     * @param  string $text      The text to encode
     * @param  string $filename  The output PNG file path
     * @param  int    $height    The bar height in pixels (default: 60)
     * @param  string $type      The barcode type (default: 'code128b')
     *                           Supported: code128a, code128b, code128c, code128auto,
     *                           code39, code39ext, code93, code11, codabar,
     *                           itf, standard2of5, ean13, ean8, upca, upce,
     *                           msi, postnet, planet
     *
     * @return bool True on success
     *
     * @throws InvalidArgumentException If the barcode type is unsupported
     * @throws RuntimeException         If image creation fails
     */
    public static function drawBarcode(string $text, string $filename, int $height = 60, string $type = 'code128b'): bool
    {
        // Resolve the binary pattern based on the requested barcode type
        $bits = self::encode($type, $text);

        $width = strlen($bits);

        // Create the image canvas
        $img = imagecreate($width, $height);
        if ($img === false) {
            throw new RuntimeException("imagecreate() failed: Unable to create image.");
        }

        // Allocate background (white) – the first allocated colour becomes the background
        $white = imagecolorallocate($img, 255, 255, 255);
        if ($white === false) {
            throw new RuntimeException("imagecolorallocate() failed: Unable to allocate white color.");
        }

        if (imagefill($img, 0, 0, $white) === false) {
            throw new RuntimeException("imagefill() failed: Unable to fill image background.");
        }

        // Allocate foreground (black)
        $black = imagecolorallocate($img, 0, 0, 0);
        if ($black === false) {
            throw new RuntimeException("imagecolorallocate() failed: Unable to allocate black color.");
        }

        // Determine bar heights for POSTNET/PLANET (variable height) vs normal barcodes
        $isHeightEncoded = in_array(strtolower($type), ['postnet', 'planet'], true);

        for ($x = 0; $x < $width; $x++) {
            if ($bits[$x] === '1') {
                if ($isHeightEncoded) {
                    // Full-height tall bar
                    imageline($img, $x, 0, $x, $height - 1, $black);
                } else {
                    imageline($img, $x, 0, $x, $height - 1, $black);
                }
            } elseif ($isHeightEncoded && $bits[$x] === '0') {
                // Short bar for POSTNET/PLANET (approximately 40% of full height)
                $shortTop = (int) ($height * 0.6);
                imageline($img, $x, $shortTop, $x, $height - 1, $black);
            }
        }

        // Write the PNG file
        $output = imagepng($img, $filename);

        // Free image memory (imagedestroy removed in PHP 8.5+)
        if (OperationSystem::comparePHPVersion('8.5.0', '<')) {
            // @phpstan-ignore-next-line
            imagedestroy($img);
        }

        return $output;
    }

    /**
     * Generate a barcode bit-pattern for any supported type.
     *
     * This is a convenience wrapper that routes to the appropriate
     * encoder method based on the $type parameter.
     *
     * @param  string     $data  The data to encode
     * @param  string     $type  The barcode symbology (case-insensitive)
     * @return string            Binary pattern string
     *
     * @throws InvalidArgumentException If the type is unsupported
     */
    public static function encode(string $data, string $type = 'code128b'): string
    {
        return match (strtolower($type)) {
            'code128a' => self::makeCode128A($data),
            'code128b' => self::makeCode128B($data),
            'code128c' => self::makeCode128C($data),
            'code128auto', 'code128' => self::makeCode128Auto($data),
            'code39' => self::makeCode39($data),
            'code39ext', 'code39extended' => self::makeCode39Extended($data),
            'code93' => self::makeCode93($data),
            'code11' => self::makeCode11($data),
            'codabar' => self::makeCodabar($data),
            'itf', 'interleaved2of5' => self::makeITF($data),
            'standard2of5', 'industrial2of5' => self::makeStandard2of5($data),
            'ean13' => self::makeEAN13($data),
            'ean8' => self::makeEAN8($data),
            'upca' => self::makeUPCA($data),
            'upce' => self::makeUPCE($data),
            'msi' => self::makeMSI($data),
            'pharmacode' => self::makePharmacode((int) $data),
            'postnet' => self::makePostnet($data),
            'planet' => self::makePlanet($data),
            default => throw new InvalidArgumentException("Unsupported barcode type: $type"),
        };
    }

    /**
     * Return a list of all supported barcode type identifiers.
     *
     * Useful for validation or building UI selectors.
     *
     * @return array<int, string>
     */
    public static function getSupportedTypes(): array
    {
        return [
            'code128a',
            'code128b',
            'code128c',
            'code128auto',
            'code39',
            'code39ext',
            'code93',
            'code11',
            'codabar',
            'itf',
            'standard2of5',
            'ean13',
            'ean8',
            'upca',
            'upce',
            'msi',
            'pharmacode',
            'postnet',
            'planet',
        ];
    }

    /**
     * Destructor – delegates to parent.
     */
    public function __destruct()
    {
        parent::__destruct();
    }

    #endregion
}
