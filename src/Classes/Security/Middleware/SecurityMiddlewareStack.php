<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Security\Middleware;

use Clover\Classes\HTTP\Request;
use Clover\Classes\HTTP\Response;

/**
 * Security middleware stack
 */
class SecurityMiddlewareStack
{
    private $middlewares = [];
    private $index = 0;

    public function __construct(array $middlewares = [])
    {
        $this->middlewares = $middlewares;
    }

    /**
     * Add middleware
     */
    public function add(SecurityMiddleware $middleware): self
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    /**
     * Prepend middleware
     */
    public function prepend(SecurityMiddleware $middleware): self
    {
        array_unshift($this->middlewares, $middleware);
        return $this;
    }

    /**
     * Remove middleware
     */
    public function remove(string $middlewareClass): self
    {
        $this->middlewares = array_filter($this->middlewares, function($middleware) use ($middlewareClass) {
            return !($middleware instanceof $middlewareClass);
        });
        return $this;
    }

    /**
     * Execute middleware stack
     */
    public function handle(Request $request, callable $finalHandler): Response
    {
        $this->index = 0;
        return $this->callNext($request, $finalHandler);
    }

    /**
     * Call next middleware
     */
    private function callNext(Request $request, callable $finalHandler): Response
    {
        if ($this->index >= count($this->middlewares)) {
            return $finalHandler($request);
        }

        $middleware = $this->middlewares[$this->index++];
        
        return $middleware->handle($request, function($request) use ($finalHandler) {
            return $this->callNext($request, $finalHandler);
        });
    }

    /**
     * Reset middleware stack
     */
    public function reset(): self
    {
        $this->middlewares = [];
        $this->index = 0;
        return $this;
    }

    /**
     * Get middleware count
     */
    public function count(): int
    {
        return count($this->middlewares);
    }

    /**
     * Get middleware list
     */
    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }
}

/**
 * Security middleware factory
 */
class SecurityMiddlewareFactory
{
    /**
     * Create default security stack
     */
    public static function createDefaultStack(array $config = []): SecurityMiddlewareStack
    {
        $stack = new SecurityMiddlewareStack();
        
        
        $stack->add(new SecurityHeadersMiddleware($config['security_headers'] ?? []));
        
        
        if (isset($config['input_validation'])) {
            $stack->add(new InputValidationMiddleware(
                $config['input_validation']['rules'] ?? [],
                $config['input_validation']['sanitizer'] ?? null
            ));
        }
        
        
        if (isset($config['csrf_protection'])) {
            $stack->add(new CSRFProtectionMiddleware(
                $config['csrf_protection']['session_manager'],
                $config['csrf_protection']['excluded_paths'] ?? []
            ));
        }
        
        
        if (isset($config['rate_limiting'])) {
            $stack->add(new RateLimitingMiddleware(
                $config['rate_limiting']['cache'],
                $config['rate_limiting']['max_requests'] ?? 100,
                $config['rate_limiting']['time_window'] ?? 3600,
                $config['rate_limiting']['key_generator'] ?? null
            ));
        }
        
        
        if (isset($config['authentication'])) {
            $stack->add(new AuthenticationMiddleware(
                $config['authentication']['manager'],
                $config['authentication']['excluded_paths'] ?? []
            ));
        }
        
        
        if (isset($config['authorization'])) {
            $stack->add(new AuthorizationMiddleware(
                $config['authorization']['guard'],
                $config['authorization']['required_permission'] ?? null,
                $config['authorization']['required_role'] ?? null,
                $config['authorization']['required_roles'] ?? []
            ));
        }
        
        return $stack;
    }

    /**
     * Create API security stack
     */
    public static function createApiStack(array $config = []): SecurityMiddlewareStack
    {
        $stack = new SecurityMiddlewareStack();
        
        
        $stack->add(new SecurityHeadersMiddleware($config['security_headers'] ?? []));
        
        
        if (isset($config['rate_limiting'])) {
            $stack->add(new RateLimitingMiddleware(
                $config['rate_limiting']['cache'],
                $config['rate_limiting']['max_requests'] ?? 1000,
                $config['rate_limiting']['time_window'] ?? 3600
            ));
        }
        
        
        if (isset($config['authentication'])) {
            $stack->add(new AuthenticationMiddleware(
                $config['authentication']['manager'],
                $config['authentication']['excluded_paths'] ?? ['/api/auth/*', '/api/health']
            ));
        }
        
        return $stack;
    }

    /**
     * Create web security stack
     */
    public static function createWebStack(array $config = []): SecurityMiddlewareStack
    {
        $stack = new SecurityMiddlewareStack();
        
        
        $stack->add(new SecurityHeadersMiddleware($config['security_headers'] ?? []));
        
        
        if (isset($config['input_validation'])) {
            $stack->add(new InputValidationMiddleware(
                $config['input_validation']['rules'] ?? [],
                $config['input_validation']['sanitizer'] ?? null
            ));
        }
        
        
        if (isset($config['csrf_protection'])) {
            $stack->add(new CSRFProtectionMiddleware(
                $config['csrf_protection']['session_manager'],
                $config['csrf_protection']['excluded_paths'] ?? ['/api/*']
            ));
        }
        
        
        if (isset($config['authentication'])) {
            $stack->add(new AuthenticationMiddleware(
                $config['authentication']['manager'],
                $config['authentication']['excluded_paths'] ?? ['/login', '/register', '/public/*']
            ));
        }
        
        return $stack;
    }

    /**
     * Create admin security stack
     */
    public static function createAdminStack(array $config = []): SecurityMiddlewareStack
    {
        $stack = new SecurityMiddlewareStack();
        
        
        $stack->add(new SecurityHeadersMiddleware($config['security_headers'] ?? []));
        
        
        if (isset($config['input_validation'])) {
            $stack->add(new InputValidationMiddleware(
                $config['input_validation']['rules'] ?? [],
                $config['input_validation']['sanitizer'] ?? null
            ));
        }
        
        
        if (isset($config['csrf_protection'])) {
            $stack->add(new CSRFProtectionMiddleware(
                $config['csrf_protection']['session_manager']
            ));
        }
        
        
        if (isset($config['authentication'])) {
            $stack->add(new AuthenticationMiddleware(
                $config['authentication']['manager']
            ));
        }
        
        
        if (isset($config['authorization'])) {
            $stack->add(new AuthorizationMiddleware(
                $config['authorization']['guard'],
                null,
                'admin'
            ));
        }
        
        return $stack;
    }
}
