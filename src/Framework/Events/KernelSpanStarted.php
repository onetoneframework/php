<?php

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

declare(strict_types=1);

namespace Clover\Framework\Event;

final class KernelSpanStarted
{
    public function __construct(
        public readonly string $token,
        public readonly string $call,
        public readonly string $location = ''
    ) {
    }
}
