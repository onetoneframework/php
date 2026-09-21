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
 * Request Handler Interface
 *
 * The tail of a middleware pipeline: something that turns a request into a response without
 * delegating any further.
 *
 * PSR-15 in shape, deliberately, so this stack's middleware reads the same as
 * `Clover\Framework\Contract\RequestHandlerInterface`. The two are separate only because they are
 * typed to different `Request` / `Response` classes; when those merge, so do these.
 */
interface RequestHandlerInterface
{
	/**
	 * Handle the request and produce a response.
	 *
	 * @param Request $request The incoming request.
	 *
	 * @return Response
	 */
	public function handle(Request $request): Response;
}
