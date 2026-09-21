<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Compression;

/**
 * Class Zlib
 * 
 * Provides methods for compressing and uncompressing files using zlib.
 *
 * @package Clover\Classes\Compression
 */
class Zlib
{
	/**
	 * Uncompress gz file
	 *
	 * @param string $filePath Path to the gz file to uncompress
	 * @param string $destination Path where the uncompressed file should be saved
	 * 
	 * @return bool True on success, false on failure
	 */
	public static function uncompress(string $filePath, string $destination): bool
	{
		if (!is_file($filePath) || !file_exists($filePath) || filesize($filePath) <= 0) {
			return false;
		}

		$fileHandler = fopen($filePath, 'rb');

		if (!$fileHandler) {
			fclose($fileHandler);
			return false;
		}

		$uncompressContents = fread($fileHandler, filesize($filePath));
		fclose($fileHandler);

		$uncompressing = gzuncompress($uncompressContents);
		if (!$uncompressing) {
			return false;
		}

		$fileHandler = fopen($destination, 'wb');
		fwrite($fileHandler, $uncompressing);
		fclose($fileHandler);

		return true;
	}

	/**
	 * Compress file using zlib
	 *
	 * @param string $filePath Path to the file to compress
	 * @param string $destination Path where the compressed file should be saved
	 * @param int $level The level of compression. Can be given as 0 for no compression up to 9 for maximum compression. If -1 is used, the default compression of the zlib library is used which is 6.
	 * @param int $encoding One of ZLIB_ENCODING_* constants.
	 * 
	 * @return bool True on success, false on failure
	 */
	public static function compress(string $filePath, string $destination, int $level = -1, int $encoding = ZLIB_ENCODING_DEFLATE): bool
	{
		$fp = fopen($filePath, 'rb');
		$compressContents = fread($fp, filesize($filePath));
		fclose($fp);

		$compressing = gzcompress($compressContents, $level, $encoding);

		if (!$compressing) {
			return false;
		}

		if ($compressing) {
			$fp = fopen($destination, 'wb');
			fwrite($fp, $compressing);
			fclose($fp);
		}

		return true;
	}
}
