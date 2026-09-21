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
 * Middleware Interface
 *
 * One layer of the request pipeline. A middleware either answers the request itself or hands it
 * to `$handler` and may then alter what comes back.
 *
 * PSR-15 in shape. Note this is `process()`, not the `handle(Request, callable $next)` of
 * `Clover\Implement\MiddlewareInterface` - a middleware written against one cannot be used by the
 * other. This stack uses `process()`.
 */
interface MiddlewareInterface
{
	/**
	 * Process the request, optionally delegating to the rest of the pipeline.
	 *
	 * @param Request                 $request The incoming request.
	 * @param RequestHandlerInterface $handler The rest of the pipeline.
	 *
	 * @return Response
	 */
	public function process(Request $request, RequestHandlerInterface $handler): Response;
}
