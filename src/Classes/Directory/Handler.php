<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Directory;

#region use

use Clover\Classes\BaseClass;
use Clover\Classes\File\Handler as FileHandler;
use Clover\Implement\{DirectoryHandlerInterface, FileHandlerInterface};
use Clover\Exception\DirectoryHandler\{DirectoryIsNotExistsException, DirectoryIsExistsException};
use CallbackFilterIterator;
use DirectoryIterator;
use FilesystemIterator;
use InvalidArgumentException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use RuntimeException;
use SplFileInfo;

use function array_filter;
use function array_flip;
use function array_map;
use function array_merge;
use function array_slice;
use function array_unique;
use function array_values;
use function arsort;
use function asort;
use function basename;
use function chmod;
use function clearstatcache;
use function copy;
use function count;
use function ctype_alpha;
use function date;
use function dirname;
use function disk_free_space;
use function disk_total_space;
use function explode;
use function fclose;
use function file_exists;
use function file_get_contents;
use function fileatime;
use function filectime;
use function filegroup;
use function filemtime;
use function fileowner;
use function fileperms;
use function flock;
use function fnmatch;
use function fopen;
use function function_exists;
use function getcwd;
use function getenv;
use function glob;
use function hash_file;
use function hash_final;
use function hash_init;
use function hash_update;
use function implode;
use function ini_get;
use function intval;
use function is_dir;
use function is_file;
use function is_link;
use function is_readable;
use function is_resource;
use function is_writable;
use function iterator_count;
use function iterator_to_array;
use function ksort;
use function ltrim;
use function max;
use function min;
use function mkdir;
use function pathinfo;
use function posix_getgrgid;
use function posix_getpwuid;
use function preg_match;
use function preg_replace;
use function readlink;
use function realpath;
use function rename;
use function rmdir;
use function rtrim;
use function scandir;
use function sort;
use function sprintf;
use function str_contains;
use function str_replace;
use function str_starts_with;
use function stripos;
use function strlen;
use function strpos;
use function strtolower;
use function strtoupper;
use function substr;
use function substr_count;
use function symlink;
use function sys_get_temp_dir;
use function tempnam;
use function time;
use function touch;
use function trim;
use function unlink;

#endregion

/**
 * Provides comprehensive directory and filesystem operations.
 *
 * Handles creation, deletion, copying, moving, renaming, and traversal of
 * directories and their contents. Supports recursive operations, path analysis,
 * directory comparison, temporary directory management, permission handling,
 * pattern-based filtering, and disk space inspection.
 *
 * @package Clover\Classes\Directory
 */
class Handler extends BaseClass implements DirectoryHandlerInterface
{
	#region Properties

	/**
	 * @var FileHandlerInterface The file handler used for file-level operations.
	 */
	private FileHandlerInterface $fileHandler;

	/**
	 * @var int Maximum recursion depth for directory traversal (-1 means unlimited).
	 */
	private static int $directoryDepth = -1;

	#endregion

	#region function

	/**
	 * Construct a new directory handler.
	 *
	 * Accepts an optional file handler dependency. If none is provided,
	 * a default FileHandler instance is created.
	 *
	 * @param FileHandlerInterface|null $fileHandler Optional file handler implementation.
	 */
	public function __construct(?FileHandlerInterface $fileHandler = null)
	{
		if ($fileHandler instanceof FileHandlerInterface) {
			$this->fileHandler = $fileHandler;
		} else {
			$this->fileHandler = new FileHandler();
		}

		self::$directoryDepth = -1;
	}

	// =========================================================================
	// Path Validation (Internal)
	// =========================================================================

	/**
	 * Assert that a path does not exceed the system's maximum path length.
	 *
	 * @param string $path The path to validate.
	 *
	 * @return void
	 *
	 * @throws RuntimeException If the path exceeds PHP_MAXPATHLEN.
	 */
	private static function assertPathLength(string $path): void
	{
		if (strlen($path) > PHP_MAXPATHLEN) {
			throw new RuntimeException(sprintf('Path exceeds the maximum length of %d', PHP_MAXPATHLEN));
		}
	}

	/**
	 * Assert that the given path is an existing directory.
	 *
	 * @param string $path The path to validate.
	 *
	 * @return void
	 *
	 * @throws DirectoryIsNotExistsException If the path is not an existing directory.
	 */
	private static function assertDirectoryExists(string $path): void
	{
		self::assertPathLength($path);

		if (!is_dir($path)) {
			throw new DirectoryIsNotExistsException(sprintf('Directory does not exist: %s', $path));
		}
	}

	// =========================================================================
	// System Info
	// =========================================================================

	/**
	 * Get the system's default temporary directory path.
	 *
	 * @return string The temporary directory path.
	 */
	public static function getDefaultTemporaryDirectoryPath(): string
	{
		return sys_get_temp_dir();
	}

	/**
	 * Get the available free disk space for the partition containing the given path.
	 *
	 * @param string $prefix A directory path on the target partition (default: root '/').
	 *
	 * @return float|false The number of free bytes, or false on failure.
	 */
	public static function getFreeSpace($prefix = '/'): float|false
	{
		if (!function_exists('disk_free_space')) {
			return false;
		}

		return disk_free_space($prefix);
	}

	/**
	 * Get the total disk space for the partition containing the given path.
	 *
	 * @param string $prefix A directory path on the target partition (default: root '/').
	 *
	 * @return float|false The total number of bytes, or false on failure.
	 */
	public static function getTotalSpace(string $prefix = '/'): float|false
	{
		if (!function_exists('disk_total_space')) {
			return false;
		}

		return disk_total_space($prefix);
	}

	/**
	 * Get the used disk space for the partition containing the given path.
	 *
	 * Calculated as the difference between total space and free space.
	 *
	 * @param string $prefix A directory path on the target partition.
	 *
	 * @return float|false The used bytes, or false if calculation fails.
	 */
	public static function getUsedSpace(string $prefix = '/'): float|false
	{
		$total = self::getTotalSpace($prefix);
		$free = self::getFreeSpace($prefix);

		if ($total === false || $free === false) {
			return false;
		}

		return $total - $free;
	}

	/**
	 * Get the disk usage percentage for the partition containing the given path.
	 *
	 * @param string $prefix A directory path on the target partition.
	 *
	 * @return float|false The usage percentage (0.0-100.0), or false on failure.
	 */
	public static function getDiskUsagePercent(string $prefix = '/'): float|false
	{
		$total = self::getTotalSpace($prefix);
		$free = self::getFreeSpace($prefix);

		if ($total === false || $free === false || $total == 0) {
			return false;
		}

		return round(($total - $free) / $total * 100, 2);
	}

	/**
	 * Get all available writable temporary directories on the system.
	 *
	 * Checks platform-specific paths, environment variables, PHP configuration,
	 * and the system temp directory. Returns only paths that exist and are writable.
	 *
	 * @return string[] List of writable temporary directory paths.
	 */
	public static function getTemporaryDirectories(): array
	{
		$directories = [];

		$uploadTmp = ini_get('upload_tmp_dir');
		if ($uploadTmp !== false && $uploadTmp !== '') {
			$directories[] = $uploadTmp;
		}

		if (str_starts_with(PHP_OS, 'WIN')) {
			$directories[] = 'c:\\windows\\temp';
			$directories[] = 'c:\\winnt\\temp';
			$directories[] = getenv('TEMP') ?: null;
			$directories[] = getenv('TMP') ?: null;
			$directories[] = getenv('TMPDIR') ?: null;
		} else {
			$directories[] = '/tmp';
			$directories[] = '/var/tmp';
			$directories[] = '/usr/tmp';
			$directories[] = getenv('TMPDIR') ?: null;
			$directories[] = getenv('TEMP') ?: null;
			$directories[] = getenv('TMP') ?: null;
		}

		$directories[] = sys_get_temp_dir();

		return array_values(array_unique(array_filter($directories, function ($dir) {
			return $dir !== null && $dir !== '' && is_dir($dir) && is_writable($dir);
		})));
	}

	/**
	 * Create a temporary directory with a unique name inside the system temp folder.
	 *
	 * @param string $prefix A prefix for the directory name.
	 *
	 * @return string The path to the newly created temporary directory.
	 *
	 * @throws RuntimeException If the directory could not be created.
	 */
	public static function createTemporary(string $prefix = 'tmp_'): string
	{
		$tempDir = sys_get_temp_dir();
		$path = tempnam($tempDir, $prefix);

		if ($path === false) {
			throw new RuntimeException('Failed to create temporary file for directory conversion');
		}

		unlink($path);

		if (!mkdir($path, 0755)) {
			throw new RuntimeException(sprintf('Failed to create temporary directory: %s', $path));
		}

		return $path;
	}

	// =========================================================================
	// Current Working Directory
	// =========================================================================

	/**
	 * Check whether the current working directory can be determined.
	 *
	 * @return bool True if getcwd() returns a valid path.
	 */
	public static function hasCurrentWorkingLocation(): bool
	{
		return self::getCurrentWorkingLocation() !== false;
	}

	/**
	 * Get the current working directory path.
	 *
	 * @return string|false The current working directory, or false on failure.
	 */
	public static function getCurrentWorkingLocation(): string|false
	{
		return getcwd();
	}

	// =========================================================================
	// Directory Inspection
	// =========================================================================

	/**
	 * Check whether the given path is an existing directory.
	 *
	 * This method never throws — safe to use for existence checks.
	 *
	 * @param string $path The filesystem path to check.
	 *
	 * @return bool True if the path exists and is a directory.
	 *
	 * @throws RuntimeException If the path exceeds the maximum length.
	 */
	public static function isDirectory(string $path): bool
	{
		self::assertPathLength($path);

		return is_dir($path);
	}

	/**
	 * Check whether the given directory exists.
	 *
	 * Convenience alias for isDirectory(). Never throws for
	 * non-existent paths — returns false instead.
	 *
	 * @param string $path The filesystem path to check.
	 *
	 * @return bool True if the path exists and is a directory.
	 */
	public static function exists(string $path): bool
	{
		if (strlen($path) > PHP_MAXPATHLEN) {
			return false;
		}

		return is_dir($path);
	}

	/**
	 * Check whether a directory exists and is writable.
	 *
	 * @param string $path The directory path.
	 *
	 * @return bool True if the directory exists and is writable.
	 */
	public static function isWritable(string $path): bool
	{
		return is_dir($path) && is_writable($path);
	}

	/**
	 * Check whether a directory exists and is readable.
	 *
	 * @param string $path The directory path.
	 *
	 * @return bool True if the directory exists and is readable.
	 */
	public static function isReadable(string $path): bool
	{
		return is_dir($path) && is_readable($path);
	}

	/**
	 * Check whether a directory has no files or subdirectories.
	 *
	 * Returns true if the path does not exist or is not a directory,
	 * since a non-existent directory is trivially "empty".
	 * This method never throws — safe to use without try-catch.
	 *
	 * @param string $directoryPath The directory path.
	 *
	 * @return bool True if the directory is empty or does not exist.
	 */
	public static function isEmpty(string $directoryPath): bool
	{
		if (!is_dir($directoryPath)) {
			return true;
		}

		$iterator = new DirectoryIterator($directoryPath);

		foreach ($iterator as $item) {
			if (!$item->isDot()) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Get the total size of all files within a directory, recursively.
	 *
	 * @param string $directoryPath The directory path.
	 *
	 * @return int The total size in bytes.
	 *
	 * @throws DirectoryIsNotExistsException If the directory does not exist.
	 */
	public static function getSize(string $directoryPath): int
	{
		self::assertDirectoryExists($directoryPath);

		$sizes = 0;
		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($directoryPath, RecursiveDirectoryIterator::SKIP_DOTS)
		);

		foreach ($iterator as $file) {
			$sizes += $file->getSize();
		}

		return $sizes;
	}

	/**
	 * Count the number of files in a directory (non-recursive, immediate children only).
	 *
	 * @param string $directoryPath The directory path.
	 *
	 * @return int The file count.
	 *
	 * @throws DirectoryIsNotExistsException If the directory does not exist.
	 */
	public static function getFileCount(string $directoryPath): int
	{
		self::assertDirectoryExists($directoryPath);

		$iterator = new RecursiveDirectoryIterator($directoryPath, FilesystemIterator::SKIP_DOTS);

		return iterator_count($iterator);
	}

	/**
	 * Count files recursively within a directory, optionally filtering by extension.
	 *
	 * @param string   $directoryPath The directory path.
	 * @param string[] $extensions    Optional list of file extensions to count (e.g., ['php', 'js']).
	 *
	 * @return int The matching file count.
	 *
	 * @throws DirectoryIsNotExistsException If the directory does not exist.
	 */
	public static function getFileCountRecursive(string $directoryPath, array $extensions = []): int
	{
		self::assertDirectoryExists($directoryPath);

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($directoryPath, RecursiveDirectoryIterator::SKIP_DOTS)
		);

		if (self::$directoryDepth !== -1) {
			$iterator->setMaxDepth(self::$directoryDepth);
		}

		$count = 0;
		$extMap = !empty($extensions) ? array_flip(array_map('strtolower', $extensions)) : null;

		foreach ($iterator as $file) {
			if (!$file->isFile()) {
				continue;
			}

			if ($extMap !== null && !isset($extMap[strtolower($file->getExtension())])) {
				continue;
			}

			$count++;
		}

		return $count;
	}

	/**
	 * Empty directory
	 *
	 * @param string $directoryPath
	 *
	 * @throws DirectoryIsNotExistsException
	 * 
	 * @return bool
	 */
	public static function empty(string $directoryPath): bool
	{
		if (strlen($directoryPath) > PHP_MAXPATHLEN) {
			throw new RuntimeException("Path exceeds the maximum length of " . PHP_MAXPATHLEN);
		}

		if (!self::isDirectory($directoryPath)) {
			throw new DirectoryIsNotExistsException();
		}

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($directoryPath, RecursiveDirectoryIterator::SKIP_DOTS),
			RecursiveIteratorIterator::CHILD_FIRST
		);

		if (self::getMaxDepth() !== -1) {
			$iterator->setMaxDepth(self::getMaxDepth());
		}

		/** @var RecursiveDirectoryIterator[] $iterator */
		foreach ($iterator as $fileInformation) {
			if ($fileInformation->isDir()) {
				continue;
			}

			if (unlink($fileInformation->getRealPath()) === false) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Count subdirectories within a directory (immediate children only).
	 *
	 * @param string $directoryPath The directory path.
	 *
	 * @return int The subdirectory count.
	 *
	 * @throws DirectoryIsNotExistsException If the directory does not exist.
	 */
	public static function getSubdirectoryCount(string $directoryPath): int
	{
		self::assertDirectoryExists($directoryPath);

		$count = 0;
		$iterator = new DirectoryIterator($directoryPath);

		foreach ($iterator as $item) {
			if ($item->isDot()) {
				continue;
			}

			if ($item->isDir()) {
				$count++;
			}
		}

		return $count;
	}

	/**
	 * Get the last modification time of a directory.
	 *
	 * @param string $directoryPath The directory path.
	 *
	 * @return int The Unix timestamp of the last modification.
	 *
	 * @throws DirectoryIsNotExistsException If the directory does not exist.
	 */
	public static function getLastModifiedTime(string $directoryPath): int
	{
		self::assertDirectoryExists($directoryPath);

		$result = filemtime($directoryPath);

		if ($result === false) {
			throw new RuntimeException(sprintf('Failed to get modification time for: %s', $directoryPath));
		}

		return $result;
	}

	/**
	 * Get the last access time of a directory.
	 *
	 * @param string $directoryPath The directory path.
	 *
	 * @return int The Unix timestamp of the last access.
	 *
	 * @throws DirectoryIsNotExistsException If the directory does not exist.
	 */
	public static function getLastAccessTime(string $directoryPath): int
	{
		self::assertDirectoryExists($directoryPath);

		$result = fileatime($directoryPath);

		if ($result === false) {
			throw new RuntimeException(sprintf('Failed to get access time for: %s', $directoryPath));
		}

		return $result;
	}

	/**
	 * Get the most recently modified file timestamp within a directory tree.
	 *
	 * Recursively scans all files and returns the newest modification timestamp.
	 *
	 * @param string $directoryPath The directory path.
	 *
	 * @return int|null The Unix timestamp of the newest file, or null if the directory is empty.
	 *
	 * @throws DirectoryIsNotExistsException If the directory does not exist.
	 */
	public static function getNewestFileTime(string $directoryPath): ?int
	{
		self::assertDirectoryExists($directoryPath);

		$newest = null;
		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($directoryPath, RecursiveDirectoryIterator::SKIP_DOTS)
		);

		foreach ($iterator as $file) {
			if (!$file->isFile()) {
				continue;
			}

			$mtime = $file->getMTime();
			if ($newest === null || $mtime > $newest) {
				$newest = $mtime;
			}
		}

		return $newest;
	}

	/**
	 * Get the Unix file permissions of a directory as an octal string.
	 *
	 * @param string $directoryPath The directory path.
	 *
	 * @return string The octal permission string (e.g., '0755').
	 *
	 * @throws DirectoryIsNotExistsException If the directory does not exist.
	 */
	public static function getPermissions(string $directoryPath): string
	{
		self::assertDirectoryExists($directoryPath);

		return substr(sprintf('%o', fileperms($directoryPath)), -4);
	}

	/**
	 * Get the absolute (real) path of a directory, resolving symlinks and relative components.
	 *
	 * @param string $directoryPath The directory path.
	 *
	 * @return string|false The resolved real path, or false on failure.
	 */
	public static function getRealPath(string $directoryPath): string|false
	{
		return realpath($directoryPath);
	}

	// =========================================================================
	// Directory Creation
	// =========================================================================

	/**
	 * Create a directory, throwing if it already exists.
	 *
	 * This is a strict creation method — use ensureExists() if you want
	 * to silently skip creation when the directory is already present.
	 *
	 * @param string $directoryPath The path of the directory to create.
	 * @param int    $permission    Octal permission mode (default: 0755).
	 * @param bool   $recursive     If true, create parent directories as needed.
	 *
	 * @return bool True if the directory was created successfully.
	 *
	 * @throws DirectoryIsExistsException If the directory already exists.
	 * @throws RuntimeException           If the path exceeds maximum length.
	 */
	public static function create(string $directoryPath, int $permission = 0755, bool $recursive = true): bool
	{
		self::assertPathLength($directoryPath);

		if (is_dir($directoryPath)) {
			throw new DirectoryIsExistsException(sprintf('Directory already exists: %s', $directoryPath));
		}

		return mkdir($directoryPath, $permission, $recursive);
	}

	/**
	 * Create a directory if it does not already exist (alias with existence check).
	 *
	 * @param string $directoryPath The directory path.
	 * @param int    $permission    Octal permission mode (default: 0755).
	 *
	 * @return bool True on successful creation, false if creation fails. Returns true if already exists.
	 *
	 * @throws RuntimeException If the path exceeds maximum length.
	 */
	public static function make(string $directoryPath, int $permission = 0755): bool
	{
		self::assertPathLength($directoryPath);

		if (is_dir($directoryPath)) {
			return true;
		}

		return mkdir($directoryPath, $permission, true);
	}

	/**
	 * Create multiple directories from an array of paths.
	 *
	 * Skips paths that already exist. Returns false immediately if any
	 * individual creation fails.
	 *
	 * @param string[] $pathList Array of directory paths to create.
	 * @param int      $mode    Octal permission mode (default: 0755).
	 *
	 * @return bool True if all directories were created (or already existed).
	 */
	public static function makeMultiple(array $pathList, int $mode = 0755): bool
	{
		foreach ($pathList as $path) {
			if (is_dir($path)) {
				continue;
			}

			if (!mkdir($path, $mode, true)) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Ensure a directory exists, creating it if necessary.
	 *
	 * Unlike create(), this method does not throw if the directory already exists.
	 * Unlike make(), this method throws if creation fails.
	 *
	 * @param string $directoryPath The directory path to ensure.
	 * @param int    $permission    Octal permission mode (default: 0755).
	 *
	 * @return string The absolute path of the ensured directory.
	 *
	 * @throws RuntimeException If the path exists but is not a directory, or creation fails.
	 */
	public static function ensureExists(string $directoryPath, int $permission = 0755): string
	{
		self::assertPathLength($directoryPath);

		if (file_exists($directoryPath)) {
			if (!is_dir($directoryPath)) {
				throw new RuntimeException(sprintf('Path exists but is not a directory: %s', $directoryPath));
			}
			return $directoryPath;
		}

		if (!mkdir($directoryPath, $permission, true)) {
			throw new RuntimeException(sprintf('Failed to create directory: %s', $directoryPath));
		}

		return $directoryPath;
	}

	// =========================================================================
	// Directory Removal
	// =========================================================================

	/**
	 * Remove an empty directory.
	 *
	 * Only succeeds if the directory is empty. Use purge() or deleteRecursive()
	 * to remove a directory and all its contents.
	 *
	 * @param string        $directory The directory path.
	 * @param resource|null $context   Optional stream context.
	 *
	 * @return bool True if the directory was removed.
	 *
	 * @throws RuntimeException If the path exceeds maximum length.
	 */
	public static function remove(string $directory, $context = null): bool
	{
		self::assertPathLength($directory);

		if ($context !== null) {
			return rmdir($directory, $context);
		}

		return rmdir($directory);
	}

	/**
	 * Delete a directory and all of its contents recursively.
	 *
	 * Removes all files and subdirectories within the target, then removes
	 * the directory itself. Processes children first (CHILD_FIRST) to ensure
	 * parent directories are empty before attempting removal.
	 *
	 * @param string $directoryPath The directory to delete.
	 *
	 * @return bool True if the directory and all contents were successfully removed.
	 *
	 * @throws DirectoryIsNotExistsException If the directory does not exist.
	 */
	public static function delete(string $directoryPath): bool
	{
		self::assertDirectoryExists($directoryPath);

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($directoryPath, RecursiveDirectoryIterator::SKIP_DOTS),
			RecursiveIteratorIterator::CHILD_FIRST
		);

		foreach ($iterator as $item) {
			if ($item->isDir()) {
				if (!rmdir($item->getRealPath())) {
					return false;
				}
			} else {
				if (!unlink($item->getRealPath())) {
					return false;
				}
			}
		}

		return rmdir($directoryPath);
	}

	/**
	 * Remove all contents of a directory without removing the directory itself.
	 *
	 * Deletes all files and subdirectories recursively, leaving the target
	 * directory as an empty directory.
	 *
	 * @param string $directoryPath The directory to empty.
	 *
	 * @return bool True if all contents were successfully removed.
	 *
	 * @throws DirectoryIsNotExistsException If the directory does not exist.
	 */
	public static function purge(string $directoryPath): bool
	{
		self::assertDirectoryExists($directoryPath);

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($directoryPath, RecursiveDirectoryIterator::SKIP_DOTS),
			RecursiveIteratorIterator::CHILD_FIRST
		);

		foreach ($iterator as $item) {
			$realPath = $item->getRealPath();

			if ($item->isDir()) {
				if (!rmdir($realPath)) {
					return false;
				}
			} else {
				if (!unlink($realPath)) {
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Remove all contents of a directory (files and subdirectories).
	 *
	 * This is an alias for purge(). The directory itself is preserved.
	 *
	 * @param string $directoryPath The directory to clear.
	 *
	 * @return bool True if all contents were successfully removed.
	 *
	 * @throws DirectoryIsNotExistsException If the directory does not exist.
	 *
	 * @see purge()
	 */
	public static function clear(string $directoryPath): bool
	{
		return self::purge($directoryPath);
	}

	/**
	 * Remove files matching a glob pattern from a directory (non-recursive).
	 *
	 * @param string $directoryPath The directory path.
	 * @param string $pattern       A glob pattern (e.g., '*.log', '*.tmp').
	 *
	 * @return int The number of files removed.
	 *
	 * @throws DirectoryIsNotExistsException If the directory does not exist.
	 */
	public static function removeByPattern(string $directoryPath, string $pattern): int
	{
		self::assertDirectoryExists($directoryPath);

		$fullPattern = rtrim($directoryPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $pattern;
		$files = glob($fullPattern);

		if ($files === false) {
			return 0;
		}

		$removed = 0;
		foreach ($files as $file) {
			if (is_file($file) && unlink($file)) {
				$removed++;
			}
		}

		return $removed;
	}

	/**
	 * Remove files older than a given number of seconds from a directory.
	 *
	 * @param string $directoryPath The directory path.
	 * @param int    $maxAge        Maximum file age in seconds.
	 * @param bool   $recursive     Whether to search subdirectories.
	 *
	 * @return int The number of files removed.
	 *
	 * @throws DirectoryIsNotExistsException If the directory does not exist.
	 */
	public static function removeOlderThan(string $directoryPath, int $maxAge, bool $recursive = false): int
	{
		self::assertDirectoryExists($directoryPath);

		$threshold = time() - $maxAge;
		$removed = 0;

		if ($recursive) {
			$iterator = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator($directoryPath, RecursiveDirectoryIterator::SKIP_DOTS)
			);
		} else {
			$iterator = new DirectoryIterator($directoryPath);
		}

		foreach ($iterator as $file) {
			if ($iterator instanceof DirectoryIterator && $file->isDot()) {
				continue;
			}

			if ($file->isFile() && $file->getMTime() < $threshold) {
				if (unlink($file->getRealPath())) {
					$removed++;
				}
			}
		}

		return $removed;
	}

	// =========================================================================
	// Copy & Move
	// =========================================================================

	/**
	 * Copy a directory and all its contents to a new location recursively.
	 *
	 * Creates the destination directory if it does not exist. Preserves the
	 * directory structure. Files are copied using PHP's native copy().
	 *
	 * @param string $sourcePath      The source directory path.
	 * @param string $destinationPath The destination directory path.
	 *
	 * @return void
	 *
	 * @throws DirectoryIsNotExistsException If the source directory does not exist.
	 * @throws RuntimeException              If a file or directory cannot be copied/created.
	 */
	public static function copy(string $sourcePath, string $destinationPath): void
	{
		self::assertDirectoryExists($sourcePath);

		if (!is_dir($destinationPath)) {
			if (!mkdir($destinationPath, 0755, true)) {
				throw new RuntimeException(sprintf('Failed to create destination directory: %s', $destinationPath));
			}
		}

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($sourcePath, RecursiveDirectoryIterator::SKIP_DOTS),
			RecursiveIteratorIterator::SELF_FIRST
		);

		foreach ($iterator as $item) {
			$destination = $destinationPath . DIRECTORY_SEPARATOR . $iterator->getSubPathname();

			if ($item->isDir()) {
				if (!is_dir($destination) && !mkdir($destination, 0755, true)) {
					throw new RuntimeException(sprintf('Failed to create directory: %s', $destination));
				}
			} else {
				if (!copy($item->getRealPath(), $destination)) {
					throw new RuntimeException(sprintf('Failed to copy file: %s', $item->getRealPath()));
				}
			}
		}
	}

	/**
	 * Move (rename) a directory to a new location.
	 *
	 * If the source and destination are on the same filesystem, this is an
	 * atomic rename. Otherwise, falls back to copy + delete.
	 *
	 * @param string $sourcePath      The current directory path.
	 * @param string $destinationPath The target directory path.
	 *
	 * @return bool True if the move was successful.
	 *
	 * @throws DirectoryIsNotExistsException If the source does not exist.
	 * @throws RuntimeException              If the destination already exists.
	 */
	public static function move(string $sourcePath, string $destinationPath): bool
	{
		self::assertDirectoryExists($sourcePath);

		if (file_exists($destinationPath)) {
			throw new RuntimeException(sprintf('Destination already exists: %s', $destinationPath));
		}

		if (@rename($sourcePath, $destinationPath)) {
			return true;
		}

		self::copy($sourcePath, $destinationPath);
		self::delete($sourcePath);

		return true;
	}

	/**
	 * Mirror a source directory to a destination, syncing contents.
	 *
	 * Copies all files from source to destination, creating directories as needed.
	 * Optionally deletes files in the destination that do not exist in the source.
	 *
	 * @param string $sourcePath      The source directory.
	 * @param string $destinationPath The destination directory.
	 * @param bool   $deleteOrphans   If true, remove destination files not present in source.
	 *
	 * @return void
	 *
	 * @throws DirectoryIsNotExistsException If the source directory does not exist.
	 */
	public static function mirror(string $sourcePath, string $destinationPath, bool $deleteOrphans = false): void
	{
		self::assertDirectoryExists($sourcePath);
		self::ensureExists($destinationPath);

		$sourceIterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($sourcePath, RecursiveDirectoryIterator::SKIP_DOTS),
			RecursiveIteratorIterator::SELF_FIRST
		);

		foreach ($sourceIterator as $item) {
			$target = $destinationPath . DIRECTORY_SEPARATOR . $sourceIterator->getSubPathname();

			if ($item->isDir()) {
				if (!is_dir($target)) {
					mkdir($target, 0755, true);
				}
			} else {
				$sourceTime = $item->getMTime();
				if (!file_exists($target) || filemtime($target) < $sourceTime) {
					copy($item->getRealPath(), $target);
				}
			}
		}

		if ($deleteOrphans) {
			$destIterator = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator($destinationPath, RecursiveDirectoryIterator::SKIP_DOTS),
				RecursiveIteratorIterator::CHILD_FIRST
			);

			foreach ($destIterator as $item) {
				$correspondingSource = $sourcePath . DIRECTORY_SEPARATOR . $destIterator->getSubPathname();

				if (!file_exists($correspondingSource)) {
					if ($item->isDir()) {
						rmdir($item->getRealPath());
					} else {
						unlink($item->getRealPath());
					}
				}
			}
		}
	}

	// =========================================================================
	// Rename
	// =========================================================================

	/**
	 * Rename subdirectories within a directory tree using a regex pattern.
	 *
	 * Iterates all subdirectories and applies preg_replace with the given
	 * pattern and replacement on each directory name.
	 *
	 * @param string $directoryPath The root directory to start from.
	 * @param string $pattern       The regex pattern to match directory names.
	 * @param string $replacement   The replacement string.
	 *
	 * @return bool True if all renames succeeded.
	 *
	 * @throws DirectoryIsNotExistsException If the root directory does not exist.
	 */
	public static function rename(string $directoryPath, string $pattern, string $replacement): bool
	{
		self::assertDirectoryExists($directoryPath);

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($directoryPath, RecursiveDirectoryIterator::SKIP_DOTS),
			RecursiveIteratorIterator::SELF_FIRST
		);

		$renames = [];

		foreach ($iterator as $fileInformation) {
			if (!$fileInformation->isDir()) {
				continue;
			}

			$folderPath = $fileInformation->getPathname();
			$newDirectoryName = preg_replace($pattern, $replacement, $folderPath);

			if ($newDirectoryName === null || $folderPath === $newDirectoryName) {
				continue;
			}

			$renames[$folderPath] = $newDirectoryName;
		}

		krsort($renames);

		foreach ($renames as $oldPath => $newPath) {
			if (!is_dir($oldPath) || file_exists($newPath)) {
				return false;
			}

			if (!rename($oldPath, $newPath)) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Rename files within a directory tree using a regex pattern.
	 *
	 * Applies preg_replace on each filename (not the full path) and renames
	 * matching files to the new name.
	 *
	 * @param string $directoryPath The root directory to scan.
	 * @param string $pattern       The regex pattern to match file names.
	 * @param string $replacement   The replacement string.
	 *
	 * @return bool True if all renames succeeded.
	 *
	 * @throws DirectoryIsNotExistsException If the directory does not exist.
	 */
	public static function renameInnerFiles(string $directoryPath, $pattern, $replacement)
	{
		self::assertDirectoryExists($directoryPath);

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($directoryPath, RecursiveDirectoryIterator::SKIP_DOTS)
		);

		foreach ($iterator as $fileInformation) {
			if ($fileInformation->isDir()) {
				continue;
			}

			$filePath = $fileInformation->getPathname();
			$directory = $fileInformation->getPath();
			$filename = $fileInformation->getFilename();

			$newFilename = preg_replace($pattern, $replacement, $filename);

			if ($newFilename === null || $newFilename === $filename) {
				continue;
			}

			$newPath = $directory . DIRECTORY_SEPARATOR . $newFilename;

			if (file_exists($newPath)) {
				return false;
			}

			if (!rename($filePath, $newPath)) {
				return false;
			}
		}

		return true;
	}

	// =========================================================================
	// Depth Configuration
	// =========================================================================

	/**
	 * Get the currently configured maximum recursion depth.
	 *
	 * A value of -1 means unlimited depth.
	 *
	 * @return int The maximum depth setting.
	 */
	public static function getMaxDepth(): int
	{
		return self::$directoryDepth;
	}

	/**
	 * Set the maximum recursion depth for directory traversal operations.
	 *
	 * Use -1 for unlimited depth (the default). A depth of 0 means only
	 * the specified directory itself, 1 means one level of subdirectories, etc.
	 *
	 * @param int $depth The maximum depth (-1 for unlimited).
	 *
	 * @return void
	 */
	public static function setMaxDepth(int $depth): void
	{
		self::$directoryDepth = $depth;
	}

	// =========================================================================
	// File Listing
	// =========================================================================

	/**
	 * List directory contents with optional filtering by type and extension.
	 *
	 * Supports both flat (non-recursive) and recursive listing. Results can
	 * be filtered by type ('FILE' or 'PATH') and by file extension.
	 *
	 * @param string      $path               The directory to list.
	 * @param string|null $type               Filter type: 'FILE' for files only, 'PATH' for directories only, null for both.
	 * @param bool        $includePath        If true, return full paths; otherwise return names only.
	 * @param bool        $includeSubDirectory If true, recurse into subdirectories.
	 * @param string[]    $extensions         Optional list of file extensions to include (e.g., ['php', 'txt']).
	 *
	 * @return array|false The list of entries, or false on error.
	 *
	 * @throws RuntimeException If the path exceeds the maximum length.
	 */
	public static function getList(string $path = './', ?string $type = null, bool $includePath = false, bool $includeSubDirectory = false, array $extensions = []): array|false
	{
		self::assertPathLength($path);

		$normalizedType = $type !== null ? strtoupper($type) : null;
		$extMap = !empty($extensions) ? array_flip(array_map('strtolower', $extensions)) : null;

		if (!$includeSubDirectory) {
			$entries = scandir($path, SCANDIR_SORT_NONE);

			if ($entries === false) {
				return false;
			}

			$results = [];

			foreach ($entries as $entry) {
				if ($entry === '.' || $entry === '..') {
					continue;
				}

				$fullPath = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $entry;

				if ($normalizedType === 'FILE' && !is_file($fullPath)) {
					continue;
				}

				if ($normalizedType === 'PATH' && !is_dir($fullPath)) {
					continue;
				}

				if ($extMap !== null && is_file($fullPath)) {
					$extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
					if (!isset($extMap[$extension])) {
						continue;
					}
				}

				$results[] = $includePath ? $fullPath : $entry;
			}

			return $results;
		}

		try {
			$iterator = $includeSubDirectory ? new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
				RecursiveIteratorIterator::SELF_FIRST
			) : new DirectoryIterator($path);

			if (self::$directoryDepth !== -1) {
				$iterator->setMaxDepth(self::$directoryDepth);
			}

			$results = [];

			/** @var \SplFileInfo $fileInfo */
			foreach ($iterator as $fileInfo) {
				if ($normalizedType === 'FILE' && !$fileInfo->isFile()) {
					continue;
				}

				if ($normalizedType === 'PATH' && !$fileInfo->isDir()) {
					continue;
				}

				if ($extMap !== null && $fileInfo->isFile()) {
					if (!isset($extMap[strtolower($fileInfo->getExtension())])) {
						continue;
					}
				}

				$results[] = $includePath
					? ($fileInfo->getRealPath() ?: $fileInfo->getPathname())
					: $fileInfo->getFilename();
			}

			return $results;
		} catch (\UnexpectedValueException) {
			return false;
		}
	}

	/**
	 * Get a flat list of all file paths within a directory tree.
	 *
	 * Respects the configured maximum depth. Optionally sorts the result
	 * alphabetically.
	 *
	 * @param string $directoryPath The directory to scan.
	 * @param bool   $sort          If true, sort the file list alphabetically.
	 *
	 * @return string[] Array of absolute file paths.
	 *
	 * @throws DirectoryIsNotExistsException If the directory does not exist.
	 */
	public static function getFileList(string $directoryPath = './', bool $sort = false): array
	{
		self::assertDirectoryExists($directoryPath);

		$fileList = [];

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($directoryPath, RecursiveDirectoryIterator::SKIP_DOTS),
			RecursiveIteratorIterator::CHILD_FIRST
		);

		if (self::$directoryDepth !== -1) {
			$iterator->setMaxDepth(self::$directoryDepth);
		}

		foreach ($iterator as $fileInformation) {
			if (!$fileInformation->isFile()) {
				continue;
			}

			$fileList[] = $fileInformation->getRealPath();
		}

		if ($sort) {
			sort($fileList);
		}

		return $fileList;
	}

	/**
	 * Find files matching a glob pattern within a directory.
	 *
	 * @param string $directoryPath The directory to search in.
	 * @param string $pattern       A glob pattern (e.g., '*.php', 'test_*.txt').
	 * @param bool   $recursive     Whether to search subdirectories.
	 *
	 * @return string[] Array of matching file paths.
	 *
	 * @throws DirectoryIsNotExistsException If the directory does not exist.
	 */
	public static function findByGlob(string $directoryPath, string $pattern, bool $recursive = false): array
	{
		self::assertDirectoryExists($directoryPath);

		if (!$recursive) {
			$fullPattern = rtrim($directoryPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $pattern;
			$results = glob($fullPattern);

			return $results !== false ? $results : [];
		}

		$results = [];
		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($directoryPath, RecursiveDirectoryIterator::SKIP_DOTS)
		);

		foreach ($iterator as $file) {
			if (!$file->isFile()) {
				continue;
			}

			if (fnmatch($pattern, $file->getFilename())) {
				$results[] = $file->getRealPath();
			}
		}

		return $results;
	}

	/**
	 * Find files matching a regex pattern on their filenames.
	 *
	 * @param string $directoryPath The root directory.
	 * @param string $regex         A regex pattern (e.g., '/^test_\d+\.php$/').
	 * @param bool   $recursive     Whether to search subdirectories.
	 *
	 * @return string[] Array of matching file paths.
	 *
	 * @throws DirectoryIsNotExistsException If the directory does not exist.
	 */
	public static function findByRegex(string $directoryPath, string $regex, bool $recursive = false): array
	{
		self::assertDirectoryExists($directoryPath);

		$results = [];

		if ($recursive) {
			$directoryIterator = new RecursiveDirectoryIterator($directoryPath, RecursiveDirectoryIterator::SKIP_DOTS);
			$iterator = new RecursiveIteratorIterator($directoryIterator);
		} else {
			$iterator = new DirectoryIterator($directoryPath);
		}

		foreach ($iterator as $file) {
			if ($iterator instanceof DirectoryIterator && $file->isDot()) {
				continue;
			}

			if (!$file->isFile()) {
				continue;
			}

			if (preg_match($regex, $file->getFilename())) {
				$results[] = $file->getRealPath() ?: $file->getPathname();
			}
		}

		return $results;
	}

	/**
	 * Get the largest files within a directory tree.
	 *
	 * Returns an associative array of file paths => sizes (in bytes),
	 * sorted by size descending, limited to the specified count.
	 *
	 * @param string $directoryPath The directory to scan.
	 * @param int    $limit         Maximum number of files to return.
	 *
	 * @return array<string, int> File path => size map, sorted largest first.
	 *
	 * @throws DirectoryIsNotExistsException If the directory does not exist.
	 */
	public static function getLargestFiles(string $directoryPath, int $limit = 10): array
	{
		self::assertDirectoryExists($directoryPath);

		$files = [];
		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($directoryPath, RecursiveDirectoryIterator::SKIP_DOTS)
		);

		foreach ($iterator as $file) {
			if ($file->isFile()) {
				$files[$file->getRealPath()] = $file->getSize();
			}
		}

		arsort($files);

		return array_slice($files, 0, $limit, true);
	}

	/**
	 * Build a tree-like array representation of a directory structure.
	 *
	 * Each directory entry is an associative array with 'name', 'type' ('dir' or 'file'),
	 * 'path', and for directories a 'children' key containing nested entries.
	 *
	 * @param string $directoryPath The root directory.
	 * @param int    $maxDepth      Maximum traversal depth (-1 for unlimited).
	 * @param int    $currentDepth  Internal counter for current depth (do not pass manually).
	 *
	 * @return array The directory tree structure.
	 *
	 * @throws DirectoryIsNotExistsException If the directory does not exist.
	 */
	public static function getTree(string $directoryPath, int $maxDepth = -1, int $currentDepth = 0): array
	{
		self::assertDirectoryExists($directoryPath);

		$tree = [];
		$iterator = new DirectoryIterator($directoryPath);

		foreach ($iterator as $item) {
			if ($item->isDot()) {
				continue;
			}

			$entry = [
				'name' => $item->getFilename(),
				'type' => $item->isDir() ? 'dir' : 'file',
				'path' => $item->getRealPath() ?: $item->getPathname(),
			];

			if ($item->isDir()) {
				if ($maxDepth === -1 || $currentDepth < $maxDepth) {
					$entry['children'] = self::getTree($item->getRealPath(), $maxDepth, $currentDepth + 1);
				} else {
					$entry['children'] = [];
				}
			} else {
				$entry['size'] = $item->getSize();
			}

			$tree[] = $entry;
		}

		return $tree;
	}

	// =========================================================================
	// Comparison
	// =========================================================================

	/**
	 * Compare two directories and return their differences.
	 *
	 * Walks both directory trees and categorizes files into 'added' (in target
	 * but not source), 'removed' (in source but not target), and 'modified'
	 * (present in both but different sizes or modification times).
	 *
	 * @param string $sourcePath The first (reference) directory.
	 * @param string $targetPath The second (comparison) directory.
	 *
	 * @return array{added: string[], removed: string[], modified: string[]} The diff result.
	 *
	 * @throws DirectoryIsNotExistsException If either directory does not exist.
	 */
	public static function diff(string $sourcePath, string $targetPath): array
	{
		self::assertDirectoryExists($sourcePath);
		self::assertDirectoryExists($targetPath);

		$sourceFiles = self::getRelativeFileMap($sourcePath);
		$targetFiles = self::getRelativeFileMap($targetPath);

		$added = [];
		$removed = [];
		$modified = [];

		foreach ($targetFiles as $relativePath => $targetInfo) {
			if (!isset($sourceFiles[$relativePath])) {
				$added[] = $relativePath;
			} elseif (
				$sourceFiles[$relativePath]['size'] !== $targetInfo['size']
				|| $sourceFiles[$relativePath]['mtime'] !== $targetInfo['mtime']
			) {
				$modified[] = $relativePath;
			}
		}

		foreach ($sourceFiles as $relativePath => $sourceInfo) {
			if (!isset($targetFiles[$relativePath])) {
				$removed[] = $relativePath;
			}
		}

		return [
			'added' => $added,
			'removed' => $removed,
			'modified' => $modified,
		];
	}

	/**
	 * Build a map of relative file paths to their metadata within a directory.
	 *
	 * Used internally by diff() for comparison. Returns an associative array
	 * where keys are relative paths and values contain 'size' and 'mtime'.
	 *
	 * @param string $directoryPath The directory to scan.
	 *
	 * @return array<string, array{size: int, mtime: int}> The file metadata map.
	 */
	private static function getRelativeFileMap(string $directoryPath): array
	{
		$map = [];
		$basePath = realpath($directoryPath);

		if ($basePath === false) {
			return $map;
		}

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($directoryPath, RecursiveDirectoryIterator::SKIP_DOTS)
		);

		foreach ($iterator as $file) {
			if (!$file->isFile()) {
				continue;
			}

			$realPath = $file->getRealPath();
			$relativePath = substr($realPath, strlen($basePath) + 1);

			$map[$relativePath] = [
				'size' => $file->getSize(),
				'mtime' => $file->getMTime(),
			];
		}

		return $map;
	}

	/**
	 * Recursively set permissions on all files and directories within a path.
	 *
	 * @param string $directoryPath    The target directory.
	 * @param int    $directoryPermission Octal permission for directories (e.g., 0755).
	 * @param int    $filePermission      Octal permission for files (e.g., 0644).
	 *
	 * @return bool True if all permission changes succeeded.
	 *
	 * @throws DirectoryIsNotExistsException If the directory does not exist.
	 */
	public static function setPermissionsRecursive(string $directoryPath, int $directoryPermission = 0755, int $filePermission = 0644): bool
	{
		self::assertDirectoryExists($directoryPath);

		if (!chmod($directoryPath, $directoryPermission)) {
			return false;
		}

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($directoryPath, RecursiveDirectoryIterator::SKIP_DOTS),
			RecursiveIteratorIterator::SELF_FIRST
		);

		foreach ($iterator as $item) {
			$perm = $item->isDir() ? $directoryPermission : $filePermission;

			if (!chmod($item->getRealPath(), $perm)) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Extract the root component from a filesystem path.
	 *
	 * Handles Unix absolute paths ('/'), Windows drive letters ('C:\'),
	 * and URI schemes ('file://'). Returns an empty string for relative paths.
	 *
	 * @param string $path The path to analyze.
	 *
	 * @return string The root component (e.g., '/', 'C:/', 'file:///'), or '' for relative paths.
	 */
	public static function getRoot(string $path): string
	{
		if ('' === $path) {
			return '';
		}

		$scheme = '';
		$schemeSeparatorPosition = strpos($path, '://');

		if ($schemeSeparatorPosition !== false) {
			$scheme = substr($path, 0, $schemeSeparatorPosition + 3);
			$path = substr($path, $schemeSeparatorPosition + 3);
		}

		if ($path === '' || $path === false) {
			return $scheme;
		}

		$firstCharacter = $path[0];

		if ('/' === $firstCharacter || '\\' === $firstCharacter) {
			return $scheme . '/';
		}

		$length = strlen($path);

		if ($length > 1 && ':' === $path[1] && ctype_alpha($firstCharacter)) {
			if (2 === $length) {
				return $scheme . $path . '/';
			}

			if ('/' === $path[2] || '\\' === $path[2]) {
				return $scheme . $firstCharacter . $path[1] . '/';
			}
		}

		return '';
	}

	/**
	 * Determine whether a path is absolute.
	 *
	 * Recognizes Unix absolute paths, Windows drive-letter paths, and URI schemes.
	 *
	 * @param string $path The path to check.
	 *
	 * @return bool True if the path is absolute.
	 */
	public static function isAbsolute(string $path): bool
	{
		if ('' === $path) {
			return false;
		}

		$schemeSeparatorPosition = strpos($path, '://');
		if ($schemeSeparatorPosition !== false && $schemeSeparatorPosition !== 1) {
			$path = substr($path, $schemeSeparatorPosition + 3);
		}

		if ($path === '' || $path === false) {
			return false;
		}

		$firstCharacter = $path[0];

		if ('/' === $firstCharacter || '\\' === $firstCharacter) {
			return true;
		}

		if (strlen($path) > 1 && ctype_alpha($firstCharacter) && ':' === $path[1]) {
			if (2 === strlen($path)) {
				return true;
			}

			if ('/' === $path[2] || '\\' === $path[2]) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check whether a path is a local filesystem path (no URI scheme).
	 *
	 * @param string $path The path to check.
	 *
	 * @return bool True if the path has no URI scheme component.
	 */
	public static function isLocal(string $path): bool
	{
		return '' !== $path && strpos($path, '://') === false;
	}

	/**
	 * Normalize a path by resolving '..' and '.' segments and standardizing separators.
	 *
	 * Does not access the filesystem — operates purely on the string.
	 * Trailing separators are preserved only for root paths.
	 *
	 * @param string $path The path to normalize.
	 *
	 * @return string The normalized path.
	 */
	public static function normalize(string $path): string
	{
		$path = str_replace('\\', '/', $path);

		$root = '';
		if (str_starts_with($path, '/')) {
			$root = '/';
			$path = substr($path, 1);
		} elseif (strlen($path) > 2 && ctype_alpha($path[0]) && $path[1] === ':' && $path[2] === '/') {
			$root = substr($path, 0, 3);
			$path = substr($path, 3);
		}

		$parts = explode('/', $path);
		$resolved = [];

		foreach ($parts as $part) {
			if ($part === '' || $part === '.') {
				continue;
			}

			if ($part === '..') {
				if (!empty($resolved) && end($resolved) !== '..') {
					array_pop($resolved);
				} elseif ($root === '') {
					$resolved[] = '..';
				}
				continue;
			}

			$resolved[] = $part;
		}

		return $root . implode('/', $resolved);
	}

	/**
	 * Remove all files from a directory, preserving subdirectory structure.
	 *
	 * Unlike purge(), this only removes files and keeps empty subdirectories.
	 * Unlike the old empty() method, this correctly handles all edge cases
	 * and has an unambiguous name.
	 *
	 * @param string $directoryPath The directory to clear files from.
	 *
	 * @return bool True if all files were removed successfully.
	 *
	 * @throws DirectoryIsNotExistsException If the directory does not exist.
	 */
	public static function clearFiles(string $directoryPath): bool
	{
		self::assertDirectoryExists($directoryPath);

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($directoryPath, RecursiveDirectoryIterator::SKIP_DOTS),
			RecursiveIteratorIterator::CHILD_FIRST
		);

		if (self::$directoryDepth !== -1) {
			$iterator->setMaxDepth(self::$directoryDepth);
		}

		foreach ($iterator as $fileInformation) {
			if ($fileInformation->isDir()) {
				continue;
			}

			if (!unlink($fileInformation->getRealPath())) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Compute the relative path from one directory to another.
	 *
	 * @param string $from The starting directory path.
	 * @param string $to   The target path.
	 *
	 * @return string The relative path from $from to $to.
	 */
	public static function getRelativePath(string $from, string $to): string
	{
		$from = self::normalize($from);
		$to = self::normalize($to);

		$fromParts = $from !== '' ? explode('/', $from) : [];
		$toParts = $to !== '' ? explode('/', $to) : [];

		$commonLength = 0;
		$maxCommon = min(count($fromParts), count($toParts));

		while ($commonLength < $maxCommon && $fromParts[$commonLength] === $toParts[$commonLength]) {
			$commonLength++;
		}

		$upCount = count($fromParts) - $commonLength;
		$remaining = array_slice($toParts, $commonLength);

		$relative = array_merge(array_fill(0, $upCount, '..'), $remaining);

		return implode('/', $relative) ?: '.';
	}

	/**
	 * Concatenate multiple path components into a single normalized path.
	 *
	 * Removes redundant separators and uses forward slashes consistently.
	 * Empty segments are skipped; the first segment keeps any leading separator.
	 *
	 * @param string ...$parts Path segments to join.
	 *
	 * @return string The joined path.
	 */
	public static function join(string ...$parts): string
	{
		$cleaned = [];
		foreach ($parts as $i => $part) {
			if ($part === '') {
				continue;
			}
			$part = str_replace('\\', '/', $part);
			if ($i === 0) {
				$cleaned[] = rtrim($part, '/');
			} else {
				$cleaned[] = trim($part, '/');
			}
		}

		$cleaned = array_filter($cleaned, static fn(string $p): bool => $p !== '');

		if (empty($cleaned)) {
			return '';
		}

		$first = reset($cleaned);
		$joined = implode('/', $cleaned);

		// Restore a leading slash if the first segment originally started with one.
		if ($first === '' && strlen($joined) > 0 && $joined[0] !== '/') {
			$joined = '/' . $joined;
		}

		return $joined;
	}

	/**
	 * Append the system directory separator to a path if it is not already present.
	 *
	 * @param string $path The path to modify.
	 *
	 * @return string The path with a trailing separator.
	 */
	public static function withTrailingSeparator(string $path): string
	{
		if ($path === '') {
			return DIRECTORY_SEPARATOR;
		}

		$last = $path[strlen($path) - 1];
		if ($last === '/' || $last === '\\') {
			return $path;
		}

		return $path . DIRECTORY_SEPARATOR;
	}

	/**
	 * Strip trailing directory separators from a path while preserving root paths.
	 *
	 * @param string $path The path to modify.
	 *
	 * @return string The path without a trailing separator.
	 */
	public static function withoutTrailingSeparator(string $path): string
	{
		if ($path === '' || $path === '/' || $path === '\\') {
			return $path;
		}

		$trimmed = rtrim($path, '/\\');
		return $trimmed !== '' ? $trimmed : '/';
	}

	/**
	 * Get the base name (last component) of a path.
	 *
	 * @param string $path The path.
	 *
	 * @return string The trailing name component.
	 */
	public static function getBasename(string $path): string
	{
		return basename($path);
	}

	/**
	 * Get the parent directory of a path.
	 *
	 * @param string $path   The path.
	 * @param int    $levels The number of parent directories to ascend (default 1).
	 *
	 * @return string The parent directory path.
	 */
	public static function getName(string $path, int $levels = 1): string
	{
		return dirname($path, $levels);
	}

	/**
	 * Decompose a path into its directory, base name, file name, and extension.
	 *
	 * @param string $path The path to split.
	 *
	 * @return array{dirname: string, basename: string, filename: string, extension: string} The decomposed path.
	 */
	public static function splitPath(string $path): array
	{
		$info = pathinfo($path);

		return [
			'dirname'   => $info['dirname']   ?? '',
			'basename'  => $info['basename']  ?? '',
			'filename'  => $info['filename']  ?? '',
			'extension' => $info['extension'] ?? '',
		];
	}

	/**
	 * Find the longest path prefix shared by all given paths.
	 *
	 * Paths are normalized before comparison; the result has no trailing slash.
	 *
	 * @param string[] $paths The paths to compare.
	 *
	 * @return string The common path prefix, or '' if none.
	 */
	public static function commonPath(array $paths): string
	{
		if (count($paths) === 0) {
			return '';
		}

		$normalized = array_map(static fn(string $p): string => self::normalize($p), $paths);
		$common = explode('/', $normalized[0]);

		foreach ($normalized as $path) {
			$parts = explode('/', $path);
			$index = 0;
			$max = min(count($common), count($parts));

			while ($index < $max && $common[$index] === $parts[$index]) {
				$index++;
			}

			$common = array_slice($common, 0, $index);

			if (empty($common)) {
				return '';
			}
		}

		return implode('/', $common);
	}

	/**
	 * Count the number of segments in a normalized path.
	 *
	 * Returns 0 for empty paths and root-only paths ('/' or 'C:/').
	 *
	 * @param string $path The path.
	 *
	 * @return int The segment count.
	 */
	public static function getDepth(string $path): int
	{
		$normalized = trim(self::normalize($path), '/');

		if ($normalized === '') {
			return 0;
		}

		// Drop a Windows drive prefix if present so it is not counted as a segment.
		if (strlen($normalized) > 1 && ctype_alpha($normalized[0]) && $normalized[1] === ':') {
			$normalized = substr($normalized, 2);
			$normalized = trim($normalized, '/');
			if ($normalized === '') {
				return 0;
			}
		}

		return substr_count($normalized, '/') + 1;
	}

	/**
	 * Determine whether a file or directory name is hidden (starts with a dot).
	 *
	 * @param string $path The path or base name to check.
	 *
	 * @return bool True if the base name starts with '.' (and is not '.' or '..').
	 */
	public static function isHidden(string $path): bool
	{
		$base = basename($path);

		return $base !== '' && $base !== '.' && $base !== '..' && $base[0] === '.';
	}

	/**
	 * Determine whether a path represents a filesystem root (e.g., '/' or 'C:/').
	 *
	 * @param string $path The path.
	 *
	 * @return bool True if the path is a root.
	 */
	public static function isRoot(string $path): bool
	{
		$normalized = self::normalize($path);
		$root = self::getRoot($normalized);

		return $normalized !== '' && $root !== '' && rtrim($normalized, '/') === rtrim($root, '/');
	}

	/**
	 * Determine whether a child path is the same as or located beneath a parent path.
	 *
	 * Both arguments are normalized; no filesystem access is performed.
	 *
	 * @param string $parent The candidate parent directory.
	 * @param string $child  The candidate child path.
	 *
	 * @return bool True if $child is the same as or located under $parent.
	 */
	public static function pathContains(string $parent, string $child): bool
	{
		$parent = self::normalize($parent);
		$child = self::normalize($child);

		if ($parent === '' || $child === '') {
			return false;
		}

		if ($parent === $child) {
			return true;
		}

		return str_starts_with($child, rtrim($parent, '/') . '/');
	}

	/**
	 * Strip control characters and platform-reserved characters from a path string.
	 *
	 * Useful for sanitizing user-supplied path components before joining them
	 * onto a known-safe base directory.
	 *
	 * @param string $path The path to sanitize.
	 *
	 * @return string The sanitized path.
	 */
	public static function sanitize(string $path): string
	{
		$clean = preg_replace('/[\x00-\x1F\x7F]/u', '', $path) ?? '';
		$clean = preg_replace('/[<>:"|?*]/u', '', $clean) ?? '';

		return trim($clean);
	}

	/**
	 * Count subdirectories within a path, optionally recursively.
	 *
	 * @param string $directoryPath The directory path.
	 * @param bool   $recursive     Whether to recurse into nested subdirectories.
	 *
	 * @return int The number of subdirectories.
	 *
	 * @throws DirectoryIsNotExistsException If the directory does not exist.
	 */
	public static function getDirectoryCount(string $directoryPath, bool $recursive = false): int
	{
		self::assertDirectoryExists($directoryPath);

		if (!$recursive) {
			return self::getSubdirectoryCount($directoryPath);
		}

		$count = 0;
		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($directoryPath, RecursiveDirectoryIterator::SKIP_DOTS),
			RecursiveIteratorIterator::SELF_FIRST
		);

		foreach ($iterator as $item) {
			if ($item->isDir()) {
				$count++;
			}
		}

		return $count;
	}

	/**
	 * Count all entries (files plus directories) in a directory.
	 *
	 * @param string $directoryPath The directory path.
	 * @param bool   $recursive     Whether to recurse into subdirectories.
	 *
	 * @return int The total number of entries.
	 *
	 * @throws DirectoryIsNotExistsException If the directory does not exist.
	 */
	public static function getEntryCount(string $directoryPath, bool $recursive = false): int
	{
		self::assertDirectoryExists($directoryPath);

		if ($recursive) {
			$iterator = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator($directoryPath, RecursiveDirectoryIterator::SKIP_DOTS)
			);

			return iterator_count($iterator);
		}

		$count = 0;
		foreach (new DirectoryIterator($directoryPath) as $item) {
			if (!$item->isDot()) {
				$count++;
			}
		}

		return $count;
	}

	/**
	 * Find the maximum nesting depth of a directory tree.
	 *
	 * Returns 0 if the directory contains no subdirectories.
	 *
	 * @param string $directoryPath The root directory.
	 *
	 * @return int The deepest nesting depth.
	 *
	 * @throws DirectoryIsNotExistsException If the directory does not exist.
	 */
	public static function getDeepestDepth(string $directoryPath): int
	{
		self::assertDirectoryExists($directoryPath);

		$maxDepth = 0;
		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($directoryPath, RecursiveDirectoryIterator::SKIP_DOTS),
			RecursiveIteratorIterator::SELF_FIRST
		);

		foreach ($iterator as $item) {
			$depth = $iterator->getDepth() + 1;
			if ($depth > $maxDepth) {
				$maxDepth = $depth;
			}
		}

		return $maxDepth;
	}

	/**
	 * Build a comprehensive statistics summary for a directory tree.
	 *
	 * @param string $directoryPath The root directory.
	 *
	 * @return array{
	 * 	files: int, 
	 * 	directories: int, 
	 * 	totalSize: int, 
	 * 	maxDepth: int, 
	 * 	largestFile: ?string, 
	 * 	oldest: ?int, 
	 * 	newest: ?int
	 * }
	 *
	 * @throws DirectoryIsNotExistsException If the directory does not exist.
	 */
	public static function getStats(string $directoryPath): array
	{
		self::assertDirectoryExists($directoryPath);

		$files = 0;
		$directories = 0;
		$totalSize = 0;
		$maxDepth = 0;
		$largestPath = null;
		$largestSize = -1;
		$oldest = null;
		$newest = null;

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($directoryPath, RecursiveDirectoryIterator::SKIP_DOTS),
			RecursiveIteratorIterator::SELF_FIRST
		);

		foreach ($iterator as $item) {
			$depth = $iterator->getDepth() + 1;
			if ($depth > $maxDepth) {
				$maxDepth = $depth;
			}

			if ($item->isDir()) {
				$directories++;
				continue;
			}

			if (!$item->isFile()) {
				continue;
			}

			$files++;
			$size = $item->getSize();
			$totalSize += $size;

			if ($size > $largestSize) {
				$largestSize = $size;
				$largestPath = $item->getRealPath() ?: $item->getPathname();
			}

			$mtime = $item->getMTime();
			if ($oldest === null || $mtime < $oldest) {
				$oldest = $mtime;
			}
			if ($newest === null || $mtime > $newest) {
				$newest = $mtime;
			}
		}

		return [
			'files'       => $files,
			'directories' => $directories,
			'totalSize'   => $totalSize,
			'maxDepth'    => $maxDepth,
			'largestFile' => $largestPath,
			'oldest'      => $oldest,
			'newest'      => $newest,
		];
	}

	/**
	 * Build a histogram of file extensions found within a directory tree.
	 *
	 * Files without an extension are grouped under the empty string ''.
	 *
	 * @param string $directoryPath The root directory.
	 *
	 * @return array<string, int> Lowercase extension => count, sorted by count descending.
	 *
	 * @throws DirectoryIsNotExistsException If the directory does not exist.
	 */
	public static function getExtensionStats(string $directoryPath): array
	{
		self::assertDirectoryExists($directoryPath);

		$stats = [];
		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($directoryPath, RecursiveDirectoryIterator::SKIP_DOTS)
		);

		foreach ($iterator as $file) {
			if (!$file->isFile()) {
				continue;
			}

			$ext = strtolower($file->getExtension());
			$stats[$ext] = ($stats[$ext] ?? 0) + 1;
		}

		arsort($stats);

		return $stats;
	}

	/**
	 * Get the inode-change (creation) time of a directory.
	 *
	 * On most Unix filesystems this is the metadata change time;
	 * on Windows it is the actual creation timestamp.
	 *
	 * @param string $directoryPath The directory path.
	 *
	 * @return int The Unix timestamp.
	 *
	 * @throws DirectoryIsNotExistsException If the directory does not exist.
	 * @throws RuntimeException              If the timestamp cannot be retrieved.
	 */
	public static function getCreationTime(string $directoryPath): int
	{
		self::assertDirectoryExists($directoryPath);

		$time = filectime($directoryPath);
		if ($time === false) {
			throw new RuntimeException(sprintf('Failed to get creation time for: %s', $directoryPath));
		}

		return $time;
	}

	/**
	 * Get the owner of a directory, preferring a user name when posix is available.
	 *
	 * @param string $directoryPath The directory path.
	 *
	 * @return string|int|false User name, numeric UID, or false on failure.
	 */
	public static function getOwner(string $directoryPath): string|int|false
	{
		if (!is_dir($directoryPath)) {
			return false;
		}

		$uid = fileowner($directoryPath);
		if ($uid === false) {
			return false;
		}

		if (function_exists('posix_getpwuid')) {
			$info = posix_getpwuid($uid);
			if (is_array($info) && isset($info['name'])) {
				return $info['name'];
			}
		}

		return $uid;
	}

	/**
	 * Get the group of a directory, preferring a group name when posix is available.
	 *
	 * @param string $directoryPath The directory path.
	 *
	 * @return string|int|false Group name, numeric GID, or false on failure.
	 */
	public static function getGroup(string $directoryPath): string|int|false
	{
		if (!is_dir($directoryPath)) {
			return false;
		}

		$gid = filegroup($directoryPath);
		if ($gid === false) {
			return false;
		}

		if (function_exists('posix_getgrgid')) {
			$info = posix_getgrgid($gid);
			if (is_array($info) && isset($info['name'])) {
				return $info['name'];
			}
		}

		return $gid;
	}

	/**
	 * Format a byte count as a human-readable size string (e.g., "12.34 MB").
	 *
	 * @param int $bytes     The number of bytes.
	 * @param int $precision Number of decimal places (default 2).
	 *
	 * @return string A human-readable representation.
	 */
	public static function formatSize(int $bytes, int $precision = 2): string
	{
		if ($bytes < 0) {
			return '-' . self::formatSize(-$bytes, $precision);
		}

		$units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB'];
		$index = 0;
		$value = (float) $bytes;

		while ($value >= 1024 && $index < count($units) - 1) {
			$value /= 1024;
			$index++;
		}

		return sprintf('%.' . $precision . 'f %s', $value, $units[$index]);
	}

	/**
	 * List subdirectories within a directory.
	 *
	 * @param string $directoryPath The directory to scan.
	 * @param bool   $recursive     Whether to recurse into nested subdirectories.
	 *
	 * @return string[] Subdirectory paths.
	 *
	 * @throws DirectoryIsNotExistsException If the directory does not exist.
	 */
	public static function getSubdirectories(string $directoryPath, bool $recursive = false): array
	{
		self::assertDirectoryExists($directoryPath);

		$results = [];

		if ($recursive) {
			$iterator = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator($directoryPath, RecursiveDirectoryIterator::SKIP_DOTS),
				RecursiveIteratorIterator::SELF_FIRST
			);

			foreach ($iterator as $item) {
				if ($item->isDir() && !$item->isLink()) {
					$results[] = $item->getRealPath() ?: $item->getPathname();
				}
			}
		} else {
			foreach (new DirectoryIterator($directoryPath) as $item) {
				if (!$item->isDot() && $item->isDir()) {
					$results[] = $item->getRealPath() ?: $item->getPathname();
				}
			}
		}

		return $results;
	}

	/**
	 * List file paths within a directory.
	 *
	 * Unlike getFileList(), this method offers an explicit recursion flag
	 * defaulting to non-recursive.
	 *
	 * @param string $directoryPath The directory to scan.
	 * @param bool   $recursive     Whether to recurse into subdirectories.
	 *
	 * @return string[] File paths.
	 *
	 * @throws DirectoryIsNotExistsException If the directory does not exist.
	 */
	public static function getFiles(string $directoryPath, bool $recursive = false): array
	{
		self::assertDirectoryExists($directoryPath);

		$results = [];

		if ($recursive) {
			$iterator = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator($directoryPath, RecursiveDirectoryIterator::SKIP_DOTS)
			);

			foreach ($iterator as $item) {
				if ($item->isFile()) {
					$results[] = $item->getRealPath() ?: $item->getPathname();
				}
			}
		} else {
			foreach (new DirectoryIterator($directoryPath) as $item) {
				if (!$item->isDot() && $item->isFile()) {
					$results[] = $item->getRealPath() ?: $item->getPathname();
				}
			}
		}

		return $results;
	}

	/**
	 * List files whose extensions match the given allowlist.
	 *
	 * Extension comparison is case-insensitive.
	 *
	 * @param string   $directoryPath The directory to scan.
	 * @param string[] $extensions    Acceptable extensions (without leading dots).
	 * @param bool     $recursive     Whether to recurse into subdirectories.
	 *
	 * @return string[] Matching file paths.
	 *
	 * @throws DirectoryIsNotExistsException If the directory does not exist.
	 */
	public static function getFilesByExtension(string $directoryPath, array $extensions, bool $recursive = false): array
	{
		self::assertDirectoryExists($directoryPath);

		$extMap = array_flip(array_map(static fn(string $e): string => strtolower(ltrim($e, '.')), $extensions));
		$results = [];
		$iterator = self::buildIterator($directoryPath, $recursive);

		foreach ($iterator as $item) {
			if ($iterator instanceof DirectoryIterator && $item->isDot()) {
				continue;
			}

			if (!$item->isFile()) {
				continue;
			}

			if (!isset($extMap[strtolower($item->getExtension())])) {
				continue;
			}

			$results[] = $item->getRealPath() ?: $item->getPathname();
		}

		return $results;
	}

	/**
	 * List files whose size falls within an inclusive range.
	 *
	 * @param string   $directoryPath The directory to scan.
	 * @param int      $minSize       Minimum size in bytes (inclusive).
	 * @param int|null $maxSize       Maximum size in bytes, or null for no upper bound.
	 * @param bool     $recursive     Whether to recurse into subdirectories.
	 *
	 * @return string[] Matching file paths.
	 *
	 * @throws DirectoryIsNotExistsException If the directory does not exist.
	 */
	public static function getFilesBySize(string $directoryPath, int $minSize, ?int $maxSize = null, bool $recursive = false): array
	{
		self::assertDirectoryExists($directoryPath);

		$results = [];
		$iterator = self::buildIterator($directoryPath, $recursive);

		foreach ($iterator as $item) {
			if ($iterator instanceof DirectoryIterator && $item->isDot()) {
				continue;
			}

			if (!$item->isFile()) {
				continue;
			}

			$size = $item->getSize();
			if ($size < $minSize) {
				continue;
			}
			if ($maxSize !== null && $size > $maxSize) {
				continue;
			}

			$results[] = $item->getRealPath() ?: $item->getPathname();
		}

		return $results;
	}

	/**
	 * List files whose modification time falls within a given range.
	 *
	 * @param string   $directoryPath The directory to scan.
	 * @param int      $since         Lower-bound (inclusive) Unix timestamp.
	 * @param int|null $until         Upper-bound (inclusive) Unix timestamp, or null for no upper bound.
	 * @param bool     $recursive     Whether to recurse into subdirectories.
	 *
	 * @return string[] Matching file paths.
	 *
	 * @throws DirectoryIsNotExistsException If the directory does not exist.
	 */
	public static function getFilesByModifiedTime(string $directoryPath, int $since, ?int $until = null, bool $recursive = false): array
	{
		self::assertDirectoryExists($directoryPath);

		$results = [];
		$iterator = self::buildIterator($directoryPath, $recursive);

		foreach ($iterator as $item) {
			if ($iterator instanceof DirectoryIterator && $item->isDot()) {
				continue;
			}

			if (!$item->isFile()) {
				continue;
			}

			$mtime = $item->getMTime();
			if ($mtime < $since || ($until !== null && $mtime > $until)) {
				continue;
			}

			$results[] = $item->getRealPath() ?: $item->getPathname();
		}

		return $results;
	}

	/**
	 * Find subdirectories that contain no files or further subdirectories.
	 *
	 * @param string $directoryPath The directory to scan.
	 * @param bool   $recursive     Whether to look inside nested subdirectories.
	 *
	 * @return string[] Paths of empty subdirectories.
	 *
	 * @throws DirectoryIsNotExistsException If the directory does not exist.
	 */
	public static function getEmptySubdirectories(string $directoryPath, bool $recursive = true): array
	{
		self::assertDirectoryExists($directoryPath);

		$results = [];

		if ($recursive) {
			$iterator = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator($directoryPath, RecursiveDirectoryIterator::SKIP_DOTS),
				RecursiveIteratorIterator::SELF_FIRST
			);

			foreach ($iterator as $item) {
				if (!$item->isDir()) {
					continue;
				}

				$path = $item->getRealPath() ?: $item->getPathname();
				if (self::isEmpty($path)) {
					$results[] = $path;
				}
			}
		} else {
			foreach (new DirectoryIterator($directoryPath) as $item) {
				if ($item->isDot() || !$item->isDir()) {
					continue;
				}

				$path = $item->getRealPath() ?: $item->getPathname();
				if (self::isEmpty($path)) {
					$results[] = $path;
				}
			}
		}

		return $results;
	}

	/**
	 * List hidden files (those whose name starts with a dot).
	 *
	 * @param string $directoryPath The directory to scan.
	 * @param bool   $recursive     Whether to recurse into subdirectories.
	 *
	 * @return string[] Hidden file paths.
	 *
	 * @throws DirectoryIsNotExistsException If the directory does not exist.
	 */
	public static function getHiddenFiles(string $directoryPath, bool $recursive = false): array
	{
		self::assertDirectoryExists($directoryPath);

		$results = [];
		$iterator = self::buildIterator($directoryPath, $recursive);

		foreach ($iterator as $item) {
			if ($iterator instanceof DirectoryIterator && $item->isDot()) {
				continue;
			}

			if (!$item->isFile()) {
				continue;
			}

			$name = $item->getFilename();
			if ($name !== '' && $name[0] === '.') {
				$results[] = $item->getRealPath() ?: $item->getPathname();
			}
		}

		return $results;
	}

	/**
	 * Get the smallest files in a directory tree, sorted by size ascending.
	 *
	 * @param string $directoryPath The directory to scan.
	 * @param int    $limit         Maximum number of entries to return.
	 *
	 * @return array<string, int> Map of file path => size in bytes.
	 *
	 * @throws DirectoryIsNotExistsException If the directory does not exist.
	 */
	public static function getSmallestFiles(string $directoryPath, int $limit = 10): array
	{
		self::assertDirectoryExists($directoryPath);

		$files = [];
		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($directoryPath, RecursiveDirectoryIterator::SKIP_DOTS)
		);

		foreach ($iterator as $file) {
			if ($file->isFile()) {
				$files[$file->getRealPath() ?: $file->getPathname()] = $file->getSize();
			}
		}

		asort($files);

		return array_slice($files, 0, $limit, true);
	}

	/**
	 * Get the oldest files (by mtime) in a directory tree.
	 *
	 * @param string $directoryPath The directory to scan.
	 * @param int    $limit         Maximum number of entries to return.
	 *
	 * @return array<string, int> Map of file path => modification timestamp.
	 *
	 * @throws DirectoryIsNotExistsException If the directory does not exist.
	 */
	public static function getOldestFiles(string $directoryPath, int $limit = 10): array
	{
		self::assertDirectoryExists($directoryPath);

		$files = [];
		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($directoryPath, RecursiveDirectoryIterator::SKIP_DOTS)
		);

		foreach ($iterator as $file) {
			if ($file->isFile()) {
				$files[$file->getRealPath() ?: $file->getPathname()] = $file->getMTime();
			}
		}

		asort($files);

		return array_slice($files, 0, $limit, true);
	}

	/**
	 * Get the most recently modified files in a directory tree.
	 *
	 * @param string $directoryPath The directory to scan.
	 * @param int    $limit         Maximum number of entries to return.
	 *
	 * @return array<string, int> Map of file path => modification timestamp.
	 *
	 * @throws DirectoryIsNotExistsException If the directory does not exist.
	 */
	public static function getNewestFiles(string $directoryPath, int $limit = 10): array
	{
		self::assertDirectoryExists($directoryPath);

		$files = [];
		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($directoryPath, RecursiveDirectoryIterator::SKIP_DOTS)
		);

		foreach ($iterator as $file) {
			if ($file->isFile()) {
				$files[$file->getRealPath() ?: $file->getPathname()] = $file->getMTime();
			}
		}

		arsort($files);

		return array_slice($files, 0, $limit, true);
	}

	/**
	 * Build a SPL iterator over a directory's entries without materializing them in memory.
	 *
	 * Useful for iterating very large trees lazily.
	 *
	 * @param string $directoryPath The directory to iterate.
	 * @param bool   $recursive     Whether to recurse into subdirectories.
	 *
	 * @return \Iterator The iterator.
	 *
	 * @throws DirectoryIsNotExistsException If the directory does not exist.
	 */
	public static function getIterator(string $directoryPath, bool $recursive = false): \Iterator
	{
		self::assertDirectoryExists($directoryPath);

		return self::buildIterator($directoryPath, $recursive);
	}

	/**
	 * Internal helper that produces a flat or recursive iterator with shared semantics.
	 *
	 * @param string $directoryPath The directory to iterate.
	 * @param bool   $recursive     Whether to recurse into subdirectories.
	 *
	 * @return \Iterator The iterator.
	 */
	private static function buildIterator(string $directoryPath, bool $recursive): \Iterator
	{
		if ($recursive) {
			// SELF_FIRST so that callers' predicates can match both files and directories.
			return new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator($directoryPath, RecursiveDirectoryIterator::SKIP_DOTS),
				RecursiveIteratorIterator::SELF_FIRST
			);
		}

		return new DirectoryIterator($directoryPath);
	}

	/**
	 * Apply a callback to each entry of a directory.
	 *
	 * The callback receives an SplFileInfo. Returning false stops iteration early.
	 *
	 * @param string   $directoryPath The directory to iterate.
	 * @param callable $callback      function(SplFileInfo $entry): mixed
	 * @param bool     $recursive     Whether to recurse into subdirectories.
	 *
	 * @return void
	 *
	 * @throws DirectoryIsNotExistsException If the directory does not exist.
	 */
	public static function iterate(string $directoryPath, callable $callback, bool $recursive = false): void
	{
		self::assertDirectoryExists($directoryPath);

		$iterator = self::buildIterator($directoryPath, $recursive);

		foreach ($iterator as $item) {
			if ($iterator instanceof DirectoryIterator && $item->isDot()) {
				continue;
			}

			if ($callback($item) === false) {
				return;
			}
		}
	}

	/**
	 * Walk a directory tree recursively, invoking a callback for every entry.
	 *
	 * Convenience alias for iterate() with recursive=true.
	 *
	 * @param string   $directoryPath The directory to walk.
	 * @param callable $callback      function(SplFileInfo $entry): mixed
	 *
	 * @return void
	 *
	 * @throws DirectoryIsNotExistsException If the directory does not exist.
	 */
	public static function walk(string $directoryPath, callable $callback): void
	{
		self::iterate($directoryPath, $callback, true);
	}

	/**
	 * Find the first entry matching a predicate.
	 *
	 * @param string   $directoryPath The directory to search.
	 * @param callable $predicate     function(SplFileInfo $entry): bool
	 * @param bool     $recursive     Whether to recurse into subdirectories.
	 *
	 * @return string|null The path of the first match, or null if none found.
	 *
	 * @throws DirectoryIsNotExistsException If the directory does not exist.
	 */
	public static function findFirst(string $directoryPath, callable $predicate, bool $recursive = false): ?string
	{
		self::assertDirectoryExists($directoryPath);

		$iterator = self::buildIterator($directoryPath, $recursive);

		foreach ($iterator as $item) {
			if ($iterator instanceof DirectoryIterator && $item->isDot()) {
				continue;
			}

			if ($predicate($item)) {
				return $item->getRealPath() ?: $item->getPathname();
			}
		}

		return null;
	}

	/**
	 * Find every entry matching a predicate.
	 *
	 * @param string   $directoryPath The directory to search.
	 * @param callable $predicate     function(SplFileInfo $entry): bool
	 * @param bool     $recursive     Whether to recurse into subdirectories.
	 *
	 * @return string[] Matching paths.
	 *
	 * @throws DirectoryIsNotExistsException If the directory does not exist.
	 */
	public static function findAll(string $directoryPath, callable $predicate, bool $recursive = false): array
	{
		self::assertDirectoryExists($directoryPath);

		$results = [];
		$iterator = self::buildIterator($directoryPath, $recursive);

		foreach ($iterator as $item) {
			if ($iterator instanceof DirectoryIterator && $item->isDot()) {
				continue;
			}

			if ($predicate($item)) {
				$results[] = $item->getRealPath() ?: $item->getPathname();
			}
		}

		return $results;
	}

	/**
	 * Check whether a file with the given exact base name exists in a directory.
	 *
	 * @param string $directoryPath The directory to search.
	 * @param string $filename      The exact filename (basename) to look for.
	 * @param bool   $recursive     Whether to recurse into subdirectories.
	 *
	 * @return bool True if a matching file is found.
	 *
	 * @throws DirectoryIsNotExistsException If the directory does not exist.
	 */
	public static function containsFile(string $directoryPath, string $filename, bool $recursive = false): bool
	{
		self::assertDirectoryExists($directoryPath);

		$iterator = self::buildIterator($directoryPath, $recursive);

		foreach ($iterator as $item) {
			if ($iterator instanceof DirectoryIterator && $item->isDot()) {
				continue;
			}

			if ($item->isFile() && $item->getFilename() === $filename) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check whether the directory contains at least one file with the given extension.
	 *
	 * @param string $directoryPath The directory to search.
	 * @param string $extension     File extension (case-insensitive, with or without leading dot).
	 * @param bool   $recursive     Whether to recurse into subdirectories.
	 *
	 * @return bool True if at least one matching file exists.
	 *
	 * @throws DirectoryIsNotExistsException If the directory does not exist.
	 */
	public static function containsExtension(string $directoryPath, string $extension, bool $recursive = false): bool
	{
		self::assertDirectoryExists($directoryPath);

		$target = strtolower(ltrim($extension, '.'));
		$iterator = self::buildIterator($directoryPath, $recursive);

		foreach ($iterator as $item) {
			if ($iterator instanceof DirectoryIterator && $item->isDot()) {
				continue;
			}

			if ($item->isFile() && strtolower($item->getExtension()) === $target) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Search file contents within a directory tree for a textual needle.
	 *
	 * Files are read with file_get_contents(); large files may be expensive to scan.
	 *
	 * @param string   $directoryPath  The directory to search.
	 * @param string   $needle         Substring to look for.
	 * @param string[] $extensions     Optional whitelist of extensions to scan.
	 * @param bool     $caseSensitive  Whether the match is case-sensitive.
	 *
	 * @return string[] Paths of files whose contents contain the needle.
	 *
	 * @throws DirectoryIsNotExistsException If the directory does not exist.
	 */
	public static function searchByContent(string $directoryPath, string $needle, array $extensions = [], bool $caseSensitive = true): array
	{
		self::assertDirectoryExists($directoryPath);

		$results = [];
		$extMap = !empty($extensions)
			? array_flip(array_map(static fn(string $e): string => strtolower(ltrim($e, '.')), $extensions))
			: null;

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($directoryPath, RecursiveDirectoryIterator::SKIP_DOTS)
		);

		foreach ($iterator as $file) {
			if (!$file->isFile()) {
				continue;
			}

			if ($extMap !== null && !isset($extMap[strtolower($file->getExtension())])) {
				continue;
			}

			$contents = @file_get_contents($file->getRealPath() ?: $file->getPathname());
			if ($contents === false) {
				continue;
			}

			$found = $caseSensitive
				? str_contains($contents, $needle)
				: stripos($contents, $needle) !== false;

			if ($found) {
				$results[] = $file->getRealPath() ?: $file->getPathname();
			}
		}

		return $results;
	}

	/**
	 * Quickly determine whether a directory contains at least one file.
	 *
	 * @param string $directoryPath The directory to inspect.
	 * @param bool   $recursive     Whether to recurse into subdirectories.
	 *
	 * @return bool True if the directory contains a file.
	 *
	 * @throws DirectoryIsNotExistsException If the directory does not exist.
	 */
	public static function hasFiles(string $directoryPath, bool $recursive = false): bool
	{
		self::assertDirectoryExists($directoryPath);

		$iterator = self::buildIterator($directoryPath, $recursive);

		foreach ($iterator as $item) {
			if ($iterator instanceof DirectoryIterator && $item->isDot()) {
				continue;
			}

			if ($item->isFile()) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Quickly determine whether a directory has at least one immediate subdirectory.
	 *
	 * @param string $directoryPath The directory to inspect.
	 *
	 * @return bool True if a subdirectory is present.
	 *
	 * @throws DirectoryIsNotExistsException If the directory does not exist.
	 */
	public static function hasSubdirectories(string $directoryPath): bool
	{
		self::assertDirectoryExists($directoryPath);

		foreach (new DirectoryIterator($directoryPath) as $item) {
			if (!$item->isDot() && $item->isDir()) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Determine whether a path is a symbolic link.
	 *
	 * @param string $path The path to test.
	 *
	 * @return bool True if the path is a symlink.
	 */
	public static function isSymlink(string $path): bool
	{
		return is_link($path);
	}

	/**
	 * Create a symbolic link pointing to the given target.
	 *
	 * Fails if the link path already exists.
	 *
	 * @param string $target The destination the link should point to.
	 * @param string $link   The symlink path to create.
	 *
	 * @return bool True on success, false on failure.
	 *
	 * @throws RuntimeException If either path exceeds the maximum length.
	 */
	public static function createSymlink(string $target, string $link): bool
	{
		self::assertPathLength($target);
		self::assertPathLength($link);

		if (file_exists($link) || is_link($link)) {
			return false;
		}

		return @symlink($target, $link);
	}

	/**
	 * Read the destination of a symbolic link.
	 *
	 * @param string $path The symlink path.
	 *
	 * @return string|false The target path, or false on failure.
	 */
	public static function readSymlink(string $path): string|false
	{
		if (!is_link($path)) {
			return false;
		}

		return readlink($path);
	}

	/**
	 * Remove a symbolic link without following it.
	 *
	 * @param string $path The symlink path.
	 *
	 * @return bool True if the link was removed.
	 */
	public static function removeSymlink(string $path): bool
	{
		if (!is_link($path)) {
			return false;
		}

		return @unlink($path);
	}

	/**
	 * Get all symbolic links under a directory.
	 *
	 * @param string $directoryPath The directory to scan.
	 * @param bool   $recursive     Whether to recurse into subdirectories.
	 *
	 * @return string[] Symlink paths.
	 *
	 * @throws DirectoryIsNotExistsException If the directory does not exist.
	 */
	public static function getSymlinks(string $directoryPath, bool $recursive = false): array
	{
		self::assertDirectoryExists($directoryPath);

		$results = [];
		$iterator = self::buildIterator($directoryPath, $recursive);

		foreach ($iterator as $item) {
			if ($iterator instanceof DirectoryIterator && $item->isDot()) {
				continue;
			}

			if ($item->isLink()) {
				$results[] = $item->getPathname();
			}
		}

		return $results;
	}

	/**
	 * Compute a single hash representing the entire content of a directory tree.
	 *
	 * Each file's relative path and content hash are combined deterministically;
	 * two directories with identical layout and content produce the same digest.
	 *
	 * @param string $directoryPath The directory to hash.
	 * @param string $algo          Hash algorithm (default 'sha256').
	 *
	 * @return string The aggregate hex digest.
	 *
	 * @throws DirectoryIsNotExistsException If the directory does not exist.
	 */
	public static function getHash(string $directoryPath, string $algo = 'sha256'): string
	{
		self::assertDirectoryExists($directoryPath);

		$hashes = self::getFileHashes($directoryPath, $algo);
		ksort($hashes);

		$context = hash_init($algo);
		foreach ($hashes as $relativePath => $hash) {
			hash_update($context, $relativePath . "\0" . $hash . "\0");
		}

		return hash_final($context);
	}

	/**
	 * Compute a hash for every file in a directory tree.
	 *
	 * Returns a map of relative file paths (with forward-slash separators
	 * for cross-platform stability) to their hex digests.
	 *
	 * @param string $directoryPath The directory to scan.
	 * @param string $algo          Hash algorithm (default 'sha256').
	 *
	 * @return array<string, string> Map of relative path => hex digest.
	 *
	 * @throws DirectoryIsNotExistsException If the directory does not exist.
	 */
	public static function getFileHashes(string $directoryPath, string $algo = 'sha256'): array
	{
		self::assertDirectoryExists($directoryPath);

		$base = realpath($directoryPath);
		if ($base === false) {
			return [];
		}

		$results = [];
		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($directoryPath, RecursiveDirectoryIterator::SKIP_DOTS)
		);

		foreach ($iterator as $file) {
			if (!$file->isFile()) {
				continue;
			}

			$real = $file->getRealPath();
			if ($real === false) {
				continue;
			}

			$relative = str_replace('\\', '/', substr($real, strlen($base) + 1));
			$hash = hash_file($algo, $real);
			if ($hash !== false) {
				$results[$relative] = $hash;
			}
		}

		return $results;
	}

	/**
	 * Remove subdirectories that contain no entries.
	 *
	 * In recursive mode, leaf directories are removed first so that newly
	 * empty parents are cleaned up in the same pass.
	 *
	 * @param string $directoryPath The directory to clean.
	 * @param bool   $recursive     Whether to recursively prune empty subdirectories.
	 *
	 * @return int The number of subdirectories removed.
	 *
	 * @throws DirectoryIsNotExistsException If the directory does not exist.
	 */
	public static function removeEmptySubdirectories(string $directoryPath, bool $recursive = true): int
	{
		self::assertDirectoryExists($directoryPath);

		$removed = 0;

		if ($recursive) {
			$iterator = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator($directoryPath, RecursiveDirectoryIterator::SKIP_DOTS),
				RecursiveIteratorIterator::CHILD_FIRST
			);

			foreach ($iterator as $item) {
				if (!$item->isDir()) {
					continue;
				}

				$path = $item->getPathname();
				if (self::isEmpty($path) && @rmdir($path)) {
					$removed++;
				}
			}
		} else {
			foreach (new DirectoryIterator($directoryPath) as $item) {
				if ($item->isDot() || !$item->isDir()) {
					continue;
				}

				$path = $item->getPathname();
				if (self::isEmpty($path) && @rmdir($path)) {
					$removed++;
				}
			}
		}

		return $removed;
	}

	/**
	 * Remove files whose size lies within an inclusive range.
	 *
	 * @param string   $directoryPath The directory to clean.
	 * @param int      $minSize       Minimum size in bytes (inclusive).
	 * @param int|null $maxSize       Maximum size in bytes, or null for no upper bound.
	 * @param bool     $recursive     Whether to recurse into subdirectories.
	 *
	 * @return int The number of files removed.
	 *
	 * @throws DirectoryIsNotExistsException If the directory does not exist.
	 */
	public static function removeBySize(string $directoryPath, int $minSize, ?int $maxSize = null, bool $recursive = false): int
	{
		$matches = self::getFilesBySize($directoryPath, $minSize, $maxSize, $recursive);
		$removed = 0;

		foreach ($matches as $path) {
			if (@unlink($path)) {
				$removed++;
			}
		}

		return $removed;
	}

	/**
	 * Remove files whose extension is in the given list.
	 *
	 * @param string   $directoryPath The directory to clean.
	 * @param string[] $extensions    Extensions to delete (case-insensitive).
	 * @param bool     $recursive     Whether to recurse into subdirectories.
	 *
	 * @return int The number of files removed.
	 *
	 * @throws DirectoryIsNotExistsException If the directory does not exist.
	 */
	public static function removeByExtension(string $directoryPath, array $extensions, bool $recursive = false): int
	{
		$matches = self::getFilesByExtension($directoryPath, $extensions, $recursive);
		$removed = 0;

		foreach ($matches as $path) {
			if (@unlink($path)) {
				$removed++;
			}
		}

		return $removed;
	}

	/**
	 * Remove files modified more recently than a given threshold.
	 *
	 * Counterpart of removeOlderThan(): targets fresh files instead of stale ones.
	 *
	 * @param string $directoryPath The directory to clean.
	 * @param int    $minAge        Minimum age in seconds; files younger than this are removed.
	 * @param bool   $recursive     Whether to recurse into subdirectories.
	 *
	 * @return int The number of files removed.
	 *
	 * @throws DirectoryIsNotExistsException If the directory does not exist.
	 */
	public static function removeNewerThan(string $directoryPath, int $minAge, bool $recursive = false): int
	{
		self::assertDirectoryExists($directoryPath);

		$threshold = time() - $minAge;
		$removed = 0;
		$iterator = self::buildIterator($directoryPath, $recursive);

		foreach ($iterator as $file) {
			if ($iterator instanceof DirectoryIterator && $file->isDot()) {
				continue;
			}

			if ($file->isFile() && $file->getMTime() >= $threshold) {
				if (@unlink($file->getRealPath() ?: $file->getPathname())) {
					$removed++;
				}
			}
		}

		return $removed;
	}

	/**
	 * Remove hidden files (those whose name starts with a dot).
	 *
	 * @param string $directoryPath The directory to clean.
	 * @param bool   $recursive     Whether to recurse into subdirectories.
	 *
	 * @return int The number of files removed.
	 *
	 * @throws DirectoryIsNotExistsException If the directory does not exist.
	 */
	public static function removeHidden(string $directoryPath, bool $recursive = false): int
	{
		$matches = self::getHiddenFiles($directoryPath, $recursive);
		$removed = 0;

		foreach ($matches as $path) {
			if (@unlink($path)) {
				$removed++;
			}
		}

		return $removed;
	}

	/**
	 * Move every file from a directory tree into a single destination directory.
	 *
	 * Subdirectory names are inlined into the resulting filename using the given
	 * separator to avoid collisions (e.g. 'a/b/c.txt' -> 'a_b_c.txt').
	 *
	 * @param string $sourceDir  The directory to flatten.
	 * @param string $destDir    The destination directory (created if missing).
	 * @param string $separator  Separator used between path segments in the new filename.
	 *
	 * @return int The number of files moved.
	 *
	 * @throws DirectoryIsNotExistsException If the source directory does not exist.
	 */
	public static function flatten(string $sourceDir, string $destDir, string $separator = '_'): int
	{
		self::assertDirectoryExists($sourceDir);
		self::ensureExists($destDir);

		$moved = 0;
		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($sourceDir, RecursiveDirectoryIterator::SKIP_DOTS)
		);

		foreach ($iterator as $file) {
			if (!$file->isFile()) {
				continue;
			}

			$relative = str_replace(['/', '\\'], $separator, $iterator->getSubPathname());
			$target = rtrim($destDir, '/\\') . DIRECTORY_SEPARATOR . $relative;

			if (@rename($file->getRealPath() ?: $file->getPathname(), $target)) {
				$moved++;
			}
		}

		return $moved;
	}

	/**
	 * Reorganize files into subdirectories grouped by file extension.
	 *
	 * Files without extensions are placed in a '_no_ext' subdirectory.
	 *
	 * @param string $sourceDir Directory containing files to reorganize.
	 * @param string $destDir   Target directory (created if missing).
	 *
	 * @return int The number of files moved.
	 *
	 * @throws DirectoryIsNotExistsException If the source directory does not exist.
	 */
	public static function splitByExtension(string $sourceDir, string $destDir): int
	{
		self::assertDirectoryExists($sourceDir);
		self::ensureExists($destDir);

		$moved = 0;
		foreach (new DirectoryIterator($sourceDir) as $item) {
			if ($item->isDot() || !$item->isFile()) {
				continue;
			}

			$ext = strtolower($item->getExtension());
			$bucket = $ext !== '' ? $ext : '_no_ext';
			$bucketDir = rtrim($destDir, '/\\') . DIRECTORY_SEPARATOR . $bucket;
			self::ensureExists($bucketDir);

			$target = $bucketDir . DIRECTORY_SEPARATOR . $item->getFilename();
			if (@rename($item->getRealPath() ?: $item->getPathname(), $target)) {
				$moved++;
			}
		}

		return $moved;
	}

	/**
	 * Reorganize files into subdirectories grouped by their modification date.
	 *
	 * @param string $sourceDir Directory containing files to reorganize.
	 * @param string $destDir   Target directory (created if missing).
	 * @param string $format    A date() format used to derive the bucket name (default 'Y-m').
	 *
	 * @return int The number of files moved.
	 *
	 * @throws DirectoryIsNotExistsException If the source directory does not exist.
	 */
	public static function splitByDate(string $sourceDir, string $destDir, string $format = 'Y-m'): int
	{
		self::assertDirectoryExists($sourceDir);
		self::ensureExists($destDir);

		$moved = 0;
		foreach (new DirectoryIterator($sourceDir) as $item) {
			if ($item->isDot() || !$item->isFile()) {
				continue;
			}

			$bucket = date($format, $item->getMTime());
			$bucketDir = rtrim($destDir, '/\\') . DIRECTORY_SEPARATOR . $bucket;
			self::ensureExists($bucketDir);

			$target = $bucketDir . DIRECTORY_SEPARATOR . $item->getFilename();
			if (@rename($item->getRealPath() ?: $item->getPathname(), $target)) {
				$moved++;
			}
		}

		return $moved;
	}

	/**
	 * Acquire an exclusive advisory lock for a directory using a lock file.
	 *
	 * The returned handle must be passed back to releaseLock(). The lock is
	 * automatically released when the handle is closed (e.g., at script end).
	 *
	 * @param string $directoryPath The directory to lock.
	 * @param string $name          Lock file name (default '.lock').
	 *
	 * @return resource|false The open lock-file handle on success, false on failure.
	 *
	 * @throws DirectoryIsNotExistsException If the directory does not exist.
	 */
	public static function acquireLock(string $directoryPath, string $name = '.lock'): mixed
	{
		self::assertDirectoryExists($directoryPath);

		$lockPath = rtrim($directoryPath, '/\\') . DIRECTORY_SEPARATOR . $name;
		$handle = @fopen($lockPath, 'c');
		if ($handle === false) {
			return false;
		}

		if (!flock($handle, LOCK_EX | LOCK_NB)) {
			fclose($handle);
			return false;
		}

		return $handle;
	}

	/**
	 * Release a lock previously acquired with acquireLock().
	 *
	 * @param resource $handle        The handle returned by acquireLock().
	 * @param string   $directoryPath The directory whose lock is being released.
	 * @param string   $name          Lock file name (default '.lock').
	 *
	 * @return bool True on successful release.
	 */
	public static function releaseLock(mixed $handle, string $directoryPath, string $name = '.lock'): bool
	{
		if (!is_resource($handle)) {
			return false;
		}

		$released = flock($handle, LOCK_UN);
		fclose($handle);

		$lockPath = rtrim($directoryPath, '/\\') . DIRECTORY_SEPARATOR . $name;
		if (file_exists($lockPath)) {
			@unlink($lockPath);
		}

		return $released;
	}

	/**
	 * Check whether a directory is currently locked by an acquireLock() handle.
	 *
	 * Probes the lock file with a non-blocking exclusive lock attempt.
	 *
	 * @param string $directoryPath The directory to check.
	 * @param string $name          Lock file name (default '.lock').
	 *
	 * @return bool True if the lock cannot currently be acquired.
	 */
	public static function isLocked(string $directoryPath, string $name = '.lock'): bool
	{
		$lockPath = rtrim($directoryPath, '/\\') . DIRECTORY_SEPARATOR . $name;
		if (!file_exists($lockPath)) {
			return false;
		}

		$handle = @fopen($lockPath, 'r');
		if ($handle === false) {
			return true;
		}

		$locked = !flock($handle, LOCK_EX | LOCK_NB);
		if (!$locked) {
			flock($handle, LOCK_UN);
		}
		fclose($handle);

		return $locked;
	}

	/**
	 * Create a timestamped backup copy of a directory.
	 *
	 * The backup is placed inside $backupDir; if no name is given the
	 * destination is "{source basename}_{Ymd_His}".
	 *
	 * @param string      $sourceDir The directory to back up.
	 * @param string      $backupDir Parent directory for the backup (created if missing).
	 * @param string|null $name      Optional override for the backup directory name.
	 *
	 * @return string The absolute path of the new backup directory.
	 *
	 * @throws DirectoryIsNotExistsException If the source directory does not exist.
	 * @throws RuntimeException              If the backup target already exists.
	 */
	public static function backup(string $sourceDir, string $backupDir, ?string $name = null): string
	{
		self::assertDirectoryExists($sourceDir);
		self::ensureExists($backupDir);

		$name ??= basename(rtrim($sourceDir, '/\\')) . '_' . date('Ymd_His');
		$target = rtrim($backupDir, '/\\') . DIRECTORY_SEPARATOR . $name;

		if (file_exists($target)) {
			throw new RuntimeException(sprintf('Backup target already exists: %s', $target));
		}

		self::copy($sourceDir, $target);

		return $target;
	}

	/**
	 * Update the modification (and optionally access) timestamps of a directory.
	 *
	 * @param string   $directoryPath The directory whose timestamps to update.
	 * @param int|null $time          New modification time (default: current time).
	 * @param int|null $atime         New access time (default: same as $time).
	 *
	 * @return bool True on success.
	 *
	 * @throws DirectoryIsNotExistsException If the directory does not exist.
	 */
	public static function touch(string $directoryPath, ?int $time = null, ?int $atime = null): bool
	{
		self::assertDirectoryExists($directoryPath);

		$time = $time ?? time();
		$atime = $atime ?? $time;

		return touch($directoryPath, $time, $atime);
	}

	/**
	 * Quick check whether two directories differ in aggregate size or file count.
	 *
	 * Lighter than diff(): only compares totals, not individual files.
	 *
	 * @param string $a The first directory.
	 * @param string $b The second directory.
	 *
	 * @return bool True if the directories differ in total size or file count.
	 *
	 * @throws DirectoryIsNotExistsException If either directory does not exist.
	 */
	public static function differs(string $a, string $b): bool
	{
		self::assertDirectoryExists($a);
		self::assertDirectoryExists($b);

		return self::getSize($a) !== self::getSize($b) || self::getFileCountRecursive($a) !== self::getFileCountRecursive($b);
	}

	/**
	 * Determine whether two paths reference the same canonical filesystem location.
	 *
	 * @param string $a The first path.
	 * @param string $b The second path.
	 *
	 * @return bool True if both resolve to the same real path.
	 */
	public static function isSamePath(string $a, string $b): bool
	{
		$realA = realpath($a);
		$realB = realpath($b);

		return $realA !== false && $realB !== false && $realA === $realB;
	}

	/**
	 * Clear the PHP filesystem stat cache, optionally for a single path only.
	 *
	 * Useful after performing bulk operations whose results stat() may have cached.
	 *
	 * @param string|null $path Optional specific path to clear from the cache.
	 *
	 * @return void
	 */
	public static function clearStatCache(?string $path = null): void
	{
		if ($path !== null) {
			clearstatcache(true, $path);
		} else {
			clearstatcache(true);
		}
	}

	/**
	 * Get the path of the most recently modified file in a directory tree.
	 *
	 * @param string $directoryPath The directory to scan.
	 * @param bool   $recursive     Whether to recurse into subdirectories.
	 *
	 * @return string|null Path of the newest file, or null if the directory contains no files.
	 *
	 * @throws DirectoryIsNotExistsException If the directory does not exist.
	 */
	public static function lastModifiedFile(string $directoryPath, bool $recursive = true): ?string
	{
		self::assertDirectoryExists($directoryPath);

		$newestPath = null;
		$newestMtime = null;
		$iterator = self::buildIterator($directoryPath, $recursive);

		foreach ($iterator as $file) {
			if ($iterator instanceof DirectoryIterator && $file->isDot()) {
				continue;
			}

			if (!$file->isFile()) {
				continue;
			}

			$mtime = $file->getMTime();
			if ($newestMtime === null || $mtime > $newestMtime) {
				$newestMtime = $mtime;
				$newestPath = $file->getRealPath() ?: $file->getPathname();
			}
		}

		return $newestPath;
	}

	/**
	 * Get the path of the first file encountered in a directory (non-recursive).
	 *
	 * Useful for quick existence-style probes when only one file is expected.
	 *
	 * @param string $directoryPath The directory to scan.
	 *
	 * @return string|null The first file path, or null if the directory has no files.
	 *
	 * @throws DirectoryIsNotExistsException If the directory does not exist.
	 */
	public static function firstFile(string $directoryPath): ?string
	{
		self::assertDirectoryExists($directoryPath);

		foreach (new DirectoryIterator($directoryPath) as $item) {
			if (!$item->isDot() && $item->isFile()) {
				return $item->getRealPath() ?: $item->getPathname();
			}
		}

		return null;
	}

	#endregion
}
