<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Upload;

use Clover\Classes\Data\StringHandler;
use Clover\Classes\File\Handler as FileHandler;
use Clover\Classes\Format\MultiPurposeInternetMailExtensions;
use Clover\Classes\OperationSystem;
use Clover\Enumeration\{UploadedFile, UploadedFileError, UploadedFileErrorMessage};
use function sprintf;
use function in_array;
use function count;
use function array_key_exists;
use Exception;

/**
 * Class Handler
 * 
 * Provides methods to handle file uploads.
 */
class Handler
{
	// Common executable file extensions
	private const EXECUTABLE_EXTENSIONS = [
		'exe',
		'sh',
		'bin',
		'out',
		'elf',
		'bat',
		'cmd',
		'com',
		'msi',
		'ps1',
		'vbs',
		'wsf',
		'cpl',
		'scr',
		'pif',
		'hta',
		'jar',
		'app',
		'action',
		'command',
		'workflow',
		'csh',
		'ksh',
		'run',
		'apk',
		'ipa',
	];

	// Common image MIME types
	private const IMAGE_MIME_TYPES = [
		'image/jpeg',
		'image/png',
		'image/gif',
		'image/webp',
		'image/bmp',
		'image/tiff',
		'image/svg+xml',
		'image/x-icon',
	];

	// Common image file extensions
	private const IMAGE_EXTENSIONS = [
		'jpg',
		'jpeg',
		'png',
		'gif',
		'webp',
		'bmp',
		'tiff',
		'tif',
		'svg',
		'ico',
	];

	/**
	 * Throw an exception when the upload subsystem is unavailable.
	 *
	 * This method checks PHP configuration and throws a generic Exception
	 * if the "file_uploads" directive is disabled. Intended for early
	 * initialization where uploads are required.
	 *
	 * @throws Exception if uploads are not enabled
	 */
	public static function throwErrors(): void
	{
		if (self::isAvailable()) {
			return;
		}

		throw new Exception("Unable to create Upload because 'file_uploads' directive is disabled in your php.ini file");
	}

	/**
	 * Check if file uploads are enabled in the PHP configuration
	 * 
	 * @return bool|string
	 */
	public static function isAvailable(): bool|string
	{
		return ini_get('file_uploads');
	}

	/**
	 * Retrieve uploaded file information
	 * 
	 * @param string $name       input field name from $_FILES array
	 * @param string $key        the key to retrieve (e.g., 'name', 'tmp_name', 'error', 'size', 'type')
	 * 
	 * @return mixed
	 */
	public static function get(string $name, string $key = UploadedFile::FULLY_DATA): mixed
	{
		if ($key === UploadedFile::FULLY_DATA) {
			return $_FILES[$name] ?? null;
		}

		if (preg_match('/^([A-Za-z0-9\-_]+)\[([A-Za-z0-9\-_]+)\]$/', $name, $match)) {
			return $_FILES[$match[1]][$key][$match[2]] ?? null;
		}

		return $_FILES[$name][$key] ?? null;
	}

	/**
	 * Move uploaded file to a new location
	 * 
	 * @param string $name       input field name from $_FILES array
	 * @param string $filePath    the destination file path
	 * 
	 * @return bool
	 */
	public static function move(string $name, string $filePath): bool
	{
		$temporaryName = self::getTemporaryName($name);

		if ($temporaryName === null || $temporaryName === false) {
			return false;
		}

		if (!is_uploaded_file($temporaryName)) {
			return false;
		}

		$directory = dirname($filePath);
		if (!is_dir($directory) || !is_writable($directory)) {
			return false;
		}

		return move_uploaded_file($temporaryName, $filePath);
	}

	/**
	 * Check if the file is uploaded in the specified path
	 * 
	 * @param string $name            input field name from $_FILES array
	 * @param string $directoryPath    the target directory path
	 * 
	 * @return bool
	 */
	public static function isUploaded(string $name, string $directoryPath): bool
	{
		$temporaryName = self::getTemporaryName($name);

		if ($temporaryName === null || $temporaryName === false) {
			return false;
		}

		return is_uploaded_file($temporaryName) && file_exists(sprintf("%s/%s", $directoryPath, $temporaryName));
	}

	/**
	 * Get error message from error code
	 * 
	 * @param int|string $error   the error code (e.g., UPLOAD_ERR_OK, UPLOAD_ERR_NO_FILE)
	 * 
	 * @return string
	 */
	public static function getErrorMessageFromCode(int|string $error): string
	{
		$errorMap = [
			UPLOAD_ERR_OK => UploadedFileErrorMessage::UPLOAD_ERR_OK,
			UPLOAD_ERR_INI_SIZE => UploadedFileErrorMessage::UPLOAD_ERR_INI_SIZE,
			UPLOAD_ERR_FORM_SIZE => UploadedFileErrorMessage::UPLOAD_ERR_FORM_SIZE,
			UPLOAD_ERR_PARTIAL => UploadedFileErrorMessage::UPLOAD_ERR_PARTIAL,
			UPLOAD_ERR_NO_FILE => UploadedFileErrorMessage::UPLOAD_ERR_NO_FILE,
			UPLOAD_ERR_NO_TMP_DIR => UploadedFileErrorMessage::UPLOAD_ERR_NO_TMP_DIR,
			UPLOAD_ERR_CANT_WRITE => UploadedFileErrorMessage::UPLOAD_ERR_CANT_WRITE,
			UPLOAD_ERR_EXTENSION => UploadedFileErrorMessage::UPLOAD_ERR_EXTENSION,
			UploadedFileError::UPLOAD_FILE_IS_EMPTY => UploadedFileErrorMessage::UPLOAD_ERR_EMPTY,
			UploadedFileError::UPLOAD_IS_NOT_ALLOWED => UploadedFileErrorMessage::UPLOAD_ERR_EXTENSION,
		];

		return $errorMap[$error] ?? UploadedFileErrorMessage::UPLOAD_ERR_UNKNOWN;
	}

	/**
	 * Determine whether the global \\$_FILES array is empty.
	 *
	 * @return bool true when no files have been uploaded in this request
	 */
	public static function isEmpty(): bool
	{
		return empty($_FILES) || !is_countable($_FILES);
	}

	/**
	 * Tells whether there is an error with the uploaded file
	 * 
	 * @param string $name   input field name from $_FILES array
	 * 
	 * @return bool
	 */
	public static function hasError(string $name): bool
	{
		return (self::getFileError($name) !== UPLOAD_ERR_OK);
	}

	/**
	 * Tells whether the file was uploaded via HTTP POST
	 * 
	 * @param string $name   input field name from $_FILES array
	 * 
	 * @return bool
	 */
	public static function isTemporaryUploaded(string $name): bool
	{
		if (!array_key_exists($name, $_FILES)) {
			return false;
		}

		if (!isset($_FILES[$name])) {
			return false;
		}

		$temporaryName = self::getTemporaryName($name);

		if ($temporaryName === null || $temporaryName === false) {
			return false;
		}

		return is_uploaded_file($temporaryName);
	}

	/**
	 * Get MIME type of the uploaded file
	 * 
	 * @param string $name   input field name from $_FILES array
	 * 
	 * @return string|bool
	 */
	public static function getMimeType(string $name): string|false
	{
		if (!array_key_exists($name, $_FILES)) {
			return false;
		}

		if (!isset($_FILES[$name])) {
			return false;
		}

		$temporaryName = self::getTemporaryName($name);

		if ($temporaryName === null || $temporaryName === false) {
			return false;
		}

		if (!file_exists($temporaryName)) {
			return false;
		}

		$finfo = finfo_open(FILEINFO_MIME_TYPE);

		if ($finfo === false) {
			return false;
		}

		$mimeType = finfo_file($finfo, $temporaryName);

		if (version_compare(PHP_VERSION, '8.5.0', '<')) {
			// @phpstan-ignore-next-line
			finfo_close($finfo);
		} else {
			unset($finfo);
		}

		return $mimeType;
	}

	/**
	 * Check if the uploaded file is an executable file
	 * 
	 * @param string $name   input field name from $_FILES array
	 * 
	 * @return bool
	 */
	public static function isExecutableFile(string $name): bool
	{
		return self::inExtension($name, self::EXECUTABLE_EXTENSIONS);
	}

	/**
	 * Check if the uploaded file is an image
	 * 
	 * @param string $name   input field name from $_FILES array
	 * 
	 * @return bool
	 */
	public static function isImageFile(string $name): bool
	{
		if (!self::inExtension($name, self::IMAGE_EXTENSIONS)) {
			return false;
		}

		return self::inMimeType($name, self::IMAGE_MIME_TYPES);
	}

	/**
	 * Get file extension from MIME type
	 * 
	 * @param string $name   input field name from $_FILES array
	 * 
	 * @return string|bool
	 */
	public static function getExtensionFromMimeType(string $name): string|false
	{
		$type = self::getMimeType($name);

		if ($type === false) {
			return false;
		}

		return MultiPurposeInternetMailExtensions::getExtensionFromContentType($type);
	}

	/**
	 * Get file extension from file data (by analyzing file header)
	 * 
	 * @param string $name   input field name from $_FILES array
	 * 
	 * @return string|bool
	 */
	public static function getExtensionFromFileData(string $name): string|false
	{
		$temporaryName = self::getTemporaryName($name);

		if (!array_key_exists($name, $_FILES)) {
			return false;
		}

		if (!isset($_FILES[$name])) {
			return false;
		}

		if ($temporaryName === null || $temporaryName === false) {
			return false;
		}

		return FileHandler::getHeaderType($temporaryName);
	}

	/**
	 * Tells whether any file has been uploaded
	 * 
	 * @return bool
	 */
	public static function hasItem(): bool
	{
		return (count($_FILES) > 0);
	}

	/**
	 * Get temporary name of the uploaded file
	 * 
	 * @param string $name   input field name from $_FILES array
	 * 
	 * @return mixed
	 */
	public static function getTemporaryName(string $name): mixed
	{
		return self::get($name, UploadedFile::TEMPORARY_NAME);
	}

	/**
	 * Get uploaded file type
	 * 
	 * @param string $name   input field name from $_FILES array
	 * 
	 * @return mixed
	 */
	public static function getFileType(string $name): mixed
	{
		return self::get($name, UploadedFile::TYPE);
	}

	/**
	 * Generate a unique file name in the specified upload path
	 * 
	 * @param string $uploadPath       the target upload directory path
	 * @param string $name             input field name from $_FILES array or file extension
	 * @param bool   $fromTemporary    whether to extract extension from uploaded file or treat name as extension
	 * 
	 * @return string
	 */
	public static function generateUniqueFileName(string $uploadPath, string $name, bool $fromTemporary = true): string
	{
		$extension = $fromTemporary ? self::getExtension($name) : $name;
		$generatedFileName = sprintf("%s.%s", StringHandler::getRandomHex(), $extension);
		$filePath = sprintf("%s/%s", $uploadPath, $generatedFileName);

		while (file_exists($filePath)) {
			$generatedFileName = sprintf("%s.%s", StringHandler::getRandomHex(), $extension);
			$filePath = sprintf("%s/%s", $uploadPath, $generatedFileName);
		}

		return $generatedFileName;
	}

	/**
	 * Check if the uploaded file has one of the specified extensions
	 * 
	 * @param string 		$name       input field name from $_FILES array
	 * @param array<string> $extensions list of allowed file extensions
	 * 
	 * @return bool
	 */
	public static function inExtension(string $name, array $extensions = []): bool
	{
		$extension = self::getExtension($name);

		if ($extension === '') {
			return false;
		}

		return in_array(strtolower($extension), array_map('strtolower', $extensions), true);
	}

	/**
	 * Check if the uploaded file has one of the specified MIME types
	 * 
	 * @param string $name       input field name from $_FILES array
	 * @param array $mimeTypes   list of allowed MIME types
	 * 
	 * @return bool
	 */
	public static function inMimeType(string $name, array $mimeTypes = []): bool
	{
		$mimeType = self::getMimeType($name);

		if ($mimeType === false) {
			return false;
		}

		return in_array($mimeType, $mimeTypes, true);
	}

	/**
	 * Get file extension of the uploaded file
	 * 
	 * @param string $name   input field name from $_FILES array
	 * 
	 * @return string
	 */
	public static function getExtension(string $name): string
	{
		$fileName = self::get($name, UploadedFile::NAME);

		if ($fileName === null || $fileName === '') {
			return '';
		}

		$dotPosition = strrpos($fileName, '.');

		if ($dotPosition === false) {
			return '';
		}

		return strtolower(substr($fileName, $dotPosition + 1));
	}

	/**
	 * Get file name of the uploaded file
	 * 
	 * @param string $name   input field name from $_FILES array
	 * 
	 * @return mixed
	 */
	public static function getFileName(string $name): mixed
	{
		return self::get($name, UploadedFile::NAME);
	}

	/**
	 * Get sanitized file name of the uploaded file
	 * 
	 * @param string $name   input field name from $_FILES array
	 * 
	 * @return string
	 */
	public static function getSanitizedFileName(string $name): string
	{
		$fileName = self::getFileName($name);

		if ($fileName === null || $fileName === '') {
			return '';
		}

		$fileName = basename($fileName);
		$fileName = preg_replace('/[^\w.\-]/', '_', $fileName);
		$fileName = preg_replace('/\.{2,}/', '.', $fileName);
		$fileName = trim($fileName, '._');

		return $fileName;
	}

	/**
	 * Get file size of the uploaded file
	 * 
	 * @param string $name   input field name from $_FILES array
	 * 
	 * @return mixed
	 */
	public static function getFileSize(string $name): mixed
	{
		return self::get($name, UploadedFile::SIZE);
	}

	/**
	 * Get file error code of the uploaded file
	 * 
	 * @param string $name   input field name from $_FILES array
	 * 
	 * @return mixed
	 */
	public static function getFileError(string $name): mixed
	{
		if (!OperationSystem::isFileUploadAllowed()) {
			return UploadedFileError::UPLOAD_IS_NOT_ALLOWED;
		}

		return self::get($name, UploadedFile::ERROR);
	}

	/**
	 * Get file error message of the uploaded file
	 * 
	 * @param string $name   input field name from $_FILES array
	 * 
	 * @return string
	 */
	public static function getFileErrorMessage(string $name): string
	{
		$code = self::getFileError($name);

		return self::getErrorMessageFromCode($code);
	}

	/**
	 * Check if the uploaded file exists
	 * 
	 * @param string $name   input field name from $_FILES array
	 * 
	 * @return bool
	 */
	public static function has(string $name = UploadedFile::TEMPORARY_NAME): bool
	{
		return self::get($name) !== null;
	}

	/**
	 * Perform a comprehensive validation check on an uploaded file.
	 *
	 * The validation covers existence, upload errors, temporary upload status,
	 * size limits, allowed extensions/mime types and disallows executable files.
	 *
	 * @param string $name               input field name or temporary name key
	 * @param array $allowedExtensions   list of lowercase extensions that are permitted
	 * @param array $allowedMimeTypes    list of mime types that are permitted
	 * @param int $maxSize               maximum allowed file size in bytes (0 for no limit)
	 * @return int|string                UPLOAD_ERR_* constant or custom error value
	 */
	public static function validate(string $name, array $allowedExtensions = [], array $allowedMimeTypes = [], int $maxSize = 0): int|string
	{
		if (!self::has($name)) {
			return UPLOAD_ERR_NO_FILE;
		}

		$error = self::getFileError($name);
		if ($error !== UPLOAD_ERR_OK) {
			return $error;
		}

		if (!self::isTemporaryUploaded($name)) {
			return UPLOAD_ERR_NO_FILE;
		}

		$size = self::getFileSize($name);
		if ($size === 0) {
			return UploadedFileError::UPLOAD_FILE_IS_EMPTY;
		}

		if ($maxSize > 0 && $size > $maxSize) {
			return UPLOAD_ERR_FORM_SIZE;
		}

		if (!empty($allowedExtensions) && !self::inExtension($name, $allowedExtensions)) {
			return UPLOAD_ERR_EXTENSION;
		}

		if (!empty($allowedMimeTypes) && !self::inMimeType($name, $allowedMimeTypes)) {
			return UPLOAD_ERR_EXTENSION;
		}

		if (self::isExecutableFile($name)) {
			return UploadedFileError::UPLOAD_IS_NOT_ALLOWED;
		}

		return UPLOAD_ERR_OK;
	}

	/**
	 * Check if the MIME type of the uploaded file is consistent with its extension
	 * 
	 * @param string $name   input field name from $_FILES array
	 * @return bool
	 */
	public static function isMimeTypeConsistent(string $name): bool
	{
		$extensionFromMime = self::getExtensionFromMimeType($name);
		$extensionFromName = self::getExtension($name);

		if ($extensionFromMime === false || $extensionFromName === '') {
			return false;
		}

		return strtolower($extensionFromMime) === strtolower($extensionFromName);
	}
}
