<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Event\Middleware;

use Clover\Classes\Event\EventEnvelope;
use Clover\Implement\EventBusMiddlewareInterface;
use function bin2hex;
use function random_bytes;

final class CorrelationMiddleware implements EventBusMiddlewareInterface
{
	public function handle(EventEnvelope $envelope, callable $next): void
	{
		$correlationId = $envelope->correlationId ?? bin2hex(random_bytes(8));
		$next($envelope->withHeader('correlation_id', $correlationId));
	}
}
