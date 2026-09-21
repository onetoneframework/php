<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Logging;

use Clover\Classes\Date\Date;
use Clover\Classes\File\Handler as FileHandler;
use Clover\Enumeration\LoggingLevel;
use DateTimeImmutable;
use DateTimeZone;
use RuntimeException;
use Throwable;
use function array_key_exists;
use function filesize;
use function get_class;
use function is_array;
use function is_null;
use function is_object;
use function is_scalar;
use function json_encode;
use function preg_match;
use function rename;
use function sprintf;
use function str_replace;

/**
 * Class Logger
 *
 * A structured, level-aware logging system with support for contextual data,
 * log rotation, multiple output channels, and PSR-3 compatible message interpolation.
 *
 * @package Clover\Classes\Logging
 */
class Logger
{
    /**
     * @var DateTimeZone The timezone used for log timestamps.
     */
    private DateTimeZone $timezone;

    /**
     * @var string The primary log file path.
     */
    private string $fileLocation;

    /**
     * @var string The minimum logging level threshold.
     */
    private string $minimumLevel;

    /**
     * @var int Maximum log file size in bytes before rotation occurs (default: 10MB).
     */
    private int $maxFileSize;

    /**
     * @var int Maximum number of rotated log files to retain.
     */
    private int $maxRotatedFiles;

    /**
     * @var string The format template for log entries.
     */
    private string $logFormat;

    /**
     * @var string The date format used in log timestamps.
     */
    private string $dateFormat;

    /**
     * @var array<string, mixed> Global context appended to every log entry.
     */
    private array $globalContext;

    /**
     * @var array<string, int> Severity weight map for level comparison.
     */
    private const LEVEL_PRIORITY = [
        LoggingLevel::DEBUG => 100,
        LoggingLevel::INFORMATION => 200,
        LoggingLevel::NOTICE => 300,
        LoggingLevel::WARNING => 400,
        LoggingLevel::ERROR => 500,
        LoggingLevel::CRITICAL => 600,
        LoggingLevel::ALERT => 700,
        LoggingLevel::EMERGENCY => 800,
    ];

    /**
     * Logger constructor.
     *
     * Initializes the logger with configurable timezone, file path, minimum level,
     * rotation settings, format, and global context.
     *
     * @param DateTimeZone|null   $timezone        The timezone for timestamps. Defaults to the system timezone.
     * @param string|null         $fileLocation    The log file path. Defaults to BASE_PATH/application.log.
     * @param string              $minimumLevel    The minimum severity level to record.
     * @param int                 $maxFileSize     Maximum file size in bytes before rotation (default: 10MB).
     * @param int                 $maxRotatedFiles Maximum number of rotated backup files to keep.
     * @param string              $logFormat       The log entry format template with placeholders.
     * @param string              $dateFormat      The PHP date format string for timestamps.
     * @param array<string,mixed> $globalContext   Key-value pairs appended to every log entry.
     */
    public function __construct(?DateTimeZone $timezone = null, ?string $fileLocation = null, string $minimumLevel = LoggingLevel::DEBUG, int $maxFileSize = 10485760, int $maxRotatedFiles = 5, string $logFormat = "[{timestamp}] [{level}] [{namespace}] {message} {context}", string $dateFormat = 'Y-m-d H:i:s.u', array $globalContext = [])
    {
        $this->timezone = $timezone ?? new DateTimeZone(Date::getDefaultTimezone());
        $this->fileLocation = $fileLocation ?? BASE_PATH . "/application.log";
        $this->minimumLevel = $minimumLevel;
        $this->maxFileSize = $maxFileSize;
        $this->maxRotatedFiles = $maxRotatedFiles;
        $this->logFormat = $logFormat;
        $this->dateFormat = $dateFormat;
        $this->globalContext = $globalContext;
    }

    /**
     * Write a log entry to the log file.
     *
     * Checks the minimum level threshold, performs log rotation if needed,
     * formats the entry using the configured template, and writes to the file.
     *
     * @param string              $content      The log message. Supports {key} placeholders for context interpolation.
     * @param string              $loggingLevel The severity level of this entry.
     * @param string|null         $namespace    An optional namespace or channel identifier for categorization.
     * @param array<string,mixed> $context      Contextual key-value data to include with the entry.
     *
     * @return bool True if the log entry was successfully written, false otherwise.
     */
    public function write(string $content, string $loggingLevel = LoggingLevel::INFORMATION, ?string $namespace = null, array $context = []): bool
    {
        if (!$this->isLevelEnabled($loggingLevel)) {
            return false;
        }

        $this->rotateIfNeeded();

        $mergedContext = array_merge($this->globalContext, $context);
        $interpolatedMessage = $this->interpolate($content, $mergedContext);
        $formattedLog = $this->formatEntry($interpolatedMessage, $loggingLevel, $namespace, $mergedContext);

        if (FileHandler::isExists($this->fileLocation)) {
            return FileHandler::appendContent($this->fileLocation, $formattedLog);
        }

        return FileHandler::write($this->fileLocation, $formattedLog);
    }

    /**
     * Format a log entry string from the configured template.
     *
     * Replaces {timestamp}, {level}, {namespace}, {message}, and {context}
     * placeholders in the log format with actual values.
     *
     * @param string              $message   The interpolated log message.
     * @param string              $level     The severity level string.
     * @param string|null         $namespace The optional namespace or channel.
     * @param array<string,mixed> $context   The merged context data.
     *
     * @return string The fully formatted log line with a trailing newline.
     */
    private function formatEntry(string $message, string $level, ?string $namespace, array $context): string
    {
        $timestamp = new DateTimeImmutable("now", $this->timezone);

        $contextString = !empty($context) ? json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '';

        $entry = str_replace(
            ['{timestamp}', '{level}', '{namespace}', '{message}', '{context}'],
            [$timestamp->format($this->dateFormat), $level, $namespace ?? 'app', $message, $contextString],
            $this->logFormat
        );

        return rtrim($entry) . "\n";
    }

    /**
     * Interpolate context values into a message template.
     *
     * Replaces {key} placeholders in the message with corresponding values from the context array.
     * Objects implementing __toString are converted; other non-scalar values are JSON-encoded.
     *
     * @param string              $message The message template with {key} placeholders.
     * @param array<string,mixed> $context The context data for replacement.
     *
     * @return string The message with all matched placeholders replaced.
     */
    private function interpolate(string $message, array $context): string
    {
        if (!preg_match('/\{[a-zA-Z0-9_.]+\}/', $message)) {
            return $message;
        }

        $replacements = [];
        foreach ($context as $key => $value) {
            $placeholder = '{' . $key . '}';
            if (strpos($message, $placeholder) === false) {
                continue;
            }

            if (is_array($value)) {
                $replacements[$placeholder] = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            } elseif (is_object($value) && method_exists($value, '__toString')) {
                $replacements[$placeholder] = (string) $value;
            } elseif (is_scalar($value) || is_null($value)) {
                $replacements[$placeholder] = (string) $value;
            } else {
                $replacements[$placeholder] = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
        }

        return strtr($message, $replacements);
    }

    /**
     * Determine whether a given logging level meets or exceeds the minimum threshold.
     *
     * @param string $level The logging level to check.
     *
     * @return bool True if the level is enabled (priority >= minimum), false otherwise.
     */
    private function isLevelEnabled(string $level): bool
    {
        $levelPriority = self::LEVEL_PRIORITY[$level] ?? 0;
        $minimumPriority = self::LEVEL_PRIORITY[$this->minimumLevel] ?? 0;

        return $levelPriority >= $minimumPriority;
    }

    /**
     * Rotate log files if the current file exceeds the maximum allowed size.
     *
     * Shifts existing rotated files (e.g., .1 → .2, .2 → .3) up to the configured
     * maximum, removes the oldest if it exceeds the limit, and renames the current
     * log file to .1 to free up the primary file path.
     *
     * @return void
     */
    private function rotateIfNeeded(): void
    {
        if (!FileHandler::isExists($this->fileLocation)) {
            return;
        }

        if (filesize($this->fileLocation) < $this->maxFileSize) {
            return;
        }

        for ($i = $this->maxRotatedFiles - 1; $i >= 1; $i--) {
            $source = $this->fileLocation . '.' . $i;
            $target = $this->fileLocation . '.' . ($i + 1);

            if (FileHandler::isExists($source)) {
                rename($source, $target);
            }
        }

        $oldestFile = $this->fileLocation . '.' . ($this->maxRotatedFiles + 1);
        if (FileHandler::isExists($oldestFile)) {
            FileHandler::delete($oldestFile);
        }

        rename($this->fileLocation, $this->fileLocation . '.1');
    }

    /**
     * Add a key-value pair to the global context.
     *
     * Global context entries are automatically merged into every log entry's context.
     *
     * @param string $key   The context key.
     * @param mixed  $value The context value.
     *
     * @return void
     */
    public function addGlobalContext(string $key, mixed $value): void
    {
        $this->globalContext[$key] = $value;
    }

    /**
     * Set the minimum logging level threshold.
     *
     * Log entries below this level will be silently discarded.
     *
     * @param string $level The minimum level (must be a valid LoggingLevel constant).
     *
     * @return void
     *
     * @throws RuntimeException If the provided level is not recognized.
     */
    public function setMinimumLevel(string $level): void
    {
        if (!array_key_exists($level, self::LEVEL_PRIORITY)) {
            throw new RuntimeException(sprintf('Invalid logging level: %s', $level));
        }

        $this->minimumLevel = $level;
    }

    /**
     * Set the log file output path.
     *
     * @param string $fileLocation The absolute or relative path to the log file.
     *
     * @return void
     */
    public function setFileLocation(string $fileLocation): void
    {
        $this->fileLocation = $fileLocation;
    }

    /**
     * Set the log entry format template.
     *
     * Supported placeholders: {timestamp}, {level}, {namespace}, {message}, {context}.
     *
     * @param string $format The format template string.
     *
     * @return void
     */
    public function setLogFormat(string $format): void
    {
        $this->logFormat = $format;
    }

    /**
     * Log a debug-level message.
     *
     * @param string|array        $content   The log message or structured data.
     * @param string|null         $namespace Optional namespace or channel.
     * @param array<string,mixed> $context   Additional contextual data.
     *
     * @return void
     */
    public function debug(string|array $content, ?string $namespace = null, array $context = []): void
    {
        $this->writeResolved($content, LoggingLevel::DEBUG, $namespace, $context);
    }

    /**
     * Log an informational message.
     *
     * @param string|array        $content   The log message or structured data.
     * @param string|null         $namespace Optional namespace or channel.
     * @param array<string,mixed> $context   Additional contextual data.
     *
     * @return void
     */
    public function information(string|array $content, ?string $namespace = null, array $context = []): void
    {
        $this->writeResolved($content, LoggingLevel::INFORMATION, $namespace, $context);
    }

    /**
     * Log a notice-level message.
     *
     * @param string|array        $content   The log message or structured data.
     * @param string|null         $namespace Optional namespace or channel.
     * @param array<string,mixed> $context   Additional contextual data.
     *
     * @return void
     */
    public function notice(string|array $content, ?string $namespace = null, array $context = []): void
    {
        $this->writeResolved($content, LoggingLevel::NOTICE, $namespace, $context);
    }

    /**
     * Log a warning-level message.
     *
     * @param string|array        $content   The log message or structured data.
     * @param string|null         $namespace Optional namespace or channel.
     * @param array<string,mixed> $context   Additional contextual data.
     *
     * @return void
     */
    public function warning(string|array $content, ?string $namespace = null, array $context = []): void
    {
        $this->writeResolved($content, LoggingLevel::WARNING, $namespace, $context);
    }

    /**
     * Log an error-level message.
     *
     * @param string|array        $content   The log message or structured data.
     * @param string|null         $namespace Optional namespace or channel.
     * @param array<string,mixed> $context   Additional contextual data.
     *
     * @return void
     */
    public function error(string|array $content, ?string $namespace = null, array $context = []): void
    {
        $this->writeResolved($content, LoggingLevel::ERROR, $namespace, $context);
    }

    /**
     * Log a critical-level message.
     *
     * @param string|array        $content   The log message or structured data.
     * @param string|null         $namespace Optional namespace or channel.
     * @param array<string,mixed> $context   Additional contextual data.
     *
     * @return void
     */
    public function critical(string|array $content, ?string $namespace = null, array $context = []): void
    {
        $this->writeResolved($content, LoggingLevel::CRITICAL, $namespace, $context);
    }

    /**
     * Log an alert-level message.
     *
     * @param string|array        $content   The log message or structured data.
     * @param string|null         $namespace Optional namespace or channel.
     * @param array<string,mixed> $context   Additional contextual data.
     *
     * @return void
     */
    public function alert(string|array $content, ?string $namespace = null, array $context = []): void
    {
        $this->writeResolved($content, LoggingLevel::ALERT, $namespace, $context);
    }

    /**
     * Log an emergency-level message.
     *
     * @param string|array        $content   The log message or structured data.
     * @param string|null         $namespace Optional namespace or channel.
     * @param array<string,mixed> $context   Additional contextual data.
     *
     * @return void
     */
    public function emergency(string|array $content, ?string $namespace = null, array $context = []): void
    {
        $this->writeResolved($content, LoggingLevel::EMERGENCY, $namespace, $context);
    }

    /**
     * Log a Throwable (exception or error) with its full stack trace and metadata.
     *
     * Captures the exception message, class name, file, line, and trace as context,
     * then writes an error-level log entry. If the throwable has a previous exception,
     * it is recursively logged as well.
     *
     * @param Throwable           $throwable The exception or error to log.
     * @param string|null         $namespace Optional namespace or channel.
     * @param array<string,mixed> $context   Additional contextual data to merge.
     *
     * @return void
     */
    public function throwable(Throwable $throwable, ?string $namespace = null, array $context = []): void
    {
        $context = array_merge($context, [
            'exception_class' => get_class($throwable),
            'file' => $throwable->getFile(),
            'line' => $throwable->getLine(),
            'trace' => $throwable->getTraceAsString(),
        ]);

        $this->write($throwable->getMessage(), LoggingLevel::ERROR, $namespace, $context);

        if ($throwable->getPrevious() !== null) {
            $this->throwable($throwable->getPrevious(), $namespace, ['previous_exception' => true]);
        }
    }

    /**
     * Resolve content that may be a string or array into a write call.
     *
     * If the content is an array, it is JSON-encoded before being passed to write().
     *
     * @param string|array        $content   The log message or structured data.
     * @param string              $level     The severity level.
     * @param string|null         $namespace Optional namespace or channel.
     * @param array<string,mixed> $context   Additional contextual data.
     *
     * @return void
     */
    private function writeResolved(string|array $content, string $level, ?string $namespace, array $context): void
    {
        $message = is_array($content) ? json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : $content;

        $this->write($message, $level, $namespace, $context);
    }
}