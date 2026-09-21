<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Permission;

use Clover\Classes\Permission;

/**
 * Class Owner
 *
 * @package Clover\Classes\Permission
 */
class Owner extends Permission
{

	private static $mode;

	/**
	 * Owner constructor.
	 */
	public function __construct()
	{
	}

	/**
	 * Check if the owner has read permission.
	 *
	 * @return bool
	 */
	public function isReadable(): bool
	{
		return (self::getSharedMode() & 0x0100) === 0x0100;
	}

	/**
	 * Check if the owner has write permission.
	 *
	 * @return bool
	 */
	public function isWritable(): bool
	{
		return self::getSharedMode() === 0x0100;
	}

	/**
	 * Check if the owner has execute permission.
	 *
	 * @return string
	 */
	public function getExecutableUsers(): string
	{
		return ((self::getSharedMode() & 0x0008) ?
			((self::getSharedMode() & 0x0400) ? 's' : 'x') : ((self::getSharedMode() & 0x0400) ? 'S' : '-'));
	}
}
