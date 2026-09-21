<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\File;

use Clover\Classes\Data\ArrayObject;
use Clover\Classes\Data\StringObject;
use Clover\Classes\Directory\Handler as DirectoryHandler;
use Clover\Classes\Event\AsyncPromise;
use Clover\Classes\File\Functions as FileFunctions;
use Clover\Classes\FileSystem\Handler as FileSystemHandler;
use Clover\Enumeration\FileMode;
use Clover\Implement\{DirectoryHandlerInterface, FileHandlerInterface};

/**
 * File handler
 */
class Handler implements FileHandlerInterface
{
	protected $useStatFunction = [
		'stat',
		'lstat',
		'file_exists',
		'is_writable',
		'is_readable',
		'is_executable',
		'is_file',
		'is_dir',
		'is_link',
		'filectime',
		'fileatime',
		'filemtime',
		'fileinode',
		'filegroup',
		'fileowner',
		'filesize',
		'filetype',
		'fileperms'
	];

	// Last catched error
	private static $lastError;

	// Handler of file system
	private $fileSystemHandler;

	// Handler of directory system
	private $directoryHandler;

	public function __construct(?FileHandlerInterface $fileSystemHandler = null, ?DirectoryHandlerInterface $directoryHandler = null)
	{
		$this->fileSystemHandler = isset($fileSystemHandler) ? $fileSystemHandler : new FileSystemHandler();
		$this->directoryHandler = isset($directoryHandler) ? $directoryHandler : new DirectoryHandler($this);
	}

	public static function generateHashByContents($algorithm = 'md5', $filePath = '', $rawContents = null): string
	{
		return FileFunctions::generateHashByContents($algorithm, $filePath, $rawContents);
	}

	public static function readFileAsync(string $filename): AsyncPromise
	{
		return FileFunctions::readFileAsync($filename);
	}

	public static function writeFileAsync(string $filename, string $content): AsyncPromise
	{
		return FileFunctions::writeFileAsync($filename, $content);
	}

	public static function readBytes($handler, $n): string
	{
		return FileFunctions::readBytes($handler, $n);
	}

	public function getStandardErrorStream($mode = FileMode::READ_ONLY)
	{
		return FileFunctions::getStandardErrorStream($mode);
	}

	public static function getStandardOutputStream($mode = FileMode::READ_ONLY)
	{
		return FileFunctions::getStandardOutputStream($mode);
	}

	public static function getStandardInputStream($mode = FileMode::READ_ONLY)
	{
		return FileFunctions::getStandardInputStream($mode);
	}

	public static function getFilterStream($mode = 'rb')
	{
		return FileFunctions::getFilterStream($mode);
	}

	public static function getTemporaryStream($mode = 'rb')
	{
		return FileFunctions::getTemporaryStream($mode);
	}

	public static function getMemoryStream($mode = 'rb')
	{
		return FileFunctions::getMemoryStream($mode);
	}

	public static function getInputStream($mode = 'rb')
	{
		return FileFunctions::getInputStream($mode);
	}

	public static function getOutputStream($mode = FileMode::WRITE_ONLY)
	{
		return FileFunctions::getOutputStream($mode);
	}

	public static function isEqual($firstPath, $secondPath, $chunkSize = 500): bool
	{
		return FileFunctions::isEqual($firstPath, $secondPath, $chunkSize);
	}

	public static function open(?string $filePath = null, ?string $mode = null, $use_include_path = false)
	{
		return FileFunctions::open($filePath, $mode, $use_include_path);
	}

	public static function closeProcess($processResource): int
	{
		return FileFunctions::closeProcess($processResource);
	}

	public static function openProcess(?string $processPath = null, $mode = FileMode::WRITE_ONLY)
	{
		return FileFunctions::openProcess($processPath, $mode);
	}

	public static function clearStatusCache(string $filePath): void
	{
		FileFunctions::clearStatusCache($filePath);
	}

	public static function convertToNomalizePath($filePath): string
	{
		return FileFunctions::convertToNomalizePath($filePath);
	}

	public static function isValidHandler($fileHandler): bool
	{
		return FileFunctions::isValidHandler($fileHandler);
	}

	public static function getPointerLocation($fileHandler): bool|int
	{
		return FileFunctions::getPointerLocation($fileHandler);
	}

	public static function createCache(string $filePath, string $destination): bool
	{
		return FileFunctions::createCache($filePath, $destination);
	}

	public static function isReadable(string $filePath): bool
	{
		return FileFunctions::isReadable($filePath);
	}

	public static function parseINI(string $filePath): array|bool
	{
		return FileFunctions::parseINI($filePath);
	}

	public static function getMIMEContentTypeFromMagicMIME($filePath): bool|string
	{
		return FileFunctions::getMIMEContentTypeFromMagicMIME($filePath);
	}

	public static function getMIMEContentTypeFromAlaMimetypeExtension($filePath): bool|string
	{
		return FileFunctions::getMIMEContentTypeFromAlaMimetypeExtension($filePath);
	}

	public static function getMIMEContentType($filePath): bool|string|null
	{
		return FileFunctions::getMIMEContentType($filePath);
	}

	public static function isLocked($filePath): bool
	{
		return FileFunctions::isLocked($filePath);
	}

	public static function getLine($fileHandler, int $length): string
	{
		return FileFunctions::getLine($fileHandler, $length);
	}

	public static function getCharacter($fileHandler, int $length): string
	{
		return FileFunctions::getCharacter($fileHandler);
	}

	public static function getPermission($filePath): int
	{
		return fileperms($filePath);
	}

	public static function getPermissionCode($filePath): string
	{
		$permission = self::getPermission($filePath);

		return substr(sprintf('%o', $permission), -4);
	}

	public static function getOwner($filePath): int
	{
		return fileowner($filePath);
	}

	public static function createTemporary()
	{
		return FileFunctions::createTemporary();
	}

	public function createUniqueTemporary($directory, $prefix): bool|string
	{
		return FileFunctions::createUniqueTemporary($directory, $prefix);
	}

	public static function setAccessAndModificatinTime($filePath, $time, $atime): bool
	{
		return FileFunctions::setAccessAndModificatinTime($filePath, $time, $atime);
	}

	public static function unlock($fileHandler): void
	{
		FileFunctions::unlock($fileHandler);
	}

	public function setPermission(string $filePath, int $mode): bool
	{
		return FileFunctions::setPermission($filePath, $mode);
	}

	public function changeGroup(string $filePath, string $group): bool
	{
		return FileFunctions::changeGroup($filePath, $group);
	}

	public static function lock($fileHandler, $mode = FileMode::READ_ONLY): bool
	{
		return FileFunctions::lock($fileHandler, $mode);
	}

	public static function isEmpty(string $filePath): bool
	{
		return FileFunctions::isEmpty($filePath);
	}

	public static function isExists(string $filePath): bool
	{
		return FileFunctions::isExists($filePath);
	}

	public static function isUnknownFile(string $filePath): bool
	{
		return FileFunctions::isUnknownFile($filePath);
	}

	public function getSymbolicLink(string $symbolicLink): bool|string
	{
		return FileFunctions::getSymbolicLink($symbolicLink);
	}

	public static function isSymbolicLink(string $filePath): bool
	{
		return FileFunctions::isSymbolicLink($filePath);
	}

	public static function isRegularFile(string $filePath): bool
	{
		return FileFunctions::isRegularFile($filePath);
	}

	public static function isContainFolder(string $basePath, string $filePath): bool
	{
		return FileFunctions::isContainFolder($basePath, $filePath);
	}

	public function getFileEncoding(?string $filePath = null): string
	{
		return FileFunctions::getFileEncoding($filePath);
	}

	public static function isFile(?string $filePath = null, ?string $containDirectory = null): bool
	{
		return FileFunctions::isFile($filePath, $containDirectory);
	}

	public static function isEqualByLine(?string $filePath = null, ?string $string = null): bool
	{
		return FileFunctions::isEqualByLine($filePath, $string);
	}

	public static function isExecutable(?string $filePath = null): bool|string|null
	{
		return FileFunctions::isExecutable($filePath);
	}

	public static function isWritable(?string $filePath = null): bool
	{
		return FileFunctions::isWritable($filePath);
	}

	public static function delete(?string $filePath = null): bool
	{
		return FileFunctions::delete($filePath);
	}

	public static function getSize(?string $filePath = null, bool $humanReadable = false, ?string $type = NULL): int|string
	{
		return FileFunctions::getSize($filePath, $humanReadable, $type);
	}

	public static function copy(string $filePath, string $destination): bool
	{
		return FileFunctions::copy($filePath, $destination);
	}

	public static function merge(string $filePath, string $mergeFile): bool
	{
		return FileFunctions::merge($filePath, $mergeFile);
	}

	public static function changeUmask($mask): int
	{
		return FileFunctions::changeUmask($mask);
	}

	public static function getHeaderType(string $filePath): string|bool
	{
		return FileFunctions::getHeaderType($filePath);
	}

	public static function readCsvFile(string $filePath, $separator): array
	{
		return FileFunctions::readCsvFile($filePath, $separator);
	}

	public static function read(string $filePath, int $length = -1, string $mode = FileMode::READ_ONLY): bool|StringObject
	{
		return FileFunctions::read($filePath, $length, $mode);
	}

	public static function computeFileContent($a, $b): ArrayObject
	{
		return FileFunctions::computeFileContent($a, $b);
	}

	public static function readAllContent(string $filePath, string $writeMode = FileMode::READ_ONLY): mixed
	{
		return FileFunctions::readAllContent($filePath, $writeMode);
	}

	public static function write(?string $filePath = null, ?string $content = null, string $mode = 'w'): bool
	{
		return FileFunctions::write($filePath, $content, $mode);
	}

	public static function appendContent(?string $filePath = null, ?string $content = null, bool $stream = false, bool $overwrite = true): bool
	{
		return FileFunctions::appendContent($filePath, $content, $stream, $overwrite);
	}

	public static function getLastAccessDate($filePath): bool|int
	{
		return FileFunctions::getLastAccessDate($filePath);
	}

	public static function getCreatedDate($filePath): bool|int|string
	{
		return FileFunctions::getCreatedDate($filePath);
	}

	public static function getLastModifiedTime(string $filePath): bool|int|string
	{
		return FileFunctions::getLastModifiedTime($filePath);
	}

	public static function getType(string $filePath): string
	{
		return FileFunctions::getType($filePath);
	}

	public static function reverseContent(?string $filePath = null): bool
	{
		return FileFunctions::reverseContent($filePath);
	}

	public static function getBasename(string $fileName, $extension = ""): string
	{
		return FileFunctions::getBasename($fileName, $extension);
	}

	public static function getExtensionByFilePath(string $filePath): string
	{
		return FileFunctions::getExtensionByFilePath($filePath);
	}

	public static function getExtension(string $filePath): string
	{
		return FileFunctions::getExtension($filePath);
	}

	public static function getContent(string $filePath): string
	{
		return FileFunctions::getContent($filePath);
	}

	public static function download(string $filePath, int $bufferSize = 0): bool
	{
		return FileFunctions::download($filePath, $bufferSize);
	}

	public static function isCorrectInode($filePath): bool
	{
		return FileFunctions::isCorrectInode($filePath);
	}

	public static function getInode(string $filePath): bool|int
	{
		return FileFunctions::getInode($filePath);
	}

	public static function getInterpretedContent(string $filePath): string
	{
		return FileFunctions::getInterpretedContent($filePath);
	}

	public static function requireOnce(string $filePath): void
	{
		FileFunctions::requireOnce($filePath);
	}

	public static function move(string $source, string $destination): bool
	{
		return FileFunctions::move($source, $destination);
	}
}
