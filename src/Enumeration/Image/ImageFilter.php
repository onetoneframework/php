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
 * Enumeration class for image filters.
 */
abstract class ImageFilter
{
    public const BRIGHTNESS = 'brightness';
    public const COLORIZE = 'colorize';
    public const CONTRAST = 'contrast';
    public const EDGEDETECT = 'edgedetect';
    public const EMBOSS = 'emboss';
    public const GAUSSIAN_BLUR = 'gaussian_blur';
    public const GRAYSCALE = 'grayscale';
    public const PIXELATE = 'pixelate';
    public const REVERSE = 'reverse';
    public const SCATTER = 'scatter';
    public const SELECTIVE_BLUR = 'selective_blur';
    public const SKETCH = 'sketch';
    public const SMOOTH = 'smooth';
}
