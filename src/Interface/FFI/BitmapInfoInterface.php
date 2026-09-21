<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Interface\FFI;

/**
 * Bitmap Info Interface
 *
 * Defines the structure for bitmap information.
 * Contains header information for Windows bitmap images.
 */
abstract class BitmapInfoInterface
{
    /**
     * @var BmiHeaderInterface The bitmap header information.
     */
    public BmiHeaderInterface $bmiHeader;
}