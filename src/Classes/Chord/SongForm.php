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
use Exception;
use function count;
use function in_array;

/**
 * Class SongForm
 * 
 * Provides methods to analyze chord progressions and identify specific chord types.
 */
class SongForm extends BaseClass
{
    private $chordProgessions = [];

    /**
     * Get next available secondary dominant chord
     * 
     * @param int $diatonicRoot The diatonic root of the chord progression
     * 
     * @return Chord The next available secondary dominant chord based on the diatonic root
     */
    public function getNextSecondaryDominantChord(int $diatonicRoot): Chord
    {
        switch ($diatonicRoot) {
            case 4: // IIIm - A7
                return new Chord('A', '7', 0);
            case 5: // IV - B7
                return new Chord('B', '7', 0);
            case 7: // V - I7
                return new Chord('C', '7', 0);
            case 9: // VIm - II7
                return new Chord('D', '7', 0);
            case 11: // VIIm7b5 - III7
                return new Chord('E', '7', 0);
            default:
                throw new Exception('Next secondary dominant chord is not available');
        }
    }

    /**
     * Get upper and lower pitches from a list of pitches
     * 
     * @param int[] $pitches List of pitches in a chord, represented as MIDI note numbers
     * 
     * @return array [upper, lower]
     */
    public function getUpperLower(int $pitches): array
    {
        sort($pitches);
        return [max($pitches), min($pitches)];
    }

    /**
     * Get parallel and hidden perfect intervals in a chord progression
     * 
     * @param array $chords List of chords in the progression, where each chord is represented as an array of pitches
     * 
     * @return array List of parallel and hidden perfect intervals found in the progression
     */
    public function getParallelOrHidden(array $chords): array
    {
        $hidden = [];
        $parallel = [];

        for ($i = 0; $i < count($chords) - 1; $i++) {
            list($upper1, $lower1) = $this->getUpperLower($chords[$i]);
            list($upper2, $lower2) = $this->getUpperLower($chords[$i + 1]);

            $int1 = $this->interval($upper1, $lower1);
            $int2 = $this->interval($upper2, $lower2);

            $dirUpper = $this->direction($upper1, $upper2);
            $dirLower = $this->direction($lower1, $lower2);

            $perfects = [0, 7];
            if ($int1 == $int2 && in_array($int1, $perfects) && $dirUpper == $dirLower && $dirUpper != 0) {
                $parallel[] = ["from" => $i, "to" => $i + 1, "interval" => $int1];
            }

            if (!in_array($int1, $perfects) && in_array($int2, $perfects) && $dirUpper == $dirLower && $dirUpper != 0) {
                if (abs($upper2 - $upper1) > 2) {
                    $hidden[] = ["from" => $i, "to" => $i + 1, "interval" => $int2];
                }
            }
        }

        return [
            'hidden' => $hidden,
            'parallel' => $parallel,
        ];
    }

    /**
     * Calculate interval between two pitches
     * 
     * @param int $p1 First pitch
     * @param int $p2 Second pitch
     * 
     * @return int Interval value
     */
    public function interval(int $p1, int $p2): int
    {
        return ($p1 - $p2 + 12) % 12;
    }

    /**
     * Determine the direction between two pitches
     * 
     * @param int $prev Previous pitch
     * @param int $next Next pitch
     * 
     * @return int 1 for up, -1 for down, 0 for same
     */
    public function direction(int $prev, int $next): int
    {
        if ($next > $prev) {
            return 1;
        }

        if ($next < $prev) {
            return -1;
        }

        return 0;
    }

    /**
     * Get next available modal interchange chord
     * 
     * @param mixed $diatonicRoot The diatonic root of the chord progression
     * 
     * @return string[] The list of available modal interchange chords based on the diatonic root
     */
    public function getNextModalInterchangeChord(int $diatonicRoot): array
    {
        switch ($diatonicRoot) {
            case 0:
                return ['i', 'ii°', 'III', 'iv', 'v', 'VI', 'VII'];
            case 2:
                return ['ii°', 'iv', 'VI'];
            case 4:
                return ['i', 'VII'];
            case 5:
                return ['iv', 'ii°', 'VI'];
            case 7:
                return ['v', 'VII'];
            case 9:
                return ['i', 'iv', 'VII', 'ii°'];
            case 11:
                return ['i', 'III', 'VI', 'iv'];
            default:
                throw new Exception('Next modal interchange chord is not available');
        }
    }

    /**
     * Get next available borrowed chord
     * 
     * @param int $diatonicRoot The diatonic root of the chord progression
     * 
     * @return string[] The list of available borrowed chords based on the diatonic root
     */
    public function getNextBorrowedChord(int $diatonicRoot): array
    {
        switch ($diatonicRoot) {
            case 0:
                return ['i', 'ii°', 'III', 'iv', 'v', 'VI', 'VII'];
            case 2:
                return ['ii°', 'iv', 'VI'];
            case 4:
                return ['i', 'VII'];
            case 5:
                return ['iv', 'ii°', 'VI'];
            case 7:
                return ['v', 'VII'];
            case 9:
                return ['i', 'iv', 'VII', 'ii°'];
            case 11:
                return ['i', 'III', 'VI', 'iv'];
            default:
                throw new Exception('Next borrowed chord is not available');
        }
    }

    /**
     * Get next available tritone substitution chord
     * 
     * @param int $diatonicRoot The diatonic root of the chord progression
     * 
     * @return string[] The list of available tritone substitution chords based on the diatonic root
     */
    public function getNextTritoneSubstitutionChord(int $diatonicRoot): array
    {
        switch ($diatonicRoot) {
            case 0:
                return ['Db7'];
            case 2:
                return ['D#7'];
            case 4:
                return ['Eb7'];
            case 5:
                return ['E7'];
            case 7: 
                return ['F7'];
            case 9:
                return ['F#7'];
            case 11:
                return ['Gb7'];
            default:
                throw new Exception('Next tritone substitution chord is not available');
        }
    }

    /**
     * Get next available modal mixture chord
     * 
     * @param int $diatonicRoot The diatonic root of the chord progression
     * 
     * @return string[] The list of available modal mixture chords based on the diatonic root
     */
    public function getNextModalMixtureChord(int $diatonicRoot): array
    {
        switch ($diatonicRoot) {
            case 0:
                return ['i', 'ii°', 'III', 'iv', 'v', 'VI', 'VII'];
            case 2:
                return ['ii°', 'iv', 'VI'];
            case 4:
                return ['i', 'VII'];
            case 5:
                return ['iv', 'ii°', 'VI'];
            case 7:
                return ['v', 'VII'];
            case 9:
                return ['i', 'iv', 'VII', 'ii°'];
            case 11:
                return ['i', 'III', 'VI', 'iv'];
            default:
                throw new Exception('Next modal mixture chord is not available');
        }
    }

    /**
     * Get next available chromatic mediant chord
     * 
     * @param int $diatonicRoot The diatonic root of the chord progression
     * 
     * @return string[] The list of available chromatic mediant chords based on the diatonic root
     */
    public function getNextChromaticMediantChord(int $diatonicRoot): array
    {
        switch ($diatonicRoot) {
            case 0:
                return ['III', 'VI'];
            case 2:
                return ['i', 'iv'];
            case 4:
                return ['i', 'iv'];
            case 5:
                return ['i', 'iv'];
            case 7:
                return ['i', 'iv'];
            case 9:
                return ['i', 'iv'];
            case 11:
                return ['i', 'iv'];
            default:
                throw new Exception('Next chromatic mediant chord is not available');
        }
    }

    /**
     * Get next available Neapolitan chord
     * 
     * @param int $diatonicRoot The diatonic root of the chord progression
     * 
     * @return string[] The list of available Neapolitan chords based on the diatonic root
     */
    public function getNextNeapolitanChord(int $diatonicRoot): array
    {
        switch ($diatonicRoot) {
            case 0:
                return ['Db'];
            case 2:
                return ['D#'];
            case 4:
                return ['Eb'];
            case 5:
                return ['E'];
            case 7:
                return ['F'];
            case 9:
                return ['F#'];
            case 11:
                return ['Gb'];
            default:
                throw new Exception('Next Neapolitan chord is not available');
        }
    }

    /**
     * Get next available altered chord
     * 
     * @param int $diatonicRoot The diatonic root of the chord progression
     * 
     * @return string[] The list of available altered chords based on the diatonic root
     */
    public function getNextAlteredChord(int $diatonicRoot): array
    {
        switch ($diatonicRoot) {
            case 0:
                return ['C7#9', 'C7b9', 'C7#5', 'C7b5'];
            case 2:
                return ['D7#9', 'D7b9', 'D7#5', 'D7b5'];
            case 4:
                return ['E7#9', 'E7b9', 'E7#5', 'E7b5'];
            case 5:
                return ['F7#9', 'F7b9', 'F7#5', 'F7b5'];
            case 7:
                return ['G7#9', 'G7b9', 'G7#5', 'G7b5'];
            case 9:
                return ['A7#9', 'A7b9', 'A7#5', 'A7b5'];
            case 11:
                return ['B7#9', 'B7b9', 'B7#5', 'B7b5'];
            default:
                throw new Exception('Next altered chord is not available');
        }
    }

    /**
     * Get next available polychord
     * 
     * @param int $diatonicRoot The diatonic root of the chord progression
     * 
     * @return string[] The list of available polychords based on the diatonic root
     */
    public function getNextPolychord(int $diatonicRoot): array
    {
        switch ($diatonicRoot) {
            case 0:
                return ['C/E', 'C/G', 'C/B'];
            case 2:
                return ['D/F#', 'D/A', 'D/C#'];
            case 4:
                return ['E/G#', 'E/B', 'E/D#'];
            case 5:
                return ['F/A', 'F/C', 'F/E'];
            case 7:
                return ['G/B', 'G/D', 'G/F#'];
            case 9:
                return ['A/C#', 'A/E', 'A/G#'];
            case 11:
                return ['B/D#', 'B/F#', 'B/A#'];
            default:
                throw new Exception('Next polychord is not available');
        }
    }

    /**
     * Get next available slash chord
     * 
     * @param int $diatonicRoot The diatonic root of the chord progression
     * 
     * @return string[] The list of available slash chords based on the diatonic root
     */
    public function getNextSlashChord(int $diatonicRoot): array
    {
        switch ($diatonicRoot) {
            case 0:
                return ['C/E', 'C/G', 'C/B'];
            case 2:
                return ['D/F#', 'D/A', 'D/C#'];
            case 4:
                return ['E/G#', 'E/B', 'E/D#'];
            case 5:
                return ['F/A', 'F/C', 'F/E'];
            case 7:
                return ['G/B', 'G/D', 'G/F#'];
            case 9: 
                return ['A/C#', 'A/E', 'A/G#'];
            case 11:
                return ['B/D#', 'B/F#', 'B/A#'];
            default:
                throw new Exception('Next slash chord is not available');
        }
    }

    /**
     * Get next available extended chord
     * 
     * @param int $diatonicRoot The diatonic root of the chord progression
     * 
     * @return string[] The list of available extended chords based on the diatonic root
     */
    public function getNextExtendedChord(int $diatonicRoot): array
    {
        switch ($diatonicRoot) {
            case 0:
                return ['Cmaj9', 'C13', 'Cmaj11'];
            case 2:
                return ['Dmaj9', 'D13', 'Dmaj11'];
            case 4:
                return ['Emaj9', 'E13', 'Emaj11'];
            case 5:
                return ['Fmaj9', 'F13', 'Fmaj11'];
            case 7:
                return ['Gmaj9', 'G13', 'Gmaj11'];
            case 9:
                return ['Amaj9', 'A13', 'Amaj11'];
            case 11:
                return ['Bmaj9', 'B13', 'Bmaj11'];
            default:
                throw new Exception('Next extended chord is not available');
        }
    }

    /**
     * Get next available slash chord
     * 
     * @param int $diatonicRoot The diatonic root of the chord progression
     * 
     * @return string[] The list of available slash chords based on the diatonic root
     */
    public function getNextSuspendedChord(int $diatonicRoot): array
    {
        switch ($diatonicRoot) {
            case 0:
                return ['Csus2', 'Csus4'];
            case 2:
                return ['Dsus2', 'Dsus4'];
            case 4:
                return ['Esus2', 'Esus4'];
            case 5:
                return ['Fsus2', 'Fsus4'];
            case 7:
                return ['Gsus2', 'Gsus4'];
            case 9:
                return ['Asus2', 'Asus4'];
            case 11:
                return ['Bsus2', 'Bsus4'];
            default:
                throw new Exception('Next suspended chord is not available');
        }
    }

    /**
     * Get next available quartal chord
     * 
     * @param int $diatonicRoot The diatonic root of the chord progression
     * 
     * @return string[] The list of available quartal chords based on the diatonic root
     */
    public function getNextQuartalChord(int $diatonicRoot): array
    {
        switch ($diatonicRoot) {
            case 0: 
                return ['Cmaj7sus4', 'C7sus4'];
            case 2:
                return ['Dmaj7sus4', 'D7sus4'];
            case 4:
                return ['Emaj7sus4', 'E7sus4'];
            case 5:
                return ['Fmaj7sus4', 'F7sus4'];
            case 7:
                return ['Gmaj7sus4', 'G7sus4'];
            case 9:
                return ['Amaj7sus4', 'A7sus4'];
            case 11:
                return ['Bmaj7sus4', 'B7sus4'];
            default:
                throw new Exception('Next quartal chord is not available');
        }
    }
}