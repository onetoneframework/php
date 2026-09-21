<?php

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

declare(strict_types=1);

namespace Clover\Classes\Debug;

use Clover\Classes\Event\AbstractSubscriber;
use Clover\Framework\Component\TraceContext;
use Clover\Framework\Event\DatabaseLifecycleEvent;
use Clover\Framework\Event\HttpClientLifecycleEvent;
use Clover\Framework\Event\KernelLifecycleEvent;
use Clover\Framework\Event\RoutingLifecycleEvent;
use function date;
use function defined;
use function file_put_contents;
use function is_dir;
use function is_string;
use function json_encode;
use function mkdir;
use function rtrim;
use const DATE_ATOM;
use const FILE_APPEND;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;
use const LOCK_EX;

final class KernelLifecycleLoggerListener extends AbstractSubscriber
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelLifecycleEvent::class => 'onKernelLifecycleEvent',
            RoutingLifecycleEvent::class => 'onRoutingLifecycleEvent',
            DatabaseLifecycleEvent::class => 'onDatabaseLifecycleEvent',
            HttpClientLifecycleEvent::class => 'onHttpClientLifecycleEvent',
        ];
    }

    public function onKernelLifecycleEvent(object $event, ?string $eventName = null, mixed $dispatcher = null): void
    {
        if (!($event instanceof KernelLifecycleEvent)) {
            return;
        }

        self::writeEvent('kernel', $event->type, $event->payload);
    }

    public function onRoutingLifecycleEvent(object $event, ?string $eventName = null, mixed $dispatcher = null): void
    {
        if (!($event instanceof RoutingLifecycleEvent)) {
            return;
        }

        self::writeEvent('routing', $event->type, $event->payload);
    }

    public function onDatabaseLifecycleEvent(object $event, ?string $eventName = null, mixed $dispatcher = null): void
    {
        if (!($event instanceof DatabaseLifecycleEvent)) {
            return;
        }

        self::writeEvent('database', $event->type, $event->payload);
    }

    public function onHttpClientLifecycleEvent(object $event, ?string $eventName = null, mixed $dispatcher = null): void
    {
        if (!($event instanceof HttpClientLifecycleEvent)) {
            return;
        }

        self::writeEvent('http_client', $event->type, $event->payload);
    }

    /**
     * @param array<string,mixed> $payload
     */
    private static function writeEvent(string $channel, string $type, array $payload = []): void
    {
        if (!defined('BASE_PATH')) {
            return;
        }

        $dir = rtrim(BASE_PATH, '/\\') . DIRECTORY_SEPARATOR . 'App/Cache/events';
        if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
            return;
        }

        $record = [
            'at' => date(DATE_ATOM),
            'channel' => $channel,
            'type' => $type,
            'trace_id' => TraceContext::getTraceId(),
            'payload' => TraceContext::appendTrace($payload),
        ];

        $line = json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($line)) {
            return;
        }

        file_put_contents($dir . DIRECTORY_SEPARATOR . 'kernel-lifecycle.log', $line . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}
