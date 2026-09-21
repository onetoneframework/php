<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Image;

#region use

use Clover\Classes\BaseClass;
use Clover\Classes\Header\File as FileHeader;
use Clover\Classes\OperationSystem;
use Clover\Enumeration\{ExifFileHeader, ImageFilter, MIME, Orientation};
use Clover\Exception\Functions\FunctionIsNotExistsException;
use Clover\Implement\ImageHandlerInterface;
use Exception;
use GdImage;
use function chr;
use function count;
use function defined;
use function getimagesizefromstring;
use function is_array;
use function ord;
use function sprintf;
use function strlen;

#endregion

/**
 * Class Handler
 *
 * A comprehensive image manipulation class using the GD library.
 * Provides methods for creating, transforming, filtering, drawing,
 * and outputting images in various formats (JPEG, PNG, GIF, BMP, WebP, AVIF, etc.).
 */
class Handler extends BaseClass implements ImageHandlerInterface
{
	#region function

	/**
	 * Parse a GIF file header to extract the global color table size.
	 *
	 * Reads the GIF89a/GIF87a file header structure including the
	 * signature, version, logical screen descriptor, and calculates
	 * the global color table size from the packed fields.
	 *
	 * @param string $filename  Path to the GIF file to parse
	 *
	 * @return int|false  The global color table size in bytes, or false if the file cannot be opened
	 */
	public function parseGif(string $filename): int|false
	{
		// Attempt to open the file in binary read mode
		if (!$fp = @fopen($filename, 'rb')) {
			return false;
		}

		// Read the 3-byte signature (e.g., "GIF")
		$signature = fread($fp, 3);

		// Read the 3-byte version (e.g., "89a" or "87a")
		$version = fread($fp, 3);

		// Read the 7-byte logical screen descriptor
		$screen_descriptor = fread($fp, 7);

		// Calculate screen width from two bytes (little-endian)
		$screen_width = ((ord($screen_descriptor[1])) << 8) +
			((ord($screen_descriptor[0])));

		// Calculate screen height from two bytes (little-endian)
		$screen_height = ((ord($screen_descriptor[3])) << 8) +
			((ord($screen_descriptor[2])));

		// Calculate global color table size from packed byte fields
		$global_color_table_size = (ord($screen_descriptor[4]) + ord($screen_descriptor[5]));
		$global_color_table_size = 3 * (2 ^ ($global_color_table_size + 1));

		return $global_color_table_size;
	}

	/**
	 * Create a GD image resource from raw image string data.
	 *
	 * Automatically detects the image format (JPEG, PNG, GIF, etc.)
	 * from the binary data and creates the appropriate GD resource.
	 *
	 * @param string $data  Raw binary image data
	 *
	 * @return bool|GdImage|resource  The GD image resource, or false on failure
	 */
	public function createFromString(string $data): mixed
	{
		return imagecreatefromstring($data);
	}

	/**
	 * Check whether a GIF image file contains animation (multiple frames).
	 *
	 * Reads through the file in 100KB chunks looking for GIF frame headers.
	 * An animated GIF contains multiple frames, each preceded by a specific
	 * byte sequence: \x00\x21\xF9\x04 (4 bytes) followed by variable bytes
	 * and then \x00\x2C or \x00\x21.
	 *
	 * @see http://www.php.net/manual/en/function.imagecreatefromgif.php#104473
	 *
	 * @param string $filename  Path to the GIF file to check
	 *
	 * @return bool  True if the GIF contains more than one frame (animated), false otherwise
	 */
	public function isAnimated(string $filename): bool
	{
		// Attempt to open the file in binary read mode
		if (!($fh = @fopen($filename, 'rb'))) {
			return false;
		}

		$count = 0;

		// an animated gif contains multiple "frames", with each frame having a
		// header made up of:
		// * a static 4-byte sequence (\x00\x21\xF9\x04)
		// * 4 variable bytes
		// * a static 2-byte sequence (\x00\x2C) (some variants may use \x00\x21 ?)

		// We read through the file til we reach the end of the file, or we've found
		// at least 2 frame headers

		// Read through the file in chunks until EOF or 2+ frames found
		while (!feof($fh) && $count < 2) {
			$chunk = fread($fh, 1024 * 100); // Read 100KB at a time
			$count += preg_match_all('#\x00\x21\xF9\x04.{4}\x00(\x2C|\x21)#s', $chunk, $matches);
		}

		fclose($fh);

		// More than 1 frame header means it's animated
		return $count > 1;
	}

	/**
	 * Draw a tiled (repeated) image pattern across the entire canvas.
	 *
	 * Sets the given tile image as a fill pattern and draws it repeatedly
	 * across the specified width and height of the destination image.
	 *
	 * @param GdImage|resource $imageResource  The destination image resource
	 * @param GdImage|resource $tile           The tile image to repeat
	 * @param int              $width          Canvas width (0 = use image width)
	 * @param int              $height         Canvas height (0 = use image height)
	 *
	 * @return GdImage|resource|bool  The modified image resource, or false on failure
	 */
	public function drawRepeat(mixed $imageResource, mixed $tile, int $width = 0, int $height = 0): mixed
	{
		if (!self::isResource($imageResource)) {
			$imageResource = self::getInstance($imageResource);
		}

		// Use image dimensions if width/height not explicitly provided
		$width = $width ?? self::getWidth($imageResource);
		$height = $height ?? self::getHeight($imageResource);

		// Set the tile image and fill the rectangle with the tiled pattern
		imagesettile($imageResource, $tile);
		imagefilledrectangle($imageResource, 0, 0, $width, $height, IMG_COLOR_TILED);

		return $imageResource;
	}

	/**
	 * Retrieve EXIF metadata from an image file.
	 *
	 * Reads EXIF data such as camera model, orientation, GPS coordinates,
	 * exposure settings, and other metadata embedded in the image file.
	 * Falls back to returning the file path if exif_read_data is not available.
	 *
	 * @param resource|string $filePath           Path to the image file or file resource
	 * @param string|null     $required_sections  EXIF section to read (default: main image)
	 *
	 * @return array|bool|string  EXIF data array on success, or the file path if EXIF functions unavailable
	 */
	public function getExifData(mixed $filePath, string|null $required_sections = ExifFileHeader::MAIN_IMAGE): array|bool|string
	{
		if (function_exists('exif_read_data')) {
			return exif_read_data($filePath, $required_sections);
		}

		return $filePath;
	}

	/**
	 * Get the rotation degree and flip orientation correction values
	 * corresponding to an EXIF orientation tag value (1-8).
	 *
	 * EXIF orientation values map to specific rotation and flip operations
	 * needed to display the image correctly:
	 *   1 = Normal (no rotation)
	 *   2 = Mirrored horizontally
	 *   3 = Rotated 180 degrees
	 *   4 = Mirrored vertically
	 *   5 = Mirrored horizontally + rotated 270° CW
	 *   6 = Rotated 90° CW (displayed as 270° CCW)
	 *   7 = Mirrored horizontally + rotated 90° CW
	 *   8 = Rotated 270° CW (displayed as 90° CCW)
	 *
	 * @param int $orientation  EXIF orientation value (1-8)
	 *
	 * @return array{Degree: int, Orientation: int}  Rotation degree and flip orientation
	 */
	private function getExifOrientationData(int $orientation): array
	{
		$corrections = [
			// Horizontal (normal) — no transformation needed
			'1' => [
				"Degree" => 0,
				"Orientation" => Orientation::NORMAL
			],
			// Mirror horizontal — flip horizontally only
			'2' => [
				"Degree" => 0,
				"Orientation" => Orientation::HORIZONTAL
			],
			// Rotate 180° — rotate without flipping
			'3' => [
				"Degree" => 180,
				"Orientation" => Orientation::NORMAL
			],
			// Mirror vertical — flip vertically only
			'4' => [
				"Degree" => 0,
				"Orientation" => Orientation::VERTICAL
			],
			// Mirror horizontal and rotate 270° CW
			'5' => [
				"Degree" => 270,
				"Orientation" => Orientation::HORIZONTAL
			],
			// Rotate 90° CW (use 270° for imagerotate which rotates CCW)
			'6' => [
				"Degree" => 270,
				"Orientation" => Orientation::NORMAL
			],
			// Mirror horizontal and rotate 90° CW
			'7' => [
				"Degree" => 90,
				"Orientation" => Orientation::HORIZONTAL
			],
			// Rotate 270° CW (use 90° for imagerotate)
			'8' => [
				"Degree" => 90,
				"Orientation" => Orientation::NORMAL
			]
		];

		return $corrections[$orientation] ?? [];
	}

	/**
	 * Automatically fix the orientation of an image based on its EXIF data.
	 *
	 * Many digital cameras and phones store images in a fixed orientation
	 * and record the correct display orientation in EXIF metadata. This
	 * method reads that metadata and applies the necessary rotation and
	 * flip operations to display the image correctly.
	 *
	 * @param string $filePath       Path to the original image file (for EXIF reading)
	 * @param mixed  $imageResource  The GD image resource to correct
	 *
	 * @return bool|GdImage|resource  The orientation-corrected image resource
	 */
	public function fixOrientation(string $filePath, mixed $imageResource): mixed
	{
		// Read EXIF data from the file
		$exif = $this->getExifData($filePath);

		$image = $imageResource;
		$degree = 0;
		$flip = "";

		// Extract orientation correction values if EXIF orientation is present
		if (!empty($exif['Orientation'])) {
			$orientation = $exif['Orientation'];
			$data = $this->getExifOrientationData($orientation);
			$degree = $data['Degree'];
			$flip = $data['Orientation'];
		}

		// Apply rotation first
		$image = $this->rotate($imageResource, $degree);

		// Then apply flip if needed
		switch ($flip) {
			case Orientation::VERTICAL:
			case Orientation::HORIZONTAL:
				$image = $this->flip($image, $flip);
				break;
		}

		return $image;
	}

	/**
	 * Draw a filled ellipse on an image resource.
	 *
	 * Creates an ellipse centered at the given (x, y) coordinates with the
	 * specified dimensions and fills it with the specified RGB color.
	 *
	 * @param mixed $imageResource  The GD image resource to draw on
	 * @param int   $width          Width of the ellipse
	 * @param int   $height         Height of the ellipse
	 * @param int   $x              X-coordinate of the ellipse center
	 * @param int   $y              Y-coordinate of the ellipse center
	 * @param int   $red            Red component (0-255)
	 * @param int   $green          Green component (0-255)
	 * @param int   $blue           Blue component (0-255)
	 *
	 * @return bool|GdImage|resource  True on success, or false on failure
	 */
	public function drawEclipse(mixed $imageResource, int $width, int $height, int $x, int $y, int $red, int $green, int $blue): mixed
	{
		if (!self::isResource($imageResource)) {
			$imageResource = self::getInstance($imageResource);
		}

		// Allocate the fill color
		$backgroundColor = imagecolorallocate($imageResource, $red, $green, $blue);

		// Draw and fill the ellipse
		$outputImage = imagefilledellipse($imageResource, $x, $y, $width, $height, $backgroundColor);

		return $outputImage;
	}

	/**
	 * Combine (overlay) one image on top of another, positioned from the bottom-right corner.
	 *
	 * Calculates the placement of the overlay image relative to the palette image's
	 * bottom-right corner using the right and top offsets, then copies the overlay
	 * onto the palette image.
	 *
	 * @param mixed $paletteImage  The base (background) image resource
	 * @param mixed $combineImage  The overlay (foreground) image resource
	 * @param int   $right         Offset from the right edge in pixels
	 * @param int   $top           Offset from the bottom edge in pixels
	 *
	 * @return GdImage|resource  The combined image resource
	 */
	public function combine(mixed $paletteImage, mixed $combineImage, int $right = 0, int $top = 0): mixed
	{
		if (!self::isResource($paletteImage)) {
			$paletteImage = self::getInstance($paletteImage);
		}

		if (!self::isResource($combineImage)) {
			$combineImage = self::getInstance($combineImage);
		}

		// Calculate overlay position relative to bottom-right corner
		$x = imagesx($paletteImage) - imagesx($combineImage) - $right;
		$y = imagesy($paletteImage) - imagesy($combineImage) - $top;

		// Copy the overlay image onto the base image
		imagecopy($paletteImage, $combineImage, intval($x), intval($y), 0, 0, imagesx($combineImage), imagesy($combineImage));

		return $paletteImage;
	}

	/**
	 * Resize an image while preserving its aspect ratio.
	 *
	 * Calculates the correct dimensions based on the original aspect ratio
	 * to prevent distortion. Supports alpha channel preservation.
	 *
	 * @param mixed $imageResource   The source image resource or file path
	 * @param int   $resizeWidth     Maximum target width
	 * @param int   $resizeHeight    Maximum target height
	 * @param int   $thumbnailWidth  Width used for ratio calculation when ratio >= 1
	 * @param int   $thumbnailHeight Height used for ratio calculation when ratio < 1
	 *
	 * @return bool|GdImage|resource  The resized image resource, or false on failure
	 */
	public function ratioResize(mixed $imageResource, int $resizeWidth, int $resizeHeight, int $thumbnailWidth = 0, int $thumbnailHeight = 0): mixed
	{
		if (!self::isResource($imageResource)) {
			$imageResource = self::getInstance($imageResource);
		}

		// Get original dimensions and calculate aspect ratio
		list($origin_width, $origin_height) = getimagesize($imageResource);
		$ratio = $origin_width / $origin_height;
		$resizeWidth = $resizeHeight = min($resizeWidth, max($origin_width, $origin_height));

		// Adjust dimensions based on aspect ratio to maintain proportions
		if ($ratio < 1) {
			$resizeWidth = $thumbnailHeight * $ratio;
		} else {
			$resizeHeight = $thumbnailWidth / $ratio;
		}

		// Create a new true-color canvas at the target dimensions
		$outputImage = imagecreatetruecolor($resizeWidth, $resizeHeight);

		$width = self::getWidth($imageResource);
		$height = self::getHeight($imageResource);

		// Disable alpha blending and save alpha for transparency support
		imagealphablending($outputImage, false);
		imagesavealpha($outputImage, false);

		// Resample (high-quality resize) from source to destination
		imagecopyresampled($outputImage, $imageResource, 0, 0, 0, 0, $resizeWidth, $resizeHeight, $width, $height);

		return $outputImage;
	}

	/**
	 * Convert YUYV (YUV 4:2:2) raw video frame data to PPM (Portable Pixmap) format.
	 *
	 * YUYV is a common raw format for webcam/video capture where each pair of pixels
	 * shares chroma (U, V) values. This method converts those values to RGB using
	 * the standard YUV-to-RGB conversion formulas and outputs PPM P6 binary format.
	 *
	 * @param string $yuyvData  Raw YUYV binary data
	 * @param int    $width     Frame width in pixels
	 * @param int    $height    Frame height in pixels
	 *
	 * @return string  PPM P6 formatted image data, or empty string on invalid input
	 */
	public function yuyvToPpm(string $yuyvData, int $width, int $height): string
	{
		// Build the PPM header with magic number, dimensions, and max color value
		$ppmHeader = "P6\n" . $width . " " . $height . "\n255\n";
		$ppmData = "";
		$len = strlen($yuyvData);

		// Validate that the data length matches expected YUYV format (2 bytes per pixel)
		if ($len != $width * $height * 2) {
			error_log("yuyvToPpm: Invalid YUYV data length. Expected " . ($width * $height * 2) . ", got " . $len);
			return "";
		}

		// Process each 4-byte YUYV macro-pixel (produces 2 RGB pixels)
		for ($i = 0; $i < $len; $i += 4) {
			$y1 = ord($yuyvData[$i]);            // Luminance for first pixel
			$u = ord($yuyvData[$i + 1]) - 128;   // Chrominance U (centered at 0)
			$y2 = ord($yuyvData[$i + 2]);         // Luminance for second pixel
			$v = ord($yuyvData[$i + 3]) - 128;    // Chrominance V (centered at 0)

			// Convert first pixel (Y1, U, V) to RGB using simplified BT.601 formula
			$r1 = max(0, min(255, (int) ($y1 + 1.140 * $v)));
			$g1 = max(0, min(255, (int) ($y1 - 0.396 * $u - 0.581 * $v)));
			$b1 = max(0, min(255, (int) ($y1 + 2.029 * $u)));

			// Convert second pixel (Y2, U, V) to RGB
			$r2 = max(0, min(255, (int) ($y2 + 1.140 * $v)));
			$g2 = max(0, min(255, (int) ($y2 - 0.396 * $u - 0.581 * $v)));
			$b2 = max(0, min(255, (int) ($y2 + 2.029 * $u)));

			// Append both pixels as RGB triplets
			$ppmData .= chr($r1) . chr($g1) . chr($b1);
			$ppmData .= chr($r2) . chr($g2) . chr($b2);
		}

		return $ppmHeader . $ppmData;
	}

	/**
	 * Crop an image to a specified rectangle defined by position and size.
	 *
	 * Extracts a rectangular portion of the source image starting at (sourceX, sourceY)
	 * and copies it into a new true-color image of the specified dimensions.
	 * Supports alpha channel preservation.
	 *
	 * @param mixed $imageResource  The source image resource or file path
	 * @param int   $resizeWidth    Width of the cropped output
	 * @param int   $resizeHeight   Height of the cropped output
	 * @param int   $sourceX        X-coordinate of the crop origin (left edge)
	 * @param int   $sourceY        Y-coordinate of the crop origin (top edge)
	 *
	 * @return GdImage|resource  The cropped image resource
	 */
	public function crop(mixed $imageResource, int $resizeWidth, int $resizeHeight, int $sourceX = 0, int $sourceY = 0): mixed
	{
		if (!self::isResource($imageResource)) {
			$imageResource = self::getInstance($imageResource);
		}

		// Create a new canvas at the target crop dimensions
		$trueColorImage = self::createTrueColorImage($resizeWidth, $resizeHeight);
		$this->setAlphaBlendMode($trueColorImage);
		$this->saveAlphaChannel($trueColorImage, false);

		// Resample from the source region to the destination canvas
		$this->resample($trueColorImage, $imageResource, 0, 0, $sourceX, $sourceY, $resizeWidth, $resizeHeight, $resizeWidth - $sourceX, $resizeHeight - $sourceY);

		return $trueColorImage;
	}

	/**
	 * Crop an image from its center point to specified dimensions.
	 *
	 * Calculates the center of the source image and extracts a rectangle
	 * of the given width and height centered on that point. Useful for
	 * creating uniform-sized thumbnails from images of varying dimensions.
	 *
	 * @param mixed $imageResource  The source image resource or file path
	 * @param int   $resizeWidth    Desired crop width
	 * @param int   $resizeHeight   Desired crop height
	 *
	 * @return GdImage|resource  The center-cropped image resource
	 */
	public function centerCrop($imageResource, $resizeWidth, $resizeHeight): mixed
	{
		if (!self::isResource($imageResource)) {
			$imageResource = self::getInstance($imageResource);
		}

		// Get original dimensions
		$sourceWidth = self::getWidth($imageResource);
		$sourceHeight = self::getHeight($imageResource);

		// Calculate center coordinates of the source image
		$centreX = round($sourceWidth / 2);
		$centreY = round($sourceHeight / 2);

		// Calculate half-dimensions for the crop rectangle
		$cropWidthHalf = round($resizeWidth / 2);
		$cropHeightHalf = round($resizeHeight / 2);

		// Determine crop boundaries, clamping to image edges
		$x1 = max(0, $centreX - $cropWidthHalf);
		$y1 = max(0, $centreY - $cropHeightHalf);

		$x2 = min($sourceWidth, $centreX + $cropWidthHalf);
		$y2 = min($sourceHeight, $centreY + $cropHeightHalf);

		// Create the output canvas and preserve alpha
		$trueColorImage = self::createTrueColorImage($resizeWidth, $resizeHeight);
		$this->setAlphaBlendMode($trueColorImage);
		$this->saveAlphaChannel($trueColorImage, false);

		// Resample from the calculated center region
		$this->resample($trueColorImage, $imageResource, 0, 0, (int) $x1, (int) $y1, $resizeWidth, $resizeHeight, $resizeWidth, $resizeHeight);

		return $trueColorImage;
	}

	/**
	 * Save a raw RGB bitmap data buffer to a PNG image file.
	 *
	 * Converts raw BGR byte data (3 bytes per pixel: Blue, Green, Red)
	 * into a GD image and writes it as a PNG file. Useful for processing
	 * raw framebuffer or BMP-style pixel data.
	 *
	 * @param string $data    Raw BGR pixel data (3 bytes per pixel)
	 * @param int    $width   Image width in pixels
	 * @param int    $height  Image height in pixels
	 * @param string $path    Output file path for the PNG
	 *
	 * @return void
	 * @throws Exception  If the true-color image cannot be created
	 */
	public static function saveFromBitmapData(string $data, int $width, int $height, string $path): void
	{
		// Create a blank true-color canvas
		$image = imagecreatetruecolor($width, $height);
		if ($image === false) {
			throw new Exception("Error creating image.");
		}

		// Iterate over each pixel and set its color from the raw data
		for ($y = 0; $y < $height; $y++) {
			for ($x = 0; $x < $width; $x++) {
				$offset = ($y * $width + $x) * 3;
				$b = ord($data[$offset]);       // Blue channel
				$g = ord($data[$offset + 1]);   // Green channel
				$r = ord($data[$offset + 2]);   // Red channel
				$color = imagecolorallocate($image, $r, $g, $b);
				imagesetpixel($image, $x, $y, $color);
			}
		}

		// Write the image as PNG
		imagepng($image, $path);

		// Destroy the image resource (only needed for PHP < 8.5 where GC doesn't auto-free)
		if (OperationSystem::comparePHPVersion('8.5.0', '<')) {
			// @phpstan-ignore-next-line
			imagedestroy($image);
		}
	}

	/**
	 * Resample (copy and resize) a rectangular portion from a source image to a destination image.
	 *
	 * Uses bicubic interpolation (imagecopyresampled) for high-quality resizing.
	 * This is the core primitive used by crop, resize, and other transformation methods.
	 *
	 * @param GdImage|resource $destinationImage   Destination image resource
	 * @param GdImage|resource $imageResource      Source image resource
	 * @param int              $destinationX       X-coordinate in the destination
	 * @param int              $destinationY       Y-coordinate in the destination
	 * @param int              $sourceX            X-coordinate in the source
	 * @param int              $sourceY            Y-coordinate in the source
	 * @param int              $destinationWidth   Width of the destination rectangle
	 * @param int              $destinationHeight  Height of the destination rectangle
	 * @param int              $sourceWidth        Width of the source rectangle
	 * @param int              $sourceHeight       Height of the source rectangle
	 *
	 * @return void
	 */
	public function resample(mixed $destinationImage, mixed $imageResource, int $destinationX = 0, int $destinationY = 0, int $sourceX = 0, int $sourceY = 0, int $destinationWidth = 0, int $destinationHeight = 0, int $sourceWidth = 0, int $sourceHeight = 0): void
	{
		imagecopyresampled($destinationImage, $imageResource, $destinationX, $destinationY, $sourceX, $sourceY, $destinationWidth, $destinationHeight, $sourceWidth, $sourceHeight);
	}

	/**
	 * Enable or disable the alpha channel save flag for an image resource.
	 *
	 * When enabled, the full alpha transparency information is preserved
	 * when outputting PNG images. This must be set before calling imagepng().
	 *
	 * @param GdImage|resource $imageResource  The image resource
	 * @param bool             $saveFlag       True to save alpha channel, false to discard
	 *
	 * @return void
	 */
	public function saveAlphaChannel(mixed $imageResource, bool $saveFlag = false): void
	{
		imagesavealpha($imageResource, $saveFlag);
	}

	/**
	 * Create a new true-color image resource with the specified dimensions.
	 *
	 * Unlike palette-based images, true-color images support 16 million+
	 * colors and are required for high-quality image manipulation.
	 *
	 * @param int $width   Image width in pixels
	 * @param int $height  Image height in pixels
	 *
	 * @return GdImage|resource  The new true-color image resource
	 */
	public static function createTrueColorImage(int $width, int $height): mixed
	{
		return imagecreatetruecolor($width, $height);
	}

	/**
	 * Set the alpha blending mode for drawing operations on an image.
	 *
	 * When enabled (true), drawing operations blend the foreground color
	 * with the existing background using the alpha channel. When disabled,
	 * the foreground color (including its alpha) is written directly.
	 *
	 * @param GdImage|resource $imageResource  The image resource
	 * @param bool             $useBlendMode   True to enable blending, false to disable
	 *
	 * @return bool  True on success, false on failure
	 */
	public function setAlphaBlendMode(mixed $imageResource, bool $useBlendMode = true): bool
	{
		return imagealphablending($imageResource, $useBlendMode);
	}

	/**
	 * Scale an image resource to specific dimensions using the specified interpolation mode.
	 *
	 * If only width is given (height = -1), the height is calculated automatically
	 * to preserve the aspect ratio.
	 *
	 * @param GdImage|resource $imageResource  The image resource to scale
	 * @param int              $width          Target width in pixels
	 * @param int              $height         Target height (-1 to auto-calculate from aspect ratio)
	 * @param int              $mode           Interpolation mode (default: IMG_BILINEAR_FIXED)
	 *
	 * @return GdImage|resource|bool  The scaled image, or false on failure
	 */
	public function setScale(mixed $imageResource, int $width, int $height = -1, int $mode = IMG_BILINEAR_FIXED): mixed
	{
		return imagescale($imageResource, $width, $height, $mode);
	}

	/**
	 * Apply an emboss (raised relief) filter to an image.
	 *
	 * Creates a 3D-like embossed effect by highlighting edges
	 * based on pixel brightness differences.
	 *
	 * @param GdImage|resource $imageResource  The image resource to process
	 *
	 * @return GdImage|resource  The embossed image resource
	 */
	public function embossFilter(mixed $imageResource): mixed
	{
		return $this->filter($imageResource, ImageFilter::EMBOSS);
	}

	/**
	 * Apply a desaturation (grayscale) filter to an image.
	 *
	 * Removes all color information, converting the image to shades of gray.
	 * Each pixel's luminance is calculated from its RGB values.
	 *
	 * @param GdImage|resource $imageResource  The image resource to process
	 *
	 * @return GdImage|resource  The grayscale image resource
	 */
	public function desaturateFilter(mixed $imageResource): mixed
	{
		return $this->filter($imageResource, ImageFilter::GRAYSCALE);
	}

	/**
	 * Apply an edge detection filter to an image.
	 *
	 * Highlights boundaries between different color regions, making edges
	 * appear as bright lines on a dark background.
	 *
	 * @param GdImage|resource $imageResource  The image resource to process
	 *
	 * @return GdImage|resource  The edge-detected image resource
	 */
	public function edgesFilter(mixed $imageResource): mixed
	{
		return $this->filter($imageResource, ImageFilter::EDGEDETECT);
	}

	/**
	 * Apply a mean removal (sketch-like) filter to an image.
	 *
	 * Creates a pencil-sketch effect by removing the mean color value
	 * and enhancing edges in the image.
	 *
	 * @param GdImage|resource $imageResource  The image resource to process
	 *
	 * @return GdImage|resource  The sketch-filtered image resource
	 */
	public function meanRemoveFilter(mixed $imageResource): mixed
	{
		return $this->filter($imageResource, ImageFilter::SKETCH);
	}

	/**
	 * Apply a pixelate (mosaic) filter to an image.
	 *
	 * Replaces blocks of pixels with a single average color, creating
	 * a blocky, pixelated appearance.
	 *
	 * @param GdImage|resource $imageResource  The image resource to process
	 *
	 * @return GdImage|resource  The pixelated image resource
	 */
	public function pixelateFilter(mixed $imageResource): mixed
	{
		return $this->filter($imageResource, ImageFilter::PIXELATE);
	}

	/**
	 * Apply a sepia tone filter to an image.
	 *
	 * First converts the image to grayscale, then applies a warm
	 * brownish-yellow color overlay to simulate an aged photograph look.
	 *
	 * @param GdImage|resource $imageResource  The image resource to process
	 *
	 * @return GdImage|resource  The sepia-toned image resource
	 */
	public function sepiaFilter(mixed $imageResource): mixed
	{
		// First convert to grayscale
		$filter = $this->filter($imageResource, ImageFilter::GRAYSCALE);

		// Then apply warm brown colorize (R:100, G:50, B:0)
		$filter = $this->filter($filter, ImageFilter::COLORIZE, 100, 50, 0);

		return $filter;
	}

	/**
	 * Apply a Gaussian blur filter to an image.
	 *
	 * Softens the image by applying a Gaussian convolution kernel.
	 * Multiple applications increase the blur radius.
	 *
	 * @param GdImage|resource $imageResource  The image resource to process
	 * @param int              $passes         Number of times to apply the blur (more = stronger blur)
	 *
	 * @return GdImage|resource  The blurred image resource
	 */
	public function gaussianBlurFilter(mixed $imageResource, int $passes = 1): mixed
	{
		// Apply the Gaussian blur filter multiple times for stronger effect
		for ($i = 0; $i < $passes; $i++) {
			$imageResource = $this->filter($imageResource, ImageFilter::GAUSSIAN_BLUR);
		}

		return $imageResource;
	}

	/**
	 * Apply a selective blur filter to an image.
	 *
	 * Similar to Gaussian blur but uses a different algorithm that
	 * preserves edges better while smoothing uniform areas.
	 *
	 * @param GdImage|resource $imageResource  The image resource to process
	 *
	 * @return GdImage|resource  The selectively blurred image resource
	 */
	public function selectiveBlurFilter(mixed $imageResource): mixed
	{
		return $this->filter($imageResource, ImageFilter::SELECTIVE_BLUR);
	}

	/**
	 * Apply a brightness adjustment filter to an image.
	 *
	 * Adjusts the overall brightness of the image. Positive values
	 * brighten, negative values darken.
	 *
	 * @param GdImage|resource $imageResource  The image resource to process
	 * @param int              $level          Brightness level (-255 to 255)
	 *
	 * @return GdImage|resource  The brightness-adjusted image resource
	 */
	public function brightnessFilter(mixed $imageResource, int $level): mixed
	{
		return $this->filter($imageResource, ImageFilter::BRIGHTNESS, $level);
	}

	/**
	 * Apply a contrast adjustment filter to an image.
	 *
	 * Adjusts the difference between light and dark areas.
	 * Note: GD uses inverted contrast values — negative values increase contrast,
	 * positive values decrease it.
	 *
	 * @param GdImage|resource $imageResource  The image resource to process
	 * @param int              $level          Contrast level (-100 to 100, negative = more contrast)
	 *
	 * @return GdImage|resource  The contrast-adjusted image resource
	 */
	public function contrastFilter(mixed $imageResource, int $level): mixed
	{
		return $this->filter($imageResource, ImageFilter::CONTRAST, $level);
	}

	/**
	 * Apply a color inversion (negative) filter to an image.
	 *
	 * Reverses all colors: each RGB component value is subtracted from 255,
	 * producing a photographic negative effect.
	 *
	 * @param GdImage|resource $imageResource  The image resource to process
	 *
	 * @return GdImage|resource  The inverted image resource
	 */
	public function negativeFilter(mixed $imageResource): mixed
	{
		return $this->filter($imageResource, ImageFilter::REVERSE);
	}

	/**
	 * Apply a color tint (colorize) filter to an image.
	 *
	 * Adds the specified RGB values to each pixel's color channels,
	 * shifting the overall color balance of the image.
	 *
	 * @param GdImage|resource $imageResource  The image resource to process
	 * @param int              $red            Red tint amount (-255 to 255)
	 * @param int              $green          Green tint amount (-255 to 255)
	 * @param int              $blue           Blue tint amount (-255 to 255)
	 * @param int              $alpha          Alpha value (0=opaque, 127=transparent)
	 *
	 * @return GdImage|resource  The colorized image resource
	 */
	public function colorizeFilter(mixed $imageResource, int $red, int $green, int $blue, int $alpha = 0): mixed
	{
		return $this->filter($imageResource, ImageFilter::COLORIZE, $red, $green, $blue, $alpha);
	}

	/**
	 * Apply a smoothing filter to an image.
	 *
	 * Reduces noise and jagged edges by averaging neighboring pixel values.
	 * Higher smoothness values produce more blurring.
	 *
	 * @param GdImage|resource $imageResource  The image resource to process
	 * @param int              $smoothness     Smoothness level (higher = smoother)
	 *
	 * @return GdImage|resource  The smoothed image resource
	 */
	public function smoothFilter(mixed $imageResource, int $smoothness): mixed
	{
		return $this->filter($imageResource, ImageFilter::SMOOTH, $smoothness);
	}

	/**
	 * Apply a scatter (noise dispersion) filter to an image.
	 *
	 * Randomly displaces pixels within the given range, creating
	 * a scattered/dispersed visual effect. Available since PHP 7.4.
	 *
	 * @param GdImage|resource $imageResource  The image resource to process
	 * @param int              $sub            Minimum scatter distance
	 * @param int              $plus           Maximum scatter distance
	 *
	 * @return GdImage|resource  The scattered image resource
	 */
	public function scatterFilter(mixed $imageResource, int $sub = 0, int $plus = 5): mixed
	{
		return $this->filter($imageResource, ImageFilter::SCATTER, $sub, $plus);
	}

	/**
	 * Apply a sharpening filter to an image using a convolution matrix.
	 *
	 * Uses a 3×3 kernel that enhances edges and fine details by increasing
	 * the contrast between neighboring pixels.
	 *
	 * @param GdImage|resource $imageResource  The image resource to process
	 *
	 * @return GdImage|resource  The sharpened image resource
	 */
	public function sharpenFilter(mixed $imageResource): mixed
	{
		if (!self::isResource($imageResource)) {
			$imageResource = self::getInstance($imageResource);
		}

		// 3×3 sharpening kernel: center pixel emphasized, neighbors subtracted
		$sharpenMatrix = [
			[0, -1, 0],
			[-1, 5, -1],
			[0, -1, 0]
		];

		// Divisor = sum of all matrix values (ensures brightness is preserved)
		$divisor = 1;

		// Offset added to each pixel (0 = no shift)
		$offset = 0;

		// Apply the convolution matrix to the image
		imageconvolution($imageResource, $sharpenMatrix, $divisor, $offset);

		return $imageResource;
	}

	/**
	 * Apply a custom convolution matrix (kernel) to an image.
	 *
	 * Convolution is a mathematical operation that processes each pixel based
	 * on its neighboring pixels and a weight matrix (kernel). Used to implement
	 * blur, sharpen, edge detection, emboss, and other spatial filters.
	 *
	 * @param GdImage|resource $imageResource  The image resource to process
	 * @param array            $matrix         3×3 convolution matrix (array of 3 arrays, each with 3 floats)
	 * @param float            $divisor        Normalization divisor (usually sum of matrix values)
	 * @param float            $offset         Value added to each result pixel
	 *
	 * @return GdImage|resource  The processed image resource
	 */
	public function applyConvolution(mixed $imageResource, array $matrix, float $divisor, float $offset = 0): mixed
	{
		if (!self::isResource($imageResource)) {
			$imageResource = self::getInstance($imageResource);
		}

		imageconvolution($imageResource, $matrix, $divisor, $offset);

		return $imageResource;
	}

	/**
	 * Apply a GD image filter by filter type name with optional arguments.
	 *
	 * Maps the framework's ImageFilter enum constants to PHP's IMG_FILTER_*
	 * constants and applies the filter with any additional parameters.
	 *
	 * @param GdImage|resource $imageResource  The image resource to process
	 * @param string           $type           Filter type from ImageFilter enum
	 * @param array|bool|float|int ...$args    Additional filter-specific arguments
	 *
	 * @return GdImage|resource  The filtered image resource
	 */
	public function filter(mixed $imageResource, string $type, ...$args): mixed
	{
		$filter = 0;
		$type = strtolower($type);

		// Map framework filter constants to GD filter constants
		switch ($type) {
			case ImageFilter::REVERSE:        // Negate (invert) all colors
				$filter = IMG_FILTER_NEGATE;
				break;
			case ImageFilter::GRAYSCALE:      // Convert to grayscale
				$filter = IMG_FILTER_GRAYSCALE;
				break;
			case ImageFilter::BRIGHTNESS:     // Adjust brightness
				$filter = IMG_FILTER_BRIGHTNESS;
				break;
			case ImageFilter::CONTRAST:       // Adjust contrast
				$filter = IMG_FILTER_CONTRAST;
				break;
			case ImageFilter::COLORIZE:       // Apply color tint
				$filter = IMG_FILTER_COLORIZE;
				break;
			case ImageFilter::EDGEDETECT:     // Detect edges
				$filter = IMG_FILTER_EDGEDETECT;
				break;
			case ImageFilter::EMBOSS:         // Emboss (3D relief)
				$filter = IMG_FILTER_EMBOSS;
				break;
			case ImageFilter::GAUSSIAN_BLUR:  // Gaussian blur
				$filter = IMG_FILTER_GAUSSIAN_BLUR;
				break;
			case ImageFilter::SELECTIVE_BLUR: // Edge-preserving blur
				$filter = IMG_FILTER_SELECTIVE_BLUR;
				break;
			case ImageFilter::SKETCH:         // Mean removal (sketch)
				$filter = IMG_FILTER_MEAN_REMOVAL;
				break;
			case ImageFilter::SMOOTH:         // Smoothing
				$filter = IMG_FILTER_SMOOTH;
				break;
			case ImageFilter::PIXELATE:       // Pixelation (mosaic)
				$filter = IMG_FILTER_PIXELATE;
				break;
			case ImageFilter::SCATTER:        // Pixel scatter
				$filter = IMG_FILTER_SCATTER;
				break;
		}

		// Apply the filter with any additional arguments
		imagefilter($imageResource, $filter, $args);

		return $imageResource;
	}

	/**
	 * Output an image directly to the browser with the appropriate content-type header.
	 *
	 * Sets the correct MIME type header for the specified format and outputs
	 * the image binary data to the output buffer (stdout).
	 *
	 * @param GdImage|string|resource $imageResource  The image resource or file path
	 * @param string                  $format         Output format (use MIME::IMAGE_* constants)
	 * @param int                     $quality        Output quality (0-100 for JPEG, 0-9 for PNG)
	 *
	 * @return void
	 */
	public function draw(mixed $imageResource, string $format, int $quality = 1): void
	{
		if (!self::isResource($imageResource)) {
			$imageResource = self::getInstance($imageResource);
		}

		switch ($format) {
			case MIME::IMAGE_JPEG:
				FileHeader::fromMIME('jpeg');
				imagejpeg($imageResource, null, $quality);
				break;
			case MIME::IMAGE_PNG:
				FileHeader::fromMIME('png');
				imagepng($imageResource, null, $quality);
				break;
			case MIME::IMAGE_BMP:
				FileHeader::fromMIME('bmp');
				imagebmp($imageResource, null);
				break;
			case MIME::IMAGE_GIF:
				FileHeader::fromMIME('gif');
				imagegif($imageResource);
				break;
			case MIME::IMAGE_WBMP:
				FileHeader::fromMIME('wbmp');
				imagewbmp($imageResource);
				break;
			case MIME::IMAGE_XBM:
				FileHeader::fromMIME('xbm');
				imagexbm($imageResource, null);
				break;
			case MIME::IMAGE_GD:
				header("Content-Type: image/gd");
				imagegd($imageResource);
				break;
			case MIME::IMAGE_GD2:
				header("Content-Type: image/gd2");
				imagegd($imageResource);
				break;
			default:
				break;
		}
	}

	/**
	 * Pick the RGBA color value at a specific pixel coordinate.
	 *
	 * Returns the alpha, red, green, and blue channel values
	 * for the pixel at position (x, y) in the image.
	 *
	 * @param mixed $imageResource  The image resource or file path
	 * @param int   $x              X-coordinate of the pixel
	 * @param int   $y              Y-coordinate of the pixel
	 *
	 * @return array{alpha: int, r: int, g: int, b: int}  RGBA color components
	 */
	public function pickColor(mixed $imageResource, $x, $y): array
	{
		if (!self::isResource($imageResource)) {
			$imageResource = self::getInstance($imageResource);
		}

		// Get the color index at the specified pixel
		$rgb = imagecolorat($imageResource, $x, $y);

		// Convert the integer color to RGBA components
		return self::intToRgb($rgb);
	}

	/**
	 * Convert a packed integer color value to an RGBA component array.
	 *
	 * GD stores colors as 32-bit integers in the format 0xAARRGGBB.
	 * This method extracts each channel using bit shifting and masking.
	 *
	 * @param int $rgb  Packed integer color value (0xAARRGGBB)
	 *
	 * @return array{alpha: int, r: int, g: int, b: int}  RGBA color components
	 */
	public static function intToRgb(int $rgb): array
	{
		// Extract individual channels via bit shifting
		// Format: 0xAARRGGBB => alpha(8 bits) red(8 bits) green(8 bits) blue(8 bits)
		$alpha = ($rgb >> 24) & 0xFF;
		$r = ($rgb >> 16) & 0xFF;
		$g = ($rgb >> 8) & 0xFF;
		$b = $rgb & 0xFF;

		return [$alpha, $r, $g, $b];
	}

	/**
	 * Draw a text string on an image at the specified position using built-in GD fonts.
	 *
	 * Uses GD's built-in bitmap fonts (sizes 1-5). For TrueType font support,
	 * use drawTrueTypeText() instead.
	 *
	 * @param mixed  $imageResource  The image resource or file path
	 * @param int    $fontSize       Built-in font size (1-5)
	 * @param int    $x              X-coordinate of the text start position
	 * @param int    $y              Y-coordinate of the text start position
	 * @param string $text           The text string to draw
	 * @param int    $red            Red component of text color (0-255)
	 * @param int    $green          Green component of text color (0-255)
	 * @param int    $blue           Blue component of text color (0-255)
	 *
	 * @return mixed  The modified image resource
	 */
	public function drawText(mixed $imageResource, $fontSize, $x, $y, $text, $red, $green, $blue): mixed
	{
		if (!self::isResource($imageResource)) {
			$imageResource = self::getInstance($imageResource);
		}

		// Allocate the text color
		$textcolor = imagecolorallocate($imageResource, $red, $green, $blue);

		// Draw the string using the built-in GD font
		imagestring($imageResource, $fontSize, $x, $y, $text, $textcolor);

		return $imageResource;
	}

	/**
	 * Draw text on an image using a TrueType (.ttf) font file.
	 *
	 * Provides more control over text rendering than drawText(), including
	 * custom font faces, rotation angle, and precise positioning.
	 *
	 * @param mixed  $imageResource  The image resource or file path
	 * @param float  $fontSize       Font size in points
	 * @param float  $angle          Text rotation angle in degrees (0 = horizontal, CCW)
	 * @param int    $x              X-coordinate of the text baseline start
	 * @param int    $y              Y-coordinate of the text baseline start
	 * @param string $text           The text string to render
	 * @param string $fontFile       Path to the TrueType (.ttf) font file
	 * @param int    $red            Red component of text color (0-255)
	 * @param int    $green          Green component of text color (0-255)
	 * @param int    $blue           Blue component of text color (0-255)
	 * @param int    $alpha          Alpha transparency (0=opaque, 127=transparent)
	 *
	 * @return array|false  Bounding box coordinates array on success, false on failure
	 */
	public function drawTrueTypeText(mixed $imageResource, float $fontSize, float $angle, int $x, int $y, string $text, string $fontFile, int $red = 0, int $green = 0, int $blue = 0, int $alpha = 0): array|false
	{
		if (!self::isResource($imageResource)) {
			$imageResource = self::getInstance($imageResource);
		}

		// Allocate the text color with optional alpha channel
		$textColor = imagecolorallocatealpha($imageResource, $red, $green, $blue, $alpha);

		// Render the TrueType text onto the image
		return imagettftext($imageResource, $fontSize, $angle, $x, $y, $textColor, $fontFile, $text);
	}

	/**
	 * Calculate the bounding box of a TrueType text string without rendering it.
	 *
	 * Useful for calculating text dimensions before drawing, e.g., to center
	 * text or check if it fits within a specific area.
	 *
	 * @param float  $fontSize  Font size in points
	 * @param float  $angle     Text rotation angle in degrees
	 * @param string $fontFile  Path to the TrueType (.ttf) font file
	 * @param string $text      The text string to measure
	 *
	 * @return array|false  Array of 8 values (4 corner coordinates) on success, false on failure
	 */
	public function getTrueTypeTextBoundingBox(float $fontSize, float $angle, string $fontFile, string $text): array|false
	{
		return imagettfbbox($fontSize, $angle, $fontFile, $text);
	}

	/**
	 * Detect the MIME type of an image from a file path or resource.
	 *
	 * For GD resources, uses getimagesizefromstring(). For file paths,
	 * reads the file header with getimagesize() to determine the MIME type.
	 *
	 * @param resource|string $filePath  Path to the image file or GD resource
	 *
	 * @return mixed  MIME type string (e.g., "image/jpeg"), or false on failure
	 */
	public static function getType(mixed $filePath): mixed
	{
		$format = "unknown";

		if (self::isResource($filePath)) {
			// For GD resources, determine format from binary data
			$format = getimagesizefromstring($filePath);
		} else {
			// For file paths, read the file header
			$finfo = getimagesize($filePath);
			if ($finfo === false) {
				return false;
			}
			$format = $finfo['mime'];
		}

		return $format;
	}

	/**
	 * Save an image resource to a file in the format determined by the source file's MIME type.
	 *
	 * Automatically detects the correct output format from the source file path
	 * and writes the image using the corresponding GD output function.
	 * Supports JPEG, PNG, GIF, WBMP, XBM, GD, GD2, AVIF, and WebP formats.
	 *
	 * @param string           $format         Path to the original source file (used for format detection)
	 * @param GdImage|resource $imageResource  The image resource to save
	 * @param resource|string|null $outputPath  Destination file path
	 * @param int              $quality         Output quality (0-100 for JPEG, ignored for most others)
	 *
	 * @return bool  True on successful write, false on failure
	 */
	public static function create(string $format, mixed $imageResource, mixed $outputPath, int $quality = 100): bool
	{
		$created = false;

		switch ($format) {
			case MIME::IMAGE_JPEG:
				$created = imagejpeg($imageResource, $outputPath, $quality);
				break;
			case MIME::IMAGE_PNG:
				$created = imagepng($imageResource, $outputPath);
				break;
			case MIME::IMAGE_GIF:
				$created = imagegif($imageResource, $outputPath);
				break;
			case MIME::IMAGE_WBMP:
				$created = imagewbmp($imageResource, $outputPath);
				break;
			case MIME::IMAGE_XBM:
				$created = imagexbm($imageResource, $outputPath);
				break;
			case MIME::IMAGE_GD:
				$created = imagegd($imageResource, $outputPath);
				break;
			case MIME::IMAGE_GD2:
				$created = imagegd2($imageResource, $outputPath);
				break;
			case MIME::IMAGE_AVIF:
				// AVIF support requires PHP 8.1+ and the imageavif() function
				if (OperationSystem::comparePHPVersion('8.1.0', '<=') && function_exists('imageavif')) {
					$created = imageavif($imageResource, $outputPath);
				}
				break;
			case MIME::IMAGE_WEBP:
				$created = imagewebp($imageResource, $outputPath);
				break;
		}

		if (OperationSystem::comparePHPVersion('8.5.0', '<') && $created) {
			// @phpstan-ignore-next-line
			imagedestroy($imageResource);
		}

		return $created;
	}

	/**
	 * Flip an image horizontally, vertically, or both.
	 *
	 * Mirrors the image along the specified axis.
	 *
	 * @param mixed  $imageResource  The image resource or file path
	 * @param string $type           Flip direction (use Orientation::VERTICAL, HORIZONTAL, or BOTH)
	 *
	 * @return resource|GdImage|bool  The flipped image resource
	 */
	public function flip(mixed $imageResource, string $type): mixed
	{
		if (!self::isResource($imageResource)) {
			$imageResource = self::getInstance($imageResource);
		}

		switch ($type) {
			case Orientation::VERTICAL:
				imageflip($imageResource, IMG_FLIP_VERTICAL);
				break;
			case Orientation::HORIZONTAL:
				imageflip($imageResource, IMG_FLIP_HORIZONTAL);
				break;
			case Orientation::BOTH:
				imageflip($imageResource, IMG_FLIP_BOTH);
				break;
		}

		return $imageResource;
	}

	/**
	 * Get the width of an image in pixels.
	 *
	 * Attempts to read the width from EXIF computed data first (more reliable
	 * for rotated images), then falls back to GD's imagesx() function.
	 *
	 * @param mixed $imageResource  The image resource or file path
	 *
	 * @return int  Image width in pixels
	 */
	public static function getWidth(mixed $imageResource): int
	{
		if (!self::isResource($imageResource)) {
			$imageResource = self::getInstance($imageResource);
		}

		// Try to get width from EXIF data for non-GD resources (file paths)
		if (function_exists('exif_read_data') && !self::isGdReturn($imageResource)) {
			$exifData = exif_read_data($imageResource, null, true, false);

			if (isset($exifData['COMPUTED'])) {
				$computed = $exifData['COMPUTED'];
				return $computed['Width'];
			}
		}

		// Fall back to GD's built-in width function
		return imagesx($imageResource);
	}

	/**
	 * Check whether the given value is a GdImage instance (PHP 8.0+).
	 *
	 * In PHP 8.0+, GD functions return GdImage objects instead of resources.
	 * This method checks for that object type along with the PHP version.
	 *
	 * @param mixed $imageResource  The value to check
	 *
	 * @return bool  True if the value is a GdImage instance on PHP 8.0+
	 */
	public static function isGdReturn(mixed $imageResource): bool
	{
		return $imageResource instanceof GdImage && OperationSystem::comparePHPVersion('8.0.0', '>');
	}

	/**
	 * Get the height of an image in pixels.
	 *
	 * Attempts to read the height from EXIF computed data first, then
	 * falls back to GD's imagesy() function.
	 *
	 * @param mixed $imageResource  The image resource or file path
	 *
	 * @return int  Image height in pixels
	 */
	public static function getHeight(mixed $imageResource): int
	{
		if (!self::isResource($imageResource)) {
			$imageResource = self::getInstance($imageResource);
		}

		// Try to get height from EXIF data for non-GD resources
		if (function_exists('exif_read_data') && !self::isGdReturn($imageResource)) {
			$exif = exif_read_data($imageResource, null, true, false);

			if (isset($exif['COMPUTED'])) {
				$computed = $exif['COMPUTED'];
				return $computed['Height'];
			}
		}

		// Fall back to GD's built-in height function
		return imagesy($imageResource);
	}

	/**
	 * Check whether a given value is a valid GD image resource or GdImage instance.
	 *
	 * Supports both legacy PHP resources (< 8.0) and modern GdImage objects (>= 8.0).
	 *
	 * @param resource $imageResource  The value to check
	 *
	 * @return bool  True if the value is a valid image resource or GdImage object
	 */
	public static function isResource(mixed $imageResource): bool
	{
		if (gettype($imageResource) === 'resource' || $imageResource instanceof GdImage) {
			return true;
		}

		return false;
	}

	/**
	 * Rotate an image by a specified number of degrees.
	 *
	 * Rotates counter-clockwise by the given degree value. Uncovered areas
	 * after rotation are filled with the background color (default: black/0).
	 *
	 * @param mixed $imageResource  The image resource or file path
	 * @param int   $degrees        Rotation angle in degrees (CCW)
	 *
	 * @return bool|GdImage|resource  The rotated image, or false on failure
	 */
	public function rotate(mixed $imageResource, $degrees): mixed
	{
		if (!self::isResource($imageResource)) {
			$imageResource = self::getInstance($imageResource);
		}

		// imagerotate rotates counter-clockwise; background color = 0 (black)
		$image = imagerotate($imageResource, $degrees, 0);

		return $image;
	}

	/**
	 * Check whether the GD image extension is loaded in the current PHP installation.
	 *
	 * @return bool  True if the GD extension is available
	 */
	public static function isGdExtensionLoaded(): bool
	{
		return extension_loaded('gd');
	}

	/**
	 * Create a GD image resource from a file path based on its detected MIME type.
	 *
	 * Automatically determines the image format and calls the appropriate
	 * imagecreatefrom*() function. Supports JPEG, BMP, PNG, GIF, and WebP.
	 *
	 * @param mixed $filePath  Path to the image file
	 *
	 * @return resource|GdImage|bool  The GD image resource, or false if unsupported/failed
	 */
	public static function getimageResource(mixed $filePath): mixed
	{
		$format = self::getType($filePath);

		try {
			switch ($format) {
				case MIME::IMAGE_JPEG:
					if (!self::isGdExtensionLoaded() || !function_exists('imagecreatefromjpeg')) {
						throw new FunctionIsNotExistsException("Cannot found function 'imagecreatefromjpeg'");
					}
					return imagecreatefromjpeg($filePath);

				case MIME::IMAGE_BMP:
					if (!function_exists('imagecreatefrombmp')) {
						throw new FunctionIsNotExistsException("Cannot found function 'imagecreatefrombmp'");
					}
					return imagecreatefrombmp($filePath);

				case MIME::IMAGE_PNG:
					if (!self::isGdExtensionLoaded() || !function_exists('imagecreatefrompng')) {
						throw new FunctionIsNotExistsException("Cannot found function 'imagecreatefrompng'");
					}
					return imagecreatefrompng($filePath);

				case MIME::IMAGE_GIF:
					if (!self::isGdExtensionLoaded() || !function_exists('imagecreatefromgif')) {
						throw new FunctionIsNotExistsException("Cannot found function 'imagecreatefromgif'");
					}
					return imagecreatefromgif($filePath);

				case MIME::IMAGE_WEBP:
					if (!self::isGdExtensionLoaded() || !function_exists('imagecreatefromwebp')) {
						throw new FunctionIsNotExistsException("Cannot found function 'imagecreatefromwebp'");
					}
					return imagecreatefromwebp($filePath);
			}
		} catch (Exception $e) {
			// Silently fail — return false below
		}

		return false;
	}

	/**
	 * Create a blank true-color image with a solid background color and transparent fill.
	 *
	 * Creates a new image canvas, fills it with the specified RGB color,
	 * and sets that color as transparent. Useful as a starting point for
	 * compositing operations.
	 *
	 * @param int $width   Image width in pixels
	 * @param int $height  Image height in pixels
	 * @param int $red     Red component of background color (0-255)
	 * @param int $blue    Blue component of background color (0-255)
	 * @param int $green   Green component of background color (0-255)
	 *
	 * @return GdImage|bool  The image resource, or false on failure
	 */
	public function getBlank($width, $height, $red, $blue, $green): GdImage|bool
	{
		// Create a new true-color canvas
		$image = imagecreatetruecolor($width, $height);
		if (!$image) {
			return false;
		}

		// Allocate the background color
		$backgroundColor = imagecolorallocate($image, $red, $green, $blue);
		if (!$backgroundColor) {
			return false;
		}

		// Fill the entire canvas with the background color
		imagefilledrectangle($image, 0, 0, $width, $height, $backgroundColor);

		// Set the background color as the transparent color
		imagecolortransparent($image, $backgroundColor);

		return self::getInstance($image);
	}

	/**
	 * Resize an image to exact target dimensions (ignoring aspect ratio).
	 *
	 * Creates a new true-color canvas at the specified dimensions and
	 * resamples the source image to fill it completely. Does not preserve
	 * aspect ratio — use ratioResize() for that.
	 *
	 * @param mixed $imageResource  The source image resource or file path
	 * @param int   $resizeWidth    Target width in pixels
	 * @param int   $resizeHeight   Target height in pixels
	 *
	 * @return bool|GdImage|resource  The resized image resource, or false on failure
	 */
	public static function resize(mixed $imageResource, int $resizeWidth, int $resizeHeight): mixed
	{
		if (!self::isResource($imageResource)) {
			$imageResource = self::getInstance($imageResource);
		}

		// Create a new canvas at the target dimensions
		$outputImage = self::createTrueColorImage($resizeWidth, $resizeHeight);

		$width = self::getWidth($imageResource);
		$height = self::getHeight($imageResource);

		// Preserve alpha channel during resize
		imageAlphaBlending($outputImage, false);
		imageSaveAlpha($outputImage, false);

		// High-quality resample from source to destination
		imagecopyresampled($outputImage, $imageResource, 0, 0, 0, 0, $resizeWidth, $resizeHeight, $width, $height);

		return $outputImage;
	}

	/**
	 * Merge one image on top of another with a transparency percentage.
	 *
	 * Copies the source image onto the merge target image at position (0, 0)
	 * with the specified transparency level. The transparency controls how
	 * much of the source image shows through.
	 *
	 * @param mixed $sourceCreateObject  The source (overlay) image resource or file path
	 * @param mixed $mergeCreateObject   The destination (background) image resource or file path
	 * @param int   $transparent         Transparency percentage (0 = invisible, 100 = fully opaque)
	 *
	 * @return bool  True on success, false on failure
	 */
	public function merge(mixed $sourceCreateObject, mixed $mergeCreateObject, $transparent): bool
	{
		if (!self::isResource($sourceCreateObject)) {
			$sourceCreateObject = self::getInstance($sourceCreateObject);
		}

		if (!self::isResource($mergeCreateObject)) {
			$mergeCreateObject = self::getInstance($mergeCreateObject);
		}

		$sourceWidth = self::getWidth($sourceCreateObject);
		$sourceHeight = self::getHeight($sourceCreateObject);

		// Copy and merge with transparency
		return imagecopymerge($mergeCreateObject, $sourceCreateObject, 0, 0, 0, 0, $sourceWidth, $sourceHeight, $transparent);
	}

	/**
	 * Get or create a singleton GD image resource from a file path.
	 *
	 * If the input is already a GD resource, returns it directly.
	 * If it's a file path with valid image data, creates and returns a GD resource.
	 * Acts as a unified entry point for all methods that accept both resources and paths.
	 *
	 * @param mixed $filePath  A GD image resource or file path string
	 *
	 * @return GdImage|resource|bool  The GD image resource, or false on failure
	 */
	public static function getInstance(mixed $filePath): mixed
	{
		// If already a GD resource, return as-is
		if (self::isResource($filePath)) {
			return $filePath;
		}

		// If it's a valid image file, create a GD resource from it
		if (is_array(getImageSize($filePath))) {
			return self::getImageResource($filePath);
		}

		// Final check — return false if getimagesize fails
		$finfo = getImageSize($filePath);
		if ($finfo === false) {
			return false;
		}

		return $filePath;
	}

	/**
	 * Convert a hexadecimal color string to an RGB array.
	 *
	 * Parses hex color values (e.g., "0xFF0000" or "#FF0000") and
	 * returns the individual red, green, and blue components.
	 *
	 * @param string $hex  Hexadecimal color string (with 0x or # prefix)
	 *
	 * @return array  Indexed array [red, green, blue] with values 0-255
	 */
	public function hexToRgb($hex): array
	{
		// Strip the "0x" or "#" prefix (2 characters)
		$rgb = substr($hex, 2, strlen($hex) - 1);

		// Extract each 2-character hex pair and convert to decimal
		$r = hexdec(substr($rgb, 0, 2));
		$g = hexdec(substr($rgb, 2, 2));
		$b = hexdec(substr($rgb, 4, 2));

		return [$r, $g, $b];
	}

	/**
	 * Convert individual RGB values to a hexadecimal color string.
	 *
	 * @param int $red    Red component (0-255)
	 * @param int $green  Green component (0-255)
	 * @param int $blue   Blue component (0-255)
	 *
	 * @return string  Hex color string in "#RRGGBB" format
	 */
	public function rgbToHex(int $red, int $green, int $blue): string
	{
		return sprintf("#%02X%02X%02X", $red, $green, $blue);
	}

	/**
	 * Check whether a specific image type is supported by the current GD installation.
	 *
	 * Uses PHP's imagetypes() bitmask to check for format support.
	 *
	 * @param int $type  Image type constant (e.g., IMG_PNG, IMG_JPEG, IMG_GIF, IMG_WEBP)
	 *
	 * @return bool  True if the format is supported
	 */
	public static function isSupportedTypeWithGD(int $type): bool
	{
		return (imagetypes() & $type) === $type;
	}

	/**
	 * Check whether PNG format is supported by the current GD installation.
	 *
	 * @return bool  True if PNG is supported
	 */
	public static function isPNGSupportedWithGD(): bool
	{
		return self::isSupportedTypeWithGD(IMG_PNG);
	}

	/**
	 * Check whether JPEG format is supported by the current GD installation.
	 *
	 * @return bool  True if JPEG is supported
	 */
	public static function isJPEGSupportedWithGD(): bool
	{
		return self::isSupportedTypeWithGD(IMG_JPEG);
	}

	/**
	 * Check whether GIF format is supported by the current GD installation.
	 *
	 * @return bool  True if GIF is supported
	 */
	public static function isGIFSupportedWithGD(): bool
	{
		return self::isSupportedTypeWithGD(IMG_GIF);
	}

	/**
	 * Check whether WebP format is supported by the current GD installation.
	 *
	 * @return bool  True if WebP is supported
	 */
	public static function isWebPSupportedWithGD(): bool
	{
		return self::isSupportedTypeWithGD(IMG_WEBP);
	}

	/**
	 * Check whether BMP format is supported by the current GD installation.
	 *
	 * @return bool  True if BMP is supported
	 */
	public static function isBMPSupportedWithGD(): bool
	{
		return self::isSupportedTypeWithGD(IMG_BMP);
	}

	/**
	 * Check whether AVIF format is supported by the current GD installation.
	 *
	 * AVIF support was introduced in PHP 8.1 with the GD extension.
	 *
	 * @return bool  True if AVIF is supported
	 */
	public static function isAVIFSupportedWithGD(): bool
	{
		// IMG_AVIF constant may not exist in older PHP versions
		if (defined('IMG_AVIF')) {
			return self::isSupportedTypeWithGD(IMG_AVIF);
		}

		return false;
	}

	/**
	 * Find the exact color index for a specific RGB color in the image's palette.
	 *
	 * Returns the index of the color that exactly matches the given RGB values,
	 * or -1 if the color is not found in the palette.
	 *
	 * @param mixed $image  The image resource
	 * @param int   $red    Red component (0-255)
	 * @param int   $green  Green component (0-255)
	 * @param int   $blue   Blue component (0-255)
	 *
	 * @return int  Color index, or -1 if not found
	 */
	public static function extractColor(mixed $image, int $red, int $green, int $blue): int
	{
		return imagecolorexact($image, $red, $green, $blue);
	}

	/**
	 * Find the closest matching color index for the given RGB values in the image's palette.
	 *
	 * Unlike extractColor() which requires an exact match, this method finds the
	 * nearest color using Euclidean distance in RGB color space.
	 *
	 * @param mixed $image  The image resource
	 * @param int   $red    Red component (0-255)
	 * @param int   $green  Green component (0-255)
	 * @param int   $blue   Blue component (0-255)
	 *
	 * @return int  Index of the closest matching color
	 */
	public static function closestColor(mixed $image, int $red, int $green, int $blue): int
	{
		return imagecolorclosest($image, $red, $green, $blue);
	}

	/**
	 * Allocate a color with alpha transparency for an image.
	 *
	 * Creates a new color entry in the image's color table with the specified
	 * RGBA values. The alpha ranges from 0 (fully opaque) to 127 (fully transparent).
	 *
	 * @param GdImage|resource $imageResource  The image resource
	 * @param int              $red            Red component (0-255)
	 * @param int              $green          Green component (0-255)
	 * @param int              $blue           Blue component (0-255)
	 * @param int              $alpha          Alpha value (0=opaque, 127=transparent)
	 *
	 * @return int|false  The color index, or false on failure
	 */
	public static function allocateColorAlpha(mixed $imageResource, int $red, int $green, int $blue, int $alpha = 0): int|false
	{
		return imagecolorallocatealpha($imageResource, $red, $green, $blue, $alpha);
	}

	/**
	 * Allocate a color for an image resource.
	 *
	 * Creates a new color entry in the image's color table with the specified RGB values.
	 *
	 * @param GdImage|resource $imageResource  The image resource
	 * @param int              $red            Red component (0-255)
	 * @param int              $green          Green component (0-255)
	 * @param int              $blue           Blue component (0-255)
	 *
	 * @return int|false  The color index, or false on failure
	 */
	public static function allocateColor(mixed $imageResource, int $red, int $green, int $blue): int|false
	{
		return imagecolorallocate($imageResource, $red, $green, $blue);
	}

	/**
	 * Draw a filled rectangle on an image.
	 *
	 * Fills a rectangular area defined by two corner coordinates
	 * with the specified color.
	 *
	 * @param GdImage|resource $imageResource  The image resource to draw on
	 * @param int              $x1             X-coordinate of the top-left corner
	 * @param int              $y1             Y-coordinate of the top-left corner
	 * @param int              $x2             X-coordinate of the bottom-right corner
	 * @param int              $y2             Y-coordinate of the bottom-right corner
	 * @param int              $red            Red component (0-255)
	 * @param int              $green          Green component (0-255)
	 * @param int              $blue           Blue component (0-255)
	 *
	 * @return bool  True on success, false on failure
	 */
	public function drawFilledRectangle(mixed $imageResource, int $x1, int $y1, int $x2, int $y2, int $red, int $green, int $blue): bool
	{
		if (!self::isResource($imageResource)) {
			$imageResource = self::getInstance($imageResource);
		}

		$color = imagecolorallocate($imageResource, $red, $green, $blue);

		return imagefilledrectangle($imageResource, $x1, $y1, $x2, $y2, $color);
	}

	/**
	 * Draw an outlined (non-filled) rectangle on an image.
	 *
	 * Draws only the border of a rectangular area defined by two corner coordinates.
	 *
	 * @param GdImage|resource $imageResource  The image resource to draw on
	 * @param int              $x1             X-coordinate of the top-left corner
	 * @param int              $y1             Y-coordinate of the top-left corner
	 * @param int              $x2             X-coordinate of the bottom-right corner
	 * @param int              $y2             Y-coordinate of the bottom-right corner
	 * @param int              $red            Red component (0-255)
	 * @param int              $green          Green component (0-255)
	 * @param int              $blue           Blue component (0-255)
	 *
	 * @return bool  True on success, false on failure
	 */
	public function drawRectangle(mixed $imageResource, int $x1, int $y1, int $x2, int $y2, int $red, int $green, int $blue): bool
	{
		if (!self::isResource($imageResource)) {
			$imageResource = self::getInstance($imageResource);
		}

		$color = imagecolorallocate($imageResource, $red, $green, $blue);

		return imagerectangle($imageResource, $x1, $y1, $x2, $y2, $color);
	}

	/**
	 * Draw a straight line between two points on an image.
	 *
	 * @param GdImage|resource $imageResource  The image resource to draw on
	 * @param int              $x1             X-coordinate of the start point
	 * @param int              $y1             Y-coordinate of the start point
	 * @param int              $x2             X-coordinate of the end point
	 * @param int              $y2             Y-coordinate of the end point
	 * @param int              $red            Red component (0-255)
	 * @param int              $green          Green component (0-255)
	 * @param int              $blue           Blue component (0-255)
	 *
	 * @return bool  True on success, false on failure
	 */
	public function drawLine(mixed $imageResource, int $x1, int $y1, int $x2, int $y2, int $red, int $green, int $blue): bool
	{
		if (!self::isResource($imageResource)) {
			$imageResource = self::getInstance($imageResource);
		}

		$color = imagecolorallocate($imageResource, $red, $green, $blue);

		return imageline($imageResource, $x1, $y1, $x2, $y2, $color);
	}

	/**
	 * Draw a dashed line between two points on an image.
	 *
	 * @param GdImage|resource $imageResource  The image resource to draw on
	 * @param int              $x1             X-coordinate of the start point
	 * @param int              $y1             Y-coordinate of the start point
	 * @param int              $x2             X-coordinate of the end point
	 * @param int              $y2             Y-coordinate of the end point
	 * @param int              $red            Red component (0-255)
	 * @param int              $green          Green component (0-255)
	 * @param int              $blue           Blue component (0-255)
	 *
	 * @return bool  True on success, false on failure
	 */
	public function drawDashedLine(mixed $imageResource, int $x1, int $y1, int $x2, int $y2, int $red, int $green, int $blue): bool
	{
		if (!self::isResource($imageResource)) {
			$imageResource = self::getInstance($imageResource);
		}

		$color = imagecolorallocate($imageResource, $red, $green, $blue);

		return imagedashedline($imageResource, $x1, $y1, $x2, $y2, $color);
	}

	/**
	 * Draw a polygon (outlined) on an image.
	 *
	 * @param GdImage|resource $imageResource  The image resource to draw on
	 * @param array            $points         Array of polygon vertices [x1, y1, x2, y2, ...]
	 * @param int              $numPoints      Number of vertices
	 * @param int              $red            Red component (0-255)
	 * @param int              $green          Green component (0-255)
	 * @param int              $blue           Blue component (0-255)
	 *
	 * @return bool  True on success, false on failure
	 */
	public function drawPolygon(mixed $imageResource, array $points, int $numPoints, int $red, int $green, int $blue): bool
	{
		if (!self::isResource($imageResource)) {
			$imageResource = self::getInstance($imageResource);
		}

		$color = imagecolorallocate($imageResource, $red, $green, $blue);

		return imagepolygon($imageResource, $points, $numPoints, $color);
	}

	/**
	 * Draw a filled polygon on an image.
	 *
	 * @param GdImage|resource $imageResource  The image resource to draw on
	 * @param array            $points         Array of polygon vertices [x1, y1, x2, y2, ...]
	 * @param int              $numPoints      Number of vertices
	 * @param int              $red            Red component (0-255)
	 * @param int              $green          Green component (0-255)
	 * @param int              $blue           Blue component (0-255)
	 *
	 * @return bool  True on success, false on failure
	 */
	public function drawFilledPolygon(mixed $imageResource, array $points, int $numPoints, int $red, int $green, int $blue): bool
	{
		if (!self::isResource($imageResource)) {
			$imageResource = self::getInstance($imageResource);
		}

		$color = imagecolorallocate($imageResource, $red, $green, $blue);

		return imagefilledpolygon($imageResource, $points, $numPoints, $color);
	}

	/**
	 * Draw an arc (partial ellipse outline) on an image.
	 *
	 * Draws a portion of an ellipse centered at (cx, cy) from startAngle to endAngle.
	 *
	 * @param GdImage|resource $imageResource  The image resource to draw on
	 * @param int              $cx             X-coordinate of the center
	 * @param int              $cy             Y-coordinate of the center
	 * @param int              $width          Width of the arc's ellipse
	 * @param int              $height         Height of the arc's ellipse
	 * @param int              $startAngle     Start angle in degrees (0 = 3 o'clock, CW)
	 * @param int              $endAngle       End angle in degrees
	 * @param int              $red            Red component (0-255)
	 * @param int              $green          Green component (0-255)
	 * @param int              $blue           Blue component (0-255)
	 *
	 * @return bool  True on success, false on failure
	 */
	public function drawArc(mixed $imageResource, int $cx, int $cy, int $width, int $height, int $startAngle, int $endAngle, int $red, int $green, int $blue): bool
	{
		if (!self::isResource($imageResource)) {
			$imageResource = self::getInstance($imageResource);
		}

		$color = imagecolorallocate($imageResource, $red, $green, $blue);

		return imagearc($imageResource, $cx, $cy, $width, $height, $startAngle, $endAngle, $color);
	}

	/**
	 * Draw a filled arc (pie slice) on an image.
	 *
	 * Draws and fills a portion of an ellipse. The style parameter controls
	 * how the arc edges are connected and filled.
	 *
	 * @param GdImage|resource $imageResource  The image resource to draw on
	 * @param int              $cx             X-coordinate of the center
	 * @param int              $cy             Y-coordinate of the center
	 * @param int              $width          Width of the arc's ellipse
	 * @param int              $height         Height of the arc's ellipse
	 * @param int              $startAngle     Start angle in degrees
	 * @param int              $endAngle       End angle in degrees
	 * @param int              $red            Red component (0-255)
	 * @param int              $green          Green component (0-255)
	 * @param int              $blue           Blue component (0-255)
	 * @param int              $style          Fill style (IMG_ARC_PIE, IMG_ARC_CHORD, IMG_ARC_NOFILL, IMG_ARC_EDGED)
	 *
	 * @return bool  True on success, false on failure
	 */
	public function drawFilledArc(mixed $imageResource, int $cx, int $cy, int $width, int $height, int $startAngle, int $endAngle, int $red, int $green, int $blue, int $style = IMG_ARC_PIE): bool
	{
		if (!self::isResource($imageResource)) {
			$imageResource = self::getInstance($imageResource);
		}

		$color = imagecolorallocate($imageResource, $red, $green, $blue);

		return imagefilledarc($imageResource, $cx, $cy, $width, $height, $startAngle, $endAngle, $color, $style);
	}

	/**
	 * Set a single pixel's color at the specified coordinates.
	 *
	 * @param GdImage|resource $imageResource  The image resource to modify
	 * @param int              $x              X-coordinate of the pixel
	 * @param int              $y              Y-coordinate of the pixel
	 * @param int              $red            Red component (0-255)
	 * @param int              $green          Green component (0-255)
	 * @param int              $blue           Blue component (0-255)
	 *
	 * @return bool  True on success, false on failure
	 */
	public function setPixel(mixed $imageResource, int $x, int $y, int $red, int $green, int $blue): bool
	{
		if (!self::isResource($imageResource)) {
			$imageResource = self::getInstance($imageResource);
		}

		$color = imagecolorallocate($imageResource, $red, $green, $blue);

		return imagesetpixel($imageResource, $x, $y, $color);
	}

	/**
	 * Flood-fill a region of the image starting from the given coordinates.
	 *
	 * Fills the contiguous area of the same color surrounding the starting
	 * pixel with the specified new color.
	 *
	 * @param GdImage|resource $imageResource  The image resource to modify
	 * @param int              $x              X-coordinate of the fill start point
	 * @param int              $y              Y-coordinate of the fill start point
	 * @param int              $red            Red component of the fill color (0-255)
	 * @param int              $green          Green component of the fill color (0-255)
	 * @param int              $blue           Blue component of the fill color (0-255)
	 *
	 * @return bool  True on success, false on failure
	 */
	public function floodFill(mixed $imageResource, int $x, int $y, int $red, int $green, int $blue): bool
	{
		if (!self::isResource($imageResource)) {
			$imageResource = self::getInstance($imageResource);
		}

		$color = imagecolorallocate($imageResource, $red, $green, $blue);

		return imagefill($imageResource, $x, $y, $color);
	}

	/**
	 * Set the line thickness for subsequent drawing operations.
	 *
	 * Affects lines, rectangles, polygons, and other drawing functions.
	 *
	 * @param GdImage|resource $imageResource  The image resource
	 * @param int              $thickness      Line thickness in pixels
	 *
	 * @return bool  True on success, false on failure
	 */
	public function setLineThickness(mixed $imageResource, int $thickness): bool
	{
		return imagesetthickness($imageResource, $thickness);
	}

	/**
	 * Set the line drawing style for dashed/patterned lines.
	 *
	 * Defines a pixel pattern that is repeated when drawing lines with
	 * IMG_COLOR_STYLED. Each element in the style array represents a color
	 * for one pixel in the pattern.
	 *
	 * @param GdImage|resource $imageResource  The image resource
	 * @param array            $style          Array of color indices defining the line pattern
	 *
	 * @return bool  True on success, false on failure
	 */
	public function setLineStyle(mixed $imageResource, array $style): bool
	{
		return imagesetstyle($imageResource, $style);
	}

	/**
	 * Enable or disable interlacing for an image.
	 *
	 * Interlaced images (progressive JPEG, interlaced GIF/PNG) load progressively
	 * in web browsers, showing a low-quality preview before fully loading.
	 *
	 * @param GdImage|resource $imageResource  The image resource
	 * @param bool             $enable         True to enable interlacing, false to disable
	 *
	 * @return bool  Previous interlace setting (0 or 1)
	 */
	public function setInterlace(mixed $imageResource, bool $enable = true): bool
	{
		return imageinterlace($imageResource, $enable);
	}

	/**
	 * Apply gamma correction to an image.
	 *
	 * Adjusts the gamma curve to correct for display differences or create
	 * intentional brightness/contrast effects. The output gamma divided by
	 * the input gamma determines the correction factor.
	 *
	 * @param GdImage|resource $imageResource  The image resource
	 * @param float            $inputGamma     Current (source) gamma value
	 * @param float            $outputGamma    Desired (target) gamma value
	 *
	 * @return bool  True on success, false on failure
	 */
	public function gammaCorrect(mixed $imageResource, float $inputGamma, float $outputGamma): bool
	{
		if (!self::isResource($imageResource)) {
			$imageResource = self::getInstance($imageResource);
		}

		return imagegammacorrect($imageResource, $inputGamma, $outputGamma);
	}

	/**
	 * Get the total number of colors used in a palette-based image.
	 *
	 * For palette images, returns the number of unique colors. For true-color
	 * images, returns 0 (true-color images don't use palettes).
	 *
	 * @param GdImage|resource $imageResource  The image resource
	 *
	 * @return int  Number of colors in the palette
	 */
	public function getColorCount(mixed $imageResource): int
	{
		if (!self::isResource($imageResource)) {
			$imageResource = self::getInstance($imageResource);
		}

		return imagecolorstotal($imageResource);
	}

	/**
	 * Check whether an image resource uses true-color mode (vs palette mode).
	 *
	 * True-color images support 16.7 million colors (24-bit RGB + alpha),
	 * while palette images are limited to 256 colors.
	 *
	 * @param GdImage|resource $imageResource  The image resource
	 *
	 * @return bool  True if the image is true-color, false if palette-based
	 */
	public function isTrueColor(mixed $imageResource): bool
	{
		if (!self::isResource($imageResource)) {
			$imageResource = self::getInstance($imageResource);
		}

		return imageistruecolor($imageResource);
	}

	/**
	 * Convert a true-color image to a palette-based image with color quantization.
	 *
	 * Reduces the number of colors to 256 or fewer, optionally using dithering
	 * to simulate missing colors. Useful for creating GIF or 8-bit PNG images.
	 *
	 * @param GdImage|resource $imageResource  The image resource to convert
	 * @param bool             $dither         True to enable dithering (Floyd-Steinberg)
	 * @param int              $numColors      Maximum number of palette colors (1-256)
	 *
	 * @return bool  True on success, false on failure
	 */
	public function convertToPalette(mixed $imageResource, bool $dither = true, int $numColors = 256): bool
	{
		if (!self::isResource($imageResource)) {
			$imageResource = self::getInstance($imageResource);
		}

		return imagetruecolortopalette($imageResource, $dither, $numColors);
	}

	/**
	 * Copy a rectangular region from one image to another.
	 *
	 * Performs a pixel-for-pixel copy without resampling (no interpolation).
	 * Use resample() for high-quality resizing operations.
	 *
	 * @param GdImage|resource $destinationImage  Destination image resource
	 * @param GdImage|resource $sourceImage       Source image resource
	 * @param int              $destX             X-coordinate in the destination
	 * @param int              $destY             Y-coordinate in the destination
	 * @param int              $srcX              X-coordinate in the source
	 * @param int              $srcY              Y-coordinate in the source
	 * @param int              $srcWidth          Width of the region to copy
	 * @param int              $srcHeight         Height of the region to copy
	 *
	 * @return bool  True on success, false on failure
	 */
	public function copy(mixed $destinationImage, mixed $sourceImage, int $destX, int $destY, int $srcX, int $srcY, int $srcWidth, int $srcHeight): bool
	{
		return imagecopy($destinationImage, $sourceImage, $destX, $destY, $srcX, $srcY, $srcWidth, $srcHeight);
	}

	/**
	 * Add a text watermark to an image using a TrueType font.
	 *
	 * Renders semi-transparent text at the specified position as a watermark overlay.
	 * The text color and alpha can be customized.
	 *
	 * @param GdImage|resource $imageResource  The image resource to watermark
	 * @param string           $text           Watermark text string
	 * @param string           $fontFile       Path to the TrueType (.ttf) font file
	 * @param float            $fontSize       Font size in points
	 * @param int              $x              X-coordinate of the watermark baseline
	 * @param int              $y              Y-coordinate of the watermark baseline
	 * @param int              $red            Red component of text color (0-255)
	 * @param int              $green          Green component of text color (0-255)
	 * @param int              $blue           Blue component of text color (0-255)
	 * @param int              $alpha          Alpha transparency (0=opaque, 127=transparent)
	 * @param float            $angle          Text rotation angle in degrees (default: 0)
	 *
	 * @return GdImage|resource  The watermarked image resource
	 */
	public function addTextWatermark(mixed $imageResource, string $text, string $fontFile, float $fontSize, int $x, int $y, int $red = 128, int $green = 128, int $blue = 128, int $alpha = 60, float $angle = 0): mixed
	{
		if (!self::isResource($imageResource)) {
			$imageResource = self::getInstance($imageResource);
		}

		// Allocate watermark color with transparency
		$watermarkColor = imagecolorallocatealpha($imageResource, $red, $green, $blue, $alpha);

		// Render the TrueType watermark text
		imagettftext($imageResource, $fontSize, $angle, $x, $y, $watermarkColor, $fontFile, $text);

		return $imageResource;
	}

	/**
	 * Add an image watermark (logo/stamp) overlay onto a base image.
	 *
	 * Places a watermark image on top of the base image at the specified
	 * position with the given opacity level. Useful for copyright watermarks,
	 * brand logos, or stamp overlays.
	 *
	 * @param GdImage|resource $imageResource      The base image resource
	 * @param GdImage|resource $watermarkResource  The watermark image resource
	 * @param int              $x                  X-coordinate for the watermark placement
	 * @param int              $y                  Y-coordinate for the watermark placement
	 * @param int              $opacity            Opacity percentage (0=invisible, 100=fully opaque)
	 *
	 * @return GdImage|resource  The watermarked image resource
	 */
	public function addImageWatermark(mixed $imageResource, mixed $watermarkResource, int $x = 0, int $y = 0, int $opacity = 50): mixed
	{
		if (!self::isResource($imageResource)) {
			$imageResource = self::getInstance($imageResource);
		}

		if (!self::isResource($watermarkResource)) {
			$watermarkResource = self::getInstance($watermarkResource);
		}

		$watermarkWidth = imagesx($watermarkResource);
		$watermarkHeight = imagesy($watermarkResource);

		// Merge the watermark onto the base image with the specified opacity
		imagecopymerge($imageResource, $watermarkResource, $x, $y, 0, 0, $watermarkWidth, $watermarkHeight, $opacity);

		return $imageResource;
	}

	/**
	 * Generate a thumbnail image with a maximum dimension constraint.
	 *
	 * Creates a proportionally scaled-down version of the image where
	 * the longest side matches the specified maximum dimension. Aspect
	 * ratio is always preserved.
	 *
	 * @param mixed $imageResource  The source image resource or file path
	 * @param int   $maxDimension   Maximum width or height in pixels
	 *
	 * @return GdImage|resource|false  The thumbnail image resource, or false on failure
	 */
	public function createThumbnail(mixed $imageResource, int $maxDimension = 150): mixed
	{
		if (!self::isResource($imageResource)) {
			$imageResource = self::getInstance($imageResource);
		}

		if (!$imageResource) {
			return false;
		}

		$originalWidth = self::getWidth($imageResource);
		$originalHeight = self::getHeight($imageResource);

		// Calculate new dimensions while preserving aspect ratio
		if ($originalWidth > $originalHeight) {
			// Landscape: constrain by width
			$newWidth = $maxDimension;
			$newHeight = (int) round($originalHeight * ($maxDimension / $originalWidth));
		} else {
			// Portrait or square: constrain by height
			$newHeight = $maxDimension;
			$newWidth = (int) round($originalWidth * ($maxDimension / $originalHeight));
		}

		// Create the resized thumbnail
		return self::resize($imageResource, $newWidth, $newHeight);
	}

	/**
	 * Encode a GD image resource to a base64 data URI string.
	 *
	 * Captures the image output to a string buffer and encodes it as base64,
	 * suitable for embedding directly in HTML <img> src attributes or CSS.
	 *
	 * @param GdImage|resource $imageResource  The image resource to encode
	 * @param string           $format         Output format: 'png', 'jpeg', 'gif', or 'webp'
	 * @param int              $quality        Output quality (0-100 for JPEG/WebP, 0-9 for PNG)
	 *
	 * @return string|false  Base64 data URI string (e.g., "data:image/png;base64,..."), or false on failure
	 */
	public function toBase64(mixed $imageResource, string $format = 'png', int $quality = 90): string|false
	{
		if (!self::isResource($imageResource)) {
			$imageResource = self::getInstance($imageResource);
		}

		if (!$imageResource) {
			return false;
		}

		// Capture output to a buffer instead of writing to a file
		ob_start();

		switch (strtolower($format)) {
			case 'jpeg':
			case 'jpg':
				imagejpeg($imageResource, null, $quality);
				$mimeType = 'image/jpeg';
				break;
			case 'gif':
				imagegif($imageResource);
				$mimeType = 'image/gif';
				break;
			case 'webp':
				imagewebp($imageResource, null, $quality);
				$mimeType = 'image/webp';
				break;
			case 'png':
			default:
				imagepng($imageResource, null, min(9, (int) ($quality / 10)));
				$mimeType = 'image/png';
				break;
		}

		$imageData = ob_get_clean();

		if ($imageData === false || strlen($imageData) === 0) {
			return false;
		}

		// Encode as base64 data URI
		return 'data:' . $mimeType . ';base64,' . base64_encode($imageData);
	}

	/**
	 * Create a GD image resource from a base64-encoded image string.
	 *
	 * Accepts both raw base64 data and full data URI strings
	 * (e.g., "data:image/png;base64,...").
	 *
	 * @param string $base64String  Base64-encoded image data (with or without data URI prefix)
	 *
	 * @return GdImage|resource|false  The GD image resource, or false on failure
	 */
	public function fromBase64(string $base64String): mixed
	{
		// Strip data URI prefix if present
		if (str_contains($base64String, ',')) {
			$base64String = explode(',', $base64String, 2)[1];
		}

		// Decode the base64 data to binary
		$imageData = base64_decode($base64String, true);

		if ($imageData === false) {
			return false;
		}

		// Create a GD image from the binary data
		return imagecreatefromstring($imageData);
	}

	/**
	 * Get comprehensive information about an image file.
	 *
	 * Returns an associative array containing the image dimensions, MIME type,
	 * file size, number of channels (RGB/CMYK), bits per channel, and aspect ratio.
	 *
	 * @param string $filePath  Path to the image file
	 *
	 * @return array|false  Image information array, or false on failure
	 */
	public function getImageInfo(string $filePath): array|false
	{
		$info = getimagesize($filePath);

		if ($info === false) {
			return false;
		}

		$result = [
			'width' => $info[0],
			'height' => $info[1],
			'type' => $info[2],                  // IMAGETYPE_* constant
			'mime' => $info['mime'],
			'channels' => $info['channels'] ?? 3, // 3=RGB, 4=CMYK
			'bits' => $info['bits'] ?? 8,          // Bits per channel
			'aspect_ratio' => $info[0] / max(1, $info[1]),
		];

		// Add file size if the file exists and is readable
		if (is_readable($filePath)) {
			$result['filesize'] = filesize($filePath);
		}

		return $result;
	}

	/**
	 * Get information about the current GD library installation.
	 *
	 * Returns version, supported formats, FreeType support,
	 * and other GD configuration details.
	 *
	 * @return array|false  GD info array, or false if GD is not loaded
	 */
	public static function getGdInfo(): array|false
	{
		if (!self::isGdExtensionLoaded()) {
			return false;
		}

		return gd_info();
	}

	/**
	 * Destroy (free memory for) a GD image resource.
	 *
	 * In PHP 8.0+, GdImage objects are garbage collected automatically,
	 * but this method provides explicit cleanup for earlier versions
	 * or when immediate memory reclamation is needed.
	 *
	 * @param GdImage|resource $imageResource  The image resource to destroy
	 *
	 * @return bool  True on success, false on failure
	 */
	public static function destroy(mixed $imageResource): bool
	{
		if (!self::isResource($imageResource)) {
			return false;
		}

		// imagedestroy() is deprecated in PHP 8.0+ (GC handles it)
		if (OperationSystem::comparePHPVersion('8.0.0', '<')) {
			// @phpstan-ignore-next-line
			return imagedestroy($imageResource);
		}

		// For PHP 8.0+, unset triggers GC; return true for API compatibility
		return true;
	}

	/**
	 * Set the transparent color for a palette-based image.
	 *
	 * Defines which color index should be rendered as fully transparent.
	 * Only applicable to palette-based (non-true-color) images.
	 *
	 * @param GdImage|resource $imageResource  The image resource
	 * @param int|null         $color          Color index to make transparent, or null to remove transparency
	 *
	 * @return int  The previous transparent color index, or -1 if none was set
	 */
	public function setTransparentColor(mixed $imageResource, ?int $color = null): int
	{
		if ($color === null) {
			return imagecolortransparent($imageResource);
		}

		return imagecolortransparent($imageResource, $color);
	}

	/**
	 * Get the total number of unique colors in an image resource.
	 *
	 * Iterates through every pixel to count distinct colors. This can be
	 * slow for large images but provides an accurate count for true-color images.
	 *
	 * @param GdImage|resource $imageResource  The image resource to analyze
	 *
	 * @return int  Number of unique colors found
	 */
	public function countUniqueColors(mixed $imageResource): int
	{
		if (!self::isResource($imageResource)) {
			$imageResource = self::getInstance($imageResource);
		}

		$width = self::getWidth($imageResource);
		$height = self::getHeight($imageResource);
		$colors = [];

		// Scan every pixel and record unique color values
		for ($y = 0; $y < $height; $y++) {
			for ($x = 0; $x < $width; $x++) {
				$colorIndex = imagecolorat($imageResource, $x, $y);
				$colors[$colorIndex] = true;
			}
		}

		return count($colors);
	}

	/**
	 * Calculate the average color of an entire image.
	 *
	 * Downscales the image to a single pixel using GD's interpolation to
	 * efficiently compute the average color. This is much faster than
	 * iterating over every pixel manually.
	 *
	 * @param GdImage|resource $imageResource  The image resource to analyze
	 *
	 * @return array{r: int, g: int, b: int}  Average RGB color values
	 */
	public function getAverageColor(mixed $imageResource): array
	{
		if (!self::isResource($imageResource)) {
			$imageResource = self::getInstance($imageResource);
		}

		// Scale down to a single pixel to get the average color
		$singlePixel = imagescale($imageResource, 1, 1);
		$rgb = imagecolorat($singlePixel, 0, 0);

		$r = ($rgb >> 16) & 0xFF;
		$g = ($rgb >> 8) & 0xFF;
		$b = $rgb & 0xFF;

		return ['r' => $r, 'g' => $g, 'b' => $b];
	}

	/**
	 * Extract the dominant colors from an image.
	 *
	 * Downscales the image to a small size and extracts the most frequently
	 * occurring colors. Useful for generating color palettes or themes.
	 *
	 * @param GdImage|resource $imageResource  The image resource to analyze
	 * @param int              $numColors      Number of dominant colors to extract
	 * @param int              $sampleSize     Size to downscale to for sampling (default: 50px)
	 *
	 * @return array  Array of dominant colors, each as ['r' => int, 'g' => int, 'b' => int, 'count' => int]
	 */
	public function getDominantColors(mixed $imageResource, int $numColors = 5, int $sampleSize = 50): array
	{
		if (!self::isResource($imageResource)) {
			$imageResource = self::getInstance($imageResource);
		}

		// Downscale the image for faster processing
		$width = self::getWidth($imageResource);
		$height = self::getHeight($imageResource);
		$ratio = $width / max(1, $height);

		$sampleWidth = $sampleSize;
		$sampleHeight = (int) round($sampleSize / $ratio);

		$sample = imagescale($imageResource, $sampleWidth, $sampleHeight);

		// Count color frequency, quantizing to reduce noise (round to nearest 8)
		$colorCounts = [];
		for ($y = 0; $y < $sampleHeight; $y++) {
			for ($x = 0; $x < $sampleWidth; $x++) {
				$rgb = imagecolorat($sample, $x, $y);
				$r = (($rgb >> 16) & 0xFF) >> 3 << 3; // Quantize to nearest 8
				$g = (($rgb >> 8) & 0xFF) >> 3 << 3;
				$b = ($rgb & 0xFF) >> 3 << 3;
				$key = "{$r},{$g},{$b}";
				$colorCounts[$key] = ($colorCounts[$key] ?? 0) + 1;
			}
		}

		// Sort by frequency (most common first)
		arsort($colorCounts);

		// Return the top N colors
		$result = [];
		$i = 0;
		foreach ($colorCounts as $key => $count) {
			if ($i >= $numColors)
				break;
			list($r, $g, $b) = explode(',', $key);
			$result[] = ['r' => (int) $r, 'g' => (int) $g, 'b' => (int) $b, 'count' => $count];
			$i++;
		}

		return $result;
	}

	/**
	 * Create a mirrored (reflected) copy of an image.
	 *
	 * Generates a new image that is double the height of the original,
	 * with the original image on top and a vertically flipped, faded
	 * reflection below it. Commonly used for product image effects.
	 *
	 * @param GdImage|resource $imageResource   The source image resource
	 * @param int              $reflectionHeight Height of the reflection area (default: half the original)
	 * @param int              $startOpacity     Starting opacity of the reflection (0-127, default: 80)
	 *
	 * @return GdImage|resource  The image with reflection effect
	 */
	public function createReflection(mixed $imageResource, int $reflectionHeight = 0, int $startOpacity = 80): mixed
	{
		if (!self::isResource($imageResource)) {
			$imageResource = self::getInstance($imageResource);
		}

		$width = self::getWidth($imageResource);
		$height = self::getHeight($imageResource);

		// Default reflection height is half the original image
		if ($reflectionHeight <= 0) {
			$reflectionHeight = (int) ($height / 2);
		}

		// Create a canvas for the original + reflection
		$totalHeight = $height + $reflectionHeight;
		$output = imagecreatetruecolor($width, $totalHeight);
		imagealphablending($output, true);
		imagesavealpha($output, true);

		// Fill background with transparent white
		$transparent = imagecolorallocatealpha($output, 255, 255, 255, 127);
		imagefill($output, 0, 0, $transparent);

		// Copy original image to the top
		imagecopy($output, $imageResource, 0, 0, 0, 0, $width, $height);

		// Create the reflection (vertically flipped, progressively fading)
		for ($y = 0; $y < $reflectionHeight; $y++) {
			// Source row from the bottom of the original image
			$sourceY = $height - 1 - $y;

			// Calculate progressive opacity (fade from startOpacity to fully transparent)
			$opacity = (int) ($startOpacity + (127 - $startOpacity) * ($y / $reflectionHeight));

			// Copy one row at a time, applying transparency
			imagecopymerge($output, $imageResource, 0, $height + $y, 0, $sourceY, $width, 1, (int) (100 * (127 - $opacity) / 127));
		}

		return $output;
	}

	/**
	 * Add a border/frame around an image.
	 *
	 * Creates a new image with the specified border width added on all sides,
	 * filled with the given color, and the original image centered within.
	 *
	 * @param GdImage|resource $imageResource  The source image resource
	 * @param int              $borderWidth    Border width in pixels on each side
	 * @param int              $red            Red component of border color (0-255)
	 * @param int              $green          Green component of border color (0-255)
	 * @param int              $blue           Blue component of border color (0-255)
	 *
	 * @return GdImage|resource  The bordered image resource
	 */
	public function addBorder(mixed $imageResource, int $borderWidth, int $red = 0, int $green = 0, int $blue = 0): mixed
	{
		if (!self::isResource($imageResource)) {
			$imageResource = self::getInstance($imageResource);
		}

		$origWidth = self::getWidth($imageResource);
		$origHeight = self::getHeight($imageResource);

		// New dimensions including border on all sides
		$newWidth = $origWidth + ($borderWidth * 2);
		$newHeight = $origHeight + ($borderWidth * 2);

		// Create the bordered canvas
		$output = imagecreatetruecolor($newWidth, $newHeight);
		$borderColor = imagecolorallocate($output, $red, $green, $blue);
		imagefilledrectangle($output, 0, 0, $newWidth - 1, $newHeight - 1, $borderColor);

		// Center the original image within the border
		imagecopy($output, $imageResource, $borderWidth, $borderWidth, 0, 0, $origWidth, $origHeight);

		return $output;
	}

	/**
	 * Create rounded corner mask on an image.
	 *
	 * Applies rounded corners by making the corner regions transparent.
	 * Works best with PNG output format to preserve transparency.
	 *
	 * @param GdImage|resource $imageResource  The source image resource
	 * @param int              $radius         Corner radius in pixels
	 *
	 * @return GdImage|resource  The image with rounded corners
	 */
	public function roundCorners(mixed $imageResource, int $radius): mixed
	{
		if (!self::isResource($imageResource)) {
			$imageResource = self::getInstance($imageResource);
		}

		$width = self::getWidth($imageResource);
		$height = self::getHeight($imageResource);

		// Create a mask image for the rounded corners
		$mask = imagecreatetruecolor($width, $height);
		$transparent = imagecolorallocate($mask, 255, 0, 255); // Magenta as transparent key
		$black = imagecolorallocate($mask, 0, 0, 0);

		// Fill mask with transparent color
		imagefilledrectangle($mask, 0, 0, $width - 1, $height - 1, $transparent);
		imagecolortransparent($mask, $transparent);

		// Draw filled rounded rectangle (center + corners)
		imagefilledrectangle($mask, $radius, 0, $width - $radius - 1, $height - 1, $black);
		imagefilledrectangle($mask, 0, $radius, $width - 1, $height - $radius - 1, $black);

		// Draw corner circles
		imagefilledellipse($mask, $radius, $radius, $radius * 2, $radius * 2, $black);
		imagefilledellipse($mask, $width - $radius - 1, $radius, $radius * 2, $radius * 2, $black);
		imagefilledellipse($mask, $radius, $height - $radius - 1, $radius * 2, $radius * 2, $black);
		imagefilledellipse($mask, $width - $radius - 1, $height - $radius - 1, $radius * 2, $radius * 2, $black);

		// Apply the mask: make pixels outside the mask transparent
		$output = imagecreatetruecolor($width, $height);
		imagealphablending($output, false);
		imagesavealpha($output, true);
		$transparentColor = imagecolorallocatealpha($output, 0, 0, 0, 127);
		imagefill($output, 0, 0, $transparentColor);

		for ($y = 0; $y < $height; $y++) {
			for ($x = 0; $x < $width; $x++) {
				$maskColor = imagecolorat($mask, $x, $y);
				if ($maskColor !== $transparent) {
					$srcColor = imagecolorat($imageResource, $x, $y);
					imagesetpixel($output, $x, $y, $srcColor);
				}
			}
		}

		// Clean up the mask
		if (OperationSystem::comparePHPVersion('8.0.0', '<')) {
			// @phpstan-ignore-next-line
			imagedestroy($mask);
		}

		return $output;
	}

	/**
	 * Convert an image from one format to another and save to a file.
	 *
	 * Reads the source image regardless of its format and writes it
	 * in the specified target format.
	 *
	 * @param string $sourcePath   Path to the source image file
	 * @param string $outputPath   Path for the converted output file
	 * @param string $targetFormat Target MIME type (use MIME::IMAGE_* constants)
	 * @param int    $quality      Output quality (0-100 for JPEG/WebP)
	 *
	 * @return bool  True on success, false on failure
	 */
	public function convert(string $sourcePath, string $outputPath, string $targetFormat, int $quality = 90): bool
	{
		// Load the source image regardless of its format
		$imageResource = self::getimageResource($sourcePath);

		if (!$imageResource) {
			return false;
		}

		// Write in the target format
		switch ($targetFormat) {
			case MIME::IMAGE_JPEG:
				return imagejpeg($imageResource, $outputPath, $quality);
			case MIME::IMAGE_PNG:
				return imagepng($imageResource, $outputPath, min(9, (int) ($quality / 10)));
			case MIME::IMAGE_GIF:
				return imagegif($imageResource, $outputPath);
			case MIME::IMAGE_WEBP:
				return imagewebp($imageResource, $outputPath, $quality);
			case MIME::IMAGE_BMP:
				return imagebmp($imageResource, $outputPath);
			case MIME::IMAGE_AVIF:
				if (function_exists('imageavif')) {
					return imageavif($imageResource, $outputPath, $quality);
				}
				return false;
			default:
				return false;
		}
	}

	/**
	 * Get the raw binary output of an image as a string.
	 *
	 * Captures the image output in a buffer and returns it as a string,
	 * useful for streaming, caching, or further processing without writing to disk.
	 *
	 * @param GdImage|resource $imageResource  The image resource
	 * @param string           $format         Output format: 'png', 'jpeg', 'gif', or 'webp'
	 * @param int              $quality        Output quality
	 *
	 * @return string|false  Raw image binary data, or false on failure
	 */
	public function toBuffer(mixed $imageResource, string $format = 'png', int $quality = 90): string|false
	{
		if (!self::isResource($imageResource)) {
			$imageResource = self::getInstance($imageResource);
		}

		if (!$imageResource) {
			return false;
		}

		ob_start();

		switch (strtolower($format)) {
			case 'jpeg':
			case 'jpg':
				imagejpeg($imageResource, null, $quality);
				break;
			case 'gif':
				imagegif($imageResource);
				break;
			case 'webp':
				imagewebp($imageResource, null, $quality);
				break;
			case 'png':
			default:
				imagepng($imageResource, null, min(9, (int) ($quality / 10)));
				break;
		}

		$data = ob_get_clean();

		return ($data !== false && strlen($data) > 0) ? $data : false;
	}

	/**
	 * Create a grayscale copy of an image (non-destructive).
	 *
	 * Returns a new image resource that is a grayscale version of the original.
	 * The original image resource is not modified.
	 *
	 * @param GdImage|resource $imageResource  The source image resource
	 *
	 * @return GdImage|resource  A new grayscale image resource
	 */
	public function toGrayscale(mixed $imageResource): mixed
	{
		if (!self::isResource($imageResource)) {
			$imageResource = self::getInstance($imageResource);
		}

		// Create a copy to avoid modifying the original
		$width = self::getWidth($imageResource);
		$height = self::getHeight($imageResource);
		$copy = self::createTrueColorImage($width, $height);
		imagecopy($copy, $imageResource, 0, 0, 0, 0, $width, $height);

		// Apply the grayscale filter to the copy
		imagefilter($copy, IMG_FILTER_GRAYSCALE);

		return $copy;
	}

	public static function getRgbFromPosition(mixed $image, int $x, int $y)
	{
		$rgb = imagecolorat($image, $x, $y);

		if ($rgb <= 255) {
			$pixel = imagecolorsforindex($image, $rgb);
			$r = $pixel['red'];
			$g = $pixel['green'];
			$b = $pixel['blue'];
		} else {
			$r = ($rgb >> 16) & 0xFF;
			$g = ($rgb >> 8) & 0xFF;
			$b = $rgb & 0xFF;
		}

		return ['r' => $r, 'g' => $g, 'b' => $b];
	}

	/**
	 * Adjust the opacity (alpha channel) of an entire image.
	 *
	 * Applies a uniform transparency level across all pixels in the image.
	 * Useful for creating semi-transparent overlays.
	 *
	 * @param GdImage|resource $imageResource  The image resource
	 * @param int              $opacity        Opacity percentage (0=transparent, 100=opaque)
	 *
	 * @return GdImage|resource  The opacity-adjusted image resource
	 */
	public function setOpacity(mixed $imageResource, int $opacity): mixed
	{
		if (!self::isResource($imageResource)) {
			$imageResource = self::getInstance($imageResource);
		}

		$width = self::getWidth($imageResource);
		$height = self::getHeight($imageResource);

		// Create a new image with alpha support
		$output = imagecreatetruecolor($width, $height);
		imagealphablending($output, false);
		imagesavealpha($output, true);

		// Fill with fully transparent background
		$transparent = imagecolorallocatealpha($output, 0, 0, 0, 127);
		imagefill($output, 0, 0, $transparent);

		// Copy each pixel with adjusted alpha
		$alphaFactor = 127 - (int) (127 * $opacity / 100);

		for ($y = 0; $y < $height; $y++) {
			for ($x = 0; $x < $width; $x++) {
				$rgb = imagecolorat($imageResource, $x, $y);
				$a = ($rgb >> 24) & 0x7F;
				$r = ($rgb >> 16) & 0xFF;
				$g = ($rgb >> 8) & 0xFF;
				$b = $rgb & 0xFF;

				// Combine existing alpha with the requested opacity
				$newAlpha = min(127, $a + $alphaFactor);
				$newColor = imagecolorallocatealpha($output, $r, $g, $b, $newAlpha);
				imagesetpixel($output, $x, $y, $newColor);
			}
		}

		return $output;
	}

	/**
	 * Create a color palette-based image with the specified dimensions.
	 *
	 * Palette images are limited to 256 colors but use less memory.
	 * Use createTrueColorImage() for full-color support.
	 *
	 * @param int $width   Image width in pixels
	 * @param int $height  Image height in pixels
	 *
	 * @return GdImage|resource  The new palette-based image resource
	 */
	public static function createPaletteImage(int $width, int $height): mixed
	{
		return imagecreate($width, $height);
	}

	/**
	 * Get the RGBA values for a specific color index in a palette image.
	 *
	 * @param GdImage|resource $imageResource  The image resource
	 * @param int              $colorIndex     The color index to query
	 *
	 * @return array{red: int, green: int, blue: int, alpha: int}  RGBA component values
	 */
	public function getColorComponents(mixed $imageResource, int $colorIndex): array
	{
		return imagecolorsforindex($imageResource, $colorIndex);
	}

	/**
	 * Set the anti-aliasing mode for drawing operations.
	 *
	 * When enabled, lines and shape edges are rendered with anti-aliasing
	 * for smoother appearance. Note: does not work with alpha colors.
	 *
	 * @param GdImage|resource $imageResource  The image resource
	 * @param bool             $enabled        True to enable anti-aliasing
	 *
	 * @return bool  True on success, false on failure
	 */
	public function setAntiAlias(mixed $imageResource, bool $enabled = true): bool
	{
		return imageantialias($imageResource, $enabled);
	}

	/**
	 * Set the clipping rectangle for drawing operations.
	 *
	 * All subsequent drawing operations will be confined within the
	 * specified rectangular area.
	 *
	 * @param GdImage|resource $imageResource  The image resource
	 * @param int              $x1             X-coordinate of the top-left corner
	 * @param int              $y1             Y-coordinate of the top-left corner
	 * @param int              $x2             X-coordinate of the bottom-right corner
	 * @param int              $y2             Y-coordinate of the bottom-right corner
	 *
	 * @return bool  True on success, false on failure
	 */
	public function setClipRect(mixed $imageResource, int $x1, int $y1, int $x2, int $y2): bool
	{
		return imagesetclip($imageResource, $x1, $y1, $x2, $y2);
	}

	/**
	 * Get the current clipping rectangle for an image.
	 *
	 * @param GdImage|resource $imageResource  The image resource
	 *
	 * @return array{x1: int, y1: int, x2: int, y2: int}  Current clipping rectangle coordinates
	 */
	public function getClipRect(mixed $imageResource): array
	{
		return imagegetclip($imageResource);
	}

	/**
	 * Set the interpolation method used for image transformations.
	 *
	 * Controls the quality of scaling, rotation, and other transformations.
	 * Higher quality methods are slower but produce better results.
	 *
	 * @param GdImage|resource $imageResource  The image resource
	 * @param int              $method         Interpolation method (IMG_BILINEAR_FIXED, IMG_BICUBIC, etc.)
	 *
	 * @return bool  True on success, false on failure
	 */
	public function setInterpolation(mixed $imageResource, int $method = IMG_BILINEAR_FIXED): bool
	{
		return imagesetinterpolation($imageResource, $method);
	}

	/**
	 * Get the image resolution (DPI) for an image resource.
	 *
	 * @param GdImage|resource $imageResource  The image resource
	 *
	 * @return array{0: int, 1: int}  Array with horizontal and vertical DPI values
	 */
	public function getResolution(mixed $imageResource): array
	{
		return imageresolution($imageResource);
	}

	/**
	 * Set the image resolution (DPI) for an image resource.
	 *
	 * @param GdImage|resource $imageResource   The image resource
	 * @param int              $horizontalDpi   Horizontal DPI value
	 * @param int              $verticalDpi     Vertical DPI value (default: same as horizontal)
	 *
	 * @return bool  True on success, false on failure
	 */
	public function setResolution(mixed $imageResource, int $horizontalDpi, int $verticalDpi = 0): bool
	{
		if ($verticalDpi <= 0) {
			$verticalDpi = $horizontalDpi;
		}

		return imageresolution($imageResource, $horizontalDpi, $verticalDpi);
	}

	/**
	 * Draw an outlined (non-filled) ellipse on an image resource.
	 *
	 * Unlike drawEclipse() which is always filled, this method draws only
	 * the border of the ellipse without any fill.
	 *
	 * @param GdImage|resource $imageResource  The image resource to draw on
	 * @param int              $cx             X-coordinate of the ellipse center
	 * @param int              $cy             Y-coordinate of the ellipse center
	 * @param int              $width          Width of the ellipse
	 * @param int              $height         Height of the ellipse
	 * @param int              $red            Red component (0-255)
	 * @param int              $green          Green component (0-255)
	 * @param int              $blue           Blue component (0-255)
	 *
	 * @return bool  True on success, false on failure
	 */
	public function drawNonFilledEllipse(mixed $imageResource, int $cx, int $cy, int $width, int $height, int $red, int $green, int $blue): bool
	{
		if (!self::isResource($imageResource)) {
			$imageResource = self::getInstance($imageResource);
		}

		$color = imagecolorallocate($imageResource, $red, $green, $blue);

		return imageellipse($imageResource, $cx, $cy, $width, $height, $color);
	}

	/**
	 * Draw a filled circle on an image resource.
	 *
	 * Convenience wrapper around drawEclipse() that accepts a single diameter
	 * value instead of separate width/height.
	 *
	 * @param GdImage|resource $imageResource  The image resource to draw on
	 * @param int              $cx             X-coordinate of the center
	 * @param int              $cy             Y-coordinate of the center
	 * @param int              $diameter       Circle diameter in pixels
	 * @param int              $red            Red component (0-255)
	 * @param int              $green          Green component (0-255)
	 * @param int              $blue           Blue component (0-255)
	 *
	 * @return bool  True on success, false on failure
	 */
	public function drawFilledCircle(mixed $imageResource, int $cx, int $cy, int $diameter, int $red, int $green, int $blue): bool
	{
		if (!self::isResource($imageResource)) {
			$imageResource = self::getInstance($imageResource);
		}

		$color = imagecolorallocate($imageResource, $red, $green, $blue);

		return imagefilledellipse($imageResource, $cx, $cy, $diameter, $diameter, $color);
	}

	/**
	 * Draw an outlined circle on an image resource.
	 *
	 * Convenience wrapper around drawEllipse() that accepts a single diameter
	 * value instead of separate width/height.
	 *
	 * @param GdImage|resource $imageResource  The image resource to draw on
	 * @param int              $cx             X-coordinate of the center
	 * @param int              $cy             Y-coordinate of the center
	 * @param int              $diameter       Circle diameter in pixels
	 * @param int              $red            Red component (0-255)
	 * @param int              $green          Green component (0-255)
	 * @param int              $blue           Blue component (0-255)
	 *
	 * @return bool  True on success, false on failure
	 */
	public function drawCircle(mixed $imageResource, int $cx, int $cy, int $diameter, int $red, int $green, int $blue): bool
	{
		if (!self::isResource($imageResource)) {
			$imageResource = self::getInstance($imageResource);
		}

		$color = imagecolorallocate($imageResource, $red, $green, $blue);

		return imageellipse($imageResource, $cx, $cy, $diameter, $diameter, $color);
	}

	/**
	 * Draw a filled rounded rectangle on an image.
	 *
	 * Fills the interior of a rectangle with circular arc corners of the
	 * specified radius.
	 *
	 * @param GdImage|resource $imageResource  The image resource to draw on
	 * @param int              $x1             X-coordinate of the top-left corner
	 * @param int              $y1             Y-coordinate of the top-left corner
	 * @param int              $x2             X-coordinate of the bottom-right corner
	 * @param int              $y2             Y-coordinate of the bottom-right corner
	 * @param int              $radius         Corner radius in pixels
	 * @param int              $red            Red component (0-255)
	 * @param int              $green          Green component (0-255)
	 * @param int              $blue           Blue component (0-255)
	 *
	 * @return GdImage|resource  The modified image resource
	 */
	public function drawFilledRoundedRectangle(mixed $imageResource, int $x1, int $y1, int $x2, int $y2, int $radius, int $red, int $green, int $blue): mixed
	{
		if (!self::isResource($imageResource)) {
			$imageResource = self::getInstance($imageResource);
		}

		$color = imagecolorallocate($imageResource, $red, $green, $blue);

		// Fill the center cross-shape
		imagefilledrectangle($imageResource, $x1 + $radius, $y1, $x2 - $radius, $y2, $color);
		imagefilledrectangle($imageResource, $x1, $y1 + $radius, $x2, $y2 - $radius, $color);

		// Fill the four rounded corners
		imagefilledellipse($imageResource, $x1 + $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
		imagefilledellipse($imageResource, $x2 - $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
		imagefilledellipse($imageResource, $x1 + $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
		imagefilledellipse($imageResource, $x2 - $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);

		return $imageResource;
	}

	/**
	 * Draw an arrow between two points on an image.
	 *
	 * Draws a straight line with a V-shaped arrowhead at the endpoint.
	 *
	 * @param GdImage|resource $imageResource  The image resource to draw on
	 * @param int              $x1             X-coordinate of the tail (start)
	 * @param int              $y1             Y-coordinate of the tail (start)
	 * @param int              $x2             X-coordinate of the head (end)
	 * @param int              $y2             Y-coordinate of the head (end)
	 * @param int              $arrowSize      Length of the arrowhead lines in pixels
	 * @param int              $red            Red component (0-255)
	 * @param int              $green          Green component (0-255)
	 * @param int              $blue           Blue component (0-255)
	 *
	 * @return GdImage|resource  The modified image resource
	 */
	public function drawArrow(mixed $imageResource, int $x1, int $y1, int $x2, int $y2, int $arrowSize = 10, int $red = 0, int $green = 0, int $blue = 0): mixed
	{
		if (!self::isResource($imageResource)) {
			$imageResource = self::getInstance($imageResource);
		}

		$color = imagecolorallocate($imageResource, $red, $green, $blue);

		// Draw the shaft
		imageline($imageResource, $x1, $y1, $x2, $y2, $color);

		// Calculate angle of the line
		$angle = atan2($y2 - $y1, $x2 - $x1);
		$arrowAngle = deg2rad(30);

		// Arrowhead left and right wings
		$ax1 = (int) round($x2 - $arrowSize * cos($angle - $arrowAngle));
		$ay1 = (int) round($y2 - $arrowSize * sin($angle - $arrowAngle));
		$ax2 = (int) round($x2 - $arrowSize * cos($angle + $arrowAngle));
		$ay2 = (int) round($y2 - $arrowSize * sin($angle + $arrowAngle));

		imageline($imageResource, $x2, $y2, $ax1, $ay1, $color);
		imageline($imageResource, $x2, $y2, $ax2, $ay2, $color);

		return $imageResource;
	}

	/**
	 * Draw a regular grid overlay on an image.
	 *
	 * Draws evenly spaced horizontal and vertical lines across the canvas.
	 * Supports semi-transparent grid lines via the alpha parameter.
	 *
	 * @param GdImage|resource $imageResource  The image resource to draw on
	 * @param int              $cellWidth      Column width in pixels
	 * @param int              $cellHeight     Row height in pixels
	 * @param int              $red            Line color red component (0-255)
	 * @param int              $green          Line color green component (0-255)
	 * @param int              $blue           Line color blue component (0-255)
	 * @param int              $alpha          Line alpha (0=opaque, 127=transparent)
	 *
	 * @return GdImage|resource  The modified image resource
	 */
	public function drawGrid(mixed $imageResource, int $cellWidth = 50, int $cellHeight = 50, int $red = 200, int $green = 200, int $blue = 200, int $alpha = 0): mixed
	{
		if (!self::isResource($imageResource)) {
			$imageResource = self::getInstance($imageResource);
		}

		$width = self::getWidth($imageResource);
		$height = self::getHeight($imageResource);
		$color = imagecolorallocatealpha($imageResource, $red, $green, $blue, $alpha);

		// Vertical lines
		for ($x = 0; $x < $width; $x += $cellWidth) {
			imageline($imageResource, $x, 0, $x, $height - 1, $color);
		}

		// Horizontal lines
		for ($y = 0; $y < $height; $y += $cellHeight) {
			imageline($imageResource, 0, $y, $width - 1, $y, $color);
		}

		return $imageResource;
	}

	/**
	 * Get the RGBA color components of a specific pixel.
	 *
	 * @param GdImage|resource $imageResource  The image resource
	 * @param int              $x              X-coordinate of the pixel
	 * @param int              $y              Y-coordinate of the pixel
	 *
	 * @return array{r: int, g: int, b: int, a: int}  RGBA component values (alpha 0=opaque, 127=transparent)
	 */
	public function getPixelColor(mixed $imageResource, int $x, int $y): array
	{
		if (!self::isResource($imageResource)) {
			$imageResource = self::getInstance($imageResource);
		}

		$rgb = imagecolorat($imageResource, $x, $y);

		return [
			'r' => ($rgb >> 16) & 0xFF,
			'g' => ($rgb >> 8) & 0xFF,
			'b' => $rgb & 0xFF,
			'a' => ($rgb >> 24) & 0x7F,
		];
	}

	/**
	 * Apply a vignette effect to an image.
	 *
	 * Applies a radial gradient darkening from the center outward, drawing
	 * the viewer's eye to the middle of the image. The effect blends each
	 * edge pixel toward the specified tint color.
	 *
	 * @param GdImage|resource $imageResource  The image resource to process
	 * @param float            $strength       Vignette strength (0.0 = none, 1.0 = full black)
	 * @param int              $red            Vignette tint red (default 0 = black)
	 * @param int              $green          Vignette tint green
	 * @param int              $blue           Vignette tint blue
	 *
	 * @return GdImage|resource  The vignette-applied image resource
	 */
	public function applyVignette(mixed $imageResource, float $strength = 0.5, int $red = 0, int $green = 0, int $blue = 0): mixed
	{
		if (!self::isResource($imageResource)) {
			$imageResource = self::getInstance($imageResource);
		}

		$width = self::getWidth($imageResource);
		$height = self::getHeight($imageResource);
		$centerX = $width / 2.0;
		$centerY = $height / 2.0;
		$maxDist = sqrt($centerX ** 2 + $centerY ** 2);

		$output = imagecreatetruecolor($width, $height);

		for ($y = 0; $y < $height; $y++) {
			for ($x = 0; $x < $width; $x++) {
				$dist = sqrt(($x - $centerX) ** 2 + ($y - $centerY) ** 2);
				$factor = min(1.0, ($dist / $maxDist) * $strength);

				$srcRgb = imagecolorat($imageResource, $x, $y);
				$r = (int) ((($srcRgb >> 16) & 0xFF) * (1 - $factor) + $red * $factor);
				$g = (int) ((($srcRgb >> 8) & 0xFF) * (1 - $factor) + $green * $factor);
				$b = (int) (($srcRgb & 0xFF) * (1 - $factor) + $blue * $factor);

				$newColor = imagecolorallocate($output, $r, $g, $b);
				imagesetpixel($output, $x, $y, $newColor);
			}
		}

		return $output;
	}

	/**
	 * Add random noise (film grain) to an image.
	 *
	 * Applies pixel-level random color variation to simulate the look of
	 * analog film grain or sensor noise. The same random offset is applied
	 * to all three channels for monochromatic grain.
	 *
	 * @param GdImage|resource $imageResource  The image resource to process
	 * @param int              $intensity      Maximum noise offset per channel (0-255)
	 *
	 * @return GdImage|resource  The noise-added image resource
	 */
	public function addNoise(mixed $imageResource, int $intensity = 30): mixed
	{
		if (!self::isResource($imageResource)) {
			$imageResource = self::getInstance($imageResource);
		}

		$width = self::getWidth($imageResource);
		$height = self::getHeight($imageResource);

		for ($y = 0; $y < $height; $y++) {
			for ($x = 0; $x < $width; $x++) {
				$rgb = imagecolorat($imageResource, $x, $y);
				$noise = random_int(-$intensity, $intensity);

				$r = max(0, min(255, (($rgb >> 16) & 0xFF) + $noise));
				$g = max(0, min(255, (($rgb >> 8) & 0xFF) + $noise));
				$b = max(0, min(255, ($rgb & 0xFF) + $noise));

				$newColor = imagecolorallocate($imageResource, $r, $g, $b);
				imagesetpixel($imageResource, $x, $y, $newColor);
			}
		}

		return $imageResource;
	}

	/**
	 * Apply a posterize effect to reduce the number of distinct color levels.
	 *
	 * Quantizes each color channel to a fixed number of levels, producing
	 * a flat, graphic-poster appearance.
	 *
	 * @param GdImage|resource $imageResource  The image resource to process
	 * @param int              $levels         Color levels per channel (2-255; fewer = stronger effect)
	 *
	 * @return GdImage|resource  The posterized image resource
	 */
	public function posterizeFilter(mixed $imageResource, int $levels = 4): mixed
	{
		if (!self::isResource($imageResource)) {
			$imageResource = self::getInstance($imageResource);
		}

		$levels = max(2, min(255, $levels));
		$step = 255.0 / ($levels - 1);

		$width = self::getWidth($imageResource);
		$height = self::getHeight($imageResource);

		for ($y = 0; $y < $height; $y++) {
			for ($x = 0; $x < $width; $x++) {
				$rgb = imagecolorat($imageResource, $x, $y);

				$r = min(255, (int) round((($rgb >> 16) & 0xFF) / $step) * (int) $step);
				$g = min(255, (int) round((($rgb >> 8) & 0xFF) / $step) * (int) $step);
				$b = min(255, (int) round(($rgb & 0xFF) / $step) * (int) $step);

				$newColor = imagecolorallocate($imageResource, $r, $g, $b);
				imagesetpixel($imageResource, $x, $y, $newColor);
			}
		}

		return $imageResource;
	}

	/**
	 * Apply a duotone effect to an image.
	 *
	 * First desaturates the image to grayscale, then remaps each luminance
	 * value to a blend between a shadow color and a highlight color, producing
	 * a stylized two-color tonal image.
	 *
	 * @param GdImage|resource $imageResource  The image resource to process
	 * @param int              $shadowR        Shadow tone red component
	 * @param int              $shadowG        Shadow tone green component
	 * @param int              $shadowB        Shadow tone blue component
	 * @param int              $highlightR     Highlight tone red component
	 * @param int              $highlightG     Highlight tone green component
	 * @param int              $highlightB     Highlight tone blue component
	 *
	 * @return GdImage|resource  The duotone image resource
	 */
	public function duotoneFilter(mixed $imageResource, int $shadowR = 0, int $shadowG = 0, int $shadowB = 128, int $highlightR = 255, int $highlightG = 200, int $highlightB = 100): mixed
	{
		if (!self::isResource($imageResource)) {
			$imageResource = self::getInstance($imageResource);
		}

		// Convert to grayscale first
		imagefilter($imageResource, IMG_FILTER_GRAYSCALE);

		$width = self::getWidth($imageResource);
		$height = self::getHeight($imageResource);

		// Remap each luminance value to the duotone gradient
		for ($y = 0; $y < $height; $y++) {
			for ($x = 0; $x < $width; $x++) {
				$rgb = imagecolorat($imageResource, $x, $y);
				$lum = ($rgb >> 16) & 0xFF; // R = G = B after grayscale
				$t = $lum / 255.0;

				$r = (int) ($shadowR + ($highlightR - $shadowR) * $t);
				$g = (int) ($shadowG + ($highlightG - $shadowG) * $t);
				$b = (int) ($shadowB + ($highlightB - $shadowB) * $t);

				$newColor = imagecolorallocate($imageResource, $r, $g, $b);
				imagesetpixel($imageResource, $x, $y, $newColor);
			}
		}

		return $imageResource;
	}

	/**
	 * Apply a vintage film look to an image.
	 *
	 * Combines sepia toning, reduced contrast, a slight brightness lift,
	 * subtle film grain, and a soft vignette to simulate aged analog photography.
	 *
	 * @param GdImage|resource $imageResource  The image resource to process
	 *
	 * @return GdImage|resource  The vintage-processed image resource
	 */
	public function vintageFilter(mixed $imageResource): mixed
	{
		if (!self::isResource($imageResource)) {
			$imageResource = self::getInstance($imageResource);
		}

		// Sepia base tone
		$imageResource = $this->sepiaFilter($imageResource);

		// Slightly reduce contrast for a faded look
		imagefilter($imageResource, IMG_FILTER_CONTRAST, 15);

		// Lift shadows for a washed-out appearance
		imagefilter($imageResource, IMG_FILTER_BRIGHTNESS, 10);

		// Subtle film grain
		$imageResource = $this->addNoise($imageResource, 12);

		// Soft dark vignette
		$imageResource = $this->applyVignette($imageResource, 0.4, 40, 20, 0);

		return $imageResource;
	}

	/**
	 * Add a drop shadow beneath an image on a transparent canvas.
	 *
	 * Creates a larger canvas, renders a Gaussian-blurred shadow at the
	 * specified offset, then composites the original image over it.
	 *
	 * @param GdImage|resource $imageResource  The source image resource
	 * @param int              $offsetX        Horizontal shadow offset in pixels
	 * @param int              $offsetY        Vertical shadow offset in pixels
	 * @param int              $blurPasses     Number of Gaussian blur passes on the shadow layer
	 * @param int              $red            Shadow color red component
	 * @param int              $green          Shadow color green component
	 * @param int              $blue           Shadow color blue component
	 * @param int              $alpha          Shadow alpha (0=opaque, 127=fully transparent)
	 * @param int              $padding        Extra canvas padding added on all sides
	 *
	 * @return GdImage|resource  The image resource with drop shadow on expanded canvas
	 */
	public function addDropShadow(mixed $imageResource, int $offsetX = 5, int $offsetY = 5, int $blurPasses = 3, int $red = 0, int $green = 0, int $blue = 0, int $alpha = 80, int $padding = 15): mixed
	{
		if (!self::isResource($imageResource)) {
			$imageResource = self::getInstance($imageResource);
		}

		$origWidth = self::getWidth($imageResource);
		$origHeight = self::getHeight($imageResource);
		$canvasWidth = $origWidth + $padding * 2 + abs($offsetX);
		$canvasHeight = $origHeight + $padding * 2 + abs($offsetY);

		// Transparent canvas
		$canvas = imagecreatetruecolor($canvasWidth, $canvasHeight);
		imagealphablending($canvas, false);
		imagesavealpha($canvas, true);
		$transparent = imagecolorallocatealpha($canvas, 255, 255, 255, 127);
		imagefill($canvas, 0, 0, $transparent);

		// Shadow layer
		$shadow = imagecreatetruecolor($canvasWidth, $canvasHeight);
		imagealphablending($shadow, false);
		imagesavealpha($shadow, true);
		imagefill($shadow, 0, 0, $transparent);

		// Stamp shadow silhouette
		imagecopy($shadow, $imageResource, $padding + $offsetX, $padding + $offsetY, 0, 0, $origWidth, $origHeight);

		// Colorize shadow
		imagealphablending($shadow, true);
		$shadowColor = imagecolorallocatealpha($shadow, $red, $green, $blue, $alpha);
		imagefilledrectangle($shadow, $padding + $offsetX, $padding + $offsetY, $padding + $offsetX + $origWidth - 1, $padding + $offsetY + $origHeight - 1, $shadowColor);

		// Blur the shadow layer
		for ($i = 0; $i < $blurPasses; $i++) {
			imagefilter($shadow, IMG_FILTER_GAUSSIAN_BLUR);
		}

		// Composite shadow, then original image
		imagealphablending($canvas, true);
		imagecopy($canvas, $shadow, 0, 0, 0, 0, $canvasWidth, $canvasHeight);
		imagecopy($canvas, $imageResource, $padding, $padding, 0, 0, $origWidth, $origHeight);

		if (OperationSystem::comparePHPVersion('8.0.0', '<')) {
			// @phpstan-ignore-next-line
			imagedestroy($shadow);
		}

		return $canvas;
	}

	/**
	 * Perform auto-contrast stretching on an image.
	 *
	 * Remaps each color channel's pixel values so the darkest pixel becomes 0
	 * and the brightest becomes 255, maximizing the tonal range of the image.
	 *
	 * @param GdImage|resource $imageResource  The image resource to process
	 *
	 * @return GdImage|resource  The contrast-stretched image resource
	 */
	public function autoContrast(mixed $imageResource): mixed
	{
		if (!self::isResource($imageResource)) {
			$imageResource = self::getInstance($imageResource);
		}

		$width = self::getWidth($imageResource);
		$height = self::getHeight($imageResource);

		$minR = $minG = $minB = 255;
		$maxR = $maxG = $maxB = 0;

		// First pass: find per-channel min and max values
		for ($y = 0; $y < $height; $y++) {
			for ($x = 0; $x < $width; $x++) {
				$rgb = imagecolorat($imageResource, $x, $y);
				$r = ($rgb >> 16) & 0xFF;
				$g = ($rgb >> 8) & 0xFF;
				$b = $rgb & 0xFF;

				$minR = min($minR, $r);
				$maxR = max($maxR, $r);
				$minG = min($minG, $g);
				$maxG = max($maxG, $g);
				$minB = min($minB, $b);
				$maxB = max($maxB, $b);
			}
		}

		$rangeR = max(1, $maxR - $minR);
		$rangeG = max(1, $maxG - $minG);
		$rangeB = max(1, $maxB - $minB);

		// Second pass: stretch each channel to [0, 255]
		for ($y = 0; $y < $height; $y++) {
			for ($x = 0; $x < $width; $x++) {
				$rgb = imagecolorat($imageResource, $x, $y);

				$r = (int) round(((($rgb >> 16) & 0xFF) - $minR) * 255 / $rangeR);
				$g = (int) round(((($rgb >> 8) & 0xFF) - $minG) * 255 / $rangeG);
				$b = (int) round((($rgb & 0xFF) - $minB) * 255 / $rangeB);

				$newColor = imagecolorallocate($imageResource, $r, $g, $b);
				imagesetpixel($imageResource, $x, $y, $newColor);
			}
		}

		return $imageResource;
	}

	/**
	 * Create a new image filled with a linear gradient between two colors.
	 *
	 * Generates a smooth horizontal or vertical color transition from
	 * a start color to an end color.
	 *
	 * @param int    $width      Canvas width in pixels
	 * @param int    $height     Canvas height in pixels
	 * @param int    $startR     Start color red component
	 * @param int    $startG     Start color green component
	 * @param int    $startB     Start color blue component
	 * @param int    $endR       End color red component
	 * @param int    $endG       End color green component
	 * @param int    $endB       End color blue component
	 * @param string $direction  'horizontal' (left→right) or 'vertical' (top→bottom)
	 *
	 * @return GdImage|resource  The gradient image resource
	 */
	public function createLinearGradient(int $width, int $height, int $startR, int $startG, int $startB, int $endR, int $endG, int $endB, string $direction = 'horizontal'): mixed
	{
		$image = imagecreatetruecolor($width, $height);

		if ($direction === 'vertical') {
			for ($y = 0; $y < $height; $y++) {
				$t = $y / max(1, $height - 1);
				$r = (int) ($startR + ($endR - $startR) * $t);
				$g = (int) ($startG + ($endG - $startG) * $t);
				$b = (int) ($startB + ($endB - $startB) * $t);
				$color = imagecolorallocate($image, $r, $g, $b);
				imageline($image, 0, $y, $width - 1, $y, $color);
			}
		} else {
			for ($x = 0; $x < $width; $x++) {
				$t = $x / max(1, $width - 1);
				$r = (int) ($startR + ($endR - $startR) * $t);
				$g = (int) ($startG + ($endG - $startG) * $t);
				$b = (int) ($startB + ($endB - $startB) * $t);
				$color = imagecolorallocate($image, $r, $g, $b);
				imageline($image, $x, 0, $x, $height - 1, $color);
			}
		}

		return $image;
	}

	/**
	 * Overlay a semi-transparent linear gradient on top of an existing image.
	 *
	 * Useful for darkening one edge of a photo, creating title bars,
	 * or blending images into a background.
	 *
	 * @param GdImage|resource $imageResource  The base image resource
	 * @param int              $startR         Gradient start color red
	 * @param int              $startG         Gradient start color green
	 * @param int              $startB         Gradient start color blue
	 * @param int              $startAlpha     Start transparency (0=opaque, 127=fully transparent)
	 * @param int              $endR           Gradient end color red
	 * @param int              $endG           Gradient end color green
	 * @param int              $endB           Gradient end color blue
	 * @param int              $endAlpha       End transparency (0=opaque, 127=fully transparent)
	 * @param string           $direction      'horizontal' or 'vertical'
	 *
	 * @return GdImage|resource  The image resource with gradient overlay applied
	 */
	public function addGradientOverlay(mixed $imageResource, int $startR, int $startG, int $startB, int $startAlpha, int $endR, int $endG, int $endB, int $endAlpha, string $direction = 'vertical'): mixed
	{
		if (!self::isResource($imageResource)) {
			$imageResource = self::getInstance($imageResource);
		}

		$width = self::getWidth($imageResource);
		$height = self::getHeight($imageResource);

		$overlay = imagecreatetruecolor($width, $height);
		imagealphablending($overlay, false);
		imagesavealpha($overlay, true);

		if ($direction === 'vertical') {
			for ($y = 0; $y < $height; $y++) {
				$t = $y / max(1, $height - 1);
				$r = (int) ($startR + ($endR - $startR) * $t);
				$g = (int) ($startG + ($endG - $startG) * $t);
				$b = (int) ($startB + ($endB - $startB) * $t);
				$a = (int) ($startAlpha + ($endAlpha - $startAlpha) * $t);
				$color = imagecolorallocatealpha($overlay, $r, $g, $b, $a);
				imageline($overlay, 0, $y, $width - 1, $y, $color);
			}
		} else {
			for ($x = 0; $x < $width; $x++) {
				$t = $x / max(1, $width - 1);
				$r = (int) ($startR + ($endR - $startR) * $t);
				$g = (int) ($startG + ($endG - $startG) * $t);
				$b = (int) ($startB + ($endB - $startB) * $t);
				$a = (int) ($startAlpha + ($endAlpha - $startAlpha) * $t);
				$color = imagecolorallocatealpha($overlay, $r, $g, $b, $a);
				imageline($overlay, $x, 0, $x, $height - 1, $color);
			}
		}

		// Composite the overlay on the base image
		imagealphablending($imageResource, true);
		imagecopy($imageResource, $overlay, 0, 0, 0, 0, $width, $height);

		if (OperationSystem::comparePHPVersion('8.0.0', '<')) {
			// @phpstan-ignore-next-line
			imagedestroy($overlay);
		}

		return $imageResource;
	}

	/**
	 * Crop the image to exact dimensions, centered on the original.
	 *
	 * Trims equally from all sides. If the requested dimension exceeds the
	 * original, the original dimension is used unchanged.
	 *
	 * @param GdImage|resource $imageResource  The image resource to crop
	 * @param int              $cropWidth      Target width in pixels
	 * @param int              $cropHeight     Target height in pixels
	 *
	 * @return GdImage|resource  The center-cropped image resource
	 */
	public function cropCenter(mixed $imageResource, int $cropWidth, int $cropHeight): mixed
	{
		if (!self::isResource($imageResource)) {
			$imageResource = self::getInstance($imageResource);
		}

		$origWidth = self::getWidth($imageResource);
		$origHeight = self::getHeight($imageResource);

		$cropWidth = min($cropWidth, $origWidth);
		$cropHeight = min($cropHeight, $origHeight);

		$srcX = (int) (($origWidth - $cropWidth) / 2);
		$srcY = (int) (($origHeight - $cropHeight) / 2);

		$output = imagecreatetruecolor($cropWidth, $cropHeight);
		imagealphablending($output, false);
		imagesavealpha($output, true);
		imagecopy($output, $imageResource, 0, 0, $srcX, $srcY, $cropWidth, $cropHeight);

		return $output;
	}

	/**
	 * Crop the image to a square from the center.
	 *
	 * Uses the shorter dimension as the square side. Aspect ratio is not
	 * preserved — the result is always 1:1.
	 *
	 * @param GdImage|resource $imageResource  The image resource to crop
	 *
	 * @return GdImage|resource  The square image resource
	 */
	public function squareCrop(mixed $imageResource): mixed
	{
		if (!self::isResource($imageResource)) {
			$imageResource = self::getInstance($imageResource);
		}

		$size = min(self::getWidth($imageResource), self::getHeight($imageResource));

		return $this->cropCenter($imageResource, $size, $size);
	}

	/**
	 * Crop the image to a specified aspect ratio from the center.
	 *
	 * Calculates the largest rectangle matching the target ratio that fits
	 * within the image, then performs a center crop to that rectangle.
	 *
	 * @param GdImage|resource $imageResource  The image resource to crop
	 * @param float            $aspectWidth    Width component of target ratio (e.g., 16)
	 * @param float            $aspectHeight   Height component of target ratio (e.g., 9)
	 *
	 * @return GdImage|resource  The aspect-ratio-cropped image resource
	 */
	public function cropToAspectRatio(mixed $imageResource, float $aspectWidth, float $aspectHeight): mixed
	{
		if (!self::isResource($imageResource)) {
			$imageResource = self::getInstance($imageResource);
		}

		$origWidth = self::getWidth($imageResource);
		$origHeight = self::getHeight($imageResource);
		$targetRatio = $aspectWidth / $aspectHeight;
		$origRatio = $origWidth / $origHeight;

		if ($origRatio > $targetRatio) {
			// Image is wider than target: trim the sides
			$cropHeight = $origHeight;
			$cropWidth = (int) round($origHeight * $targetRatio);
		} else {
			// Image is taller than target: trim top/bottom
			$cropWidth = $origWidth;
			$cropHeight = (int) round($origWidth / $targetRatio);
		}

		return $this->cropCenter($imageResource, $cropWidth, $cropHeight);
	}

	/**
	 * Scale an image to fit within target dimensions, padding the remainder.
	 *
	 * The source image is resized to fill as much of the canvas as possible
	 * while preserving its aspect ratio. The empty space on the shorter axis
	 * is filled with the specified background color (letterbox / pillarbox).
	 *
	 * @param GdImage|resource $imageResource  The image resource to fit
	 * @param int              $targetWidth    Canvas width in pixels
	 * @param int              $targetHeight   Canvas height in pixels
	 * @param int              $red            Background fill red component (0-255)
	 * @param int              $green          Background fill green component (0-255)
	 * @param int              $blue           Background fill blue component (0-255)
	 *
	 * @return GdImage|resource  The padded image resource
	 */
	public function padToSize(mixed $imageResource, int $targetWidth, int $targetHeight, int $red = 0, int $green = 0, int $blue = 0): mixed
	{
		if (!self::isResource($imageResource)) {
			$imageResource = self::getInstance($imageResource);
		}

		$origWidth = self::getWidth($imageResource);
		$origHeight = self::getHeight($imageResource);

		$scale = min($targetWidth / $origWidth, $targetHeight / $origHeight);
		$scaledWidth = (int) round($origWidth * $scale);
		$scaledHeight = (int) round($origHeight * $scale);

		$output = imagecreatetruecolor($targetWidth, $targetHeight);
		$bgColor = imagecolorallocate($output, $red, $green, $blue);
		imagefilledrectangle($output, 0, 0, $targetWidth - 1, $targetHeight - 1, $bgColor);

		$offsetX = (int) (($targetWidth - $scaledWidth) / 2);
		$offsetY = (int) (($targetHeight - $scaledHeight) / 2);

		imagecopyresampled($output, $imageResource, $offsetX, $offsetY, 0, 0, $scaledWidth, $scaledHeight, $origWidth, $origHeight);

		return $output;
	}

	/**
	 * Check whether the image is in portrait orientation (height > width).
	 *
	 * @param GdImage|resource $imageResource  The image resource to check
	 *
	 * @return bool  True if height > width
	 */
	public function isPortrait(mixed $imageResource): bool
	{
		if (!self::isResource($imageResource)) {
			$imageResource = self::getInstance($imageResource);
		}

		return self::getHeight($imageResource) > self::getWidth($imageResource);
	}

	/**
	 * Check whether the image is in landscape orientation (width > height).
	 *
	 * @param GdImage|resource $imageResource  The image resource to check
	 *
	 * @return bool  True if width > height
	 */
	public function isLandscape(mixed $imageResource): bool
	{
		if (!self::isResource($imageResource)) {
			$imageResource = self::getInstance($imageResource);
		}

		return self::getWidth($imageResource) > self::getHeight($imageResource);
	}

	/**
	 * Check whether the image is square (width equals height).
	 *
	 * @param GdImage|resource $imageResource  The image resource to check
	 *
	 * @return bool  True if width equals height
	 */
	public function isSquare(mixed $imageResource): bool
	{
		if (!self::isResource($imageResource)) {
			$imageResource = self::getInstance($imageResource);
		}

		return self::getWidth($imageResource) === self::getHeight($imageResource);
	}

	/**
	 * Get the image aspect ratio as a simplified human-readable string.
	 *
	 * Divides width and height by their greatest common divisor.
	 * For example, a 1920×1080 image returns "16:9".
	 *
	 * @param GdImage|resource $imageResource  The image resource
	 *
	 * @return string  Simplified ratio string (e.g., "16:9", "4:3", "1:1")
	 */
	public function getAspectRatioString(mixed $imageResource): string
	{
		if (!self::isResource($imageResource)) {
			$imageResource = self::getInstance($imageResource);
		}

		$width = self::getWidth($imageResource);
		$height = self::getHeight($imageResource);

		$gcd = static function (int $a, int $b) use (&$gcd): int {
			return $b === 0 ? $a : $gcd($b, $a % $b);
		};

		$divisor = $gcd($width, $height);

		return ($width / $divisor) . ':' . ($height / $divisor);
	}

	/**
	 * Trim uniform solid-color borders from all sides of an image.
	 *
	 * Samples the top-left corner pixel as the background color and removes
	 * rows/columns from each edge where all pixels fall within the tolerance range.
	 *
	 * @param GdImage|resource $imageResource  The image resource to trim
	 * @param int              $tolerance      Per-channel color match tolerance (0 = exact)
	 *
	 * @return GdImage|resource  The trimmed image resource
	 */
	public function autoTrim(mixed $imageResource, int $tolerance = 10): mixed
	{
		if (!self::isResource($imageResource)) {
			$imageResource = self::getInstance($imageResource);
		}

		$width = self::getWidth($imageResource);
		$height = self::getHeight($imageResource);

		// Sample background from top-left corner
		$bgRgb = imagecolorat($imageResource, 0, 0);
		$bgR = ($bgRgb >> 16) & 0xFF;
		$bgG = ($bgRgb >> 8) & 0xFF;
		$bgB = $bgRgb & 0xFF;

		$matches = function (int $rgb) use ($bgR, $bgG, $bgB, $tolerance): bool {
			return abs(($rgb >> 16 & 0xFF) - $bgR) <= $tolerance
				&& abs(($rgb >> 8 & 0xFF) - $bgG) <= $tolerance
				&& abs(($rgb & 0xFF) - $bgB) <= $tolerance;
		};

		// Locate each boundary
		$top = 0;
		for ($y = 0; $y < $height; $y++) {
			for ($x = 0; $x < $width; $x++) {
				if (!$matches(imagecolorat($imageResource, $x, $y))) {
					$top = $y;
					break 2;
				}
			}
		}

		$bottom = $height - 1;
		for ($y = $height - 1; $y >= 0; $y--) {
			for ($x = 0; $x < $width; $x++) {
				if (!$matches(imagecolorat($imageResource, $x, $y))) {
					$bottom = $y;
					break 2;
				}
			}
		}

		$left = 0;
		for ($x = 0; $x < $width; $x++) {
			for ($y = 0; $y < $height; $y++) {
				if (!$matches(imagecolorat($imageResource, $x, $y))) {
					$left = $x;
					break 2;
				}
			}
		}

		$right = $width - 1;
		for ($x = $width - 1; $x >= 0; $x--) {
			for ($y = 0; $y < $height; $y++) {
				if (!$matches(imagecolorat($imageResource, $x, $y))) {
					$right = $x;
					break 2;
				}
			}
		}

		$cropWidth = $right - $left + 1;
		$cropHeight = $bottom - $top + 1;

		$output = imagecreatetruecolor($cropWidth, $cropHeight);
		imagealphablending($output, false);
		imagesavealpha($output, true);
		imagecopy($output, $imageResource, 0, 0, $left, $top, $cropWidth, $cropHeight);

		return $output;
	}

	/**
	 * Create a checkerboard pattern image.
	 *
	 * Useful as a transparency-preview background or a standalone decorative
	 * element. Alternates two colors in a regular grid.
	 *
	 * @param int $width    Canvas width in pixels
	 * @param int $height   Canvas height in pixels
	 * @param int $cellSize Width and height of each square in pixels
	 * @param int $lightR   Light square red component
	 * @param int $lightG   Light square green component
	 * @param int $lightB   Light square blue component
	 * @param int $darkR    Dark square red component
	 * @param int $darkG    Dark square green component
	 * @param int $darkB    Dark square blue component
	 *
	 * @return GdImage|resource  The checkerboard image resource
	 */
	public function createCheckerboard(int $width, int $height, int $cellSize = 10, int $lightR = 204, int $lightG = 204, int $lightB = 204, int $darkR = 153, int $darkG = 153, int $darkB = 153): mixed
	{
		$image = imagecreatetruecolor($width, $height);
		$light = imagecolorallocate($image, $lightR, $lightG, $lightB);
		$dark = imagecolorallocate($image, $darkR, $darkG, $darkB);

		for ($y = 0; $y < $height; $y += $cellSize) {
			for ($x = 0; $x < $width; $x += $cellSize) {
				$isLight = (intdiv($x, $cellSize) + intdiv($y, $cellSize)) % 2 === 0;
				$color = $isLight ? $light : $dark;
				imagefilledrectangle(
					$image,
					$x,
					$y,
					min($x + $cellSize - 1, $width - 1),
					min($y + $cellSize - 1, $height - 1),
					$color
				);
			}
		}

		return $image;
	}

	/**
	 * Blend two images together with a weighted average per pixel.
	 *
	 * A weight of 0.0 produces the base image unchanged; 1.0 produces
	 * only the blend image; 0.5 is an equal mix of both.
	 * Both images must have the same dimensions.
	 *
	 * @param GdImage|resource $baseImage   The primary image resource
	 * @param GdImage|resource $blendImage  The secondary image resource
	 * @param float            $weight      Blend weight (0.0–1.0)
	 *
	 * @return GdImage|resource  The blended image resource
	 */
	public function blend(mixed $baseImage, mixed $blendImage, float $weight = 0.5): mixed
	{
		if (!self::isResource($baseImage)) {
			$baseImage = self::getInstance($baseImage);
		}

		if (!self::isResource($blendImage)) {
			$blendImage = self::getInstance($blendImage);
		}

		$width = self::getWidth($baseImage);
		$height = self::getHeight($baseImage);
		$blendH = imagesy($blendImage);
		$output = imagecreatetruecolor($width, $height);

		for ($y = 0; $y < $height; $y++) {
			for ($x = 0; $x < $width; $x++) {
				$rgb1 = imagecolorat($baseImage, $x, $y);
				$rgb2 = imagecolorat($blendImage, min($x, imagesx($blendImage) - 1), min($y, $blendH - 1));

				$r = (int) ((($rgb1 >> 16 & 0xFF) * (1 - $weight)) + (($rgb2 >> 16 & 0xFF) * $weight));
				$g = (int) ((($rgb1 >> 8 & 0xFF) * (1 - $weight)) + (($rgb2 >> 8 & 0xFF) * $weight));
				$b = (int) ((($rgb1 & 0xFF) * (1 - $weight)) + (($rgb2 & 0xFF) * $weight));

				$newColor = imagecolorallocate($output, $r, $g, $b);
				imagesetpixel($output, $x, $y, $newColor);
			}
		}

		return $output;
	}

	/**
	 * Calculate the pixel-level absolute difference between two images.
	 *
	 * Produces a new image where each pixel's RGB value equals the absolute
	 * difference of the corresponding pixels in the two source images. Identical
	 * pixels appear black; differing pixels appear bright.
	 *
	 * @param GdImage|resource $imageA  The first image resource
	 * @param GdImage|resource $imageB  The second image resource (should be same dimensions)
	 *
	 * @return GdImage|resource  The difference image resource
	 */
	public function difference(mixed $imageA, mixed $imageB): mixed
	{
		if (!self::isResource($imageA)) {
			$imageA = self::getInstance($imageA);
		}

		if (!self::isResource($imageB)) {
			$imageB = self::getInstance($imageB);
		}

		$width = self::getWidth($imageA);
		$height = self::getHeight($imageA);
		$output = imagecreatetruecolor($width, $height);

		for ($y = 0; $y < $height; $y++) {
			for ($x = 0; $x < $width; $x++) {
				$rgb1 = imagecolorat($imageA, $x, $y);
				$rgb2 = imagecolorat($imageB, $x, $y);

				$r = abs(($rgb1 >> 16 & 0xFF) - ($rgb2 >> 16 & 0xFF));
				$g = abs(($rgb1 >> 8 & 0xFF) - ($rgb2 >> 8 & 0xFF));
				$b = abs(($rgb1 & 0xFF) - ($rgb2 & 0xFF));

				$newColor = imagecolorallocate($output, $r, $g, $b);
				imagesetpixel($output, $x, $y, $newColor);
			}
		}

		return $output;
	}

	/**
	 * Compute a normalized color histogram for an image.
	 *
	 * Returns the frequency distribution of pixel intensities across each
	 * color channel. Each channel array contains 256 float values (indices
	 * 0-255) normalized to the range [0.0, 1.0] where 1.0 means every pixel
	 * has that value.
	 *
	 * @param GdImage|resource $imageResource  The image resource to analyze
	 *
	 * @return array{red: float[], green: float[], blue: float[]}  Normalized per-channel histograms
	 */
	public function getHistogram(mixed $imageResource): array
	{
		if (!self::isResource($imageResource)) {
			$imageResource = self::getInstance($imageResource);
		}

		$width = self::getWidth($imageResource);
		$height = self::getHeight($imageResource);
		$total = max(1, $width * $height);

		$histogram = [
			'red' => array_fill(0, 256, 0),
			'green' => array_fill(0, 256, 0),
			'blue' => array_fill(0, 256, 0),
		];

		for ($y = 0; $y < $height; $y++) {
			for ($x = 0; $x < $width; $x++) {
				$rgb = imagecolorat($imageResource, $x, $y);
				$histogram['red'][($rgb >> 16) & 0xFF]++;
				$histogram['green'][($rgb >> 8) & 0xFF]++;
				$histogram['blue'][$rgb & 0xFF]++;
			}
		}

		// Normalize to [0, 1]
		foreach ($histogram as $channel => $values) {
			$histogram[$channel] = array_map(static fn($v) => $v / $total, $values);
		}

		return $histogram;
	}

	/**
	 * Assemble a grid collage from an array of image resources.
	 *
	 * Places each image into a cell of a fixed-size grid, scaling it to fit
	 * while preserving the aspect ratio. Cells exceeding the image count are
	 * left blank.
	 *
	 * @param array $images      Array of GdImage|resource items
	 * @param int   $columns     Number of columns in the grid
	 * @param int   $cellWidth   Width of each grid cell in pixels
	 * @param int   $cellHeight  Height of each grid cell in pixels
	 * @param int   $gap         Pixel gap between cells
	 * @param int   $red         Background color red component
	 * @param int   $green       Background color green component
	 * @param int   $blue        Background color blue component
	 *
	 * @return GdImage|resource  The assembled collage image resource
	 */
	public function createCollage(array $images, int $columns = 3, int $cellWidth = 200, int $cellHeight = 200, int $gap = 5, int $red = 255, int $green = 255, int $blue = 255): mixed
	{
		$count = count($images);
		$rows = (int) ceil($count / $columns);
		$canvasWidth = $columns * $cellWidth + ($columns - 1) * $gap;
		$canvasHeight = $rows * $cellHeight + ($rows - 1) * $gap;

		$canvas = imagecreatetruecolor($canvasWidth, $canvasHeight);
		$bgColor = imagecolorallocate($canvas, $red, $green, $blue);
		imagefilledrectangle($canvas, 0, 0, $canvasWidth - 1, $canvasHeight - 1, $bgColor);

		foreach ($images as $index => $imageResource) {
			if (!self::isResource($imageResource)) {
				$imageResource = self::getInstance($imageResource);
			}

			$col = $index % $columns;
			$row = intdiv($index, $columns);
			$destX = $col * ($cellWidth + $gap);
			$destY = $row * ($cellHeight + $gap);

			$srcWidth = self::getWidth($imageResource);
			$srcHeight = self::getHeight($imageResource);

			// Fit the image into the cell, preserving aspect ratio
			$scale = min($cellWidth / $srcWidth, $cellHeight / $srcHeight);
			$scaledW = (int) round($srcWidth * $scale);
			$scaledH = (int) round($srcHeight * $scale);
			$offsetX = $destX + (int) (($cellWidth - $scaledW) / 2);
			$offsetY = $destY + (int) (($cellHeight - $scaledH) / 2);

			imagecopyresampled($canvas, $imageResource, $offsetX, $offsetY, 0, 0, $scaledW, $scaledH, $srcWidth, $srcHeight);
		}

		return $canvas;
	}

	/**
	 * Create a GD image resource from a remote URL.
	 *
	 * Downloads the raw image data via file_get_contents() and creates
	 * a GD resource from the response body using imagecreatefromstring().
	 *
	 * @param string $url      Remote image URL
	 * @param int    $timeout  HTTP request timeout in seconds
	 *
	 * @return GdImage|resource|false  The GD image resource, or false on failure
	 */
	public function createFromUrl(string $url, int $timeout = 10): mixed
	{
		$context = stream_context_create([
			'http' => ['timeout' => $timeout, 'user_agent' => 'PHP GD Image Handler'],
			'https' => ['timeout' => $timeout, 'user_agent' => 'PHP GD Image Handler'],
		]);

		$data = @file_get_contents($url, false, $context);

		if ($data === false) {
			return false;
		}

		return imagecreatefromstring($data);
	}

	/**
	 * Send an image directly to the browser with the appropriate Content-Type header.
	 *
	 * Must be called before any other output. Automatically sets the MIME type
	 * and writes the encoded image binary to stdout.
	 *
	 * @param GdImage|resource $imageResource  The image resource to output
	 * @param string           $format         Format: 'jpeg', 'png', 'gif', 'webp', 'avif', or 'bmp'
	 * @param int              $quality        Output quality 0-100 (JPEG/WebP/AVIF)
	 *
	 * @return bool  True on success, false on failure
	 */
	public static function output(mixed $imageResource, string $format = 'png', int $quality = 90): bool
	{
		if (!self::isResource($imageResource)) {
			$imageResource = self::getInstance($imageResource);
		}

		if (!$imageResource) {
			return false;
		}

		$mimeMap = [
			'jpeg' => 'image/jpeg',
			'jpg' => 'image/jpeg',
			'png' => 'image/png',
			'gif' => 'image/gif',
			'webp' => 'image/webp',
			'avif' => 'image/avif',
			'bmp' => 'image/bmp',
		];

		$format = strtolower($format);
		header('Content-Type: ' . ($mimeMap[$format] ?? 'image/png'));
		$created = false;

		switch ($format) {
			case 'jpeg':
			case 'jpg':
				$created = imagejpeg($imageResource, null, $quality);
				break;
			case 'gif':
				$created = imagegif($imageResource);
				break;
			case 'webp':
				$created = imagewebp($imageResource, null, $quality);
				break;
			case 'avif':
				if (function_exists('imageavif')) {
					$created = imageavif($imageResource, null, $quality);
				}
				$created = false;
				break;
			case 'bmp':
				$created = imagebmp($imageResource);
				break;
			case 'png':
			default:
				$created = imagepng($imageResource, null, min(9, (int) ($quality / 10)));
				break;
		}

		if (!$created) {
			return $created;
		}

		if (OperationSystem::comparePHPVersion('8.5.0', '<') && $created) {
			// @phpstan-ignore-next-line
			imagedestroy($imageResource);
		}

		return $created;
	}

	/**
	 * Save an image resource to a JPEG file.
	 *
	 * Convenience shortcut for imagejpeg() with resource normalization.
	 *
	 * @param GdImage|resource $imageResource  The image resource to save
	 * @param string           $outputPath     Destination file path
	 * @param int              $quality        JPEG quality (0-100)
	 *
	 * @return bool  True on success, false on failure
	 */
	public function toJpeg(mixed $imageResource, string $outputPath, int $quality = 90): bool
	{
		if (!self::isResource($imageResource)) {
			$imageResource = self::getInstance($imageResource);
		}

		return imagejpeg($imageResource, $outputPath, $quality);
	}

	/**
	 * Save an image resource to a PNG file.
	 *
	 * Convenience shortcut for imagepng() with resource normalization.
	 *
	 * @param GdImage|resource $imageResource  The image resource to save
	 * @param string           $outputPath     Destination file path
	 * @param int              $compression    PNG compression level (0=none, 9=maximum, default: 6)
	 *
	 * @return bool  True on success, false on failure
	 */
	public function toPng(mixed $imageResource, string $outputPath, int $compression = 6): bool
	{
		if (!self::isResource($imageResource)) {
			$imageResource = self::getInstance($imageResource);
		}

		return imagepng($imageResource, $outputPath, $compression);
	}

	/**
	 * Save an image resource to a WebP file.
	 *
	 * Convenience shortcut for imagewebp() with resource normalization.
	 * Returns false if the WebP encoder is not available in the current build.
	 *
	 * @param GdImage|resource $imageResource  The image resource to save
	 * @param string           $outputPath     Destination file path
	 * @param int              $quality        WebP quality (0-100)
	 *
	 * @return bool  True on success, false on failure or if WebP is unsupported
	 */
	public function toWebp(mixed $imageResource, string $outputPath, int $quality = 90): bool
	{
		if (!self::isResource($imageResource)) {
			$imageResource = self::getInstance($imageResource);
		}

		if (!function_exists('imagewebp')) {
			return false;
		}

		return imagewebp($imageResource, $outputPath, $quality);
	}

	/**
	 * Save an image resource to an AVIF file.
	 *
	 * Requires PHP 8.1+ with libavif support compiled into GD.
	 * Returns false if imageavif() is not available.
	 *
	 * @param GdImage|resource $imageResource  The image resource to save
	 * @param string           $outputPath     Destination file path
	 * @param int              $quality        AVIF quality (0-100)
	 *
	 * @return bool  True on success, false on failure or if AVIF is unsupported
	 */
	public function toAvif(mixed $imageResource, string $outputPath, int $quality = 80): bool
	{
		if (!self::isResource($imageResource)) {
			$imageResource = self::getInstance($imageResource);
		}

		if (!function_exists('imageavif')) {
			return false;
		}

		return imageavif($imageResource, $outputPath, $quality);
	}

	#endregion
}
