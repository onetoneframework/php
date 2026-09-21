<?php

declare(strict_types=1);

namespace Clover\Tests\Framework\Event;

use Clover\Framework\Event\KernelLifecycleEvent;
use PHPUnit\Framework\TestCase;

final class KernelLifecycleEventTest extends TestCase
{
    public function testRuntimeLifecycleTypeStringsAreStable(): void
    {
        $this->assertSame('runtime.boot', KernelLifecycleEvent::RUNTIME_BOOT);
        $this->assertSame('runtime.configure.started', KernelLifecycleEvent::RUNTIME_CONFIGURE_STARTED);
        $this->assertSame('runtime.configure.finished', KernelLifecycleEvent::RUNTIME_CONFIGURE_FINISHED);
        $this->assertSame('runtime.mapping.started', KernelLifecycleEvent::RUNTIME_MAPPING_STARTED);
        $this->assertSame('runtime.mapping.finished', KernelLifecycleEvent::RUNTIME_MAPPING_FINISHED);
    }

    public function testKernelLifecycleEventExposesTypeAndPayload(): void
    {
        $event = new KernelLifecycleEvent(KernelLifecycleEvent::HANDLE_STARTED, ['uri' => '/']);

        $this->assertSame(KernelLifecycleEvent::HANDLE_STARTED, $event->type);
        $this->assertSame(['uri' => '/'], $event->payload);
    }
}
