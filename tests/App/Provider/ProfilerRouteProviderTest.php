<?php

declare(strict_types=1);

namespace Clover\Tests\App\Provider;

use App\Provider\ProfilerRouteProvider;
use Clover\Classes\Routing\Router;
use PHPUnit\Framework\TestCase;

class ProfilerRouteProviderTest extends TestCase
{
    public function testRegisterAddsProfilerGetRoute(): void
    {
        $router = new Router();
        ProfilerRouteProvider::register($router);

        $found = false;
        foreach ($router->getRoutesByMethod('GET') as $route) {
            if ((string) $route->getPattern() === '/profiler') {
                $found = true;
                $this->assertSame(
                    ['App\\Controller\\ProfilerController', 'profiler'],
                    $route->getClassAndMethod()
                );
            }
        }

        $this->assertTrue($found, 'Expected GET /profiler to be registered.');
    }
}
