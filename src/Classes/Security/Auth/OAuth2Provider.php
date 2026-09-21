<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Security\Auth;

use Clover\Classes\ClientURL;
use Clover\Classes\Data\ArrayObject;
use Clover\Classes\Data\JSONHandler;

/**
 * OAuth2 Provider - OAuth2/OpenID Connect authentication provider
 */
class OAuth2Provider
{
    private $clientId;
    private $clientSecret;
    private $redirectUri;
    private $authorizationEndpoint;
    private $tokenEndpoint;
    private $userInfoEndpoint;
    private $scope;
    private $state;
    private $nonce;

    public function __construct(array $config)
    {
        $this->clientId = $config['client_id'] ?? null;
        $this->clientSecret = $config['client_secret'] ?? null;
        $this->redirectUri = $config['redirect_uri'] ?? null;
        $this->authorizationEndpoint = $config['authorization_endpoint'] ?? null;
        $this->tokenEndpoint = $config['token_endpoint'] ?? null;
        $this->userInfoEndpoint = $config['userinfo_endpoint'] ?? null;
        $this->scope = $config['scope'] ?? 'openid profile email';
        $this->state = $this->generateState();
        $this->nonce = $this->generateNonce();
    }

    /**
     * Build authorization URL
     */
    public function getAuthorizationUrl(array $additionalParams = []): string
    {
        $params = array_merge([
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'scope' => $this->scope,
            'state' => $this->state,
            'nonce' => $this->nonce
        ], $additionalParams);

        return $this->authorizationEndpoint . '?' . http_build_query($params);
    }

    /**
     * Exchange authorization code for access token
     * 
     * @return ArrayObject
     */
    public function exchangeCodeForToken(string $code, ?string $state = null): ArrayObject
    {
        if ($state && $state !== $this->state) {
            throw new \Exception('Invalid state parameter');
        }

        $params = [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->redirectUri,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret
        ];

        $client = new ClientURL($this->tokenEndpoint);
        $client->option->setPostMethod()
            ->setContentTypeFormUrlEncoded()
            ->setPostField(http_build_query($params));

        $response = $client->execute();
        $data = JSONHandler::decode($response);

        if (isset($data['error'])) {
            throw new \Exception('Token exchange failed: ' . $data['error_description']);
        }

        return $data;
    }

    /**
     * Retrieve user info using access token
     * 
     * @return ArrayObject
     */
    public function getUserInfo(string $accessToken): ArrayObject
    {
        $client = new ClientURL($this->userInfoEndpoint);
        $client->option->setHeader('Authorization', 'Bearer ' . $accessToken);

        $response = $client->execute();
        $data = JSONHandler::decode($response);

        if (isset($data['error'])) {
            throw new \Exception('Failed to get user info: ' . $data['error_description']);
        }

        return $data;
    }

    /**
     * Refresh access token using refresh token
     * 
     * @return ArrayObject
     */
    public function refreshToken(string $refreshToken): ArrayObject
    {
        $params = [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret
        ];

        $client = new ClientURL($this->tokenEndpoint);
        $client->option->setPostMethod()
            ->setContentTypeFormUrlEncoded()
            ->setPostField(http_build_query($params));

        $response = $client->execute();
        $data = JSONHandler::decode($response);

        if (isset($data['error'])) {
            throw new \Exception('Token refresh failed: ' . $data['error_description']);
        }

        return $data;
    }

    /**
     * Verify ID token (OpenID Connect)
     * 
     * @return ArrayObject
     */
    public function verifyIdToken(string $idToken, ?string $expectedNonce = null): ArrayObject
    {
        
        $parts = explode('.', $idToken);
        if (count($parts) !== 3) {
            throw new \Exception('Invalid ID token format');
        }

        $header = JSONHandler::decode(base64_decode($parts[0]));
        $payload = JSONHandler::decode(base64_decode($parts[1]));

        
        if ($payload['iss'] !== $this->authorizationEndpoint) {
            throw new \Exception('Invalid issuer');
        }

        if ($payload['aud'] !== $this->clientId) {
            throw new \Exception('Invalid audience');
        }

        if (isset($payload['exp']) && $payload['exp'] < time()) {
            throw new \Exception('Token expired');
        }

        if (isset($payload['iat']) && $payload['iat'] > time() + 60) {
            throw new \Exception('Token issued in the future');
        }

        if ($expectedNonce && $payload['nonce'] !== $expectedNonce) {
            throw new \Exception('Invalid nonce');
        }

        return $payload;
    }

    /**
     * Generate state parameter
     */
    private function generateState(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Generate nonce parameter
     */
    private function generateNonce(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * Get current state
     */
    public function getState(): string
    {
        return $this->state;
    }

    /**
     * Get current nonce
     */
    public function getNonce(): string
    {
        return $this->nonce;
    }
}
