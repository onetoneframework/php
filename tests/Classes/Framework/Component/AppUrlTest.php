<?php

declare(strict_types=1);

namespace Clover\Tests\Framework\Component;

use Clover\Framework\Component\AppUrl;
use PHPUnit\Framework\TestCase;

final class AppUrlTest extends TestCase
{
    private array $originalEnv = [];

    protected function setUp(): void
    {
        $this->originalEnv = $_ENV;
    }

    protected function tearDown(): void
    {
        $_ENV = $this->originalEnv;
        putenv('APP_PUBLIC_URL');
    }

    public function testBaseUsesConfiguredEnvironmentValue(): void
    {
        $_ENV['APP_PUBLIC_URL'] = ' https://onetone.dev/base/ ';

        $base = AppUrl::base();

        $this->assertSame('https://onetone.dev/base', $base);
    }

    public function testBaseFallsBackToDefaultWhenEnvironmentIsBlank(): void
    {
        $_ENV['APP_PUBLIC_URL'] = '   ';

        $base = AppUrl::base('https://fallback.dev/');

        $this->assertSame('https://fallback.dev', $base);
    }

    public function testAbsolutePrependsSlashForRelativePath(): void
    {
        $_ENV['APP_PUBLIC_URL'] = 'https://onetone.dev';

        $absolute = AppUrl::absolute('docs/getting-started');

        $this->assertSame('https://onetone.dev/docs/getting-started', $absolute);
    }

    public function testAbsoluteKeepsLeadingSlashPath(): void
    {
        $_ENV['APP_PUBLIC_URL'] = 'https://onetone.dev/';

        $absolute = AppUrl::absolute('/api/v1/health');

        $this->assertSame('https://onetone.dev/api/v1/health', $absolute);
    }
}
