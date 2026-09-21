<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Math;

use Clover\Enumeration\MessUnit;

class Mass
{
    /**
     * Grams per unit.
     *
     * SI/metric units are exact by definition.
     * Avoirdupois pound = 453.592 37 g (exact, since 1959).
     * All avoirdupois sub-units are derived from the pound.
     * Troy ounce = 31.103 476 8 g (exact).
     * Slug = 1 lbf·s²/ft = 14 593.902 937 … g.
     * Atomic mass unit (dalton) = 1.660 539 066 60 × 10⁻²⁴ g (CODATA 2018).
     */
    private const GRAMS_PER_UNIT = [
        MessUnit::GRAM => 1.0,
        MessUnit::DESHGRAM => 10.0,                       // decagram
        MessUnit::HEXTOGRAM => 100.0,                      // hectogram
        MessUnit::KILLOGRAM => 1_000.0,                    // kilogram
        MessUnit::MEGAGRAM => 1_000_000.0,
        MessUnit::METERTON => 1_000_000.0,                // metric ton = megagram
        MessUnit::CENTIGRAM => 0.01,
        MessUnit::MILLIGRAM => 0.001,
        MessUnit::MICROGRAM => 0.000_001,
        MessUnit::NANOGRAM => 0.000_000_001,
        MessUnit::PICOGRAM => 1.0e-12,
        MessUnit::PEMTOGRAM => 1.0e-15,                    // femtogram
            // 1 lb = 453.592 37 g (exact)
        MessUnit::POUND => 453.592_37,
            // 1 oz = 1/16 lb
        MessUnit::OUNCE => 28.349_523_125,
            // 1 troy oz = 480 grains; 1 grain = 64.798 91 mg
        MessUnit::TRIONCE => 31.103_476_8,
            // 1 dram (avoirdupois) = 1/256 lb
        MessUnit::DRAM => 1.771_845_195_312_5,
            // DRAIN treated as apothecary dram = 1/8 apothecary ounce = 3.887 934 6 g
        MessUnit::DRAIN => 3.887_934_6,
            // 1 stone = 14 lb
        MessUnit::STONE => 6_350.293_18,
            // 1 slug = lb·s²/ft
        MessUnit::SLUG => 14_593.902_937,
            // 1 short ton (US) = 2 000 lb
        MessUnit::TON_USA => 907_184.74,
            // 1 long ton (England) = 2 240 lb
        MessUnit::TON_ENGLAND => 1_016_046.908_8,
            // metric quintal = 100 kg
        MessUnit::QUINTILE_METER => 100_000.0,
            // US quintal = 100 lb
        MessUnit::QUINTILE_USA => 45_359.237,
            // French quintal = 100 kg (same as metric)
        MessUnit::QUINTILE_FRANCE => 100_000.0,
            // 1 tola (India) = 180 grains
        MessUnit::TOLA => 11.663_8,
            // 1 carat = 200 mg (exact, since 1907)
        MessUnit::CARROT => 0.2,
            // atomic mass unit (dalton)
        MessUnit::ATOMIC => 1.660_539_066_60e-24,
    ];

    public static function getConversionFactor(string $from, string $to): ?float
    {
        if (!isset(self::GRAMS_PER_UNIT[$from], self::GRAMS_PER_UNIT[$to])) {
            return null;
        }

        if ($from === $to) {
            return 1.0;
        }

        return self::GRAMS_PER_UNIT[$from] / self::GRAMS_PER_UNIT[$to];
    }

    public static function convert(float $value, string $from, string $to): ?float
    {
        $factor = self::getConversionFactor($from, $to);

        if ($factor === null) {
            return null;
        }

        return $value * $factor;
    }
}
