<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\FileSystem;

use Clover\Implement\FileSystemInterface as FileSystemInterface;
use function count;

/**
 * File System Handler
 */
class Handler implements FileSystemInterface
{

	public function __construct()
	{
	}

	/**
	 * Get current inode number
	 * 
	 * @return bool|int
	 */
	public static function getCurrentInode(): bool|int
	{
		return getmyinode();
	}

	/**
	 * Get specific index from stat array
	 * 
	 * @param string $filePath
	 * @param int $index
	 * 
	 * @return bool|int
	 */
	public static function getStatFromIndex(string $filePath, int $index): bool|int
	{
		$stat = self::getStat($filePath);

		if (count($stat) >= $index) {
			return $stat[$index];
		}

		return false;
	}

	/**
	 * Gives information about a file
	 * 
	 * @param string $filePath
	 * 
	 * @return array{
	 * 	dev:int, 
	 * 	ino:int, 
	 * 	mode:int, 
	 * 	nlink:int, 
	 * 	uid:int, 
	 * 	gid:int, 
	 * 	rdev:int, 
	 * 	size:int, 
	 * 	atime:int, 
	 * 	mtime:int, 
	 * 	ctime:int, 
	 * 	blksize:int, 
	 * 	blocks:int
	 * }|false
	 */
	public static function getStat($filePath): array
	{
		$return = stat($filePath);

		return $return;
	}

	/**
	 * Get device number
	 * 
	 * @param string $filePath
	 * 
	 * @return bool|int
	 */
	public static function getDeviceNumber($filePath): bool|int
	{
		return self::getStatFromIndex($filePath, 0);
	}

	/**
	 * Get inode number
	 * 
	 * @param string $filePath
	 * 
	 * @return bool|int
	 */
	public static function getInodeNumber($filePath): bool|int
	{
		return self::getStatFromIndex($filePath, 1);
	}

	/**
	 * Get protection number
	 * 
	 * @param string $filePath
	 * 
	 * @return bool|int
	 */
	public static function getProtectionNumber($filePath): bool|int
	{
		return self::getStatFromIndex($filePath, 2);
	}

	/**
	 * Get number of hard links
	 * 
	 * @param string $filePath
	 * 
	 * @return bool|int
	 */
	public static function getLinkNumber($filePath): bool|int
	{
		return self::getStatFromIndex($filePath, 3);
	}

	/**
	 * Get owner user ID
	 * 
	 * @param string $filePath
	 * 
	 * @return bool|int
	 */
	public static function getOwnerUserID($filePath): bool|int
	{
		return self::getStatFromIndex($filePath, 4);
	}

	/**
	 * Get owner group ID
	 * 
	 * @param string $filePath
	 * 
	 * @return bool|int
	 */
	public static function getOwnerGroupID($filePath): bool|int
	{
		return self::getStatFromIndex($filePath, 5);
	}

	/**
	 * Get device type, if inode device
	 * 
	 * @param string $filePath
	 * 
	 * @return bool|int
	 */
	public static function getDeviceType($filePath): bool|int
	{
		return self::getStatFromIndex($filePath, 6);
	}

	/**
	 * Get the size of the file in bytes
	 * 
	 * @param string $filePath
	 * 
	 * @return bool|int
	 */
	public static function getSizeOfByte($filePath): bool|int
	{
		return self::getStatFromIndex($filePath, 7);
	}

	/**
	 * Get the last access time
	 * 
	 * @param string $filePath
	 * 
	 * @return bool|int
	 */
	public static function getLastAccessTime($filePath): bool|int
	{
		return self::getStatFromIndex($filePath, 8);
	}

	/**
	 * Get the last modified time
	 * 
	 * @param string $filePath
	 * 
	 * @return bool|int
	 */
	public static function getLastModifiedTime($filePath): bool|int
	{
		return self::getStatFromIndex($filePath, 9);
	}

	/**
	 * Get the last inode change time
	 * 
	 * @param string $filePath
	 * 
	 * @return bool|int
	 */
	public static function getLastInodeModifiedTime($filePath): bool|int
	{
		return self::getStatFromIndex($filePath, 10);
	}

	/**
	 * Get the block size for filesystem I/O
	 * 
	 * @param string $filePath
	 * 
	 * @return bool|int
	 */
	public static function getIOBlockSize($filePath): bool|int
	{
		return self::getStatFromIndex($filePath, 11);
	}

	/**
	 * Get the number of 512 byte blocks allocated
	 * 
	 * @param string $filePath
	 * 
	 * @return bool|int
	 */
	public static function get512ByteAllocatedBlocks($filePath): bool|int
	{
		return self::getStatFromIndex($filePath, 12);
	}
}
