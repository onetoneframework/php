<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration;

abstract class MusicSymbol
{
    public const ALTO_CLEF = '𝄡';
    public const BASS_CLEF = '𝄢';
    public const COMMON_TIME = '𝄴'; 
    public const CRESCENDO = 'crescendo';
    public const CUT_TIME = '𝄵'; 
    public const DECRESCENDO = 'diminuendo';
    public const EIGHTH_NOTE = '♪';
    public const EIGHTH_REST = '♪';
    public const END_REPEAT = '𝄇';
    public const FERMATA = '𝄐';
    public const FLAT = '♭';
    public const HALF_NOTE = 'd';
    public const HALF_REST = '𝄼';
    public const NATURAL = '♮';
    public const QUARTER_NOTE = '♩';
    public const QUARTER_REST = '♩';
    public const REPEAT = '𝄆';
    public const SHARP = '♯';
    public const SIXTEENTH_NOTE = '♫';
    public const SIXTEENTH_REST = '♫'; 
    public const STACCATO = '·';
    public const TENOR_CLEF = '𝄥';
    public const TREBLE_CLEF = '𝄞';
    public const WHOLE_NOTE = 'o';
    public const WHOLE_REST = '𝄻';
}