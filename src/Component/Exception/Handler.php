<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Component\Exception;

use Clover\Component\Contract\ExceptionHandlerInterface;
use Clover\Component\Foundation\Application;
use Clover\Component\Http\Request;
use Clover\Component\Http\Response;
use Clover\Component\Log\Logger;
use Throwable;

use function filter_var;
use function get_class;
use function is_int;
use function is_string;
use function method_exists;
use function sprintf;
use function str_contains;
use function strtolower;

use const DIRECTORY_SEPARATOR;
use const FILTER_VALIDATE_BOOL;

/**
 * Exception Handler
 *
 * The framework's default {@see ExceptionHandlerInterface}: log the throwable, then answer with a
 * 500.
 *
 * How much of the throwable reaches the client depends on `APP_DEBUG`. With it off the client is
 * told only that the request failed - a stack trace names internal paths and class layout, which
 * is help for an attacker and noise for everybody else. With it on the message and trace are
 * included, because that is the point of running with it on.
 *
 * An application replaces this wholesale by binding its own handler to the interface.
 */
class Handler implements ExceptionHandlerInterface
{
	/**
	 * Status code used for a throwable that carries no status of its own.
	 */
	protected const DEFAULT_STATUS_CODE = 500;

	/**
	 * @var Application The application, used to locate the log destination.
	 */
	protected Application $application;

	/**
	 * @param Application $application The application this handler serves.
	 */
	public function __construct(Application $application)
	{
		$this->application = $application;
	}

	/**
	 * Write the throwable to the application log.
	 *
	 * Reporting must never itself break the response, so a failure to log is swallowed here - the
	 * client still gets the rendered error, which is the more useful of the two.
	 *
	 * @param Throwable $throwable The throwable that escaped.
	 *
	 * @return void
	 */
	public function report(Throwable $throwable): void
	{
		try {
			$logger = new Logger($this->application->getStoragePath() . DIRECTORY_SEPARATOR . 'application.log');
			$logger->error(sprintf(
				'%s: %s in %s:%d',
				get_class($throwable),
				$throwable->getMessage(),
				$throwable->getFile(),
				$throwable->getLine()
			), [
				'trace' => $throwable->getTraceAsString(),
			]);
		} catch (Throwable) {
			// A logger that cannot write must not replace the original failure with its own.
		}
	}

	/**
	 * Render the throwable as a response.
	 *
	 * @param Request   $request   The request being handled when it was thrown.
	 * @param Throwable $throwable The throwable that escaped.
	 *
	 * @return Response
	 */
	public function render(Request $request, Throwable $throwable): Response
	{
		$statusCode = $this->resolveStatusCode($throwable);

		if (!$this->isDebug()) {
			return $this->wantsJson($request)
				? Response::json(['error' => 'Server Error'], $statusCode)
				: Response::text('Server Error', $statusCode);
		}

		// The trace is rendered as a string, not as `getTrace()`. That array carries the call
		// arguments, which may hold closures and resources; `Response::json()` encodes with
		// JSON_THROW_ON_ERROR, so one unencodable argument anywhere in the stack would turn the
		// debug page into a bare 500 - losing exactly the detail it exists to show.
		$payload = [
			'error' => get_class($throwable),
			'message' => $throwable->getMessage(),
			'file' => $throwable->getFile(),
			'line' => $throwable->getLine(),
			'trace' => $throwable->getTraceAsString(),
		];

		if ($this->wantsJson($request)) {
			return Response::json($payload, $statusCode);
		}

		return Response::text(sprintf(
			"%s: %s\nin %s:%d\n\n%s",
			$payload['error'],
			$payload['message'],
			$payload['file'],
			$payload['line'],
			$throwable->getTraceAsString()
		), $statusCode);
	}

	/**
	 * Status code to answer with.
	 *
	 * A throwable may carry its own by implementing `getStatusCode()`; anything else is a 500,
	 * because an unrecognised failure is a server failure.
	 *
	 * @param Throwable $throwable The throwable being rendered.
	 *
	 * @return int
	 */
	protected function resolveStatusCode(Throwable $throwable): int
	{
		if (!method_exists($throwable, 'getStatusCode')) {
			return static::DEFAULT_STATUS_CODE;
		}

		$statusCode = $throwable->getStatusCode();

		if (!is_int($statusCode) || $statusCode < 100 || $statusCode > 599) {
			return static::DEFAULT_STATUS_CODE;
		}

		return $statusCode;
	}

	/**
	 * Whether the client asked for JSON.
	 *
	 * @param Request $request The request being handled.
	 *
	 * @return bool
	 */
	protected function wantsJson(Request $request): bool
	{
		$accept = $request->getHeader('accept');

		if (!is_string($accept)) {
			return false;
		}

		return str_contains(strtolower($accept), 'application/json');
	}

	/**
	 * Whether the application is running with debug output enabled.
	 *
	 * @return bool
	 */
	protected function isDebug(): bool
	{
		return filter_var($_ENV['APP_DEBUG'] ?? getenv('APP_DEBUG') ?: false, FILTER_VALIDATE_BOOL) === true;
	}
}
