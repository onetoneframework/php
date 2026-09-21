<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Audio;

use Clover\Classes\BaseClass;

/**
 * Class VowelFormant
 * 
 * This class provides formant frequency presets for different vowel sounds.
 */
class VowelFormant extends BaseClass
{
    /**
     * @var array<string, array<string, int>> Preset formant frequencies for vowels.
     */
    private array $formantPresets = [
        'a' => [
            ['frequency' => 730, 'bandwidth' => 100, 'amplitude' => 1.0],
            ['frequency' => 1090, 'bandwidth' => 90, 'amplitude' => 0.5],
            ['frequency' => 2440, 'bandwidth' => 120, 'amplitude' => 0.25]
        ],
        'e' => [
            ['frequency' => 530, 'bandwidth' => 80, 'amplitude' => 1.0],
            ['frequency' => 1840, 'bandwidth' => 100, 'amplitude' => 0.5],
            ['frequency' => 2480, 'bandwidth' => 120, 'amplitude' => 0.25]
        ],
        'i' => [
            ['frequency' => 270, 'bandwidth' => 60, 'amplitude' => 1.0],
            ['frequency' => 2290, 'bandwidth' => 110, 'amplitude' => 0.5],
            ['frequency' => 3010, 'bandwidth' => 150, 'amplitude' => 0.25]
        ],
        'o' => [
            ['frequency' => 490, 'bandwidth' => 70, 'amplitude' => 1.0],
            ['frequency' => 910, 'bandwidth' => 90, 'amplitude' => 0.5],
            ['frequency' => 2380, 'bandwidth' => 120, 'amplitude' => 0.25]
        ],
        'u' => [
            ['frequency' => 300, 'bandwidth' => 60, 'amplitude' => 1.0],
            ['frequency' => 870, 'bandwidth' => 90, 'amplitude' => 0.5],
            ['frequency' => 2240, 'bandwidth' => 120, 'amplitude' => 0.25]
        ]
    ];

    /**
     * Get formant frequencies for a given vowel.
     *
     * @param string $vowel Vowel character (e.g., 'a', 'e', 'i', 'o', 'u').
     * 
     * @return array<string, int> Formant frequencies, bandwidths, and amplitudes.
     */
    public function getFormants(string $vowel): array
    {
        return $this->formantPresets[strtolower($vowel)] ?? [];
    }

    /**
     * Get all available vowel presets.
     * 
     * @return array<string> List of vowel characters with presets.
     */
    public function getAllVowels(): array
    {
        return array_keys($this->formantPresets);
    }

    /**
     * Add a custom vowel formant preset.
     *
     * @param string $name Name of the custom vowel preset.
     * @param array $formants Array of formants, each with 'frequency', 'bandwidth', and 'amplitude'.
     * 
     * @return void
     */
    public function addCustomFormant(string $name, array $formants): void
    {
        $this->formantPresets[$name] = $formants;
    }
}
