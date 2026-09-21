<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration\Bitwise;

/**
 * Class Bit
 *
 * Defines constants for bitwise operations and hashing algorithms.
 */
abstract class Bit
{
    public const UINT32_MAX_PLUS_ONE = 4294967296;
    public const MULBERRY32_INCREMENT = 0x6d2b79f5;
    public const MULBERRY32_MULTIPLIER_MASK = 1;
    public const MULBERRY32_SECOND_MULTIPLIER = 61;
    public const MULBERRY32_SHIFT1 = 15;
    public const MULBERRY32_SHIFT2 = 7;
    public const MULBERRY32_SHIFT3 = 14;
    /**
     *  2(32) /ϕ
     * @var int
     */
    public const GOLDEN_RATIO_APPROXIMATION = 0x9e3779b9;
    public const BIT_SCRAMBLING_CONSTANT_A = 0x21f0aaad;
    public const BIT_SCRAMBLING_CONSTANT_B = 0x735a2d97;

    public const MASK_64 = '0xFFFFFFFFFFFFFFFF';
    public const LEHMER_64 = '0xDA942042E4DD58B5';
    public const PCG_M64 = '0x2360ed051fc65da44385df649fccf645';
    public const PCG_A64 = '0x5851f42d4c957f2d14057b7ef767814f';
}
