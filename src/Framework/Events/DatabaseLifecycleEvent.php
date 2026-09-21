<?php

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

declare(strict_types=1);

namespace Clover\Framework\Event;

final class DatabaseLifecycleEvent
{
    public const QUERY_STARTED = 'database.query.started';
    public const QUERY_FINISHED = 'database.query.finished';
    public const QUERY_FAILED = 'database.query.failed';

    /**
     * @param array<string,mixed> $payload
     */
    public function __construct(
        public readonly string $type,
        public readonly array $payload = []
    ) {
    }
}
