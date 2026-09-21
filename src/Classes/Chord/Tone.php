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

/**
 * Class Tone
 * 
 * Represents musical tones and provides methods to retrieve their notations.
 */
class Tone extends BaseClass
{
    /** @var array $tones The list of tones */
    private array $tones = [];

    /** @var string[] $yamahaTones */
    private array $yamahaTones = ['C', 'C#', 'D', 'D#', 'E', 'F', 'F#', 'G', 'G#', 'A', 'A#', 'B'];

    /** @var string[] $romeTones */
    private array $romeTones = ['I', '#I', 'II', '#II', 'III', 'IV', '#IV', 'V', '#V', 'VI', 'VI#', 'VII'];

    /**
     * Constructor
     *
     * @param array $tones The list of tones
     */
    public function __construct(array $tones = [])
    {
        $this->tones = $tones;
    }

    /**
     * Get tones in Rome notation
     *
     * @return array<string> The tones in Rome notation
     */
    public function getRomeTones(): array
    {
        return array_map(function ($value): mixed {
            return $this->romeTones[$value % 12];
        }, $this->tones);
    }

    /**
     * Get tones in Yamaha notation
     *
     * @return array<string> The tones in Yamaha notation
     */
    public function getYamahaTones(): array
    {
        return array_map(function ($value): mixed {
            return $this->yamahaTones[$value % 12];
        }, $this->tones);
    }

    /**
     * Get tones in MIDI notation
     *
     * @return array<int> The tones in MIDI notation
     */
    public function getMidiTones(): array
    {
        return $this->tones;
    }

    /**
     * Add a tone to the list
     *
     * @param int $tone The tone to add
     */
    public function addTone(int $tone): void
    {
        $this->tones[] = $tone;
    }
}
