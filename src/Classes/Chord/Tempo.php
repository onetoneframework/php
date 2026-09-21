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
use InvalidArgumentException;

/**
 * Class Tempo
 * 
 * Represents the tempo of a musical piece and provides methods to analyze it.
 */
class Tempo extends BaseClass
{
    /** @var int $bpm The beats per minute (BPM) of the tempo */
    private $bpm = 120;

    /**
     * Constructor
     *
     * @param int $bpm The beats per minute (BPM) of the tempo
     */
    public function __construct(int $bpm = 120)
    {
        $this->bpm = $bpm;
    }

    /**
     * Get the BPM of the tempo
     *
     * @return int The BPM of the tempo
     */
    public function getBPM(): int
    {
        return $this->bpm;
    }

    /**
     * Set the BPM of the tempo
     *
     * @param int $bpm The BPM to set
     */
    public function setBPM(int $bpm): void
    {
        $this->bpm = $bpm;
    }

    /**
     * Get the duration of a beat in seconds
     *
     * @return float The duration of a beat in seconds
     */
    public function getBeatDuration(): float
    {
        return 60 / $this->bpm;
    }

    /**
     * Get the duration of a bar in seconds
     *
     * @param int $beatsPerBar The number of beats per bar
     * @return float The duration of a bar in seconds
     */
    public function getBarDuration(int $beatsPerBar): float
    {
        return $this->getBeatDuration() * $beatsPerBar;
    }

    /**
     * Get the tempo category based on the BPM
     *
     * @return string The tempo category
     */
    public function getTempoCategory(): string
    {
        if ($this->bpm < 60) {
            return 'Largo';
        } elseif ($this->bpm < 76) {
            return 'Adagio';
        } elseif ($this->bpm < 108) {
            return 'Andante';
        } elseif ($this->bpm < 120) {
            return 'Moderato';
        } elseif ($this->bpm < 168) {
            return 'Allegro';
        } elseif ($this->bpm < 200) {
            return 'Presto';
        } else {
            return 'Prestissimo';
        }
    }

    /**
     * Get the tempo in terms of note values (e.g., quarter notes, eighth notes)
     *
     * @param string $noteValue The note value (e.g., 'quarter', 'eighth')
     * @return float The tempo in terms of the specified note value
     */
    public function getTempoInNoteValue(string $noteValue): float
    {
        $noteValueMap = [
            'whole' => 1,
            'half' => 0.5,
            'quarter' => 0.25,
            'eighth' => 0.125,
            'sixteenth' => 0.0625,
        ];

        if (!isset($noteValueMap[$noteValue])) {
            throw new InvalidArgumentException("Invalid note value: $noteValue");
        }

        return $this->bpm * $noteValueMap[$noteValue];
    }
}
