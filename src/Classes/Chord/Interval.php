<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Chord;

use Clover\Classes\BaseClass;
use function in_array;

/**
 * Class Interval
 * 
 * Represents a musical interval and provides methods to analyze it.
 */
class Interval extends BaseClass
{
    /** @var int $top The top measure */
    private int $top = 0;

    /** @var int $bottom The bottom measure */
    private int $bottom = 0;

    /**
     * Constructor
     *
     * @param int $top The top measure
     * @param int $bottom The bottom measure
     */
    public function __construct(int $top = 0, int $bottom = 0)
    {
        $this->top = $top;
        $this->bottom = $bottom;
    }

    /**
     * Get the interval between the two measures
     * 
     * Path : Diminished <=> Perfect <=> Augumented
     * 
     * @return int The interval value
     */
    public function getInterval(): int
    {
        $top = $this->top;
        $bottom = $this->bottom;
        if ($top > $bottom) {
            $interval = $top - $bottom;
        } else {
            $interval = $bottom - $top;
        }

        return $interval;
    }

    /**
     * Get the interval type from two measures
     * 
     * Path : Diminished <=> Perfect <=> Augumented
     * 
     * @return bool
     */
    public function isPerfect(): bool
    {
        $interval = $this->getInterval();

        return in_array($interval, [0, 5, 7, 12]); // 1(C), 4(F), 5(G), 8(C)
    }

    /**
     * Get a frequence value from two measures
     * 
     * https://www.audiolabs-erlangen.de/resources/MIR/FMP/C5/C5S1_Intervals.html
     * 
     * @param float $pitch
     * 
     * @return float|int
     */
    public function getFrequence(float $pitch): float|int
    {
        return 2 ** (($pitch - 69) / 12) * 440;
    }

    /**
     * Get a hz value from two measures
     * 
     * https://www.audiolabs-erlangen.de/resources/MIR/FMP/C5/C5S1_Intervals.html
     * 
     * @return float|int
     */
    public function getHertz(): float|int
    {
        $interval = $this->getInterval();
        $intervals = [
            1 / 1, // 1
            16 / 15, // 1.06666666666667
            9 / 8, // 1.125
            6 / 5, // 1.2
            5 / 4, // 1.25
            4 / 3, // 1.33333333333333
            45 / 32, // 1.40625
            3 / 2, // 1.5
            8 / 5, // 1.6
            5 / 3, // 1.66666666666667
            9 / 5, // 1.8
            15 / 8, // 1.875
            2 / 1 // 2
        ];
        $octave = intdiv($interval, 12);
        $degree = $interval % 12;
        if ($degree < 0) {
            $degree += 12;
            $octave -= 1;
        }

        $base = 440;
        return $base * $intervals[$degree] * pow(2, $octave);
    }

    /**
     * Get a interval type from two measures
     * 
     * Path : Minor <=> Major
     * 
     * @return bool
     */
    public function isMinor(): bool
    {
        $interval = $this->getInterval();

        return in_array($interval, [2, 4, 9, 11]); // 2(D), 3(E), 6(A), 7(B)
    }

    /**
     * Get a interval type from two measures
     * 
     * Path : Diminished <=> Perfect <=> Augumented
     * 
     * @return bool
     */
    public function isDiminished(): bool
    {
        $interval = $this->getInterval();
        return in_array($interval, [1, 3, 6, 8, 10]); // 1(C#), 3(Eb), 6(Ab), 8(G#), 10(Bb)
    }

    /**
     * Get a interval type from two measures
     * 
     * Path : Diminished <=> Perfect <=> Augumented
     * 
     * @return bool
     */
    public function isAugmented(): bool
    {
        $interval = $this->getInterval();
        return in_array($interval, [1, 4, 8]); // 1(C#), 4(F#), 8(G#)
    }

    /**
     * Get a interval type from two measures
     * 
     * Path : Diminished <=> Perfect <=> Augumented
     * 
     * @return string
     */
    public function getIntervalType(): string
    {
        if ($this->isPerfect()) {
            return 'perfect';
        } elseif ($this->isMinor()) {
            return 'minor';
        } elseif ($this->isDiminished()) {
            return 'diminished';
        } elseif ($this->isAugmented()) {
            return 'augmented';
        } else {
            return 'unknown';
        }
    }

    /**
     * Get a interval name from two measures
     * 
     * @return string
     */
    public function getIntervalName(): string
    {
        $interval = $this->getInterval();
        $names = [
            0 => 'unison',
            1 => 'minor second',
            2 => 'major second',
            3 => 'minor third',
            4 => 'major third',
            5 => 'perfect fourth',
            6 => 'tritone',
            7 => 'perfect fifth',
            8 => 'minor sixth',
            9 => 'major sixth',
            10 => 'minor seventh',
            11 => 'major seventh',
            12 => 'octave'
        ];
        return $names[$interval % 12] ?? 'unknown';
    }

    /**
     * Get a interval name from two measures
     * 
     * @return string
     */
    public function getIntervalFullName(): string
    {
        $interval = $this->getInterval();
        $names = [
            0 => 'unison',
            1 => 'minor second',
            2 => 'major second',
            3 => 'minor third',
            4 => 'major third',
            5 => 'perfect fourth',
            6 => 'tritone',
            7 => 'perfect fifth',
            8 => 'minor sixth',
            9 => 'major sixth',
            10 => 'minor seventh',
            11 => 'major seventh',
            12 => 'octave',
            13 => 'minor ninth',
            14 => 'major ninth',
            15 => 'minor tenth',
            16 => 'major tenth',
            17 => 'perfect eleventh',
            18 => 'augmented eleventh',
            19 => 'perfect twelfth',
            20 => 'minor thirteenth',
            21 => 'major thirteenth',
            22 => 'minor fourteenth',
            23 => 'major fourteenth',
            24 => 'double octave'
        ];
        return $names[$interval] ?? 'unknown';
    }

    /**
     * Get a interval name from two measures
     * 
     * @return string
     */
    public function getIntervalShortName(): string
    {
        $interval = $this->getInterval();
        $names = [
            0 => 'P1',
            1 => 'm2',
            2 => 'M2',
            3 => 'm3',
            4 => 'M3',
            5 => 'P4',
            6 => 'A4/d5',
            7 => 'P5',
            8 => 'm6',
            9 => 'M6',
            10 => 'm7',
            11 => 'M7',
            12 => 'P8',
            13 => 'm9',
            14 => 'M9',
            15 => 'm10',
            16 => 'M10',
            17 => 'P11',
            18 => 'A11',
            19 => 'P12',
            20 => 'm13',
            21 => 'M13',
            22 => 'm14',
            23 => 'M14',
            24 => 'P15'
        ];
        return $names[$interval] ?? 'unknown';
    }

    /**
     * Get a interval name from two measures
     * 
     * @return string
     */
    public function getIntervalNumber(): int
    {
        $interval = $this->getInterval();
        return $interval + 1;
    }

    /**
     * Get a interval name from two measures
     * 
     * @return string
     */
    public function getIntervalSemitones(): int
    {
        return $this->getInterval();
    }

    /**
     * Get a interval name from two measures
     * 
     * @return string
     */
    public function getIntervalCents(): float
    {
        $interval = $this->getInterval();
        return $interval * 100;
    }

    /**
     * Get a interval name from two measures
     * 
     * @return string
     */
    public function getIntervalRatio(): float
    {
        $interval = $this->getInterval();
        $ratios = [
            1 / 1, // 1
            16 / 15, // 1.06666666666667
            9 / 8, // 1.125
            6 / 5, // 1.2
            5 / 4, // 1.25
            4 / 3, // 1.33333333333333
            45 / 32, // 1.40625
            3 / 2, // 1.5
            8 / 5, // 1.6
            5 / 3, // 1.66666666666667
            9 / 5, // 1.8
            15 / 8, // 1.875
            2 / 1 // 2
        ];
        return $ratios[$interval % 12] ?? 1;
    }

    /**
     * Get a interval name from two measures
     * 
     * @return string
     */
    public function getIntervalDirection(): string
    {
        if ($this->top > $this->bottom) {
            return 'ascending';
        } elseif ($this->top < $this->bottom) {
            return 'descending';
        } else {
            return 'unison';
        }
    }

    /**
     * Get a interval name from two measures
     * 
     * @return string
     */
    public function getIntervalQuality(): string
    {
        if ($this->isPerfect()) {
            return 'perfect';
        } elseif ($this->isMinor()) {
            return 'minor';
        } elseif ($this->isDiminished()) {
            return 'diminished';
        } elseif ($this->isAugmented()) {
            return 'augmented';
        } else {
            return 'unknown';
        }
    }

    /**
     * Get a interval name from two measures
     * 
     * @return string
     */
    public function getIntervalClass(): string
    {
        $interval = $this->getInterval();
        $classes = [
            0 => 'unison',
            1 => 'minor second',
            2 => 'major second',
            3 => 'minor third',
            4 => 'major third',
            5 => 'perfect fourth',
            6 => 'tritone',
            7 => 'perfect fifth',
            8 => 'minor sixth',
            9 => 'major sixth',
            10 => 'minor seventh',
            11 => 'major seventh',
            12 => 'octave'
        ];
        return $classes[$interval % 12] ?? 'unknown';
    }
}