<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Math;

use Clover\Enumeration\LengthUnit;

/**
 * Class Health
 *
 * A utility class for health-related mathematical operations.
 */
class Health
{
    /**
     * Calculate Body Mass Index (BMI)
     * 
     * @param int $mass
     * @param int $height
     * @param string $unit
     * @param string|null $heightUnit
     * 
     * @return null|float|int
     */
    public static function getBMI(int $mass, int $height, string $unit, ?string $heightUnit = null): float|int|null
    {
        switch (strtolower($unit)) {
            case 'usc':
                $mass *= Length::getConversionFactor(LengthUnit::INCHES, $heightUnit ?? LengthUnit::INCHES);
                return 703 * ($mass / pow($height, 2));
            case 'metric':
                $mass *= Length::getConversionFactor(LengthUnit::METERS, $heightUnit ?? LengthUnit::METERS);
                return $mass / pow($height, 2);
            default:
                return null;
        }
    }

    /**
     * Get classification from BMI value
     * 
     * @param int $bmi
     * 
     * @return ?string
     */
    public static function getClassificationFromBMI(int $bmi): ?string
    {
        if ($bmi < 16) {
            return 'Severe Thinness';
        } else if ($bmi >= 16 && $bmi <= 17) {
            return 'Moderate Thinness';
        } else if ($bmi <= 18.5 && $bmi > 17) {
            return 'Mild Thinness';
        } else if ($bmi <= 25 && $bmi > 18.5) {
            return 'Normal';
        } else if ($bmi <= 30 && $bmi > 25) {
            return 'Overweight';
        } else if ($bmi <= 35 && $bmi > 30) {
            return 'Obese Class I';
        } else if ($bmi <= 40 && $bmi > 35) {
            return 'Obese Class II';
        } else if ($bmi > 40) {
            return 'Obese Class III';
        }

        return null;
    }
}
