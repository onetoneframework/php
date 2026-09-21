<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration\Codec;

/**
 * XviD Encoder Version Registry
 *
 * Maps XviD build strings (as written into the AVI stream header) to
 * their human-readable version names.
 * Data provided by Kurohane (http://www.kurohane.net/) for Shinkuu Hadoken.
 *
 * Backing value: build identifier string (as stored in the encoded file).
 */
enum XviDVersion: string
{
    case BUILD_0009 = 'XviD0009';
    case BUILD_0011 = 'XviD0011';
    case BUILD_0012 = 'XviD0012';
    case BUILD_0016 = 'XviD0016';
    case BUILD_0019 = 'XviD0019';
    case BUILD_0020 = 'XviD0020';
    case BUILD_0021 = 'XviD0021';
    case BUILD_0022 = 'XviD0022';
    case BUILD_0023 = 'XviD0023';
    case BUILD_0024 = 'XviD0024';
    case BUILD_0025 = 'XviD0025';
    case BUILD_0026 = 'XviD0026';
    case BUILD_0027 = 'XviD0027';
    case BUILD_0028 = 'XviD0028';
    case BUILD_0029 = 'XviD0029';
    case BUILD_0030 = 'XviD0030';
    case BUILD_0031 = 'XviD0031';
    case BUILD_0032 = 'XviD0032';
    case BUILD_0033 = 'XviD0033';
    case BUILD_0034 = 'XviD0034';
    case BUILD_0035 = 'XviD0035';
    case BUILD_0036 = 'XviD0036';
    case BUILD_0037 = 'XviD0037';
    case BUILD_0038 = 'XviD0038';
    case BUILD_0039 = 'XviD0039';
    case BUILD_0040 = 'XviD0040';
    case BUILD_0041 = 'XviD0041';
    case BUILD_0042 = 'XviD0042';
    case BUILD_0043 = 'XviD0043';
    case BUILD_0044 = 'XviD0044';
    case BUILD_0045 = 'XviD0045';
    case BUILD_0046 = 'XviD0046';
    case BUILD_0047 = 'XviD0047';
    case BUILD_0048 = 'XviD0048';
    case BUILD_0049 = 'XviD0049';
    case BUILD_0050 = 'XviD0050';
    case BUILD_0055 = 'XviD0055';
    case BUILD_0057 = 'XviD0057';
    case BUILD_0061 = 'XviD0061';
    case BUILD_0062 = 'XviD0062';
    case BUILD_0063 = 'XviD0063';
    case BUILD_0064 = 'XviD0064';
    case BUILD_0065 = 'XviD0065';
    case BUILD_0070 = 'XviD0070';

    // -------------------------------------------------------------------------
    // Helper methods
    // -------------------------------------------------------------------------

    /**
     * Returns the human-readable version name for this build string.
     */
    public function label(): string
    {
        return match ($this) {
            self::BUILD_0009 => 'XviD 0.9 BitStream 9',
            self::BUILD_0011 => 'XviD 0.9 Patch-66',
            self::BUILD_0012 => 'XviD 0.9 BitStream 12',
            self::BUILD_0016 => 'XviD 1.0 Patch-13',
            self::BUILD_0019 => 'XviD 1.0 Patch-47',
            self::BUILD_0020 => 'XviD 1.0 Patch-61',
            self::BUILD_0021 => 'XviD 1.0.0 Beta1',
            self::BUILD_0022 => 'XviD 1.0.0 Beta1.5',
            self::BUILD_0023 => 'XviD 1.0.0 Beta2',
            self::BUILD_0024 => 'XviD 1.0.0 Beta2.5',
            self::BUILD_0025 => 'XviD 1.0.0 Beta3',
            self::BUILD_0026 => 'XviD 1.0.0 RC1',
            self::BUILD_0027 => 'XviD 1.0.0 RC1b',
            self::BUILD_0028 => 'XviD 1.0.0 RC2',
            self::BUILD_0029 => 'XviD 1.0.0 RC3',
            self::BUILD_0030 => 'XviD 1.0.0 RC4',
            self::BUILD_0031 => 'XviD 1.0.0 RC4b',
            self::BUILD_0032 => 'XviD 1.0.0 RC4c',
            self::BUILD_0033 => 'XviD 1.0.0 RC4d',
            self::BUILD_0034 => 'XviD 1.0.0',
            self::BUILD_0035 => 'XviD 1.0.1',
            self::BUILD_0036 => 'XviD 1.0.2',
            self::BUILD_0037 => 'XviD 1.0.3',
            self::BUILD_0038 => 'XviD 1.1.0 Beta1',
            self::BUILD_0039 => 'XviD 1.1.0 Beta2',
            self::BUILD_0040 => 'XviD 1.1.0',
            self::BUILD_0041 => 'XviD 1.1.0 Final',
            self::BUILD_0042 => 'XviD 1.2.-127',
            self::BUILD_0043 => 'XviD 1.2 SMP',
            self::BUILD_0044 => 'XviD 1.1.1',
            self::BUILD_0045 => 'XviD 1.2.-127',
            self::BUILD_0046 => 'XviD 1.1.2 Final',
            self::BUILD_0047 => 'XviD 1.1.-127',
            self::BUILD_0048 => 'XviD 1.2.-127',
            self::BUILD_0049 => 'XviD 1.2.0',
            self::BUILD_0050 => 'XviD 1.2.1',
            self::BUILD_0055 => 'XviD 1.3.-127',
            self::BUILD_0057 => 'XviD 1.3.-127 Dev',
            self::BUILD_0061 => 'XviD 1.3.0 RC1',
            self::BUILD_0062 => 'XviD 1.3.0 Final',
            self::BUILD_0063 => 'XviD 1.3.1 Final',
            self::BUILD_0064 => 'XviD 1.3.2',
            self::BUILD_0065 => 'XviD 1.3.3',
            self::BUILD_0070 => 'XviD 1.4.-127 Dev',
        };
    }
}
