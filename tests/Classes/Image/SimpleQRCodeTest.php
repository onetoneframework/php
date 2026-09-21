<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Image;

/**
 * Unit tests for SimpleQRCode.
 *
 * Run with:
 *   vendor/bin/phpunit SimpleQRCodeTest.php
 *
 * Requires:
 *   composer require --dev phpunit/phpunit ^10
 *   Autoloader must resolve Clover\Classes\Image\SimpleQRCode.
 *
 * Private methods are accessed via ReflectionMethod / ReflectionProperty so
 * that every piece of logic can be verified in isolation without depending on
 * GD image I/O.
 */

use ReflectionProperty;
use ReflectionMethod;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Clover\Classes\Image\SimpleQRCode;

class SimpleQRCodeTest extends TestCase
{
    // ── Reflection helpers ────────────────────────────────────────────────────

    /**
     * Invoke a private/protected static method on SimpleQRCode.
     */
    private static function callStatic(string $method, mixed ...$args): mixed
    {
        $ref = new ReflectionMethod(SimpleQRCode::class, $method);
        if (version_compare(PHP_VERSION, '8.1.0', '<')) {
            // @phpstan-ignore-next-line
            $ref->setAccessible(true);
        }
        return $ref->invoke(null, ...$args);
    }

    /**
     * Invoke a private/protected instance method on a SimpleQRCode object.
     */
    private function callMethod(SimpleQRCode $obj, string $method, mixed ...$args): mixed
    {
        $ref = new ReflectionMethod(SimpleQRCode::class, $method);
        if (version_compare(PHP_VERSION, '8.1.0', '<')) {
            // @phpstan-ignore-next-line
            $ref->setAccessible(true);
        }
        return $ref->invoke($obj, ...$args);
    }

    /**
     * Build a minimal, valid byte-mode QR bit stream for use in parseBitStream tests.
     *
     * Layout: mode(4) | charCount(8 or 16) | data bytes | terminator(4)
     */
    private static function byteModeStream(string $payload, int $version = 1): string
    {
        $bits = '0100'; // byte mode indicator
        $ccBits = $version <= 9 ? 8 : 16;
        $bits .= str_pad(decbin(strlen($payload)), $ccBits, '0', STR_PAD_LEFT);
        foreach (str_split($payload) as $char) {
            $bits .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }
        $bits .= '0000'; // terminator
        return $bits;
    }

    // =========================================================================
    // parseBitStream
    // =========================================================================

    public function testParseBitStreamByteMode(): void
    {
        $result = self::callStatic('parseBitStream', self::byteModeStream('Hello'), 1);
        $this->assertSame('Hello', $result);
    }

    public function testParseBitStreamByteModeVersion10Uses16BitCount(): void
    {
        // Version 10+ must use a 16-bit character-count field in byte mode.
        $result = self::callStatic('parseBitStream', self::byteModeStream('Hi', 10), 10);
        $this->assertSame('Hi', $result);
    }

    public function testParseBitStreamByteModeSpecialChars(): void
    {
        $payload = "https://example.com/path?q=1&r=2";
        $result = self::callStatic('parseBitStream', self::byteModeStream($payload), 1);
        $this->assertSame($payload, $result);
    }

    // ── Numeric mode ─────────────────────────────────────────────────────────

    public function testParseBitStreamNumericThreeDigits(): void
    {
        // "123" → 10-bit value 123 = 0001111011
        $bits = '0001';
        $bits .= '0000000011';  // count = 3 (10-bit for v ≤ 9)
        $bits .= '0001111011';  // 123
        $bits .= '0000';        // terminator
        $this->assertSame('123', self::callStatic('parseBitStream', $bits, 1));
    }

    public function testParseBitStreamNumericTwoDigitRemainder(): void
    {
        // "12" → 2 remainder digits → 7-bit value 12 = 0001100
        $bits = '0001';
        $bits .= '0000000010';  // count = 2
        $bits .= '0001100';     // 12
        $bits .= '0000';
        $this->assertSame('12', self::callStatic('parseBitStream', $bits, 1));
    }

    public function testParseBitStreamNumericOneDigitRemainder(): void
    {
        // "7" → single digit → 4-bit value 7 = 0111
        $bits = '0001';
        $bits .= '0000000001';  // count = 1
        $bits .= '0111';        // 7
        $bits .= '0000';
        $this->assertSame('7', self::callStatic('parseBitStream', $bits, 1));
    }

    public function testParseBitStreamNumericLeadingZeros(): void
    {
        // "007" → 3 digits, value = 7 → output must be zero-padded to 3 chars.
        $bits = '0001';
        $bits .= '0000000011';  // count = 3
        $bits .= '0000000111';  // 7 in 10 bits
        $bits .= '0000';
        $this->assertSame('007', self::callStatic('parseBitStream', $bits, 1));
    }

    // ── Alphanumeric mode ─────────────────────────────────────────────────────

    public function testParseBitStreamAlphanumericEvenCount(): void
    {
        // "AB": A=10, B=11 → 10*45+11 = 461
        $bits = '0010';
        $bits .= '000000010';                               // count = 2 (9-bit)
        $bits .= str_pad(decbin(10 * 45 + 11), 11, '0', STR_PAD_LEFT);
        $bits .= '0000';
        $this->assertSame('AB', self::callStatic('parseBitStream', $bits, 1));
    }

    public function testParseBitStreamAlphanumericOddCount(): void
    {
        // "A" (odd = 1 char in 6 bits): A=10 = 001010
        $bits = '0010';
        $bits .= '000000001';   // count = 1
        $bits .= '001010';      // 10 in 6 bits
        $bits .= '0000';
        $this->assertSame('A', self::callStatic('parseBitStream', $bits, 1));
    }

    public function testParseBitStreamAlphanumericFiveChars(): void
    {
        // "AC-42": sample from the QR spec.
        $bits = '0010';
        $bits .= '000000101';                                           // count = 5
        $bits .= str_pad(decbin(10 * 45 + 12), 11, '0', STR_PAD_LEFT); // AC
        $bits .= str_pad(decbin(41 * 45 + 4), 11, '0', STR_PAD_LEFT); // -4
        $bits .= str_pad(decbin(2), 6, '0', STR_PAD_LEFT); // 2
        $bits .= '0000';
        $this->assertSame('AC-42', self::callStatic('parseBitStream', $bits, 1));
    }

    // ── Multi-segment ─────────────────────────────────────────────────────────

    public function testParseBitStreamTwoByteSegmentsConcatenated(): void
    {
        // Two consecutive byte-mode segments (no terminator between them).
        $seg1 = '0100' . '00000001' . str_pad(decbin(ord('X')), 8, '0', STR_PAD_LEFT);
        $seg2 = '0100' . '00000001' . str_pad(decbin(ord('Y')), 8, '0', STR_PAD_LEFT);
        $bits = $seg1 . $seg2 . '0000'; // single terminator at the end
        $this->assertSame('XY', self::callStatic('parseBitStream', $bits, 1));
    }

    // ── Terminator handling (the bug fix) ─────────────────────────────────────

    /**
     * Core regression test for the `continue` → `break` fix.
     *
     * Previously, hitting the terminator (0000) issued `continue`, which kept
     * the loop alive and read trailing pad bytes (0xEC = 11101100…) as a mode
     * indicator.  0b1110 = 14 is an unknown mode → the default branch ran and
     * returned false when $result happened to be empty.
     *
     * After the fix, `break` exits immediately, and the already-accumulated
     * $result is returned correctly.
     */
    public function testParseBitStreamTerminatorBreaksLoop(): void
    {
        // Valid payload followed by terminator, then a deliberately bad mode (0b1111 = 15).
        // With `continue` the loop would read 0b1111 and hit default → possibly false.
        // With `break`   the loop exits before reading anything past the terminator.
        $stream = self::byteModeStream('Z');
        $stream .= '11110000'; // junk that must never be parsed
        $result = self::callStatic('parseBitStream', $stream, 1);
        $this->assertSame('Z', $result, 'Terminator must stop parsing; trailing bits must be ignored.');
    }

    public function testParseBitStreamTerminatorWithPadBytes(): void
    {
        // Mimic what a real QR stream looks like: valid data, terminator, then
        // 0xEC / 0x11 pad bytes.  The result must equal the original payload.
        $payload = 'QR';
        $stream = self::byteModeStream($payload);
        $stream .= '11101100'; // 0xEC pad
        $stream .= '00010001'; // 0x11 pad
        $result = self::callStatic('parseBitStream', $stream, 1);
        $this->assertSame($payload, $result);
    }

    // ── Edge cases ────────────────────────────────────────────────────────────

    public function testParseBitStreamReturnsFalseOnlyTerminator(): void
    {
        // Stream contains only a terminator → no content decoded → false.
        $this->assertFalse(self::callStatic('parseBitStream', '0000', 1));
    }

    public function testParseBitStreamReturnsFalseUnknownMode(): void
    {
        // Mode 0b1111 (15) is unknown and $result is empty → false.
        $this->assertFalse(self::callStatic('parseBitStream', '1111', 1));
    }

    public function testParseBitStreamReturnsFalseOnEmptyStream(): void
    {
        // Stream too short to contain even one mode indicator.
        $this->assertFalse(self::callStatic('parseBitStream', '010', 1));
    }

    // =========================================================================
    // getCharCountBits
    // =========================================================================

    #[DataProvider('charCountBitsProvider')]
    public function testGetCharCountBits(int $mode, int $version, int $expected): void
    {
        $this->assertSame($expected, self::callStatic('getCharCountBits', $mode, $version));
    }

    public static function charCountBitsProvider(): array
    {
        return [
            // Numeric (0b0001) – three version bands
            'numeric v1' => [0b0001, 1, 10],
            'numeric v9' => [0b0001, 9, 10],
            'numeric v10' => [0b0001, 10, 12],
            'numeric v26' => [0b0001, 26, 12],
            'numeric v27' => [0b0001, 27, 14],
            'numeric v40' => [0b0001, 40, 14],
            // Alphanumeric (0b0010)
            'alnum v1' => [0b0010, 1, 9],
            'alnum v9' => [0b0010, 9, 9],
            'alnum v10' => [0b0010, 10, 11],
            'alnum v26' => [0b0010, 26, 11],
            'alnum v27' => [0b0010, 27, 13],
            'alnum v40' => [0b0010, 40, 13],
            // Byte (0b0100) – only two bands
            'byte v1' => [0b0100, 1, 8],
            'byte v9' => [0b0100, 9, 8],
            'byte v10' => [0b0100, 10, 16],
            'byte v40' => [0b0100, 40, 16],
            // Kanji (0b1000)
            'kanji v1' => [0b1000, 1, 8],
            'kanji v9' => [0b1000, 9, 8],
            'kanji v10' => [0b1000, 10, 10],
            'kanji v26' => [0b1000, 26, 10],
            'kanji v27' => [0b1000, 27, 12],
            'kanji v40' => [0b1000, 40, 12],
        ];
    }

    // =========================================================================
    // isFinderRunRatio
    // =========================================================================

    public function testIsFinderRunRatioPerfectUnit(): void
    {
        // Exact 1:1:3:1:1 at 1 px per module.
        $this->assertTrue(self::callStatic('isFinderRunRatio', 1, 1, 3, 1, 1));
    }

    public function testIsFinderRunRatioScaledUp(): void
    {
        // 3× scale (3:3:9:3:3) must still match.
        $this->assertTrue(self::callStatic('isFinderRunRatio', 3, 3, 9, 3, 3));
    }

    public function testIsFinderRunRatioWithinTolerance(): void
    {
        // Slight distortion that stays within the 50 % per-unit tolerance.
        $this->assertTrue(self::callStatic('isFinderRunRatio', 2, 2, 6, 2, 1));
    }

    public function testIsFinderRunRatioTooShort(): void
    {
        // Total modules < 7 is always rejected.
        $this->assertFalse(self::callStatic('isFinderRunRatio', 1, 1, 1, 1, 1));
    }

    public function testIsFinderRunRatioWrongProportions(): void
    {
        // 1:2:3:2:1 is not a finder-pattern ratio.
        $this->assertFalse(self::callStatic('isFinderRunRatio', 2, 4, 6, 4, 2));
    }

    public function testIsFinderRunRatioAllEqual(): void
    {
        // 1:1:1:1:1 fails the 3-unit centre requirement.
        $this->assertFalse(self::callStatic('isFinderRunRatio', 7, 7, 7, 7, 7));
    }

    // =========================================================================
    // hammingWeight
    // =========================================================================

    #[DataProvider('hammingWeightProvider')]
    public function testHammingWeight(int $n, int $expected): void
    {
        $this->assertSame($expected, self::callStatic('hammingWeight', $n));
    }

    public static function hammingWeightProvider(): array
    {
        return [
            'zero' => [0b00000000, 0],
            'one' => [0b00000001, 1],
            'high bit only' => [0b10000000, 1],
            'all 8 bits' => [0b11111111, 8],
            'alternating' => [0b10101010, 4],
            'format mask' => [0x5412, 5],
        ];
    }

    // =========================================================================
    // bchFormatCheck
    // =========================================================================

    public function testBchFormatCheckAllEccLevelMaskCombinations(): void
    {
        // Every (eccLevel, mask) pair must produce a codeword that passes BCH validation.
        // This mirrors placeFormatInfo's encoding path exactly.
        $eccFormatBits = [1, 0, 3, 2]; // L=01, M=00, Q=11, H=10

        foreach ($eccFormatBits as $eccBits) {
            for ($mask = 0; $mask < 8; $mask++) {
                $data = ($eccBits << 3) | $mask;
                $bch = $data << 10;
                for ($i = 4; $i >= 0; $i--) {
                    if ((($bch >> ($i + 10)) & 1) === 1) {
                        $bch ^= 0x537 << $i;
                    }
                }
                // XOR with FORMAT_MASK (0x5412) then undo it = original BCH word.
                $unmasked = (($data << 10) | ($bch & 0x3FF));
                $this->assertTrue(
                    self::callStatic('bchFormatCheck', $unmasked),
                    "BCH check failed for eccBits=$eccBits mask=$mask"
                );
            }
        }
    }

    public function testBchFormatCheckSingleBitFlipFails(): void
    {
        // Flipping any one bit of a valid codeword must invalidate the BCH check.
        $data = (0b01 << 3) | 0; // ECC_L, mask 0
        $bch = $data << 10;
        for ($i = 4; $i >= 0; $i--) {
            if ((($bch >> ($i + 10)) & 1) === 1) {
                $bch ^= 0x537 << $i;
            }
        }
        $valid = ($data << 10) | ($bch & 0x3FF);

        // Flip each of the 15 bits and verify the check fails.
        for ($bit = 0; $bit < 15; $bit++) {
            $corrupted = $valid ^ (1 << $bit);
            $this->assertFalse(
                self::callStatic('bchFormatCheck', $corrupted),
                "BCH check should fail with bit $bit flipped"
            );
        }
    }

    // =========================================================================
    // padToCodewords
    // =========================================================================

    public function testPadToCodewordsNopadNeeded(): void
    {
        // 8-bit stream = 1 byte, target = 1 → no pad appended.
        $result = self::callStatic('padToCodewords', '01001000', 1);
        $this->assertSame([0x48], $result);
    }

    public function testPadToCodewordsFirstPadIsEC(): void
    {
        $result = self::callStatic('padToCodewords', '00000000', 2);
        $this->assertSame([0x00, 0xEC], $result);
    }

    public function testPadToCodewordsAlternatesECand11(): void
    {
        // One real byte + four pad bytes must alternate 0xEC, 0x11, 0xEC, 0x11.
        $result = self::callStatic('padToCodewords', '00000000', 5);
        $this->assertSame([0x00, 0xEC, 0x11, 0xEC, 0x11], $result);
    }

    public function testPadToCodewordsByteBoundaryConversion(): void
    {
        // 16-bit stream = two bytes: 0xFF and 0x00.
        $result = self::callStatic('padToCodewords', '1111111100000000', 2);
        $this->assertSame([0xFF, 0x00], $result);
    }

    // =========================================================================
    // interleaveBlocks
    // =========================================================================

    public function testInterleaveBlocksSingleBlock(): void
    {
        $result = self::callStatic('interleaveBlocks', [[1, 2, 3]], [[10, 11]], 3, 2);
        $this->assertSame([1, 2, 3, 10, 11], $result);
    }

    public function testInterleaveBlocksTwoEqualDataBlocks(): void
    {
        // Data: [1,2] and [3,4] → interleaved: 1,3,2,4
        // EC:   [10,11] and [12,13] → interleaved: 10,12,11,13
        $result = self::callStatic(
            'interleaveBlocks',
            [[1, 2], [3, 4]],
            [[10, 11], [12, 13]],
            2,
            2
        );
        $this->assertSame([1, 3, 2, 4, 10, 12, 11, 13], $result);
    }

    public function testInterleaveBlocksUnequalDataLengths(): void
    {
        // Group 1: 2 codewords, Group 2: 3 codewords (one extra).
        // Data: col0 → 1,3; col1 → 2,4; col2 → 5 (only block[1] has index 2)
        $result = self::callStatic(
            'interleaveBlocks',
            [[1, 2], [3, 4, 5]],
            [[10], [11]],
            3,
            1
        );
        $this->assertSame([1, 3, 2, 4, 5, 10, 11], $result);
    }

    public function testInterleaveBlocksEcOrderPreserved(): void
    {
        // Verify that EC codewords of each block appear consecutively per column,
        // not per block.  With 3 blocks each having 3 EC codewords:
        //   col0: ec[0][0], ec[1][0], ec[2][0]
        //   col1: ec[0][1], ec[1][1], ec[2][1]
        //   col2: ec[0][2], ec[1][2], ec[2][2]
        $data = [[0], [0], [0]];
        $ec = [[1, 2, 3], [4, 5, 6], [7, 8, 9]];
        $result = self::callStatic('interleaveBlocks', $data, $ec, 1, 3);
        $ecPart = array_slice($result, 3); // skip the 3 data bytes
        $this->assertSame([1, 4, 7, 2, 5, 8, 3, 6, 9], $ecPart);
    }

    // =========================================================================
    // Version selection and matrix size (via public API)
    // =========================================================================

    #[DataProvider('versionSelectionProvider')]
    public function testChooseVersion(string $payload, int $eccLevel, int $expectedVersion): void
    {
        $qr = new SimpleQRCode($payload, 1, $eccLevel);
        $this->assertSame($expectedVersion, $qr->getVersion());
    }

    public static function versionSelectionProvider(): array
    {
        return [
            // Version 1: max 19 bytes (ECC_L), 16 bytes (ECC_M)
            'v1 ECC_L short' => ['Hi!', SimpleQRCode::ECC_L, 1],
            'v1 ECC_M short' => ['Hello', SimpleQRCode::ECC_M, 1],
            'v1 ECC_L at capacity' => [str_repeat('A', 17), SimpleQRCode::ECC_L, 1],
            // Version 2
            'v2 ECC_L' => [str_repeat('A', 20), SimpleQRCode::ECC_L, 2],
            'v2 ECC_M' => [str_repeat('A', 20), SimpleQRCode::ECC_M, 2],
        ];
    }

    public function testMatrixSizeIsAlways17Plus4TimesVersion(): void
    {
        foreach (['x', str_repeat('A', 20), str_repeat('B', 80)] as $payload) {
            $qr = new SimpleQRCode($payload, 1, SimpleQRCode::ECC_M);
            $this->assertSame(
                17 + 4 * $qr->getVersion(),
                $qr->getMatrixSize(),
                "Matrix size mismatch for payload length " . strlen($payload)
            );
        }
    }

    public function testInvalidEccLevelDefaultsToM(): void
    {
        // An out-of-range ECC level must silently fall back to ECC_M.
        $qrInvalid = new SimpleQRCode('test', 1, 99);
        $qrM = new SimpleQRCode('test', 1, SimpleQRCode::ECC_M);
        $this->assertSame($qrM->getVersion(), $qrInvalid->getVersion());
    }

    public function testEstimateModuleSizeUsesDetectedFinderModuleWidths(): void
    {
        $this->assertSame(
            8.0,
            self::callStatic(
                'estimateModuleSize',
                [60.0, 60.0, 8.0],
                [236.0, 60.0, 8.0],
                [60.0, 236.0, 8.0]
            )
        );
    }

    #[DataProvider('readImageRoundTripProvider')]
    public function testReadImageRoundTrip(string $payload, int $eccLevel): void
    {
        $qr = new SimpleQRCode($payload, 8, $eccLevel);
        $tempPath = tempnam(sys_get_temp_dir(), 'qr_');
        $this->assertNotFalse($tempPath);
        if ($tempPath === false) {
            return;
        }

        $imagePath = $tempPath . '.png';
        unlink($tempPath);

        try {
            $this->assertTrue($qr->saveImage($imagePath));
            $this->assertSame($payload, SimpleQRCode::readImage($imagePath));
        } finally {
            if (is_file($imagePath)) {
                unlink($imagePath);
            }
        }
    }

    public static function readImageRoundTripProvider(): array
    {
        return [
            'version 1 payload' => ['Hello', SimpleQRCode::ECC_M],
            'version 3 payload' => [str_repeat('A', 30), SimpleQRCode::ECC_M],
            'version 5 payload' => [str_repeat('B', 80), SimpleQRCode::ECC_M],
        ];
    }

    // =========================================================================
    // scorePenalty rules (via injected matrix through Reflection)
    // =========================================================================

    /**
     * Inject a custom matrix into a SimpleQRCode instance so individual
     * penalty-rule methods can be tested in isolation.
     */
    private function makeQrWithMatrix(array $matrix): SimpleQRCode
    {
        $qr = new SimpleQRCode('x', 1, SimpleQRCode::ECC_M);

        $propMatrix = new ReflectionProperty(SimpleQRCode::class, 'matrix');
        if (version_compare(PHP_VERSION, '8.1.0', '<')) {
            // @phpstan-ignore-next-line
            $propMatrix->setAccessible(true);
        }
        $propMatrix->setValue($qr, $matrix);

        $propSize = new ReflectionProperty(SimpleQRCode::class, 'matrixSize');
        if (version_compare(PHP_VERSION, '8.1.0', '<')) {
            // @phpstan-ignore-next-line
            $propSize->setAccessible(true);
        }
        $propSize->setValue($qr, count($matrix));

        return $qr;
    }

    /** 6×6 checkerboard: no run ≥ 2, no 2×2 blocks, perfect balance. */
    private static function checkerboard(int $size): array
    {
        $m = [];
        for ($r = 0; $r < $size; $r++) {
            $row = [];
            for ($c = 0; $c < $size; $c++) {
                $row[] = ($r + $c) % 2;
            }
            $m[] = $row;
        }
        return $m;
    }

    // ── Rule 1: consecutive runs ──────────────────────────────────────────────

    public function testScorePenaltyRunCheckerboardIsZero(): void
    {
        $qr = $this->makeQrWithMatrix(self::checkerboard(6));
        $this->assertSame(0, $this->callMethod($qr, 'scorePenaltyRun', 6));
    }

    public function testScorePenaltyRunFiveInARowAddsThree(): void
    {
        // One row of 5 identical modules → +3 points (minimum).
        $m = self::checkerboard(6);
        $m[0] = [1, 1, 1, 1, 1, 0]; // 5-dark run at start of row 0
        $qr = $this->makeQrWithMatrix($m);
        $this->assertGreaterThanOrEqual(3, $this->callMethod($qr, 'scorePenaltyRun', 6));
    }

    public function testScorePenaltyRunSixInARowAddsFour(): void
    {
        // n=6 → penalty = 3 + (6-5) = 4 for that run alone.
        $m = self::checkerboard(6);
        $m[0] = [1, 1, 1, 1, 1, 1]; // 6-dark run, full row
        $qr = $this->makeQrWithMatrix($m);
        $score = $this->callMethod($qr, 'scorePenaltyRun', 6);
        // Row contributes 4; columns over row 0 also have long runs.
        $this->assertGreaterThanOrEqual(4, $score);
    }

    public function testScorePenaltyRunCountsColumnsAndRows(): void
    {
        // All-dark matrix: every row AND every column scores the max run penalty.
        $size = 5;
        $m = array_fill(0, $size, array_fill(0, $size, 1));
        $qr = $this->makeQrWithMatrix($m);
        $score = $this->callMethod($qr, 'scorePenaltyRun', $size);
        // Each of 5 rows + 5 cols has a run of 5 → 10 × 3 = 30 minimum.
        $this->assertSame(30, $score);
    }

    // ── Rule 2: 2×2 blocks ───────────────────────────────────────────────────

    public function testScorePenaltyBlockCheckerboardIsZero(): void
    {
        $qr = $this->makeQrWithMatrix(self::checkerboard(6));
        $this->assertSame(0, $this->callMethod($qr, 'scorePenaltyBlock', 6));
    }

    public function testScorePenaltyBlockOneBlock(): void
    {
        $m = self::checkerboard(4);
        $m[0][0] = $m[0][1] = $m[1][0] = $m[1][1] = 1;
        $qr = $this->makeQrWithMatrix($m);
        $this->assertSame(3, $this->callMethod($qr, 'scorePenaltyBlock', 4));
    }

    public function testScorePenaltyBlockOverlappingBlocks(): void
    {
        // 3×3 all-dark → four overlapping 2×2 blocks → 4 × 3 = 12.
        $m = array_fill(0, 4, array_fill(0, 4, 0));
        for ($r = 0; $r < 3; $r++) {
            for ($c = 0; $c < 3; $c++) {
                $m[$r][$c] = 1;
            }
        }
        $qr = $this->makeQrWithMatrix($m);
        $this->assertSame(12, $this->callMethod($qr, 'scorePenaltyBlock', 4));
    }

    // ── Rule 4: dark/light balance ────────────────────────────────────────────

    public function testScorePenaltyBalancePerfectHalfIsZero(): void
    {
        // Checkerboard = exactly 50 % dark → penalty = 0.
        $qr = $this->makeQrWithMatrix(self::checkerboard(4));
        $this->assertSame(0, $this->callMethod($qr, 'scorePenaltyBalance', 4));
    }

    public function testScorePenaltyBalanceAllDark(): void
    {
        // 100 % dark → 50 percentage-points off → 10 steps of 5 % → 100 points.
        $m = array_fill(0, 4, array_fill(0, 4, 1));
        $qr = $this->makeQrWithMatrix($m);
        $this->assertSame(100, $this->callMethod($qr, 'scorePenaltyBalance', 4));
    }

    public function testScorePenaltyBalanceAllLight(): void
    {
        // 0 % dark → same deviation as all-dark → 100 points.
        $m = array_fill(0, 4, array_fill(0, 4, 0));
        $qr = $this->makeQrWithMatrix($m);
        $this->assertSame(100, $this->callMethod($qr, 'scorePenaltyBalance', 4));
    }

    public function testScorePenaltyBalanceSingleStepDeviation(): void
    {
        // 9 dark out of 16 (56.25 %) → deviation = 1 step → penalty = 10.
        $m = array_fill(0, 4, array_fill(0, 4, 0));
        $dark = 0;
        for ($r = 0; $r < 4 && $dark < 9; $r++) {
            for ($c = 0; $c < 4 && $dark < 9; $c++) {
                $m[$r][$c] = 1;
                $dark++;
            }
        }
        $qr = $this->makeQrWithMatrix($m);
        $this->assertSame(10, $this->callMethod($qr, 'scorePenaltyBalance', 4));
    }
}
