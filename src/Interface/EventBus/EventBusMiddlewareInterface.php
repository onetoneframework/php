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

interface EventBusMiddlewareInterface
{
	/**
	 * @param EventEnvelope $envelope
	 * @param callable(EventEnvelope): void $next
	 * @return void
	 */
	public function handle(EventEnvelope $envelope, callable $next): void;
}
