<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Framework\Middleware;

use Clover\Framework\Component\Request;
use Clover\Framework\Component\Response;
use Clover\Framework\Component\Translator;
use Clover\Framework\Contract\MiddlewareInterface;
use Clover\Framework\Contract\RequestHandlerInterface;
use function explode;
use function in_array;
use function is_string;
use function parse_url;
use function trim;

/**
 * Maintenance Middleware
 * 
 * This middleware checks if the application is in maintenance mode by checking the 'APP_MAINTENANCE' environment variable. If it is set to 'true', it returns a 503 Service Unavailable response. Otherwise, it delegates the request to the next middleware component.
 */
class MaintenanceMiddleware implements MiddlewareInterface
{
    /**
     * Process an incoming server request and return a response, optionally delegating to the next middleware component to create the response.
     * 
     * @param Request $request
     * @param RequestHandlerInterface $handler
     * @return Response
     */
    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        if (($_ENV['APP_MAINTENANCE'] ?? 'false') === 'true' && !$this->isMaintenanceBypassPath($this->resolveRequestPath($request))) {
            return new Response(
                Translator::trans('errors.503', [], 'Service Unavailable'),
                [],
                'html',
                503
            );
        }

        return $handler->handle($request);
    }

    /**
     * Returns the path component for maintenance bypass checks (e.g. orchestrator probes).
     */
    private function resolveRequestPath(Request $request): string
    {
        $uri = $request->server['REQUEST_URI'] ?? '/';
        if (!is_string($uri)) {
            return '/';
        }
        $path = parse_url($uri, PHP_URL_PATH);

        return is_string($path) && $path !== '' ? $path : '/';
    }

    /**
     * Health and readiness endpoints must stay reachable so load balancers can drain traffic correctly.
     *
     * @return list<string>
     */
    private function maintenanceBypassPaths(): array
    {
        $paths = ['/health', '/ready'];
        $extra = $_ENV['APP_MAINTENANCE_BYPASS_PATHS'] ?? '';
        if (!is_string($extra) || trim($extra) === '') {
            return $paths;
        }
        foreach (explode(',', $extra) as $segment) {
            $p = trim($segment);
            if ($p !== '') {
                $paths[] = $p;
            }
        }

        return $paths;
    }

    private function isMaintenanceBypassPath(string $path): bool
    {
        return in_array($path, $this->maintenanceBypassPaths(), true);
    }
}

