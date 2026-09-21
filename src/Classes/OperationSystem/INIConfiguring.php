<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


use Clover\Enumeration\PHPINI;

/**
 * Class INIConfiguration
 *
 * @package Clover\Classes\OperationSystem
 */
class INIConfiguration
{
	/**
	 * Set the display_startup_errors directive in the PHP configuration.
	 *
	 * @param bool $displayErrors Whether to display startup errors (true for 'On', false for 'Off')
	 * @return bool|string The old value of the display_startup_errors directive on success, or false on failure.
	 */
	public static function setDisplayStatupErrors(bool $displayErrors): string|bool
	{
		return ini_set(PHPINI::DISPLAY_STARTUP_ERRORS, $displayErrors ? 'On' : 'Off');
	}

	/**
	 * Set the display_errors directive in the PHP configuration.
	 *
	 * @param string $displayErrors The new value for the display_errors directive (e.g., 'On', 'Off', '1', '0', etc.)
	 * @return bool|string The old value of the display_errors directive on success, or false on failure.
	 */
	public static function setDisplayErrors(string $displayErrors): string|bool
	{
		return ini_set(PHPINI::DISPLAY_ERRORS, $displayErrors);
	}

	/**
	 * Check if PHP is configured to use cookies for session management.
	 *
	 * @return bool|string True if sessions are configured to use cookies, false otherwise, or false on failure.
	 */
	public static function isSessionUseCookies(): bool|string
	{
		return ini_get(PHPINI::SESSION_USE_COOKIES);
	}

	/**
	 * Get the maximum size of POST data that PHP will accept.
	 *
	 * @return bool|string The maximum POST data size (e.g., '8M', '16M', etc.) or false on failure.
	 */
	public static function getMaxPostSize(): bool|string
	{
		return ini_get(PHPINI::MAX_POST_DATA_SIZE);
	}

	/**
	 * Get the maximum allowed size for uploaded files.
	 *
	 * @return bool|string The maximum upload file size (e.g., '2M', '128M', etc.) or false on failure.
	 */
	public static function getMaxUploadFileSize(): bool|string
	{
		return ini_get(PHPINI::MAX_UPLOAD_FILESIZE);
	}

	/**
	 * Check if short open tags are allowed in the PHP configuration.
	 *
	 * @return bool True if short open tags are allowed, false otherwise.
	 */
	public static function isShortOpenTagAllowed(): bool
	{
		return ini_get(PHPINI::ALLOW_SHORT_OPEN_TAG) == 1;
	}

	/**
	 * Check if file uploads are allowed in the PHP configuration.
	 *
	 * @return bool True if file uploads are allowed, false otherwise.
	 */
	public static function isFileUploadAllowed(): bool
	{
		return ini_get(PHPINI::ALLOW_FILE_UPLOADS) == 1;
	}

	/**
	 * Get the maximum number of files that can be uploaded simultaneously.
	 *
	 * @return bool
	 */
	public static function getMaxUploadFileNumber(): bool
	{
		return ini_get(PHPINI::MAX_UPLOAD_FILE_NUMBER) == 1;
	}

	/**
	 * Get the temporary directory used for file uploads.
	 *
	 * @return bool|string The path to the temporary directory or false on failure.
	 */
	public static function getUploadTemporaryDirectory(): bool
	{
		return ini_get(PHPINI::UPLOAD_TEMPORARY_DIRECTORY) == 1;
	}

	/**
	 * Get the current memory limit for PHP scripts.
	 *
	 * @return bool|string The current memory limit value (e.g., '128M', '1G', etc.) or false on failure.
	 */
	public static function getMemoryLimit(): bool
	{
		return ini_get(PHPINI::MEMORY_LIMIT) == 1;
	}

	/**
	 * Set the memory limit for PHP scripts.
	 *
	 * @param bool|float|int|string|null $value The new memory limit value (e.g., '128M', '1G', etc.)
	 * @return bool|string The old memory limit value on success, or false on failure.
	 */
	public static function setMemoryLimit(bool|float|int|string|null $value): bool|string
	{
		return ini_set('memory_limit', $value);
	}

	/**
	 * Get the maximum number of parts allowed in a multipart/form-data request.
	 *
	 * @return bool
	 */
	public static function getMaxMultipartBodyParts(): bool
	{
		return ini_get(PHPINI::MAX_MULTIPART_BODY_PARTS) == 1;
	}
}
