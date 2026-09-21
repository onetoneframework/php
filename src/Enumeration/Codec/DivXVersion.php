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
 * DivX Encoder Version Registry
 *
 * Maps DivX build strings (as written into the AVI stream header) to
 * their human-readable version names.
 * Data provided by Kurohane (http://www.kurohane.net/) for Shinkuu Hadoken.
 *
 * Backing value: build identifier string (as stored in the encoded file).
 */
enum DivXVersion: string
{
    case BUILD_999b000 = 'DivX999b000';
    case BUILD_500b413 = 'DivX500Build413';
    case BUILD_501b413 = 'DivX501b413';
    case BUILD_501b450 = 'DivX501b450';
    case BUILD_501b481 = 'DivX501b481';
    case BUILD_501b484 = 'DivX501b484';
    case BUILD_501b487 = 'DivX501b487';
    case BUILD_503b688 = 'DivX503b688';
    case BUILD_503b696 = 'DivX503b696';
    case BUILD_503b740 = 'DivX503b740';
    case BUILD_503b795 = 'DivX503b795';
    case BUILD_503b804 = 'DivX503b804';
    case BUILD_503b814 = 'DivX503b814';
    case BUILD_503b822 = 'DivX503b822';
    case BUILD_503b830 = 'DivX503b830';
    case BUILD_503b894 = 'DivX503b894';
    case BUILD_503b922 = 'DivX503b922';
    case BUILD_503b936 = 'DivX503b936';
    case BUILD_503b959 = 'DivX503b959';
    case BUILD_503b0985 = 'DivX503b0985';
    case BUILD_503b1009 = 'DivX503b1009';
    case BUILD_503b1025 = 'DivX503b1025';
    case BUILD_503b1031 = 'DivX503b1031';
    case BUILD_503b1263 = 'DivX503b1263';
    case BUILD_503b1272 = 'DivX503b1272';
    case BUILD_503b1307 = 'DivX503b1307';
    case BUILD_503b1314 = 'DivX503b1314';
    case BUILD_503b1328 = 'DivX503b1328';
    case BUILD_503b1338 = 'DivX503b1338';
    case BUILD_503b1393 = 'DivX503b1393';
    case BUILD_503b1394 = 'DivX503b1394';
    case BUILD_503b1408 = 'DivX503b1408';
    case BUILD_503b1453 = 'DivX503b1453';
    case BUILD_503b1461 = 'DivX503b1461';
    case BUILD_503b1528 = 'DivX503b1528';
    case BUILD_503b1571 = 'DivX503b1571';
    case BUILD_503b1594 = 'DivX503b1594';
    case BUILD_503b1599 = 'DivX503b1599';
    case BUILD_503b1612 = 'DivX503b1612';
    case BUILD_503b1670 = 'DivX503b1670';
    case BUILD_503b1697 = 'DivX503b1697';
    case BUILD_503b1737 = 'DivX503b1737';
    case BUILD_503b1786 = 'DivX503b1786';
    case BUILD_503b1807 = 'DivX503b1807';
    case BUILD_503b1814 = 'DivX503b1814';
    case BUILD_503b1828 = 'DivX503b1828';
    case BUILD_503b1838 = 'DivX503b1838';
    case BUILD_503b1856 = 'DivX503b1856';
    case BUILD_503b1893 = 'DivX503b1893';
    case BUILD_503b1910 = 'DivX503b1910';
    case BUILD_503b1913 = 'DivX503b1913';
    case BUILD_503b1915 = 'DivX503b1915';
    case BUILD_503b1920 = 'DivX503b1920';
    case BUILD_503b1974 = 'DivX503b1974';
    case BUILD_503b1977 = 'DivX503b1977';
    case BUILD_503b1988 = 'DivX503b1988';
    case BUILD_503b2081 = 'DivX503b2081';
    case BUILD_503b2086 = 'DivX503b2086';
    case BUILD_503b2121 = 'DivX503b2121';
    case BUILD_503b2151 = 'DivX503b2151';
    case BUILD_503b2201 = 'DivX503b2201';
    case BUILD_503b2207 = 'DivX503b2207';
    case BUILD_503b2292 = 'DivX503b2292';
    case BUILD_503b2306 = 'DivX503b2306';
    case BUILD_503b2309 = 'DivX503b2309';
    case BUILD_503b2318 = 'DivX503b2318';
    case BUILD_503b2376 = 'DivX503b2376';
    case BUILD_503b2396 = 'DivX503b2396';
    case BUILD_503b2432 = 'DivX503b2432';
    case BUILD_503b2510 = 'DivX503b2510';
    case BUILD_503b2521 = 'DivX503b2521';
    case BUILD_503b2559 = 'DivX503b2559';
    case BUILD_503b2676 = 'DivX503b2676';
    case BUILD_503b2816 = 'DivX503b2816';
    case BUILD_503b2991 = 'DivX503b2991';
    case BUILD_503b3013 = 'DivX503b3013';

    // -------------------------------------------------------------------------
    // Helper methods
    // -------------------------------------------------------------------------

    /**
     * Returns the human-readable version name for this build string.
     */
    public function label(): string
    {
        return match ($this) {
            self::BUILD_999b000 => 'XviD MPEG4',
            self::BUILD_500b413 => 'DivX 5.0.0',
            self::BUILD_501b413 => 'DivX 5.0.0 (unconfirmed)',
            self::BUILD_501b450 => 'DivX 5.0.1',
            self::BUILD_501b481 => 'DivX 5.0.2',
            self::BUILD_501b484 => 'DivX 5.0.2',
            self::BUILD_501b487 => 'DivX 5.0.2',
            self::BUILD_503b688 => 'DivX 5.0.3 Beta',
            self::BUILD_503b696 => 'DivX 5.0.3 Beta',
            self::BUILD_503b740 => 'DivX 5.0.3',
            self::BUILD_503b795 => 'DivX 5.0.4 Beta1-2',
            self::BUILD_503b804 => 'DivX 5.0.4 Beta3',
            self::BUILD_503b814 => 'DivX 5.0.4 Beta4',
            self::BUILD_503b822 => 'DivX 5.0.4',
            self::BUILD_503b830 => 'DivX 5.0.5.830',
            self::BUILD_503b894 => 'DivX 5.0.5 Kauehi',
            self::BUILD_503b922 => 'DivX 5.1.0 Beta1',
            self::BUILD_503b936 => 'DivX 5.1.0 Beta2',
            self::BUILD_503b959 => 'DivX 5.1.0.959',
            self::BUILD_503b0985 => 'DivX 5.1 (DivX HD)',
            self::BUILD_503b1009 => 'DivX 5.1.1 Beta1',
            self::BUILD_503b1025 => 'DivX 5.1.1 Beta2',
            self::BUILD_503b1031 => 'DivX 5.1.1.1031',
            self::BUILD_503b1263 => 'DivX 5.2.0',
            self::BUILD_503b1272 => 'DivX 5.2.0 (Dr.DivX 1.0.5)',
            self::BUILD_503b1307 => 'DivX 5.2.1 Alpha (Dr.DivX)',
            self::BUILD_503b1314 => 'DivX Pro 5.2.1 Codec (X-Transcoder)',
            self::BUILD_503b1328 => 'DivX 5.2.1',
            self::BUILD_503b1338 => 'DivX 5.2.1 (Dr.DivX 1.0.6)',
            self::BUILD_503b1393 => 'DivX 5.3.0 (XviD 1.3.2)',
            self::BUILD_503b1394 => 'DivX Pro Plasma Codec Ver.5.3.0 Build 1394',
            self::BUILD_503b1408 => 'DivX Pro Plasma Codec Ver.5.3.0 Build 1408',
            self::BUILD_503b1453 => 'DivX Pro Fusion Beta Build 1453 (DivX Ver.5.9)',
            self::BUILD_503b1461 => 'DivX Pro Fusion Beta Build 1461 (DivX Ver.5.9)',
            self::BUILD_503b1528 => 'DivX 5.9 Fusion',
            self::BUILD_503b1571 => 'DivX 6.0.0',
            self::BUILD_503b1594 => 'DivX 6.0.0 (DivX Converter 1.0)',
            self::BUILD_503b1599 => 'DivX 6 Helium',
            self::BUILD_503b1612 => 'DivX 6 Helium',
            self::BUILD_503b1670 => 'Dr.DivX 1.07',
            self::BUILD_503b1697 => 'DivX 6.0.3 Fusion',
            self::BUILD_503b1737 => 'DivX He-3',
            self::BUILD_503b1786 => 'DivX 6.1.0',
            self::BUILD_503b1807 => 'DivX 6.1.0 Patch 1 Beta',
            self::BUILD_503b1814 => 'DivX 6.1.0 Patch 2 Beta',
            self::BUILD_503b1828 => 'DivX 6.1.1',
            self::BUILD_503b1838 => 'DivX 6 (TMPGEnc 4.1.0.180)',
            self::BUILD_503b1856 => 'DivX 6.1.1',
            self::BUILD_503b1893 => 'DivX 6.2.0 Beta 1',
            self::BUILD_503b1910 => 'DivX 6.2.0',
            self::BUILD_503b1913 => 'DivX 6.2.1',
            self::BUILD_503b1915 => 'DivX 6.2.1 Patch 1 Beta',
            self::BUILD_503b1920 => 'DivX 6.2.2',
            self::BUILD_503b1974 => 'DivX 6.2.5',
            self::BUILD_503b1977 => 'DivX 6.2.5',
            self::BUILD_503b1988 => 'DivX 6.2.5 (DivX Converter 6.2.1)',
            self::BUILD_503b2081 => 'DivX 6.4 Beta 1',
            self::BUILD_503b2086 => 'DivX 6.4',
            self::BUILD_503b2121 => 'DivX 6.4 (TMPGEnc 4.0.3.169)',
            self::BUILD_503b2151 => 'DivX 6.4 (DivX Author)',
            self::BUILD_503b2201 => 'DivX 6.5.0',
            self::BUILD_503b2207 => 'DivX 6.5.1',
            self::BUILD_503b2292 => 'DivX 6.6.0',
            self::BUILD_503b2306 => 'DivX 6.6.1',
            self::BUILD_503b2309 => 'DivX 6.6.1',
            self::BUILD_503b2318 => 'DivX 6.6.1.4',
            self::BUILD_503b2376 => 'DivX 6 (Stage6)',
            self::BUILD_503b2396 => 'DivX 6.7 Beta',
            self::BUILD_503b2432 => 'DivX 6.7.0.28',
            self::BUILD_503b2510 => 'DivX 6.8.0.14',
            self::BUILD_503b2521 => 'DivX 6.8.0 (DivX Converter 6.6)',
            self::BUILD_503b2559 => 'DivX 6.8.2.6',
            self::BUILD_503b2676 => 'DivX 6.8.3.13',
            self::BUILD_503b2816 => 'DivX 6.8.5.5',
            self::BUILD_503b2991 => 'DivX 6.8.5 (TMPGEnc 4.7.3.292)',
            self::BUILD_503b3013 => 'DivX 6.8.5',
        };
    }
}
