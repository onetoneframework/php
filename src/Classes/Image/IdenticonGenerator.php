<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Image;

use Clover\Classes\OperationSystem;
use function count;
use function strlen;

/**
 * Class IdenticonGenerator
 *
 * @package Clover\Classes\Image
 */
class IdenticonGenerator
{
    private $size;
    private $blocks;
    private $sprites;
    private $image;

    /**
     * IdenticonGenerator constructor.
     *
     * @param int $size
     * @param int $blocks
     */
    public function __construct($size = 64, $blocks = 4)
    {
        $this->size = $size;
        $this->blocks = $blocks;
        $this->sprites = $this->loadSprites();
    }

    /**
     * Generate an identicon image based on the input string.
     *
     * @param string $input
     * 
     * @return bool|\GdImage|resource
     */
    public function generate(string $input): mixed
    {
        $hash = sha1($input);
        $this->image = imagecreatetruecolor($this->size, $this->size);
        imagesavealpha($this->image, true);
        imagefill($this->image, 0, 0, imagecolorallocatealpha($this->image, 0, 0, 0, 127));

        list($r, $g, $b) = $this->rgb(hexdec(substr($hash, -6)));
        $fg = imagecolorallocate($this->image, $r, $g, $b);

        $dim = floor($this->size / $this->blocks);
        $spriteCount = count($this->sprites);

        for ($j = 0; $j < ceil($this->blocks / 2); ++$j) {
            for ($i = $j; $i < $this->blocks - $j; ++$i) {
                $spriteIndex = hexdec($hash[($j * $this->blocks + $i) % strlen($hash)]) % $spriteCount;
                $polygon = $this->sprites[$spriteIndex];
                $points = array_map(fn($p) => $p * $dim, $polygon);

                $sprite = imagecreatetruecolor((int) $dim, (int) $dim);
                imagefill($sprite, 0, 0, imagecolorallocatealpha($sprite, 0, 0, 0, 127));
                imagesavealpha($sprite, true);
                imagefilledpolygon($sprite, $points, count($points) / 2, $fg);

                for ($k = 0; $k < 4; ++$k) {
                    $x = round($i * $dim);
                    $y = round($j * $dim);
                    imagecopy($this->image, $sprite, (int) $x, (int) $y, 0, 0, (int) $dim, (int) $dim);
                    $sprite = imagerotate($sprite, 90, imagecolorallocatealpha($sprite, 0, 0, 0, 127));
                }

                if (OperationSystem::comparePHPVersion('8.5.0', '<')) {
                    // @phpstan-ignore-next-line
                    imagedestroy($sprite);
                }
            }
        }

        return $this->image;
    }

    /**
     * Save the generated identicon image to a file.
     *
     * @param string $path
     * 
     * @return void
     */
    public function save($path = 'identicon.png'): void
    {
        imagesavealpha($this->image, true);
        imagepng($this->image, $path);

        if (OperationSystem::comparePHPVersion('8.5.0', '<')) {
            // @phpstan-ignore-next-line
            imagedestroy($this->image);
        }
    }

    /**
     * Output the generated identicon image directly to the browser.
     *
     * @return void
     */
    public function output(): void
    {
        header('Content-Type: image/png');
        imagepng($this->image);

        if (OperationSystem::comparePHPVersion('8.5.0', '<')) {
            // @phpstan-ignore-next-line
            imagedestroy($this->image);
        }
    }

    /**
     * Convert a hex color value to RGB components.
     *
     * @param int $value
     * 
     * @return array<int>
     */
    private function rgb(int $value): array
    {
        return [
            ($value >> 16) & 0xFF,
            ($value >> 8) & 0xFF,
            $value & 0xFF
        ];
    }

    /**
     * Load predefined sprite patterns for identicon generation.
     *
     * @return array<array<float>>
     */
    private function loadSprites(): array
    {
        return [
            [.5, 1, 1, 0, 1, 1],
            [.5, 0, 1, 0, .5, 1, 0, 1],
            [.5, 0, 1, 0, 1, 1, .5, 1, 1, .5],
            [0, .5, .5, 0, 1, .5, .5, 1, .5, .5],
            [0, .5, 1, 0, 1, 1, 0, 1, 1, .5],
            [1, 0, 1, 1, .5, 1, 1, .5, .5, .5],
            [0, 0, 1, 0, 1, .5, 0, 0, .5, 1, 0, 1],
            [0, 0, .5, 0, 1, .5, .5, 1, 0, 1, .5, .5],
            [.5, 0, .5, .5, 1, .5, 1, 1, .5, 1, .5, .5, 0, .5],
            [0, 0, 1, 0, .5, .5, 1, .5, .5, 1, .5, .5, 0, 1],
        ];
    }
}