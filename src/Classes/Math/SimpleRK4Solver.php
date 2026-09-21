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
 * Class SimpleRK4Solver
 *
 * A simple implementation of the Runge-Kutta 4th order method for solving ordinary differential equations (ODEs).
 */
class SimpleRK4Solver
{
    /**
     * Solve an ordinary differential equation using the Runge-Kutta 4th order method.
     *
     * @param string $initialY The initial value of the dependent variable.
     * @param string $tStart The starting time.
     * @param string $tEnd The ending time.
     * @param string $h The step size.
     * @param callable $f The function representing the derivative (dy/dt = f(t, y)).
     * @return array An array of [time, value] pairs representing the solution at each time step.
     */
    public static function solve(string $initialY, string $tStart, string $tEnd, string $h, callable $f): array
    {
        AdvancedMathBCMath::setScale(20);
        $currentTime = $tStart;
        $currentY = $initialY;
        $results = [[$currentTime, $currentY]];

        while (AdvancedMathBCMath::bccomp($currentTime, $tEnd, 10) < 0) {
            $nextY = AdvancedMathBCMath::rungeKutta4($currentTime, $currentY, $f, $h);
            $currentTime = AdvancedMathBCMath::bcadd($currentTime, $h);
            $results[] = [$currentTime, $nextY];
            $currentY = $nextY;
        }
        return $results;
    }
}
