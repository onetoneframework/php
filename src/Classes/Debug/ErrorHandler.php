<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Debug;

#region use

use Clover\Classes\Data\StringObject;
use Clover\Classes\Data\JSONHandler;
use Clover\Classes\Date\Date;
use Clover\Classes\Debug\TraceObject;
use Clover\Classes\Exception\Handler as ExceptionHandler;
use Clover\Classes\File\Functions as FileFunction;
use Clover\Classes\Header;
use Clover\Classes\Linker\IDELink;
use Clover\Classes\File\Handler as FileHandler;
use Clover\Classes\Logging\StructuredKernelLogger;
use Clover\Classes\OperationSystem;
use Clover\Classes\Reflection\Handler as ReflectionHandler;
use Clover\Classes\System\Output;
use Clover\Enumeration\FileSizeUnit;
use Clover\Framework\Component\TraceContext;
use function get_class;
use function is_string;
use function filter_var;
use function preg_match;
use function sprintf;
use function str_contains;
use function strtolower;
use const FILTER_VALIDATE_BOOL;

#endregion

/**
 * Error Handler Class
 * 
 * @package Clover\Classes\Debug
 */
class ErrorHandler
{
	private const INTERNAL_SERVER_ERROR_STATUS_CODE = 500;
	private const GENERIC_HTTP_EXCEPTION_TEMPLATE = '<!doctype html><html lang="en"><head><meta charset="UTF-8"><title>Internal Server Error</title></head><body><h1>Internal Server Error</h1><p>An unexpected error occurred. Reference: %s</p></body></html>';
	private const DEBUG_CONTEXT_MAXIMUM_DEPTH = 6;
	private const DEBUG_CONTEXT_MAXIMUM_ITEMS = 250;
	private const DEBUG_CONTEXT_MAXIMUM_STRING_LENGTH = 8192;
	private const EXECUTION_TIME_PRECISION = 6;
	private const EXCEPTION_CHAIN_MAXIMUM_DEPTH = 32;
	private const SERVER_HTTP_HEADER_PREFIX = 'HTTP_';
	private const REDACTED_VALUE = '[REDACTED]';
	private const TRUNCATED_VALUE = '[TRUNCATED]';

    private function isProfilerEnabled(): bool
    {
        return filter_var($_ENV['PROFILER_ENABLED'] ?? false, FILTER_VALIDATE_BOOL) === true;
    }

	private function isDebuggable(): bool
	{
		$appDebug = $_ENV['APP_DEBUG'] ?? getenv('APP_DEBUG');
		$debuggable = $_ENV['IS_DEBUGGABLE'] ?? getenv('IS_DEBUGGABLE');

		return filter_var($appDebug, FILTER_VALIDATE_BOOL) === true
			|| filter_var($debuggable, FILTER_VALIDATE_BOOL) === true;
	}

    /**
     * Register error and exception handlers
     * 
     * @return void
     */
    public static function register(): void
    {
        $handler = new static();

        ExceptionHandler::setExceptionHandler([$handler, 'handleException']);
        ExceptionHandler::setErrorHandler([$handler, 'handleError']);
        ExceptionHandler::registerShutdownFunction([$handler, 'handleShutdown']);
    }

    /**
     * Handle shutdown errors
     */
    public function handleShutdown(): void
    {
        $lastError = error_get_last();
        if ($lastError === null) {
            return;
        }

        ob_start();
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        ob_flush();
        flush();

        $isCommandLineInterface = OperationSystem::isCommandLineInterface();
        if ($isCommandLineInterface) {
            $content = $this->compile(__DIR__ . '/../../Template/CLI/Shutdown.php', ['error' => $lastError]);
        } elseif (!$this->isDebuggable()) {
            $correlationIdentifier = TraceContext::getTraceId();
            $this->logUnhandledError($lastError, $correlationIdentifier);
            http_response_code(self::INTERNAL_SERVER_ERROR_STATUS_CODE);
            $content = $this->renderGenericHttpException($correlationIdentifier);
        } else {
            $arguments = ['error' => $lastError];
            $arguments = $this->enrichWithVsCodeDebugContext(
                $arguments,
                (string) ($lastError['file'] ?? ''),
                max(1, (int) ($lastError['line'] ?? 1))
            );
            $content = $this->compile(__DIR__ . '/../../Template/Shutdown.php', $arguments);
        }

        $content = ($content instanceof StringObject) ? $content->__toString() : $content;
        exit($content);
    }

    /**
     * Handle errors
     *
     * @param int    $errorRaised
     * @param string $errorMessage
     * @param string $fileName
     * @param int    $lineNumber
     * 
     * @return void
     */
    public function handleError($errorRaised, $errorMessage, $fileName, $lineNumber): void
    {
        if (!(error_reporting() & $errorRaised)) {
            return;
        }

        $isCommandLineInterface = OperationSystem::isCommandLineInterface();
        if (
            !$isCommandLineInterface
            && !$this->isDebuggable()
            && ($errorRaised === E_ERROR || $errorRaised === E_USER_ERROR)
        ) {
            $correlationIdentifier = TraceContext::getTraceId();
            $this->logUnhandledError([
                'type' => $errorRaised,
                'file' => $fileName,
                'line' => $lineNumber,
            ], $correlationIdentifier);
            http_response_code(self::INTERNAL_SERVER_ERROR_STATUS_CODE);

            exit($this->renderGenericHttpException($correlationIdentifier));
        }

        $arguments = [
            'error_raised' => $errorRaised,
            'error_message' => $errorMessage,
            'filename' => $fileName,
            'linenumber' => $lineNumber
        ];
        $arguments = $this->enrichWithVsCodeDebugContext($arguments, $fileName, max(1, $lineNumber));

        switch ($errorRaised) {
            case E_ERROR: //1
                $content = $this->compile(__DIR__ . '/../../Template/Error.php', $arguments);
                exit($content);
            case E_WARNING: // 2
            case E_PARSE: //4
            case E_NOTICE: //8
            case E_CORE_WARNING: //32
            case E_DEPRECATED: //8192
            case E_STRICT: // 2048
            case E_USER_ERROR: //256
            default:
                break;
        }
    }

    /**
     * Compile a template with arguments
     *
     * @param string     $path
     * @param array|null $arguments
     *
     * @return string|StringObject
     */
    private function compile(string $path, ?array $arguments): string|StringObject
    {
        return FileFunction::getInterpretedContent($path, $arguments);
    }

    /**
     * Handle exceptions
     *
     * @param \Throwable $exception
     *
     * @return void
     */
	public function handleException(\Throwable $exception): void
	{
		$isCommandLineInterface = OperationSystem::isCommandLineInterface();
		if (!$isCommandLineInterface) {
			http_response_code(self::INTERNAL_SERVER_ERROR_STATUS_CODE);
		}

		Output::print($this->renderException($exception, $isCommandLineInterface));
	}

	/**
	 * Render an exception for the current execution boundary.
	 *
	 * @param \Throwable $exception
	 * @param bool|null $isCommandLineInterface
	 *
	 * @return string|StringObject
	 */
	public function renderException(\Throwable $exception, ?bool $isCommandLineInterface = null): string|StringObject
	{
		$isCommandLineInterface ??= OperationSystem::isCommandLineInterface();
		if (!$isCommandLineInterface && !$this->isDebuggable()) {
			$correlationIdentifier = TraceContext::getTraceId();
			$this->logUnhandledException($exception, $correlationIdentifier);

			return $this->renderGenericHttpException($correlationIdentifier);
		}

		return $this->renderDetailedException($exception, $isCommandLineInterface);
	}

	/**
	 * Render a detailed exception for trusted debugging or command-line use.
	 *
	 * @param \Throwable $exception
	 * @param bool $isCommandLineInterface
	 *
	 * @return string|StringObject
	 */
	private function renderDetailedException(\Throwable $exception, bool $isCommandLineInterface): string|StringObject
	{
		$code = $exception->getCode();
		$message = $exception->getMessage();
		$file = $exception->getFile();
		$line = $exception->getLine();
		$traces = ReflectionHandler::parseTrace($exception->getTrace());
		$fileSize = FileHandler::getSize($file, true, FileSizeUnit::SHORT);

		$exceptionCode = TraceObject::renderCodeBlock($file, $line, !$isCommandLineInterface);
		$exceptionChain = [];
		$currentException = $exception;
		for ($exceptionIndex = 0; $exceptionIndex < self::EXCEPTION_CHAIN_MAXIMUM_DEPTH; $exceptionIndex++) {
			$exceptionChain[] = [
				'class' => $currentException::class,
				'code' => $currentException->getCode(),
				'message' => $currentException->getMessage(),
				'file' => $currentException->getFile(),
				'line' => $currentException->getLine(),
				'trace' => $currentException->getTraceAsString(),
			];

			$currentException = $currentException->getPrevious();
			if ($currentException === null) {
				break;
			}
		}

		if ($this->isProfilerEnabled()) {
			Profiler::recordException($exception, $traces);
		}

		$arguments = [
			'code' => $code,
			'message' => $message,
			'file' => $file,
			'line' => $line,
			'traces' => $traces,
			'className' => get_class($exception),
			'exceptionChain' => $exceptionChain,
			'fileSize' => $fileSize,
			'exceptionCode' => $exceptionCode,
			'troubleshootingHints' => $this->resolveTroubleshootingHints($exception),
			'debugSections' => $isCommandLineInterface ? [] : $this->collectDebugSections(),
		];
		$arguments = $this->enrichWithVsCodeDebugContext($arguments, $file, max(1, $line));

		if ($isCommandLineInterface) {
			return $this->compile(__DIR__ . '/../../Template/CLI/Exception.php', $arguments);
		}

		return $this->compile(__DIR__ . '/../../Template/Exception.php', $arguments);
	}

	/**
	 * Collect request and runtime diagnostics for the trusted debug page.
	 *
	 * @return array<int, array{title: string, count: int, content: string}>
	 */
	private function collectDebugSections(): array
	{
		$requestHeaders = [];
		foreach ($_SERVER as $serverKey => $serverValue) {
			if (str_starts_with($serverKey, self::SERVER_HTTP_HEADER_PREFIX)) {
				$headerName = str_replace('_', '-', substr($serverKey, strlen(self::SERVER_HTTP_HEADER_PREFIX)));
				$requestHeaders[$headerName] = $serverValue;
				continue;
			}

			if ($serverKey === 'CONTENT_TYPE' || $serverKey === 'CONTENT_LENGTH') {
				$requestHeaders[str_replace('_', '-', $serverKey)] = $serverValue;
			}
		}
		ksort($requestHeaders);

		$processEnvironment = getenv();
		if (!is_array($processEnvironment)) {
			$processEnvironment = [];
		}
		$environment = array_replace($processEnvironment, $_ENV);
		ksort($environment);

		$phpConfiguration = ini_get_all(null, false);
		if (!is_array($phpConfiguration)) {
			$phpConfiguration = [];
		}
		ksort($phpConfiguration);

		$includedFiles = get_included_files();
		sort($includedFiles);
		$loadedExtensions = ReflectionHandler::getLoadedExtensionNames();
		sort($loadedExtensions);

		$requestStartedAt = $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true);
		$runtime = [
			'trace_id' => TraceContext::getTraceId(),
			'php_version' => OperationSystem::getPHPVersion(),
			'php_sapi' => PHP_SAPI,
			'operating_system' => OperationSystem::getBuiltOperationSystemString(),
			'timezone' => Date::getDefaultTimezone(),
			'server_software' => OperationSystem::getServerSoftware(),
			'http_status_code' => http_response_code(),
			'loaded_ini_file' => OperationSystem::getLoadedINIFiles(),
			'scanned_ini_files' => OperationSystem::getScannedINIFiles(),
			'memory_usage' => FileFunction::formatSize(OperationSystem::getMemoryUsage(), FileSizeUnit::SHORT),
			'peak_memory_usage' => FileFunction::formatSize(OperationSystem::getPeakMemoryUsage(), FileSizeUnit::SHORT),
			'execution_time_seconds' => round(microtime(true) - $requestStartedAt, self::EXECUTION_TIME_PRECISION),
		];

		$request = [
			'method' => $_SERVER['REQUEST_METHOD'] ?? '',
			'uri' => $_SERVER['REQUEST_URI'] ?? '',
			'protocol' => $_SERVER['SERVER_PROTOCOL'] ?? '',
			'scheme' => $_SERVER['REQUEST_SCHEME'] ?? '',
			'host' => $_SERVER['HTTP_HOST'] ?? '',
			'remote_address' => $_SERVER['REMOTE_ADDR'] ?? '',
			'content_type' => $_SERVER['CONTENT_TYPE'] ?? '',
			'content_length' => $_SERVER['CONTENT_LENGTH'] ?? '',
			'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
		];

		$session = isset($_SESSION) && is_array($_SESSION) ? $_SESSION : [];

		return [
			$this->createDebugSection('Runtime', $runtime),
			$this->createDebugSection('Request', $request),
			$this->createDebugSection('Request headers', $requestHeaders),
			$this->createDebugSection('Response headers', Header::getHeaders()),
			$this->createDebugSection('Query parameters', $_GET),
			$this->createDebugSection('Body parameters', $_POST),
			$this->createDebugSection('Uploaded files', $_FILES),
			$this->createDebugSection('Cookies', $_COOKIE, true),
			$this->createDebugSection('Session', $session, true),
			$this->createDebugSection('Server variables', $_SERVER),
			$this->createDebugSection('Environment variables', $environment),
			$this->createDebugSection('PHP configuration', $phpConfiguration),
			$this->createDebugSection('Loaded extensions', $loadedExtensions),
			$this->createDebugSection('Included files', $includedFiles),
		];
	}

	/**
	 * Create a printable diagnostic section with sensitive values removed.
	 *
	 * @param string $title
	 * @param array<mixed> $values
	 * @param bool $redactAllValues
	 *
	 * @return array{title: string, count: int, content: string}
	 */
	private function createDebugSection(string $title, array $values, bool $redactAllValues = false): array
	{
		$normalizedValues = $this->normalizeDebugArray($values, $redactAllValues);
		$content = JSONHandler::encode(
			$normalizedValues,
			JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR
		);

		return [
			'title' => $title,
			'count' => count($normalizedValues),
			'content' => is_string($content) ? $content : '{}',
		];
	}

	/**
	 * Normalize an array without invoking application object behavior.
	 *
	 * @param array<mixed> $values
	 * @param bool $redactAllValues
	 * @param int $depth
	 *
	 * @return array<mixed>
	 */
	private function normalizeDebugArray(array $values, bool $redactAllValues = false, int $depth = 0): array
	{
		$normalizedValues = [];
		$itemCount = 0;
		foreach ($values as $key => $value) {
			if ($itemCount >= self::DEBUG_CONTEXT_MAXIMUM_ITEMS) {
				$normalizedValues[self::TRUNCATED_VALUE] = sprintf(
					'More than %d entries were available.',
					self::DEBUG_CONTEXT_MAXIMUM_ITEMS
				);
				break;
			}

			$isSensitive = $redactAllValues || (is_string($key) && $this->isSensitiveDebugKey($key));
			$normalizedValues[$key] = $isSensitive
				? self::REDACTED_VALUE
				: $this->normalizeDebugValue($value, $depth, false);
			$itemCount++;
		}

		return $normalizedValues;
	}

	/**
	 * Convert a diagnostic value into a bounded JSON-safe representation.
	 *
	 * @param mixed $value
	 * @param int $depth
	 * @param bool $redactAllValues
	 *
	 * @return mixed
	 */
	private function normalizeDebugValue(mixed $value, int $depth, bool $redactAllValues): mixed
	{
		if ($depth >= self::DEBUG_CONTEXT_MAXIMUM_DEPTH) {
			return self::TRUNCATED_VALUE;
		}

		if (is_array($value)) {
			return $this->normalizeDebugArray($value, $redactAllValues, $depth + 1);
		}

		if (is_object($value)) {
			return sprintf('object(%s)', $value::class);
		}

		if (is_resource($value)) {
			return sprintf('resource(%s)', get_resource_type($value));
		}

		if (is_string($value) && strlen($value) > self::DEBUG_CONTEXT_MAXIMUM_STRING_LENGTH) {
			return substr($value, 0, self::DEBUG_CONTEXT_MAXIMUM_STRING_LENGTH) . self::TRUNCATED_VALUE;
		}

		return $value;
	}

	/**
	 * Determine whether a diagnostic key identifies confidential data.
	 *
	 * @param string $key
	 *
	 * @return bool
	 */
	private function isSensitiveDebugKey(string $key): bool
	{
		return preg_match(
			'/(?:password|passwd|pwd|secret|token|authorization|cookie|session|credential|api[_-]?key|access[_-]?key|private[_-]?key|csrf|xsrf|auth[_-]?(?:pw|user)|remote[_-]?user|database[_-]?url|redis[_-]?url|mailer[_-]?dsn|dsn|proxy)/i',
			$key
		) === 1;
	}

	/**
	 * Log the full exception context without returning it to a client.
	 *
	 * @param \Throwable $exception
	 * @param string $correlationIdentifier
	 *
	 * @return void
	 */
	private function logUnhandledException(\Throwable $exception, string $correlationIdentifier): void
	{
		try {
			StructuredKernelLogger::error('exception.unhandled', [
				'correlation_id' => $correlationIdentifier,
				'exception_class' => $exception::class,
				'file' => $exception->getFile(),
				'line' => $exception->getLine(),
				'trace' => $exception->getTraceAsString(),
			]);
		} catch (\Throwable $loggingException) {
			error_log(sprintf(
				'Unable to write structured exception log for %s: %s',
				$correlationIdentifier,
				$loggingException::class
			));
		}
	}

	/**
	 * Render the safe HTTP error content used outside trusted debugging.
	 *
	 * @param string $correlationIdentifier
	 *
	 * @return string
	 */
	private function renderGenericHttpException(string $correlationIdentifier): string
	{
		return sprintf(
			self::GENERIC_HTTP_EXCEPTION_TEMPLATE,
			htmlspecialchars($correlationIdentifier, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
		);
	}

	/**
	 * Log a non-exception PHP error without returning it to a client.
	 *
	 * @param array{type?: int, file?: string, line?: int} $error
	 * @param string $correlationIdentifier
	 *
	 * @return void
	 */
	private function logUnhandledError(array $error, string $correlationIdentifier): void
	{
		try {
			StructuredKernelLogger::error('error.unhandled', [
				'correlation_id' => $correlationIdentifier,
				'error_type' => $error['type'] ?? null,
				'file' => $error['file'] ?? null,
				'line' => $error['line'] ?? null,
			]);
		} catch (\Throwable $loggingException) {
			error_log(sprintf(
				'Unable to write structured error log for %s: %s',
				$correlationIdentifier,
				$loggingException::class
			));
		}
	}

    /**
     * Adds a vscode:// deep link so the debug template can offer "Open in VS Code" (user must click).
     *
     * @param array<string, mixed> $arguments Template arguments.
     * @param string                 $file      Source file path.
     * @param int                    $line      One-based line number.
     *
     * @return array<string, mixed>
     */
    private function enrichWithVsCodeDebugContext(array $arguments, string $file, int $line): array
    {
        if (OperationSystem::isCommandLineInterface()) {
            return $arguments;
        }
        if ($file === '') {
            return $arguments;
        }

        $arguments['vscodeOpenUri'] = IDELink::buildVisualStudioCodeUri($file, $line);

        return $arguments;
    }

    /**
     * Resolve context-aware troubleshooting hints for exception debug template.
     *
     * @param \Throwable $e
     *
     * @return array{
     *  titleKey: string,
     *  titleDefault: string,
     *  items: array<int,array{
     *      key: string,
     *      default: string,
     *      replacements?: array<string,string>
     *  }>
     * }|null
     */
    private function resolveTroubleshootingHints(\Throwable $e): ?array
    {
        $message = $e->getMessage();
        $normalized = is_string($message) ? strtolower($message) : '';
        $items = [];

        if (str_contains($normalized, 'sqlstate[hy000] [2002]') || str_contains($normalized, 'connection refused')) {
            $items[] = [
                'key' => 'template_messages.web.troubleshooting.db_connection.check_service',
                'default' => 'Check whether MariaDB/MySQL service is running and listening on the target port.',
            ];
            $items[] = [
                'key' => 'template_messages.web.troubleshooting.db_connection.check_host_port',
                'default' => 'Verify MYSQL_HOST and MYSQL_PORT for your runtime (Docker: db:3306, Host CLI: localhost:3308).',
            ];
            $items[] = [
                'key' => 'template_messages.web.troubleshooting.db_connection.check_local_env',
                'default' => 'If running from host CLI, ensure .env.local exists and overrides DB host/port.',
            ];
        }

        if (
            str_contains($normalized, 'could not translate host name')
            || str_contains($normalized, 'getaddrinfo')
            || str_contains($normalized, 'name or service not known')
        ) {
            $items[] = [
                'key' => 'template_messages.web.troubleshooting.db_dns.check_hostname',
                'default' => 'The DB hostname cannot be resolved. Use db only inside Docker Compose network.',
            ];
            $items[] = [
                'key' => 'template_messages.web.troubleshooting.db_dns.use_localhost_on_host',
                'default' => 'For host CLI execution, use localhost/127.0.0.1 with the published DB port.',
            ];
        }

        if (
            is_string($message)
            && preg_match('/syntax error,\s*unexpected variable\s*"(\$[A-Za-z_][A-Za-z0-9_]*)"/i', $message, $matches) === 1
        ) {
            $variable = $matches[1] ?? '$variable';
            $items[] = [
                'key' => 'template_messages.web.troubleshooting.php_syntax.unexpected_variable',
                'default' => 'Unexpected variable {variable}: check the previous line for missing comma, semicolon, bracket, or quote.',
                'replacements' => ['variable' => $variable],
            ];
            $items[] = [
                'key' => 'template_messages.web.troubleshooting.php_syntax.validate_nearby_lines',
                'default' => 'Inspect lines right above the error line and run `php -l <file>` to validate syntax quickly.',
            ];
        }

        if (
            is_string($message)
            && preg_match('/call to undefined function\s+([a-zA-Z0-9_\\\\]+)\s*\(\)/i', $message, $matches) === 1
        ) {
            $functionName = $matches[1] ?? 'unknown';
            $items[] = [
                'key' => 'template_messages.web.troubleshooting.undefined_function.check_typo_or_import',
                'default' => 'Undefined function {function}: check typos, namespace, and whether the function is imported correctly.',
                'replacements' => ['function' => $functionName],
            ];
            $items[] = [
                'key' => 'template_messages.web.troubleshooting.undefined_function.check_extension_or_polyfill',
                'default' => 'If this is built-in, verify required PHP extension is enabled; otherwise add a fallback/polyfill.',
            ];
        }

        if (empty($items)) {
            return null;
        }

        return [
            'titleKey' => 'template_messages.web.how_to_fix',
            'titleDefault' => 'How to fix',
            'items' => $items,
        ];
    }
}
