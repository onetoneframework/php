<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Security\Guard;

use Clover\Classes\Security\Auth\AuthenticatedUser;
use Clover\Classes\Security\Auth\AuthenticationResult;
use Clover\Classes\Hash\PasswordHash;

/**
 * Authentication guard interface
 */
interface AuthenticationGuard
{
    public function authenticate(array $credentials): AuthenticationResult;
    public function supports(array $credentials): bool;
}

/**
 * Database authentication guard implementation
 */
class DatabaseAuthenticationGuard implements AuthenticationGuard
{
    private $connection;
    private $userTable;
    private $usernameField;
    private $passwordField;

    public function __construct($connection, string $userTable = 'users', string $usernameField = 'email', string $passwordField = 'password')
    {
        $this->connection = $connection;
        $this->userTable = $userTable;
        $this->usernameField = $usernameField;
        $this->passwordField = $passwordField;
    }

    public function authenticate(array $credentials): AuthenticationResult
    {
        $username = $credentials['username'] ?? $credentials['email'] ?? null;
        $password = $credentials['password'] ?? null;

        if (!$username || !$password) {
            return AuthenticationResult::failure('Username and password are required');
        }

        try {
            
            $stmt = $this->connection->prepare("SELECT * FROM {$this->userTable} WHERE {$this->usernameField} = ? AND is_active = 1");
            $stmt->execute([$username]);
            $userData = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$userData) {
                return AuthenticationResult::failure('Invalid credentials');
            }

            
            if (!PasswordHash::verify($password, $userData[$this->passwordField])) {
                return AuthenticationResult::failure('Invalid credentials');
            }

            
            $user = new AuthenticatedUser();
            $user->setId($userData['id'])
                ->setEmail($userData['email'] ?? '')
                ->setName($userData['name'] ?? '')
                ->setActive($userData['is_active'] ?? true);

            if (isset($userData['created_at'])) {
                $user->setCreatedAt(new \DateTime($userData['created_at']));
            }
            if (isset($userData['updated_at'])) {
                $user->setUpdatedAt(new \DateTime($userData['updated_at']));
            }

            return AuthenticationResult::success($user, 'Authentication successful');

        } catch (\Exception $e) {
            return AuthenticationResult::failure('Authentication failed: ' . $e->getMessage());
        }
    }

    public function supports(array $credentials): bool
    {
        return isset($credentials['username']) || isset($credentials['email']);
    }
}

/**
 * LDAP authentication guard implementation
 */
class LDAPAuthenticationGuard implements AuthenticationGuard
{
    private $ldapHost;
    private $ldapPort;
    private $ldapBaseDn;
    private $ldapBindDn;
    private $ldapBindPassword;

    public function __construct(string $ldapHost, int $ldapPort = 389, string $ldapBaseDn = '', string $ldapBindDn = '', string $ldapBindPassword = '')
    {
        $this->ldapHost = $ldapHost;
        $this->ldapPort = $ldapPort;
        $this->ldapBaseDn = $ldapBaseDn;
        $this->ldapBindDn = $ldapBindDn;
        $this->ldapBindPassword = $ldapBindPassword;
    }

    public function authenticate(array $credentials): AuthenticationResult
    {
        $username = $credentials['username'] ?? null;
        $password = $credentials['password'] ?? null;

        if (!$username || !$password) {
            return AuthenticationResult::failure('Username and password are required');
        }

        try {
            $ldap = ldap_connect($this->ldapHost, $this->ldapPort);
            if (!$ldap) {
                return AuthenticationResult::failure('LDAP connection failed');
            }

            ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
            ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);

            
            $bindDn = "uid={$username},{$this->ldapBaseDn}";
            $bind = ldap_bind($ldap, $bindDn, $password);

            if (!$bind) {
                ldap_close($ldap);
                return AuthenticationResult::failure('Invalid LDAP credentials');
            }

            
            $search = ldap_search($ldap, $this->ldapBaseDn, "uid={$username}");
            $entries = ldap_get_entries($ldap, $search);

            ldap_close($ldap);

            if ($entries['count'] === 0) {
                return AuthenticationResult::failure('User not found in LDAP');
            }

            $userData = $entries[0];

            
            $user = new AuthenticatedUser();
            $user->setId($userData['uid'][0] ?? $username)
                ->setEmail($userData['mail'][0] ?? '')
                ->setName($userData['cn'][0] ?? $userData['displayname'][0] ?? $username)
                ->setProvider('ldap');

            return AuthenticationResult::success($user, 'LDAP authentication successful');

        } catch (\Exception $e) {
            return AuthenticationResult::failure('LDAP authentication failed: ' . $e->getMessage());
        }
    }

    public function supports(array $credentials): bool
    {
        return isset($credentials['username']) && isset($credentials['password']);
    }
}

/**
 * API key authentication guard implementation
 */
class ApiKeyAuthenticationGuard implements AuthenticationGuard
{
    private $connection;
    private $apiKeyTable;

    public function __construct($connection, string $apiKeyTable = 'api_keys')
    {
        $this->connection = $connection;
        $this->apiKeyTable = $apiKeyTable;
    }

    public function authenticate(array $credentials): AuthenticationResult
    {
        $apiKey = $credentials['api_key'] ?? null;

        if (!$apiKey) {
            return AuthenticationResult::failure('API key is required');
        }

        try {
            
            $stmt = $this->connection->prepare("SELECT ak.*, u.* FROM {$this->apiKeyTable} ak 
                JOIN users u ON ak.user_id = u.id 
                WHERE ak.key_hash = ? AND ak.is_active = 1 AND ak.expires_at > NOW()");
            
            $keyHash = hash('sha256', $apiKey);
            $stmt->execute([$keyHash]);
            $data = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$data) {
                return AuthenticationResult::failure('Invalid or expired API key');
            }

            
            $user = new AuthenticatedUser();
            $user->setId($data['user_id'])
                ->setEmail($data['email'] ?? '')
                ->setName($data['name'] ?? '')
                ->setProvider('api_key');

            return AuthenticationResult::success($user, 'API key authentication successful');

        } catch (\Exception $e) {
            return AuthenticationResult::failure('API key authentication failed: ' . $e->getMessage());
        }
    }

    public function supports(array $credentials): bool
    {
        return isset($credentials['api_key']);
    }
}

