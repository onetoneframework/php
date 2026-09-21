<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Math;

use Clover\Classes\Math\Volume;
use Clover\Enumeration\VolumeUnit;
use PHPUnit\Framework\TestCase;

/**
 * Comprehensive test suite for the Volume utility class.
 *
 * Coverage:
 *  - getConversionFactor(): known pairs, same-unit identity, unknown units, null guard
 *  - convert(): correct scaled result, null propagation
 *  - Round-trip consistency: forward × inverse ≈ 1.0
 *  - Cross-system spot-checks grounded in published reference values
 */
class VolumeTest extends TestCase
{
    public function testGetConversionFactor(): void
    {
        $factor = Volume::getConversionFactor(VolumeUnit::LITER, VolumeUnit::US_GALLON);
        $this->assertEqualsWithDelta(0.26417205235815, $factor, 0.0000001);
    }

    public function testGetConversionFactorReturnNull(): void
    {
        $factor = Volume::getConversionFactor(VolumeUnit::LITER, 'UNKNOWN_UNIT');
        $this->assertNull($factor);

        $factor2 = Volume::getConversionFactor('UNKNOWN_UNIT', VolumeUnit::US_GALLON);
        $this->assertNull($factor2);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Assert two floats are equal within a relative tolerance of 1e-6
     * plus a tiny absolute floor for values close to zero.
     */
    private function assertFactorEquals(float $expected, float|null $actual, string $label): void
    {
        $this->assertNotNull($actual, "{$label}: factor must not be null.");
        $delta = abs($expected) * 1e-6 + 1e-30;
        $this->assertEqualsWithDelta($expected, $actual, $delta, $label);
    }

    // -------------------------------------------------------------------------
    // getConversionFactor() – null guard for unknown units
    // -------------------------------------------------------------------------

    public function testUnknownFromUnitReturnsNull(): void
    {
        $this->assertNull(
            Volume::getConversionFactor('UNKNOWN', VolumeUnit::LITER),
            "An unrecognised source unit must return null."
        );
    }

    public function testUnknownToUnitReturnsNull(): void
    {
        $this->assertNull(
            Volume::getConversionFactor(VolumeUnit::LITER, 'UNKNOWN'),
            "An unrecognised target unit must return null."
        );
    }

    public function testBothUnitsUnknownReturnsNull(): void
    {
        $this->assertNull(
            Volume::getConversionFactor('FOO', 'BAR'),
            "Both units unknown must return null."
        );
    }

    public function testEmptyStringReturnsNull(): void
    {
        $this->assertNull(
            Volume::getConversionFactor('', VolumeUnit::LITER),
            "Empty string source must return null."
        );
    }

    // -------------------------------------------------------------------------
    // getConversionFactor() – same-unit identity
    // -------------------------------------------------------------------------

    /**
     * @dataProvider provideAllUnits
     */
    public function testSameUnitReturnsOne(string $unit): void
    {
        $factor = Volume::getConversionFactor($unit, $unit);
        $this->assertSame(1.0, $factor, "Same-unit conversion for '{$unit}' must return exactly 1.0.");
    }

    public static function provideAllUnits(): array
    {
        return array_map(
            fn(string $u) => [$u],
            [
                VolumeUnit::CUBIC_KILOMETER,
                VolumeUnit::CUBIC_METER,
                VolumeUnit::CUBIC_CENTIMETER,
                VolumeUnit::CUBIC_MILLIMETER,
                VolumeUnit::LITER,
                VolumeUnit::MILLILITER,
                VolumeUnit::CUBIC_MILE,
                VolumeUnit::CUBIC_YARD,
                VolumeUnit::CUBIC_FOOT,
                VolumeUnit::CUBIC_INCH,
                VolumeUnit::US_GALLON,
                VolumeUnit::US_QUART,
                VolumeUnit::US_PINT,
                VolumeUnit::US_CUP,
                VolumeUnit::US_FLUID_OUNCE,
                VolumeUnit::US_TABLE_SPOON,
                VolumeUnit::US_TEA_SPOON,
                VolumeUnit::IMPERIAL_GALLON,
                VolumeUnit::IMPERIAL_QUART,
                VolumeUnit::IMPERIAL_PINT,
                VolumeUnit::IMPERIAL_CUP,
                VolumeUnit::IMPERIAL_FLUID_OUNCE,
                VolumeUnit::IMPERIAL_TABLE_SPOON,
                VolumeUnit::IMPERIAL_TEA_SPOON,
            ]
        );
    }

    // -------------------------------------------------------------------------
    // getConversionFactor() – metric / SI conversions
    // -------------------------------------------------------------------------

    /**
     * @dataProvider provideMetricConversions
     */
    public function testMetricConversions(string $from, string $to, float $expected): void
    {
        $this->assertFactorEquals(
            $expected,
            Volume::getConversionFactor($from, $to),
            "{$from} → {$to}"
        );
    }

    public static function provideMetricConversions(): array
    {
        return [
            // Liter ↔ metric
            'L → mL' => [VolumeUnit::LITER, VolumeUnit::MILLILITER, 1_000.0],
            'L → cm³' => [VolumeUnit::LITER, VolumeUnit::CUBIC_CENTIMETER, 1_000.0],
            'L → mm³' => [VolumeUnit::LITER, VolumeUnit::CUBIC_MILLIMETER, 1_000_000.0],
            'L → m³' => [VolumeUnit::LITER, VolumeUnit::CUBIC_METER, 0.001],
            'L → km³' => [VolumeUnit::LITER, VolumeUnit::CUBIC_KILOMETER, 1e-12],

            // Milliliter
            'mL → L' => [VolumeUnit::MILLILITER, VolumeUnit::LITER, 0.001],
            'mL → cm³' => [VolumeUnit::MILLILITER, VolumeUnit::CUBIC_CENTIMETER, 1.0],
            'mL → mm³' => [VolumeUnit::MILLILITER, VolumeUnit::CUBIC_MILLIMETER, 1_000.0],

            // Cubic meter
            'm³ → L' => [VolumeUnit::CUBIC_METER, VolumeUnit::LITER, 1_000.0],
            'm³ → mL' => [VolumeUnit::CUBIC_METER, VolumeUnit::MILLILITER, 1_000_000.0],
            'm³ → km³' => [VolumeUnit::CUBIC_METER, VolumeUnit::CUBIC_KILOMETER, 1e-9],
            'm³ → cm³' => [VolumeUnit::CUBIC_METER, VolumeUnit::CUBIC_CENTIMETER, 1_000_000.0],
            'm³ → mm³' => [VolumeUnit::CUBIC_METER, VolumeUnit::CUBIC_MILLIMETER, 1_000_000_000.0],

            // Cubic kilometer
            'km³ → m³' => [VolumeUnit::CUBIC_KILOMETER, VolumeUnit::CUBIC_METER, 1_000_000_000.0],
            'km³ → L' => [VolumeUnit::CUBIC_KILOMETER, VolumeUnit::LITER, 1_000_000_000_000.0],
        ];
    }

    // -------------------------------------------------------------------------
    // getConversionFactor() – cubic imperial / US customary
    // -------------------------------------------------------------------------

    /**
     * @dataProvider provideCubicImperialConversions
     */
    public function testCubicImperialConversions(string $from, string $to, float $expected): void
    {
        $this->assertFactorEquals(
            $expected,
            Volume::getConversionFactor($from, $to),
            "{$from} → {$to}"
        );
    }

    public static function provideCubicImperialConversions(): array
    {
        return [
            // Cubic foot (1 ft³ = 28.316 846 592 L)
            'ft³ → L' => [VolumeUnit::CUBIC_FOOT, VolumeUnit::LITER, 28.316_846_592],
            'ft³ → in³' => [VolumeUnit::CUBIC_FOOT, VolumeUnit::CUBIC_INCH, 1_728.0],        // 12³
            'ft³ → yd³' => [VolumeUnit::CUBIC_FOOT, VolumeUnit::CUBIC_YARD, 1.0 / 27.0],     // 1/3³
            'ft³ → mL' => [VolumeUnit::CUBIC_FOOT, VolumeUnit::MILLILITER, 28_316.846_592],

            // Cubic inch (1 in³ = 16.387 064 mL)
            'in³ → mL' => [VolumeUnit::CUBIC_INCH, VolumeUnit::MILLILITER, 16.387_064],
            'in³ → L' => [VolumeUnit::CUBIC_INCH, VolumeUnit::LITER, 0.016_387_064],
            'in³ → ft³' => [VolumeUnit::CUBIC_INCH, VolumeUnit::CUBIC_FOOT, 1.0 / 1_728.0],

            // Cubic yard
            'yd³ → ft³' => [VolumeUnit::CUBIC_YARD, VolumeUnit::CUBIC_FOOT, 27.0],
            'yd³ → L' => [VolumeUnit::CUBIC_YARD, VolumeUnit::LITER, 764.554_857_984],
        ];
    }

    // -------------------------------------------------------------------------
    // getConversionFactor() – US customary liquid volume
    // -------------------------------------------------------------------------

    /**
     * @dataProvider provideUSConversions
     */
    public function testUSConversions(string $from, string $to, float $expected): void
    {
        $this->assertFactorEquals(
            $expected,
            Volume::getConversionFactor($from, $to),
            "{$from} → {$to}"
        );
    }

    public static function provideUSConversions(): array
    {
        return [
            // US gallon as base (all exact ratios within US system)
            'US gal → L' => [VolumeUnit::US_GALLON, VolumeUnit::LITER, 3.785_411_784],
            'US gal → qt' => [VolumeUnit::US_GALLON, VolumeUnit::US_QUART, 4.0],
            'US gal → pt' => [VolumeUnit::US_GALLON, VolumeUnit::US_PINT, 8.0],
            'US gal → cup' => [VolumeUnit::US_GALLON, VolumeUnit::US_CUP, 16.0],
            'US gal → fl oz' => [VolumeUnit::US_GALLON, VolumeUnit::US_FLUID_OUNCE, 128.0],
            'US gal → tbsp' => [VolumeUnit::US_GALLON, VolumeUnit::US_TABLE_SPOON, 256.0],
            'US gal → tsp' => [VolumeUnit::US_GALLON, VolumeUnit::US_TEA_SPOON, 768.0],

            // Liter → US
            'L → US gal' => [VolumeUnit::LITER, VolumeUnit::US_GALLON, 1.0 / 3.785_411_784],
            'L → US qt' => [VolumeUnit::LITER, VolumeUnit::US_QUART, 1.0 / 0.946_352_946],
            'L → US pt' => [VolumeUnit::LITER, VolumeUnit::US_PINT, 1.0 / 0.473_176_473],
            'L → US fl oz' => [VolumeUnit::LITER, VolumeUnit::US_FLUID_OUNCE, 1.0 / 0.029_573_529_6],

            // Sub-unit ratios
            'US qt → pt' => [VolumeUnit::US_QUART, VolumeUnit::US_PINT, 2.0],
            'US pt → cup' => [VolumeUnit::US_PINT, VolumeUnit::US_CUP, 2.0],
            'US cup → fl oz' => [VolumeUnit::US_CUP, VolumeUnit::US_FLUID_OUNCE, 8.0],
            'US fl oz → tbsp' => [VolumeUnit::US_FLUID_OUNCE, VolumeUnit::US_TABLE_SPOON, 2.0],
            'US tbsp → tsp' => [VolumeUnit::US_TABLE_SPOON, VolumeUnit::US_TEA_SPOON, 3.0],
        ];
    }

    // -------------------------------------------------------------------------
    // getConversionFactor() – Imperial liquid volume
    // -------------------------------------------------------------------------

    /**
     * @dataProvider provideImperialConversions
     */
    public function testImperialConversions(string $from, string $to, float $expected): void
    {
        $this->assertFactorEquals(
            $expected,
            Volume::getConversionFactor($from, $to),
            "{$from} → {$to}"
        );
    }

    public static function provideImperialConversions(): array
    {
        return [
            // Imperial gallon as base
            'Imp gal → L' => [VolumeUnit::IMPERIAL_GALLON, VolumeUnit::LITER, 4.546_09],
            'Imp gal → qt' => [VolumeUnit::IMPERIAL_GALLON, VolumeUnit::IMPERIAL_QUART, 4.0],
            'Imp gal → pt' => [VolumeUnit::IMPERIAL_GALLON, VolumeUnit::IMPERIAL_PINT, 8.0],
            'Imp gal → cup' => [VolumeUnit::IMPERIAL_GALLON, VolumeUnit::IMPERIAL_CUP, 16.0],
            'Imp gal → fl oz' => [VolumeUnit::IMPERIAL_GALLON, VolumeUnit::IMPERIAL_FLUID_OUNCE, 160.0],
            'Imp gal → tbsp' => [VolumeUnit::IMPERIAL_GALLON, VolumeUnit::IMPERIAL_TABLE_SPOON, 256.0],
            'Imp gal → tsp' => [VolumeUnit::IMPERIAL_GALLON, VolumeUnit::IMPERIAL_TEA_SPOON, 768.0],

            // Liter → Imperial
            'L → Imp gal' => [VolumeUnit::LITER, VolumeUnit::IMPERIAL_GALLON, 1.0 / 4.546_09],
            'L → Imp fl oz' => [VolumeUnit::LITER, VolumeUnit::IMPERIAL_FLUID_OUNCE, 1.0 / 0.028_413_062_5],

            // Sub-unit ratios
            'Imp qt → pt' => [VolumeUnit::IMPERIAL_QUART, VolumeUnit::IMPERIAL_PINT, 2.0],
            'Imp pt → cup' => [VolumeUnit::IMPERIAL_PINT, VolumeUnit::IMPERIAL_CUP, 2.0],
            'Imp cup → fl oz' => [VolumeUnit::IMPERIAL_CUP, VolumeUnit::IMPERIAL_FLUID_OUNCE, 10.0],
            'Imp fl oz → tbsp' => [VolumeUnit::IMPERIAL_FLUID_OUNCE, VolumeUnit::IMPERIAL_TABLE_SPOON, 1.6],
            'Imp tbsp → tsp' => [VolumeUnit::IMPERIAL_TABLE_SPOON, VolumeUnit::IMPERIAL_TEA_SPOON, 3.0],
        ];
    }

    // -------------------------------------------------------------------------
    // getConversionFactor() – cross-system conversions
    // -------------------------------------------------------------------------

    /**
     * @dataProvider provideCrossSystemConversions
     */
    public function testCrossSystemConversions(string $from, string $to, float $expected): void
    {
        $this->assertFactorEquals(
            $expected,
            Volume::getConversionFactor($from, $to),
            "{$from} → {$to}"
        );
    }

    public static function provideCrossSystemConversions(): array
    {
        return [
            // US gallon vs Imperial gallon (Imperial is ~20% larger)
            'US gal → Imp gal' => [VolumeUnit::US_GALLON, VolumeUnit::IMPERIAL_GALLON, 3.785_411_784 / 4.546_09],
            'Imp gal → US gal' => [VolumeUnit::IMPERIAL_GALLON, VolumeUnit::US_GALLON, 4.546_09 / 3.785_411_784],

            // US fl oz vs Imperial fl oz (different sizes)
            'US fl oz → Imp fl oz' => [
                VolumeUnit::US_FLUID_OUNCE,
                VolumeUnit::IMPERIAL_FLUID_OUNCE,
                0.029_573_529_6 / 0.028_413_062_5
            ],

            // Cubic foot vs US gallon (1 ft³ = 7.480519... US gal)
            'ft³ → US gal' => [
                VolumeUnit::CUBIC_FOOT,
                VolumeUnit::US_GALLON,
                28.316_846_592 / 3.785_411_784
            ],

            // Cubic inch vs US fl oz (1 in³ = 0.554113... US fl oz)
            'in³ → US fl oz' => [
                VolumeUnit::CUBIC_INCH,
                VolumeUnit::US_FLUID_OUNCE,
                0.016_387_064 / 0.029_573_529_6
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // Round-trip consistency: forward × inverse ≈ 1.0
    // -------------------------------------------------------------------------

    /**
     * For any pair (A, B) the product of the A→B and B→A factors must equal 1.
     *
     * @dataProvider provideRoundTripPairs
     */
    public function testRoundTripConsistency(string $a, string $b): void
    {
        $forward = Volume::getConversionFactor($a, $b);
        $inverse = Volume::getConversionFactor($b, $a);

        $this->assertNotNull($forward, "Forward factor {$a}→{$b} must not be null.");
        $this->assertNotNull($inverse, "Inverse factor {$b}→{$a} must not be null.");

        $this->assertEqualsWithDelta(
            1.0,
            $forward * $inverse,
            1e-9,
            "Round-trip {$a} ↔ {$b} must multiply to ~1.0."
        );
    }

    public static function provideRoundTripPairs(): array
    {
        return [
            'L ↔ mL' => [VolumeUnit::LITER, VolumeUnit::MILLILITER],
            'L ↔ m³' => [VolumeUnit::LITER, VolumeUnit::CUBIC_METER],
            'L ↔ US gal' => [VolumeUnit::LITER, VolumeUnit::US_GALLON],
            'L ↔ Imp gal' => [VolumeUnit::LITER, VolumeUnit::IMPERIAL_GALLON],
            'US gal ↔ Imp gal' => [VolumeUnit::US_GALLON, VolumeUnit::IMPERIAL_GALLON],
            'ft³ ↔ in³' => [VolumeUnit::CUBIC_FOOT, VolumeUnit::CUBIC_INCH],
            'yd³ ↔ ft³' => [VolumeUnit::CUBIC_YARD, VolumeUnit::CUBIC_FOOT],
            'US qt ↔ US pt' => [VolumeUnit::US_QUART, VolumeUnit::US_PINT],
            'Imp qt ↔ Imp pt' => [VolumeUnit::IMPERIAL_QUART, VolumeUnit::IMPERIAL_PINT],
            'US tbsp ↔ US tsp' => [VolumeUnit::US_TABLE_SPOON, VolumeUnit::US_TEA_SPOON],
            'Imp tbsp ↔ Imp tsp' => [VolumeUnit::IMPERIAL_TABLE_SPOON, VolumeUnit::IMPERIAL_TEA_SPOON],
            'm³ ↔ km³' => [VolumeUnit::CUBIC_METER, VolumeUnit::CUBIC_KILOMETER],
        ];
    }

    // -------------------------------------------------------------------------
    // convert() – basic value scaling
    // -------------------------------------------------------------------------

    /**
     * @dataProvider provideConvertValues
     */
    public function testConvertScalesValueCorrectly(
        float $value,
        string $from,
        string $to,
        float $expected
    ): void {
        $result = Volume::convert($value, $from, $to);
        $delta = abs($expected) * 1e-6 + 1e-30;
        $this->assertNotNull($result);
        $this->assertEqualsWithDelta($expected, $result, $delta, "{$value} {$from} → {$to}");
    }

    public static function provideConvertValues(): array
    {
        return [
            '1 L → 1000 mL' => [1.0, VolumeUnit::LITER, VolumeUnit::MILLILITER, 1_000.0],
            '2.5 L → 2500 mL' => [2.5, VolumeUnit::LITER, VolumeUnit::MILLILITER, 2_500.0],
            '1 US gal → 3.785... L' => [1.0, VolumeUnit::US_GALLON, VolumeUnit::LITER, 3.785_411_784],
            '0 L → 0 US gal' => [0.0, VolumeUnit::LITER, VolumeUnit::US_GALLON, 0.0],
            '1 ft³ → 1728 in³' => [1.0, VolumeUnit::CUBIC_FOOT, VolumeUnit::CUBIC_INCH, 1_728.0],
            '4 US qt → 1 US gal' => [4.0, VolumeUnit::US_QUART, VolumeUnit::US_GALLON, 1.0],
            '8 Imp pt → 1 Imp gal' => [8.0, VolumeUnit::IMPERIAL_PINT, VolumeUnit::IMPERIAL_GALLON, 1.0],
        ];
    }

    // -------------------------------------------------------------------------
    // convert() – null propagation for unknown units
    // -------------------------------------------------------------------------

    public function testConvertWithUnknownFromReturnsNull(): void
    {
        $this->assertNull(Volume::convert(1.0, 'UNKNOWN', VolumeUnit::LITER));
    }

    public function testConvertWithUnknownToReturnsNull(): void
    {
        $this->assertNull(Volume::convert(1.0, VolumeUnit::LITER, 'UNKNOWN'));
    }

    // -------------------------------------------------------------------------
    // convert() – same-unit always returns the original value
    // -------------------------------------------------------------------------

    /**
     * @dataProvider provideAllUnits
     */
    public function testConvertSameUnitReturnsOriginalValue(string $unit): void
    {
        $value = 42.5;
        $result = Volume::convert($value, $unit, $unit);
        $this->assertSame($value, $result, "Same-unit convert for '{$unit}' must return original value.");
    }
}
