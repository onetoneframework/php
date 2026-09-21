<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Security\Auth;

use Clover\Classes\Security\Guard\AuthenticationGuard;
use Clover\Classes\Security\Guard\AuthorizationGuard;
use Clover\Classes\Token\JWTToken;
use Clover\Classes\Hash\PasswordHash;

/**
 * Authentication Manager - Centralized authentication system
 */
class AuthenticationManager
{
    private $guards = [];
    private $defaultGuard;
    private $sessionManager;
    private $tokenManager;

    public function __construct(SessionManager $sessionManager, TokenManager $tokenManager)
    {
        $this->sessionManager = $sessionManager;
        $this->tokenManager = $tokenManager;
    }

    public function getSessionManager()
    {
        return $this->sessionManager;
    }

    /**
     * Register an authentication guard
     */
    public function addGuard(string $name, AuthenticationGuard $guard): self
    {
        $this->guards[$name] = $guard;
        if (!$this->defaultGuard) {
            $this->defaultGuard = $name;
        }
        return $this;
    }

    /**
     * Set default guard
     */
    public function setDefaultGuard(string $name): self
    {
        if (!isset($this->guards[$name])) {
            throw new \Exception("Guard '{$name}' not found");
        }
        $this->defaultGuard = $name;
        return $this;
    }

    /**
     * Authenticate a user
     */
    public function authenticate(array $credentials, ?string $guard = null): AuthenticationResult
    {
        $guard = $guard ?: $this->defaultGuard;
        
        if (!isset($this->guards[$guard])) {
            throw new \Exception("Guard '{$guard}' not found");
        }

        $result = $this->guards[$guard]->authenticate($credentials);
        
        if ($result->isSuccess()) {
            $this->sessionManager->setUser($result->getUser());
            $this->tokenManager->createToken($result->getUser());
        }

        return $result;
    }

    /**
     * Get currently authenticated user
     */
    public function getUser(): ?AuthenticatedUser
    {
        return $this->sessionManager->getUser();
    }

    /**
     * Check if a user is authenticated
     */
    public function isAuthenticated(): bool
    {
        return $this->getUser() !== null;
    }

    /**
     * Logout current user
     */
    public function logout(): void
    {
        $this->sessionManager->clear();
        $this->tokenManager->revokeToken();
    }

    /**
     * Authenticate using OAuth2
     */
    public function authenticateWithOAuth2(OAuth2Provider $provider, string $code, ?string $state = null): AuthenticationResult
    {
        try {
            
            $tokenData = $provider->exchangeCodeForToken($code, $state);
            
            
            $userInfo = $provider->getUserInfo($tokenData['access_token']);
            
            
            if (isset($tokenData['id_token'])) {
                $idTokenPayload = $provider->verifyIdToken($tokenData['id_token'], $provider->getNonce());
                $userInfo = array_merge($userInfo, $idTokenPayload);
            }

            
            $user = $this->createOrUpdateOAuth2User($userInfo);
            
            
            $this->sessionManager->setUser($user);
            $this->tokenManager->createToken($user);

            return new AuthenticationResult(true, $user, 'OAuth2 authentication successful');
            
        } catch (\Exception $e) {
            return new AuthenticationResult(false, null, $e->getMessage());
        }
    }

    /**
     * Create or update OAuth2 user from provider data
     */
    private function createOrUpdateOAuth2User(array $userInfo): AuthenticatedUser
    {
        
        $user = new AuthenticatedUser();
        $user->setId($userInfo['sub'] ?? $userInfo['id']);
        $user->setEmail($userInfo['email'] ?? '');
        $user->setName($userInfo['name'] ?? $userInfo['given_name'] ?? '');
        $user->setProvider('oauth2');
        $user->setProviderId($userInfo['sub'] ?? $userInfo['id']);
        
        return $user;
    }

    /**
     * Verify 2FA code
     */
    public function authenticateWith2FA(string $code, string $secret): bool
    {
        $totp = new TOTPGenerator();
        return $totp->verify($code, $secret);
    }

    /**
     * Generate 2FA secret
     */
    public function generate2FASecret(): string
    {
        $totp = new TOTPGenerator();
        return $totp->generateSecret();
    }

    /**
     * Generate 2FA QR code URL
     */
    public function generate2FAQRCode(string $secret, string $email, string $issuer = 'CloverFramework'): string
    {
        $totp = new TOTPGenerator();
        return $totp->getQRCodeUrl($email, $secret, $issuer);
    }
}
