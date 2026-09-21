<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\File;

use Clover\Classes\OperationSystem;

define('RECOVERY_OFFSET_X', 1);
define('RECOVERY_OFFSET_Y', 0);

class SteganographyProcessor
{
    private const PSEUDO_RANDOM_SEED_R = 12345;
    private const PSEUDO_RANDOM_SEED_G = 54321;
    private const PSEUDO_RANDOM_SEED_B = 98765;

    private const JPEG_QUALITY = 90;
    private const PNG_COMPRESSION = 9;

    public static function embedBitInChannel(int $channelValue, int $bitToHide): int
    {
        if ($bitToHide == 0) {
            return $channelValue & 0xFE;
        } else {
            return $channelValue | 0x01;
        }
    }

    public static function extractBitFromChannel(int $channelValue): int
    {
        return $channelValue & 0x01;
    }

    private static function generatePseudoRandomChannelValue(int $x, int $y, int $seed): int
    {
        $hash = crc32(sprintf("%d-%d-%d", $x, $y, $seed));
        return abs($hash) % 256;
    }

    public static function createDistortedImages(string $imagePath, string $outputNoisePath, string $outputRecoveryPath): bool
    {
        $imageType = @exif_imagetype($imagePath);
        if ($imageType === false) {
            error_log("SteganographyProcessor: Could not determine image type for " . $imagePath);
            return false;
        }

        $sourceImage = null;
        switch ($imageType) {
            case IMAGETYPE_PNG:
                $sourceImage = @imagecreatefrompng($imagePath);
                break;
            case IMAGETYPE_JPEG:
                $sourceImage = @imagecreatefromjpeg($imagePath);
                break;
            default:
                error_log("SteganographyProcessor: Unsupported image type for " . $imagePath);
                return false;
        }

        if (!$sourceImage) {
            error_log("SteganographyProcessor: Failed to load source image " . $imagePath);
            return false;
        }

        $width = imagesx($sourceImage);
        $height = imagesy($sourceImage);

        if (!imageistruecolor($sourceImage)) {
            imagepalettetotruecolor($sourceImage);
        }

        $imageNoise = imagecreatetruecolor($width, $height);
        $imageRecovery = imagecreatetruecolor($width, $height);

        if (!$imageNoise || !$imageRecovery) {
            error_log("SteganographyProcessor: Failed to create image handles.");
            if ($sourceImage) {
                if (OperationSystem::comparePHPVersion('8.5.0', '<')) {
                    // @phpstan-ignore-next-line
                    imagedestroy($sourceImage);
                }
            }

            if ($imageNoise) {
                if (OperationSystem::comparePHPVersion('8.5.0', '<')) {
                    // @phpstan-ignore-next-line
                    imagedestroy($imageNoise);
                }
            }

            if ($imageRecovery) {
                if (OperationSystem::comparePHPVersion('8.5.0', '<')) {
                    // @phpstan-ignore-next-line
                    imagedestroy($imageRecovery);
                }
            }

            return false;
        }

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $rgbOriginal = imagecolorat($sourceImage, $x, $y);
                $origR = ($rgbOriginal >> 16) & 0xFF;
                $origG = ($rgbOriginal >> 8) & 0xFF;
                $origB = $rgbOriginal & 0xFF;

                $noiseR = self::generatePseudoRandomChannelValue($x, $y, self::PSEUDO_RANDOM_SEED_R);
                $noiseG = self::generatePseudoRandomChannelValue($x, $y, self::PSEUDO_RANDOM_SEED_G);
                $noiseB = self::generatePseudoRandomChannelValue($x, $y, self::PSEUDO_RANDOM_SEED_B);

                $colorNoise = imagecolorallocate($imageNoise, $noiseR, $noiseG, $noiseB);
                if ($colorNoise !== false) {
                    imagesetpixel($imageNoise, $x, $y, $colorNoise);
                }

                $maskedR = $origR ^ $noiseR;
                $maskedG = $origG ^ $noiseG;
                $maskedB = $origB ^ $noiseB;
                $colorRecovery = imagecolorallocate($imageRecovery, $maskedR, $maskedG, $maskedB);
                if ($colorRecovery !== false) {
                    imagesetpixel($imageRecovery, $x, $y, $colorRecovery);
                }
            }
        }

        if (OperationSystem::comparePHPVersion('8.5.0', '<')) {
            // @phpstan-ignore-next-line
            imagedestroy($sourceImage);
        }

        $saveNoiseResult = false;
        switch ($imageType) {
            case IMAGETYPE_PNG:
                $saveNoiseResult = imagepng($imageNoise, $outputNoisePath, self::PNG_COMPRESSION);
                break;
            case IMAGETYPE_JPEG:
                $saveNoiseResult = imagejpeg($imageNoise, $outputNoisePath, self::JPEG_QUALITY);
                break;
            default:
                $saveNoiseResult = imagepng($imageNoise, $outputNoisePath, self::PNG_COMPRESSION);
        }

        if (OperationSystem::comparePHPVersion('8.5.0', '<')) {
            // @phpstan-ignore-next-line
            imagedestroy($imageNoise);
        }

        if (!$saveNoiseResult) {
            error_log("SteganographyProcessor: Failed to save noise image to " . $outputNoisePath);
            if ($imageRecovery) {
                if (OperationSystem::comparePHPVersion('8.5.0', '<')) {
                    // @phpstan-ignore-next-line
                    imagedestroy($imageRecovery);
                }
            }

            return false;
        }

        $saveRecoveryResult = false;
        switch ($imageType) {
            case IMAGETYPE_PNG:
                $saveRecoveryResult = imagepng($imageRecovery, $outputRecoveryPath, self::PNG_COMPRESSION);
                break;
            case IMAGETYPE_JPEG:
                $saveRecoveryResult = imagepng($imageRecovery, $outputRecoveryPath, self::PNG_COMPRESSION);
                break;
            default:
                $saveRecoveryResult = imagepng($imageRecovery, $outputRecoveryPath, self::PNG_COMPRESSION);
        }

        if (OperationSystem::comparePHPVersion('8.5.0', '<')) {
            // @phpstan-ignore-next-line
            imagedestroy($imageRecovery);
        }

        if (!$saveRecoveryResult) {
            error_log("SteganographyProcessor: Failed to save recovery image to " . $outputRecoveryPath);
            return false;
        }

        return true;
    }

    public static function restoreImageFromTwo(string $noiseImagePath, string $recoveryImagePath, string $outputRestoredPath): bool
    {
        $imageTypeNoise = @exif_imagetype($noiseImagePath);
        $imageTypeRecovery = @exif_imagetype($recoveryImagePath);

        if ($imageTypeNoise === false || $imageTypeRecovery === false) {
            error_log("SteganographyProcessor: Could not determine image type for noise or recovery image.");
            return false;
        }

        $imageNoise = @imagecreatefrompng($noiseImagePath);
        $imageRecovery = @imagecreatefrompng($recoveryImagePath);

        if (!$imageNoise || !$imageRecovery) {
            error_log("SteganographyProcessor: Failed to load noise or recovery image.");
            if ($imageNoise) {
                if (OperationSystem::comparePHPVersion('8.5.0', '<')) {
                    // @phpstan-ignore-next-line
                    imagedestroy($imageNoise);
                }
            }

            if ($imageRecovery) {
                if (OperationSystem::comparePHPVersion('8.5.0', '<')) {
                    // @phpstan-ignore-next-line
                    imagedestroy($imageRecovery);
                }
            }

            return false;
        }

        $width = imagesx($imageNoise);
        $height = imagesy($imageNoise);

        if (imagesx($imageRecovery) !== $width || imagesy($imageRecovery) !== $height) {
            error_log("SteganographyProcessor: Noise and recovery images have different dimensions.");
            if (OperationSystem::comparePHPVersion('8.5.0', '<')) {
                // @phpstan-ignore-next-line
                imagedestroy($imageNoise);
                // @phpstan-ignore-next-line
                imagedestroy($imageRecovery);
            }
            return false;
        }

        if (!imageistruecolor($imageNoise)) {
            imagepalettetotruecolor($imageNoise);
        }

        if (!imageistruecolor($imageRecovery)) {
            imagepalettetotruecolor($imageRecovery);
        }

        $restoredImage = imagecreatetruecolor($width, $height);
        if (!$restoredImage) {
            error_log("SteganographyProcessor: Failed to create restored image handle.");

            if (OperationSystem::comparePHPVersion('8.5.0', '<')) {
                // @phpstan-ignore-next-line
                imagedestroy($imageNoise);
                // @phpstan-ignore-next-line
                imagedestroy($imageRecovery);
            }
            return false;
        }

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $rgbNoise = imagecolorat($imageNoise, $x, $y);
                $noiseR = ($rgbNoise >> 16) & 0xFF;
                $noiseG = ($rgbNoise >> 8) & 0xFF;
                $noiseB = $rgbNoise & 0xFF;

                $rgbRecovery = imagecolorat($imageRecovery, $x, $y);
                $maskedR = ($rgbRecovery >> 16) & 0xFF;
                $maskedG = ($rgbRecovery >> 8) & 0xFF;
                $maskedB = $rgbRecovery & 0xFF;

                $origR = $maskedR ^ $noiseR;
                $origG = $maskedG ^ $noiseG;
                $origB = $maskedB ^ $noiseB;

                $colorRestored = imagecolorallocate($restoredImage, $origR, $origG, $origB);
                if ($colorRestored !== false)
                    imagesetpixel($restoredImage, $x, $y, $colorRestored);
            }
        }

        if (OperationSystem::comparePHPVersion('8.5.0', '<')) {
            // @phpstan-ignore-next-line
            imagedestroy($imageNoise);
            // @phpstan-ignore-next-line
            imagedestroy($imageRecovery);
        }

        $saveRestoredResult = imagepng($restoredImage, $outputRestoredPath, self::PNG_COMPRESSION);
        if (OperationSystem::comparePHPVersion('8.5.0', '<')) {
            // @phpstan-ignore-next-line
            imagedestroy($restoredImage);
        }

        if (!$saveRestoredResult) {
            error_log("SteganographyProcessor: Failed to save restored image to " . $outputRestoredPath);
            return false;
        }

        return true;
    }
}