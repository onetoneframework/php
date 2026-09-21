<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\File;

use Clover\Classes\Data\{ArrayObject, StringObject};
use Clover\Classes\Directory\Handler as DirectoryHandler;
use Clover\Classes\Event\AsyncPromise;
use Clover\Classes\Event\EventLoop;
use Clover\Classes\FileSystem\Handler as FileSystemHandler;
use Clover\Classes\OperationSystem;
use Clover\Classes\Protocol\PHP as PHPProtocol;
use Clover\Enumeration\{FileMode, FileSizeUnit, LockMode, UnpackArguments};
use Clover\Exception\FileHandler\{FileNotFoundException, InvalidFileHandler, TargetIsNotFileException};
use Clover\Exception\Functions\FunctionIsNotExistsException as FunctionIsNotExistsException;
use Clover\Exception\ResourceHandler\InvalidTypeException as InvalidTypeException;
use Clover\Exception\StupidIdeaException as StupidIdeaException;
use Clover\Message\FileHandler\FileHandlerMessage as FileHandlerMessage;
use Clover\Message\Functions\FunctionMessage as FunctionMessage;
use Clover\Validation\FileValidation as FileValidation;
use Exception;
use RuntimeException;
use UnexpectedValueException;
use function clearstatcache;
use function count;
use function fileatime;
use function filetype;
use function getType;
use function in_array;
use function intval;
use function is_int;
use function ob_flush;
use function parse_ini_file;
use function rename;
use function sha1_file;
use function sprintf;
use function strlen;
use function strrchr;

/**
 * File functions
 */
class Functions
{
	/**
	 * @var string $lastError Store the last error message for file operations
	 */
	private static string $lastError;

	/**
	 * How many times an asynchronous child process is respawned after the
	 * operating system kills it.
	 *
	 * A negative exit code means the child was terminated rather than returning
	 * — on Windows a crash surfaces as e.g. -1073741819 (STATUS_ACCESS_VIOLATION).
	 * Spawning php.exe concurrently under CPU contention does this intermittently:
	 * the child dies during start-up, so it neither performs the operation nor
	 * reports anything on either pipe. One respawn is cheaper than surfacing a
	 * failure the caller cannot act on.
	 */
	private const ASYNC_CHILD_RETRY_LIMIT = 1;

	/**
	 * Write file contents asynchronously via the event loop.
	 *
	 * @param string $filename Target file path
	 * @param string $content  Content to write
	 *
	 * @return AsyncPromise Resolves to true on success, rejects with the child's exit code and stderr
	 */
	public static function writeFileAsync(string $filename, string $content): AsyncPromise
	{
		return new AsyncPromise(function ($resolve, $reject) use ($filename, $content) {
			self::spawnAsyncWrite($filename, $content, $resolve, $reject, self::ASYNC_CHILD_RETRY_LIMIT);
		});
	}

	/**
	 * One attempt at an asynchronous write, respawning itself when the child is killed.
	 *
	 * @param string   $filename    Target file path
	 * @param string   $content     Content to write
	 * @param callable $resolve     Promise resolver
	 * @param callable $reject      Promise rejecter
	 * @param int      $retriesLeft Remaining respawns after an abnormal child termination
	 *
	 * @return void
	 */
	private static function spawnAsyncWrite(string $filename, string $content, callable $resolve, callable $reject, int $retriesLeft): void
	{
		$script = '$d=stream_get_contents(STDIN);'
			. '$r=file_put_contents($argv[1],$d);'
			. 'echo $r===false?"0":"1";';

		$proc = proc_open(
			['php', '-r', $script, $filename],
			[0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
			$pipes
		);

		if ($proc === false) {
			$reject(new RuntimeException(sprintf('Failed to spawn write process for: %s', $filename)));
			return;
		}

		[$stdin, $stdout, $stderr] = $pipes;

		// stderr is kept open and drained alongside stdout: it is the only place a
		// failing child explains itself, and closing it here used to discard that.
		stream_set_blocking($stdin, false);
		stream_set_blocking($stdout, false);
		stream_set_blocking($stderr, false);

		$length = strlen($content);
		$written = 0;

		$waitForSignal = static function () use ($stdout, $stderr, $proc, $filename, $content, $resolve, $reject, $retriesLeft): void {
			$output = '';
			$errors = '';

			EventLoop::addReadStream(
				$stdout,
				static function ($stream) use (&$output, &$errors, $stdout, $stderr, $proc, $filename, $content, $resolve, $reject, $retriesLeft): void {
					self::drainChildStream($stream, $output);
					self::drainChildStream($stderr, $errors);

					if (!feof($stream)) {
						return;
					}

					EventLoop::removeReadStream($stdout);
					self::drainChildStream($stdout, $output);
					self::drainChildStream($stderr, $errors);
					fclose($stdout);
					fclose($stderr);

					// stdout is at EOF, so the child has closed it and is on its way
					// out; proc_close() reaps it and hands back its exit status. That
					// status is the whole point: a child killed during start-up also
					// reaches EOF with nothing written, which EOF alone cannot tell
					// apart from a clean but empty result.
					$exitCode = proc_close($proc);

					if ($exitCode === 0 && trim($output) === '1') {
						$resolve(true);
						return;
					}

					if ($exitCode < 0 && $retriesLeft > 0) {
						self::spawnAsyncWrite($filename, $content, $resolve, $reject, $retriesLeft - 1);
						return;
					}

					$reject(new RuntimeException(
						self::describeAsyncChildFailure('write', $filename, $exitCode, $output, $errors)
					));
				}
			);
		};

		if ($length === 0) {
			fclose($stdin);
			$waitForSignal();
			return;
		}

		EventLoop::addWriteStream(
			$stdin,
			static function ($stream) use (&$written, $content, $length, $stdin, $stdout, $stderr, $proc, $filename, $reject, $waitForSignal): void {
				$chunk = fwrite($stream, substr($content, $written, 65536));

				if ($chunk === false) {
					EventLoop::removeWriteStream($stdin);
					fclose($stdin);
					fclose($stdout);
					fclose($stderr);
					proc_close($proc);
					$reject(new RuntimeException(sprintf(
						'Asynchronous write of "%s" failed: the pipe to the child process could not be written.',
						$filename
					)));
					return;
				}

				$written += $chunk;

				if ($written >= $length) {
					EventLoop::removeWriteStream($stdin);
					fclose($stdin);
					$waitForSignal();
				}
			}
		);
	}

	/**
	 * Read everything currently buffered on a non-blocking child pipe.
	 *
	 * @param mixed  $stream Pipe to drain; a closed handle is ignored
	 * @param string $buffer Buffer the data is appended to
	 *
	 * @return void
	 */
	private static function drainChildStream(mixed $stream, string &$buffer): void
	{
		if (!is_resource($stream)) {
			return;
		}

		while (true) {
			$chunk = fread($stream, 65536);

			if (!is_string($chunk) || $chunk === '') {
				return;
			}

			$buffer .= $chunk;
		}
	}

	/**
	 * Build the rejection message for a child process that did not do its job.
	 *
	 * @param string $operation Either "read" or "write"
	 * @param string $filename  The file the child was working on
	 * @param int    $exitCode  The child's exit code
	 * @param string $output    Whatever the child wrote to stdout
	 * @param string $errors    Whatever the child wrote to stderr
	 *
	 * @return string
	 */
	private static function describeAsyncChildFailure(string $operation, string $filename, int $exitCode, string $output, string $errors): string
	{
		$reason = $exitCode < 0
			? sprintf('was terminated by the operating system (exit code %d)', $exitCode)
			: sprintf('exited with code %d', $exitCode);

		$message = sprintf('Asynchronous %s of "%s" failed: the child process %s.', $operation, $filename, $reason);

		if (trim($errors) !== '') {
			$message .= ' stderr: ' . trim($errors);
		}

		if (trim($output) !== '') {
			$message .= ' stdout: ' . trim($output);
		}

		return $message;
	}

	public static function readOnlyLines(string $filePath)
	{
		return file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?? [];
	}

	/**
	 * Read exact number of bytes from a file handle, looping until fulfilled or EOF.
	 *
	 * @param resource $fh File handle
	 * @param int      $n  Number of bytes to read
	 *
	 * @return string The read data (may be shorter than $n on EOF)
	 */
	public static function readBytes(mixed $fh, int $n): string
	{
		$data = '';
		while (strlen($data) < $n && !feof($fh)) {
			$chunk = fread($fh, $n - strlen($data));
			if ($chunk === false) {
				break;
			}

			$data .= $chunk;
		}
		return $data;
	}

	/**
	 * Read file contents asynchronously via the event loop.
	 *
	 * @param string $filename Target file path
	 *
	 * @return AsyncPromise Resolves to file content string, rejects with the child's exit code and stderr
	 */
	public static function readFileAsync(string $filename): AsyncPromise
	{
		return new AsyncPromise(function ($resolve, $reject) use ($filename) {
			if (!file_exists($filename)) {
				$reject(new RuntimeException("File not found: $filename"));
				return;
			}

			self::spawnAsyncRead($filename, $resolve, $reject, self::ASYNC_CHILD_RETRY_LIMIT);
		});
	}

	/**
	 * One attempt at an asynchronous read, respawning itself when the child is killed.
	 *
	 * @param string   $filename    Target file path
	 * @param callable $resolve     Promise resolver
	 * @param callable $reject      Promise rejecter
	 * @param int      $retriesLeft Remaining respawns after an abnormal child termination
	 *
	 * @return void
	 */
	private static function spawnAsyncRead(string $filename, callable $resolve, callable $reject, int $retriesLeft): void
	{
		$proc = proc_open(
			['php', '-r', 'echo file_get_contents($argv[1]);', $filename],
			[0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
			$pipes
		);

		if ($proc === false) {
			$reject(new RuntimeException(sprintf('Failed to spawn read process for: %s', $filename)));
			return;
		}

		[$stdin, $stdout, $stderr] = $pipes;
		fclose($stdin);
		stream_set_blocking($stdout, false);
		stream_set_blocking($stderr, false);

		$content = '';
		$errors = '';

		EventLoop::addReadStream(
			$stdout,
			static function ($stream) use (&$content, &$errors, $stdout, $stderr, $proc, $filename, $resolve, $reject, $retriesLeft): void {
				self::drainChildStream($stream, $content);
				self::drainChildStream($stderr, $errors);

				if (!feof($stream)) {
					return;
				}

				EventLoop::removeReadStream($stdout);
				self::drainChildStream($stdout, $content);
				self::drainChildStream($stderr, $errors);
				fclose($stdout);
				fclose($stderr);

				// The exit code decides, not EOF: a child killed part way through
				// also reaches EOF, and resolving on that handed the caller a
				// silently truncated file.
				$exitCode = proc_close($proc);

				if ($exitCode === 0) {
					$resolve($content);
					return;
				}

				if ($exitCode < 0 && $retriesLeft > 0) {
					self::spawnAsyncRead($filename, $resolve, $reject, $retriesLeft - 1);
					return;
				}

				$reject(new RuntimeException(
					self::describeAsyncChildFailure('read', $filename, $exitCode, '', $errors)
				));
			}
		);
	}

	/**
	 * Check if a stream wrapper refers to a local file.
	 *
	 * @param mixed $stream Stream or URI string
	 *
	 * @return bool
	 */
	public static function isLocalStream(mixed $stream): bool
	{
		return stream_is_local($stream) || 0 === stripos($stream, 'file://');
	}

	/**
	 * Check if the given characters are allowed in a file name on the current OS.
	 *
	 * @param array $characters Characters to validate
	 *
	 * @return bool
	 */
	public static function isAllowedCharacter(array $characters): bool
	{
		if (OperationSystem::isWindows()) {
			return !in_array([':', '*', '?', '"', '<', '>', '|'], $characters);
		}

		return true;
	}

	/**
	 * Create a symbolic link.
	 *
	 * @param string $file Target file
	 * @param string $link Link name
	 *
	 * @return bool|int
	 */
	public static function createSymbolicLink(string $file, string $link): bool|int
	{
		if (OperationSystem::isWindows()) {
			$escapedLink = escapeshellarg($link);
			$escapedFile = escapeshellarg($file);
			exec("mklink /D {$escapedLink} {$escapedFile}", $output, $code);
			return $code;
		}

		return symlink($file, $link);
	}

	/**
	 * Set include path.
	 *
	 * @param string $include_path
	 *
	 * @return bool|string
	 */
	public static function setIncludePath(string $include_path): bool|string
	{
		return set_include_path($include_path);
	}

	/**
	 * Get class names declared by including a PHP file.
	 *
	 * @param string $filePath PHP file path
	 *
	 * @return array<string> Newly declared class names
	 */
	public static function getClassNames(string $filePath): array
	{
		$declared = get_declared_classes();
		$includedFiles = get_included_files();

		if (!in_array(realpath($filePath), $includedFiles) && file_exists($filePath)) {
			require_once $filePath;
		}

		$differences = array_diff(get_declared_classes(), $declared);
		if (count($differences) === 0) {
			$paths = explode(DIRECTORY_SEPARATOR, realpath($filePath));
			$extensions = explode(".", $paths[count($paths) - 1]);
			$className = $extensions[count($extensions) - 1];
			if (class_exists($className)) {
				return [$className];
			}
		}

		return $differences ?? [];
	}

	/**
	 * Check if the file name length is within system limits.
	 *
	 * @param string $fileName File name
	 *
	 * @return bool
	 */
	public static function isCorrectName(string $fileName): bool
	{
		if (strlen($fileName) > PHP_MAXPATHLEN) {
			return false;
		}

		return true;
	}

	/**
	 * Get a character from file handler.
	 *
	 * @param mixed $stream
	 *
	 * @throws InvalidFileHandler
	 * 
	 * @return string
	 */
	public static function getCharacter(mixed $stream): string
	{
		if (!self::isValidHandler($stream)) {
			throw new InvalidFileHandler(FileHandlerMessage::getInvalidFileHandler());
		}

		return fgetc($stream);
	}

	/**
	 * Get a line from file handler.
	 *
	 * @param mixed $stream
	 * @param int   $length
	 *
	 * @throws InvalidFileHandler
	 * 
	 * @return string
	 */
	public static function getLine(mixed $stream, int $length): string
	{
		if (!self::isValidHandler($stream)) {
			throw new InvalidFileHandler(FileHandlerMessage::getInvalidFileHandler());
		}

		return fgets($stream, $length);
	}

	/**
	 * Read the file contents.
	 *
	 * @param string $filePath  Absolute or relative file path
	 * @param string $writeMode fopen mode string
	 *
	 * @return mixed StringObject on success, false on failure
	 */
	public static function readAllContent(string $filePath, string $writeMode = FileMode::READ_ONLY): mixed
	{
		$filePath = self::convertToNomalizePath($filePath);

		return self::read($filePath, -1, $writeMode);
	}

	/**
	 * Create a file.
	 *
	 * @param string      $filePath Target file path
	 * @param string|null $content  Content to write
	 * @param string      $mode     fopen mode string
	 *
	 * @return bool
	 */
	public static function write(string $filePath, ?string $content = null, string $mode = 'w+'): bool
	{
		$filePath = self::convertToNomalizePath($filePath);

		$fileObject = new FileObject($filePath, false, $mode);
		$fileObject->startHandle();

		if (!$fileObject->successToStartHandle()) {
			return false;
		}

		$fileObject->writeContent($content);
		if (!$fileObject->successToWriteContent()) {
			return false;
		}

		$fileObject->closeFileHandle();

		return true;
	}

	/**
	 * Bring the created time.
	 *
	 * @param string $filePath
	 *
	 * @return string
	 */
	public static function getCreatedDate(string $filePath): bool|int
	{
		$filePath = self::convertToNomalizePath($filePath);

		if (!self::isExists($filePath)) {
			throw new FileNotFoundException(FileHandlerMessage::getFileIsNotExistsMessage($filePath));
		}

		if (!self::isFile($filePath)) {
			return false;
		}

		self::clearStatusCache($filePath);
		$return = filectime($filePath);

		return $return;
	}

	/**
	 * Bring the last modified time.
	 *
	 * @param string $fileName
	 *
	 * @throws FileNotFoundException
	 * @throws TargetIsNotFileException
	 * 
	 * @return string
	 */
	public static function getLastModifiedTime(string $fileName): bool|int
	{
		$fileName = self::convertToNomalizePath($fileName);

		if (!self::isExists($fileName)) {
			throw new FileNotFoundException(FileHandlerMessage::getFileIsNotExistsMessage($fileName));
		}

		if (!self::isFile($fileName)) {
			throw new TargetIsNotFileException(FileHandlerMessage::getFileIsNotExistsMessage($fileName));
		}

		self::clearStatusCache($fileName);
		$return = filemtime($fileName);

		return $return;
	}

	/**
	 * Write the contents of the file backwards.
	 *
	 * @param string $filePath
	 *
	 * @return bool
	 */
	public static function reverseContent(string $filePath): bool
	{
		$filePath = self::convertToNomalizePath($filePath);

		$fileLines = file($filePath);
		$invertedLines = strrev(array_shift($fileLines));

		return self::write($filePath, $invertedLines, 'w');
	}

	/**
	 * Check if the file name exceed the maximum length.
	 *
	 * @param string $file
	 *
	 * @return bool
	 */
	protected static function isExceedFileName(string $file): bool
	{
		$maxPathLength = PHP_MAXPATHLEN - 2;

		if (strlen($file) > $maxPathLength) {
			return true;
		}

		return false;
	}

	/**
	 * Get the filename without extension.
	 *
	 * @param string      $path
	 * @param string|null $extension
	 *
	 * @return string
	 */
	public static function getFilenameWithoutExtension(string $path, ?string $extension = null): string
	{
		if ('' === $path) {
			return '';
		}

		if (null !== $extension) {

			return rtrim(basename($path, $extension), '.');
		}

		return pathinfo($path, \PATHINFO_FILENAME);
	}

	/**
	 * Check if the file has specified extension.
	 *
	 * @param string       $path
	 * @param string|array $extensions
	 *
	 * @return bool
	 */
	public static function hasExtension(string $path, ?string $extensions = null): bool
	{
		if ('' === $path) {
			return false;
		}

		$actualExtension = self::getExtension($path);

		if ([] === $extensions || null === $extensions) {
			return '' !== $actualExtension;
		}

		if (\is_string($extensions)) {
			$extensions = [$extensions];
		}

		foreach ($extensions as $key => $extension) {
			$extensions[$key] = ltrim($extension, '.');
		}

		return in_array($actualExtension, $extensions, true);
	}

	/**
	 * Get the extension of file by file path.
	 *
	 * @param string $filePath
	 *
	 * @return string
	 */
	public static function getExtensionByFilePath(string $filePath): string
	{
		$return = null;

		if (function_exists("pathinfo")) {
			$return = pathinfo($filePath, \PATHINFO_EXTENSION);
		}

		return $return;
	}

	/**
	 * Check if the file exists, throwing if the path exceeds PHP_MAXPATHLEN.
	 *
	 * @param string $filePath File path
	 *
	 * @throws Exception When path length exceeds system limit
	 *
	 * @return bool
	 */
	public static function isExists(string $filePath): bool
	{
		if (self::isExceedFileName($filePath)) {
			throw new Exception(sprintf('Could not check if file exist because path length exceeds %d characters.', strlen($filePath)), 0, null);
		}

		$filePath = self::convertToNomalizePath($filePath);

		$return = file_exists($filePath);

		return $return;
	}

	/**
	 * Recursively delete files whose size is at or below the given threshold.
	 *
	 * @param string $dir  Directory to scan
	 * @param int    $size Maximum file size in bytes (files <= this are deleted)
	 *
	 * @return void
	 */
	public static function deleteLessSizeFiles(string $dir, int $size = 100): void
	{
		$files = scandir($dir);

		foreach ($files as $file) {
			if ($file == '.' || $file == '..') {
				continue;
			}

			$filePath = $dir . DIRECTORY_SEPARATOR . $file;

			if (is_dir($filePath)) {
				self::deleteLessSizeFiles($filePath);
			} elseif (is_file($filePath)) {
				if (filesize($filePath) <= $size) {
					unlink($filePath);
				}
			}
		}
	}


	/**
	 * Determine whether a file is currently locked by attempting an exclusive lock.
	 *
	 * @param string $filePath File to check
	 *
	 * @throws TargetIsNotFileException
	 *
	 * @return bool True if the file is locked by another process
	 */
	public static function isLocked(string $filePath): bool
	{
		$filePath = self::convertToNomalizePath($filePath);

		if (!self::isExists($filePath)) {
			return false;
		}

		if (!self::isFile($filePath)) {
			throw new TargetIsNotFileException(FileHandlerMessage::getFileIsNotExistsMessage($filePath));
		}

		if (!self::isValidHandler($filePath) && !self::isFile($filePath)) {
			return false;
		}

		if (!self::isValidHandler($filePath)) {
			$filePath = self::open($filePath, FileMode::READ_OVERWRITE);
		}

		$locked = !flock($filePath, LOCK_EX | LOCK_NB);
		if (!$locked) {
			flock($filePath, LOCK_UN);
		}
		fclose($filePath);

		return $locked;
	}

	/**
	 * Create an anonymous temporary file.
	 *
	 * @return resource|false
	 */
	public static function createTemporary(): mixed
	{
		return tmpfile();
	}

	/**
	 * Create a unique temporary file
	 *
	 * @param string $directory
	 * @param string $prefix
	 * 
	 * @return bool|string
	 */
	public static function createUniqueTemporary(string $directory, string $prefix): bool|string
	{
		return tempnam($directory, $prefix);
	}

	/**
	 * Create a unique temporary file on default temporary path
	 *
	 * @return mixed
	 */
	public static function createUniqueTemporaryOnDefaultPath($prefix): mixed
	{
		return self::createUniqueTemporary(DirectoryHandler::getDefaultTemporaryDirectoryPath(), $prefix);
	}

	/**
	 * Modify access and modification time of file
	 *
	 * @return bool
	 */
	public static function setAccessAndModificatinTime(string $filePath, int|null $time = null, int|null $atime = null): bool
	{
		return touch($filePath, $time, $atime);
	}

	/**
	 * Release the lock on a file handle.
	 *
	 * @param resource $fileHandler Open file resource
	 *
	 * @throws InvalidFileHandler
	 *
	 * @return void
	 */
	public static function unlock($fileHandler): void
	{
		if (!self::isValidHandler($fileHandler)) {
			throw new InvalidFileHandler(FileHandlerMessage::getInvalidFileHandler());
		}

		flock($fileHandler, LOCK_UN); // Unlock file handler
	}

	/**
	 * Change the permission mode of a file.
	 *
	 * @param string $filePath File path
	 * @param int    $mode     Octal permission mode
	 *
	 * @throws TargetIsNotFileException
	 *
	 * @return bool
	 */
	public static function setPermission(string $filePath, int $mode): bool
	{
		if (!self::isFile($filePath)) {
			throw new TargetIsNotFileException(FileHandlerMessage::getFileIsNotExistsMessage($filePath));
		}

		return chmod($filePath, $mode) ? true : false;
	}

	/**
	 * Change the group ownership of a file.
	 *
	 * @param string $filePath File path
	 * @param string $group    Group name
	 *
	 * @throws TargetIsNotFileException
	 *
	 * @return bool
	 */
	public static function changeGroup(string $filePath, string $group): bool
	{
		if (!self::isFile($filePath)) {
			throw new TargetIsNotFileException(FileHandlerMessage::getFileIsNotExistsMessage($filePath));
		}

		return chgrp($filePath, $group);
	}

	/**
	 * Acquire a lock on a file handle.
	 *
	 * @param resource $fileHandler Open file resource
	 * @param string   $mode       Lock mode from LockMode enumeration
	 *
	 * @throws InvalidFileHandler
	 *
	 * @return bool
	 */
	public static function lock(object $fileHandler, $mode = LockMode::ACQUIRE_SHARED_LOCK): bool
	{
		if (!self::isValidHandler($fileHandler)) {
			throw new InvalidFileHandler(FileHandlerMessage::getInvalidFileHandler());
		}

		$result = false;
		$mode = strtolower($mode);

		switch ($mode) {
			case LockMode::ACQUIRE_SHARED_LOCK:
				$result = flock($fileHandler, LOCK_SH);
				break;
			case LockMode::ACQUIRE_EXCLUSIVE_LOCK:
				$result = flock($fileHandler, LOCK_EX);
				break;
			case LockMode::RELEASE_LOCK:
				$result = flock($fileHandler, LOCK_UN);
				break;
		}

		return $result;
	}

	/**
	 * Check if the file is empty (0 bytes).
	 *
	 * @param string $filePath File path
	 *
	 * @throws TargetIsNotFileException
	 *
	 * @return bool
	 */
	public static function isEmpty(string $filePath): bool
	{
		$filePath = self::convertToNomalizePath($filePath);

		if (!self::isFile($filePath)) {
			throw new TargetIsNotFileException(FileHandlerMessage::getFileIsNotExistsMessage($filePath));
		}

		$return = self::getSize($filePath) === 0;

		return $return;
	}

	/**
	 * Check if the file type is 'unknown'.
	 *
	 * @param string $filePath File path
	 *
	 * @return bool
	 */
	public static function isUnknownFile(string $filePath): bool
	{
		$filePath = self::convertToNomalizePath($filePath);

		if (self::getType($filePath) === 'unknown') {
			return true;
		}

		return false;
	}

	/**
	 * Get a basename of file
	 *
	 * @param string $fileName
	 * @param string $extension
	 *
	 * @return string
	 */
	public static function getBasename(string $fileName, $extension = ""): string
	{
		return basename($fileName, $extension);
	}

	/**
	 * Require a PHP file once, with existence and type validation.
	 *
	 * @param string $filePath PHP file path
	 *
	 * @throws FileNotFoundException
	 * @throws TargetIsNotFileException
	 *
	 * @return void
	 */
	public static function requireOnce(string $filePath): void
	{
		$filePath = self::convertToNomalizePath($filePath);

		if (!self::isExists($filePath)) {
			throw new FileNotFoundException(FileHandlerMessage::getFileIsNotExistsMessage($filePath));
		}

		if (!self::isFile($filePath)) {
			throw new TargetIsNotFileException(FileHandlerMessage::getFileIsNotExistsMessage($filePath));
		}

		require_once $filePath;
	}

	/**
	 * Get the last access timestamp of a file.
	 *
	 * @param string $filePath File path
	 *
	 * @throws FileNotFoundException
	 *
	 * @return int|bool
	 */
	public static function getLastAccessDate(string $filePath): int|bool
	{
		$filePath = self::convertToNomalizePath($filePath);

		if (!self::isExists($filePath)) {
			throw new FileNotFoundException(FileHandlerMessage::getFileIsNotExistsMessage($filePath));
		}

		if (!self::isFile($filePath)) {
			return false;
		}

		self::clearStatusCache($filePath);
		$return = fileatime($filePath);

		return $return;
	}

	/**
	 * Append content to the end of a file.
	 *
	 * @param string $filePath Target file path
	 * @param string $content  Content to append
	 * @param bool $stream    Use file_put_contents with LOCK_EX when true
	 * @param bool $overwrite Allow overwriting existing file
	 *
	 * @throws TargetIsNotFileException When $overwrite is false and file already exists
	 * 
	 * @return bool
	 */
	public static function appendContent(string $filePath, ?string $content = null, bool $stream = false, bool $overwrite = true): bool
	{
		$filePath = self::convertToNomalizePath($filePath);

		if (!$overwrite && self::isFile($filePath)) {
			throw new TargetIsNotFileException(FileHandlerMessage::getFileIsNotExistsMessage($filePath));
		}

		if ($stream === true) {
			file_put_contents($filePath, $content, FILE_APPEND | LOCK_EX);
		} else {
			self::write($filePath, $content, 'a');
		}

		return true;
	}
	/**
	 * Clear the file status cache for a specific file.
	 *
	 * @param string $filePath
	 *
	 * @return bool
	 */
	public static function clearStatusCache(string $filePath): void
	{
		clearstatcache(true, $filePath);
	}

	/**
	 * Get the file type string (file, dir, link, etc.).
	 *
	 * @param string $filePath
	 *
	 * @throws FileNotFoundException
	 * @throws TargetIsNotFileException
	 * @throws StupidIdeaException
	 * 
	 * @return string
	 */
	public static function getType(string $filePath): string
	{
		$filePath = self::convertToNomalizePath($filePath);

		if (!self::isExists($filePath)) {
			throw new FileNotFoundException(FileHandlerMessage::getFileIsNotExistsMessage($filePath));
		}

		if (!self::isFile($filePath)) {
			throw new TargetIsNotFileException(FileHandlerMessage::getFileIsNotExistsMessage($filePath));
		}

		if (FileValidation::isPharProtocol($filePath)) {
			throw new StupidIdeaException(FileHandlerMessage::getDoNotUsePharProtocolMessage());
		}

		self::clearStatusCache($filePath);

		$return = filetype($filePath);

		return $return;
	}

	/**
	 * Verify that a file is located within a specific base directory (path traversal protection).
	 *
	 * @param string $basePath Base directory
	 * @param string $filePath File to check
	 *
	 * @return bool
	 */
	public static function isContainFolder(string $basePath, string $filePath): bool
	{
		$filePath = self::convertToNomalizePath($filePath);

		$realBasePath = realpath($basePath);
		$realFilePath = realpath(dirname($filePath));

		if ($realFilePath === false || strncmp($realFilePath, $realBasePath, strlen($realBasePath)) !== 0) {
			return false;
		}

		return true;
	}

	/**
	 * Return the encoding of file
	 *
	 * @param string $filePath File path
	 *
	 * @return string|null Charset string or null on failure
	 */
	public static function getFileEncoding(string $filePath): string|null
	{
		$output = [];
		exec('file -i ' . escapeshellarg($filePath), $output);

		if (!isset($output[0])) {
			return null;
		}

		$parts = explode('charset=', $output[0]);

		return $parts[1] ?? null;
	}


	/**
	 * Check that the inode of a file matches the current process inode.
	 *
	 * @param string $filePath File path
	 *
	 * @throws FileNotFoundException
	 * @throws TargetIsNotFileException
	 *
	 * @return bool
	 */
	public static function isCorrectInode(string $filePath): bool
	{
		$filePath = self::convertToNomalizePath($filePath);

		if (!self::isExists($filePath)) {
			throw new FileNotFoundException(FileHandlerMessage::getFileIsNotExistsMessage($filePath));
		}

		if (!self::isFile($filePath)) {
			throw new TargetIsNotFileException(FileHandlerMessage::getFileIsNotExistsMessage($filePath));
		}

		if (FileSystemHandler::getCurrentInode() === self::getInode($filePath)) {
			return true;
		}

		return false;
	}

	/**
	 * Get the inode number of a file.
	 *
	 * @param string $filePath File path
	 *
	 * @throws FileNotFoundException
	 * @throws TargetIsNotFileException
	 *
	 * @return int|bool
	 */
	public static function getInode(string $filePath): int|bool
	{
		$filePath = self::convertToNomalizePath($filePath);

		if (!self::isExists($filePath)) {
			throw new FileNotFoundException(FileHandlerMessage::getFileIsNotExistsMessage($filePath));
		}

		if (!self::isFile($filePath)) {
			throw new TargetIsNotFileException(FileHandlerMessage::getFileIsNotExistsMessage($filePath));
		}

		return FileSystemHandler::getInodeNumber($filePath);
	}

	/**
	 * Close a process pipe and return the exit status.
	 *
	 * @param resource $processResource Process resource from openProcess
	 *
	 * @throws InvalidTypeException
	 *
	 * @return int Exit status code
	 */
	public static function closeProcess($processResource): int
	{
		if (getType($processResource) !== 'resource') {
			throw new InvalidTypeException("");
		}

		$return = pclose($processResource);

		return $return;
	}

	/**
	 * Open a process pipe.
	 *
	 * @param string $processPath Command to execute
	 * @param string $mode        fopen mode for the pipe
	 *
	 * @return resource|bool
	 */
	public static function openProcess($processPath, $mode = FileMode::WRITE_ONLY): mixed
	{
		$handle = popen($processPath, $mode);

		return $handle;
	}


	/**
	 * Get the MIME content type of a file, preferring mime_content_type() then finfo.
	 *
	 * @param string $filePath File path
	 *
	 * @return string|false|null
	 */
	public static function getMIMEContentType(string $filePath): bool|string|null
	{
		$result = null;

		if (function_exists("mime_content_type")) {
			$result = self::getMIMEContentTypeFromMagicMIME($filePath);
		} else if (function_exists("finfo_open") && function_exists("finfo_file")) {
			$result = self::getMIMEContentTypeFromAlaMimetypeExtension($filePath);
		}

		return $result;
	}

	/**
	 * Get content-type of file to use Ala Mime-type extension
	 *
	 * @param string $filePath
	 *
	 * @throws FunctionIsNotExistsException
	 * 
	 * @return bool|string
	 */
	public static function getMIMEContentTypeFromAlaMimetypeExtension($filePath): bool|string
	{
		if (!(function_exists("finfo_open") || function_exists("finfo_file"))) {
			throw new FunctionIsNotExistsException(FunctionMessage::getFunctionIsNotFileMessage());
		}

		$filePath = self::convertToNomalizePath($filePath);

		$fileinfoResource = finfo_open(FILEINFO_MIME_TYPE);

		$result = finfo_file($fileinfoResource, $filePath);

		return $result;
	}

	/**
	 * Get MIME type via the magic.mime database (mime_content_type).
	 *
	 * @param string $filePath File path
	 *
	 * @throws FunctionIsNotExistsException
	 * @throws FileNotFoundException
	 * @throws TargetIsNotFileException
	 *
	 * @return string|bool
	 */
	public static function getMIMEContentTypeFromMagicMIME($filePath): bool|string
	{
		if (!function_exists("mime_content_type")) {
			throw new FunctionIsNotExistsException(FunctionMessage::getFunctionIsNotFileMessage());
		}

		$filePath = self::convertToNomalizePath($filePath);

		if (!self::isExists($filePath)) {
			throw new FileNotFoundException(FileHandlerMessage::getFileIsNotExistsMessage($filePath));
		}

		if (!self::isFile($filePath)) {
			throw new TargetIsNotFileException(FileHandlerMessage::getFileIsNotExistsMessage($filePath));
		}

		return mime_content_type($filePath);
	}

	/**
	 * Parse an INI configuration file.
	 *
	 * @param string $filePath INI file path
	 *
	 * @throws FileNotFoundException
	 * @throws TargetIsNotFileException
	 *
	 * @return array|false
	 */
	public static function parseINI(string $filePath): array|bool
	{
		$filePath = self::convertToNomalizePath($filePath);

		if (!self::isExists($filePath)) {
			throw new FileNotFoundException(FileHandlerMessage::getFileIsNotExistsMessage($filePath));
		}

		if (!self::isFile($filePath)) {
			throw new TargetIsNotFileException(FileHandlerMessage::getFileIsNotExistsMessage($filePath));
		}

		return parse_ini_file($filePath);
	}

	/**
	 * Get the current position of the file read/write pointer.
	 *
	 * @param resource $fileHandler Valid file resource
	 *
	 * @throws InvalidFileHandler
	 *
	 * @return int|false
	 */
	public static function getPointerLocation($fileHandler): bool|int
	{
		if (!self::isValidHandler($fileHandler)) {
			throw new InvalidFileHandler(FileHandlerMessage::getInvalidFileHandler());
		}

		return ftell($fileHandler);
	}

	/**
	 * Delete the last directory separator.
	 *
	 * @param string $filePath
	 *
	 * @return string
	 */
	public static function convertToNomalizePath($filePath): string
	{
		return rtrim($filePath, DIRECTORY_SEPARATOR); // Remove last Directory separator
	}

	/**
	 * Make sure the file handler is of type resource.
	 *
	 * @param string|resource $fileHandler
	 *
	 * @return bool
	 */
	public static function isValidHandler(string|object $fileHandler): bool
	{
		if (getType($fileHandler) !== 'resource') {
			return false;
		}

		if (get_resource_type($fileHandler) !== 'stream') {
			return false;
		}

		return true;
	}

	/**
	 * Sets access and modification time of file Attempts to set the access and modification times of the file named in the filename parameter to the value given in mtime
	 */
	public static function touch(string $filePath, int|null $touchTime = null, int|null $accessTime = null): bool
	{
		return touch($filePath, $touchTime, $accessTime);
	}

	/**
	 * Create a cache file
	 *
	 * @param string $filePath
	 * @param string $destination
	 *
	 * @throws FileNotFoundException
	 * 
	 * @return bool
	 */
	public static function createCache(string $filePath, string $destination): bool
	{
		$filePath = self::convertToNomalizePath($filePath);
		$destination = self::convertToNomalizePath($destination);

		if (!self::isExists($filePath)) {
			throw new FileNotFoundException(FileHandlerMessage::getFileIsNotExistsMessage($filePath));
		}

		if (!self::isFile($filePath)) {
			return false;
		}

		$cached = self::open($filePath, FileMode::WRITE_ONLY);
		fwrite($cached, ob_get_contents());
		fclose($cached);
		ob_end_flush();

		return true;
	}

	/**
	 * Gets whether the file can be read.
	 *
	 * @param string $filePath
	 *
	 * @throws FileNotFoundException
	 * @throws TargetIsNotFileException
	 * 
	 * @return bool
	 */
	public static function isReadable(string $filePath): bool
	{
		$filePath = self::convertToNomalizePath($filePath);

		if (!self::isExists($filePath)) {
			throw new FileNotFoundException(FileHandlerMessage::getFileIsNotExistsMessage($filePath));
		}

		if (!self::isFile($filePath)) {
			throw new TargetIsNotFileException(FileHandlerMessage::getFileIsNotExistsMessage($filePath));
		}

		return is_readable($filePath);
	}

	/**
	 * Check that the file is correct.
	 *
	 * @param string $filePath
	 * @param string $containDirectory
	 *
	 * @throws StupidIdeaException
	 * 
	 * @return bool
	 */
	public static function isFile(string $filePath, ?string $containDirectory = null): bool
	{
		$filePath = self::convertToNomalizePath($filePath);

		if (!FileValidation::isReadable($filePath)) {
			return false;
		}

		if (FileValidation::hasSubfolderSyntax($filePath)) {
			if ($filePath === null) {
				throw new StupidIdeaException(FileHandlerMessage::getDoNotUseSubDirectorySyntaxMessage());
			} else if (!self::isContainFolder($containDirectory, $filePath)) {
				return false;
			}
		}

		if (FileValidation::isPharProtocol($filePath)) {
			throw new StupidIdeaException(FileHandlerMessage::getDoNotUsePharProtocolMessage());
		}

		return is_file($filePath);
	}

	/**
	 * Get size unit array
	 *
	 * @param string $type
	 *
	 * @return array<string>
	 */
	public static function getSizeUnit(string $type): array
	{
		switch ($type) {
			case FileSizeUnit::LONG:
				return ['B', 'Kilo B', 'Mega B', 'Giga B', 'Tera B', 'Peta B', 'Exa B', 'Zetta B', 'Yotta B'];
			default:
			case FileSizeUnit::SHORT:
				return ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
		}
	}

	/**
	 * Format the size of the file.
	 *
	 * @param int|float|string $bytes
	 * @param string $type
	 * @param bool   $round
	 * @param int    $presicion
	 *
	 * @return string
	 */
	public static function formatSize(int|float|string $bytes, ?string $type = NULL, $round = true, $presicion = 0): string
	{
		if ($bytes > 0) {
			$sizes = self::getSizeUnit($type ?? FileSizeUnit::LONG);
			$measure = strlen((string) ($bytes >> 10));
			$factor = $bytes < (1024 ** 6) ? ($measure > 1 ? floor((($measure - 1) / 3) + 1) : 1) : floor((strlen($bytes) - 1) / 3);
			$capacity = $bytes / pow(1024, $factor);
			$multiBytesPrefix = ($capacity === intval($capacity) ?: ($type == FileSizeUnit::LONG ? 'ytes' : ''));
			$bytes = sprintf('%s%s%s', $round ? round($capacity, $presicion) : $capacity, $sizes[$factor], $multiBytesPrefix);
		}

		return $bytes;
	}

	/**
	 * Check the size of the file.
	 *
	 * @param string $filePath
	 *
	 * @throws FileNotFoundException
	 * @throws TargetIsNotFileException
	 * 
	 * @return int
	 */
	public static function getSize(string $filePath, bool $humanReadable = false, ?string $type = NULL): int|string
	{
		$filePath = self::convertToNomalizePath($filePath);

		if (!self::isExists($filePath)) {
			throw new FileNotFoundException(FileHandlerMessage::getFileIsNotExistsMessage(($filePath)));
		}

		if (!self::isFile($filePath)) {
			throw new TargetIsNotFileException(FileHandlerMessage::getFileIsNotExistsMessage($filePath));
		}

		self::clearStatusCache($filePath);

		if ($humanReadable) {
			if (is_readable($filePath)) {
				$bytes = (int) filesize($filePath);
			} else {
				$bytes = (int) is_int($filePath) ? $filePath : -1;
			}

			return self::formatSize($bytes, $type);
		}

		$return = filesize($filePath);

		return $return >= 0 ? $return : -1;
	}

	/**
	 * Gets the standard output stream.
	 *
	 * @param string $mode
	 *
	 * @return bool|resource
	 */
	public static function getOutputStream(string $mode = FileMode::WRITE_ONLY): mixed
	{
		$phpProtocol = new PHPProtocol();
		$handler = self::open($phpProtocol->getOutput(), $mode);

		return $handler;
	}

	/**
	 * Gets the standard input stream.
	 *
	 * @param string $mode
	 *
	 * @return bool|resource
	 */
	public static function getInputStream($mode = 'rb'): mixed
	{
		$phpProtocol = new PHPProtocol();
		$handler = self::open($phpProtocol->getInput(), $mode);

		return $handler;
	}

	/**
	 * Gets the temporary stream.
	 *
	 * @param string $mode
	 *
	 * @return bool|resource
	 */
	public static function getMemoryStream($mode = 'rb'): mixed
	{
		$phpProtocol = new PHPProtocol();
		$handler = self::open($phpProtocol->getMemory(), $mode);

		return $handler;
	}

	/**
	 * Gets the temporary stream.
	 *
	 * @param string $mode
	 *
	 * @return bool|resource
	 */
	public static function getTemporaryStream($mode = 'rb'): mixed
	{
		$phpProtocol = new PHPProtocol();
		$handler = self::open($phpProtocol->getTemporary(), $mode);

		return $handler;
	}

	/**
	 * Gets the filter stream.
	 *
	 * @param string $mode
	 *
	 * @return bool|resource
	 */
	public static function getFilterStream($mode = 'rb'): mixed
	{
		$phpProtocol = new PHPProtocol();
		$handler = self::open($phpProtocol->getFilter(), $mode);

		return $handler;
	}

	/**
	 * Gets the standard output stream.
	 *
	 * @param string $mode
	 *
	 * @return bool|resource
	 */
	public static function getStandardInputStream($mode = FileMode::READ_ONLY): mixed
	{
		$phpProtocol = new PHPProtocol();
		$handler = self::open($phpProtocol->getStandardInput(), $mode);

		return $handler;
	}

	/**
	 * Get the standard output stream
	 *
	 * @param string $mode
	 *
	 * @return bool|resource
	 */
	public static function getStandardOutputStream($mode = FileMode::READ_ONLY): mixed
	{
		$phpProtocol = new PHPProtocol();
		$handler = self::open($phpProtocol->getStandardOutput(), $mode);

		return $handler;
	}

	/**
	 * Seeks on a file pointer Sets the file position indicator for the file referenced by stream
	 * @param mixed $stream
	 * @param int 	$offset
	 * @param int 	$whence
	 * 
	 * @return int
	 */
	public static function seek($stream, int $offset, int $whence = SEEK_SET): int
	{
		return fseek($stream, $offset, $whence);
	}

	/**
	 * Gets the interpreted file content.
	 *
	 * @param string $filePath
	 * @param ArrayObject|array $data
	 *
	 * @throws FileNotFoundException
	 * @throws TargetIsNotFileException
	 * 
	 * @return StringObject|string
	 */
	public static function getInterpretedContent(string $filePath, ArrayObject|array $data = []): StringObject|string
	{
		$filePath = self::convertToNomalizePath($filePath);

		if (!self::isExists($filePath)) {
			throw new FileNotFoundException(FileHandlerMessage::getFileIsNotExistsMessage($filePath));
		}

		if (!self::isFile($filePath)) {
			throw new TargetIsNotFileException(FileHandlerMessage::getFileIsNotExistsMessage($filePath));
		}

		ob_start();

		extract(($data instanceof ArrayObject ? $data->getRawData() : $data) ?? []);

		@include $filePath;

		$buffer = ob_get_contents();

		ob_end_clean();

		return new StringObject($buffer);
	}


	/**
	 * Move the file to a specific location.
	 *
	 * @param string $source
	 * @param string $destination
	 *
	 * @throws FileNotFoundException
	 * @throws TargetIsNotFileException
	 * 
	 * @return bool
	 */
	public static function move(string $source, string $destination): bool
	{
		$source = self::convertToNomalizePath($source);
		$destination = self::convertToNomalizePath($destination);

		if (!self::isExists($source)) {
			throw new FileNotFoundException(FileHandlerMessage::getFileIsNotExistsMessage($source));
		}

		if (self::isFile($destination)) {
			throw new TargetIsNotFileException(FileHandlerMessage::getFileIsNotExistsMessage($source));
		}

		return rename($source, $destination);
	}

	/**
	 * Compute the difference between two files.
	 *
	 * @param StringObject|string $a
	 * @param StringObject|string $b
	 *
	 * @throws FileNotFoundException
	 * @throws TargetIsNotFileException
	 * 
	 * @return ArrayObject
	 */
	public static function computeFileContent(StringObject|string $a, StringObject|string $b): ArrayObject
	{
		/**
		 * File content represented as a StringObject.
		 *
		 * @var StringObject
		 */
		$a = self::read($a);
		$a = $a->splitLines();
		$b = self::read($b)->splitLines();

		$diff = $a->computesDifference($b);

		return $diff;
	}

	/**
	 * Read the csv file.
	 *
	 * @param StringObject|string $filePath
	 * @param string $separator
	 *
	 * @return array
	 */
	public static function readCsvFile(StringObject|string $filePath, string $separator = ','): array
	{
		$readed = [];

		if (($handle = fopen($filePath, 'r')) !== false) {
			while (($data = fgetcsv($handle, 1000, $separator)) !== false) {
				$readed[] = $data;
			}

			fclose($handle);
		}

		return $readed;
	}

	/**
	 * Read the file.
	 *
	 * @param StringObject|string $filePath
	 * @param int    $length
	 * @param string $mode
	 *
	 * @return bool|StringObject
	 */
	public static function read(string $filePath, int $length = -1, string $mode = FileMode::READ_ONLY): bool|StringObject
	{
		$filePath = self::convertToNomalizePath($filePath);

		$fileObject = new FileObject($filePath, false, $mode);
		if (!$fileObject->isEnoughFreeSpace()) {
			self::$lastError = 'Disk space is not enough';

			return false;
		}

		$fileObject->startHandle();

		if (!$fileObject->successToStartHandle()) {
			return false;
		}

		if (!$fileObject->hasReadedContent()) {
			return false;
		}

		if ($length === -1) {
			$fileObject->readAllContent();
		} else {
			$fileObject->readContent($length);
		}

		$content = $fileObject->getReadedContent();

		$fileObject->closeFileHandle();

		return new StringObject($content);
	}

	/**
	 * Gets the symbolic link
	 *
	 * @param string $symbolicLink
	 *
	 * @return bool|string
	 */
	public static function getSymbolicLink(string $symbolicLink): bool|string
	{
		if (!self::isSymbolicLink($symbolicLink)) {
			return false;
		}

		return readlink($symbolicLink);
	}

	/**
	 * Tells whether the filename is a symbolic link Tells whether the given file is a symbolic link.
	 * 
	 * @param string $filePath
	 * @return bool
	 */
	public static function isSymbolicLink(string $filePath): bool
	{
		$filePath = self::convertToNomalizePath($filePath);
		if (is_link($filePath) && self::getType($filePath) === 'link') {
			return true;
		}

		return false;
	}

	/**
	 * Check if the file type is regular.
	 *
	 * @param string $filePath
	 *
	 * @return bool
	 */
	public static function isRegularFile(string $filePath): bool
	{
		$filePath = self::convertToNomalizePath($filePath);
		if (self::getType($filePath) === 'file') {
			return true;
		}

		return false;
	}

	/**
	 * Checks for a match on a line in the file.
	 *
	 * @param string $filePath
	 * @param string $string
	 *
	 * @return bool
	 */
	public static function isEqualByLine(string $filePath, ?string $string = null): bool
	{
		$filePath = self::convertToNomalizePath($filePath);

		$fileObject = new FileObject($filePath, false, FileMode::READ_ONLY);
		$fileObject->startHandle();
		$bool = $fileObject->isEqualByLine($string);
		$fileObject->closeFileHandle();

		return $bool;
	}

	/**
	 * Make sure the file is executable on your system.
	 *
	 * @param string $filePath
	 *
	 * @throws FileNotFoundException
	 * 
	 * @return bool|string|null
	 */
	public static function isExecutable($filePath, $byShell = false): bool|string|null
	{
		$filePath = self::convertToNomalizePath($filePath);
		if (!self::isExists($filePath)) {
			throw new FileNotFoundException(FileHandlerMessage::getFileIsNotExistsMessage($filePath));
		}

		if (!self::isFile($filePath)) {
			return false;
		}

		self::clearStatusCache($filePath);
		$return = is_executable($filePath);

		if (!$return && $byShell && !OperationSystem::isWindows()) {
			$filePath = escapeshellarg($filePath);
			if (!is_readable($filePath) || preg_match('/[^\w\s.-]/', $filePath)) {
				return false;
			}

			return shell_exec("file $filePath");
		}

		return $return;
	}

	/**
	 * XORs the data with the key.
	 *
	 * @param string $data
	 * @param string $key
	 *
	 * @return string
	 */
	public static function xorData($data, $key): string
	{
		$keyLen = strlen($key);
		$dataLen = strlen($data);
		$xoredData = '';

		for ($i = 0; $i < $dataLen; $i++) {
			$xoredData .= chr(ord($data[$i]) ^ ord($key[$i % $keyLen]));
		}

		return $xoredData;
	}

	/**
	 * Gets whether the file can be written to.
	 *
	 * @param string $filePath
	 *
	 * @return bool
	 */
	public static function isWritable(string $filePath): bool
	{
		$filePath = self::convertToNomalizePath($filePath);

		if (!self::isExists($filePath)) {
			return true;
		}

		if (!self::isFile($filePath)) {
			return true;
		}

		self::clearStatusCache($filePath);
		$return = is_writable($filePath);

		return $return;
	}

	/**
	 * Delete the file.
	 *
	 * @param string $filePath
	 *
	 * @throws FileNotFoundException
	 * @throws TargetIsNotFileException
	 * 
	 * @return bool
	 */
	public static function delete(string $filePath): bool
	{
		$filePath = self::convertToNomalizePath($filePath);
		if (!self::isExists($filePath)) {
			throw new FileNotFoundException(FileHandlerMessage::getFileIsNotExistsMessage($filePath));
		}

		if (!self::isFile($filePath)) {
			throw new TargetIsNotFileException(FileHandlerMessage::getFileIsNotExistsMessage($filePath));
		}

		return unlink($filePath);
	}

	/**
	 * Copy the file.
	 *
	 * @param string $filePath
	 * @param string $destination
	 *
	 * @throws FileNotFoundException
	 * 
	 * @return bool
	 */
	public static function copy(string $filePath, string $destination): bool
	{
		$filePath = self::convertToNomalizePath($filePath);
		if (!self::isExists($filePath)) {
			throw new FileNotFoundException(FileHandlerMessage::getFileIsNotExistsMessage($filePath));
		}

		if (!self::isFile($filePath)) {
			throw new FileNotFoundException(FileHandlerMessage::getFileIsNotExistsMessage($filePath));
		}

		$return = copy($filePath, $destination);

		return $return;
	}

	/**
	 * Combine the two files.
	 *
	 * @param string $filePath
	 * @param string $mergeFile
	 *
	 * @return bool
	 */
	public static function merge(string $filePath, string $mergeFile): bool
	{
		$filePath = self::convertToNomalizePath($filePath);

		$fileObject = new FileObject($filePath, false, 'a');
		$fileObject->startHandle();

		$fileObject->appendContent($mergeFile);

		$fileObject->closeFileHandle();

		return true;
	}

	/**
	 * Change the umask of file.
	 *
	 * @param int $mask
	 *
	 * @return int
	 */
	public static function changeUmask($mask): int
	{
		return umask($mask);
	}

	/**
	 * Detect file type by reading the binary header (magic number).
	 *
	 * Reads up to 100 bytes, unpacks a big-endian unsigned long,
	 * and matches against known HEADER_SIGNATURES.
	 *
	 * @param string $filePath File path
	 *
	 * @return string|bool File type identifier or false if unrecognized
	 */
	public static function getHeaderType(string $filePath): string|bool
	{
		$size = filesize($filePath);
		$size = $size > 100 ? 100 : $size;

		if ($size <= 4) {
			return false;
		}

		$header = self::read($filePath, $size);
		if ($header) {
			$bigEndianUnpack = unpack(UnpackArguments::BIG_ENDIAN_UNSIGNED_LONG, $header);
		} else {
			return false;
		}

		/* ISO 8859-1 */
		$fileSignature = array_shift($bigEndianUnpack);

		if ($fileSignature === 0) {
			return 'EMPTY';
		}

		$headers = [
			'EXE' => [
				'0x4D5A5700',
				'0x4D5A5000',
				'0x4D5AC401',
				'0x4D5A8800', /* MZ */
				'0x4D5A9000'
			],
			'MP3' => [
				/* ftyp3gp4isom3gp4 */
				'0x18', /* !DO (p Hq) */
				'0x3C21444F',
				'0x4D617220', /* ID3 (TSSE) */
				'0x2F271E8', /* ID3 (GEOB, TYER, TALB, TSSE, TXXX, TPE1, TIT2, TCON, PRIV, TRCK) */
				'0x49443303', /* ID3 (TALB, TIT2, TSS, TT2, FTT2, TPE1) */
				'0x49443302',
				'0x2E4BEAA',
				'0xFFFBE444',
				'0xFFFBE044', /* d (dInfo) */
				'0xFFFBD064', /* D (DInfo) */
				'0xFFFBD044',
				'0xFFFBC060',
				'0xFFFBB064', /* ` */
				'0xFFFBB060', /*  */
				'0xFFFBB004',
				'0xFFFBB000',
				'0xFFFBA064', /* ` */
				'0xFFFBA060', /* D */
				'0xFFFBA044',
				'0xFFFBA040',
				'0xFFFB9464',
				'0xFFFB9444',
				'0xFFFB9264',
				'0xFFFB90C4',
				'0xFFFB90C0', /* d (dInfo) */
				'0xFFFB9064', /* ` */
				'0xFFFB9060', /* D (DXing) */
				'0xFFFB9044', /* @ */
				'0xFFFB9040',
				'0xFFFB9004',
				'0xFFFB9000', /* p */
				'0xFFFB70C4', /* ` */
				'0xFFFB60C4',
				'0xFFFB50C4',
				'0xFFFB30C4',
				'0xFFFA9400',
				'0xFFF3C8C4',
				'0xFFF3A064',
				'0xFFF380C4',
				'0xFFF37454',
				'0xFFE388C4', /* ÿû */
				'0xC3BFC3BB',
				'0x11200100',
				'0xD0AF339',
				'0xD0AE88A',
				'0xD0A4944',
				'0xD0A6085', /* � (dInfo)*/
				'0xAFFFB80',
			],
			'JPG' => [
				'0xFFD8FFEE', /* JPG, JFIF */
				'0xFFD8FFE0', /* Exif JPG */
				'0xFFD8FFE1',
				'0xFFD8FFDB', /* MZ */
				'0xFFD8FFE2',
				'0xFFD8FFEC'
			],
			'BMP' => [
				'0x424D3653',
				'0x424D569F',
				'0x424D56FE',
				'0x424D3616',
			],
			'XP3' => [
				'0x5850330D',
				'0x424D0638',
				'0x424D3404',
				'0x424D365C',
			],
			'SWF' => [
				'0x46575306',
				'0x46575309',
				'0x43575306'
			],
			'OGG' => [
				'4294676676',
				'0x4F676753'
			],
			'MID' => [
				'0x4D546864', /* MThd */
				'0xB7075'
			],
			'GBA' => [
				'0x2E0000EA'
			],
			'PSD' => [
				'0x38425053'
			],
			'NES' => [
				'0x4E45531A'
			],
			'IDX' => [
				'0x494E4458'
			],
			'LZ' => [
				'0x4C5A4950'
			],
			'ISO' => [
				'0x44303031'
			],
			'TDEF' => [
				'0x54444546'
			],
			'FLAG' => [
				'0x664C6143'
			],
			'ZIP' => [
				'0xC3130',
				'0x504B0304' /* PK, KPZIP/PPTX */
			],
			'GIF' => [
				'0x46383761', /* GIF8 (GIF87a) */
				'0x47494638' /* GIF8 (GIF89a) */
			],
			'DLL' => [
				'0x4D5A6C00'
			],
			'LNK' => [
				'0x4C000000' /* LLNK */
			],
			'TORRENTDATA' => [
				'0x64343A69'
			],
			'PDF' => [
				'0x50445431',
				'0x25504446'
			],
			'AIMPPL4' => [
				'0xFFFE2300'
			],
			'CHM' => [
				'0x49545346'
			],
			'MSI' => [
				'0xD0CF11E0'
			],
			'WAV' => [
				'0x50494646',
				'0x52494646' /* RIFF */
			],
			'PCX' => [
				'0xA050101'
			],
			'DAT' => [
				'0x6431303A'
			],
			'WMV' => [
				'0x3026B275'
			],
			'WEBM' => [
				'0x1A45DFA3'
			],
			'RAR' => [
				'0x52617221' /* Rar! */
			],
			'EGG' => [
				'0x45474741'
			],
			'ARJ' => [
				'0x60EA2900'
			],
			'TORRENT' => [
				'0x64383A61'
			],
			'NWZ' => [
				'0x5B4E575A'
			],
			'FLV' => [
				'0x464C5601'
			],
			'IPK' => [
				'0x213C6172'
			],
			'UnityFS' => [
				'0x556E6974'
			],
			'PHP' => [
				'0x3C3F7068'
			],
			'XML' => [
				'0x3C3F786D'
			],
			'URL' => [
				'0x5B7B3030',
				'0x5B444546'
			],
			'MUS' => [
				'0x454E4947'
			],
			'PNG' => [
				'0x1C'
			],
			'CAP' => [
				'0x2D2D2D2D'
			],
			'TLB' => [
				'0x4D534654'
			],
			'ICODATA' => [
				'0x100'
			],
			'3GP' => [
				'0x79703367'
			],
			'MP4' => [
				'32', /* MP4 (ftypisom isomiso2avc1mp41) */
				'20',
			],
		];

		foreach ($headers as $type => $values) {
			if (in_array($fileSignature, $values, true)) {
				return $type;
			}
		}

		if ($fileSignature === 0) {
			return 'EMPTY';
		}

		return false;
	}

	/**
	 * Get the contents of the file via raw fread.
	 *
	 * @param string $filePath Absolute or relative file path
	 *
	 * @throws FileNotFoundException
	 * @throws TargetIsNotFileException
	 *
	 * @return string
	 */
	public static function getContent(string $filePath): string
	{
		$filePath = self::convertToNomalizePath($filePath);

		if (!self::isExists($filePath)) {
			throw new FileNotFoundException(FileHandlerMessage::getFileIsNotExistsMessage($filePath));
		}

		if (!self::isFile($filePath)) {
			throw new TargetIsNotFileException(FileHandlerMessage::getFileIsNotExistsMessage($filePath));
		}

		$fileHandler = self::open($filePath, FileMode::READ_ONLY);
		$fileSize = self::getSize($filePath);
		$return = fread($fileHandler, $fileSize);
		fclose($fileHandler);

		return $return;
	}

	/**
	 * Stream a file to the output buffer for download.
	 *
	 * @param string $filePath   File path
	 * @param int    $bufferSize Read chunk size in bytes (default 8KB)
	 *
	 * @throws FileNotFoundException
	 * @throws TargetIsNotFileException
	 *
	 * @return bool
	 */
	public static function download(string $filePath, int $bufferSize = 0): bool
	{
		$filePath = self::convertToNomalizePath($filePath);

		if (!self::isExists($filePath)) {
			throw new FileNotFoundException(FileHandlerMessage::getFileIsNotExistsMessage($filePath));
		}

		if (!self::isFile($filePath)) {
			throw new TargetIsNotFileException(FileHandlerMessage::getFileIsNotExistsMessage($filePath));
		}

		$fileHandler = self::open($filePath, 'rb');
		if ($fileHandler === false) {
			return false;
		}

		while (!feof($fileHandler)) {
			print (@fread($fileHandler, $bufferSize > 0 ? $bufferSize : (1024 * 8)));
			ob_flush();
			flush();
		}

		fclose($fileHandler);

		return true;
	}

	/**
	 * Get the base name of a path without its extension.
	 *
	 * @param string $path The path.
	 *
	 * @return string The base name with the extension stripped.
	 */
	public static function getFilename(string $path): string
	{
		return pathinfo($path, PATHINFO_FILENAME);
	}

	/**
	 * Get the file's extension.
	 *
	 * @param string $filePath
	 *
	 * @return string
	 */
	public static function getExtension(string $filePath): string
	{
		$extension = null;

		$filePath = self::convertToNomalizePath($filePath);

		if (function_exists("pathinfo")) {
			$extension = self::getExtensionByFilePath($filePath);
		} else {
			$isDotExists = strrchr($filePath, '.');

			if ($isDotExists !== false) {
				$extension = substr($isDotExists, 1);
			}
		}

		return $extension;
	}

	/**
	 * Return file pointer of specific file
	 *
	 * @param string  $filePath
	 * @param string  $mode
	 * @param boolean $useIncludePath
	 *
	 * @return bool|resource
	 */
	public static function open(?string $filePath = null, ?string $mode = null, bool $useIncludePath = false): mixed
	{
		$handler = fopen($filePath, $mode, $useIncludePath);

		return $handler;
	}

	/**
	 * Generate a hash from file contents using the specified algorithm.
	 *
	 * @param string $algorithm Hash algorithm name (e.g. 'md5', 'sha256')
	 * @param string $filePath File path
	 * @param bool|null $rawContents Output raw binary when true
	 *
	 * @return string
	 */
	public static function generateHashByContents(string $algorithm = 'md5', string $filePath = '', bool|null $rawContents = true): string
	{
		$return = hash_file($algorithm, $filePath, $rawContents);

		return $return;
	}

	/**
	 * Check if the phar file is valid.
	 *
	 * @param string $pharFile
	 *
	 * @return bool
	 */
	public static function isValidPharFile(string $pharFile): bool
	{
		if (ini_get('phar.readonly')) {
			return true;
		}

		try {
			$phar = new \Phar($pharFile);
			unset($phar);

			return true;
		} catch (Exception $e) {
			if (!$e instanceof UnexpectedValueException && !$e instanceof \PharException) {
				throw $e;
			}
			return false;
		}
	}

	/**
	 * Get the standard error stream (php://stderr).
	 *
	 * @param string $mode fopen mode
	 *
	 * @return bool|resource
	 */
	public static function getStandardErrorStream(string $mode = FileMode::READ_ONLY): mixed
	{
		$phpProtocol = new PHPProtocol();

		$handler = self::open($phpProtocol->getStandardError(), $mode);

		return $handler;
	}

	/**
	 * Compare two files for identity using size, partial read, and hash comparison.
	 *
	 * @param string $firstPath  First file path
	 * @param string $secondPath Second file path
	 * @param int    $chunkSize  Bytes for the initial quick comparison
	 *
	 * @return bool
	 */
	public static function isEqual($firstPath, $secondPath, $chunkSize = 500): bool
	{
		// https://stackoverflow.com/questions/30107521/check-if-same-image-has-already-been-uploaded-by-comparing-base64

		// First check if file are not the same size as the fastest method
		if (filesize($firstPath) !== filesize($secondPath)) {
			return false;
		}

		// Compare the first ${chunkSize} bytes
		// This is fast and binary files will most likely be different
		$fp1 = self::open($firstPath, FileMode::READ_ONLY);
		$fp2 = self::open($secondPath, FileMode::READ_ONLY);
		$chunksAreEqual = fread($fp1, $chunkSize) == fread($fp2, $chunkSize);
		fclose($fp1);
		fclose($fp2);

		if (!$chunksAreEqual) {
			return false;
		}

		// Compare hashes
		// SHA1 calculates a bit faster than MD5
		$firstChecksum = sha1_file($firstPath);
		$secondChecksum = sha1_file($secondPath);
		if ($firstChecksum !== $secondChecksum) {
			return false;
		}

		return true;
	}

	/**
	 * Assert that a file exists at the given path.
	 *
	 * @param string $filePath
	 * 
	 * @throws FileNotFoundException
	 */
	private static function assertFileExists(string $filePath): void
	{
		if (!self::isExists($filePath)) {
			throw new FileNotFoundException(FileHandlerMessage::getFileIsNotExistsMessage($filePath));
		}
	}

	/**
	 * Assert that the path points to a valid file.
	 *
	 * @param string $filePath
	 * 
	 * @throws TargetIsNotFileException
	 */
	private static function assertIsFile(string $filePath): void
	{
		if (!self::isFile($filePath)) {
			throw new TargetIsNotFileException(FileHandlerMessage::getFileIsNotExistsMessage($filePath));
		}
	}

	/**
	 * Assert that the given value is a valid stream resource.
	 *
	 * @param string|resource $handler
	 * 
	 * @throws InvalidFileHandler
	 */
	private static function assertValidHandler(mixed $handler): void
	{
		if (!self::isValidHandler($handler)) {
			throw new InvalidFileHandler(FileHandlerMessage::getInvalidFileHandler());
		}
	}

	/**
	 * Convert a local filesystem path to a file:// URI.
	 *
	 * @param string $path The local path.
	 *
	 * @return string A file URI (e.g., 'file:///C:/path' or 'file:///usr/local').
	 */
	public static function toUri(string $path): string
	{
		$normalized = str_replace('\\', '/', $path);

		if ($normalized === '') {
			return 'file://';
		}

		// Windows drive letters need an extra leading slash so the URI authority is empty.
		if (preg_match('/^[A-Za-z]:/', $normalized) === 1) {
			$normalized = '/' . $normalized;
		}

		if ($normalized[0] !== '/') {
			$normalized = '/' . $normalized;
		}

		return 'file://' . $normalized;
	}

	/**
	 * Extract the local filesystem path from a file:// URI.
	 *
	 * If the input is not a file URI it is returned as-is.
	 *
	 * @param string $uri The URI to decode.
	 *
	 * @return string The local path.
	 */
	public static function fromUri(string $uri): string
	{
		if (!str_starts_with($uri, 'file://')) {
			return $uri;
		}

		$path = substr($uri, 7);

		// Strip the extra leading '/' inserted before a Windows drive letter.
		if (preg_match('#^/([A-Za-z]):/#', $path) === 1) {
			$path = substr($path, 1);
		}

		return $path;
	}

	/**
	 * Replace the extension of a path with a new one.
	 *
	 * Passing an empty extension removes the extension entirely.
	 *
	 * @param string $path      The path.
	 * @param string $extension The new extension (with or without a leading dot).
	 *
	 * @return string The path with the new extension.
	 */
	public static function withExtension(string $path, string $extension): string
	{
		$info = pathinfo($path);
		$dir = $info['dirname'] ?? '';
		$name = $info['filename'] ?? '';

		$ext = ltrim($extension, '.');
		$basename = $ext === '' ? $name : $name . '.' . $ext;

		if ($dir === '' || $dir === '.') {
			return $basename;
		}

		return $dir . DIRECTORY_SEPARATOR . $basename;
	}

	/**
	 * Strip the file extension from a path.
	 *
	 * @param string $path The path.
	 *
	 * @return string The path with no extension.
	 */
	public static function withoutExtension(string $path): string
	{
		return self::withExtension($path, '');
	}
}
