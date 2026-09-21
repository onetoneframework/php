<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration;

abstract class CommonChordProgression
{
    public const PACHELBEL_CANON = 'I-V-VIm-IIIm-IV-I-IV-V';
    public const JAZZ_BLUES = 'I7-IV7-I7-VI7-II7-V7-I7-IV7-I7-VI7-II7-V7';
    public const COLTRANE_CYCLE = 'Imaj7-III7-VI7-IIm7-V7';
    public const CIRCLE_OF_FIFTHS = 'I-IV-VIImø-IIIm-VIm-IIm-V-I';
    public const EXTENDED_DOO_WOP_BALLAD = 'Imaj7-VIm7-IVmaj7-V7-Imaj7-VIm7-IVmaj7-V7-IIm7-V7';
    public const COLTRANE_MINOR_CYCLE = 'Im7-bIII7-VI7-IIm7-V7-Im7-bIII7-VI7-IIø7-V7(b9)';
    public const GOSPEL_LIFT_SEQUENCE = 'IVmaj9-Imaj7-IIm7-V7-Imaj7-VIm7-IVmaj9-V7sus4-IIm7-V7';
    public const COMMON_12_BAR_BLUES = 'I7-IV7-I7-I7-IV7-IV7-I7-I7-V7-IV7-I7-V7';
    public const DOO_WAP = 'I-VIm-IV-V-I-VIm-IV-V-I-VIm-IV-V';
    public const RHYTHM_CHANGE = 'I6-VI7-IIIm7-VI7-IIm7-V7-I6-VI7-IIm7-V7-I6-VI7-IIIm7-VI7-IIm7-V7';
    public const NEO_SOUL_16_BAR_CYCLE = 'Imaj9-VI7sus4-IIIm7-V7b9-Imaj9-VI7sus4-IIIm7-V7b9-IVmaj7-II7-VIm7-III7-IVmaj7-II7-VIm7-V7sus4';
    public const EXTENDED_LIQUID_DNB = 'Im-VI-III-VII-im-VI-III-VII-IVm-VII-III-VI';
}