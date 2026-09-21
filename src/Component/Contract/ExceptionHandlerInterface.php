<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Component\Contract;

use Clover\Component\Http\Request;
use Clover\Component\Http\Response;
use Throwable;

/**
 * Exception Handler Interface
 *
 * Turns a throwable that escaped the pipeline into a response, and records it.
 *
 * An application replaces the default by binding its own implementation to this interface, which
 * is what `HandleExceptions` resolves.
 */
interface ExceptionHandlerInterface
{
	/**
	 * Record the throwable - log it, report it upstream, whatever the application does with it.
	 *
	 * @param Throwable $throwable The throwable that escaped.
	 *
	 * @return void
	 */
	public function report(Throwable $throwable): void;

	/**
	 * Convert the throwable into a response to send to the client.
	 *
	 * @param Request   $request   The request being handled when it was thrown.
	 * @param Throwable $throwable The throwable that escaped.
	 *
	 * @return Response
	 */
	public function render(Request $request, Throwable $throwable): Response;
}
