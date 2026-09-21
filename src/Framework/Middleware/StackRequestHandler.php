<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Framework\Middleware;

use Clover\Classes\Debug\Profiler;
use Clover\Classes\Event\EventDispatcherAdapter;
use Clover\Framework\Component\Response;
use Clover\Framework\Component\Request;
use Clover\Framework\Contract\MiddlewareInterface;
use Clover\Framework\Contract\RequestHandlerInterface;
use Clover\Framework\Event\KernelSpanFinished;
use Clover\Framework\Event\KernelSpanStarted;

use function array_slice;
use function uniqid;

/**
 * Stack Request Handler
 * 
 * This class implements the RequestHandlerInterface and is responsible for managing a stack of middleware components. It processes incoming requests by passing them through the middleware stack in order, allowing each middleware to modify the request or response as needed. If the stack is exhausted, it delegates to a fallback handler.
 */
class StackRequestHandler implements RequestHandlerInterface
{
    /**
     * @var MiddlewareInterface[] An array of middleware components to be processed in order.
     */
    private array $middlewares;

    // Index to keep track of the current middleware being processed
    private int $index = 0;

    // Fallback handler to be called when the middleware stack is exhausted
    private RequestHandlerInterface $fallbackHandler;
    private EventDispatcherAdapter $eventDispatcher;

    /**
     * StackRequestHandler constructor.
     * 
     * @param MiddlewareInterface[] $middlewares
     * @param RequestHandlerInterface $fallbackHandler
     */
    public function __construct(array $middlewares, RequestHandlerInterface $fallbackHandler, ?EventDispatcherAdapter $eventDispatcher = null)
    {
        $this->middlewares = $middlewares;
        $this->fallbackHandler = $fallbackHandler;
        $this->eventDispatcher = $eventDispatcher ?? EventDispatcherAdapter::fromEventManager();
    }

    /**
     * Handle the request and return a response.
     * 
     * @param Request $request
     * @return Response
     */
    public function handle(Request $request): Response
    {
		$profilerEnabled = Profiler::isEnabled();
        if (!isset($this->middlewares[$this->index])) {
			$fallbackSpan = '';
			if ($profilerEnabled) {
				$fallbackSpan = uniqid('pf_', true);
				$this->eventDispatcher->dispatch(new KernelSpanStarted(
					$fallbackSpan,
					'Fallback request handler',
					'StackRequestHandler::fallback'
				));
			}
			try {
				return $this->fallbackHandler->handle($request);
			} finally {
				if ($profilerEnabled) {
					$this->eventDispatcher->dispatch(new KernelSpanFinished($fallbackSpan));
				}
			}
		}

        $middleware = $this->middlewares[$this->index];

        // Create a clone or new handler for the next middleware to advance the index
        // Actually, cleaner is to just increment index if we reuse the handler, but that's stateful.
        // Better: create a NextHandler.

        $nextHandler = new self(array_slice($this->middlewares, 1), $this->fallbackHandler, $this->eventDispatcher);
		$span = '';
		if ($profilerEnabled) {
			$span = uniqid('pf_', true);
			$this->eventDispatcher->dispatch(new KernelSpanStarted(
				$span,
				'Middleware: ' . $middleware::class,
				'StackRequestHandler::handle'
			));
		}

        try {
            return $middleware->process($request, $nextHandler);
		} finally {
			if ($profilerEnabled) {
				$this->eventDispatcher->dispatch(new KernelSpanFinished($span));
			}
		}
    }
}
