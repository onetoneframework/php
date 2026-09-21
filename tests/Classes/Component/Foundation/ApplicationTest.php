<?php

declare(strict_types=1);

namespace Clover\Tests\Component\Foundation;

use Clover\Component\Foundation\Application;
use Clover\Support\ServiceProvider;
use PHPUnit\Framework\TestCase;

class ApplicationTest extends TestCase
{
    protected function tearDown(): void
    {
        Application::setInstance(null);
    }

    public function testBasePathBindingsAreRegistered(): void
    {
        $app = new Application('C:/tmp/example-app/');

        $this->assertSame('C:/tmp/example-app', $app->make('path'));
        $this->assertSame('C:/tmp/example-app', $app->make('path.base'));
        $this->assertSame('C:/tmp/example-app' . DIRECTORY_SEPARATOR . 'config', $app->make('path.config'));
    }

    public function testRegisterSkipsDuplicateProviderUnlessForced(): void
    {
        CountingServiceProvider::$registerCalls = 0;

        $app = new Application();
        $first = $app->register(CountingServiceProvider::class);
        $second = $app->register(CountingServiceProvider::class);
        $forced = $app->register(CountingServiceProvider::class, true);

        $this->assertSame($first, $second);
        $this->assertInstanceOf(CountingServiceProvider::class, $forced);
        $this->assertSame(2, CountingServiceProvider::$registerCalls);
    }

    public function testGetInstanceReturnsExplicitlySetInstance(): void
    {
        $app = new Application();
        Application::setInstance($app);

        $this->assertSame($app, Application::getInstance());
    }
}

class CountingServiceProvider extends ServiceProvider
{
    public static int $registerCalls = 0;

    public function register(): void
    {
        self::$registerCalls++;
    }
}
