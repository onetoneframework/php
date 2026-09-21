<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Security\Auth;

use Clover\Classes\Security\Auth\AuthenticatedUser;
use Clover\Classes\Security\Auth\TokenManager;
use Clover\Classes\Token\JWTToken;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class TokenManagerTest extends TestCase
{
	private function createUser(): AuthenticatedUser
	{
		$user = new AuthenticatedUser();
		$user->setId('user-123');
		$user->setEmail('user@example.com');
		$user->setName('Example User');
		$user->setRoles(['member']);
		$user->setPermissions(['profile.read']);

		return $user;
	}

	public function testCreatesAndVerifiesAccessToken(): void
	{
		$manager = new TokenManager('test-secret');
		$token = $manager->createToken($this->createUser());

		$payload = $manager->verifyToken($token);

		$this->assertNotNull($payload);
		$this->assertSame('user-123', $payload['sub']);
		$this->assertSame('access', $payload['type']);
	}

	public function testRejectsTokenForDifferentIssuerOrAudience(): void
	{
		$issuerManager = (new TokenManager('test-secret'))
			->setIssuer('issuer-a')
			->setAudience('audience-a');
		$token = $issuerManager->createToken($this->createUser());
		$verificationManager = (new TokenManager('test-secret'))
			->setIssuer('issuer-b')
			->setAudience('audience-b');

		$this->assertNull($verificationManager->verifyToken($token));
	}

	public function testUsesTheConfiguredSigningAlgorithm(): void
	{
		$manager = (new TokenManager('test-secret'))->setAlgorithm('HS512');
		$token = $manager->createToken($this->createUser());

		$this->assertTrue(JWTToken::isValid($token, 'test-secret', 'HS512'));
		$this->assertNotNull($manager->verifyToken($token));
	}

	public function testRevokedTokenCannotBeVerified(): void
	{
		$manager = new TokenManager('test-secret');
		$token = $manager->createToken($this->createUser());

		$manager->revokeToken($token);

		$this->assertTrue($manager->isTokenRevoked($token));
		$this->assertNull($manager->verifyToken($token));
	}

	public function testRejectsUnsupportedSigningAlgorithm(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Unsupported JWT algorithm.');

		(new TokenManager('test-secret'))->setAlgorithm('none');
	}
}
