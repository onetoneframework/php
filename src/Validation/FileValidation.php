<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Validation;

use Clover\Validation\PHPValidation;
use function strlen;

/**
 * File Validation Class
 *
 * Provides static methods for validating file-related operations.
 * Includes checks for file readability, protocol validation, and file path syntax.
 */
class FileValidation
{
	/**
	 * Check if a file is readable
	 * 
	 * @param string $filename
	 * @return bool
	 */
	public static function isReadable(string $filename): bool
	{
		if (PHPValidation::versionGreaterThanCurrent('5.3.0')) {
			if (strlen($filename) >= PHP_MAXPATHLEN) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Check if a file is not readable
	 * 
	 * @param string $filename
	 * @return bool
	 */
	public static function isNotReadable(string $filename): bool
	{
		return !self::isReadable($filename);
	}


	/**
	 * Check if a file uses the HTTP protocol
	 * 
	 * @param string $filePath
	 * 
	 * @return bool
	 */
	public static function isHTTPProtocol(string $filePath): bool
	{
		$regexr = '/^(http||https):\/\//i';

		if (preg_match($regexr, $filePath)) {
			return true;
		}

		return false;
	}

	/**
	 * Check if a file is not writable
	 * @param string $filePath
	 * @return bool
	 */
	public static function isNotHTTPProtocol(string $filePath): bool
	{
		return !self::isHTTPProtocol($filePath);
	}

	/**
	 * Check if a file uses the phar protocol
	 * @param string $filePath
	 * @return bool
	 */
	public static function isPharProtocol(string $filePath): bool
	{
		$regexr = '/^phar:\/\/.*/i';

		if (preg_match($regexr, $filePath)) {
			return true;
		}

		return false;
	}

	/**
	 * Check if a file uses the file protocol
	 * @param string $filePath
	 * @return bool
	 */
	public static function isFileProtocol(string $filePath): bool
	{
		$regexr = '/^file:\/\/.*/i';

		if (preg_match($regexr, $filePath)) {
			return true;
		}

		return false;
	}

	/**
	 * Check if a file does not use the phar protocol
	 * 
	 * @param string $filePath
	 * @return bool
	 */
	public static function isNotPharProtocol(string $filePath): bool
	{
		return !self::isPharProtocol($filePath);
	}

	/**
	 * Check if a file has subfolder syntax
	 * @param string $filePath
	 * @return bool
	 */
	public static function hasSubfolderSyntax(string $filePath): bool
	{
		$regexr = '/..\/$/i';

		if (preg_match($regexr, $filePath)) {
			return true;
		}

		return false;
	}

	/**
	 * Check if a file does not have subfolder syntax
	 * 
	 * @param string $filePath
	 * @return bool
	 */
	public static function hasNotSubfolderSyntax(string $filePath): bool
	{
		return !self::hasSubfolderSyntax($filePath);
	}

	/**
	 * Check if a file has extention
	 * @param string $filePath
	 * @return bool
	 */
	public static function hasExtention(string $filePath): bool
	{
		$regexr = '/^.+\.[A-Za-z0-9]{1,5}$/i';

		if (preg_match($regexr, $filePath)) {
			return true;
		}

		return false;
	}

	/**
	 * Check if a file has not extention
	 * @param string $filePath
	 * @return bool
	 */
	public static function hasNotExtention(string $filePath): bool
	{
		return !self::hasExtention($filePath);
	}
}
