<?php

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

declare(strict_types=1);

namespace Clover\Framework\Event;

final class HttpClientLifecycleEvent
{
    public const REQUEST_STARTED = 'http_client.request.started';
    public const REQUEST_FINISHED = 'http_client.request.finished';
    public const REQUEST_FAILED = 'http_client.request.failed';

    /**
     * @param array<string,mixed> $payload
     */
    public function __construct(
        public readonly string $type,
        public readonly array $payload = []
    ) {
    }
}
