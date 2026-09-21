<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Image;

use Clover\Classes\Image\Handler as ImageHandler;
use GdImage;
use RuntimeException;
use function strlen;
use function ord;
use function chr;
use function count;
use function array_slice;

/**
 * Class SimpleQRCode
 *
 * @package Clover\Classes\Image
 */
class SimpleQRCode
{
    #region properties

    /** Error correction level constants. */
    public const ECC_L = 0; // ~7%  recovery
    public const ECC_M = 1; // ~15% recovery
    public const ECC_Q = 2; // ~25% recovery
    public const ECC_H = 3; // ~30% recovery

    /** Byte-mode indicator (4 bits). */
    private const MODE_BYTE = 0b0100;

    /** Padding bytes alternated to fill any remaining data capacity. */
    private const PAD_BYTES = [0xEC, 0x11];

    /** XOR mask applied to the 15-bit format information codeword. */
    private const FORMAT_MASK = 0x5412;

    /** Generator polynomial for the BCH(15,5) format information code. */
    private const FORMAT_GEN_POLY = 0x537;

    /** Generator polynomial for the BCH(18,6) version information code. */
    private const VERSION_GEN_POLY = 0x1F25;

    /** Mapping: internal ECC index -> 2-bit format-info ECC field (L=01, M=00, Q=11, H=10). */
    private const ECC_TO_FORMAT_BITS = [1, 0, 3, 2];

    /** Two finder candidates belong to the same cluster only within this many module widths. */
    private const FINDER_CLUSTER_RADIUS_MODULES = 1.5;

    /** Two finder candidates merge only when their estimated module sizes stay within this ratio. */
    private const FINDER_CLUSTER_SIZE_RATIO = 1.5;

    /**
     * Error correction table for every (version, ECC level) pair.
     *
     * Each entry is [ecCodewordsPerBlock, group1Blocks, group1DataPerBlock,
     * group2Blocks, group2DataPerBlock]. Indexed [version-1][ecLevel].
     * Sourced from ISO/IEC 18004:2015 Annex D.
     */
    private const EC_TABLE = [
        // Version 1
        [[7, 1, 19, 0, 0], [10, 1, 16, 0, 0], [13, 1, 13, 0, 0], [17, 1, 9, 0, 0]],
        // Version 2
        [[10, 1, 34, 0, 0], [16, 1, 28, 0, 0], [22, 1, 22, 0, 0], [28, 1, 16, 0, 0]],
        // Version 3
        [[15, 1, 55, 0, 0], [26, 1, 44, 0, 0], [18, 2, 17, 0, 0], [22, 2, 13, 0, 0]],
        // Version 4
        [[20, 1, 80, 0, 0], [18, 2, 32, 0, 0], [26, 2, 24, 0, 0], [16, 4, 9, 0, 0]],
        // Version 5
        [[26, 1, 108, 0, 0], [24, 2, 43, 0, 0], [18, 2, 15, 2, 16], [22, 2, 11, 2, 12]],
        // Version 6
        [[18, 2, 68, 0, 0], [16, 4, 27, 0, 0], [24, 4, 19, 0, 0], [28, 4, 15, 0, 0]],
        // Version 7
        [[20, 2, 78, 0, 0], [18, 4, 31, 0, 0], [18, 2, 14, 4, 15], [26, 4, 13, 1, 14]],
        // Version 8
        [[24, 2, 97, 0, 0], [22, 2, 38, 2, 39], [22, 4, 18, 2, 19], [26, 4, 14, 2, 15]],
        // Version 9
        [[30, 2, 116, 0, 0], [22, 3, 36, 2, 37], [20, 4, 16, 4, 17], [24, 4, 12, 4, 13]],
        // Version 10
        [[18, 2, 68, 2, 69], [26, 4, 43, 1, 44], [24, 6, 19, 2, 20], [28, 6, 15, 2, 16]],
        // Version 11
        [[20, 4, 81, 0, 0], [30, 1, 50, 4, 51], [28, 4, 22, 4, 23], [24, 3, 12, 8, 13]],
        // Version 12
        [[24, 2, 92, 2, 93], [22, 6, 36, 2, 37], [26, 4, 20, 6, 21], [28, 7, 14, 4, 15]],
        // Version 13
        [[26, 4, 107, 0, 0], [22, 8, 37, 1, 38], [24, 8, 20, 4, 21], [22, 12, 11, 4, 12]],
        // Version 14
        [[30, 3, 115, 1, 116], [24, 4, 40, 5, 41], [20, 11, 16, 5, 17], [24, 11, 12, 5, 13]],
        // Version 15
        [[22, 5, 87, 1, 88], [24, 5, 41, 5, 42], [30, 5, 24, 7, 25], [24, 11, 12, 7, 13]],
        // Version 16
        [[24, 5, 98, 1, 99], [28, 7, 45, 3, 46], [24, 15, 19, 2, 20], [30, 3, 15, 13, 16]],
        // Version 17
        [[28, 1, 107, 5, 108], [28, 10, 46, 1, 47], [28, 1, 22, 15, 23], [28, 2, 14, 17, 15]],
        // Version 18
        [[30, 5, 120, 1, 121], [26, 9, 43, 4, 44], [28, 17, 22, 1, 23], [28, 2, 14, 19, 15]],
        // Version 19
        [[28, 3, 113, 4, 114], [26, 3, 44, 11, 45], [26, 17, 21, 4, 22], [26, 9, 13, 16, 14]],
        // Version 20
        [[28, 3, 107, 5, 108], [26, 3, 41, 13, 42], [30, 15, 24, 5, 25], [28, 15, 15, 10, 16]],
        // Version 21
        [[28, 4, 116, 4, 117], [26, 17, 42, 0, 0], [28, 17, 22, 6, 23], [30, 19, 16, 6, 17]],
        // Version 22
        [[28, 2, 111, 7, 112], [28, 17, 46, 0, 0], [30, 7, 24, 16, 25], [24, 34, 13, 0, 0]],
        // Version 23
        [[30, 4, 121, 5, 122], [28, 4, 47, 14, 48], [30, 11, 24, 14, 25], [30, 16, 15, 14, 16]],
        // Version 24
        [[30, 6, 117, 4, 118], [28, 6, 45, 14, 46], [30, 11, 24, 16, 25], [30, 30, 16, 2, 17]],
        // Version 25
        [[26, 8, 106, 4, 107], [28, 8, 47, 13, 48], [30, 7, 24, 22, 25], [30, 22, 15, 13, 16]],
        // Version 26
        [[28, 10, 114, 2, 115], [28, 19, 46, 4, 47], [28, 28, 22, 6, 23], [30, 33, 16, 4, 17]],
        // Version 27
        [[30, 8, 122, 4, 123], [28, 22, 45, 3, 46], [30, 8, 23, 26, 24], [30, 12, 15, 28, 16]],
        // Version 28
        [[30, 3, 117, 10, 118], [28, 3, 45, 23, 46], [30, 4, 24, 31, 25], [30, 11, 15, 31, 16]],
        // Version 29
        [[30, 7, 116, 7, 117], [28, 21, 45, 7, 46], [30, 1, 23, 37, 24], [30, 19, 15, 26, 16]],
        // Version 30
        [[30, 5, 115, 10, 116], [28, 19, 47, 10, 48], [30, 15, 24, 25, 25], [30, 23, 15, 25, 16]],
        // Version 31
        [[30, 13, 115, 3, 116], [28, 2, 46, 29, 47], [30, 42, 24, 1, 25], [30, 23, 15, 28, 16]],
        // Version 32
        [[30, 17, 115, 0, 0], [28, 10, 46, 23, 47], [30, 10, 24, 35, 25], [30, 19, 15, 35, 16]],
        // Version 33
        [[30, 17, 115, 1, 116], [28, 14, 46, 21, 47], [30, 29, 24, 19, 25], [30, 11, 15, 46, 16]],
        // Version 34
        [[30, 13, 115, 6, 116], [28, 14, 46, 23, 47], [30, 44, 24, 7, 25], [30, 59, 16, 1, 17]],
        // Version 35
        [[30, 12, 121, 7, 122], [28, 12, 47, 26, 48], [30, 39, 24, 14, 25], [30, 22, 15, 41, 16]],
        // Version 36
        [[30, 6, 121, 14, 122], [28, 6, 47, 34, 48], [30, 46, 24, 10, 25], [30, 2, 15, 64, 16]],
        // Version 37
        [[30, 17, 122, 4, 123], [28, 29, 46, 14, 47], [30, 49, 24, 10, 25], [30, 24, 15, 46, 16]],
        // Version 38
        [[30, 4, 122, 18, 123], [28, 13, 46, 32, 47], [30, 48, 24, 14, 25], [30, 42, 15, 32, 16]],
        // Version 39
        [[30, 20, 117, 4, 118], [28, 40, 47, 7, 48], [30, 43, 24, 22, 25], [30, 10, 15, 67, 16]],
        // Version 40
        [[30, 19, 118, 6, 119], [28, 18, 47, 31, 48], [30, 34, 24, 34, 25], [30, 20, 15, 61, 16]],
    ];

    /**
     * Center coordinates of alignment patterns for each version (1-40).
     * Version 1 has none. The three corners coinciding with finder patterns
     * are skipped at placement time.
     */
    private const ALIGNMENT_POSITIONS = [
        [],
        [6, 18],
        [6, 22],
        [6, 26],
        [6, 30],
        [6, 34],
        [6, 22, 38],
        [6, 24, 42],
        [6, 26, 46],
        [6, 28, 50],
        [6, 30, 54],
        [6, 32, 58],
        [6, 34, 62],
        [6, 26, 46, 66],
        [6, 26, 48, 70],
        [6, 26, 50, 74],
        [6, 30, 54, 78],
        [6, 30, 56, 82],
        [6, 30, 58, 86],
        [6, 34, 62, 90],
        [6, 28, 50, 72, 94],
        [6, 26, 50, 74, 98],
        [6, 30, 54, 78, 102],
        [6, 28, 54, 80, 106],
        [6, 32, 58, 84, 110],
        [6, 30, 58, 86, 114],
        [6, 34, 62, 90, 118],
        [6, 26, 50, 74, 98, 122],
        [6, 30, 54, 78, 102, 126],
        [6, 26, 52, 78, 104, 130],
        [6, 30, 56, 82, 108, 134],
        [6, 34, 60, 86, 112, 138],
        [6, 30, 58, 86, 114, 142],
        [6, 34, 62, 90, 118, 146],
        [6, 30, 54, 78, 102, 126, 150],
        [6, 24, 50, 76, 102, 128, 154],
        [6, 28, 54, 80, 106, 132, 158],
        [6, 32, 58, 84, 110, 136, 162],
        [6, 26, 54, 82, 110, 138, 166],
        [6, 30, 58, 86, 114, 142, 170],
    ];

    /**
     * Number of remainder bits appended after the final data codeword,
     * indexed by version-1.
     */
    private const REMAINDER_BITS = [0, 7, 7, 7, 7, 7, 0, 0, 0, 0, 0, 0, 0, 3, 3, 3, 3, 3, 3, 3, 4, 4, 4, 4, 4, 4, 4, 3, 3, 3, 3, 3, 3, 3, 0, 0, 0, 0, 0, 0];

    /** Galois field GF(256) exponent table (length 512 for free wraparound). */
    private static ?array $gfExp = null;
    /** Galois field GF(256) logarithm table (length 256). */
    private static ?array $gfLog = null;
    /** Payload to encode (raw bytes). */
    private string $data;
    /** Module size in pixels. */
    private int $moduleSize;
    /** Error correction level (0=L, 1=M, 2=Q, 3=H). */
    private int $eccLevel;
    /** Version number (1-40). */
    private int $version;
    /** Size of the QR matrix (version-dependent, 21-177). */
    private int $matrixSize;
    /** 2D matrix of module values (0 = light, 1 = dark). */
    private array $matrix;
    /** 2D matrix of booleans: true if the cell belongs to a function pattern and must not be touched by data placement / masking. */
    private array $reserved;

    /**
     * SimpleQRCode constructor.
     *
     * @param string $data       Payload to encode (treated as raw bytes; UTF-8 safe).
     * @param int    $size       Module size in pixels (each QR module renders as a $size x $size square).
     * @param int    $eccLevel   One of self::ECC_L, ECC_M, ECC_Q, ECC_H.
     */
    public function __construct(string $data, int $size = 10, int $eccLevel = self::ECC_M)
    {
        $this->data = $data;
        $this->moduleSize = max(1, $size);
        $this->eccLevel = ($eccLevel >= 0 && $eccLevel <= 3) ? $eccLevel : self::ECC_M;
        $this->generateMatrix();
    }

    /**
     * Build the full QR matrix: pick a version, lay out function patterns,
     * encode data with Reed-Solomon, place the bit stream, and apply the
     * best mask along with format/version information.
     */
    private function generateMatrix(): void
    {
        $this->version = $this->chooseVersion();
        $this->matrixSize = 17 + 4 * $this->version;

        $this->matrix = array_fill(0, $this->matrixSize, array_fill(0, $this->matrixSize, 0));
        $this->reserved = array_fill(0, $this->matrixSize, array_fill(0, $this->matrixSize, false));

        $this->placeFinderPatterns();
        $this->placeSeparators();
        $this->placeTimingPatterns();
        $this->placeAlignmentPatterns();
        $this->placeDarkModule();
        $this->reserveFormatArea();
        if ($this->version >= 7) {
            $this->placeVersionInfo();
        }

        $codewords = $this->buildCodewords();
        $bitStream = $this->codewordsToBitStream($codewords);
        $this->placeData($bitStream);

        $maskNum = $this->selectBestMask();
        $this->applyMask($maskNum);
        $this->placeFormatInfo($maskNum);
    }

    /**
     * Pick the smallest version (1-40) that can hold the payload at the
     * configured ECC level.
     */
    private function chooseVersion(): int
    {
        $dataLen = strlen($this->data);
        for ($v = 1; $v <= 40; $v++) {
            $charCountBits = $v <= 9 ? 8 : 16;
            $bitsRequired = 4 + $charCountBits + 8 * $dataLen;
            [, $g1B, $g1D, $g2B, $g2D] = self::EC_TABLE[$v - 1][$this->eccLevel];
            $capacityBits = ($g1B * $g1D + $g2B * $g2D) * 8;
            if ($bitsRequired <= $capacityBits) {
                return $v;
            }
        }
        throw new RuntimeException('Payload exceeds maximum QR code capacity (version 40).');
    }

    /**
     * Build the interleaved data + EC codeword sequence per ISO/IEC 18004 sec. 8.6.
     *
     * @return int[] Sequence of bytes ready for module placement.
     * @throws RuntimeException if the data cannot be encoded in the chosen version/ECC level.
     */
    private function buildCodewords(): array
    {
        [$ecPerBlock, $g1Blocks, $g1Data, $g2Blocks, $g2Data] = self::EC_TABLE[$this->version - 1][$this->eccLevel];
        $totalDataCodewords = $g1Blocks * $g1Data + $g2Blocks * $g2Data;

        // Build raw bit string, then convert to a padded byte sequence.
        $bits = $this->buildRawBitString($totalDataCodewords);
        $bytes = self::padToCodewords($bits, $totalDataCodewords);

        // Split into blocks per the EC table and compute Reed-Solomon EC codewords.
        $dataBlocks = [];
        $ecBlocks = [];
        $offset = 0;
        for ($i = 0; $i < $g1Blocks; $i++) {
            $block = array_slice($bytes, $offset, $g1Data);
            $offset += $g1Data;
            $dataBlocks[] = $block;
            $ecBlocks[] = self::reedSolomonEncode($block, $ecPerBlock);
        }
        for ($i = 0; $i < $g2Blocks; $i++) {
            $block = array_slice($bytes, $offset, $g2Data);
            $offset += $g2Data;
            $dataBlocks[] = $block;
            $ecBlocks[] = self::reedSolomonEncode($block, $ecPerBlock);
        }

        $maxDataLen = max($g1Data, $g2Data);
        return self::interleaveBlocks($dataBlocks, $ecBlocks, $maxDataLen, $ecPerBlock);
    }

    /**
     * Build the raw bit string for the payload in byte mode:
     * mode indicator → character count → data bytes → terminator → byte-boundary padding.
     *
     * @param  int    $totalDataCodewords  Total data capacity in codewords for the chosen version/ECC.
     * @return string                      Bit string aligned to a byte boundary.
     */
    private function buildRawBitString(int $totalDataCodewords): string
    {
        $bits = '';

        // Mode indicator (byte mode = 0100).
        $bits .= str_pad(decbin(self::MODE_BYTE), 4, '0', STR_PAD_LEFT);

        // Character count indicator (8 bits for v1-9, 16 bits for v10+ in byte mode).
        $charCountBits = $this->version <= 9 ? 8 : 16;
        $bits .= str_pad(decbin(strlen($this->data)), $charCountBits, '0', STR_PAD_LEFT);

        // Payload bytes, each encoded as 8 bits MSB-first.
        $dataLen = strlen($this->data);
        for ($i = 0; $i < $dataLen; $i++) {
            $bits .= str_pad(decbin(ord($this->data[$i])), 8, '0', STR_PAD_LEFT);
        }

        // Terminator: up to 4 zero bits (fewer if it would exceed capacity).
        $totalDataBits = $totalDataCodewords * 8;
        $terminatorLen = min(4, $totalDataBits - strlen($bits));
        if ($terminatorLen > 0) {
            $bits .= str_repeat('0', $terminatorLen);
        }

        // Pad to the next byte boundary.
        $bitsLen = strlen($bits);
        if ($bitsLen % 8 !== 0) {
            $bits .= str_repeat('0', 8 - ($bitsLen % 8));
        }

        return $bits;
    }

    /**
     * Convert a bit string to a byte array and pad with alternating 0xEC / 0x11
     * bytes until the target codeword count is reached.
     *
     * @param  string $bits               Byte-aligned bit string.
     * @param  int    $totalDataCodewords Target number of data codewords.
     * @return int[]                      Byte array of length $totalDataCodewords.
     */
    private static function padToCodewords(string $bits, int $totalDataCodewords): array
    {
        // Convert bit string to bytes.
        $bytes = [];
        $bitsLen = strlen($bits);
        for ($i = 0; $i < $bitsLen; $i += 8) {
            $bytes[] = (int) bindec(substr($bits, $i, 8));
        }

        // Fill remaining capacity with alternating pad bytes 0xEC, 0x11.
        $padIdx = 0;
        while (count($bytes) < $totalDataCodewords) {
            $bytes[] = self::PAD_BYTES[$padIdx % 2];
            $padIdx++;
        }

        return $bytes;
    }

    /**
     * Interleave data and EC codewords from multiple blocks into a single flat sequence.
     *
     * Data codewords are taken column-by-column across all blocks; EC codewords follow
     * in the same order (every block always has exactly $ecPerBlock EC codewords).
     *
     * @param  int[][] $dataBlocks   Array of data-codeword blocks.
     * @param  int[][] $ecBlocks     Corresponding EC-codeword blocks.
     * @param  int     $maxDataLen   Length of the longest data block.
     * @param  int     $ecPerBlock   Number of EC codewords per block.
     * @return int[]                 Final interleaved codeword sequence.
     */
    private static function interleaveBlocks(array $dataBlocks, array $ecBlocks, int $maxDataLen, int $ecPerBlock): array
    {
        $result = [];

        // Interleave data codewords: take the i-th codeword from each block in turn.
        for ($i = 0; $i < $maxDataLen; $i++) {
            foreach ($dataBlocks as $block) {
                if (isset($block[$i])) {
                    $result[] = $block[$i];
                }
            }
        }

        // Interleave EC codewords: every block contributes exactly $ecPerBlock words.
        for ($i = 0; $i < $ecPerBlock; $i++) {
            foreach ($ecBlocks as $block) {
                $result[] = $block[$i];
            }
        }

        return $result;
    }

    /**
     * Convert the final codeword sequence into a bit string and append the
     * required version-specific remainder bits.
     *
     * @param int[] $codewords Sequence of data + EC codewords to be placed in the matrix.
     * @return string Bit string ready for placement in the QR matrix (including remainder bits).
     */
    private function codewordsToBitStream(array $codewords): string
    {
        $bits = '';
        foreach ($codewords as $byte) {
            $bits .= str_pad(decbin($byte), 8, '0', STR_PAD_LEFT);
        }
        $bits .= str_repeat('0', self::REMAINDER_BITS[$this->version - 1]);
        return $bits;
    }

    /**
     * Place the three 7x7 finder patterns at the top-left, top-right, and bottom-left.
     * 
     * @return void
     */
    private function placeFinderPatterns(): void
    {
        $size = $this->matrixSize;
        $corners = [[0, 0], [0, $size - 7], [$size - 7, 0]];
        foreach ($corners as [$row, $col]) {
            for ($r = 0; $r < 7; $r++) {
                for ($c = 0; $c < 7; $c++) {
                    $isOuter = ($r === 0 || $r === 6 || $c === 0 || $c === 6);
                    $isInner = ($r >= 2 && $r <= 4 && $c >= 2 && $c <= 4);
                    $this->matrix[$row + $r][$col + $c] = ($isOuter || $isInner) ? 1 : 0;
                    $this->reserved[$row + $r][$col + $c] = true;
                }
            }
        }
    }

    /**
     * Reserve the 1-module-wide white separator that surrounds each finder pattern.
     */
    private function placeSeparators(): void
    {
        $size = $this->matrixSize;
        // Top-left finder separator.
        for ($i = 0; $i < 8; $i++) {
            $this->setReservedLight(7, $i);
            $this->setReservedLight($i, 7);
        }
        // Top-right finder separator.
        for ($i = 0; $i < 8; $i++) {
            $this->setReservedLight(7, $size - 1 - $i);
            $this->setReservedLight($i, $size - 8);
        }
        // Bottom-left finder separator.
        for ($i = 0; $i < 8; $i++) {
            $this->setReservedLight($size - 8, $i);
            $this->setReservedLight($size - 1 - $i, 7);
        }
    }

    /**
     * Lay down the horizontal/vertical timing patterns (alternating dark/light) along row 6 and column 6.
     */
    private function placeTimingPatterns(): void
    {
        $size = $this->matrixSize;
        for ($i = 8; $i < $size - 8; $i++) {
            $value = ($i % 2 === 0) ? 1 : 0;
            $this->matrix[6][$i] = $value;
            $this->reserved[6][$i] = true;
            $this->matrix[$i][6] = $value;
            $this->reserved[$i][6] = true;
        }
    }

    /**
     * Place every alignment pattern that does not overlap a finder pattern.
     */
    private function placeAlignmentPatterns(): void
    {
        $positions = self::ALIGNMENT_POSITIONS[$this->version - 1];
        $count = count($positions);
        if ($count === 0) {
            return;
        }
        foreach ($positions as $i => $row) {
            foreach ($positions as $j => $col) {
                // Skip the three corners that overlap with finder patterns.
                if (
                    ($i === 0 && $j === 0)
                    || ($i === 0 && $j === $count - 1)
                    || ($i === $count - 1 && $j === 0)
                ) {
                    continue;
                }
                $this->placeAlignmentPattern($row - 2, $col - 2);
            }
        }
    }

    /**
     * Place a single 5x5 alignment pattern with its top-left corner at ($row, $col).
     * 
     * @param int $row Row index of the top-left corner of the alignment pattern.
     * @param int $col Column index of the top-left corner of the alignment pattern.
     */
    private function placeAlignmentPattern(int $row, int $col): void
    {
        for ($r = 0; $r < 5; $r++) {
            for ($c = 0; $c < 5; $c++) {
                $isOuter = ($r === 0 || $r === 4 || $c === 0 || $c === 4);
                $isCenter = ($r === 2 && $c === 2);
                $this->matrix[$row + $r][$col + $c] = ($isOuter || $isCenter) ? 1 : 0;
                $this->reserved[$row + $r][$col + $c] = true;
            }
        }
    }

    /**
     * Place the mandatory dark module at coordinate (4 * version + 9, 8).
     */
    private function placeDarkModule(): void
    {
        $row = 4 * $this->version + 9;
        $this->matrix[$row][8] = 1;
        $this->reserved[$row][8] = true;
    }

    /**
     * Mark every cell that will hold format information so that data placement
     * skips them. The actual format bits are written later, once the mask is
     * chosen.
     */
    private function reserveFormatArea(): void
    {
        $size = $this->matrixSize;
        // L-shape around the top-left finder. Skip column / row 6 (timing pattern).
        for ($i = 0; $i < 9; $i++) {
            if ($i !== 6) {
                $this->setReservedLight(8, $i);
                $this->setReservedLight($i, 8);
            }
        }
        // Strip along row 8 next to the top-right finder.
        for ($i = $size - 8; $i < $size; $i++) {
            $this->setReservedLight(8, $i);
        }
        // Strip along column 8 next to the bottom-left finder.
        for ($i = $size - 7; $i < $size; $i++) {
            $this->setReservedLight($i, 8);
        }
    }

    /**
     * Reserve a cell as a function pattern carrying a light module by default.
     * 
     * @param int $row Row index of the cell to reserve.
     * @param int $col Column index of the cell to reserve.
     */
    private function setReservedLight(int $row, int $col): void
    {
        $this->matrix[$row][$col] = 0;
        $this->reserved[$row][$col] = true;
    }

    /**
     * Place data and EC bits in the canonical zigzag order: column pairs from
     * the bottom-right going left, alternating up/down, skipping column 6.
     * 
     * @param string $bits Bit string of the full data + EC payload to place in the matrix.
     */
    private function placeData(string $bits): void
    {
        $size = $this->matrixSize;
        $bitIdx = 0;
        $bitsLen = strlen($bits);
        $col = $size - 1;
        $upward = true;

        while ($col > 0) {
            // The vertical timing column (column 6) is bypassed.
            if ($col === 6) {
                $col = 5;
            }
            for ($i = 0; $i < $size; $i++) {
                $row = $upward ? ($size - 1 - $i) : $i;
                for ($c = 0; $c < 2; $c++) {
                    $cc = $col - $c;
                    if (!$this->reserved[$row][$cc]) {
                        $bit = $bitIdx < $bitsLen ? ($bits[$bitIdx] === '1' ? 1 : 0) : 0;
                        $this->matrix[$row][$cc] = $bit;
                        $bitIdx++;
                    }
                }
            }
            $col -= 2;
            $upward = !$upward;
        }
    }

    /**
     * Try every mask pattern, score it, and return the index of the best one.
     * The matrix is left in its unmasked state on return.
     * 
     * @return int Index of the best mask pattern (0-7).
     */
    private function selectBestMask(): int
    {
        $bestMask = 0;
        $bestScore = PHP_INT_MAX;
        $original = $this->matrix;
        for ($m = 0; $m < 8; $m++) {
            $this->matrix = $original;
            $this->applyMask($m);
            $this->placeFormatInfo($m);
            $score = $this->scorePenalty();
            if ($score < $bestScore) {
                $bestScore = $score;
                $bestMask = $m;
            }
        }
        $this->matrix = $original;
        return $bestMask;
    }

    /**
     * XOR each non-reserved module with the given mask pattern.
     * 
     * @param int $maskNum Mask pattern index (0-7).
     */
    private function applyMask(int $maskNum): void
    {
        $size = $this->matrixSize;
        for ($r = 0; $r < $size; $r++) {
            for ($c = 0; $c < $size; $c++) {
                if (!$this->reserved[$r][$c] && self::maskCondition($maskNum, $r, $c)) {
                    $this->matrix[$r][$c] ^= 1;
                }
            }
        }
    }

    /**
     * Evaluate one of the eight standard mask conditions for the given coordinate.
     * 
     * @param int $maskNum Mask pattern index (0-7).
     * @param int $row     Row index of the module.
     * @param int $col     Column index of the module.
     * @return bool True if the mask condition is met and the module should be flipped.
     */
    private static function maskCondition(int $maskNum, int $row, int $col): bool
    {
        return match ($maskNum) {
            0 => ($row + $col) % 2 === 0,
            1 => $row % 2 === 0,
            2 => $col % 3 === 0,
            3 => ($row + $col) % 3 === 0,
            4 => (intdiv($row, 2) + intdiv($col, 3)) % 2 === 0,
            5 => (($row * $col) % 2) + (($row * $col) % 3) === 0,
            6 => ((($row * $col) % 2) + (($row * $col) % 3)) % 2 === 0,
            7 => ((($row + $col) % 2) + (($row * $col) % 3)) % 2 === 0,
            default => false,
        };
    }

    /**
     * Compute the total mask penalty score using the four standard rules
     * (consecutive runs, 2x2 blocks, finder-like patterns, dark/light balance).
     * 
     * @return int Total penalty score (lower is better).
     */
    private function scorePenalty(): int
    {
        $size = $this->matrixSize;

        return $this->scorePenaltyRun($size)
            + $this->scorePenaltyBlock($size)
            + $this->scorePenaltyFinder($size)
            + $this->scorePenaltyBalance($size);
    }

    /**
     * Rule 1: Penalty for runs of 5 or more identical modules in any row or column.
     * Each qualifying run of length n contributes (3 + n − 5) points.
     *
     * @param  int $size Matrix dimension.
     * @return int       Penalty score.
     */
    private function scorePenaltyRun(int $size): int
    {
        $score = 0;

        // Scan rows.
        for ($r = 0; $r < $size; $r++) {
            $runColor = -1;
            $runLen = 0;
            for ($c = 0; $c < $size; $c++) {
                if ($this->matrix[$r][$c] === $runColor) {
                    $runLen++;
                } else {
                    if ($runLen >= 5) {
                        $score += 3 + ($runLen - 5);
                    }
                    $runColor = $this->matrix[$r][$c];
                    $runLen = 1;
                }
            }
            if ($runLen >= 5) {
                $score += 3 + ($runLen - 5);
            }
        }

        // Scan columns.
        for ($c = 0; $c < $size; $c++) {
            $runColor = -1;
            $runLen = 0;
            for ($r = 0; $r < $size; $r++) {
                if ($this->matrix[$r][$c] === $runColor) {
                    $runLen++;
                } else {
                    if ($runLen >= 5) {
                        $score += 3 + ($runLen - 5);
                    }
                    $runColor = $this->matrix[$r][$c];
                    $runLen = 1;
                }
            }
            if ($runLen >= 5) {
                $score += 3 + ($runLen - 5);
            }
        }

        return $score;
    }

    /**
     * Rule 2: Penalty for 2×2 blocks of identical-color modules (+3 per block).
     *
     * @param  int $size Matrix dimension.
     * @return int       Penalty score.
     */
    private function scorePenaltyBlock(int $size): int
    {
        $score = 0;
        for ($r = 0; $r < $size - 1; $r++) {
            for ($c = 0; $c < $size - 1; $c++) {
                $v = $this->matrix[$r][$c];
                if (
                    $v === $this->matrix[$r][$c + 1]
                    && $v === $this->matrix[$r + 1][$c]
                    && $v === $this->matrix[$r + 1][$c + 1]
                ) {
                    $score += 3;
                }
            }
        }
        return $score;
    }

    /**
     * Rule 3: Penalty for finder-like 1:1:3:1:1 patterns with surrounding quiet zone (+40 each).
     * Both the pattern and its mirror are checked in rows and columns.
     *
     * @param  int $size Matrix dimension.
     * @return int       Penalty score.
     */
    private function scorePenaltyFinder(int $size): int
    {
        $score = 0;
        $patternA = [1, 0, 1, 1, 1, 0, 1, 0, 0, 0, 0];
        $patternB = [0, 0, 0, 0, 1, 0, 1, 1, 1, 0, 1];

        // Horizontal scan.
        for ($r = 0; $r < $size; $r++) {
            for ($c = 0; $c <= $size - 11; $c++) {
                $matchA = $matchB = true;
                for ($i = 0; $i < 11; $i++) {
                    $cell = $this->matrix[$r][$c + $i];
                    if ($cell !== $patternA[$i]) {
                        $matchA = false;
                    }
                    if ($cell !== $patternB[$i]) {
                        $matchB = false;
                    }
                    if (!$matchA && !$matchB) {
                        break;
                    }
                }
                if ($matchA) {
                    $score += 40;
                }
                if ($matchB) {
                    $score += 40;
                }
            }
        }

        // Vertical scan.
        for ($c = 0; $c < $size; $c++) {
            for ($r = 0; $r <= $size - 11; $r++) {
                $matchA = $matchB = true;
                for ($i = 0; $i < 11; $i++) {
                    $cell = $this->matrix[$r + $i][$c];
                    if ($cell !== $patternA[$i]) {
                        $matchA = false;
                    }
                    if ($cell !== $patternB[$i]) {
                        $matchB = false;
                    }
                    if (!$matchA && !$matchB) {
                        break;
                    }
                }
                if ($matchA) {
                    $score += 40;
                }
                if ($matchB) {
                    $score += 40;
                }
            }
        }

        return $score;
    }

    /**
     * Rule 4: Penalty for deviation from a 50/50 dark-to-light ratio, in 5% steps (+10 per step).
     *
     * @param  int $size Matrix dimension.
     * @return int       Penalty score.
     */
    private function scorePenaltyBalance(int $size): int
    {
        $darkCount = 0;
        $total = $size * $size;

        for ($r = 0; $r < $size; $r++) {
            for ($c = 0; $c < $size; $c++) {
                if ($this->matrix[$r][$c] === 1) {
                    $darkCount++;
                }
            }
        }

        $percentage = ($darkCount * 100) / $total;
        $deviation = (int) (abs($percentage - 50) / 5);

        return 10 * $deviation;
    }

    /**
     * Encode the chosen mask number and ECC level as a 15-bit BCH(15,5) codeword
     * and write both copies into the matrix.
     * 
     * @param int $maskNum Mask pattern index (0-7)
     * @return void
     */
    private function placeFormatInfo(int $maskNum): void
    {
        $eccBits = self::ECC_TO_FORMAT_BITS[$this->eccLevel];
        $data = ($eccBits << 3) | $maskNum;

        // BCH(15,5) using G(x) = x^10 + x^8 + x^5 + x^4 + x^2 + x + 1 (= 0x537).
        $bch = $data << 10;
        for ($i = 4; $i >= 0; $i--) {
            if ((($bch >> ($i + 10)) & 1) === 1) {
                $bch ^= self::FORMAT_GEN_POLY << $i;
            }
        }
        $code = (($data << 10) | ($bch & 0x3FF)) ^ self::FORMAT_MASK;

        $size = $this->matrixSize;

        // First copy (top-left finder area). Bit 0 (LSB) starts at (0, 8) and
        // bit 14 (MSB) ends at (8, 0); column 6 (timing) is skipped vertically
        // and column 6 is skipped horizontally as well.
        for ($i = 0; $i < 6; $i++) {
            $this->matrix[$i][8] = ($code >> $i) & 1;
        }
        $this->matrix[7][8] = ($code >> 6) & 1;
        $this->matrix[8][8] = ($code >> 7) & 1;
        $this->matrix[8][7] = ($code >> 8) & 1;
        for ($i = 9; $i < 15; $i++) {
            $this->matrix[8][14 - $i] = ($code >> $i) & 1;
        }

        // Second copy. Bits 0..7 fill row 8 from col size-1 down to col size-8,
        // bits 8..14 fill col 8 from row size-7 down to row size-1.
        for ($i = 0; $i < 8; $i++) {
            $this->matrix[8][$size - 1 - $i] = ($code >> $i) & 1;
        }
        for ($i = 8; $i < 15; $i++) {
            $this->matrix[$size - 15 + $i][8] = ($code >> $i) & 1;
        }
    }

    /**
     * For versions 7 and above, encode the version as an 18-bit BCH(18,6)
     * codeword and place both copies near the top-right and bottom-left finders.
     * 
     * @return void
     */
    private function placeVersionInfo(): void
    {
        $version = $this->version;
        // Bit-by-bit polynomial division in GF(2): feed the 6 version bits MSB-first
        // followed by 12 zero bits, reducing modulo G(x) = 0x1F25 whenever the
        // working register's degree-12 bit becomes set.
        $bch = 0;
        for ($i = 17; $i >= 0; $i--) {
            $bit = ($i >= 12) ? (($version >> ($i - 12)) & 1) : 0;
            $bch = ($bch << 1) | $bit;
            if ((($bch >> 12) & 1) === 1) {
                $bch ^= self::VERSION_GEN_POLY;
            }
        }
        $code = ($version << 12) | ($bch & 0xFFF);

        $size = $this->matrixSize;
        for ($i = 0; $i < 18; $i++) {
            $bit = ($code >> $i) & 1;
            $a = $size - 11 + ($i % 3);
            $b = intdiv($i, 3);
            // Bottom-left copy: 3x6 block left of the bottom-left finder.
            $this->matrix[$a][$b] = $bit;
            $this->reserved[$a][$b] = true;
            // Top-right copy: 6x3 block above the top-right finder.
            $this->matrix[$b][$a] = $bit;
            $this->reserved[$b][$a] = true;
        }
    }

    /**
     * Lazily initialise the GF(256) log/exp tables used by Reed-Solomon encoding.
     * The primitive polynomial is 0x11D (x^8 + x^4 + x^3 + x^2 + 1).
     * 
     * @return void
     */
    private static function initGaloisTables(): void
    {
        if (self::$gfExp !== null) {
            return;
        }
        $exp = array_fill(0, 512, 0);
        $log = array_fill(0, 256, 0);
        $x = 1;
        for ($i = 0; $i < 255; $i++) {
            $exp[$i] = $x;
            $log[$x] = $i;
            $x <<= 1;
            if ($x >= 256) {
                $x ^= 0x11D;
            }
        }
        for ($i = 255; $i < 512; $i++) {
            $exp[$i] = $exp[$i - 255];
        }
        self::$gfExp = $exp;
        self::$gfLog = $log;
    }

    /**
     * Multiply two GF(256) field elements.
     * 
     * @param int $a First operand (0-255).
     * @param int $b Second operand (0-255).
     * @return int Product (0-255).
     */
    private static function gfMultiply(int $a, int $b): int
    {
        if ($a === 0 || $b === 0) {
            return 0;
        }
        return self::$gfExp[(self::$gfLog[$a] + self::$gfLog[$b]) % 255];
    }

    /**
     * Build a Reed-Solomon generator polynomial of the requested degree.
     *
     * @param int $degree Number of EC codewords to generate (1-30).
     * 
     * @return int[] Coefficients in descending order; the leading coefficient is always 1.
     */
    private static function rsGeneratorPoly(int $degree): array
    {
        self::initGaloisTables();
        $poly = [1];
        for ($i = 0; $i < $degree; $i++) {
            $next = array_fill(0, count($poly) + 1, 0);
            for ($j = 0; $j < count($poly); $j++) {
                $next[$j] ^= $poly[$j];
                $next[$j + 1] ^= self::gfMultiply($poly[$j], self::$gfExp[$i]);
            }
            $poly = $next;
        }
        return $poly;
    }

    /**
     * Compute $ecLen Reed-Solomon error-correction codewords for the given block.
     *
     * @param int[] $block Data codewords for one block.
     * @return int[] EC codewords for the block, in order. The number of codewords is determined
     */
    private static function reedSolomonEncode(array $block, int $ecLen): array
    {
        self::initGaloisTables();
        $generator = self::rsGeneratorPoly($ecLen);
        $remainder = array_fill(0, $ecLen, 0);
        foreach ($block as $byte) {
            $factor = $byte ^ $remainder[0];
            array_shift($remainder);
            $remainder[] = 0;
            if ($factor !== 0) {
                for ($i = 0; $i < $ecLen; $i++) {
                    $remainder[$i] ^= self::gfMultiply($generator[$i + 1], $factor);
                }
            }
        }
        return $remainder;
    }

    /**
     * Render the QR code into a GD image, including the mandatory 4-module quiet zone.
     *
     * @return GdImage|resource|false The generated image resource, or false on failure.
     */
    public function generateImage(): mixed
    {
        $quietZone = 4;
        $totalModules = $this->matrixSize + 2 * $quietZone;
        $imagePixels = $totalModules * $this->moduleSize;

        $image = imagecreate($imagePixels, $imagePixels);
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);

        // Background (also serves as the quiet zone).
        imagefilledrectangle($image, 0, 0, $imagePixels - 1, $imagePixels - 1, $white);

        for ($y = 0; $y < $this->matrixSize; $y++) {
            for ($x = 0; $x < $this->matrixSize; $x++) {
                if ($this->matrix[$y][$x] === 1) {
                    $px = ($x + $quietZone) * $this->moduleSize;
                    $py = ($y + $quietZone) * $this->moduleSize;
                    imagefilledrectangle($image, $px, $py, $px + $this->moduleSize - 1, $py + $this->moduleSize - 1, $black);
                }
            }
        }

        return $image;
    }

    /**
     * Return the final size of each side of the QR matrix (number of modules).
     * 
     * @return int Size of the QR matrix in modules (e.g. 21 for version 1, 25 for version 2, etc.).
     */
    public function getMatrixSize(): int
    {
        return $this->matrixSize;
    }

    /**
     * Return the chosen QR version (1-40).
     * 
     * @return int QR version number (1-40).
     */
    public function getVersion(): int
    {
        return $this->version;
    }

    /**
     * Output the generated QR code image directly to the browser as PNG.
     *
     * @return bool
     */
    public function outputImage()
    {
        $image = $this->generateImage();
        return ImageHandler::output($image);
    }

    /**
     * Save the generated QR code image to the given path as PNG.
     *
     * @param string $filePath Path to save the PNG file to.
     * @return bool
     */
    public function saveImage(string $filePath): bool
    {
        $image = $this->generateImage();
        return ImageHandler::create("image/png", $image, $filePath);
    }

    /**
     * Rebuild the function-pattern reserved-cell map for a given QR size and
     * version.  This mirrors the encoder's instance-level logic but operates
     * purely on the matrix dimension and version number.
     *
     * @param  int    $size     Matrix dimension (17 + 4 * $version).
     * @param  int    $version  QR version number (1-40).
     * @return bool[][]         $size × $size boolean map (true = reserved).
     */
    private static function buildReservedMapStatic(int $size, int $version): array
    {
        $res = array_fill(0, $size, array_fill(0, $size, false));

        // Finder patterns (7×7) at the three corners.
        $corners = [[0, 0], [0, $size - 7], [$size - 7, 0]];
        foreach ($corners as [$br, $bc]) {
            for ($r = 0; $r < 7; $r++) {
                for ($c = 0; $c < 7; $c++) {
                    $res[$br + $r][$bc + $c] = true;
                }
            }
        }

        // Separators (1-module white border around each finder).
        for ($i = 0; $i < 8; $i++) {
            // Top-left separator.
            $res[7][$i] = true;
            $res[$i][7] = true;
            // Top-right separator.
            $res[7][$size - 1 - $i] = true;
            $res[$i][$size - 8] = true;
            // Bottom-left separator.
            $res[$size - 8][$i] = true;
            $res[$size - 1 - $i][7] = true;
        }

        // Timing patterns (row 6 and column 6).
        for ($i = 8; $i < $size - 8; $i++) {
            $res[6][$i] = true;
            $res[$i][6] = true;
        }

        // Dark module.
        $res[4 * $version + 9][8] = true;

        // Format information strips.
        for ($i = 0; $i < 9; $i++) {
            if ($i !== 6) {
                $res[8][$i] = true;   // top-left horizontal
                $res[$i][8] = true;   // top-left vertical
            }
        }
        for ($i = $size - 8; $i < $size; $i++) {
            $res[8][$i] = true;   // top-right horizontal
        }
        for ($i = $size - 7; $i < $size; $i++) {
            $res[$i][8] = true;   // bottom-left vertical
        }

        // Alignment patterns (5×5, centred on each position pair, skipping
        // positions that would overlap with a finder pattern).
        $positions = self::ALIGNMENT_POSITIONS[$version - 1];
        $cnt = count($positions);
        foreach ($positions as $pi => $arow) {
            foreach ($positions as $pj => $acol) {
                if (
                    ($pi === 0 && $pj === 0)
                    || ($pi === 0 && $pj === $cnt - 1)
                    || ($pi === $cnt - 1 && $pj === 0)
                ) {
                    continue;
                }
                for ($r = -2; $r <= 2; $r++) {
                    for ($c = -2; $c <= 2; $c++) {
                        $res[$arow + $r][$acol + $c] = true;
                    }
                }
            }
        }

        // Version information (6×3 and 3×6 blocks, versions 7+).
        if ($version >= 7) {
            for ($i = 0; $i < 18; $i++) {
                $a = $size - 11 + ($i % 3);
                $b = intdiv($i, 3);
                $res[$a][$b] = true;   // bottom-left block
                $res[$b][$a] = true;   // top-right block
            }
        }

        return $res;
    }

    /**
     * Decode the first QR code found in an image file.
     *
     * Supported image formats: PNG, JPEG, GIF, BMP, WebP
     * (requires the corresponding GD compile-time support).
     *
     * The decoder pipeline:
     *   1. Load and binarize the image with Otsu's global threshold.
     *   2. Detect finder patterns via horizontal 1:1:3:1:1 run scanning plus
     *      vertical and diagonal cross-checks.
     *   3. Identify TL / TR / BL roles from the three finder centres.
     *   4. Estimate the QR version from the inter-finder distance.
     *   5. Compute a perspective homography (DLT) and sample the QR grid.
     *   6. Read and BCH-decode the 15-bit format information.
     *   7. Reconstruct the reserved-cell map and extract + unmask the data bits.
     *   8. Parse the bit stream (numeric / alphanumeric / byte / kanji modes).
     *
     * @param  string       $imagePath  Absolute or relative path to the image file.
     * @return string|false             Decoded payload on success, false on failure.
     */
    public static function readImage(string $imagePath): string|false
    {
        // ── Step 1: Load and binarize ─────────────────────────────────────────
        $gd = self::loadGdImage($imagePath);
        if ($gd === false) {
            return false;
        }

        $w = imagesx($gd);
        $h = imagesy($gd);
        $binary = self::binarizeGd($gd, $w, $h);
        imagedestroy($gd);

        // ── Step 2: Finder-pattern detection ──────────────────────────────────
        $finders = self::detectFinderPatterns($binary, $w, $h);
        if (count($finders) < 3) {
            return false;
        }

        // ── Step 3: Assign TL / TR / BL roles ────────────────────────────────
        $corners = self::identifyFinderCorners($finders);
        if ($corners === false) {
            return false;
        }
        [$tl, $tr, $bl] = $corners;   // each is [x, y] in pixel space

        // ── Step 4: Estimate version ──────────────────────────────────────────
        $modSize = self::estimateModuleSize($tl, $tr, $bl);
        if ($modSize < 1.0) {
            return false;
        }
        $version = self::estimateVersion($tl, $tr, $bl, $modSize);
        if ($version < 1 || $version > 40) {
            return false;
        }
        $qrSize = 17 + 4 * $version;

        // ── Step 5: Perspective homography and grid sampling ──────────────────
        // Estimate the bottom-right corner via the parallelogram rule.
        $br = [
            $tr[0] + $bl[0] - $tl[0],
            $tr[1] + $bl[1] - $tl[1],
        ];
        // Finder centres are located at module coordinates (3.5, 3.5) etc.,
        // because each finder spans modules 0-6 and is centred at module 3.
        $H = self::computeHomographyDLT(
            [
                [3.5, 3.5],
                [$qrSize - 3.5, 3.5],
                [3.5, $qrSize - 3.5],
                [$qrSize - 3.5, $qrSize - 3.5],
            ],
            [$tl, $tr, $bl, $br]
        );
        $qr = self::sampleQRMatrix($binary, $w, $h, $H, $qrSize);

        // ── Step 6: Format information ────────────────────────────────────────
        $fmt = self::decodeFormatInfo($qr, $qrSize);
        if ($fmt === false) {
            return false;
        }
        [$eccLevel, $maskNum] = $fmt;

        // ── Step 7: Extract and unmask data bits ──────────────────────────────
        $res = self::buildReservedMapStatic($qrSize, $version);
        $bits = self::extractDataBits($qr, $qrSize, $res, $maskNum);
        $codewords = self::bitStreamToCodewords($bits);
        $dataCodewords = self::deinterleaveDataCodewords($codewords, $version, $eccLevel);
        if ($dataCodewords === false) {
            return false;
        }

        // ── Step 8: Parse the bit stream ──────────────────────────────────────
        $payloadBits = '';
        foreach ($dataCodewords as $codeword) {
            $payloadBits .= str_pad(decbin($codeword), 8, '0', STR_PAD_LEFT);
        }

        return self::parseBitStream($payloadBits, $version);
    }

    /**
     * Return true when five consecutive run lengths match the 1:1:3:1:1 ratio
     * within a ±50 % per-unit tolerance.
     *
     * @param  int  $a  First run length (outer dark).
     * @param  int  $b  Second run length (inner light).
     * @param  int  $c  Third run length (centre dark, should be ≈ 3 units).
     * @param  int  $d  Fourth run length (inner light).
     * @param  int  $e  Fifth run length (outer dark).
     * @return bool
     */
    private static function isFinderRunRatio(int $a, int $b, int $c, int $d, int $e): bool
    {
        $total = $a + $b + $c + $d + $e;
        if ($total < 7) {
            return false;
        }
        $u = $total / 7.0;      // expected length of one module
        $tol = $u / 2.0;          // 50 % tolerance
        return abs($a - $u) < $tol
            && abs($b - $u) < $tol
            && abs($c - 3 * $u) < $tol * 3
            && abs($d - $u) < $tol
            && abs($e - $u) < $tol;
    }

    /**
     * Cross-check a horizontal finder candidate by scanning vertically through
     * its centre column and verifying the 1:1:3:1:1 ratio in that direction.
     *
     * @param  int[][]    $binary          Binarized image.
     * @param  int        $w               Image width.
     * @param  int        $h               Image height.
     * @param  int        $cx              Column of the candidate centre.
     * @param  int        $startY          Row from which the horizontal scan matched.
     * @param  int        $horzCenterRun   Horizontal length of the 3-unit centre run.
     * @return array|false                 [float $cy, float $moduleSize] or false.
     */
    private static function crossCheckVertical(array $binary, int $w, int $h, int $cx, int $startY, int $horzCenterRun): array|false
    {
        // Five state counts: [top dark, top light, center dark, bottom light, bottom dark].
        $sc = [0, 0, 0, 0, 0];

        // Walk upward: count the centre-dark pixels, then the light gap, then the top border.
        $y = $startY;
        while ($y >= 0 && $binary[$y][$cx] === 1) {
            $sc[2]++;
            $y--;
        }
        while ($y >= 0 && $binary[$y][$cx] === 0) {
            $sc[1]++;
            $y--;
        }
        while ($y >= 0 && $binary[$y][$cx] === 1) {
            $sc[0]++;
            $y--;
        }

        // Record where the top-dark border begins so we can compute cy later.
        $topStart = $y + 1;   // first row of the top dark border

        // Walk downward: continue the centre-dark count, then the light gap, then the bottom border.
        $y = $startY + 1;
        while ($y < $h && $binary[$y][$cx] === 1) {
            $sc[2]++;
            $y++;
        }
        while ($y < $h && $binary[$y][$cx] === 0) {
            $sc[3]++;
            $y++;
        }
        while ($y < $h && $binary[$y][$cx] === 1) {
            $sc[4]++;
            $y++;
        }

        if (!self::isFinderRunRatio($sc[0], $sc[1], $sc[2], $sc[3], $sc[4])) {
            return false;
        }

        $modSize = array_sum($sc) / 7.0;

        // Reject if the vertical module-size estimate diverges too much from the horizontal one.
        $horzModSize = $horzCenterRun / 3.0;
        if (abs($modSize - $horzModSize) > $horzModSize * 0.5) {
            return false;
        }

        // Centre row = top of finder + (top-border width) + (top-light width) + half the centre.
        $cy = (float) $topStart + $sc[0] + $sc[1] + $sc[2] / 2.0;
        return [$cy, $modSize];
    }

    /**
     * Quick diagonal cross-check: verify that the finder's 1:1:3:1:1 ratio is
     * also present along the NW-SE diagonal through the candidate centre.
     *
     * @param  int[][]  $binary   Binarized image.
     * @param  int      $w        Image width.
     * @param  int      $h        Image height.
     * @param  int      $cx       Column of the candidate centre.
     * @param  int      $cy       Row of the candidate centre.
     * @param  float    $modSize  Estimated module size.
     * @return bool
     */
    private static function crossCheckDiagonal(array $binary, int $w, int $h, int $cx, int $cy, float $modSize): bool
    {
        $sc = [0, 0, 0, 0, 0];

        // Walk diagonally toward NW: centre dark → light gap → top-left dark border.
        $x = $cx;
        $y = $cy;
        while ($x >= 0 && $y >= 0 && $binary[$y][$x] === 1) {
            $sc[2]++;
            $x--;
            $y--;
        }
        while ($x >= 0 && $y >= 0 && $binary[$y][$x] === 0) {
            $sc[1]++;
            $x--;
            $y--;
        }
        while ($x >= 0 && $y >= 0 && $binary[$y][$x] === 1) {
            $sc[0]++;
            $x--;
            $y--;
        }

        // Walk diagonally toward SE: continue centre dark → light gap → bottom-right dark border.
        $x = $cx + 1;
        $y = $cy + 1;
        while ($x < $w && $y < $h && $binary[$y][$x] === 1) {
            $sc[2]++;
            $x++;
            $y++;
        }
        while ($x < $w && $y < $h && $binary[$y][$x] === 0) {
            $sc[3]++;
            $x++;
            $y++;
        }
        while ($x < $w && $y < $h && $binary[$y][$x] === 1) {
            $sc[4]++;
            $x++;
            $y++;
        }

        return self::isFinderRunRatio($sc[0], $sc[1], $sc[2], $sc[3], $sc[4]);
    }

    /**
     * Cluster nearby raw finder candidates using a running-average strategy.
     *
     * A candidate joins an existing cluster only when it falls within a finder's
     * own footprint of the cluster centre (a finder spans seven modules, so
     * matches separated by more than a couple of modules belong to different
     * features) and their estimated module sizes agree. The accumulated vote
     * count is preserved so callers can rank genuine finders, which collect a
     * vote per scan line, above accidental data matches that collect only a few.
     *
     * @param  array[] $raw  Candidates as [$cx, $cy, $moduleSize].
     * @return array[]       Cluster centres as [cx, cy, moduleSize, voteCount].
     */
    private static function clusterFinderCandidates(array $raw): array
    {
        $groups = [];  // [avgX, avgY, avgModSize, count]
        foreach ($raw as [$x, $y, $ms]) {
            $placed = false;
            foreach ($groups as &$g) {
                $dx = $g[0] - $x;
                $dy = $g[1] - $y;
                $avgMs = ($g[2] + $ms) / 2.0;
                $sizeRatio = ($ms > $g[2]) ? ($ms / $g[2]) : ($g[2] / $ms);
                if (
                    sqrt($dx * $dx + $dy * $dy) < $avgMs * self::FINDER_CLUSTER_RADIUS_MODULES
                    && $sizeRatio <= self::FINDER_CLUSTER_SIZE_RATIO
                ) {
                    // Merge into the existing cluster via incremental mean.
                    $n = $g[3] + 1;
                    $g[0] = ($g[0] * $g[3] + $x) / $n;
                    $g[1] = ($g[1] * $g[3] + $y) / $n;
                    $g[2] = ($g[2] * $g[3] + $ms) / $n;
                    $g[3] = $n;
                    $placed = true;
                    break;
                }
            }
            unset($g);
            if (!$placed) {
                $groups[] = [$x, $y, $ms, 1];
            }
        }

        return array_values($groups);
    }

    /**
     * Assign TL / TR / BL roles to the three detected finder centres.
     *
     * The TL finder is the one at the right angle of the "L" formed by the
     * three patterns, i.e., the vertex opposite the longest side.  TR vs BL
     * is then resolved with a cross product (image y-axis points downward).
     *
     * @param  array[] $finders  Two or more [x, y] finder centres.
     * @return array|false        [$tl, $tr, $bl] (each [x, y]) or false.
     */
    private static function identifyFinderCorners(array $finders): array|false
    {
        if (count($finders) < 3) {
            return false;
        }

        // If more than three candidates, keep the three with the most votes:
        // genuine finders accumulate one vote per scan line, while accidental
        // data matches collect only a handful.
        if (count($finders) > 3) {
            usort($finders, static fn($a, $b) => ($b[3] ?? 0) <=> ($a[3] ?? 0));
            $finders = array_slice($finders, 0, 3);
        }

        [$p0, $p1, $p2] = $finders;

        // Find the pair with the greatest squared distance – that is the hypotenuse.
        $d01 = self::squaredDist($p0, $p1);
        $d02 = self::squaredDist($p0, $p2);
        $d12 = self::squaredDist($p1, $p2);

        // The point opposite the hypotenuse is TL.
        if ($d01 >= $d02 && $d01 >= $d12) {
            $tl = $p2;
            $a = $p0;
            $b = $p1;
        } elseif ($d02 >= $d01 && $d02 >= $d12) {
            $tl = $p1;
            $a = $p0;
            $b = $p2;
        } else {
            $tl = $p0;
            $a = $p1;
            $b = $p2;
        }

        // Cross product (a - tl) × (b - tl): positive means $a is clockwise from $b,
        // which in image-space (y-axis down) means $a is to the right → $a = TR, $b = BL.
        $cross = ($a[0] - $tl[0]) * ($b[1] - $tl[1]) - ($a[1] - $tl[1]) * ($b[0] - $tl[0]);
        if ($cross >= 0) {
            $tr = $a;
            $bl = $b;
        } else {
            $tr = $b;
            $bl = $a;
        }

        // Sanity: TR should have a larger x-coordinate than TL.
        if ($tr[0] < $tl[0]) {
            [$tr, $bl] = [$bl, $tr];
        }

        return [$tl, $tr, $bl];
    }

    /**
     * Return the squared Euclidean distance between two 2-D points.
     *
     * @param  float[] $a  Point [x, y].
     * @param  float[] $b  Point [x, y].
     * @return float
     */
    private static function squaredDist(array $a, array $b): float
    {
        return ($a[0] - $b[0]) ** 2 + ($a[1] - $b[1]) ** 2;
    }

    /**
     * Estimate the size of one QR module (in pixels).
     *
     * Finder detection already measures the module size from the 1:1:3:1:1 run
     * lengths. Reuse that value when available so version estimation remains
     * correct for symbols larger than version 1. When only coordinates are
     * available, keep the legacy geometric fallback for compatibility.
     *
     * @param  float[] $tl  Top-left finder centre [x, y, moduleSize?].
     * @param  float[] $tr  Top-right finder centre [x, y, moduleSize?].
     * @param  float[] $bl  Bottom-left finder centre [x, y, moduleSize?].
     * @return float        Estimated module size in pixels.
     */
    private static function estimateModuleSize(array $tl, array $tr, array $bl): float
    {
        if (isset($tl[2], $tr[2], $bl[2])) {
            return ($tl[2] + $tr[2] + $bl[2]) / 3.0;
        }

        $dtr = sqrt(self::squaredDist($tl, $tr));  // Legacy fallback for coordinate-only callers.
        $dbl = sqrt(self::squaredDist($tl, $bl));
        return ($dtr + $dbl) / 2.0 / 7.0;
    }

    /**
     * Estimate the QR version (1-40) from the number of modules between finder centres.
     *
     * The QR symbol size is (17 + 4 * version) modules; the finder centres are
     * 7 modules from each outer edge and therefore (qrSize - 7) modules apart.
     *
     * @param  float[] $tl       Top-left finder centre.
     * @param  float[] $tr       Top-right finder centre.
     * @param  float[] $bl       Bottom-left finder centre.
     * @param  float   $modSize  Module size in pixels.
     * @return int               Version number, clamped to [1, 40].
     */
    private static function estimateVersion(array $tl, array $tr, array $bl, float $modSize): int
    {
        $dtr = sqrt(self::squaredDist($tl, $tr));
        $dbl = sqrt(self::squaredDist($tl, $bl));
        $modules = ($dtr + $dbl) / 2.0 / $modSize;  // ≈ qrSize - 7
        $version = (int) round(($modules + 7 - 17) / 4);
        return max(1, min(40, $version));
    }


    /**
     * Compute a 3×3 perspective homography from four point correspondences using
     * the Direct Linear Transform (DLT) algorithm.
     *
     * The homography H maps a source point (sx, sy) in module space to a
     * destination point (dx, dy) in pixel space:
     *   λ [dx, dy, 1]^T = H [sx, sy, 1]^T
     *
     * @param  float[][] $src  Four source points [[sx, sy], ...] (module space).
     * @param  float[][] $dst  Four destination points [[dx, dy], ...] (pixel space).
     * @return float[][]       3×3 homography matrix in row-major order.
     */
    private static function computeHomographyDLT(array $src, array $dst): array
    {
        // Build the 8×9 design matrix A from the four point pairs (each pair contributes 2 rows).
        $A = [];
        for ($i = 0; $i < 4; $i++) {
            [$sx, $sy] = $src[$i];
            [$dx, $dy] = $dst[$i];
            $A[] = [$sx, $sy, 1, 0, 0, 0, -$dx * $sx, -$dx * $sy, -$dx];
            $A[] = [0, 0, 0, $sx, $sy, 1, -$dy * $sx, -$dy * $sy, -$dy];
        }

        // Fix h33 = 1 and solve the resulting 8×8 linear system for [h11 … h32].
        $B = [];
        $r = [];
        for ($i = 0; $i < 8; $i++) {
            $B[] = array_slice($A[$i], 0, 8);
            $r[] = -$A[$i][8];
        }

        $h = self::solveLinear8x8($B, $r);
        if ($h === false) {
            // Fallback to identity if the system is singular.
            $h = [1.0, 0.0, 0.0, 0.0, 1.0, 0.0, 0.0, 0.0];
        }

        return [
            [$h[0], $h[1], $h[2]],
            [$h[3], $h[4], $h[5]],
            [$h[6], $h[7], 1.0],
        ];
    }

    /**
     * Solve an 8×8 linear system Ax = b using Gaussian elimination with partial
     * pivoting.
     *
     * @param  float[][] $A  8×8 coefficient matrix.
     * @param  float[]   $b  Right-hand side vector.
     * @return float[]|false Solution vector or false if the matrix is (near-)singular.
     */
    private static function solveLinear8x8(array $A, array $b): array|false
    {
        $n = 8;
        // Form the augmented matrix [A | b].
        for ($i = 0; $i < $n; $i++) {
            $A[$i][] = $b[$i];
        }

        // Forward elimination with partial pivoting.
        for ($col = 0; $col < $n; $col++) {
            // Find the row with the largest absolute value in this column.
            $maxVal = abs($A[$col][$col]);
            $maxRow = $col;
            for ($row = $col + 1; $row < $n; $row++) {
                if (abs($A[$row][$col]) > $maxVal) {
                    $maxVal = abs($A[$row][$col]);
                    $maxRow = $row;
                }
            }
            if ($maxVal < 1e-10) {
                return false;   // Singular matrix.
            }
            // Swap the pivot row to the current position.
            [$A[$col], $A[$maxRow]] = [$A[$maxRow], $A[$col]];

            $pivot = $A[$col][$col];
            for ($row = $col + 1; $row < $n; $row++) {
                $factor = $A[$row][$col] / $pivot;
                for ($k = $col; $k <= $n; $k++) {
                    $A[$row][$k] -= $factor * $A[$col][$k];
                }
            }
        }

        // Back-substitution.
        $x = array_fill(0, $n, 0.0);
        for ($i = $n - 1; $i >= 0; $i--) {
            $x[$i] = $A[$i][$n];
            for ($j = $i + 1; $j < $n; $j++) {
                $x[$i] -= $A[$i][$j] * $x[$j];
            }
            $x[$i] /= $A[$i][$i];
        }
        return $x;
    }

    /**
     * Sample one bit per QR module by projecting each module-centre coordinate
     * through the homography H into the binary image.
     *
     * Module (col, row) centre in module space is (col + 0.5, row + 0.5).
     * The homography maps this to a pixel coordinate (px, py).
     *
     * @param  int[][]   $binary  Binarized source image ($h × $w).
     * @param  int       $w       Image width.
     * @param  int       $h       Image height.
     * @param  float[][] $H       3×3 homography matrix.
     * @param  int       $qrSize  Number of modules per side.
     * @return int[][]            $qrSize × $qrSize sampled QR matrix (1 = dark, 0 = light).
     */
    private static function sampleQRMatrix(array $binary, int $w, int $h, array $H, int $qrSize): array
    {
        $qr = [];
        for ($r = 0; $r < $qrSize; $r++) {
            $row = [];
            for ($c = 0; $c < $qrSize; $c++) {
                // Module centre in module-coordinate space.
                $mx = $c + 0.5;
                $my = $r + 0.5;
                // Project through the homography: [px, py, w'] = H [mx, my, 1]^T.
                $denom = $H[2][0] * $mx + $H[2][1] * $my + $H[2][2];
                if (abs($denom) < 1e-10) {
                    $row[] = 0;
                    continue;
                }
                $px = (int) (($H[0][0] * $mx + $H[0][1] * $my + $H[0][2]) / $denom);
                $py = (int) (($H[1][0] * $mx + $H[1][1] * $my + $H[1][2]) / $denom);
                // Clamp to image bounds.
                $px = max(0, min($w - 1, $px));
                $py = max(0, min($h - 1, $py));
                $row[] = $binary[$py][$px];
            }
            $qr[] = $row;
        }
        return $qr;
    }

    /**
     * Read and BCH-decode the 15-bit format information from both copies in the
     * sampled QR matrix.  Falls back to nearest-valid-codeword (Hamming ≤ 3)
     * if neither copy passes the BCH check.
     *
     * Format-info layout (first copy, top-left corner, bits 0–14 LSB first):
     *   bits 0-5 → rows 0-5, col 8
     *   bit 6    → row 7, col 8   (row 6 is the timing pattern)
     *   bit 7    → row 8, col 8
     *   bit 8    → row 8, col 7
     *   bits 9-14 → row 8, cols 5 down to 0
     *
     * @param  int[][] $qr    Sampled QR matrix.
     * @param  int     $size  Matrix dimension ($qrSize).
     * @return array|false    [int $eccLevel, int $maskNum] or false.
     */
    private static function decodeFormatInfo(array $qr, int $size): array|false
    {
        // ── Read first copy (top-left) ────────────────────────────────────────
        $code1 = 0;
        for ($i = 0; $i < 6; $i++) {
            $code1 |= $qr[$i][8] << $i;
        }
        $code1 |= $qr[7][8] << 6;
        $code1 |= $qr[8][8] << 7;
        $code1 |= $qr[8][7] << 8;
        for ($i = 9; $i < 15; $i++) {
            $code1 |= $qr[8][14 - $i] << $i;
        }

        // ── Read second copy (top-right + bottom-left) ────────────────────────
        $code2 = 0;
        for ($i = 0; $i < 8; $i++) {
            $code2 |= $qr[8][$size - 1 - $i] << $i;
        }
        for ($i = 8; $i < 15; $i++) {
            $code2 |= $qr[$size - 15 + $i][8] << $i;
        }

        // ECC format-bits → internal ECC level (L=01→0, M=00→1, Q=11→2, H=10→3).
        $eccMap = [1 => 0, 0 => 1, 3 => 2, 2 => 3];

        // Try both copies: undo the XOR mask, then validate the BCH remainder.
        foreach ([$code1, $code2] as $raw) {
            $unmasked = $raw ^ self::FORMAT_MASK;
            if (self::bchFormatCheck($unmasked)) {
                $data = ($unmasked >> 10) & 0x1F;
                $eccBits = ($data >> 3) & 0x3;
                $mask = $data & 0x7;
                $eccLevel = $eccMap[$eccBits] ?? 1;
                return [$eccLevel, $mask];
            }
        }

        // Last resort: brute-force search over all 32 valid format words and
        // pick the one with the smallest Hamming distance to either raw copy.
        return self::bruteForceFormatInfo($code1, $code2, $eccMap);
    }

    /**
     * Return true when the BCH(15,5) remainder of $code is zero (no errors).
     *
     * The 15-bit format codeword is divided (in GF(2)) by the generator
     * polynomial G(x) = x^10 + x^8 + x^5 + x^4 + x^2 + x + 1 (0x537).
     *
     * @param  int  $code  15-bit unmasked format codeword.
     * @return bool
     */
    private static function bchFormatCheck(int $code): bool
    {
        $val = $code;
        for ($i = 14; $i >= 10; $i--) {
            if ((($val >> $i) & 1) === 1) {
                $val ^= self::FORMAT_GEN_POLY << ($i - 10);
            }
        }
        return ($val & 0x3FF) === 0;
    }


    /**
     * Find the valid format codeword nearest (by Hamming distance) to the two
     * raw candidates.  Accepts a match only when the distance is at most 3.
     *
     * @param  int   $code1   Raw format codeword, first copy.
     * @param  int   $code2   Raw format codeword, second copy.
     * @param  int[] $eccMap  Format-bits → eccLevel lookup table.
     * @return array|false    [int $eccLevel, int $maskNum] or false.
     */
    private static function bruteForceFormatInfo(int $code1, int $code2, array $eccMap): array|false
    {
        $best = PHP_INT_MAX;
        $bestFmt = false;

        foreach ([1, 0, 3, 2] as $eccFmtBits) {           // L=01, M=00, Q=11, H=10
            foreach (range(0, 7) as $maskNum) {
                $data = ($eccFmtBits << 3) | $maskNum;
                // Build the BCH remainder for this data word.
                $bch = $data << 10;
                for ($i = 4; $i >= 0; $i--) {
                    if ((($bch >> ($i + 10)) & 1) === 1) {
                        $bch ^= self::FORMAT_GEN_POLY << $i;
                    }
                }
                $candidate = (($data << 10) | ($bch & 0x3FF)) ^ self::FORMAT_MASK;
                // Minimum Hamming distance to either raw copy.
                $d = min(self::hammingWeight($candidate ^ $code1), self::hammingWeight($candidate ^ $code2));
                if ($d < $best) {
                    $best = $d;
                    $bestFmt = [$eccMap[$eccFmtBits] ?? 1, $maskNum];
                }
            }
        }
        return ($best <= 3) ? $bestFmt : false;
    }

    /**
     * Count the number of set bits in an integer (population count).
     *
     * @param  int $n  Non-negative integer.
     * @return int
     */
    private static function hammingWeight(int $n): int
    {
        $count = 0;
        while ($n) {
            $count += $n & 1;
            $n >>= 1;
        }
        return $count;
    }

    /**
     * Load an image file into a GD resource.
     *
     * @param  string           $path  Path to the image file.
     * @return GdImage|false          GD image resource or false on failure.
     */
    private static function loadGdImage(string $path): GdImage|false
    {
        if (!is_readable($path)) {
            return false;
        }
        $info = @getimagesize($path);
        if ($info === false) {
            return false;
        }
        return match ($info[2]) {
            IMAGETYPE_PNG => imagecreatefrompng($path),
            IMAGETYPE_JPEG => imagecreatefromjpeg($path),
            IMAGETYPE_GIF => imagecreatefromgif($path),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? imagecreatefromwebp($path) : false,
            IMAGETYPE_BMP => function_exists('imagecreatefrombmp') ? imagecreatefrombmp($path) : false,
            default => false,
        };
    }

    /**
     * Compute the optimal binarization threshold for an image using Otsu's method.
     *
     * Builds a 256-bin luminance histogram and finds the intensity that maximises
     * the between-class variance (equivalent to minimising within-class variance).
     * Luminance is derived via the ITU-R BT.601 formula:
     *   L = 0.299 R + 0.587 G + 0.114 B
     *
     * @param  GdImage $image  Source image.
     * @param  int     $w      Image width in pixels.
     * @param  int     $h      Image height in pixels.
     * @return int             Optimal threshold (0-255).
     */
    private static function computeOtsuThreshold(GdImage $image, int $w, int $h): int
    {
        // Build luminance histogram.
        $hist = array_fill(0, 256, 0);
        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $rgb = ImageHandler::getRgbFromPosition($image, $x, $y);
                $lum = (int) round(0.299 * $rgb['r'] + 0.587 * $rgb['g'] + 0.114 * $rgb['b']);
                $hist[$lum]++;
            }
        }

        // Compute the global weighted mean intensity.
        $total = $w * $h;
        $sum = 0;
        for ($i = 0; $i < 256; $i++) {
            $sum += $i * $hist[$i];
        }

        // Sweep through each possible threshold; keep the one with the highest between-class variance.
        $sumB = 0;
        $wB = 0;
        $maxVar = 0.0;
        $threshold = 128;

        for ($i = 0; $i < 256; $i++) {
            $wB += $hist[$i];
            if ($wB === 0) {
                continue;
            }
            $wF = $total - $wB;
            if ($wF === 0) {
                break;
            }
            $sumB += $i * $hist[$i];
            $mB = $sumB / $wB;
            $mF = ($sum - $sumB) / $wF;
            $var = (float) ($wB * $wF) * ($mB - $mF) ** 2;
            if ($var > $maxVar) {
                $maxVar = $var;
                $threshold = $i;
            }
        }

        return $threshold;
    }

    /**
     * Convert a GD image to a 2-D binary array using Otsu's global threshold.
     *
     * Returns 1 for dark pixels (below or at the threshold) and 0 for light.
     *
     * @param  GdImage $image   Source image.
     * @param  int      $w      Image width in pixels.
     * @param  int      $h      Image height in pixels.
     * @return int[][]          $h × $w binary array.
     */
    private static function binarizeGd(GdImage $image, int $w, int $h): array
    {
        $threshold = self::computeOtsuThreshold($image, $w, $h);

        // Binarize: pixels at or below the threshold are dark (1); the rest are light (0).
        $matrix = [];
        for ($y = 0; $y < $h; $y++) {
            $row = [];
            for ($x = 0; $x < $w; $x++) {
                $rgb = ImageHandler::getRgbFromPosition($image, $x, $y);
                $lum = (int) round(0.299 * $rgb['r'] + 0.587 * $rgb['g'] + 0.114 * $rgb['b']);
                $row[] = ($lum <= $threshold) ? 1 : 0;
            }
            $matrix[] = $row;
        }

        return $matrix;
    }

    /**
     * Scan every row for the dark-light-dark-light-dark 1:1:3:1:1 run pattern,
     * then cross-check each candidate vertically and diagonally before keeping it.
     *
     * @param  int[][] $binary  Binarized image ($h × $w).
     * @param  int     $w       Image width.
     * @param  int     $h       Image height.
     * @return array[]          Clustered finder centres as [[x, y], ...].
     */
    private static function detectFinderPatterns(array $binary, int $w, int $h): array
    {
        $raw = [];  // accumulates [$cx, $cy, $estimatedModuleSize]

        for ($y = 0; $y < $h; $y++) {
            // Build the run-length sequence for this row.
            $runs = [];
            $curColor = $binary[$y][0];
            $runLen = 1;
            for ($x = 1; $x < $w; $x++) {
                if ($binary[$y][$x] === $curColor) {
                    $runLen++;
                } else {
                    $runs[] = $runLen;
                    $curColor = $binary[$y][$x];
                    $runLen = 1;
                }
            }
            $runs[] = $runLen;

            $nr = count($runs);
            $startColor = $binary[$y][0];   // colour of run index 0

            for ($i = 0; $i + 4 < $nr; $i++) {
                // Determine the colour of run index $i (alternates from $startColor).
                $runColor = ($i % 2 === 0) ? $startColor : (1 - $startColor);
                // The outer border of a finder pattern is always dark.
                if ($runColor !== 1) {
                    continue;
                }
                [$a, $b, $c, $d, $e] = [$runs[$i], $runs[$i + 1], $runs[$i + 2], $runs[$i + 3], $runs[$i + 4]];
                if (!self::isFinderRunRatio($a, $b, $c, $d, $e)) {
                    continue;
                }
                // Pixel x-coordinate of the centre of the 3-unit middle run.
                $xStart = (int) array_sum(array_slice($runs, 0, $i));
                $xCenter = $xStart + $a + $b + (int) ($c / 2);

                // Vertical cross-check centred at ($xCenter, $y).
                $vc = self::crossCheckVertical($binary, $w, $h, $xCenter, $y, $c);
                if ($vc === false) {
                    continue;
                }
                [$cy, $modSize] = $vc;

                // Diagonal cross-check as a final filter.
                if (!self::crossCheckDiagonal($binary, $w, $h, $xCenter, (int) $cy, $modSize)) {
                    continue;
                }

                $raw[] = [(float) $xCenter, $cy, $modSize];
            }
        }

        return self::clusterFinderCandidates($raw);
    }

    /**
     * Walk the sampled QR matrix in the canonical right-to-left, bottom-to-top
     * zigzag order, collect bits from non-reserved cells, and undo the mask.
     *
     * This is the exact reverse of placeData() + applyMask() in the encoder.
     *
     * @param  int[][]  $qr       Sampled QR matrix.
     * @param  int      $size     Matrix dimension.
     * @param  bool[][] $reserved Reserved-cell map.
     * @param  int      $maskNum  Mask pattern index (0-7).
     * @return string             Raw bit string (without remainder bits).
     */
    private static function extractDataBits(array $qr, int $size, array $reserved, int $maskNum): string
    {
        $bits = '';
        $col = $size - 1;
        $upward = true;

        while ($col > 0) {
            if ($col === 6) {
                $col = 5;   // skip the vertical timing column
            }
            for ($i = 0; $i < $size; $i++) {
                $row = $upward ? ($size - 1 - $i) : $i;
                for ($c = 0; $c < 2; $c++) {
                    $cc = $col - $c;
                    if (!$reserved[$row][$cc]) {
                        $bit = $qr[$row][$cc];
                        // XOR-masking is its own inverse (applying it again undoes it).
                        if (self::maskCondition($maskNum, $row, $cc)) {
                            $bit ^= 1;
                        }
                        $bits .= $bit;
                    }
                }
            }
            $col -= 2;
            $upward = !$upward;
        }

        return $bits;
    }

    /**
     * Convert the extracted bit stream back into 8-bit codewords.
     *
     * Any trailing remainder bits are ignored because they do not belong to a
     * full codeword.
     *
     * @param  string $bits  Extracted QR data stream, including remainder bits.
     * @return int[]         Codewords in matrix traversal order.
     */
    private static function bitStreamToCodewords(string $bits): array
    {
        $codewords = [];
        $bitsLength = strlen($bits);
        for ($i = 0; $i + 8 <= $bitsLength; $i += 8) {
            $codewords[] = (int) bindec(substr($bits, $i, 8));
        }

        return $codewords;
    }

    /**
     * Undo QR block interleaving and rebuild the original data-codeword order.
     *
     * This restores the encoder's block layout for clean symbols. Reed-Solomon
     * correction is intentionally not attempted here.
     *
     * @param  int[]       $codewords  Codewords extracted from the sampled matrix.
     * @param  int         $version    QR version (1-40).
     * @param  int         $eccLevel   Internal ECC level index.
     * @return int[]|false             Original data codewords, or false on malformed input.
     */
    private static function deinterleaveDataCodewords(array $codewords, int $version, int $eccLevel): array|false
    {
        [, $group1Blocks, $group1DataCodewords, $group2Blocks, $group2DataCodewords] = self::EC_TABLE[$version - 1][$eccLevel];

        $dataBlockLengths = [];
        for ($i = 0; $i < $group1Blocks; $i++) {
            $dataBlockLengths[] = $group1DataCodewords;
        }
        for ($i = 0; $i < $group2Blocks; $i++) {
            $dataBlockLengths[] = $group2DataCodewords;
        }

        $blocks = [];
        foreach ($dataBlockLengths as $dataBlockLength) {
            $blocks[] = array_fill(0, $dataBlockLength, 0);
        }

        $codewordIndex = 0;
        $maxDataBlockLength = max($group1DataCodewords, $group2DataCodewords);
        for ($column = 0; $column < $maxDataBlockLength; $column++) {
            foreach ($dataBlockLengths as $blockIndex => $dataBlockLength) {
                if ($column >= $dataBlockLength) {
                    continue;
                }
                if (!isset($codewords[$codewordIndex])) {
                    return false;
                }

                $blocks[$blockIndex][$column] = $codewords[$codewordIndex];
                $codewordIndex++;
            }
        }

        $dataCodewords = [];
        foreach ($blocks as $block) {
            foreach ($block as $codeword) {
                $dataCodewords[] = $codeword;
            }
        }

        return $dataCodewords;
    }

    /**
     * Return the character-count indicator width (in bits) for a given encoding mode and version.
     *
     * Per ISO/IEC 18004, character-count bit widths vary by version range and mode.
     *
     * @param  int $mode     4-bit mode indicator (0b0001 = numeric, 0b0010 = alpha, etc.).
     * @param  int $version  QR version (1-40).
     * @return int           Number of bits used for the character count field.
     */
    private static function getCharCountBits(int $mode, int $version): int
    {
        return match ($mode) {
            0b0001 => ($version <= 9) ? 10 : (($version <= 26) ? 12 : 14), // Numeric
            0b0010 => ($version <= 9) ? 9 : (($version <= 26) ? 11 : 13), // Alphanumeric
            0b0100 => ($version <= 9) ? 8 : 16,                            // Byte
            0b1000 => ($version <= 9) ? 8 : (($version <= 26) ? 10 : 12), // Kanji
            default => 8,
        };
    }

    /**
     * Decode a numeric segment from the bit stream.
     *
     * Numeric mode packs 3 digits into 10 bits, 2 digits into 7 bits, and
     * 1 remaining digit into 4 bits.
     *
     * @param  callable $read     Bit-reader closure (reads n bits, advances position).
     * @param  int      $version  QR version (determines character-count bit width).
     * @return string             Decoded digit string.
     */
    private static function decodeNumericSegment(callable $read, int $version): string
    {
        $count = $read(self::getCharCountBits(0b0001, $version));
        $out = '';

        // Full groups of 3 digits, each encoded in 10 bits.
        for ($i = 0; $i < intdiv($count, 3); $i++) {
            $out .= str_pad((string) $read(10), 3, '0', STR_PAD_LEFT);
        }

        // Remainder: 2 digits in 7 bits, or 1 digit in 4 bits.
        $rem = $count % 3;
        if ($rem === 2) {
            $out .= str_pad((string) $read(7), 2, '0', STR_PAD_LEFT);
        } elseif ($rem === 1) {
            $out .= (string) $read(4);
        }

        return $out;
    }

    /**
     * Decode an alphanumeric segment from the bit stream.
     *
     * Alphanumeric mode encodes 2 characters per 11 bits from a 45-character table.
     * A trailing single character uses 6 bits.
     *
     * @param  callable $read     Bit-reader closure.
     * @param  int      $version  QR version (determines character-count bit width).
     * @param  string   $alnum    The 45-character lookup table.
     * @return string             Decoded character string.
     */
    private static function decodeAlphanumericSegment(callable $read, int $version, string $alnum): string
    {
        $count = $read(self::getCharCountBits(0b0010, $version));
        $out = '';

        // Pairs of characters, each packed into 11 bits.
        for ($i = 0; $i < intdiv($count, 2); $i++) {
            $val = $read(11);
            $out .= $alnum[intdiv($val, 45)] . $alnum[$val % 45];
        }

        // Optional trailing single character in 6 bits.
        if ($count % 2 === 1) {
            $out .= $alnum[$read(6)];
        }

        return $out;
    }

    /**
     * Decode a byte segment from the bit stream.
     *
     * Each byte character occupies exactly 8 bits.
     *
     * @param  callable $read     Bit-reader closure.
     * @param  int      $version  QR version (determines character-count bit width).
     * @return string             Decoded raw byte string.
     */
    private static function decodeByteSegment(callable $read, int $version): string
    {
        $count = $read(self::getCharCountBits(0b0100, $version));
        $out = '';

        for ($i = 0; $i < $count; $i++) {
            $out .= chr($read(8));
        }

        return $out;
    }

    /**
     * Decode a Kanji (Shift-JIS) segment from the bit stream.
     *
     * Each character is packed into 13 bits and reconstituted as a Shift-JIS
     * two-byte codepoint, then converted to UTF-8 if mb_convert_encoding is available.
     *
     * @param  callable $read     Bit-reader closure.
     * @param  int      $version  QR version (determines character-count bit width).
     * @return string             Decoded string (UTF-8 if possible, Shift-JIS otherwise).
     */
    private static function decodeKanjiSegment(callable $read, int $version): string
    {
        $count = $read(self::getCharCountBits(0b1000, $version));
        $out = '';

        for ($i = 0; $i < $count; $i++) {
            $val = $read(13);
            // Reconstitute the Shift-JIS codepoint from the packed 13-bit value.
            $sjis = ($val >= 0x1F00) ? ($val + 0xC140) : ($val + 0x8140);
            $high = ($sjis >> 8) & 0xFF;
            $low = $sjis & 0xFF;
            $out .= function_exists('mb_convert_encoding')
                ? mb_convert_encoding(chr($high) . chr($low), 'UTF-8', 'SJIS')
                : chr($high) . chr($low);
        }

        return $out;
    }

    /**
     * Parse the raw (unmasked) bit string into a decoded payload string.
     *
     * Supports all four standard encoding modes:
     *   - 0001  Numeric        (0-9 only, 3 digits per 10 bits)
     *   - 0010  Alphanumeric   (45-character set, 2 chars per 11 bits)
     *   - 0100  Byte           (raw bytes, one char per 8 bits)
     *   - 1000  Kanji          (Shift-JIS double-byte characters, 13 bits each)
     *
     * Multiple segments with different modes are concatenated.
     *
     * @param  string       $bits     Raw bit string extracted from the QR matrix.
     * @param  int          $version  QR version (used for character-count bit widths).
     * @return string|false           Decoded payload or false if the stream is unreadable.
     */
    private static function parseBitStream(string $bits, int $version): string|false
    {
        $pos = 0;
        $total = strlen($bits);
        $result = '';

        // Read $n bits from $bits at position $pos (MSB first) and advance $pos.
        $read = static function (int $n) use (&$bits, &$pos, $total): int {
            $val = 0;
            for ($i = 0; $i < $n; $i++) {
                $val = ($val << 1) | (($pos < $total) ? (int) $bits[$pos] : 0);
                $pos++;
            }
            return $val;
        };

        // 45-character alphanumeric set used by QR codes.
        $alnum = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ $%*+-./:';

        while ($pos + 4 <= $total) {
            $mode = $read(4);

            if ($mode === 0) {
                // Terminator (0000): no more segments follow.
                break;
            }

            switch ($mode) {
                case 0b0001:  // ── Numeric ──────────────────────────────────────
                    $result .= self::decodeNumericSegment($read, $version);
                    break;

                case 0b0010:  // ── Alphanumeric ──────────────────────────────────
                    $result .= self::decodeAlphanumericSegment($read, $version, $alnum);
                    break;

                case 0b0100:  // ── Byte ──────────────────────────────────────────
                    $result .= self::decodeByteSegment($read, $version);
                    break;

                case 0b1000:  // ── Kanji (Shift-JIS) ─────────────────────────────
                    $result .= self::decodeKanjiSegment($read, $version);
                    break;

                default:
                    // Unknown or structured-append mode: stop parsing.
                    return $result !== '' ? $result : false;
            }
        }

        return $result !== '' ? $result : false;
    }

    #endregion
}
