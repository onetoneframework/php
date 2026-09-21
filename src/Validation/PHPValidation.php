<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Validation;

use Clover\Classes\OperationSystem;

/**
 * PHP Validation Class
 *
 * Provides static methods for validating PHP version compatibility.
 * Includes version comparison and compatibility checking utilities.
 */
class PHPValidation
{
	/**
	 * Get the current PHP version
	 * 
	 * @return string
	 * @return bool|string
	 */
	public static function getVersion(): bool|string
	{
		return OperationSystem::getPHPVersion();
	}

	/**
	 * Check if the current PHP version is greater than the given version
	 * 
	 * @param string $version
	 * @return bool
	 */
	public static function versionGreaterThanCurrent($version): bool
	{
		$bool = version_compare(self::getVersion(), $version, '<') ? true : false;

		return $bool;
	}

	/**
	 * Compare two PHP versions
	 * 
	 * @param string $version1
	 * @param string $version2
	 * @return bool
	 */
	public static function versionCompare($version1, $version2): bool
	{
		$bool = version_compare($version1, $version2) >= 0 ? true : false;

		return $bool;
	}
}
