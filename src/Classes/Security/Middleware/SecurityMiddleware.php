<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Security\Middleware;

use Closure;
use Clover\Classes\HTTP\Request;
use Clover\Classes\HTTP\Response;
use Clover\Contract\CacheStoreInterface;
use InvalidArgumentException;

/**
 * Security middleware interface
 */
interface SecurityMiddleware
{
    public function handle(Request $request, callable $next): Response;
}

/**
 * Authentication middleware
 */
class AuthenticationMiddleware implements SecurityMiddleware
{
    private $authenticationManager;
    private $excludedPaths = [];

    public function __construct($authenticationManager, array $excludedPaths = [])
    {
        $this->authenticationManager = $authenticationManager;
        $this->excludedPaths = $excludedPaths;
    }

    public function handle(Request $request, callable $next): Response
    {
        
        if ($this->isExcludedPath($request->getPath())) {
            return $next($request);
        }

        
        if (!$this->authenticationManager->isAuthenticated()) {
            return $this->unauthorizedResponse($request);
        }

        return $next($request);
    }

    private function isExcludedPath(string $path): bool
    {
        foreach ($this->excludedPaths as $excludedPath) {
            if (fnmatch($excludedPath, $path)) {
                return true;
            }
        }
        return false;
    }

    private function unauthorizedResponse(Request $request): Response
    {
        if ($request->isAjax() || $request->wantsJson()) {
            return new Response([
                'error' => 'Unauthorized',
                'message' => 'Authentication required'
            ], [], 'json', 401);
        }

        return new Response('Unauthorized', [], 'html', 401);
    }
}

    /**
     * Authorization middleware
     */
class AuthorizationMiddleware implements SecurityMiddleware
{
    private $authorizationGuard;
    private $requiredPermission;
    private $requiredRole;
    private $requiredRoles = [];

    public function __construct($authorizationGuard, ?string $requiredPermission = null, ?string $requiredRole = null, array $requiredRoles = [])
    {
        $this->authorizationGuard = $authorizationGuard;
        $this->requiredPermission = $requiredPermission;
        $this->requiredRole = $requiredRole;
        $this->requiredRoles = $requiredRoles;
    }

    public function handle(Request $request, callable $next): Response
    {
        $user = $this->authorizationGuard->getUser();
        
        if (!$user) {
            return new Response([
                'error' => 'Unauthorized',
                'message' => 'User not authenticated'
            ], [], 'json', 401);
        }

        
        if ($this->requiredPermission && !$this->authorizationGuard->hasPermission($user, $this->requiredPermission)) {
            return $this->forbiddenResponse($request, 'Insufficient permissions');
        }

        
        if ($this->requiredRole && !$this->authorizationGuard->hasRole($user, $this->requiredRole)) {
            return $this->forbiddenResponse($request, 'Insufficient role');
        }

        
        if (!empty($this->requiredRoles) && !$this->authorizationGuard->hasAnyRole($user, $this->requiredRoles)) {
            return $this->forbiddenResponse($request, 'Insufficient roles');
        }

        return $next($request);
    }

    private function forbiddenResponse(Request $request, string $message): Response
    {
        if ($request->isAjax() || $request->wantsJson()) {
            return new Response([
                'error' => 'Forbidden',
                'message' => $message
            ], [], 'json', 403);
        }

        return new Response('Forbidden', [], 'html', 403);
    }
}

    /**
     * CSRF protection middleware
     */
class CSRFProtectionMiddleware implements SecurityMiddleware
{
    private $sessionManager;
    private $excludedMethods = ['GET', 'HEAD', 'OPTIONS'];
    private $excludedPaths = [];

    public function __construct($sessionManager, array $excludedPaths = [])
    {
        $this->sessionManager = $sessionManager;
        $this->excludedPaths = $excludedPaths;
    }

    public function handle(Request $request, callable $next): Response
    {
        
        if (in_array($request->getMethod(), $this->excludedMethods)) {
            return $next($request);
        }

        
        if ($this->isExcludedPath($request->getPath())) {
            return $next($request);
        }

        
        $token = $request->getHeader('X-CSRF-Token') ?: $request->get('_token');
        
        if (!$token || !$this->sessionManager->verifyCsrfToken($token)) {
            return new Response([
                'error' => 'CSRF Token Mismatch',
                'message' => 'Invalid or missing CSRF token'
            ], [], 'json', 403);
        }

        return $next($request);
    }

    private function isExcludedPath(string $path): bool
    {
        foreach ($this->excludedPaths as $excludedPath) {
            if (fnmatch($excludedPath, $path)) {
                return true;
            }
        }
        return false;
    }
}

/**
 * Rate limiting middleware
 */
class RateLimitingMiddleware implements SecurityMiddleware
{
	private const DEFAULT_MAXIMUM_REQUESTS = 100;

	private const DEFAULT_TIME_WINDOW_SECONDS = 3_600;

	private const MINIMUM_POSITIVE_VALUE = 1;

	private const TOO_MANY_REQUESTS_STATUS = 429;

	private CacheStoreInterface $cache;

	private int $maxRequests;

	private int $timeWindow;

	private Closure $keyGenerator;

	public function __construct(
		CacheStoreInterface $cache,
		int $maxRequests = self::DEFAULT_MAXIMUM_REQUESTS,
		int $timeWindow = self::DEFAULT_TIME_WINDOW_SECONDS,
		?callable $keyGenerator = null
	) {
		if ($maxRequests < self::MINIMUM_POSITIVE_VALUE) {
			throw new InvalidArgumentException('The maximum request count must be at least one.');
		}

		if ($timeWindow < self::MINIMUM_POSITIVE_VALUE) {
			throw new InvalidArgumentException('The rate limit time window must be at least one second.');
		}

		$this->cache = $cache;
		$this->maxRequests = $maxRequests;
		$this->timeWindow = $timeWindow;
		$this->keyGenerator = Closure::fromCallable($keyGenerator ?? [$this, 'defaultKeyGenerator']);
	}

	public function handle(Request $request, callable $next): Response
	{
		$key = ($this->keyGenerator)($request);
		$current = $this->cache->increment($key, self::MINIMUM_POSITIVE_VALUE, $this->timeWindow);

		if ($current > $this->maxRequests) {
			return new Response([
				'error' => 'Rate Limit Exceeded',
				'message' => 'Too many requests',
				'retry_after' => $this->getRetryAfter($key),
			], [], 'json', self::TOO_MANY_REQUESTS_STATUS);
		}

		$response = $next($request);
		$response->setHeader('X-RateLimit-Limit', (string) $this->maxRequests);
		$response->setHeader('X-RateLimit-Remaining', (string) max(0, $this->maxRequests - $current));
		$response->setHeader('X-RateLimit-Reset', (string) (time() + $this->cache->getTimeToLive($key)));

		return $response;
	}

	private function defaultKeyGenerator(Request $request): string
	{
		return 'rate_limit:' . $request->getClientIp();
	}

	private function getRetryAfter(string $key): int
	{
		$timeToLive = $this->cache->getTimeToLive($key);

		return $timeToLive > 0 ? $timeToLive : $this->timeWindow;
	}
}

    /**
     * Security headers middleware
     */
class SecurityHeadersMiddleware implements SecurityMiddleware
{
    private $headers = [
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'DENY',
        'X-XSS-Protection' => '1; mode=block',
        'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'",
        'Permissions-Policy' => 'geolocation=(), microphone=(), camera=()'
    ];

    public function __construct(array $customHeaders = [])
    {
        $this->headers = array_merge($this->headers, $customHeaders);
    }

    public function handle(Request $request, callable $next): Response
    {
        $response = $next($request);

        
        foreach ($this->headers as $name => $value) {
            $response->setHeader($name, $value);
        }

        return $response;
    }
}

    /**
     * Input validation middleware
     */
class InputValidationMiddleware implements SecurityMiddleware
{
    private $rules;
    private $sanitizer;

    public function __construct(array $rules = [], $sanitizer = null)
    {
        $this->rules = $rules;
        $this->sanitizer = $sanitizer ?: new InputSanitizer();
    }

    public function handle(Request $request, callable $next): Response
    {
        
        $data = $request->all();
        $sanitizedData = $this->sanitizer->sanitize($data);
        
        
        $validationResult = $this->validate($sanitizedData);
        
        if (!$validationResult->isValid()) {
            return new Response([
                'error' => 'Validation Failed',
                'message' => 'Invalid input data',
                'errors' => $validationResult->getErrors()
            ], [], 'json', 422);
        }

        
        $request->replace($sanitizedData);

        return $next($request);
    }

    private function validate(array $data): ValidationResult
    {
        $errors = [];
        
        foreach ($this->rules as $field => $rules) {
            $value = $data[$field] ?? null;
            
            foreach ($rules as $rule) {
                if (!$this->applyRule($value, $rule)) {
                    $errors[$field][] = "Field '{$field}' failed validation rule: {$rule}";
                }
            }
        }

        return new ValidationResult(empty($errors), $errors);
    }

    private function applyRule($value, string $rule): bool
    {
        
        switch ($rule) {
            case 'required':
                return !empty($value);
            case 'email':
                return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
            case 'numeric':
                return is_numeric($value);
            case 'string':
                return is_string($value);
            default:
                return true;
        }
    }
}

    /**
     * Validation result class
     */
class ValidationResult
{
    private $isValid;
    private $errors;

    public function __construct(bool $isValid, array $errors = [])
    {
        $this->isValid = $isValid;
        $this->errors = $errors;
    }

    public function isValid(): bool
    {
        return $this->isValid;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
