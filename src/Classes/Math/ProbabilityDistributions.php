<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Math;

/**
 * Class ProbabilityDistributions
 *
 * A utility class for probability distribution functions.
 */
class ProbabilityDistributions
{
    /**
     * Calculate the probability of a Bernoulli distribution for a given outcome and probability of success.
     *
     * @param int $outcome The outcome of the Bernoulli trial (0 or 1).
     * @param float $probabilityOfSuccess The probability of success (between 0 and 1).
     * @return float The probability of the given outcome.
     * @throws \InvalidArgumentException If the outcome is not 0 or 1, or if the probability of success is not between 0 and 1.
     */
    public static function bernoulliProbability(int $outcome, float $probabilityOfSuccess): float
    {
        if ($outcome === 1) {
            return $probabilityOfSuccess;
        } elseif ($outcome === 0) {
            return 1 - $probabilityOfSuccess;
        } else {
            throw new \InvalidArgumentException("Outcome must be 0 or 1.");
        }
    }

    /**
     * Calculate the probability density function of a normal distribution.
     *
     * @param float $x The value for which to calculate the probability density.
     * @param float $mu The mean of the normal distribution.
     * @param float $sigma The standard deviation of the normal distribution.
     * @return float The probability density at the given value.
     */
    public static function normalProbabilityDensity(float $x, float $mu, float $sigma): float
    {
        $coefficient = 1 / ($sigma * sqrt(2 * pi()));
        $exponent = -0.5 * pow(($x - $mu) / $sigma, 2);
        return $coefficient * exp($exponent);
    }

}
