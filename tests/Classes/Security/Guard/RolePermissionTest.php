<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Security\Guard;

use Clover\Classes\Security\Guard\Permission;
use Clover\Classes\Security\Guard\Role;
use DateTime;
use PHPUnit\Framework\TestCase;

final class RolePermissionTest extends TestCase
{
	public function testPermissionBuildsPermissionStringAndSerializesFields(): void
	{
		$permission = (new Permission())
			->setId('perm-1')
			->setName('posts.update')
			->setDisplayName('Update posts')
			->setDescription('Allows updating posts')
			->setResource('posts')
			->setAction('update')
			->setActive(false)
			->setCreatedAt(new DateTime('2025-01-01 00:00:00'))
			->setUpdatedAt(new DateTime('2026-01-01 00:00:00'));

		$this->assertSame('posts:update', $permission->getPermissionString());
		$this->assertSame([
			'id' => 'perm-1',
			'name' => 'posts.update',
			'display_name' => 'Update posts',
			'description' => 'Allows updating posts',
			'resource' => 'posts',
			'action' => 'update',
			'permission_string' => 'posts:update',
			'is_active' => false,
			'created_at' => '2025-01-01 00:00:00',
			'updated_at' => '2026-01-01 00:00:00',
		], $permission->toArray());
	}

	public function testRoleAddsPermissionsByIdentityAndSupportsLookupByName(): void
	{
		$read = (new Permission())->setId('read')->setName('posts.read');
		$sameReadIdentity = (new Permission())->setId('read')->setName('duplicate.name');
		$write = (new Permission())->setId('write')->setName('posts.write');
		$role = (new Role())->setId('editor')->setName('editor');

		$role->addPermission($read)->addPermission($sameReadIdentity)->addPermission($write);

		$this->assertCount(2, $role->getPermissions());
		$this->assertTrue($role->hasPermission($sameReadIdentity));
		$this->assertTrue($role->hasPermissionByName('posts.write'));
		$this->assertFalse($role->hasPermissionByName('posts.delete'));

		$role->removePermission($read);

		$this->assertFalse($role->hasPermission($read));
		$this->assertTrue($role->hasPermission($write));
	}

	public function testRoleSerializationIncludesSerializedPermissions(): void
	{
		$permission = (new Permission())
			->setId('view')
			->setName('reports.view')
			->setResource('reports')
			->setAction('view');
		$role = (new Role())
			->setId('analyst')
			->setName('analyst')
			->setDisplayName('Analyst')
			->setDescription('Reporting role')
			->setActive(true)
			->setCreatedAt(new DateTime('2025-05-01 00:00:00'))
			->setUpdatedAt(new DateTime('2025-06-01 00:00:00'))
			->addPermission($permission);

		$array = $role->toArray();

		$this->assertSame('analyst', $array['id']);
		$this->assertSame('Analyst', $array['display_name']);
		$this->assertTrue($array['is_active']);
		$this->assertSame('reports:view', $array['permissions'][0]['permission_string']);
		$this->assertSame('2025-05-01 00:00:00', $array['created_at']);
		$this->assertSame('2025-06-01 00:00:00', $array['updated_at']);
	}
}
