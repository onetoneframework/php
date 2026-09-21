<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Math;

use InvalidArgumentException;
use function count;

/**
 * Class Vocal
 *
 * Provides methods for calculating various vocal tract parameters based on formant frequencies.
 * Implements multiple VTL estimation methods including Flego (2018), Scordilis, and linear regression approaches.
 */
class Vocal
{
    /** Speed of sound at room temperature (~20°C) in cm/s */
    public const SPEED_OF_SOUND_ROOM_TEMP = 33400.0;

    /** Speed of sound at body temperature (~35°C) in cm/s, appropriate for vocal tract calculations */
    public const SPEED_OF_SOUND_BODY_TEMP = 35000.0;

    /** End correction for glottal end compliance in cm */
    public const END_CORRECTION_GLOTTAL = 0.3;

    /** End correction for lip boundary in cm */
    public const END_CORRECTION_LIP = 0.5;

    /** Total end correction (glottal + lip) in cm */
    public const END_CORRECTION_TOTAL = 0.8;

    /** Standard deviation threshold for phi values to determine neutral vowel (Flego method) */
    public const NEUTRAL_VOWEL_PHI_THRESHOLD = 80.0;

    /**
     * Calculate the effective acoustic length of the vocal tract based on formant frequency and number.
     * Uses quarter-wavelength resonator formula: Fn = (2n-1)c/4L
     * 
     * Note: Exclude back vowels (/u/, /o/) as rounded lips add acoustic length, making this formula inaccurate.
     * Front vowels (/i/, /e/, /æ/) are recommended for accurate F3-based measurements.
     *
     * @param float $formantFrequency The frequency of the formant in Hz.
     * @param int   $formantNumber    The formant number (1 for F1, 2 for F2, etc.).
     * @param float $speedOfSound     The speed of sound in cm/s (default is 35000 cm/s at body temperature).
     *
     * @return float The effective acoustic length of the vocal tract in cm.
     *
     * @throws InvalidArgumentException If formant frequency or speed of sound is non-positive.
     */
    public static function getEffectiveAcousticVocalTractLength(float $formantFrequency, int $formantNumber, float $speedOfSound = self::SPEED_OF_SOUND_BODY_TEMP): float
    {
        if ($formantFrequency <= 0 || $speedOfSound <= 0) {
            throw new InvalidArgumentException("Formant frequency and speed of sound must be positive numbers.");
        }

        return ($speedOfSound / (4 * $formantFrequency)) * (2 * $formantNumber - 1);
    }

    /**
     * Calculate the formant dispersion (average spacing between formants) based on vocal tract length.
     * Formant dispersion = c / 2L, representing the expected spacing in a uniform tube model.
     *
     * @param float $vocalTractLength The length of the vocal tract in cm.
     * @param float $speedOfSound     The speed of sound in cm/s (default is 35000 cm/s).
     *
     * @return float The formant dispersion in Hz.
     *
     * @throws InvalidArgumentException If vocal tract length or speed of sound is non-positive.
     */
    public static function getFormantDispersion(float $vocalTractLength, float $speedOfSound = self::SPEED_OF_SOUND_BODY_TEMP): float
    {
        if ($vocalTractLength <= 0 || $speedOfSound <= 0) {
            throw new InvalidArgumentException("Vocal tract length and speed of sound must be positive numbers.");
        }

        return $speedOfSound / (2 * $vocalTractLength);
    }

    /**
     * Calculate vocal tract length using F3 (third formant) frequency.
     * F3 is purely wavelength-related (not Helmholtz) and thus more reliable for VTL estimation.
     * Formula: L = 5c / 4F3 (derived from Fn = (2n-1)c/4L, where n=3 gives F3 = 5c/4L)
     * 
     * Important: Use only with front vowels (/i/, /e/, /æ/). Back vowels (/u/, /o/) have
     * lip rounding that adds several cm to acoustic length, making results inaccurate.
     *
     * @param float $f3           The frequency of the third formant in Hz.
     * @param float $speedOfSound The speed of sound in cm/s (default is 35000 cm/s).
     *
     * @return float The estimated vocal tract length in cm.
     *
     * @throws InvalidArgumentException If F3 frequency or speed of sound is non-positive.
     */
    public static function getVocalTractLengthFromF3(float $f3, float $speedOfSound = self::SPEED_OF_SOUND_BODY_TEMP): float
    {
        if ($f3 <= 0 || $speedOfSound <= 0) {
            throw new InvalidArgumentException("F3 frequency and speed of sound must be positive numbers.");
        }

        return (5 * $speedOfSound) / (4 * $f3);
    }

    /**
     * Calculate phi values for each formant (Flego method).
     * phi[n] = F[n] / (2n-1) represents an independent estimate of baseline F1 for a uniform tube.
     * If formants are in neutral-tube ratio (1:3:5:7...), all phi values will be approximately equal.
     *
     * @param array $formants Array of formant frequencies [F1, F2, F3, ...] in Hz.
     *
     * @return array<float|int> Array of phi values corresponding to each formant.
     *
     * @throws InvalidArgumentException If any formant frequency is non-positive.
     */
    public static function calculatePhiValues(array $formants): array
    {
        $phiValues = [];
        foreach ($formants as $index => $f) {
            if ($f <= 0) {
                throw new InvalidArgumentException("All formant frequencies must be positive numbers.");
            }
            $n = $index + 1;
            $phiValues[] = $f / (2 * $n - 1);
        }
        return $phiValues;
    }

    /**
     * Determine if a vowel is "near enough neutral" based on phi value consistency (Flego method).
     * A neutral vowel has formants in approximately 1:3:5:7 ratio, making phi values similar.
     * Neutral vowels provide more reliable VTL estimates than constricted vowels.
     *
     * @param array $formants  Array of formant frequencies [F1, F2, F3, ...] in Hz.
     * @param float $threshold Standard deviation threshold for phi values (default 80 Hz).
     *
     * @return bool True if the vowel is considered neutral (low phi variance), false otherwise.
     *
     * @throws InvalidArgumentException If any formant frequency is non-positive.
     */
    public static function isNeutralVowel(array $formants, float $threshold = self::NEUTRAL_VOWEL_PHI_THRESHOLD): bool
    {
        $phiValues = self::calculatePhiValues($formants);
        $stdDev = self::calculateStandardDeviation($phiValues);
        return $stdDev < $threshold;
    }

    /**
     * Estimate vocal tract length using Flego's method (2018).
     * Selects for "relatively neutral vowels" with formants in approximate 1:3:5 ratio.
     * 
     * Algorithm:
     * 1. Calculate phi[n] = F[n]/(2n-1) for each formant
     * 2. If phi values have low standard deviation, the vowel is "near neutral"
     * 3. Use average phi (phiHat) in formula: L = c/(4*phiHat)
     *
     * Reference: Flego, S. (2018). "Estimating Vocal Tract Length by Minimizing Non-Uniformity 
     * of Cross-Sectional Area." Proceedings of Meetings on Acoustics 176ASA.
     *
     * @param array $formants     Array of formant frequencies [F1, F2, F3, ...] in Hz (minimum 2).
     * @param float $speedOfSound The speed of sound in cm/s (default is 35000 cm/s).
     *
     * @return array{
     *  acoustic_length: float, 
     *  anatomical_length: float, 
     *  confidence: string, 
     *  is_neutral_vowel: bool, 
     *  phi_mean: float, 
     *  phi_std_dev: float
     * } Associative array containing:
     *  - 'phi_mean': Average phi value in Hz
     *  - 'phi_std_dev': Standard deviation of phi values
     *  - 'acoustic_length': Estimated acoustic VTL in cm
     *  - 'anatomical_length': Estimated anatomical VTL in cm (with end corrections)
     *  - 'is_neutral_vowel': Boolean indicating if vowel passes neutrality test
     *  - 'confidence': 'high' if neutral vowel, 'low' otherwise
     *
     * @throws InvalidArgumentException If less than 2 formants provided or frequencies non-positive.
     */
    public static function estimateVocalTractLengthFlego(array $formants, float $speedOfSound = self::SPEED_OF_SOUND_BODY_TEMP): array
    {
        if (count($formants) < 2) {
            throw new InvalidArgumentException("At least two formants are required.");
        }

        $phiValues = self::calculatePhiValues($formants);
        $phiMean = array_sum($phiValues) / count($phiValues);
        $phiStdDev = self::calculateStandardDeviation($phiValues);

        $acousticLength = $speedOfSound / (4 * $phiMean);
        $isNeutral = $phiStdDev < self::NEUTRAL_VOWEL_PHI_THRESHOLD;

        return [
            'phi_mean' => round($phiMean, 2),
            'phi_std_dev' => round($phiStdDev, 2),
            'acoustic_length' => round($acousticLength, 2),
            'anatomical_length' => round($acousticLength - self::END_CORRECTION_TOTAL, 2),
            'is_neutral_vowel' => $isNeutral,
            'confidence' => $isNeutral ? 'high' : 'low'
        ];
    }

    /**
     * Estimate vocal tract length using Scordilis's method.
     * Uses the highest formant consistently present across all vowel segments.
     * 
     * Algorithm:
     * 1. Observe the highest formant (Fmax) present in all vowel segments and its index N
     * 2. Compute average frequency spacing: F = Fmax/N
     * 3. Calculate VTL: L = c/(2F)
     *
     * This gives average VTL because different vowels have different lengths
     * (e.g., protruded lips in /u/ add ~2 cm).
     *
     * @param float $maxFormantFrequency The frequency of the highest consistent formant in Hz.
     * @param int   $formantIndex        The formant number (e.g., 5 for F5).
     * @param float $speedOfSound        The speed of sound in cm/s (default is 35000 cm/s).
     *
     * @return array{
     *  average_spacing: float, 
     *  acoustic_length: float, 
     *  anatomical_length: float
     * } Associative array containing:
     *  - 'average_spacing': Average formant spacing in Hz
     *  - 'acoustic_length': Estimated acoustic VTL in cm
     *  - 'anatomical_length': Estimated anatomical VTL in cm (with end corrections)
     *
     * @throws InvalidArgumentException If parameters are invalid.
     */
    public static function estimateVocalTractLengthScordilis(float $maxFormantFrequency, int $formantIndex, float $speedOfSound = self::SPEED_OF_SOUND_BODY_TEMP): array
    {
        if ($maxFormantFrequency <= 0 || $speedOfSound <= 0 || $formantIndex < 1) {
            throw new InvalidArgumentException("Invalid parameters.");
        }

        $averageSpacing = $maxFormantFrequency / $formantIndex;
        $acousticLength = $speedOfSound / (2 * $averageSpacing);

        return [
            'average_spacing' => round($averageSpacing, 2),
            'acoustic_length' => round($acousticLength, 2),
            'anatomical_length' => round($acousticLength - self::END_CORRECTION_TOTAL, 2)
        ];
    }

    /**
     * Apply end corrections to convert acoustic length to anatomical length.
     * The vocal tract has boundary effects at both ends:
     * - Glottal end: Vocal cords compliance adds ~0.3 cm acoustic length
     * - Lip end: Open-end radiation adds ~0.5 cm acoustic length
     *
     * @param float $acousticLength    The acoustic vocal tract length in cm.
     * @param float $glottalCorrection End correction for glottal compliance in cm (default 0.3).
     * @param float $lipCorrection     End correction for lip boundary in cm (default 0.5).
     *
     * @return float The estimated anatomical (real) vocal tract length in cm.
     */
    public static function applyEndCorrection(float $acousticLength, float $glottalCorrection = self::END_CORRECTION_GLOTTAL, float $lipCorrection = self::END_CORRECTION_LIP): float
    {
        return $acousticLength - $glottalCorrection - $lipCorrection;
    }

    /**
     * Determine if the vocal quality is "twang" based on the ratio of the fourth and third formant frequencies.
     * Twang is characterized by a narrowed epilaryngeal tube, bringing F4 and F3 closer together.
     *
     * @param float $f4 The frequency of the fourth formant in Hz.
     * @param float $f3 The frequency of the third formant in Hz.
     *
     * @return bool True if the vocal quality is "twang" (F4-F3 < 1000 Hz), false otherwise.
     *
     * @throws InvalidArgumentException If formant frequencies are non-positive.
     */
    public static function getTwangRatio(float $f4, float $f3): bool
    {
        if ($f4 <= 0 || $f3 <= 0) {
            throw new InvalidArgumentException("Formant frequencies must be positive numbers.");
        }

        return ($f4 - $f3) < 1000.0;
    }

    /**
     * Determine the tongue advancement (front/central/back) based on the second formant frequency.
     * F2 reflects the position of tongue constriction along the vocal tract.
     * Higher F2 indicates front vowels, lower F2 indicates back vowels.
     *
     * @param float $f2     The frequency of the second formant in Hz.
     * @param bool  $isMale True if the speaker is male, false if female (females have higher formants).
     *
     * @return string The tongue advancement: "front", "central", or "back".
     *
     * @throws InvalidArgumentException If formant frequency is non-positive.
     */
    public static function getTongueAdvancement(float $f2, bool $isMale = true): string
    {
        if ($f2 <= 0) {
            throw new InvalidArgumentException("Formant frequency must be a positive number.");
        }

        if ($isMale) {
            if ($f2 > 1500.0) {
                return "front";
            } else if ($f2 >= 1100.0 && $f2 <= 1500.0) {
                return "central";
            } else if ($f2 < 1100.0) {
                return "back";
            }
        } else {
            if ($f2 > 1800.0) {
                return "front";
            } else if ($f2 >= 1300.0 && $f2 <= 1800.0) {
                return "central";
            } else if ($f2 < 1300.0) {
                return "back";
            }
        }

        return "unknown";
    }

    /**
     * Determine the jaw opening status (opened/mid/closed) based on the first formant frequency.
     * F1 is inversely related to tongue height and directly related to jaw opening.
     * Higher F1 indicates more open jaw position (low vowels like /a/).
     *
     * @param float $f1     The frequency of the first formant in Hz.
     * @param bool  $isMale True if the speaker is male, false if female.
     *
     * @return string The jaw status: "opened", "mid", or "closed".
     *
     * @throws InvalidArgumentException If formant frequency is non-positive.
     */
    public static function getJawOpenedStatus(float $f1, bool $isMale = true): string
    {
        if ($f1 <= 0) {
            throw new InvalidArgumentException("Formant frequency must be a positive number.");
        }

        if ($isMale) {
            if ($f1 > 600.0) {
                return "opened";
            } else if ($f1 >= 400.0 && $f1 <= 600.0) {
                return "mid";
            } else if ($f1 < 400.0) {
                return "closed";
            }
        } else {
            if ($f1 > 750.0) {
                return "opened";
            } else if ($f1 >= 450.0 && $f1 <= 750.0) {
                return "mid";
            } else if ($f1 < 450.0) {
                return "closed";
            }
        }

        return "unknown";
    }

    /**
     * Determine if the jaw is opened based on the first formant frequency.
     * Returns a boolean for simple open/closed classification.
     *
     * @param float $f1     The frequency of the first formant in Hz.
     * @param bool  $isMale True if the speaker is male, false if female.
     *
     * @return bool True if the jaw is opened, false otherwise.
     *
     * @throws InvalidArgumentException If formant frequency is non-positive.
     */
    public static function isJawOpened(float $f1, bool $isMale = true): bool
    {
        if ($f1 <= 0) {
            throw new InvalidArgumentException("Formant frequency must be a positive number.");
        }

        if ($isMale) {
            return $f1 > 600.0;
        }

        return $f1 > 750.0;
    }

    /**
     * Determine if the vowel is a front vowel based on F2 frequency.
     * Front vowels (/i/, /e/, /æ/) are suitable for F3-based VTL estimation.
     *
     * @param float $f2     The frequency of the second formant in Hz.
     * @param bool  $isMale True if the speaker is male, false if female.
     *
     * @return bool True if front vowel, false otherwise.
     */
    public static function isFrontVowel(float $f2, bool $isMale = true): bool
    {
        return self::getTongueAdvancement($f2, $isMale) === 'front';
    }

    /**
     * Determine if the vowel is a back vowel based on F2 frequency.
     * Back vowels (/u/, /o/) have lip rounding that affects acoustic length measurements.
     *
     * @param float $f2     The frequency of the second formant in Hz.
     * @param bool  $isMale True if the speaker is male, false if female.
     *
     * @return bool True if back vowel, false otherwise.
     */
    public static function isBackVowel(float $f2, bool $isMale = true): bool
    {
        return self::getTongueAdvancement($f2, $isMale) === 'back';
    }

    /**
     * Calculate the anatomical vocal tract length based on the first four formant frequencies.
     * Uses linear regression to find the slope of formants and derives VTL from it.
     * Also includes Flego method results for confidence assessment.
     *
     * @param float $f1           The frequency of the first formant in Hz.
     * @param float $f2           The frequency of the second formant in Hz.
     * @param float $f3           The frequency of the third formant in Hz.
     * @param float $f4           The frequency of the fourth formant in Hz.
     * @param float $speedOfSound The speed of sound in cm/s (default is 35000 cm/s).
     *
     * @return array{
     *  acoustic_length: float, 
     *  anatomical_length: float, 
     *  confidence: string, 
     *  is_neutral_vowel: bool, 
     *  slope_delta_f: float
     * } Associative array containing:
     *  - 'slope_delta_f': The slope of the formant frequencies (formant dispersion)
     *  - 'acoustic_length': The estimated acoustic vocal tract length in cm
     *  - 'anatomical_length': The estimated anatomical vocal tract length in cm
     *  - 'is_neutral_vowel': Boolean indicating if vowel passes neutrality test
     *  - 'confidence': 'high' if neutral vowel, 'low' otherwise
     *
     * @throws InvalidArgumentException If any formant frequency or speed of sound is non-positive.
     */
    public static function getAnatomicalVocalTractLength(float $f1, float $f2, float $f3, float $f4, float $speedOfSound = self::SPEED_OF_SOUND_BODY_TEMP): array
    {
        $formants = [$f1, $f2, $f3, $f4];
        $x_values = [];
        $y_values = [];

        foreach ($formants as $index => $f) {
            $n = $index + 1;
            $x_values[] = (2 * $n - 1) / 2.0;
            $y_values[] = $f;
        }

        $slope = self::calculateSlope($x_values, $y_values);
        $estimatedVTL = $speedOfSound / (2 * $slope);
        $anatomicalVTL = $estimatedVTL - self::END_CORRECTION_TOTAL;

        $flegoResult = self::estimateVocalTractLengthFlego($formants, $speedOfSound);

        return [
            'slope_delta_f' => round($slope, 2),
            'acoustic_length' => round($estimatedVTL, 2),
            'anatomical_length' => round($anatomicalVTL, 2),
            'is_neutral_vowel' => $flegoResult['is_neutral_vowel'],
            'confidence' => $flegoResult['confidence']
        ];
    }

    /**
     * Calculate the slope of the best-fit line for given x and y coordinates using linear regression.
     * Uses least squares method: slope = (n*Σxy - Σx*Σy) / (n*Σx² - (Σx)²)
     *
     * @param array $x An array of x-coordinates.
     * @param array $y An array of y-coordinates.
     *
     * @return float|int The slope of the best-fit line.
     *
     * @throws InvalidArgumentException If input arrays have different lengths or contain less than two points.
     */
    public static function calculateSlope(array $x, array $y): float|int
    {
        if (count($x) !== count($y) || count($x) < 2) {
            throw new InvalidArgumentException("Input arrays must have the same length and contain at least two points.");
        }

        $n = count($x);
        $sumX = array_sum($x);
        $sumY = array_sum($y);
        $sumXY = 0;
        $sumX2 = 0;

        for ($i = 0; $i < $n; $i++) {
            $sumXY += $x[$i] * $y[$i];
            $sumX2 += $x[$i] * $x[$i];
        }

        $numerator = ($n * $sumXY) - ($sumX * $sumY);
        $denominator = ($n * $sumX2) - ($sumX * $sumX);

        if ($denominator == 0) {
            throw new InvalidArgumentException("Denominator in slope calculation is zero.");
        }

        return $numerator / $denominator;
    }

    /**
     * Calculate the sample standard deviation of an array of values.
     * Uses Bessel's correction (n-1) for unbiased estimation.
     *
     * @param array $values An array of numeric values.
     *
     * @return float The sample standard deviation. Returns 0.0 if less than 2 values.
     */
    public static function calculateStandardDeviation(array $values): float
    {
        $n = count($values);
        if ($n < 2) {
            return 0.0;
        }

        $mean = array_sum($values) / $n;
        $squaredDiffs = 0.0;

        foreach ($values as $value) {
            $squaredDiffs += ($value - $mean) ** 2;
        }

        return sqrt($squaredDiffs / ($n - 1));
    }

    /**
     * Calculate the arithmetic mean of an array of values.
     *
     * @param array $values An array of numeric values.
     *
     * @return float The arithmetic mean.
     *
     * @throws InvalidArgumentException If the array is empty.
     */
    public static function calculateMean(array $values): float
    {
        if (empty($values)) {
            throw new InvalidArgumentException("Array must not be empty.");
        }

        return array_sum($values) / count($values);
    }

    /**
     * Estimate vocal tract length from multiple vowel samples using Flego's method.
     * Filters samples for neutral vowels (with consistent phi values) for more reliable estimation.
     * Falls back to all samples if no neutral vowels are found.
     * 
     * This approach is based on Nearey's (1978) log-mean normalization concept:
     * a "virtual neutral vowel" can be derived from a sample of the speaker's vowel space.
     *
     * @param array $samplesFormants Array of formant arrays, each containing [F1, F2, F3, ...] in Hz.
     * @param float $speedOfSound    The speed of sound in cm/s (default is 35000 cm/s).
     *
     * @return array{
     *  acoustic_length: float, 
     *  anatomical_length: float, 
     *  confidence: string, 
     *  neutral_samples_used: int, 
     *  total_samples: int
     * } Associative array containing:
     *  - 'acoustic_length': Estimated acoustic VTL in cm
     *  - 'anatomical_length': Estimated anatomical VTL in cm (with end corrections)
     *  - 'confidence': 'high' if neutral vowels found, 'low' otherwise
     *  - 'total_samples': Total number of vowel samples provided
     *  - 'neutral_samples_used': Number of neutral vowels used in estimation
     *
     * @throws InvalidArgumentException If samples array is empty.
     */
    public static function estimateVocalTractLengthFromMultipleSamples(array $samplesFormants, float $speedOfSound = self::SPEED_OF_SOUND_BODY_TEMP): array
    {
        if (empty($samplesFormants)) {
            throw new InvalidArgumentException("Samples array must not be empty.");
        }

        $neutralSamples = [];
        $allPhiMeans = [];

        foreach ($samplesFormants as $formants) {
            $result = self::estimateVocalTractLengthFlego($formants, $speedOfSound);
            $allPhiMeans[] = $result['phi_mean'];

            if ($result['is_neutral_vowel']) {
                $neutralSamples[] = $result;
            }
        }

        if (!empty($neutralSamples)) {
            $neutralPhiMeans = array_column($neutralSamples, 'phi_mean');
            $phiMean = self::calculateMean($neutralPhiMeans);
            $confidence = 'high';
            $usedSamples = count($neutralSamples);
        } else {
            $phiMean = self::calculateMean($allPhiMeans);
            $confidence = 'low';
            $usedSamples = count($allPhiMeans);
        }

        $acousticLength = $speedOfSound / (4 * $phiMean);

        return [
            'acoustic_length' => round($acousticLength, 2),
            'anatomical_length' => round($acousticLength - self::END_CORRECTION_TOTAL, 2),
            'confidence' => $confidence,
            'total_samples' => count($samplesFormants),
            'neutral_samples_used' => !empty($neutralSamples) ? $usedSamples : 0
        ];
    }

    /**
     * Estimate anterior web / fusion fraction (w) from F0 before/after.
     *
     * Model:
     *   f_after / f_before = 1 / (1 - w)
     *   => w = 1 - (f_before / f_after)
     *
     * Returns:
     *  - w: estimated fusion fraction (0.0 ~ 1.0)
     *  - w_percent: (0 ~ 100)
     *  - ratio_f: f_after / f_before
     *  - ratio_L: L_eff_after / L_before = f_before / f_after
     *  - semitones: 12 * log2(ratio_f)
     * 
     * @param float $f0_before
     * @param float $f0_after
     * @param bool $clamp
     * 
     * @return array{
     *  ratio_L: float|int, 
     *  ratio_f: float|int, 
     *  semitones: float|int, 
     *  w: float|int, 
     *  w_percent: float|int
     * }
     */
    public static function estimateFusionFraction(float $f0_before, float $f0_after, bool $clamp = true): array
    {
        if ($f0_before <= 0.0 || $f0_after <= 0.0) {
            throw new InvalidArgumentException("F0 values must be > 0.");
        }

        $ratio_f = $f0_after / $f0_before;     // pitch ratio
        $ratio_L = $f0_before / $f0_after;     // effective length ratio

        // w = 1 - f_before/f_after
        $w = 1.0 - $ratio_L;

        if ($clamp) {
            // Clamp to [0, 0.95] just to avoid nonsense from noisy numbers
            if ($w < 0.0) {
                $w = 0.0;
            }
            if ($w > 0.95) {
                $w = 0.95;
            }
        }

        $semitones = 12.0 * (log($ratio_f) / log(2.0));

        return [
            "w" => $w,
            "w_percent" => $w * 100.0,
            "ratio_f" => $ratio_f,
            "ratio_L" => $ratio_L,
            "semitones" => $semitones,
        ];
    }

    /**
     * Forward model: given desired fusion fraction w, predict F0_after.
     * f_after = f_before / (1 - w)
     * 
     * @param float $f0_before
     * @param float $w
     * 
     * @return float
     */
    public static function predictF0After(float $f0_before, float $w): float
    {
        if ($f0_before <= 0.0) {
            throw new InvalidArgumentException("f0_before must be > 0.");
        }
        if ($w < 0.0 || $w >= 1.0) {
            throw new InvalidArgumentException("w must be in [0, 1).");
        }

        return $f0_before / (1.0 - $w);
    }
}