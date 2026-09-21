<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Framework\Livewire;

use Clover\Classes\Routing\Router;

/**
 * Registers the HTTP endpoints exposed by the Livewire-like subsystem.
 *
 * This mirrors the pattern used by {@see \App\Provider\ProfilerRouteProvider}
 * and {@see \App\Provider\GraphQLRouteProvider} so the routing layer
 * stays declarative and the Livewire controller never has to live
 * under `root/App/Controller`.
 */
final class LivewireRouteProvider
{
    public static function register(Router $router): void
    {
        $router->get('/livewire/livewire.js', LivewireController::class . '::script');
        $router->post('/livewire/update', LivewireController::class . '::update');
    }
}
