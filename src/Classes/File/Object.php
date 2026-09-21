<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\File;

use Clover\Classes\Directory\Handler as DirectoryHandler;
use Clover\Classes\File\Handler as FileHandler;
use Clover\Exception\FileHandler\{FileNotFoundException, TargetIsNotFileException};
use Clover\Implement\FileObjectInterface;
use Clover\Message\FileHandler\FileHandlerMessage;
use RuntimeException;
use function sprintf;
use function in_array;
use function gettype;
use function str_replace;

/**
 * File Object Class
 */
class FileObject implements FileObjectInterface
{
	private const FILE_MODE_DECORATORS = ['b', 't', 'e'];
	private const CREATE_MODE_LIST = ['w', 'w+', 'a', 'a+', 'x', 'x+', 'c', 'c+'];
	private const READ_MODE_LIST = ['r', 'r+', 'w+', 'a+', 'x+', 'c+'];
	private const WRITE_MODE_LIST = ['r+', 'w', 'w+', 'a', 'a+', 'x', 'x+', 'c', 'c+'];

	private $writtenContentLength;

	private $fileHandler;

	private $readedContent;

	/*
	 * r  : Read only
	 * r+ : Read and write
	 * w  : Write only
	 * w+ : Write and read
	 * a  : Read only
	 * a+ : Read and read
	 * c  : Read and write
	 *
	 * Append syntax : b, t
	 */
	private $acceptExtension = [];

	// Determines whether file size capacity is compared
	private $confirmFilesize = true;

	// The size of the file last created
	private $writeContentLength;

	// File creation mode
	private $mode;

	// The path of the file to be finally created
	private $filePath;

	// File extension
	private $fileExtension;

	// Class file for managing file
	private $fileHandlerClass;

	// Path of the file to be temporarily saved
	private $temporaryPath;

	// File pointer location
	private $seekOffset;

	// If the length does not match the contents written, it is returned to the original file
	private $recoveryMode = false;

	private $directoryHandler;

	/**
	 * Constructor
	 *
	 * @param string $filePath
	 * @param bool $recoveryMode
	 * @param string $mode
	 */
	public function __construct(string $filePath, bool $recoveryMode = false, string $mode = 'w')
	{
		$this->fileHandlerClass = new FileHandler();
		$this->directoryHandler = new DirectoryHandler($this->fileHandlerClass);

		$this->mode = $mode;
		$this->seekOffset = 0;
		$this->filePath = $filePath;
		$this->fileExtension = $this->fileHandlerClass->getExtension($this->filePath);

		$this->recoveryMode = $recoveryMode;
		if ($this->recoveryMode) {
			$this->setRecoveryFile();
		}
	}

	/**
	 * Destructor
	 */
	public function __destruct()
	{
		$this->removeTemporary();
	}

	/**
	 * Get accepted file extensions
	 *
	 * @param array $extension
	 *
	 * @return array
	 */
	public function getAcceptExtension(array $extension): array
	{
		return $this->acceptExtension;
	}

	/**
	 * Set accepted file extensions
	 *
	 * @param array|string $extension
	 * 
	 * @return void
	 */
	public function setAcceptExtension(array|string $extension): void
	{
		$this->acceptExtension = is_array($extension) ? $extension : [$extension];
	}

	/**
	 * Set recovery file
	 * 
	 * @return void
	 */
	private function setRecoveryFile(): void
	{
		do {
			$this->temporaryPath = sprintf('%s.%s.%s', $this->filePath, uniqid('', true), $this->fileExtension);
		} while ($this->fileHandlerClass->isFile($this->temporaryPath));

		$isFileExists = FileHandler::isExists($this->filePath);

		if ($isFileExists) {
			$fileContent = file_get_contents($this->filePath, true);
			file_put_contents($this->temporaryPath, $fileContent);
		}
	}

	/**
	 * Check if write content length is set
	 *
	 * @return bool
	 */
	public function hasWriteContentLength(): bool
	{
		if ($this->writeContentLength === -1) {
			return false;
		}

		return true;
	}

	/**
	 * Close file handle
	 *
	 * @return bool
	 */
	public function closeFileHandle(): bool
	{
		fclose($this->fileHandler);

		if (!$this->recoveryMode) {
			return true;
		}

		if ($this->recoveryMode && !$this->hasWriteContentLength()) {
			return true;
		}

		$filePath = $this->getFilePath();
		$currentFileSize = $this->getCurrentSize();
		$invalidFileSize = $currentFileSize === -1 ? true : false;
		$correctFileSize = ($currentFileSize === (int) $this->writeContentLength);

		$isFileExists = $this->fileHandlerClass->isFile($this->temporaryPath);

		if ($this->recoveryMode && !$isFileExists) {
			throw new TargetIsNotFileException(FileHandlerMessage::getFileIsNotExistsMessage($this->temporaryPath));
		}

		if ($this->recoveryMode && !$invalidFileSize && !$correctFileSize) {
			$this->fileHandlerClass->delete($filePath);
			return false;
		}

		if ($this->recoveryMode) {
			if ($this->fileHandlerClass->copy($filePath, $this->filePath)) {
				$this->fileHandlerClass->delete($filePath);
			}
		}

		return true;
	}

	/**
	 * Seek to a specific offset in the file
	 *
	 * @param int $offset
	 *
	 * @return bool
	 */
	public function seek(int $offset): bool
	{
		$seek = fseek($this->fileHandler, $offset, SEEK_SET);

		if ($seek === 0) {
			$this->seekOffset = $offset;

			return true;
		}

		return false;
	}

	/**
	 * Check if the current mode allows file creation
	 *
	 * @param string|null $fileMode
	 *
	 * @return bool
	 */
	public function hasMode(?string $fileMode = null): bool
	{
		$normalizedFileMode = str_replace(self::FILE_MODE_DECORATORS, '', $fileMode ?? $this->mode);

		return in_array($normalizedFileMode, self::CREATE_MODE_LIST, true);
	}

	/**
	 * Check if the current mode allows file reading
	 *
	 * @param string|null $fileMode
	 *
	 * @return bool
	 */
	public function isReadable(?string $fileMode = null): bool
	{
		$normalizedFileMode = str_replace(self::FILE_MODE_DECORATORS, '', $fileMode ?? $this->mode);

		return in_array($normalizedFileMode, self::READ_MODE_LIST, true);
	}

	/**
	 * Append content from another file
	 *
	 * @param string $filePath
	 *
	 * @return void
	 */
	public function appendContent($filePath): void
	{
		$fileHandler = fopen($filePath, 'r');

		$line = fgets($fileHandler);

		while ($line !== false) {
			fputs($this->fileHandler, $line);
			$line = fgets($fileHandler);
		}

		fclose($fileHandler);
	}

	/**
	 * Check if a specific line in the file matches a given string
	 *
	 * @param string $string
	 *
	 * @return bool
	 */
	public function isEqualByLine(string $string): bool
	{
		if (!FileHandler::isExists($this->getFilePath())) {
			throw new FileNotFoundException(FileHandlerMessage::getFileIsNotExistsMessage($this->getFilePath()));
		}

		$bool = false;

		while ($isEqual = fgets($this->fileHandler)) {
			if ($isEqual === $string) {
				$bool = true;
			} else {
				$bool = false;
			}
		}

		return $bool;
	}

	/**
	 * Inject File Not Found Exception
	 * 
	 * @return void
	 */
	public function injectFileNotFoundException(): void
	{
		if ($this->recoveryMode && !$this->fileHandlerClass->isFile($this->temporaryPath)) {
			throw new TargetIsNotFileException(FileHandlerMessage::getFileIsNotExistsMessage($this->temporaryPath));
		}
	}

	/**
	 * Check if the file is locked
	 *
	 * @return bool
	 */
	public function isLocked(): bool
	{
		$this->injectFileNotFoundException();

		return $this->fileHandlerClass->isLocked($this->filePath);
	}

	/**
	 * Check if the file is writable
	 *
	 * @return bool
	 */
	public function isWritable(): bool
	{
		$this->injectFileNotFoundException();

		return FileHandler::isWritable($this->filePath);
	}

	/**
	 * Remove temporary file
	 * 
	 * @return void
	 */
	public function removeTemporary(): void
	{
		if ($this->recoveryMode) {
			if (FileHandler::isExists($this->temporaryPath)) {
				$this->fileHandlerClass->delete($this->temporaryPath);
			}
		}
	}

	/**
	 * Write content to the file
	 *
	 * @param string $content
	 * @param bool $isLarge
	 * @param int $bufferSize
	 *
	 * @return bool
	 */
	public function writeContent(string $content, $isLarge = false, int $bufferSize = 1024): bool
	{
		if (!$this->isWritable() || $this->isLocked()) {
			$this->removeTemporary();
			return false;
		}

		$this->confirmFilesize = true;

		if ($this->mode === 'w') {
			$this->writeContentLength = strlen($content);
		} else if ($this->mode === 'a') {
			$this->writeContentLength = $this->fileHandlerClass->getSize($this->filePath);
			$this->writeContentLength += strlen($content);
		}

		if ($isLarge) {
			$pieces = str_split($content, $bufferSize ? $bufferSize : (1024 * 4));
			foreach ($pieces as $piece) {
				$this->writtenContentLength += fwrite($this->fileHandler, $piece, strlen($piece));
			}
		} else {
			$this->writtenContentLength = fwrite($this->fileHandler, $content);
		}

		return true;
	}

	/**
	 * Get current file size
	 *
	 * @return int
	 */
	public function getCurrentSize(): int
	{
		$filePath = $this->getFilePath();
		$currentFileSize = $this->fileHandlerClass->getSize($filePath);

		return $currentFileSize;
	}

	/**
	 * Get read content
	 *
	 * @return string
	 */
	public function getReadedContent(): string
	{
		return (!$this->isReadedContentValid()) ? '' : $this->readedContent;
	}

	/**
	 * Check if read content is valid
	 *
	 * @return bool
	 */
	public function isReadedContentValid(): bool
	{
		return !($this->readedContent === false);
	}

	/**
	 * Check if any content has been read
	 *
	 * @return bool
	 */
	public function hasReadedContent(): bool
	{
		return $this->getCurrentSize() > 0;
	}

	/**
	 * Read all content from the file
	 * 
	 * @return void
	 */
	public function readAllContent(): void
	{
		if (!$this->hasReadedContent()) {
		}

		$this->readContent($this->getCurrentSize());
	}

	/**
	 * Read content from the file
	 *
	 * @param int $fileSize
	 * @return void
	 */
	public function readContent(int $fileSize = 0): void
	{
		$this->readedContent = fread($this->fileHandler, $fileSize);
	}

	/**
	 * Print file data to output
	 *
	 * @param int $mbSize
	 * 
	 * @return void
	 */
	public function printFileData(int $mbSize = 8): void
	{
		while (!feof($this->fileHandler)) {
			print (@fread($this->fileHandler, (1024 * $mbSize)));
			ob_flush();
			flush();
		}
	}

	/**
	 * Check that free space is enought for write to file
	 * 
	 * @return bool
	 */
	public function isEnoughFreeSpace(): bool
	{
		$freeSpace = DirectoryHandler::getFreeSpace();
		if ($freeSpace === -1) {
			return true;
		}

		$capacity = (int) $this->writeContentLength;

		$isEnough = $capacity < $freeSpace;
		if ($this->mode === 'w' && !$isEnough) {
			return false;
		}

		$sourceFileSize = $this->fileHandlerClass->getSize($this->filePath);
		$bool = ($freeSpace + $sourceFileSize) < $freeSpace;
		if ($this->mode === 'a' && !$bool) {
			return false;
		}

		return true;
	}

	/**
	 * Check if content was written successfully
	 *
	 * @return bool
	 */
	public function successToWriteContent(): bool
	{
		if (!gettype($this->writtenContentLength) === 'integer') {
			return false;
		}

		$isInvalidSize = ($this->writtenContentLength !== (int) $this->writeContentLength);

		if ($this->mode === 'w' && $isInvalidSize) {
			return false;
		}

		if (!$this->temporaryPath) {
			return true;
		}

		$isCorrectSize = ($this->fileHandlerClass->getSize($this->temporaryPath) !== (int) $this->writeContentLength);

		if ($this->mode === 'a' && $isCorrectSize) {
			return false;
		}

		return true;
	}

	/**
	 * Get file path
	 *
	 * @return string
	 */
	public function getFilePath(): string
	{
		if ($this->recoveryMode) {
			$filePath = $this->temporaryPath;
		} else {
			$filePath = $this->filePath;
		}

		return $filePath;
	}

	/**
	 * Start handling the file
	 *
	 * @return void
	 */
	public function startHandle(): void
	{
		$filePath = $this->getFilePath();
		$fileIsNotExists = !$this->hasMode() && !FileHandler::isExists($filePath);

		if ($fileIsNotExists) {
			throw new FileNotFoundException(FileHandlerMessage::getFileIsNotExistsMessage($filePath));
		}

		$directoryPath = dirname($filePath);
		$normalizedFileMode = str_replace(self::FILE_MODE_DECORATORS, '', $this->mode);
		$requiresWriteAccess = in_array($normalizedFileMode, self::WRITE_MODE_LIST, true);
		$directoryIsAccessible = $requiresWriteAccess
			? DirectoryHandler::isWritable($directoryPath)
			: DirectoryHandler::isReadable($directoryPath);

		if (!$directoryIsAccessible) {
			$requiredPermission = $requiresWriteAccess ? 'writable' : 'readable';
			throw new RuntimeException(sprintf("`%s` Directory is not %s", $directoryPath, $requiredPermission));
		}

		$this->fileHandler = fopen($filePath, $this->mode);
	}

	/**
	 * Check if file handle started successfully
	 *
	 * @return bool
	 */
	public function successToStartHandle(): bool
	{
		if (($this->fileHandler) === false) {
			return false;
		}

		if (gettype($this->fileHandler) !== 'resource') {
			return false;
		}

		if (get_resource_type($this->fileHandler) !== 'stream') {
			return false;
		}

		return true;
	}
}
