<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Chord;

use Clover\Classes\{ArrayObject, BaseClass};
use Clover\Enumeration\MusicChordType;
use InvalidArgumentException;
use Throwable;
use function is_string;
use function sprintf;

/**
 * Class Chord
 * 
 * Represents a musical chord and provides methods to retrieve its tones.
 */
class Chord extends BaseClass
{
    /** @var string[] Yamaha notation tones */
    private array $yamahaTones = ['C', 'C#', 'D', 'D#', 'E', 'F', 'F#', 'G', 'G#', 'A', 'A#', 'B'];
    /** @var string[] Sharp notation tones */
    private array $sharpTones = ['C', 'C#', 'D', 'D#', 'E', 'F', 'F#', 'G', 'G#', 'A', 'A#', 'B'];
    /** @var string[] Flat notation tones */
    private array $flatTones = ['C', 'Db', 'D', 'Eb', 'E', 'F', 'Gb', 'G', 'Ab', 'A', 'Bb', 'B'];
    /** @var string[] German notation tones */
    private array $germanTones = ['C', 'Cis', 'D', 'Dis', 'E', 'F', 'Fis', 'G', 'Gis', 'A', 'Ais', 'H'];
    /** @var string[] Solfege notation tones */
    private array $solfegeTones = ['Do', 'Do#', 'Re', 'Re#', 'Mi', 'Fa', 'Fa#', 'Sol', 'Sol#', 'La', 'La#', 'Si'];
    /** @var string[] Roman numeral tones */
    private array $romanNumerals = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII'];

    /**
     * Root tone as MIDI note number
     * @var int
     */
    private int $root;

    /**
     * Octave per root tone
     * @var int
     */
    private int $octave;

    /**
     * Key (0 = C, 1 = C#, ..., 11 = B)
     * @var int
     */
    private int $key;

    /**
     * Chord type
     * @var string
     */
    private string $type;

    /**
     * Constructor
     *
     * @param int|string $root The root tone (index or name)
     * @param string $type The chord type
     * @param int $key The key (default: 0)
     * @param int $octave The octave (default: 0)
     */
    public function __construct(int|string $root, string $type, int $key = 0, int $octave = 0)
    {
        if (is_string($root)) {
            $root = ArrayObject::getIndexByValue($root, $this->yamahaTones);
        }

        $this->root = $root;
        $this->octave = $octave;
        $this->type = $type;
        $this->key = $key;
    }

    /**
     * Get the chord tones in Yamaha notation
     *
     * @return array<string> The chord tones in Yamaha notation
     */
    public function getYamahaTones(): array
    {
        return $this->convertToNotation($this->yamahaTones);
    }

    /**
     * Get the chord tones in Sharp notation
     *
     * @return array<string> The chord tones in Sharp notation
     */
    public function getSharpTones(): array
    {
        return $this->convertToNotation($this->sharpTones);
    }

    /**
     * Get the chord tones in Flat notation
     *
     * @return array<string> The chord tones in Flat notation
     */
    public function getFlatTones(): array
    {
        return $this->convertToNotation($this->flatTones);
    }

    /**
     * Get the chord tones in German notation
     *
     * @return array<string> The chord tones in German notation
     */
    public function getGermanTones(): array
    {
        return $this->convertToNotation($this->germanTones);
    }

    /**
     * Get the chord tones in Solfege notation
     *
     * @return array<string> The chord tones in Solfege notation
     */
    public function getSolfegeTones(): array
    {
        return $this->convertToNotation($this->solfegeTones);
    }

    /**
     * Get the chord tones in Scientific notation
     *
     * @return array<string> The chord tones in Scientific notation
     */
    public function getScientificNotation(): array
    {
        $tones = $this->getChordTones();

        return array_map(function ($value): string {
            $octave = floor($value / 12) - 1;
            $note = $this->yamahaTones[$value % 12];
            return sprintf("%s%d", $note, $octave);
        }, $tones);
    }

    /**
     * Get the frequencies of the chord tones
     *
     * @return array<float> The frequencies of the chord tones in Hz
     */
    public function getFrequencies(): array
    {
        $tones = $this->getChordTones();

        return array_map(function ($midiNote): float {
            return 440 * pow(2, ($midiNote - 69) / 12);
        }, $tones);
    }

    /**
     * Convert chord tones to the specified notation
     *
     * @param array $notation The notation array
     * @return array<string> The chord tones in the specified notation
     */
    private function convertToNotation(array $notation): array
    {
        $tones = $this->getChordTones();

        return array_map(function ($value) use ($notation): string {
            return $notation[$value % 12];
        }, $tones);
    }

    /**
     * Get the chord tones as MIDI note numbers
     *
     * @return array<int> The chord tones as MIDI note numbers
     */
    public function getMidiNumbers(): array
    {
        return $this->getChordTones();
    }

    /**
     * Get the chord tones as MIDI note numbers
     *
     * @return array<int> The chord tones as MIDI note numbers
     */
    public function getChordTones(): array
    {
        switch ($this->type) {
            default:
            case MusicChordType::MAJOR:
                return [
                    $this->root,
                    $this->root + 4, // E (major 3)
                    $this->root + 7 // G (perfect 5)
                ];
            case MusicChordType::MAJOR_7TH:
                return [
                    $this->root,
                    $this->root + 4, // E (major 5)
                    $this->root + 7, // G (perfect 5)
                    $this->root + 11 // B (minor 7)
                ];
            case MusicChordType::MAJOR_7TH_FLAT_5:
                return [
                    $this->root,
                    $this->root + 4, // E (major 3)
                    $this->root + 6, // Gb (diminished 5)
                    $this->root + 11 // B (major 7)
                ];
            case MusicChordType::MINOR:
                return [
                    $this->root,
                    $this->root + 3, // Eb (minor 3)
                    $this->root + 7 // G (perfect 5)
                ];
            case MusicChordType::MINOR_7TH:
                return [
                    $this->root,
                    $this->root + 3, // Eb (minor 3)
                    $this->root + 7, // G (perfect 5)
                    $this->root + 10 // Bb (minor 7)
                ];
            case MusicChordType::MINOR_7TH_FLAT_5:
                return [
                    $this->root,
                    $this->root + 3, // Eb (minor 3)
                    $this->root + 6, // Gb (diminished 5)
                    $this->root + 10 // Bb (minor 7)
                ];
            case MusicChordType::DIMINISHED:
                return [
                    $this->root,
                    $this->root + 3, // Eb (minor 3)
                    $this->root + 6 // Gb (diminished 5)
                ];
            case MusicChordType::DOMINANT_7TH:
                return [
                    $this->root,
                    $this->root + 4, // F
                    $this->root + 7, // G
                    $this->root + 10 // Bb
                ];
            case MusicChordType::DIMINISHED_7TH:
                return [
                    $this->root,
                    $this->root + 3, // Eb (minor 3)
                    $this->root + 7, // Gb (diminished 5)
                    $this->root + 10 // Bb (minor 7)
                ];
            case MusicChordType::DOMINANT_7TH_FLAT_5:
                return [
                    $this->root,
                    $this->root + 4, // F
                    $this->root + 6, // Gb
                    $this->root + 10 // Bb
                ];
            case MusicChordType::DOMINANT_7TH_SHARP_5:
                return [
                    $this->root,
                    $this->root + 4, // F
                    $this->root + 8, // G#
                    $this->root + 10 // Bb
                ];
            case MusicChordType::DOMINANT_7TH_FLAT_9:
                return [
                    $this->root,
                    $this->root + 4, // F
                    $this->root + 7, // G
                    $this->root + 10, // Bb
                    $this->root + 12 + 1 // Db
                ];
            case MusicChordType::DOMINANT_7TH_SHARP_9:
                return [
                    $this->root,
                    $this->root + 4, // E
                    $this->root + 7, // G
                    $this->root + 10, // Bb
                    $this->root + 12 + 3 // Eb
                ];
            case MusicChordType::DOMINANT_9TH:
                return [
                    $this->root,
                    $this->root + 4, // E
                    $this->root + 7, // G
                    $this->root + 10, // Bb
                    $this->root + 12 + 2 // D
                ];
            case MusicChordType::DOMINANT_11TH:
                return [
                    $this->root,
                    $this->root + 4, // E
                    $this->root + 7, // G
                    $this->root + 10, // Bb
                    $this->root + 12 + 2, // D
                    $this->root + 12 + 5 // F
                ];
            case MusicChordType::DOMINANT_13TH:
                return [
                    $this->root,
                    $this->root + 4, // E
                    $this->root + 7, // G
                    $this->root + 10, // Bb
                    $this->root + 12 + 2, // D
                    $this->root + 12 + 5,  // F
                    $this->root + 12 + 9 // A
                ];
            case MusicChordType::SUSPENDED_4:
                return [
                    $this->root,
                    $this->root + 5, // F (perfect 4)
                    $this->root + 7 // G (perfect 5)
                ];
            case MusicChordType::AUGMENTED:
                return [
                    $this->root,
                    $this->root + 4, // E (major 3)
                    $this->root + 8 // G# (augmented 5)
                ];
            case MusicChordType::FRENCH_6TH:
            case MusicChordType::AUGMENTED_6TH:
                return [
                    $this->root,
                    $this->root + 4, // E / E (major 3)
                    $this->root + 6, // F# (augmented 4)
                    $this->root + 10, // A# (augmented 6)
                ];
            case MusicChordType::ITALIAN_6TH:
                return [
                    $this->root,
                    $this->root + 4, // E (major 3)
                    $this->root + 10, // A# (augmented 6)
                ];
            case MusicChordType::GERMAN_6TH:
                return [
                    $this->root,
                    $this->root + 4, // E (major 3)
                    $this->root + 7, // G (overlap augmented 5)
                    $this->root + 10, // A# (augmented 6)
                ];
            case MusicChordType::NEAPOLITAN:
                return [
                    $this->root - 1, // Cb (union)
                    $this->root + 5, // F / E# (augmented 3)
                    $this->root + 8, // G# (augmented 5)
                ];
            case MusicChordType::TRISTAN:
                return [
                    $this->root,
                    $this->root + 6, // F# (augmented 4)
                    $this->root + 10, // A# (augmented 6)
                    $this->root + 12 + 3, // D# / +12 (augmented 2)
                ];
            case MusicChordType::SYNTHETIC:
                return [
                    $this->root,
                    $this->root + 5, // F
                    $this->root + 11, // B
                ];
            case MusicChordType::MYSTIC:
                return [
                    $this->root,
                    $this->root + 6, // F#
                    $this->root + 10, // Bb
                    $this->root + 12 + 4, // E
                    $this->root + 12 + 9, // A
                    $this->root + 12 + 12 + 2, // D
                ];
            case MusicChordType::POWER:
                return [
                    $this->root,
                    $this->root + 7 // G
                ];
            case MusicChordType::FIFTH:
                return [
                    $this->root,
                    $this->root + 7 // G
                ];
            case MusicChordType::SIX_NINE:
                return [
                    $this->root,
                    $this->root + 4, // E
                    $this->root + 7, // G
                    $this->root + 9, // A
                    $this->root + 12 + 2 // D
                ];
            case MusicChordType::MINOR_MAJOR_7TH:
                return [
                    $this->root,
                    $this->root + 3, // Eb
                    $this->root + 7, // G
                    $this->root + 11 // B
                ];
            case MusicChordType::MINOR_6TH:
                return [
                    $this->root,
                    $this->root + 3, // Eb
                    $this->root + 7, // G
                    $this->root + 9 // A
                ];
            case MusicChordType::MINOR_9TH:
                return [
                    $this->root,
                    $this->root + 3, // Eb
                    $this->root + 7, // G
                    $this->root + 10, // Bb
                    $this->root + 12 + 2 // D
                ];
            case MusicChordType::MINOR_11TH:
                return [
                    $this->root,
                    $this->root + 3, // Eb
                    $this->root + 7, // G
                    $this->root + 10, // Bb
                    $this->root + 12 + 2, // D
                    $this->root + 12 + 5 // F
                ];
            case MusicChordType::MINOR_13TH:
                return [
                    $this->root,
                    $this->root + 3, // Eb
                    $this->root + 7, // G
                    $this->root + 10, // Bb
                    $this->root + 12 + 2, // D
                    $this->root + 12 + 5, // F
                    $this->root + 12 + 9 // A
                ];
            case MusicChordType::MAJOR_6TH:
                return [
                    $this->root,
                    $this->root + 4, // E
                    $this->root + 7, // G
                    $this->root + 9 // A
                ];
            case MusicChordType::MAJOR_9TH:
                return [
                    $this->root,
                    $this->root + 4, // E
                    $this->root + 7, // G
                    $this->root + 11, // B
                    $this->root + 12 + 2 // D
                ];
            case MusicChordType::MAJOR_11TH:
                return [
                    $this->root,
                    $this->root + 4, // E
                    $this->root + 7, // G
                    $this->root + 11, // B
                    $this->root + 12 + 2, // D
                    $this->root + 12 + 5  // F
                ];
            case MusicChordType::MAJOR_13TH:
                return [
                    $this->root,
                    $this->root + 4, // E
                    $this->root + 7, // G
                    $this->root + 11, // B
                    $this->root + 12 + 2, // D
                    $this->root + 12 + 5,  // F
                    $this->root + 12 + 9 // A
                ];
            case MusicChordType::ADDED_2:
                return [
                    $this->root,
                    $this->root + 2, // D
                    $this->root + 4, // E
                    $this->root + 7 // G
                ];
            case MusicChordType::ADDED_4:
                return [
                    $this->root,
                    $this->root + 4, // E
                    $this->root + 5, // F
                    $this->root + 7 // G
                ];
            case MusicChordType::ADDED_6:
                return [
                    $this->root,
                    $this->root + 4, // E
                    $this->root + 7, // G
                    $this->root + 9 // A
                ];
            case MusicChordType::ADDED_9:
                return [
                    $this->root,
                    $this->root + 4, // E
                    $this->root + 7, // G
                    $this->root + 12 + 2 // D
                ];
        }
    }

    /**
     * Get the chord type
     *
     * @return string The chord type
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Get the root tone as MIDI note number
     *
     * @return int The root tone as MIDI note number
     */
    public function getRoot(): int
    {
        return $this->root;
    }

    /**
     * Get the octave
     *
     * @return int The octave
     */
    public function getOctave(): int
    {
        return $this->octave;
    }

    /**
     * Get the key
     *
     * @return int The key
     */
    public function getKey(): int
    {
        return $this->key;
    }

    /**
     * Transpose the chord by a number of semitones.
     *
     * @param int $semitones Semitones to transpose (positive up, negative down).
     * @return array<int> Transposed MIDI note numbers.
     */
    public function transpose(int $semitones): array
    {
        return array_map(fn(int $note) => $note + $semitones, $this->getChordTones());
    }

    /**
     * Identify chord quality from chord type.
     *
     * @return string One of 'major', 'minor', 'diminished', 'augmented', 'dominant', 'other'.
     */
    public function getQuality(): string
    {
        return match ($this->type) {
            MusicChordType::MAJOR,
            MusicChordType::MAJOR_7TH,
            MusicChordType::MAJOR_6TH,
            MusicChordType::MAJOR_9TH,
            MusicChordType::MAJOR_11TH,
            MusicChordType::MAJOR_13TH => 'major',

            MusicChordType::MINOR,
            MusicChordType::MINOR_7TH,
            MusicChordType::MINOR_6TH,
            MusicChordType::MINOR_9TH,
            MusicChordType::MINOR_11TH,
            MusicChordType::MINOR_13TH,
            MusicChordType::MINOR_MAJOR_7TH => 'minor',

            MusicChordType::DIMINISHED,
            MusicChordType::DIMINISHED_7TH => 'diminished',

            MusicChordType::AUGMENTED,
            MusicChordType::AUGMENTED_6TH,
            MusicChordType::FRENCH_6TH,
            MusicChordType::GERMAN_6TH,
            MusicChordType::ITALIAN_6TH => 'augmented',

            MusicChordType::DOMINANT_7TH,
            MusicChordType::DOMINANT_7TH_FLAT_5,
            MusicChordType::DOMINANT_7TH_SHARP_5,
            MusicChordType::DOMINANT_7TH_FLAT_9,
            MusicChordType::DOMINANT_7TH_SHARP_9,
            MusicChordType::DOMINANT_9TH,
            MusicChordType::DOMINANT_11TH,
            MusicChordType::DOMINANT_13TH => 'dominant',

            default => 'other',
        };
    }

    /**
     * Get the interval (in semitones) between two named notes.
     *
     * @param string $noteA Note name using Yamaha notation.
     * @param string $noteB Note name using Yamaha notation.
     * @return int Semitone distance from A to B. Value between 0 and 11.
     * @throws InvalidArgumentException If note name is invalid.
     */
    public function getIntervalBetween(string $noteA, string $noteB): int
    {
        $indexA = ArrayObject::getIndexByValue($noteA, $this->yamahaTones);
        $indexB = ArrayObject::getIndexByValue($noteB, $this->yamahaTones);

        if ($indexA === null || $indexB === null) {
            throw new InvalidArgumentException(sprintf('Invalid note name: %s or %s', $noteA, $noteB));
        }

        $interval = ($indexB - $indexA + 12) % 12;
        return $interval;
    }

    /**
     * Get all chord degrees (1-7 scale degree numbers) for the chord.
     *
     * @return array<int> Scale degrees (1 to 7). Non-diatonic notes are returned as extended values.
     */
    public function getChordDegrees(): array
    {
        $tones = $this->getChordTones();

        return array_map(function (int $value): int {
            return (($value - $this->root + 12) % 12) + 1;
        }, $tones);
    }

    /**
     * Generate a human-readable chord name.
     *
     * @return string Chord name with root and type.
     */
    public function getChordName(): string
    {
        $rootName = $this->yamahaTones[$this->root % 12];
        return sprintf('%s %s', $rootName, $this->type);
    }

    /**
     * Get the chord inversion by index (0=root, 1=1st inversion etc.).
     *
     * @param int $inversion Inversion index (0-based).
     * @return array<int> MIDI note numbers for the inversion.
     */
    public function getInversion(int $inversion): array
    {
        $tones = $this->getChordTones();
        $inversion = max(0, $inversion);

        for ($i = 0; $i < $inversion; $i++) {
            $root = array_shift($tones);
            $tones[] = $root + 12;
        }

        return $tones;
    }

    /**
     * Get the interval pattern for this chord type in semitones from root.
     *
     * @return array<int>
     */
    public function getChordIntervalPattern(): array
    {
        $tones = $this->getChordTones();
        $root = $tones[0];
        return array_map(fn(int $t) => $t - $root, $tones);
    }

    /**
     * Get the circle of fifths, optionally reverse (circle of fourths).
     *
     * @param bool $reverse If true, returns circle of fourths.
     * @return array<string>
     */
    public static function getCircleOfFifths(bool $reverse = false): array
    {
        $circle = ['C', 'G', 'D', 'A', 'E', 'B', 'F#', 'C#', 'G#', 'D#', 'A#', 'F'];
        return $reverse ? array_reverse($circle) : $circle;
    }

    /**
     * Parse a chord string into a Chord object if supported. e.g., "Cmaj7".
     *
     * @param string $chordString Input chord string.
     * @return self|null Chord object or null if cannot parse.
     */
    public static function fromChordString(string $chordString): ?self
    {
        $matches = [];
        if (!preg_match('/^([A-G](?:#|b)?)(.*)$/i', $chordString, $matches)) {
            return null;
        }

        $rootName = strtoupper($matches[1]);
        $type = strtoupper(trim($matches[2]));
        if ($type === '') {
            $type = MusicChordType::MAJOR;
        }

        try {
            return new self($rootName, $type);
        } catch (Throwable $e) {
            return null;
        }
    }

}
