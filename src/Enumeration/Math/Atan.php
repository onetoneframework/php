<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Enumeration\DateTime;

enum Atan
{
    // =========================================================================
    // §28  MINIMAX POLYNOMIALS — TURN-BASED ARCTANGENT  (§E.2)
    //     P(x) = c₁x + c₃x³ + c₅x⁵ + …  for arctan(x)/2π, x ∈ [0, 1]
    //     For |x| > 1 apply: arctan(x) = 0.25 turn − arctan(1/x)
    // =========================================================================

    // 5th-degree (max error ≈ 1.5×10⁻⁴)
    public const ATAN_5DEG_C1                       = 0.158452;
    public const ATAN_5DEG_C3                       = -0.046517;
    public const ATAN_5DEG_C5                       = 0.013212;

    // 7th-degree (max error ≈ 2.2×10⁻⁵)
    public const ATAN_7DEG_C1                       = 0.159056;
    public const ATAN_7DEG_C3                       = -0.051293;
    public const ATAN_7DEG_C5                       = 0.023719;
    public const ATAN_7DEG_C7                       = -0.006503;
}
