<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Event;

use Clover\Implement\EventBusInterface;
use Clover\Implement\EventBusMiddlewareInterface;
use RuntimeException;
use function count;
use function is_callable;

final class EventBus implements EventBusInterface
{
	/** 
	 * @var array<int, EventBusMiddlewareInterface|callable> $middlewares List of middleware components to process events through the pipeline
	 **/
	private array $middlewares = [];
	/** 
	 * @var bool $strictListenerFailure If true, any listener failure will cause an exception to be thrown after all listeners have been attempted
	 **/
	private bool $strictListenerFailure = false;

	/**
	 * EventBus constructor.
	 * 
	 * @param Dispatcher $dispatcher The event dispatcher to use for managing event listeners and dispatching events
	 */
	public function __construct(private readonly Dispatcher $dispatcher)
	{
	}

	/**
	 * Subscribe a listener to an event with an optional priority
	 * Higher priority listeners will be executed before lower priority ones
	 * 
	 * @param string $eventName The name of the event to listen for
	 * @param callable $listener The listener callback to execute when the event is dispatched
	 * @param int $priority Optional priority for the listener (default: 0)
	 * 
	 * @return void
	 */
	public function subscribe(string $eventName, callable $listener, int $priority = 0): void
	{
		$this->dispatcher->addListener($eventName, $listener, $priority);
	}

	/**
	 * Publish an event synchronously, processing it immediately through the middleware pipeline and dispatching to listeners
	 * 
	 * @param object $event The event object to publish
	 * @param string|null $eventName Optional explicit event name (if not provided, the class name of the event will be used)
	 * @return void
	 */
	public function publish(object $event, ?string $eventName = null): void
	{
		$this->publishEnvelope(EventEnvelope::wrap($event, $eventName));
	}

	/**
	 * Publish an event asynchronously by queuing it for later processing
	 * 
	 * @param object $event The event object to publish
	 * @param string|null $eventName Optional explicit event name (if not provided, the class name of the event will be used)
	 * 
	 * @return void
	 */
	public function publishAsync(object $event, ?string $eventName = null): void
	{
		$this->publishEnvelopeAsync(EventEnvelope::wrap($event, $eventName));
	}

	/**
	 * Process the event queue for asynchronous events
	 * 
	 * @param int|null $maxMessages Optional maximum number of messages to process in this call (null for no limit)
	 * 
	 * @return void
	 */
	public function processAsync(?int $maxMessages = null): void
	{
		$this->dispatcher->processQueue($maxMessages);
	}

	/**
	 * Publish an event envelope through the middleware pipeline and dispatch it
	 * 
	 * @param EventEnvelope $envelope
	 * 
	 * @return void
	 */
	public function publishEnvelope(EventEnvelope $envelope): void
	{
		$pipeline = $this->buildPipeline(function (EventEnvelope $nextEnvelope): void {
			$beforeFailures = count($this->dispatcher->getListenerFailures());
			$this->dispatcher->dispatch($nextEnvelope, EventBusInterface::ENVELOPE_EVENT_NAME);
			$this->dispatcher->dispatch($nextEnvelope->payload, $nextEnvelope->name);
			$this->assertStrictMode($beforeFailures);
		});

		$pipeline($envelope);
	}

	/**
	 * Queue an event envelope for asynchronous processing
	 * 
	 * @param EventEnvelope $envelope
	 * 
	 * @return void
	 */
	public function publishEnvelopeAsync(EventEnvelope $envelope): void
	{
		$this->dispatcher->queue($envelope, EventBusInterface::ENVELOPE_EVENT_NAME);
		$this->dispatcher->queue($envelope->payload, $envelope->name);
	}

	/**
	 * Add middleware to the event bus pipeline
	 * 
	 * @param EventBusMiddlewareInterface|callable $middleware
	 * 
	 * @return void
	 */
	public function addMiddleware(EventBusMiddlewareInterface|callable $middleware): void
	{
		if (!$middleware instanceof EventBusMiddlewareInterface && !is_callable($middleware)) {
			throw new RuntimeException('Event bus middleware must be callable or implement EventBusMiddlewareInterface.');
		}
		$this->middlewares[] = $middleware;
	}

	/**
	 * Enable or disable strict mode for listener failures
	 * In strict mode, if any listener throws an exception during event dispatch, the exception will be re-thrown after all listeners have been attempted.
	 * 
	 * @param bool $enabled
	 * 
	 * @return void
	 */
	public function setStrictListenerFailure(bool $enabled): void
	{
		$this->strictListenerFailure = $enabled;
	}

	/**
	 * Build the middleware pipeline by wrapping the final destination callable with the registered middlewares
	 * 
	 * @param callable(EventEnvelope): void $destination
	 * @return callable(EventEnvelope): void
	 */
	private function buildPipeline(callable $destination): callable
	{
		$next = $destination;
		for ($i = count($this->middlewares) - 1; $i >= 0; --$i) {
			$middleware = $this->middlewares[$i];
			$current = $next;
			$next = function (EventEnvelope $envelope) use ($middleware, $current): void {
				if ($middleware instanceof EventBusMiddlewareInterface) {
					$middleware->handle($envelope, $current);
					return;
				}
				$middleware($envelope, $current);
			};
		}

		return $next;
	}

	/**
	 * Check for listener failures after dispatching an event and throw an exception if strict mode is enabled
	 * 
	 * @param int $beforeFailureCount The number of listener failures before dispatching the event
	 * 
	 * @return void
	 */
	private function assertStrictMode(int $beforeFailureCount): void
	{
		if (!$this->strictListenerFailure) {
			return;
		}

		$failures = $this->dispatcher->getListenerFailures();
		if (count($failures) <= $beforeFailureCount) {
			return;
		}

		$lastFailure = $failures[count($failures) - 1];
		throw new RuntimeException(
			'Event listener failed in strict mode: ' . ($lastFailure['listener'] ?? 'unknown') . ' - ' . ($lastFailure['error'] ?? 'unknown error')
		);
	}
}
