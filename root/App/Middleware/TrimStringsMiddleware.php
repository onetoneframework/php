<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace App\Middleware;

use Clover\Component\Contract\MiddlewareInterface;
use Clover\Component\Contract\RequestHandlerInterface;
use Clover\Component\Http\Request;
use Clover\Component\Http\Response;
use function is_array;
use function is_string;
use function trim;

/**
 * Trim Strings Middleware
 *
 * Strips surrounding whitespace from every query value before the route sees it, so a form field
 * submitted as `" alice "` reaches the action as `"alice"`.
 *
 * The trimmed values are attached to the request rather than replacing the originals: a route that
 * genuinely needs the raw value - a password, a field where leading space is meaningful - can
 * still read it from `getQueryParams()`.
 */
class TrimStringsMiddleware implements MiddlewareInterface
{
	/**
	 * Request attribute holding the trimmed query values.
	 */
	public const ATTRIBUTE = 'trimmed_query';

	/**
	 * Trim the request's query values, then continue down the pipeline.
	 *
	 * @param Request                 $request The incoming request.
	 * @param RequestHandlerInterface $handler The rest of the pipeline.
	 *
	 * @return Response
	 */
	public function process(Request $request, RequestHandlerInterface $handler): Response
	{
		$request->setAttribute(static::ATTRIBUTE, $this->trim($request->getQueryParams()));

		return $handler->handle($request);
	}

	/**
	 * Trim every string in a nested array.
	 *
	 * @param array<array-key, mixed> $values The values to trim.
	 *
	 * @return array<array-key, mixed>
	 */
	protected function trim(array $values): array
	{
		$trimmed = [];

		foreach ($values as $key => $value) {
			if (is_array($value)) {
				$trimmed[$key] = $this->trim($value);
				continue;
			}

			$trimmed[$key] = is_string($value) ? trim($value) : $value;
		}

		return $trimmed;
	}
}
