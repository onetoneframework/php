<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Enumeration\DateTime;

enum Rational
{
    // =========================================================================
    // §26  NUMERICAL APPROXIMATION — RATIONAL PRECONDITIONER  (§E.4.2)
    //     B̃⁻¹ ≈ 1/B for the synodic elongation rate
    //     Used in the decoupled Picard iteration to prevent LCM explosion
    // =========================================================================

    /** Rational preconditioner numerator: 295306/10000 ≈ 29.5306 days */
    public const RATIONAL_PRECONDITIONER_NUM        = 295306;
    public const RATIONAL_PRECONDITIONER_DEN        = 10000;
    public const RATIONAL_PRECONDITIONER            = 295306 / 10000;
}
