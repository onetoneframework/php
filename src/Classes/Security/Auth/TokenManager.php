<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Security\Auth;

use Clover\Classes\Token\JWTToken;
use InvalidArgumentException;
use function hash;
use function in_array;
use function is_array;
use function is_numeric;

/**
 * Token Manager - JWT token handling
 */
class TokenManager
{
    private const DEFAULT_ACCESS_TOKEN_EXPIRATION_SECONDS = 3600;
    private const DEFAULT_REFRESH_TOKEN_EXPIRATION_SECONDS = 604800;
    private const TOKEN_HASH_ALGORITHM = 'sha256';
    private const SUPPORTED_ALGORITHMS = ['HS256', 'HS384', 'HS512'];

    private string $secretKey;
    private string $algorithm = 'HS256';
    private int $expirationTime = self::DEFAULT_ACCESS_TOKEN_EXPIRATION_SECONDS;
    private int $refreshExpirationTime = self::DEFAULT_REFRESH_TOKEN_EXPIRATION_SECONDS;
    private string $issuer = 'CloverFramework';
    private string $audience = 'CloverFramework';
    /** @var array<string, int> */
    private array $revokedTokens = [];

    public function __construct(string $secretKey)
    {
        $this->secretKey = $secretKey;
    }

    /**
     * Create access token
     */
    public function createToken(AuthenticatedUser $user): string
    {
        $payload = [
            'iss' => $this->issuer,
            'aud' => $this->audience,
            'iat' => time(),
            'exp' => time() + $this->expirationTime,
            'sub' => $user->getId(),
            'email' => $user->getEmail(),
            'name' => $user->getName(),
            'roles' => $user->getRoles(),
            'permissions' => $user->getPermissions(),
            'type' => 'access'
        ];

        return $this->generateJWT($payload);
    }

    /**
     * Create refresh token
     */
    public function createRefreshToken(AuthenticatedUser $user): string
    {
        $payload = [
            'iss' => $this->issuer,
            'aud' => $this->audience,
            'iat' => time(),
            'exp' => time() + $this->refreshExpirationTime,
            'sub' => $user->getId(),
            'type' => 'refresh'
        ];

        return $this->generateJWT($payload);
    }

    /**
     * Generate JWT token
     */
    private function generateJWT(array $payload): string
    {
        return JWTToken::generate($payload, $this->secretKey, $this->algorithm);
    }

    /**
     * Verify token
     */
    public function verifyToken(string $token): ?array
    {
        if (!JWTToken::isValid($token, $this->secretKey, $this->algorithm)) {
            return null;
        }

        $decoded = JWTToken::decode($token, $this->secretKey, $this->algorithm);
        if ($decoded === false || !isset($decoded['payload']) || !is_array($decoded['payload'])) {
            return null;
        }

        $payload = $decoded['payload'];
        if (($payload['iss'] ?? null) !== $this->issuer || ($payload['aud'] ?? null) !== $this->audience) {
            return null;
        }

        if ($this->isTokenRevoked($token)) {
            return null;
        }

        return $payload;
    }

    /**
     * Extract user information from access token
     */
    public function getUserFromToken(string $token): ?AuthenticatedUser
    {
        $payload = $this->verifyToken($token);
        
        if (!$payload || ($payload['type'] ?? '') !== 'access') {
            return null;
        }

        $user = new AuthenticatedUser();
        $user->setId($payload['sub'] ?? null);
        $user->setEmail($payload['email'] ?? null);
        $user->setName($payload['name'] ?? null);
        $user->setRoles($payload['roles'] ?? []);
        $user->setPermissions($payload['permissions'] ?? []);

        return $user;
    }

    /**
     * Create new access token from refresh token
     */
    public function refreshAccessToken(string $refreshToken): ?string
    {
        $payload = $this->verifyToken($refreshToken);
        
        if (!$payload || ($payload['type'] ?? '') !== 'refresh') {
            return null;
        }

        
        
        $user = new AuthenticatedUser();
        $user->setId($payload['sub']);

        return $this->createToken($user);
    }

    /**
     * Revoke token (add to blacklist)
     */
    public function revokeToken(?string $token = null): void
    {
        if ($token === null || $token === '') {
            return;
        }

        $payload = $this->verifyToken($token);
        if ($payload === null || !isset($payload['exp']) || !is_numeric($payload['exp'])) {
            return;
        }

        $this->revokedTokens[hash(self::TOKEN_HASH_ALGORITHM, $token)] = (int) $payload['exp'];
    }

    /**
     * Check if token is revoked
     */
    public function isTokenRevoked(string $token): bool
    {
        $tokenHash = hash(self::TOKEN_HASH_ALGORITHM, $token);
        $expiresAt = $this->revokedTokens[$tokenHash] ?? null;
        if ($expiresAt === null) {
            return false;
        }

        if ($expiresAt <= time()) {
            unset($this->revokedTokens[$tokenHash]);

            return false;
        }

        return true;
    }

    /**
     * Set token expiration time
     */
    public function setExpirationTime(int $time): self
    {
        $this->expirationTime = $time;
        return $this;
    }

    /**
     * Set refresh token expiration time
     */
    public function setRefreshExpirationTime(int $time): self
    {
        $this->refreshExpirationTime = $time;
        return $this;
    }

    /**
     * Set algorithm
     */
    public function setAlgorithm(string $algorithm): self
    {
        if (!in_array($algorithm, self::SUPPORTED_ALGORITHMS, true)) {
            throw new InvalidArgumentException('Unsupported JWT algorithm.');
        }

        $this->algorithm = $algorithm;
        return $this;
    }

    /**
     * Set issuer
     */
    public function setIssuer(string $issuer): self
    {
        $this->issuer = $issuer;
        return $this;
    }

    /**
     * Set audience
     */
    public function setAudience(string $audience): self
    {
        $this->audience = $audience;
        return $this;
    }
}

/**
 * Base64 URL encode helper
 */
function base64url_encode(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

/**
 * Base64 URL decode helper
 */
function base64url_decode(string $data): string
{
    return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
}
