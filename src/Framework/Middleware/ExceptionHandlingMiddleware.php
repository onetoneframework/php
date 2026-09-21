<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Framework\Middleware;

use Clover\Classes\Debug\ErrorHandler;
use Clover\Classes\Logging\StructuredKernelLogger;
use Clover\Framework\Component\Request;
use Clover\Framework\Component\Response;
use Clover\Framework\Component\TraceContext;
use Clover\Framework\Contract\MiddlewareInterface;
use Clover\Framework\Contract\RequestHandlerInterface;
use Throwable;

/**
 * Converts unhandled request failures into safe, traceable HTTP responses.
 */
final class ExceptionHandlingMiddleware implements MiddlewareInterface
{
	private const INTERNAL_SERVER_ERROR_STATUS_CODE = 500;
	private const INTERNAL_SERVER_ERROR_TITLE = 'Internal Server Error';
	private const INTERNAL_SERVER_ERROR_DETAIL = 'An unexpected error occurred.';
	private const PROBLEM_JSON_CONTENT_TYPE = 'application/problem+json; charset=utf-8';

	public function process(Request $request, RequestHandlerInterface $handler): Response
	{
		try {
			return $handler->handle($request);
		} catch (Throwable $exception) {
			$traceIdentifier = TraceContext::getTraceId();

			if ($request->wantsJson()) {
				$this->report($exception, $request, $traceIdentifier);
				$response = Response::json([
					'type' => 'about:blank',
					'title' => self::INTERNAL_SERVER_ERROR_TITLE,
					'status' => self::INTERNAL_SERVER_ERROR_STATUS_CODE,
					'detail' => self::INTERNAL_SERVER_ERROR_DETAIL,
					'trace_id' => $traceIdentifier,
				], self::INTERNAL_SERVER_ERROR_STATUS_CODE);
				$response->setHeader('Content-Type', self::PROBLEM_JSON_CONTENT_TYPE);
			} else {
				$response = new Response(
					(string) (new ErrorHandler())->renderException($exception, false),
					[],
					'html',
					self::INTERNAL_SERVER_ERROR_STATUS_CODE
				);
			}

			$response->setHeader('Cache-Control', 'no-store');
			$response->setHeader(TraceContext::getResponseHeaderName(), $traceIdentifier);

			return $response;
		}
	}

	/**
	 * Record full server-side diagnostics while keeping the client response generic.
	 */
	private function report(Throwable $exception, Request $request, string $traceIdentifier): void
	{
		try {
			StructuredKernelLogger::error('exception.unhandled', [
				'trace_id' => $traceIdentifier,
				'method' => $request->getMethod(),
				'path' => $request->getPath(),
				'exception_class' => $exception::class,
				'file' => $exception->getFile(),
				'line' => $exception->getLine(),
				'trace' => $exception->getTraceAsString(),
			]);
		} catch (Throwable $loggingException) {
			error_log(sprintf(
				'Unable to write structured exception log for %s: %s',
				$traceIdentifier,
				$loggingException::class
			));
		}
	}
}
