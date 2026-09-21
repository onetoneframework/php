<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Enumeration\DateTime;

enum Magic
{
    // =========================================================================
    // §30  FAST INVERSE SQUARE ROOT — 64-BIT REFERENCE  (§E.3)
    //     y₀ bits = MAGIC − (float_bits >> 1)
    //     Newton step: y ← y·(1.5 − 0.5·S·y²);  repeat n times
    // =========================================================================

    /** Magic constant for 64-bit fast inverse square root */
    public const FAST_INV_SQRT_MAGIC_64 = 0x5FE6EB50C7B537A9;
}
