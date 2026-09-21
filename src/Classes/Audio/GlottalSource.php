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
 * Class GlottalSource
 * 
 * This class simulates a glottal source for speech synthesis using the LF model.
 */
class GlottalSource extends BaseClass
{
    /**
     * @var float Open quotient of the glottal waveform.
     */
    private float $openQuotient;
    /**
     * @var float Speed quotient of the glottal waveform.
     */
    private float $speedQuotient;
    /**
     * @var float Fundamental frequency of the glottal source.
     */
    private float $frequency;
    
    /**
     * Constructor for GlottalSource.
     *
     * @param float $frequency Fundamental frequency of the glottal source.
     * @param float $openQuotient Open quotient of the glottal waveform.
     * @param float $speedQuotient Speed quotient of the glottal waveform.
     */
    public function __construct(float $frequency, float $openQuotient = 0.7, float $speedQuotient = 0.5)
    {
        $this->frequency = $frequency;
        $this->openQuotient = $openQuotient;
        $this->speedQuotient = $speedQuotient;
    }

    /**
     * Generate the LF waveform samples.
     *
     * @param int $samples Number of samples to generate.
     * @param int $sampleRate Sample rate in Hz.
     * 
     * @return array<float> Generated waveform samples.
     */
    public function generateLFWaveform(int $samples, int $sampleRate): array
    {
        $waveform = [];
        $period = $sampleRate / $this->frequency;
        
        for ($i = 0; $i < $samples; $i++) {
            $phase = fmod($i, $period) / $period;
            $waveform[] = $this->calculateLFValue($phase);
        }
        
        return $waveform;
    }

    /**
     * Calculate the LF model value at a given phase.
     *
     * @param float $phase Phase of the waveform (0 to 1).
     * 
     * @return float LF model value.
     */
    private function calculateLFValue(float $phase): float
    {
        $te = $this->openQuotient;
        $tp = $te * $this->speedQuotient;
        
        if ($phase < $tp) {
            return 0.5 * (1 - cos(M_PI * $phase / $tp));
        } elseif ($phase < $te) {
            return cos(M_PI * ($phase - $tp) / (2 * ($te - $tp)));
        } else {
            return 0.0;
        }
    }

    /**
     * Get the harmonic spectrum of the glottal source.
     *
     * @param int $harmonics Number of harmonics to calculate.
     * 
     * @return array{
     *  frequency: float, 
     *  amplitude: float
     * }[] Harmonic spectrum with frequency and amplitude.
     */
    public function getSpectrum(int $harmonics): array
    {
        $spectrum = [];
        
        for ($i = 1; $i <= $harmonics; $i++) {
            $amplitude = $this->calculateHarmonicAmplitude($i);
            $spectrum[] = [
                'frequency' => $this->frequency * $i,
                'amplitude' => $amplitude
            ];
        }
        
        return $spectrum;
    }

    /**
     * Calculate the amplitude of a given harmonic.
     *
     * @param int $harmonic Harmonic number.
     * 
     * @return float Amplitude of the harmonic.
     */
    private function calculateHarmonicAmplitude(int $harmonic): float
    {
        return 1.0 / pow($harmonic, 2);
    }
}
