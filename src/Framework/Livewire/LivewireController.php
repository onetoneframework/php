<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Framework\Livewire;

use Clover\Classes\Data\JSONHandler;
use Clover\Classes\DependencyInjection\Container;
use Clover\Framework\Component\BaseController;
use Clover\Framework\Component\Response;
use Clover\Framework\Context\ApplicationContext;
use Throwable;
use function file_get_contents;

/**
 * HTTP surface for the Livewire-like subsystem.
 *
 * Routes are registered via {@see LivewireRouteProvider::register()}:
 *
 *   GET  /livewire/livewire.js   → static JS client
 *   POST /livewire/update        → hydration + action endpoint
 *
 * Kept intentionally framework-internal so applications don't need
 * to copy a controller file to opt in; see {@see \Clover\Framework\Middleware\RoutingMiddleware}.
 */
class LivewireController extends BaseController
{
    public function __construct(?Container $container = null)
    {
        parent::__construct($container);
    }

    /**
     * Serve the tiny browser runtime bundled with the framework.
     * 
     * @return Response
     */
    public function script(): Response
    {
        $path = __DIR__ . '/Assets/livewire.js';
        $body = @file_get_contents($path);
        if ($body === false) {
            return new Response('// Livewire runtime not found', [], 'javascript', 500);
        }

        $response = new Response($body, [], 'javascript');
        $response->setHeader('Cache-Control', 'public, max-age=300');
        return $response;
    }

    /**
     * Handle a client update (wire:click / wire:model / etc.).
     * 
     * @return Response
     */
    public function update(): Response
    {
        $raw = file_get_contents('php://input');
        if ($raw === false || $raw === '') {
            return $this->responseJson(['error' => 'Empty request body']);
        }

        $payload = JSONHandler::decode($raw, true);
        if (!$payload->isCountable()) {
            return $this->responseJson(['error' => 'Malformed JSON payload']);
        }

        $manager = $this->resolveManager();
        $manager->ensureAutoloaded();

        try {
            $response = $manager->updateHandler()->handle($payload->toPHPObject());
        } catch (Throwable $e) {
            return $this->responseJson([
                'error' => $e->getMessage(),
            ]);
        }

        return $this->responseJson($response);
    }

    private function resolveManager(): LivewireManager
    {
        $container = $this->container ?? ApplicationContext::getContainer();

        if ($container !== null && $container->has(LivewireManager::class)) {
            /** @var LivewireManager $manager */
            $manager = $container->get(LivewireManager::class);
            return $manager;
        }

        // Lazy fallback: build a transient manager so the endpoint still works
        // even if the application forgot to register it in the container.
        return new LivewireManager($container);
    }
}
