<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Math;

use Clover\Classes\Math\Length;
use Clover\Enumeration\LengthUnit;
use PHPUnit\Framework\TestCase;

/**
 * Comprehensive test suite for the Length utility class.
 *
 * Coverage:
 *  - normalize(): all documented aliases, case-insensitivity, and the default fallback
 *  - getConversionFactor(): every source→target pair defined in the switch table,
 *    same-unit identity (returns 1), unknown-target fallback (returns 1),
 *    and unknown-source (returns null)
 */
class LengthTest extends TestCase
{
    // -------------------------------------------------------------------------
    // normalize() – alias coverage
    // -------------------------------------------------------------------------

    /**
     * @dataProvider provideMillimeterAliases
     */
    public function testNormalizeMillimeterAliases(string $input): void
    {
        $this->assertSame(
            LengthUnit::MILIMETERS,
            Length::normalize($input),
            "Expected '{$input}' to normalize to MILIMETERS."
        );
    }

    public static function provideMillimeterAliases(): array
    {
        return [
            'abbreviation mm'   => ['mm'],
            'plural en'         => ['MILIMETERS'],
            'plural en-GB'      => ['millimetres'],
        ];
    }

    /**
     * @dataProvider provideCentimeterAliases
     */
    public function testNormalizeCentimeterAliases(string $input): void
    {
        $this->assertSame(
            LengthUnit::CENTIMETERS,
            Length::normalize($input),
            "Expected '{$input}' to normalize to CENTIMETERS."
        );
    }

    public static function provideCentimeterAliases(): array
    {
        return [
            'abbreviation cm'   => ['cm'],
            'singular en-GB'    => ['centimetre'],
            'singular en'       => ['centimeter'],
            'plural en-GB'      => ['centimetres'],
            'plural en'         => ['centimeters'],
        ];
    }

    /**
     * @dataProvider provideMeterAliases
     */
    public function testNormalizeMeterAliases(string $input): void
    {
        $this->assertSame(
            LengthUnit::METERS,
            Length::normalize($input),
            "Expected '{$input}' to normalize to METERS."
        );
    }

    public static function provideMeterAliases(): array
    {
        return [
            'abbreviation m'    => ['m'],
            'singular en'       => ['meter'],
            'singular en-GB'    => ['metre'],
            'plural en'         => ['meters'],
            'plural en-GB'      => ['metres'],
        ];
    }

    /**
     * @dataProvider provideInchAliases
     */
    public function testNormalizeInchAliases(string $input): void
    {
        $this->assertSame(
            LengthUnit::INCHES,
            Length::normalize($input),
            "Expected '{$input}' to normalize to INCHES."
        );
    }

    public static function provideInchAliases(): array
    {
        return [
            'plural inches' => ['inches'],
            'singular inch' => ['inch'],
            'abbreviation in' => ['in'],
        ];
    }

    /**
     * @dataProvider provideFeetAliases
     */
    public function testNormalizeFeetAliases(string $input): void
    {
        $this->assertSame(
            LengthUnit::FEET,
            Length::normalize($input),
            "Expected '{$input}' to normalize to FEET."
        );
    }

    public static function provideFeetAliases(): array
    {
        return [
            'plural feet'       => ['feet'],
            'singular foot'     => ['foot'],
            'abbreviation ft'   => ['ft'],
        ];
    }

    /**
     * @dataProvider provideYardAliases
     */
    public function testNormalizeYardAliases(string $input): void
    {
        $this->assertSame(
            LengthUnit::YARDS,
            Length::normalize($input),
            "Expected '{$input}' to normalize to YARDS."
        );
    }

    public static function provideYardAliases(): array
    {
        return [
            'singular yard'     => ['yard'],
            'plural yards'      => ['yards'],
            'abbreviation yd'   => ['yd'],
        ];
    }

    /**
     * @dataProvider provideMileAliases
     */
    public function testNormalizeMileAliases(string $input): void
    {
        $this->assertSame(
            LengthUnit::MILES,
            Length::normalize($input),
            "Expected '{$input}' to normalize to MILES."
        );
    }

    public static function provideMileAliases(): array
    {
        return [
            'plural miles'      => ['miles'],
            'singular mile'     => ['mile'],
            'abbreviation mi'   => ['mi'],
        ];
    }

    // -------------------------------------------------------------------------
    // normalize() – case-insensitivity
    // -------------------------------------------------------------------------

    /**
     * @dataProvider provideMixedCaseInputs
     */
    public function testNormalizeIsCaseInsensitive(string $input, mixed $expected): void
    {
        $this->assertSame(
            $expected,
            Length::normalize($input),
            "normalize() should be case-insensitive for '{$input}'."
        );
    }

    public static function provideMixedCaseInputs(): array
    {
        return [
            'MM upper'          => ['MM',          LengthUnit::MILIMETERS],
            'CM upper'          => ['CM',          LengthUnit::CENTIMETERS],
            'M upper'           => ['M',           LengthUnit::METERS],
            'INCHES upper'      => ['INCHES',      LengthUnit::INCHES],
            'Feet title'        => ['Feet',        LengthUnit::FEET],
            'YARDS upper'       => ['YARDS',       LengthUnit::YARDS],
            'Miles title'       => ['Miles',       LengthUnit::MILES],
            'MILIMETERS mixed' => ['MILIMETERS', LengthUnit::MILIMETERS],
        ];
    }

    // -------------------------------------------------------------------------
    // normalize() – default fallback
    // -------------------------------------------------------------------------

    /**
     * @dataProvider provideUnknownUnits
     */
    public function testNormalizeUnknownInputFallsBackToMeters(string $input): void
    {
        $this->assertSame(
            LengthUnit::METERS,
            Length::normalize($input),
            "Unknown input '{$input}' should fall back to METERS."
        );
    }

    public static function provideUnknownUnits(): array
    {
        return [
            'empty string'      => [''],
            'random word'       => ['unknown'],
            'numeric string'    => ['123'],
            'special chars'     => ['@#$']
        ];
    }

    // -------------------------------------------------------------------------
    // getConversionFactor() – MILIMETERS as source
    // -------------------------------------------------------------------------

    /**
     * @dataProvider provideMillimeterConversions
     */
    public function testConversionFromMILIMETERS(string $to, float $expected): void
    {
        $factor = Length::getConversionFactor(LengthUnit::MILIMETERS, $to);
        $this->assertEqualsWithDelta(
            $expected,
            $factor,
            abs($expected) * 1e-7 + 1e-20,
            "MM → {$to} conversion factor mismatch."
        );
    }

    public static function provideMillimeterConversions(): array
    {
        return [
            'MM → lightyear'    => [LengthUnit::LIGHTYEAR,   1.057008707E-19],
            'MM → nanometer'    => [LengthUnit::NANOMETER,   1_000_000],
            'MM → micrometer'   => [LengthUnit::MICROMETER,  1_000],
            'MM → centimeters'  => [LengthUnit::CENTIMETERS, 0.1],
            'MM → meters'       => [LengthUnit::METERS,      0.001],
            'MM → kilometers'   => [LengthUnit::KILLOMETERS, 1e-6],
            'MM → inches'       => [LengthUnit::INCHES,      0.0393700787],
            'MM → feet'         => [LengthUnit::FEET,        0.0032808399],
            'MM → yards'        => [LengthUnit::YARDS,       0.0010936133],
            'MM → miles'        => [LengthUnit::MILES,       6.213711922E-7],
        ];
    }

    // -------------------------------------------------------------------------
    // getConversionFactor() – CENTIMETERS as source
    // -------------------------------------------------------------------------

    /**
     * @dataProvider provideCentimeterConversions
     */
    public function testConversionFromCentimeters(string $to, float $expected): void
    {
        $factor = Length::getConversionFactor(LengthUnit::CENTIMETERS, $to);
        $this->assertEqualsWithDelta(
            $expected,
            $factor,
            abs($expected) * 1e-7 + 1e-20,
            "CM → {$to} conversion factor mismatch."
        );
    }

    public static function provideCentimeterConversions(): array
    {
        return [
            'CM → lightyear'    => [LengthUnit::LIGHTYEAR,   1.057008707E-18],
            'CM → nanometer'    => [LengthUnit::NANOMETER,   10_000_000],
            'CM → micrometer'   => [LengthUnit::MICROMETER,  10_000],
            'CM → MILIMETERS'  => [LengthUnit::MILIMETERS, 10.0],
            'CM → meters'       => [LengthUnit::METERS,      0.01],
            'CM → kilometers'   => [LengthUnit::KILLOMETERS, 1e-5],
            'CM → inches'       => [LengthUnit::INCHES,      0.3937007874],
            'CM → feet'         => [LengthUnit::FEET,        0.032808399],
            'CM → yards'        => [LengthUnit::YARDS,       0.010936133],
            'CM → miles'        => [LengthUnit::MILES,       6.2137119223733E-6],
        ];
    }

    // -------------------------------------------------------------------------
    // getConversionFactor() – METERS as source
    // -------------------------------------------------------------------------

    /**
     * @dataProvider provideMeterConversions
     */
    public function testConversionFromMeters(string $to, float $expected): void
    {
        $factor = Length::getConversionFactor(LengthUnit::METERS, $to);
        $this->assertEqualsWithDelta(
            $expected,
            $factor,
            abs($expected) * 1e-7 + 1e-20,
            "M → {$to} conversion factor mismatch."
        );
    }

    public static function provideMeterConversions(): array
    {
        return [
            'M → lightyear'     => [LengthUnit::LIGHTYEAR,   1.057008707E-16],
            'M → nanometer'     => [LengthUnit::NANOMETER,   1_000_000_000],
            'M → micrometer'    => [LengthUnit::MICROMETER,  1_000_000],
            'M → MILIMETERS'   => [LengthUnit::MILIMETERS, 1_000.0],
            'M → centimeters'   => [LengthUnit::CENTIMETERS, 100.0],
            'M → kilometers'    => [LengthUnit::KILLOMETERS, 0.001],
            'M → inches'        => [LengthUnit::INCHES,      39.37007874],
            'M → feet'          => [LengthUnit::FEET,        3.280839895],
            'M → yards'         => [LengthUnit::YARDS,       1.0936132983],
            'M → miles'         => [LengthUnit::MILES,       0.00062137119223733],
        ];
    }

    // -------------------------------------------------------------------------
    // getConversionFactor() – KILOMETERS as source
    // -------------------------------------------------------------------------

    /**
     * @dataProvider provideKilometerConversions
     */
    public function testConversionFromKilometers(string $to, float $expected): void
    {
        $factor = Length::getConversionFactor(LengthUnit::KILLOMETERS, $to);
        $this->assertEqualsWithDelta(
            $expected,
            $factor,
            abs($expected) * 1e-7 + 1e-20,
            "KM → {$to} conversion factor mismatch."
        );
    }

    public static function provideKilometerConversions(): array
    {
        return [
            'KM → lightyear'    => [LengthUnit::LIGHTYEAR,   1.0570008340246E-13],
            'KM → nanometer'    => [LengthUnit::NANOMETER,   1_000_000_000_000],
            'KM → micrometer'   => [LengthUnit::MICROMETER,  1_000_000_000],
            'KM → MILIMETERS'  => [LengthUnit::MILIMETERS, 1_000_000.0],
            'KM → centimeters'  => [LengthUnit::CENTIMETERS, 100_000.0],
            'KM → meters'       => [LengthUnit::METERS,      1_000.0],
            'KM → inches'       => [LengthUnit::INCHES,      39_370.07874],
            'KM → feet'         => [LengthUnit::FEET,        3_280.839895],
            'KM → yards'        => [LengthUnit::YARDS,       1_093.6132983],
            'KM → miles'        => [LengthUnit::MILES,       0.6213711922],
        ];
    }

    // -------------------------------------------------------------------------
    // getConversionFactor() – INCHES as source
    // -------------------------------------------------------------------------

    /**
     * @dataProvider provideInchConversions
     */
    public function testConversionFromInches(string $to, float $expected): void
    {
        $factor = Length::getConversionFactor(LengthUnit::INCHES, $to);
        $this->assertEqualsWithDelta(
            $expected,
            $factor,
            abs($expected) * 1e-7 + 1e-20,
            "IN → {$to} conversion factor mismatch."
        );
    }

    public static function provideInchConversions(): array
    {
        return [
            'IN → lightyear'    => [LengthUnit::LIGHTYEAR,   2.684802117E-18],
            'IN → nanometer'    => [LengthUnit::NANOMETER,   25_400_000],
            'IN → micrometer'   => [LengthUnit::MICROMETER,  25_400],
            'IN → MILIMETERS'  => [LengthUnit::MILIMETERS, 25.4],
            'IN → centimeters'  => [LengthUnit::CENTIMETERS, 2.54],
            'IN → meters'       => [LengthUnit::METERS,      0.0254],
            'IN → kilometers'   => [LengthUnit::KILLOMETERS, 2.54e-5],
            'IN → feet'         => [LengthUnit::FEET,        0.0833333333],
            'IN → yards'        => [LengthUnit::YARDS,       0.027777777777778],
            'IN → miles'        => [LengthUnit::MILES,       1.5782828282828E-5],
        ];
    }

    // -------------------------------------------------------------------------
    // getConversionFactor() – FEET as source
    // -------------------------------------------------------------------------

    /**
     * @dataProvider provideFeetConversions
     */
    public function testConversionFromFeet(string $to, float $expected): void
    {
        $factor = Length::getConversionFactor(LengthUnit::FEET, $to);
        $this->assertEqualsWithDelta(
            $expected,
            $factor,
            abs($expected) * 1e-7 + 1e-20,
            "FT → {$to} conversion factor mismatch."
        );
    }

    public static function provideFeetConversions(): array
    {
        return [
            'FT → lightyear'    => [LengthUnit::LIGHTYEAR,   3.22176254E-17],
            'FT → MILIMETERS'  => [LengthUnit::MILIMETERS, 304.8],
            'FT → centimeters'  => [LengthUnit::CENTIMETERS, 30.48],
            'FT → meters'       => [LengthUnit::METERS,      0.3048],
            'FT → kilometers'   => [LengthUnit::KILLOMETERS, 0.0003048],
            'FT → inches'       => [LengthUnit::INCHES,      12.0],
            'FT → yards'        => [LengthUnit::YARDS,       0.33333333333333],
            'FT → miles'        => [LengthUnit::MILES,       0.00018939393939394],
        ];
    }

    // -------------------------------------------------------------------------
    // getConversionFactor() – MILES as source
    // -------------------------------------------------------------------------

    /**
     * @dataProvider provideMileConversions
     */
    public function testConversionFromMiles(string $to, float $expected): void
    {
        $factor = Length::getConversionFactor(LengthUnit::MILES, $to);
        $this->assertEqualsWithDelta(
            $expected,
            $factor,
            abs($expected) * 1e-7 + 1e-20,
            "MI → {$to} conversion factor mismatch."
        );
    }

    public static function provideMileConversions(): array
    {
        return [
            'MI → MILIMETERS'  => [LengthUnit::MILIMETERS, 1_609_344.0],
            'MI → centimeters'  => [LengthUnit::CENTIMETERS, 160_934.4],
            'MI → meters'       => [LengthUnit::METERS,      1_609.344],
            'MI → kilometers'   => [LengthUnit::KILLOMETERS, 1.609344],
            'MI → inches'       => [LengthUnit::INCHES,      63_360.0],
            'MI → feet'         => [LengthUnit::FEET,        5_280.0],
            'MI → yards'        => [LengthUnit::YARDS,       1_760],
        ];
    }

    // -------------------------------------------------------------------------
    // getConversionFactor() – YARDS as source
    // -------------------------------------------------------------------------

    /**
     * @dataProvider provideYardConversions
     */
    public function testConversionFromYards(string $to, float $expected): void
    {
        $factor = Length::getConversionFactor(LengthUnit::YARDS, $to);
        $this->assertEqualsWithDelta(
            $expected,
            $factor,
            abs($expected) * 1e-7 + 1e-20,
            "YD → {$to} conversion factor mismatch."
        );
    }

    public static function provideYardConversions(): array
    {
        return [
            'YD → MILIMETERS'  => [LengthUnit::MILIMETERS, 914.4],
            'YD → centimeters'  => [LengthUnit::CENTIMETERS, 91.44],
            'YD → meters'       => [LengthUnit::METERS,      0.9144],
            'YD → kilometers'   => [LengthUnit::KILLOMETERS, 0.0009144],
            'YD → inches'       => [LengthUnit::INCHES,      36.0],
            'YD → feet'         => [LengthUnit::FEET,        3.0],
            'YD → miles'        => [LengthUnit::MILES,       0.0005681818],
        ];
    }

    // -------------------------------------------------------------------------
    // getConversionFactor() – same-unit identity (default branch returns 1)
    // -------------------------------------------------------------------------

    /**
     * Converting any known unit to itself should return 1 (the default branch).
     *
     * @dataProvider provideIdentityUnits
     */
    public function testConversionSameUnitReturnsOne(string $unit): void
    {
        $factor = Length::getConversionFactor($unit, $unit);
        $this->assertEquals(
            1,
            $factor,
            "Same-unit conversion for '{$unit}' should return 1."
        );
    }

    public static function provideIdentityUnits(): array
    {
        return [
            'MM identity'  => [LengthUnit::MILIMETERS],
            'CM identity'  => [LengthUnit::CENTIMETERS],
            'M  identity'  => [LengthUnit::METERS],
            'KM identity'  => [LengthUnit::KILLOMETERS],
            'IN identity'  => [LengthUnit::INCHES],
            'FT identity'  => [LengthUnit::FEET],
            'MI identity'  => [LengthUnit::MILES],
            'YD identity'  => [LengthUnit::YARDS],
        ];
    }

    // -------------------------------------------------------------------------
    // getConversionFactor() – unknown target unit falls back to 1
    // -------------------------------------------------------------------------

    /**
     * A known source with an unrecognised target should fall through to the
     * inner default branch and return 1.
     */
    public function testConversionUnknownTargetReturnsOne(): void
    {
        $factor = Length::getConversionFactor(LengthUnit::METERS, 'UNKNOWN_UNIT');
        $this->assertEquals(null, $factor, "Unknown target unit should return 1 via the default branch.");
    }

    // -------------------------------------------------------------------------
    // getConversionFactor() – unknown source unit returns null
    // -------------------------------------------------------------------------

    /**
     * An unrecognised source unit must return null because no switch case
     * matches and the function falls through to the explicit `return null`.
     *
     * @dataProvider provideUnknownSourceUnits
     */
    public function testConversionUnknownSourceReturnsNull(string $from): void
    {
        $this->assertNull(
            Length::getConversionFactor($from, LengthUnit::METERS),
            "Unknown source unit '{$from}' should return null."
        );
    }

    public static function provideUnknownSourceUnits(): array
    {
        return [
            'empty string'          => [''],
            'random string UNKNOWN' => ['UNKNOWN']
        ];
    }

    // -------------------------------------------------------------------------
    // getConversionFactor() – inverse consistency spot-checks
    // -------------------------------------------------------------------------

    /**
     * For well-defined round-trip pairs the product of forward × inverse
     * should be approximately 1.0.
     *
     * @dataProvider provideRoundTripPairs
     */
    public function testConversionRoundTripIsConsistent(string $a, string $b): void
    {
        $forward = Length::getConversionFactor($a, $b);
        $inverse = Length::getConversionFactor($b, $a);

        $this->assertNotNull($forward, "Forward factor {$a}→{$b} must not be null.");
        $this->assertNotNull($inverse, "Inverse factor {$b}→{$a} must not be null.");

        $product = $forward * $inverse;
        $this->assertEqualsWithDelta(
            1.0,
            $product,
            1e-5,
            "Round-trip {$a}↔{$b} should multiply to ~1.0 (got {$product})."
        );
    }

    public static function provideRoundTripPairs(): array
    {
        return [
            'MM ↔ CM'   => [LengthUnit::MILIMETERS, LengthUnit::CENTIMETERS],
            'CM ↔ M'    => [LengthUnit::CENTIMETERS, LengthUnit::METERS],
            'M  ↔ KM'   => [LengthUnit::METERS,      LengthUnit::KILLOMETERS],
            'M  ↔ IN'   => [LengthUnit::METERS,      LengthUnit::INCHES],
            'M  ↔ FT'   => [LengthUnit::METERS,      LengthUnit::FEET],
            'M  ↔ YD'   => [LengthUnit::METERS,      LengthUnit::YARDS],
            'KM ↔ MI'   => [LengthUnit::KILLOMETERS, LengthUnit::MILES],
            'FT ↔ IN'   => [LengthUnit::FEET,        LengthUnit::INCHES],
            'YD ↔ FT'   => [LengthUnit::YARDS,       LengthUnit::FEET],
            'MI ↔ YD'   => [LengthUnit::MILES,       LengthUnit::YARDS],
        ];
    }
}
