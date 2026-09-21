<?php

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

declare(strict_types=1);

namespace Clover\Framework\Event;

final class KernelLifecycleEvent
{
    /**
     * Application runtime bootstrap (after env load, before configure/mapping).
     */
    public const RUNTIME_BOOT = 'runtime.boot';

    /**
     * Option and environment wiring inside {@see \Clover\Framework\Component\Runtime::run()}.
     */
    public const RUNTIME_CONFIGURE_STARTED = 'runtime.configure.started';
    public const RUNTIME_CONFIGURE_FINISHED = 'runtime.configure.finished';

    /**
     * Mapper/runner resolution and execution inside {@see \Clover\Framework\Component\Runtime::run()}.
     */
    public const RUNTIME_MAPPING_STARTED = 'runtime.mapping.started';
    public const RUNTIME_MAPPING_FINISHED = 'runtime.mapping.finished';

    public const HANDLE_STARTED = 'kernel.handle.started';
    public const HANDLE_FINISHED = 'kernel.handle.finished';
    public const LOCALE_DETECTED = 'kernel.locale.detected';
    public const MIDDLEWARE_STACK_STARTED = 'kernel.middleware_stack.started';
    public const MIDDLEWARE_STACK_FINISHED = 'kernel.middleware_stack.finished';
    public const RESPONSE_SENDING = 'kernel.response.sending';
    public const RESPONSE_SENT = 'kernel.response.sent';
    public const HTML_RENDER_STARTED = 'kernel.html_render.started';
    public const HTML_RENDER_FINISHED = 'kernel.html_render.finished';
    public const PROFILER_SNAPSHOT_STORED = 'kernel.profiler.snapshot_stored';

    /**
     * @param array<string,mixed> $payload
     */
    public function __construct(
        public readonly string $type,
        public readonly array $payload = []
    ) {
    }
}
