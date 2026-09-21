<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Framework\Livewire;

use Clover\Framework\Livewire\Component;
use Clover\Framework\Livewire\ComponentRegistry;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ComponentRegistryTest extends TestCase
{
	public static function aliasProvider(): array
	{
		return [
			'pascal case' => ['UserProfile', 'user-profile'],
			'camel case' => ['userProfile', 'user-profile'],
			'underscore' => ['USER_PROFILE', 'user-profile'],
			'spaces' => ['user profile', 'user-profile'],
			'namespace separators' => ['Admin\\UserProfile', 'admin-user-profile'],
		];
	}

	#[DataProvider('aliasProvider')]
	public function testNormalizeCanonicalisesAliases(string $input, string $expected): void
	{
		$this->assertSame($expected, (new ComponentRegistry())->normalize($input));
	}

	public function testRegisterAndResolveAreCaseInsensitiveAndCanonical(): void
	{
		$registry = new ComponentRegistry();
		$registry->register('User_Profile', RegistryFixtureComponent::class);

		$this->assertSame(RegistryFixtureComponent::class, $registry->resolve('user-profile'));
		$this->assertSame(RegistryFixtureComponent::class, $registry->resolve('USER PROFILE'));
		$this->assertSame(['user-profile' => RegistryFixtureComponent::class], $registry->all());
	}

	public function testResolveFallsBackToConcreteComponentClass(): void
	{
		$registry = new ComponentRegistry();

		$this->assertSame(RegistryFixtureComponent::class, $registry->resolve(RegistryFixtureComponent::class));
		$this->assertSame(
			RegistryFixtureComponent::class,
			$registry->all()['registry-fixture-component']
		);
	}

	public function testAliasFromClassUsesShortClassName(): void
	{
		$this->assertSame(
			'registry-fixture-component',
			(new ComponentRegistry())->aliasFromClass(RegistryFixtureComponent::class)
		);
	}

	public function testUnknownAliasThrowsUsefulException(): void
	{
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Livewire component "missing" is not registered.');

		(new ComponentRegistry())->resolve('missing');
	}
}

final class RegistryFixtureComponent extends Component
{
	public function render(): string
	{
		return '<div>registry</div>';
	}
}
