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
use Clover\Enumeration\MusicChordType;

/**
 * Class Tension
 * 
 * Provides methods to retrieve available tensions for different chord types.
 */
class Tension extends BaseClass
{
    /** @var int $root The root tone */
    private int $root;

    /** @var string $type The chord type */
    private string $type;

    /**
     * Constructor
     *
     * @param int $root The root tone
     * @param string $type The chord type
     */
    public function __construct(int $root = 0, string $type = MusicChordType::MAJOR_7TH)
    {
        $this->root = $root;
        $this->type = $type;
    }

    /**
     * Get available tensions based on chord type
     * 
     * @return array<int> The list of available tensions
     */
    public function getAvailableTensions(): array
    {
        $root = $this->root;
        $type = $this->type;

        return match ($type) {
            MusicChordType::MAJOR_7TH => [$root + 12 + 2, $root + 12 + 6, $root + 9], // 9, #11, 6
            MusicChordType::MINOR_7TH => [$root + 12 + 2, $root + 12 + 5, $root + 9], // 9, 11, 6
            MusicChordType::DIMINISHED_7TH => [$root + 12 + 1, $root + 12 + 2, $root + 12 + 3, $root + 12 + 6, $root + 12 + 8, $root + 12 + 9], // b9, 9, #9, #11, b13, 13
            MusicChordType::MINOR_7TH_FLAT_5 => [$root + 12 + 1, $root + 12 + 2, $root + 12 + 5], // b9, 9, 11
            default => [],
        };
    }

    /** Get the name of the tension based on its value
     * 
     * @param int $tension The tension value
     * 
     * @return string The name of the tension
     */
    public function getTensionName(int $tension): string
    {
        $interval = ($tension - $this->root) % 12;
        return match ($interval) {
            1 => 'b9',
            2 => '9',
            3 => '#9',
            4 => '3',
            5 => '11',
            6 => '#11',
            7 => '5',
            8 => 'b13',
            9 => '13',
            10 => 'b7',
            11 => '7',
            default => '',
        };
    }
}