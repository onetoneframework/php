<?php

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

declare(strict_types=1);

namespace Clover\Framework\Event;

final class RoutingLifecycleEvent
{
    public const MIDDLEWARE_STARTED = 'routing.middleware.started';
    public const MIDDLEWARE_FINISHED = 'routing.middleware.finished';
    public const CACHE_HIT = 'routing.cache.hit';
    public const CACHE_MISS = 'routing.cache.miss';
    public const CACHE_WRITE_STARTED = 'routing.cache.write_started';
    public const CACHE_WRITE_FINISHED = 'routing.cache.write_finished';
    public const FALLBACK_TRIGGERED = 'routing.fallback.triggered';
    public const RESPONSE_RESOLVED = 'routing.response.resolved';

    /**
     * @param array<string,mixed> $payload
     */
    public function __construct(
        public readonly string $type,
        public readonly array $payload = []
    ) {
    }
}
