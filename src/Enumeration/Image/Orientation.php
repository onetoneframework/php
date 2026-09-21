<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration;

/**
 * Enumeration class for image orientations.
 */
abstract class Orientation
{
    public const BOTH = 'both';
    public const HORIZONTAL = 'horizontal';
    public const LANDSCAPE = 'landscape';
    public const NORMAL = 'normal';
    public const PORTRAIT = 'portrait';
    public const VERTICAL = 'vertical';
}
