<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Implement;

/**
 * Image Handler Interface
 *
 * Defines the contract for image processing operations.
 * Provides methods for image manipulation, filtering, and transformation.
 */
interface ImageHandlerInterface
{

	public function isAnimated(string $filename);

	public function drawRepeat($imageResource, $tile, int $width = 0, int $height = 0);

	public function drawEclipse(mixed $imageResource, int $width, int $height, int $x, int $y, int $red, int $green, int $blue);

	public function combine(mixed $paletteImage, mixed $combineImage, int $right = 0, int $top = 0);

	public function ratioResize(mixed $imageResource, int $resizeWidth, int $resizeHeight, int $thumbnailWidth = 0, int $thumbnailHeight = 0);

	public function filter($imageResource, string $type, ...$args);

	public function draw(mixed $imageResource, string $format, int $quality);

	public function pickColor($imageResource, $x, $y): array;

	public function drawText($imageResource, $fontSize, $x, $y, $text, $red, $green, $blue);

	public function getExifData(string $filePath, string|null $required_sections);

	public function fixOrientation(string $filePath, mixed $imageResource);

	public static function getType($filePath);

	public static function create(string $format, mixed $imageResource, mixed $outputPath, int $quality = 100);

	public function flip(mixed $imageResource, string $type);

	public static function getWidth($imageResource);

	public static function getHeight($imageResource);

	public static function isResource($imageResource);

	public function rotate($imageResource, $degrees);

	public static function getimageResource($filePath);

	public function getBlank($width, $height, $red, $blue, $green);

	public function merge($sourceCreateObject, $mergeCreateObject, $transparent);

	public static function getInstance($filePath);

	public function hexToRgb(string $hex);
}
