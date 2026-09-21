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

/**
 * Terminable Middleware Interface
 *
 * Work a middleware wants to do after the response has been sent - writing a session, flushing a
 * log, recording a metric. The kernel calls this from `terminate()`, once the client already has
 * its bytes.
 */
interface TerminableMiddlewareInterface extends MiddlewareInterface
{
	/**
	 * Run post-response work for this request.
	 *
	 * @param Request  $request  The request that was handled.
	 * @param Response $response The response that was sent.
	 *
	 * @return void
	 */
	public function terminate(Request $request, Response $response): void;
}
