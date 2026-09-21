<?php

declare(strict_types=1);

namespace Clover\Tests\Framework\Component;

use Clover\Framework\Component\Runtime;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class RuntimeTest extends TestCase
{
    public function testSerializeReturnsEnvironmentPayload(): void
    {
        $runtime = new Runtime();

        $reflection = new ReflectionClass($runtime);
        $property = $reflection->getProperty('environment');
        if (version_compare(PHP_VERSION, '8.1.0', '<')) {
            // @phpstan-ignore-next-line
            $property->setAccessible(true);
        }
        $property->setValue($runtime, [
            'server' => ['software' => 'nginx'],
            'php' => ['version' => '8.4.0']
        ]);

        $serialized = $runtime->serialize();

        $this->assertSame(
            serialize([
                [
                    'server' => ['software' => 'nginx'],
                    'php' => ['version' => '8.4.0']
                ]
            ]),
            $serialized
        );
    }
}
