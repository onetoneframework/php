<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration;

abstract class MusicScaleType
{
    public const CHROMATIC = 'chromatic';
    public const AUGMENTED = 'augmented';
    public const IONIAN = 'ionian';
    public const PENTATONIC = 'pentatonic';
    public const PENTATONIC_MINOR = 'pentatonic_minor';
    public const HARMONIC_MINOR = 'harmonic_minor';
    public const HARMONIC_MAJOR = 'harmonic_major';
    public const MELODIC_MINOR = 'melodic_minor';
    public const DORIAN = 'dorian';
    public const PHRYGIAN = 'phrygian';
    public const LYDIAN = 'lydian';
    public const MIXOLYDIAN = 'mixolydian';
    public const AEOLIAN = 'aeolian';
    public const LOCRIAN = 'locrian';
}