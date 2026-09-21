<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Message\FileHandler;

/**
 * File Handler Message Class
 *
 * Provides static methods for retrieving file handler-related error messages.
 * Centralizes error message generation for file operations.
 */
class FileHandlerMessage
{
	/**
	 * Get target is not file message
	 * 
	 * @return string
	 */
	public static function getTargetIsNotFileMessage(): string
	{
		return 'Target file is not type of File';
	}

	/**
	 * Get invalid file handler message
	 * 
	 * @return string
	 */
	public static function getInvalidFileHandler(): string
	{
		return 'Handler type is not a resource';
	}

	/**
	 * Get do not use sub-directory syntax message
	 * 
	 * @return string
	 */
	public static function getDoNotUseSubDirectorySyntaxMessage(): string
	{
		return "Don't use the sub-directory syntax";
	}

	/**
	 * Get do not use phar protocol message
	 * 
	 * @return string
	 */
	public static function getDoNotUsePharProtocolMessage(): string
	{
		return "Don't use the `Phar` protocol, it is dangerous";
	}

	/**
	 * Get file is not exists message
	 * 
	 * @param string $filePath
	 * @return string
	 */
	public static function getFileIsNotExistsMessage(string $filePath): string
	{
		return "'{$filePath}' File is not exists or permissions denied";
	}
}
