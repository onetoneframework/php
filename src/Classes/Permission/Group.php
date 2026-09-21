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
 * Class Group
 *
 * @package Clover\Classes\Permission
 */
class Group extends Permission
{

	private static $mode;

	/**
	 * Group constructor.
	 */
	public function __construct()
	{
	}

	/**
	 * Check if the group has read permission.
	 *
	 * @return bool
	 */
	public function isReadable(): bool
	{
		return (self::getSharedMode() & 0x0020) === 0x0020;
	}

	/**
	 * Check if the group has write permission.
	 *
	 * @return bool
	 */
	public function isWritable(): bool
	{
		return (self::getSharedMode() & 0x0010) === 0x0010;
	}

	/**
	 * Check if the group has execute permission.
	 *
	 * @return string
	 */
	public function getExecutableUsers(): string
	{
		return ((self::getSharedMode() & 0x0008) ?
			((self::getSharedMode() & 0x0400) ? 's' : 'x') : ((self::getSharedMode() & 0x0400) ? 'S' : '-'));
	}
}
