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
use Clover\Enumeration\{MusicChordType, MusicScaleType};

/**
 * Class Scale
 * 
 * Represents a musical scale and provides methods to retrieve its chords.
 */
class Scale extends BaseClass
{
    /** @var int $key The key (0 = C, 1 = C#, ..., 11 = B) */
    private int $key = 0;

    /** @var string $type The scale type */
    private string $type = MusicScaleType::IONIAN;

    /**
     * Constructor
     *
     * @param int $key The key (default: 0)
     * @param string $type The scale type (default: IONIAN)
     */
    public function __construct(int $key = 0, string $type = MusicScaleType::IONIAN)
    {
        $this->key = $key;
        $this->type = $type;
    }

    /**
     * Get the list of chords in the scale
     * 
     * @return Chord[] The list of chords
     */
    public function getChordList(): array
    {
        return match ($this->type) {
            MusicScaleType::IONIAN => [
                new Chord(0, MusicChordType::MAJOR_7TH, $this->key), // CM7
                new Chord(2, MusicChordType::MAJOR_7TH, $this->key), // DM7
                new Chord(4, MusicChordType::MINOR_7TH, $this->key), // Em7
                new Chord(5, MusicChordType::MAJOR_7TH, $this->key), // FM7
                new Chord(7, MusicChordType::MAJOR_7TH, $this->key), // GM7
                new Chord(9, MusicChordType::MINOR_7TH, $this->key), // Am7
                new Chord(11, MusicChordType::MINOR_7TH_FLAT_5, $this->key), // Bm7b5
            ],
            MusicScaleType::DORIAN => [
                new Chord(0, MusicChordType::MINOR_7TH, $this->key), // Cm7
                new Chord(2, MusicChordType::MINOR_7TH, $this->key), // Dm7
                new Chord(3, MusicChordType::MAJOR_7TH, $this->key), // EbM7
                new Chord(5, MusicChordType::MAJOR_7TH, $this->key), // FM7
                new Chord(7, MusicChordType::MINOR_7TH, $this->key), // Gm7
                new Chord(9, MusicChordType::MINOR_7TH_FLAT_5, $this->key), // Am7b5
                new Chord(10, MusicChordType::MAJOR_7TH, $this->key), // BbM7
            ],
            MusicScaleType::PHRYGIAN => [
                new Chord(0, MusicChordType::MINOR_7TH, $this->key), // Cm7
                new Chord(1, MusicChordType::MAJOR_7TH, $this->key), // DbM7
                new Chord(3, MusicChordType::MAJOR_7TH, $this->key), // EbM7
                new Chord(5, MusicChordType::MINOR_7TH, $this->key), // Fm7
                new Chord(7, MusicChordType::MINOR_7TH, $this->key), // Gm7
                new Chord(8, MusicChordType::MAJOR_7TH, $this->key), // AbM7
                new Chord(10, MusicChordType::MINOR_7TH_FLAT_5, $this->key), // Bbm7b5
            ],
            MusicScaleType::LYDIAN => [
                new Chord(0, MusicChordType::MAJOR_7TH, $this->key), // CM7
                new Chord(2, MusicChordType::MAJOR_7TH, $this->key), // DM7
                new Chord(4, MusicChordType::MINOR_7TH, $this->key), // Em7
                new Chord(6, MusicChordType::MINOR_7TH_FLAT_5, $this->key), // F#m7b5
                new Chord(7, MusicChordType::MAJOR_7TH, $this->key), // GM7
                new Chord(9, MusicChordType::MINOR_7TH, $this->key), // Am7
                new Chord(11, MusicChordType::MINOR_7TH, $this->key), // Bm7
            ],
            MusicScaleType::MIXOLYDIAN => [
                new Chord(0, MusicChordType::MAJOR_7TH, $this->key), // CM7
                new Chord(2, MusicChordType::MINOR_7TH, $this->key), // Dm7
                new Chord(4, MusicChordType::MINOR_7TH, $this->key), // Em7
                new Chord(5, MusicChordType::MAJOR_7TH, $this->key), // FM7
                new Chord(7, MusicChordType::MINOR_7TH_FLAT_5, $this->key), // Gm7b5
                new Chord(9, MusicChordType::MINOR_7TH, $this->key), // Am7
                new Chord(10, MusicChordType::MAJOR_7TH, $this->key), // BbM7
            ],
            MusicScaleType::AEOLIAN => [
                new Chord(0, MusicChordType::MINOR_7TH, $this->key), // Cm7
                new Chord(2, MusicChordType::MINOR_7TH_FLAT_5, $this->key), // Dm7b5
                new Chord(3, MusicChordType::MAJOR_7TH, $this->key), // EbM7
                new Chord(5, MusicChordType::MINOR_7TH, $this->key), // Fm7
                new Chord(7, MusicChordType::MINOR_7TH, $this->key), // Gm7
                new Chord(8, MusicChordType::MAJOR_7TH, $this->key), // AbM7
                new Chord(10, MusicChordType::MAJOR_7TH, $this->key), // BbM7
            ],
            MusicScaleType::CHROMATIC => [
                new Chord(0, MusicChordType::MAJOR_7TH, $this->key), // CM7
                new Chord(1, MusicChordType::MINOR_7TH, $this->key), // Dbm7
                new Chord(2, MusicChordType::MINOR_7TH, $this->key), // Dm7
                new Chord(3, MusicChordType::MAJOR_7TH, $this->key), // EbM7
                new Chord(4, MusicChordType::MINOR_7TH, $this->key), // Em7
                new Chord(5, MusicChordType::MAJOR_7TH, $this->key), // FM7
                new Chord(6, MusicChordType::MINOR_7TH_FLAT_5, $this->key), // Gbm7b5
                new Chord(7, MusicChordType::MAJOR_7TH, $this->key), // GM7
                new Chord(8, MusicChordType::MINOR_7TH, $this->key), // Abm7
                new Chord(9, MusicChordType::MINOR_7TH, $this->key), // Am7
                new Chord(10, MusicChordType::MAJOR_7TH, $this->key), // BbM7
                new Chord(11, MusicChordType::MINOR_7TH_FLAT_5, $this->key), // Bm7b5
            ],
            MusicScaleType::MELODIC_MINOR => [
                new Chord(0, MusicChordType::MINOR_7TH, $this->key), // Cm7
                new Chord(2, MusicChordType::MINOR_7TH, $this->key), // Dm7
                new Chord(3, MusicChordType::MAJOR_7TH, $this->key), // EbM7
                new Chord(5, MusicChordType::MAJOR_7TH, $this->key), // FM7
                new Chord(7, MusicChordType::MINOR_7TH_FLAT_5, $this->key), // Gm7b5
                new Chord(9, MusicChordType::MINOR_7TH, $this->key), // Am7
                new Chord(11, MusicChordType::MAJOR_7TH, $this->key), // BM7
            ],
            MusicScaleType::PENTATONIC => [
                new Chord(0, MusicChordType::MAJOR_7TH, $this->key), // CM7
                new Chord(2, MusicChordType::MINOR_7TH, $this->key), // Dm7
                new Chord(4, MusicChordType::MINOR_7TH, $this->key), // Em7
                new Chord(7, MusicChordType::MAJOR_7TH, $this->key), // GM7
                new Chord(9, MusicChordType::MINOR_7TH, $this->key), // Am7
            ],
            MusicScaleType::PENTATONIC_MINOR => [
                new Chord(0, MusicChordType::MINOR_7TH, $this->key), // Cm7
                new Chord(3, MusicChordType::MAJOR_7TH, $this->key), // EbM7
                new Chord(5, MusicChordType::MINOR_7TH, $this->key), // Fm7
                new Chord(7, MusicChordType::MINOR_7TH, $this->key), // Gm7
                new Chord(10, MusicChordType::MAJOR_7TH, $this->key), // BbM7
            ],
            MusicScaleType::HARMONIC_MINOR => [
                new Chord(0, MusicChordType::MINOR_7TH, $this->key), // Cm7
                new Chord(2, MusicChordType::MINOR_7TH_FLAT_5, $this->key), // Dm7b5
                new Chord(3, MusicChordType::MAJOR_7TH, $this->key), // EbM7
                new Chord(5, MusicChordType::MINOR_7TH, $this->key), // Fm7
                new Chord(7, MusicChordType::MAJOR_7TH, $this->key), // GM7
                new Chord(8, MusicChordType::MAJOR_7TH, $this->key), // AbM7
                new Chord(11, MusicChordType::DIMINISHED_7TH, $this->key), // Bdim7
            ],
            MusicScaleType::HARMONIC_MAJOR => [
                new Chord(0, MusicChordType::MAJOR_7TH, $this->key), // CM7
                new Chord(2, MusicChordType::MINOR_7TH, $this->key), // Dm7
                new Chord(4, MusicChordType::MINOR_7TH_FLAT_5, $this->key), // Em7b5
                new Chord(5, MusicChordType::MAJOR_7TH, $this->key), // FM7
                new Chord(7, MusicChordType::MAJOR_7TH, $this->key), // GM7
                new Chord(8, MusicChordType::MINOR_7TH, $this->key), // Abm7
                new Chord(11, MusicChordType::DIMINISHED_7TH, $this->key), // Bdim7
            ],
            MusicScaleType::AUGMENTED => [
                new Chord(0, MusicChordType::MAJOR_7TH, $this->key), // CM7
                new Chord(3, MusicChordType::MINOR_7TH, $this->key), // Ebm7
                new Chord(4, MusicChordType::MINOR_7TH, $this->key), // Em7
                new Chord(7, MusicChordType::MAJOR_7TH, $this->key), // GM7
                new Chord(8, MusicChordType::MINOR_7TH, $this->key), // Abm7
                new Chord(11, MusicChordType::DIMINISHED_7TH, $this->key), // Bdim7
            ],
            default => throw new \Exception("Invalid scale type")
        };
    }

    /**
     * Get the scale notes based on the key and type
     * 
     * @return array<int> The scale notes
     */
    public function getScale(): array
    {
        $key = $this->key;

        return match ($this->type) {
                // C, D, E, F, G, A, B
            MusicScaleType::IONIAN => [$key, $key + 2, $key + 4, $key + 5, $key + 7, $key + 9, $key + 11],
                // C, D, E, G, A
            MusicScaleType::PENTATONIC => [$key, $key + 2, $key + 4, $key + 7, $key + 9],
                // C, D#, F, G, A#
            MusicScaleType::PENTATONIC_MINOR => [$key, $key + 3, $key + 5, $key + 7, $key + 10],
                // C, D, D#, F, G, G#, B
            MusicScaleType::HARMONIC_MINOR => [$key, $key + 2, $key + 3, $key + 5, $key + 7, $key + 8, $key + 11],
                // C, D, D#, F, G, A, B
            MusicScaleType::MELODIC_MINOR => [$key, $key + 2, $key + 3, $key + 5, $key + 7, $key + 9, $key + 11],
                // C, D, E, F, G, G#, B
            MusicScaleType::HARMONIC_MAJOR => [$key, $key + 2, $key + 4, $key + 5, $key + 7, $key + 8, $key + 11],
                // C, D#, E, G, G#, B
            MusicScaleType::AUGMENTED => [$key, $key + 3, $key + 4, $key + 7, $key + 8, $key + 11],
                // C, C#, D, D#, E, F, F#, G, G#, A, A#, B
            MusicScaleType::CHROMATIC => [$key, $key + 1, $key + 2, $key + 3, $key + 4, $key + 5, $key + 6, $key + 7, $key + 8, $key + 9, $key + 10, $key + 11],
                // C, D, D#, F, G, A, A#
            MusicScaleType::DORIAN => [$key, $key + 2, $key + 3, $key + 5, $key + 7, $key + 9, $key + 10],
                // C, C#, D#, F, G, G#, A#
            MusicScaleType::PHRYGIAN => [$key, $key + 1, $key + 3, $key + 5, $key + 7, $key + 8, $key + 10],
                // C, D, E, F#, G, A, B
            MusicScaleType::LYDIAN => [$key, $key + 2, $key + 4, $key + 6, $key + 7, $key + 9, $key + 11],
                // C, D, E, F, G, A, A#
            MusicScaleType::MIXOLYDIAN => [$key, $key + 2, $key + 4, $key + 5, $key + 7, $key + 9, $key + 10],
                // C, C#, D#, F, F#, G#, A#
            MusicScaleType::LOCRIAN => [$key, $key + 1, $key + 3, $key + 5, $key + 6, $key + 8, $key + 10],
                // C, D, D#, F, G, G#, A#
            MusicScaleType::AEOLIAN => [$key, $key + 2, $key + 3, $key + 5, $key + 7, $key + 8, $key + 10],
            default => throw new \Exception("Invalid scale type")
        };
    }

    /**
     * Get the frequency of a note based on its interval from A4 (440 Hz)
     * 
     * @param int $interval The interval in semitones from A4
     * @return float The frequency of the note
     */
    public function getFrequency(int $interval): float
    {
        $intervals = [
            1 / 1, // 1
            16 / 15, // 1.0666666666667
            9 / 8, // 1.125
            6 / 5, // 1.2
            5 / 4, // 1.25
            4 / 3, // 1.3333333333333
            45 / 32, // 1.40625
            3 / 2, // 1.5
            8 / 5, // 1.6
            5 / 3, // 1.6666666666667
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
}