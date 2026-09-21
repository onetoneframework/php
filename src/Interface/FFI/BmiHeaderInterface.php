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
 * BMI Header Interface
 *
 * Defines the structure for bitmap header information (BITMAPINFOHEADER).
 * Contains metadata about bitmap dimensions, color depth, and compression.
 */
abstract class BmiHeaderInterface
{
    /**
     * @var int Number of bits per pixel.
     */
    public $biBitCount;

    /**
     * @var int Compression type used for the bitmap.
     */
    public $biCompression;

    /**
     * @var int Height of the bitmap in pixels.
     */
    public $biHeight;

    /**
     * @var int Number of color planes.
     */
    public $biPlanes;

    /**
     * @var int Size of the structure in bytes.
     */
    public $biSize;

    /**
     * @var int Width of the bitmap in pixels.
     */
    public $biWidth;
}