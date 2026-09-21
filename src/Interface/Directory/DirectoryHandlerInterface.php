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
 * Directory Handler Interface
 *
 * Defines the contract for directory operations.
 * Provides methods for creating, copying, deleting, and managing directories.
 */
interface DirectoryHandlerInterface
{
	public static function copy(string $directoryPath, string $copyPath);
	public static function create(string $directoryPath);
	public static function delete(string $directoryPath);
	public static function empty(string $directoryPath);
	public static function getCurrentWorkingLocation();
	public static function getFileCount(string $directoryPath);
	public static function getFreeSpace($prefix = '/');
	public static function getMaxDepth();
	public static function getSize(string $directoryPath);
	public static function hasCurrentWorkingLocation();
	public static function isDirectory(string $directoryPath);
	public static function isEmpty(string $directoryPath);
	public static function make(string $directoryPath);
	public static function renameInnerFiles(string $directoryPath, $pattern, $replacement);
	public static function setMaxDepth(int $depth);
}
