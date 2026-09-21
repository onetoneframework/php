<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Implement;

use Clover\Classes\Event\EventEnvelope;

interface EventBusInterface
{
	public const ENVELOPE_EVENT_NAME = 'eventbus.envelope';

	/**
	 * Register an event handler.
	 *
	 * @param string $eventName
	 * @param callable $listener
	 * @param int $priority
	 * @return void
	 */
	public function subscribe(string $eventName, callable $listener, int $priority = 0): void;

	/**
	 * Publish an event synchronously.
	 *
	 * @param object $event
	 * @param string|null $eventName
	 * @return void
	 */
	public function publish(object $event, ?string $eventName = null): void;

	/**
	 * Publish an event asynchronously.
	 *
	 * @param object $event
	 * @param string|null $eventName
	 * @return void
	 */
	public function publishAsync(object $event, ?string $eventName = null): void;

	/**
	 * Process queued asynchronous events.
	 *
	 * @param int|null $maxMessages
	 * @return void
	 */
	public function processAsync(?int $maxMessages = null): void;

	/**
	 * Publish an already-created envelope.
	 *
	 * @param EventEnvelope $envelope
	 * @return void
	 */
	public function publishEnvelope(EventEnvelope $envelope): void;

	/**
	 * Publish envelope asynchronously.
	 *
	 * @param EventEnvelope $envelope
	 * @return void
	 */
	public function publishEnvelopeAsync(EventEnvelope $envelope): void;

	/**
	 * Register bus middleware.
	 *
	 * @param EventBusMiddlewareInterface|callable $middleware
	 * @return void
	 */
	public function addMiddleware(EventBusMiddlewareInterface|callable $middleware): void;
}
