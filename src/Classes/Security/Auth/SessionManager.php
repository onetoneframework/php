<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Security\Auth;

use Clover\Classes\HTTP\SessionDriver;

/**
 * Session Manager - Secure session handling
 */
class SessionManager
{
    private $session;
    private $userKey = 'authenticated_user';
    private $csrfTokenKey = 'csrf_token';
    private $sessionTimeout = 3600; 
    private $regenerateInterval = 300; 

    public function __construct()
    {
        $this->session = new SessionDriver();
        $this->initializeSession();
    }

    /**
     * Initialize session
     */
    private function initializeSession(): void
    {
        if (!$this->session->isStarted()) {
            $this->session->start();
        }

        
        $this->configureSessionSecurity();
        
        
        $this->checkSessionRegeneration();
    }

    /**
     * Configure session security settings
     */
    private function configureSessionSecurity(): void
    {
        
        $params = session_get_cookie_params();
        session_set_cookie_params([
            'lifetime' => $this->sessionTimeout,
            'path' => $params['path'],
            'domain' => $params['domain'],
            'secure' => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Strict'
        ]);

        
        if (!$this->session->get('session_regenerated')) {
            session_regenerate_id(true);
            $this->session->set('session_regenerated', time());
        }
    }

    /**
     * Check session regeneration timing
     */
    private function checkSessionRegeneration(): void
    {
        $lastRegeneration = $this->session->get('session_regenerated', 0);
        if (time() - $lastRegeneration > $this->regenerateInterval) {
            session_regenerate_id(true);
            $this->session->set('session_regenerated', time());
        }
    }

    /**
     * Set authenticated user in session
     */
    public function setUser(AuthenticatedUser $user): void
    {
        $this->session->set($this->userKey, $user->toArray());
        $this->session->set('user_last_activity', time());
        
        
        $this->generateCsrfToken();
    }

    /**
     * Get current user from session
     */
    public function getUser(): ?AuthenticatedUser
    {
        $userData = $this->session->get($this->userKey);
        if (!$userData) {
            return null;
        }

        
        if ($this->isSessionExpired()) {
            $this->clear();
            return null;
        }

        
        $this->session->set('user_last_activity', time());

        return $this->createUserFromArray($userData);
    }

    /**
     * Check session expiration
     */
    private function isSessionExpired(): bool
    {
        $lastActivity = $this->session->get('user_last_activity', 0);
        return (time() - $lastActivity) > $this->sessionTimeout;
    }

    /**
     * Create user object from array data
     */
    private function createUserFromArray(array $data): AuthenticatedUser
    {
        $user = new AuthenticatedUser();
        $user->setId($data['id'] ?? null);
        $user->setEmail($data['email'] ?? null);
        $user->setName($data['name'] ?? null);
        $user->setRoles($data['roles'] ?? []);
        $user->setPermissions($data['permissions'] ?? []);
        $user->setProvider($data['provider'] ?? null);
        $user->setProviderId($data['provider_id'] ?? null);
        $user->setActive($data['is_active'] ?? true);
        
        if (isset($data['last_login_at'])) {
            $user->setLastLoginAt(new \DateTime($data['last_login_at']));
        }
        if (isset($data['created_at'])) {
            $user->setCreatedAt(new \DateTime($data['created_at']));
        }
        if (isset($data['updated_at'])) {
            $user->setUpdatedAt(new \DateTime($data['updated_at']));
        }
        
        $user->setAttributes($data['attributes'] ?? []);

        return $user;
    }

    /**
     * Generate a CSRF token
     */
    public function generateCsrfToken(): string
    {
        $token = bin2hex(random_bytes(32));
        $this->session->set($this->csrfTokenKey, $token);
        return $token;
    }

    /**
     * Verify CSRF token
     */
    public function verifyCsrfToken(string $token): bool
    {
        $sessionToken = $this->session->get($this->csrfTokenKey);
        return $sessionToken && hash_equals($sessionToken, $token);
    }

    /**
     * Get current CSRF token
     */
    public function getCsrfToken(): ?string
    {
        return $this->session->get($this->csrfTokenKey);
    }

    /**
     * Set session data
     */
    public function set(string $key, $value): void
    {
        $this->session->set($key, $value);
    }

    /**
     * Get session data
     */
    public function get(string $key, $default = null)
    {
        return $this->session->get($key, $default);
    }

    /**
     * Remove session data
     */
    public function remove(string $key): void
    {
        $this->session->remove($key);
    }

    /**
     * Clear session
     */
    public function clear(): void
    {
        $this->session->destroy();
        $this->session->start();
    }

    /**
     * Set session timeout
     */
    public function setSessionTimeout(int $timeout): self
    {
        $this->sessionTimeout = $timeout;
        return $this;
    }

    /**
     * Set session regeneration interval
     */
    public function setRegenerateInterval(int $interval): self
    {
        $this->regenerateInterval = $interval;
        return $this;
    }

    /**
     * Get session ID
     */
    public function getId(): string
    {
        return $this->session->getId();
    }

    /**
     * Regenerate session ID
     */
    public function regenerateId(): void
    {
        session_regenerate_id(true);
        $this->session->set('session_regenerated', time());
    }
}
