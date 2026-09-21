<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Format;

use function is_array;

/**
 * Multi-Purpose Internet Mail Extensions
 */
class MultiPurposeInternetMailExtensions
{
	/**
	 * @var string File extension
	 */
	private static $extension = '';

	/**
	 * @var array File content types
	 */
	private static $types;

	/**
	 * Get file content type
	 * 
	 * @param resource|string $filePath File path
	 * 
	 * @return bool|string
	 */
	public static function getFileContentType(mixed $filePath): bool|string
	{
		return mime_content_type($filePath);
	}

	/**
	 * Get extension from content type
	 * 
	 * @param string $contentType
	 * 
	 * @return mixed File content type
	 */
	public static function getExtensionFromContentType(string $contentType = ''): mixed
	{
		self::$types = include(dirname(__FILE__) . '/../../Defaults/MultiPurposeInternetMailExtensions.php');

		foreach (self::$types as $key => $type) {
			if (!is_array($type['type'])) {
				if ($union == $contentType) {
					return $key;
				}

				continue;
			}

			foreach($type['type'] as $union) {
				if ($union == $contentType) {
					return $key;
				}
			}
		}

		return false;
	}

	/**
	 * Get file content type from extension
	 * 
	 * @param string $extension
	 * @param bool $toArray
	 * 
	 * @return mixed File content type
	 */
	public static function getContentTypeFromExtension(string $extension, bool $toArray = false): mixed
	{
		self::$types = include(dirname(__FILE__) . '/../../Defaults/MultiPurposeInternetMailExtensions.php');

		$type = '';

		if (!$extension && self::$extension) {
			$extension = self::$extension;
		}

		if (isset(self::$types[$extension])) {
			$type = self::$types[$extension]['type'];
		}

		return $toArray ? (is_array($type) ? $type : [$type]) : (is_array($type) ? $type[0] : $type);
	}
}
