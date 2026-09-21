<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Security\Guard;

/**
 * Role class
 */
class Role
{
    private $id;
    private $name;
    private $displayName;
    private $description;
    private $permissions = [];
    private $isActive = true;
    private $createdAt;
    private $updatedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    /**
     * ID
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(string $id): self
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Name
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Display name
     */
    public function getDisplayName(): ?string
    {
        return $this->displayName;
    }

    public function setDisplayName(string $displayName): self
    {
        $this->displayName = $displayName;
        return $this;
    }

    /**
     * Description
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Permissions
     */
    public function getPermissions(): array
    {
        return $this->permissions;
    }

    public function setPermissions(array $permissions): self
    {
        $this->permissions = $permissions;
        return $this;
    }

    public function addPermission(Permission $permission): self
    {
        if (!$this->hasPermission($permission)) {
            $this->permissions[] = $permission;
        }
        return $this;
    }

    public function removePermission(Permission $permission): self
    {
        $this->permissions = array_filter($this->permissions, function($p) use ($permission) {
            return $p->getId() !== $permission->getId();
        });
        return $this;
    }

    public function hasPermission(Permission $permission): bool
    {
        foreach ($this->permissions as $p) {
            if ($p->getId() === $permission->getId()) {
                return true;
            }
        }
        return false;
    }

    public function hasPermissionByName(string $permissionName): bool
    {
        foreach ($this->permissions as $permission) {
            if ($permission->getName() === $permissionName) {
                return true;
            }
        }
        return false;
    }

    /**
     * Active flag
     */
    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setActive(bool $active): self
    {
        $this->isActive = $active;
        return $this;
    }

    /**
     * Created at
     */
    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * Updated at
     */
    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTime $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'display_name' => $this->displayName,
            'description' => $this->description,
            'permissions' => array_map(fn($p) => $p->toArray(), $this->permissions),
            'is_active' => $this->isActive,
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s')
        ];
    }
}

