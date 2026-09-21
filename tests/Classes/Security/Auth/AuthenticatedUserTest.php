<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Security\Auth;

use Clover\Classes\Security\Auth\AuthenticatedUser;
use Clover\Classes\Security\Auth\AuthenticationResult;
use DateTime;
use PHPUnit\Framework\TestCase;

final class AuthenticatedUserTest extends TestCase
{
	public function testRolesPermissionsAndAttributesAreManagedWithoutDuplicates(): void
	{
		$user = new AuthenticatedUser();

		$user->addRole('admin')->addRole('admin')->addRole('editor');
		$user->addPermission('posts.read')->addPermission('posts.read')->addPermission('posts.write');
		$user->setAttribute('locale', 'ko-KR');

		$this->assertSame(['admin', 'editor'], $user->getRoles());
		$this->assertTrue($user->hasRole('admin'));
		$this->assertSame(['posts.read', 'posts.write'], $user->getPermissions());
		$this->assertTrue($user->hasPermission('posts.write'));
		$this->assertSame('ko-KR', $user->getAttribute('locale'));
		$this->assertSame('fallback', $user->getAttribute('missing', 'fallback'));

		$user->removeRole('admin')->removePermission('posts.read');

		$this->assertFalse($user->hasRole('admin'));
		$this->assertFalse($user->hasPermission('posts.read'));
	}

	public function testToArrayUsesStableFieldNamesAndFormatsDates(): void
	{
		$user = (new AuthenticatedUser())
			->setId('user-7')
			->setEmail('user@example.test')
			->setName('Example User')
			->setRoles(['admin'])
			->setPermissions(['reports.view'])
			->setProvider('local')
			->setProviderId('provider-7')
			->setActive(false)
			->setLastLoginAt(new DateTime('2026-01-02 03:04:05'))
			->setCreatedAt(new DateTime('2025-01-01 00:00:00'))
			->setUpdatedAt(new DateTime('2026-02-03 04:05:06'))
			->setAttributes(['locale' => 'en']);

		$this->assertSame([
			'id' => 'user-7',
			'email' => 'user@example.test',
			'name' => 'Example User',
			'roles' => ['admin'],
			'permissions' => ['reports.view'],
			'provider' => 'local',
			'provider_id' => 'provider-7',
			'is_active' => false,
			'last_login_at' => '2026-01-02 03:04:05',
			'created_at' => '2025-01-01 00:00:00',
			'updated_at' => '2026-02-03 04:05:06',
			'attributes' => ['locale' => 'en'],
		], $user->toArray());

		$this->assertSame($user->toArray(), json_decode($user->toJson(), true, 512, JSON_THROW_ON_ERROR));
	}

	public function testAuthenticationResultFactoriesPreserveSuccessAndFailureData(): void
	{
		$user = (new AuthenticatedUser())->setId('user-1');
		$success = AuthenticationResult::success($user, 'Signed in');
		$failure = AuthenticationResult::failure('Denied', 403, ['reason' => 'inactive']);

		$this->assertTrue($success->isSuccess());
		$this->assertFalse($success->isFailure());
		$this->assertSame($user, $success->getUser());
		$this->assertSame('Signed in', $success->getMessage());

		$this->assertFalse($failure->isSuccess());
		$this->assertTrue($failure->isFailure());
		$this->assertNull($failure->getUser());
		$this->assertSame(403, $failure->getCode());
		$this->assertSame(['reason' => 'inactive'], $failure->getData());
		$this->assertSame($failure->toArray(), json_decode($failure->toJson(), true, 512, JSON_THROW_ON_ERROR));
	}
}
