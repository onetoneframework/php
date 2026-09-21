<?php

declare(strict_types=1);

namespace App\Provider;

use App\Controller\ProfilerController;
use Clover\Classes\Routing\Router;

/**
 * Registers profiler-related HTTP routes on the application {@see Router}.
 */
final class ProfilerRouteProvider
{
    public static function register(Router $router): void
    {
        $router->get('/profiler', ProfilerController::class . '::profiler');
    }
}
