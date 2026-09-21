<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Implement;

/**
 * File System Interface
 *
 * Defines the contract for file system operations.
 * Provides methods for retrieving file system metadata and statistics.
 */
interface FileSystemInterface
{

	public static function getCurrentInode();

	public static function getStat($filePath): array;

	public static function getDeviceNumber($filePath);

	public static function getInodeNumber($filePath);

	public static function getProtectionNumber($filePath);

	public static function getLinkNumber($filePath);

	public static function getOwnerUserID($filePath);

	public static function getOwnerGroupID($filePath);

	public static function getDeviceType($filePath);

	public static function getSizeOfByte($filePath);

	public static function getLastAccessTime($filePath);

	public static function getLastModifiedTime($filePath);

	public static function getLastInodeModifiedTime($filePath);

	public static function getIOBlockSize($filePath);

	public static function get512ByteAllocatedBlocks(string $filePath);
}
