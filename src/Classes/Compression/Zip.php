<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Compression;

use ZipArchive;

/**
 * Class Zip
 *
 * @package Clover\Classes\Compression
 */
class Zip
{
	/**
	 * Get compressed size of zip file
	 *
	 * @param string $filePath Path to the zip file
	 * 
	 * @return bool|int Compressed size in bytes, or false on failure
	 */
	public function getCompressSize(string $filePath): bool|int
	{
		$zip = new ZipArchive();
		if (!$zip->open($filePath)) {
			return false;
		}

		$totalSize = 0;
		for ($i = 0; $i < $zip->numFiles; $i++) {
			$fileStats = $zip->statIndex($i);
			$totalSize += $fileStats['size'];
		}

		$results = filesize($filePath);
		$zip->close();

		return $results;
	}

	/**
	 * Get required size after uncompressing zip file
	 *
	 * @param string $filePath Path to the zip file
	 * 
	 * @return bool|float Required size after uncompressing, or false on failure
	 */
	public function getRequireSize(string $filePath): bool|float
	{
		$zip = new ZipArchive();
		if (!$zip->open($filePath)) {
			return false;
		}

		$totalSize = 0;
		for ($i = 0; $i < $zip->numFiles; $i++) {
			$fileStats = $zip->statIndex($i);
			$totalSize += $fileStats['size'];
		}

		$results = round(($totalSize - filesize($filePath)), -4);
		$zip->close();

		return $results;
	}

	/**
	 * Uncompress zip file
	 *
	 * @param string $filePath Path to the zip file
	 * @param string|null $destination Path where the uncompressed files should be saved (defaults to zip file directory)
	 * 
	 * @return bool	True on success, false on failure
	 */
	public function uncompress(string $filePath, ?string $destination = null): bool
	{
		$archive = new ZipArchive();
		if (!$archive->open($filePath)) {
			return false;
		}

		if ($destination === null) {
			$destination = dirname($filePath);
		}

		$ok = $archive->extractTo($destination);
		$archive->close();

		return $ok;
	}

	/**
	 * Compress files into zip file
	 *
	 * @param string $filePath Path where the zip file should be created
	 * @param array  $fileList List of files to compress, each item should be an associative array with 'filepath' and 'filename' keys
	 * 
	 * @return bool True on success, false on failure
	 */
	public function compress(string $filePath, array $fileList): bool
	{
		$zipHandler = new ZipArchive();
		if ($zipHandler->open($filePath, ZipArchive::CREATE) !== true) {
			return false;
		}

		foreach ($fileList as $file) {
			if (!file_exists($file['filepath'])) {
				continue;
			}

			$zipHandler->addFile($file['filepath'], $file['filename']);
		}

		return $zipHandler->close();
	}
}
