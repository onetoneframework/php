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
 * RBAC (Role-Based Access Control) manager
 */
class RBACManager
{
    private $roles = [];
    private $permissions = [];
    private $rolePermissions = [];
    private $userRoles = [];

    public function __construct()
    {
        $this->initializeDefaultRoles();
        $this->initializeDefaultPermissions();
    }

    /**
     * Initialize default roles
     */
    private function initializeDefaultRoles(): void
    {
        
        $adminRole = new Role();
        $adminRole->setId('admin')
            ->setName('admin')
            ->setDisplayName('Administrator')
            ->setDescription('Full system access');
        $this->addRole($adminRole);

        
        $userRole = new Role();
        $userRole->setId('user')
            ->setName('user')
            ->setDisplayName('User')
            ->setDescription('Basic user access');
        $this->addRole($userRole);

        
        $guestRole = new Role();
        $guestRole->setId('guest')
            ->setName('guest')
            ->setDisplayName('Guest')
            ->setDescription('Limited guest access');
        $this->addRole($guestRole);
    }

    /**
     * Initialize default permissions
     */
    private function initializeDefaultPermissions(): void
    {
        $permissions = [
            
            ['user:create', 'Create User', 'Create new users'],
            ['user:read', 'Read User', 'View user information'],
            ['user:update', 'Update User', 'Modify user information'],
            ['user:delete', 'Delete User', 'Delete users'],
            
            
            ['role:create', 'Create Role', 'Create new roles'],
            ['role:read', 'Read Role', 'View role information'],
            ['role:update', 'Update Role', 'Modify role information'],
            ['role:delete', 'Delete Role', 'Delete roles'],
            
            
            ['permission:create', 'Create Permission', 'Create new permissions'],
            ['permission:read', 'Read Permission', 'View permission information'],
            ['permission:update', 'Update Permission', 'Modify permission information'],
            ['permission:delete', 'Delete Permission', 'Delete permissions'],
            
            
            ['system:admin', 'System Admin', 'Full system administration'],
            ['system:config', 'System Config', 'System configuration access'],
            ['system:logs', 'System Logs', 'View system logs'],
            
            
            ['api:read', 'API Read', 'Read API access'],
            ['api:write', 'API Write', 'Write API access'],
            ['api:admin', 'API Admin', 'API administration'],
        ];

        foreach ($permissions as $perm) {
            $permission = new Permission();
            $permission->setId($perm[0])
                ->setName($perm[0])
                ->setDisplayName($perm[1])
                ->setDescription($perm[2]);
            
            
            $parts = explode(':', $perm[0]);
            if (count($parts) === 2) {
                $permission->setResource($parts[0])->setAction($parts[1]);
            }
            
            $this->addPermission($permission);
        }

        
        $this->assignPermissionsToRoles();
    }

    /**
     * Assign permissions to roles
     */
    private function assignPermissionsToRoles(): void
    {
        
        $this->rolePermissions['admin'] = array_keys($this->permissions);

        
        $this->rolePermissions['user'] = [
            'user:read',
            'api:read',
            'api:write'
        ];

        
        $this->rolePermissions['guest'] = [
            'api:read'
        ];
    }

    /**
     * Add a role
     */
    public function addRole(Role $role): self
    {
        $this->roles[$role->getId()] = $role;
        return $this;
    }

    /**
     * Add a permission
     */
    public function addPermission(Permission $permission): self
    {
        $this->permissions[$permission->getId()] = $permission;
        return $this;
    }

    /**
     * Assign a permission to a role
     */
    public function assignPermissionToRole(string $roleId, string $permissionId): self
    {
        if (!isset($this->rolePermissions[$roleId])) {
            $this->rolePermissions[$roleId] = [];
        }
        
        if (!in_array($permissionId, $this->rolePermissions[$roleId])) {
            $this->rolePermissions[$roleId][] = $permissionId;
        }
        
        return $this;
    }

    /**
     * Remove a permission from a role
     */
    public function removePermissionFromRole(string $roleId, string $permissionId): self
    {
        if (isset($this->rolePermissions[$roleId])) {
            $this->rolePermissions[$roleId] = array_filter(
                $this->rolePermissions[$roleId],
                fn($p) => $p !== $permissionId
            );
        }
        
        return $this;
    }

    /**
     * Assign role to user
     */
    public function assignRoleToUser(string $userId, string $roleId): self
    {
        if (!isset($this->userRoles[$userId])) {
            $this->userRoles[$userId] = [];
        }
        
        if (!in_array($roleId, $this->userRoles[$userId])) {
            $this->userRoles[$userId][] = $roleId;
        }
        
        return $this;
    }

    /**
     * Remove role from user
     */
    public function removeRoleFromUser(string $userId, string $roleId): self
    {
        if (isset($this->userRoles[$userId])) {
            $this->userRoles[$userId] = array_filter(
                $this->userRoles[$userId],
                fn($r) => $r !== $roleId
            );
        }
        
        return $this;
    }

    /**
     * Check if user has permission
     */
    public function hasPermission(AuthenticatedUser $user, string $permission): bool
    {
        
        $userRoleIds = $this->getUserRoles($user->getId());
        
        
        foreach ($userRoleIds as $roleId) {
            if ($this->roleHasPermission($roleId, $permission)) {
                return true;
            }
        }
        
        
        return $user->hasPermission($permission);
    }

    /**
     * Check if user has role
     */
    public function hasRole(AuthenticatedUser $user, string $role): bool
    {
        return in_array($role, $this->getUserRoles($user->getId()));
    }

    /**
     * Check if role has permission
     */
    public function roleHasPermission(string $roleId, string $permissionId): bool
    {
        return isset($this->rolePermissions[$roleId]) && 
               in_array($permissionId, $this->rolePermissions[$roleId]);
    }

    /**
     * Get all roles for a user
     */
    public function getUserRoles(string $userId): array
    {
        return $this->userRoles[$userId] ?? [];
    }

    /**
     * Get all permissions for a user
     */
    public function getUserPermissions(string $userId): array
    {
        $permissions = [];
        $userRoleIds = $this->getUserRoles($userId);
        
        foreach ($userRoleIds as $roleId) {
            if (isset($this->rolePermissions[$roleId])) {
                $permissions = array_merge($permissions, $this->rolePermissions[$roleId]);
            }
        }
        
        return array_unique($permissions);
    }

    /**
     * Get a role by id
     */
    public function getRole(string $roleId): ?Role
    {
        return $this->roles[$roleId] ?? null;
    }

    /**
     * Get all roles
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    /**
     * Get a permission by id
     */
    public function getPermission(string $permissionId): ?Permission
    {
        return $this->permissions[$permissionId] ?? null;
    }

    /**
     * Get all permissions
     */
    public function getPermissions(): array
    {
        return $this->permissions;
    }

    /**
     * Get permissions for a role
     */
    public function getRolePermissions(string $roleId): array
    {
        return $this->rolePermissions[$roleId] ?? [];
    }

    /**
     * Shortcut for permission check
     */
    public function can(AuthenticatedUser $user, string $permission): bool
    {
        return $this->hasPermission($user, $permission);
    }

    /**
     * Shortcut for role check
     */
    public function is(AuthenticatedUser $user, string $role): bool
    {
        return $this->hasRole($user, $role);
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
}

