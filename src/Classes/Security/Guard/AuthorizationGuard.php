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

/**
 * Authorization guard
 */
class AuthorizationGuard
{
    private $rbacManager;

    public function __construct(RBACManager $rbacManager)
    {
        $this->rbacManager = $rbacManager;
    }

    /**
     * Check permission
     */
    public function hasPermission(AuthenticatedUser $user, string $permission): bool
    {
        if (!$user || !$user->isActive()) {
            return false;
        }

        return $this->rbacManager->hasPermission($user, $permission);
    }

    /**
     * Check role
     */
    public function hasRole(AuthenticatedUser $user, string $role): bool
    {
        if (!$user || !$user->isActive()) {
            return false;
        }

        return $this->rbacManager->hasRole($user, $role);
    }

    /**
     * Return true if user has any of the given permissions
     */
    public function hasAnyPermission(AuthenticatedUser $user, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($user, $permission)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Return true only if user has all given permissions
     */
    public function hasAllPermissions(AuthenticatedUser $user, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($user, $permission)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Return true if user has any of the given roles
     */
    public function hasAnyRole(AuthenticatedUser $user, array $roles): bool
    {
        foreach ($roles as $role) {
            if ($this->hasRole($user, $role)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Return true only if user has all given roles
     */
    public function hasAllRoles(AuthenticatedUser $user, array $roles): bool
    {
        foreach ($roles as $role) {
            if (!$this->hasRole($user, $role)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Check admin role
     */
    public function isAdmin(AuthenticatedUser $user): bool
    {
        return $this->hasRole($user, 'admin');
    }

    /**
     * Check user role
     */
    public function isUser(AuthenticatedUser $user): bool
    {
        return $this->hasRole($user, 'user');
    }

    /**
     * Check guest role
     */
    public function isGuest(AuthenticatedUser $user): bool
    {
        return $this->hasRole($user, 'guest');
    }

    /**
     * Check resource access permission
     */
    public function canAccessResource(AuthenticatedUser $user, string $resource, string $action): bool
    {
        $permission = $resource . ':' . $action;
        return $this->hasPermission($user, $permission);
    }

    /**
     * Check if resource belongs to the user or admin
     */
    public function canAccessOwnResource(AuthenticatedUser $user, string $resourceId): bool
    {
        return $user->getId() === $resourceId || $this->isAdmin($user);
    }

    /**
     * Permission check with additional condition
     */
    public function hasPermissionWithCondition(AuthenticatedUser $user, string $permission, callable $condition): bool
    {
        if (!$this->hasPermission($user, $permission)) {
            return false;
        }

        return $condition($user);
    }

    /**
     * Return authorization result for permission
     */
    public function authorize(AuthenticatedUser $user, string $permission): AuthorizationResult
    {
        if ($this->hasPermission($user, $permission)) {
            return AuthorizationResult::allow('Permission granted');
        }

        return AuthorizationResult::deny('Permission denied');
    }

    /**
     * Return authorization result for role
     */
    public function authorizeByRole(AuthenticatedUser $user, string $role): AuthorizationResult
    {
        if ($this->hasRole($user, $role)) {
            return AuthorizationResult::allow('Role access granted');
        }

        return AuthorizationResult::deny('Role access denied');
    }

    /**
     * Return authorization result for resource access
     */
    public function authorizeResourceAccess(AuthenticatedUser $user, string $resource, string $action): AuthorizationResult
    {
        if ($this->canAccessResource($user, $resource, $action)) {
            return AuthorizationResult::allow('Resource access granted');
        }

        return AuthorizationResult::deny('Resource access denied');
    }
}

/**
 * Authorization result class
 */
class AuthorizationResult
{
    private $allowed;
    private $message;
    private $code;
    private $data;

    public function __construct(bool $allowed, string $message = '', int $code = 0, array $data = [])
    {
        $this->allowed = $allowed;
        $this->message = $message;
        $this->code = $code;
        $this->data = $data;
    }

    public function isAllowed(): bool
    {
        return $this->allowed;
    }

    public function isDenied(): bool
    {
        return !$this->allowed;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getCode(): int
    {
        return $this->code;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public static function allow(string $message = 'Access granted'): self
    {
        return new self(true, $message);
    }

    public static function deny(string $message = 'Access denied'): self
    {
        return new self(false, $message);
    }

    public function toArray(): array
    {
        return [
            'allowed' => $this->allowed,
            'message' => $this->message,
            'code' => $this->code,
            'data' => $this->data
        ];
    }
}

