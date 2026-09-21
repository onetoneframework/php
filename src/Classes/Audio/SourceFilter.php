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
 * Class SourceFilter
 * 
 * This class implements a source-filter model for speech synthesis,
 * allowing the generation of harmonic spectra and application of formant filters.
 */
class SourceFilter extends BaseClass
{
    /**
     * @var array{frequency: float, amplitude: float}[] Harmonic spectrum of the source.
     */
    private array $harmonics = [];
    /**
     * @var float Fundamental frequency of the source.
     */
    private float $fundamentalFrequency;
    /**
     * @var int Number of harmonics to generate.
     */
    private int $harmonicCount;

    /**
     * Constructor for SourceFilter.
     *
     * @param float $fundamentalFrequency Fundamental frequency of the source.
     * 
     * @param int $harmonicCount Number of harmonics to generate.
     */
    public function __construct(float $fundamentalFrequency, int $harmonicCount = 50)
    {
        $this->fundamentalFrequency = $fundamentalFrequency;
        $this->harmonicCount = $harmonicCount;
        $this->generateHarmonics();
    }

    /**
     * Generate the harmonic spectrum based on the fundamental frequency.
     * 
     * @return void
     */
    private function generateHarmonics(): void
    {
        for ($i = 1; $i <= $this->harmonicCount; $i++) {
            $this->harmonics[] = [
                'frequency' => $this->fundamentalFrequency * $i,
                'amplitude' => 1.0 / $i
            ];
        }
    }

    /**
     * Apply formant filters to the harmonic spectrum.
     *
     * @param array{array:{frequency: float, bandwidth: float}} $formants Array of formants, each with 'frequency' and 'bandwidth'.
     * 
     * @return array{
     *  amplitude: float|int|mixed,
     *  frequency: float|int|mixed
     * }[] Filtered harmonic spectrum.
     */
    public function applyFormantFilter(array $formants): array
    {
        $filtered = [];

        foreach ($this->harmonics as $harmonic) {
            $amplitude = $harmonic['amplitude'];

            foreach ($formants as $formant) {
                $amplitude *= $this->calculateFormantResponse(
                    $harmonic['frequency'],
                    $formant['frequency'],
                    $formant['bandwidth']
                );
            }

            $filtered[] = [
                'frequency' => $harmonic['frequency'],
                'amplitude' => $amplitude
            ];
        }

        return $filtered;
    }

    /**
     * Calculate the formant response at a given frequency.
     *
     * @param float $frequency Frequency of the harmonic.
     * @param float $formantFreq Center frequency of the formant.
     * @param float $bandwidth Bandwidth of the formant.
     * 
     * @return float Formant response value.
     */
    private function calculateFormantResponse(float $frequency, float $formantFreq, float $bandwidth): float
    {
        $normalized = ($frequency - $formantFreq) / $bandwidth;
        return exp(-0.5 * pow($normalized, 2));
    }

    /**
     * Get the harmonic spectrum of the source.
     * 
     * @return array{
     *  amplitude: float, 
     *  frequency: float
     * }[] Harmonic spectrum with frequency and amplitude.
     */
    public function getSpectrum(): array
    {
        return $this->harmonics;
    }

    /**
     * Set a new fundamental frequency and regenerate harmonics.
     *
     * @param float $frequency New fundamental frequency.
     * 
     * @return void
     */
    public function setFundamentalFrequency(float $frequency): void
    {
        $this->fundamentalFrequency = $frequency;
        $this->generateHarmonics();
    }

    /**
     * Get the amplitude at a specific frequency.
     *
     * @param float $frequency Frequency to query.
     * 
     * @return float Amplitude at the specified frequency.
     */
    public function getAmplitudeAtFrequency(float $frequency): float
    {
        $closest = null;
        $minDiff = PHP_FLOAT_MAX;

        foreach ($this->harmonics as $harmonic) {
            $diff = abs($harmonic['frequency'] - $frequency);
            if ($diff < $minDiff) {
                $minDiff = $diff;
                $closest = $harmonic;
            }
        }

        return $closest ? $closest['amplitude'] : 0.0;
    }
}
