<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Enumeration\DateTime;

enum Sine
{

    // =========================================================================
    // §27  MINIMAX POLYNOMIALS — TURN-BASED SINE  (§E.2)
    //     P(x) = c₁x + c₃x³ + c₅x⁵ + …  for sin(2πx), x ∈ [0, 0.25]
    // =========================================================================

    // 5th-degree (max error ≈ 1.1×10⁻⁴, ≈8 s timing)
    public const SINE_5DEG_C1                       = 6.281548;
    public const SINE_5DEG_C3                       = -41.133393;
    public const SINE_5DEG_C5                       = 74.171088;

    // 7th-degree (max error ≈ 1.1×10⁻⁶, astronomical precision)
    public const SINE_7DEG_C1                       = 6.283171;
    public const SINE_7DEG_C3                       = -41.337984;
    public const SINE_7DEG_C5                       = 81.371979;
    public const SINE_7DEG_C7                       = -71.315978;
    
    // =========================================================================
    // §29  SINE LOOK-UP TABLE — 28-POINT, AMPLITUDE 1024  (§E.1)
    //     Symmetry: tab[14-i] = tab[i], tab[14+i] = −tab[i], period 28
    //     Primary quadrant values for i = 0 … 7
    // =========================================================================

    public const SINE_LUT_AMPLITUDE                 = 1024;
    public const SINE_LUT_PERIOD                    = 28;
    // Values: [0, 228, 444, 638, 801, 923, 998, 1024]
    public const SINE_LUT_I0                        = 0;
    public const SINE_LUT_I1                        = 228;
    public const SINE_LUT_I2                        = 444;
    public const SINE_LUT_I3                        = 638;
    public const SINE_LUT_I4                        = 801;
    public const SINE_LUT_I5                        = 923;
    public const SINE_LUT_I6                        = 998;
    public const SINE_LUT_I7                        = 1024;
}
