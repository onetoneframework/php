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
 * Class World
 *
 * @package Clover\Classes\Permission
 */
class World extends Permission
{

	private static $mode;

	/**
	 * World constructor.
	 */
	public function __construct()
	{
	}

	/**
	 * Check if the world has read permission.
	 *
	 * @return bool
	 */
	public function isReadable(): bool
	{
		return (self::getSharedMode() & 0x0004) === 0x0004;
	}

	/**
	 * Check if the world has write permission.
	 *
	 * @return bool
	 */
	public function isWritable(): bool
	{
		return (self::getSharedMode() & 0x0002) === 0x0002;
	}

	/**
	 * Check if the world has execute permission.
	 *
	 * @return string
	 */
	public function getExecutableUsers(): string
	{
		return ((self::getSharedMode() & 0x0001) ?
			((self::getSharedMode() & 0x0200) ? 't' : 'x')	: ((self::getSharedMode() & 0x0200) ? 'T' : '-'));;
	}
}
