<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Data;
use RuntimeException;

/**
 * Class WideSpace
 *
 * Represents a wide space for drawing shapes and managing spatial data.
 */
class WideSpace
{
    private array $spaces;
    private array $camera;
    private int $width;
    private int $height;

    /**
     * WideSpace constructor.
     * 
     * @param int $width
     * @param int $height
     * @throws RuntimeException
     */
    public function __construct(int $width, int $height)
    {
        $width *= 2;
        $height *= 2;

        if ($width % 2 !== 0) {
            throw new RuntimeException('The value of width is must be odd');
        }

        if ($height % 2 !== 0) {
            throw new RuntimeException('The value of height is must be odd');
        }

        $this->width = $width;
        $this->height = $height;

        $flattern = array_fill(0, $height, array_fill(0, $width, '0'));

        $this->spaces = array_fill(0, $height, $flattern);
    }

    /**
     * Print the wide space
     * 
     * @return void
     */
    public function printSpaces(): void
    {
        foreach ($this->spaces as $space) {
            foreach ($space as $plate) {
                echo implode(array_map(function ($value) {
                    return $value == 0 ? ' ' : '▒';
                }, $plate)) . "\n";
            }
            echo "\n";
        }
    }

    /**
     * Draw a line box in the wide space
     * 
     * @param int $width
     * @param int $height
     * @param int $x
     * @param int $y
     * 
     * @return void
     * 
     * @throws RuntimeException
     */
    public function drawLineBox(int $width, int $height, int $x, int $y): void
    {
        $width *= 2;
        $height *= 2;

        if ($width % 2 !== 0) {
            throw new RuntimeException('The value of width is must be odd');
        }

        if ($height % 2 !== 0) {
            throw new RuntimeException('The value of height is must be odd');
        }

        $half_space_width = $this->width / 2;
        $half_space_height = $this->height / 2;
        $half_width = (int) ($width / 2);
        $half_height = (int) ($height / 2);

        if (($half_width + -$x) > $half_space_width || ($half_width + $x) > $half_space_width) {
            throw new RuntimeException('Object space is overflow');
        }

        if (($half_height + -$y) > $half_space_height || ($half_height + $x) > $half_space_height) {
            throw new RuntimeException('Object space is overflow');
        }

        $space_depth = \count($this->spaces);
        for ($i = 0; $i < $space_depth; $i++) {
            $xl_vector = $half_space_width - $half_width;
            $xr_vector = $half_space_height + $half_height;
            $space = &$this->spaces[$i];

            if ($i < $xl_vector || $i >= $xr_vector) {
                continue;
            }

            $dot_vector = \count($space);
            for ($z = 0; $z < $dot_vector; $z++) {

                if ($z >= $xl_vector && $z < $xr_vector) {
                    $margin_xl_vector = $half_space_width - $half_width;
                    $margin_xr_vector = $this->width - ($margin_xl_vector + $width);

                    $is_outline = $i == $xl_vector || ($i == $xr_vector - 1);

                    // Fill the plate vector
                    if ($is_outline) {
                        $space[$z] = [
                            ...array_fill(0, $margin_xl_vector, 0),
                            ...array_fill(0, $width, 1),
                            ...array_fill(0, $margin_xr_vector, 0),
                        ];
                        // Fill the outline vector
                    } else {
                        $inline = $z == $xl_vector || ($z == $xr_vector - 1);

                        if ($inline) {

                            $space[$z] = [
                                ...array_fill(0, $margin_xl_vector, 0),
                                ...array_fill(0, $width, 1),
                                ...array_fill(0, $margin_xr_vector, 0),
                            ];
                        } else {
                            $space[$z] = [
                                ...array_fill(0, $margin_xl_vector, 0),
                                1,
                                ...array_fill(0, $width - 2, 0),
                                1,
                                ...array_fill(0, $margin_xr_vector, 0),
                            ];
                        }
                    }
                }
            }
        }
    }

}