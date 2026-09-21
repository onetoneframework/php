<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Security;

use Clover\Classes\Security\Auth\AuthenticationManager;
use Clover\Classes\Security\Auth\SessionManager;
use Clover\Classes\Security\Auth\TokenManager;
use Clover\Classes\Security\Guard\RBACManager;
use Clover\Classes\Security\Guard\AuthorizationGuard;
use Clover\Classes\Security\Middleware\SecurityMiddlewareStack;
use Clover\Classes\Security\Middleware\SecurityMiddlewareFactory;

/**
 * Security Manager - Centralized security system manager
 */
class SecurityManager
{
    private $authenticationManager;
    private $rbacManager;
    private $authorizationGuard;
    private $securityAuditor;
    private $securityTester;
    private $middlewareStack;
    private $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->initializeComponents();
    }

    /**
     * Initialize security components
     */
    private function initializeComponents(): void
    {
        
        $sessionManager = new SessionManager();
        
        
        $tokenManager = new TokenManager($this->config['jwt_secret'] ?? 'default_secret');
        
        
        $this->authenticationManager = new AuthenticationManager($sessionManager, $tokenManager);
        
        
        $this->rbacManager = new RBACManager();
        
        
        $this->authorizationGuard = new AuthorizationGuard($this->rbacManager);
        
        
        $this->securityAuditor = new SecurityAuditor($this->config['logger'] ?? null);
        
        
        $this->securityTester = new SecurityTester($this->config['app_url'] ?? 'http://localhost');
        
        
        $this->initializeMiddlewareStack();
    }

    /**
     * Initialize middleware stack
     */
    private function initializeMiddlewareStack(): void
    {
        $middlewareConfig = [
            'security_headers' => $this->config['security_headers'] ?? [],
            'input_validation' => [
                'rules' => $this->config['validation_rules'] ?? [],
                'sanitizer' => null
            ],
            'csrf_protection' => [
                'session_manager' => $this->authenticationManager->getSessionManager(),
                'excluded_paths' => $this->config['csrf_excluded_paths'] ?? ['/api/*']
            ],
            'rate_limiting' => [
                'cache' => $this->config['cache'] ?? null,
                'max_requests' => $this->config['rate_limit_max'] ?? 100,
                'time_window' => $this->config['rate_limit_window'] ?? 3600
            ],
            'authentication' => [
                'manager' => $this->authenticationManager,
                'excluded_paths' => $this->config['auth_excluded_paths'] ?? ['/login', '/register', '/public/*']
            ],
            'authorization' => [
                'guard' => $this->authorizationGuard,
                'required_permission' => null,
                'required_role' => null,
                'required_roles' => []
            ]
        ];

        $this->middlewareStack = SecurityMiddlewareFactory::createDefaultStack($middlewareConfig);
    }

    /**
     * Get authentication manager
     */
    public function getAuthenticationManager(): AuthenticationManager
    {
        return $this->authenticationManager;
    }

    /**
     * Get RBAC manager
     */
    public function getRBACManager(): RBACManager
    {
        return $this->rbacManager;
    }

    /**
     * Get authorization guard
     */
    public function getAuthorizationGuard(): AuthorizationGuard
    {
        return $this->authorizationGuard;
    }

    /**
     * Get security auditor
     */
    public function getSecurityAuditor(): SecurityAuditor
    {
        return $this->securityAuditor;
    }

    /**
     * Get security tester
     */
    public function getSecurityTester(): SecurityTester
    {
        return $this->securityTester;
    }

    /**
     * Get middleware stack
     */
    public function getMiddlewareStack(): SecurityMiddlewareStack
    {
        return $this->middlewareStack;
    }

    /**
     * Create API middleware stack
     */
    public function createApiMiddlewareStack(): SecurityMiddlewareStack
    {
        $middlewareConfig = [
            'security_headers' => $this->config['security_headers'] ?? [],
            'rate_limiting' => [
                'cache' => $this->config['cache'] ?? null,
                'max_requests' => $this->config['api_rate_limit_max'] ?? 1000,
                'time_window' => $this->config['api_rate_limit_window'] ?? 3600
            ],
            'authentication' => [
                'manager' => $this->authenticationManager,
                'excluded_paths' => $this->config['api_auth_excluded_paths'] ?? ['/api/auth/*', '/api/health']
            ]
        ];

        return SecurityMiddlewareFactory::createApiStack($middlewareConfig);
    }

    /**
     * Create web middleware stack
     */
    public function createWebMiddlewareStack(): SecurityMiddlewareStack
    {
        $middlewareConfig = [
            'security_headers' => $this->config['security_headers'] ?? [],
            'input_validation' => [
                'rules' => $this->config['validation_rules'] ?? [],
                'sanitizer' => null
            ],
            'csrf_protection' => [
                'session_manager' => $this->authenticationManager->getSessionManager(),
                'excluded_paths' => $this->config['csrf_excluded_paths'] ?? ['/api/*']
            ],
            'authentication' => [
                'manager' => $this->authenticationManager,
                'excluded_paths' => $this->config['web_auth_excluded_paths'] ?? ['/login', '/register', '/public/*']
            ]
        ];

        return SecurityMiddlewareFactory::createWebStack($middlewareConfig);
    }

    /**
     * Create admin middleware stack
     */
    public function createAdminMiddlewareStack(): SecurityMiddlewareStack
    {
        $middlewareConfig = [
            'security_headers' => $this->config['security_headers'] ?? [],
            'input_validation' => [
                'rules' => $this->config['admin_validation_rules'] ?? [],
                'sanitizer' => null
            ],
            'csrf_protection' => [
                'session_manager' => $this->authenticationManager->getSessionManager()
            ],
            'authentication' => [
                'manager' => $this->authenticationManager
            ],
            'authorization' => [
                'guard' => $this->authorizationGuard,
                'required_role' => 'admin'
            ]
        ];

        return SecurityMiddlewareFactory::createAdminStack($middlewareConfig);
    }

    /**
     * Create OAuth2 provider
     */
    public function createOAuth2Provider(string $provider, array $config): \Clover\Classes\Security\Auth\OAuth2Provider
    {
        $providerConfigs = [
            'google' => [
                'authorization_endpoint' => 'https://accounts.google.com/o/oauth2/v2/auth',
                'token_endpoint' => 'https://oauth2.googleapis.com/token',
                'userinfo_endpoint' => 'https://www.googleapis.com/oauth2/v2/userinfo',
                'scope' => 'openid profile email'
            ],
            'github' => [
                'authorization_endpoint' => 'https://github.com/login/oauth/authorize',
                'token_endpoint' => 'https://github.com/login/oauth/access_token',
                'userinfo_endpoint' => 'https://api.github.com/user',
                'scope' => 'user:email'
            ],
            'facebook' => [
                'authorization_endpoint' => 'https://www.facebook.com/v18.0/dialog/oauth',
                'token_endpoint' => 'https://graph.facebook.com/v18.0/oauth/access_token',
                'userinfo_endpoint' => 'https://graph.facebook.com/me',
                'scope' => 'email'
            ]
        ];

        $defaultConfig = $providerConfigs[$provider] ?? [];
        $mergedConfig = array_merge($defaultConfig, $config);

        return new \Clover\Classes\Security\Auth\OAuth2Provider($mergedConfig);
    }

    /**
     * Update security configuration
     */
    public function updateSecurityConfig(array $config): self
    {
        $this->config = array_merge($this->config, $config);
        $this->initializeComponents();
        return $this;
    }

    /**
     * Get security status
     */
    public function getSecurityStatus(): array
    {
        $auditReport = $this->securityAuditor->generateSecurityReport(24);
        
        return [
            'authentication_enabled' => $this->authenticationManager !== null,
            'rbac_enabled' => $this->rbacManager !== null,
            'middleware_count' => $this->middlewareStack->count(),
            'security_score' => $auditReport['security_score'],
            'suspicious_activities' => $auditReport['suspicious_activities'],
            'failed_attempts' => $auditReport['failed_attempts'],
            'rate_limit_violations' => $auditReport['rate_limit_violations'],
            'recommendations' => $auditReport['recommendations']
        ];
    }

    /**
     * Run security tests
     */
    public function runSecurityTest(): array
    {
        return $this->securityTester->runFullSecurityTest();
    }

    /**
     * Generate audit report
     */
    public function generateAuditReport(int $hours = 24): array
    {
        return $this->securityAuditor->generateSecurityReport($hours);
    }

    /**
     * Get blocked IP recommendations
     */
    public function getBlockedIPRecommendations(): array
    {
        return $this->securityAuditor->getBlockedIPRecommendations();
    }

    /**
     * Log security event
     */
    public function logSecurityEvent(string $type, array $data): void
    {
        switch ($type) {
            case 'authentication_attempt':
                $this->securityAuditor->logAuthenticationAttempt(
                    $data['username'],
                    $data['ip'],
                    $data['success'],
                    $data['method'] ?? 'password'
                );
                break;
                
            case 'authorization_attempt':
                $this->securityAuditor->logAuthorizationAttempt(
                    $data['user_id'],
                    $data['permission'],
                    $data['success'],
                    $data['resource'] ?? null
                );
                break;
                
            case 'rate_limit_violation':
                $this->securityAuditor->logRateLimitViolation(
                    $data['ip'],
                    $data['endpoint'],
                    $data['attempts'],
                    $data['limit']
                );
                break;
                
            case 'csrf_violation':
                $this->securityAuditor->logCSRFViolation(
                    $data['ip'],
                    $data['endpoint'],
                    $data['token'] ?? null
                );
                break;
        }
    }

    /**
     * Validate security configuration
     */
    public function validateSecurityConfig(): array
    {
        $issues = [];
        
        
        if (empty($this->config['jwt_secret']) || $this->config['jwt_secret'] === 'default_secret') {
            $issues[] = 'JWT secret is not properly configured';
        }
        
        
        if (!isset($this->config['session_secure']) || !$this->config['session_secure']) {
            $issues[] = 'Session cookies should be marked as secure in production';
        }
        
        
        if (!isset($this->config['csrf_protection']) || !$this->config['csrf_protection']) {
            $issues[] = 'CSRF protection is not enabled';
        }
        
        
        if (!isset($this->config['rate_limiting']) || !$this->config['rate_limiting']) {
            $issues[] = 'Rate limiting is not configured';
        }
        
        return [
            'valid' => empty($issues),
            'issues' => $issues
        ];
    }
}

