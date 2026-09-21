<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Component\Pipeline;

use Clover\Component\Contract\MiddlewareInterface;
use Clover\Component\Contract\RequestHandlerInterface;
use Clover\Component\Http\Request;
use Clover\Component\Http\Response;

use function array_values;
use function count;

/**
 * Pipeline
 *
 * Runs a request through an ordered list of middleware and into a destination handler.
 *
 * Each layer is entered by handing it a pipeline over the *remaining* layers, so a middleware that
 * never calls `$handler->handle()` short-circuits everything after it - which is the whole point of
 * an authentication or a maintenance layer.
 *
 * The list is held as a position into one shared array rather than by slicing it per layer, so a
 * long stack does not copy itself once per hop.
 */
final class Pipeline implements RequestHandlerInterface
{
	/**
	 * @var array<int, MiddlewareInterface> The ordered middleware, shared by every position.
	 */
	private array $middleware;

	/**
	 * @var int Index of the layer this pipeline enters.
	 */
	private int $position;

	/**
	 * @var RequestHandlerInterface What runs once the middleware are exhausted.
	 */
	private RequestHandlerInterface $destination;

	/**
	 * @param array<int, MiddlewareInterface> $middleware  The layers, outermost first.
	 * @param RequestHandlerInterface         $destination What runs after the last layer.
	 * @param int                             $position    Index to enter at; callers pass 0.
	 */
	public function __construct(array $middleware, RequestHandlerInterface $destination, int $position = 0)
	{
		$this->middleware = array_values($middleware);
		$this->destination = $destination;
		$this->position = $position;
	}

	/**
	 * Enter the layer at this position, or the destination when there are none left.
	 *
	 * @param Request $request The request travelling down the pipeline.
	 *
	 * @return Response
	 */
	public function handle(Request $request): Response
	{
		if ($this->position >= count($this->middleware)) {
			return $this->destination->handle($request);
		}

		$layer = $this->middleware[$this->position];
		$next = new self($this->middleware, $this->destination, $this->position + 1);

		return $layer->process($request, $next);
	}
}
