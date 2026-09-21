<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Framework\Livewire;

use Clover\Framework\Livewire\Checksum;
use PHPUnit\Framework\TestCase;

final class ChecksumTest extends TestCase
{
	private mixed $previousLivewireKey;
	private mixed $previousAppKey;

	protected function setUp(): void
	{
		$this->previousLivewireKey = $_ENV['LIVEWIRE_KEY'] ?? null;
		$this->previousAppKey = $_ENV['APP_KEY'] ?? null;
		$_ENV['LIVEWIRE_KEY'] = 'test-livewire-secret';
		unset($_ENV['APP_KEY']);
	}

	protected function tearDown(): void
	{
		$this->restoreEnvironmentValue('LIVEWIRE_KEY', $this->previousLivewireKey);
		$this->restoreEnvironmentValue('APP_KEY', $this->previousAppKey);
	}

	public function testSignIsStableAcrossTopLevelDataKeyOrder(): void
	{
		$first = ['name' => 'counter', 'id' => 'wire-1', 'data' => ['count' => 2, 'label' => 'A']];
		$second = ['name' => 'counter', 'id' => 'wire-1', 'data' => ['label' => 'A', 'count' => 2]];

		$this->assertSame(Checksum::sign($first), Checksum::sign($second));
		$this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', Checksum::sign($first));
	}

	public function testVerifyAcceptsMatchingSnapshot(): void
	{
		$snapshot = ['name' => 'counter', 'id' => 'wire-1', 'data' => ['count' => 2]];
		$checksum = Checksum::sign($snapshot);

		$this->assertTrue(Checksum::verify($snapshot, $checksum));
	}

	public function testVerifyRejectsEmptyAndTamperedChecksums(): void
	{
		$snapshot = ['name' => 'counter', 'id' => 'wire-1', 'data' => ['count' => 2]];
		$checksum = Checksum::sign($snapshot);

		$this->assertFalse(Checksum::verify($snapshot, ''));
		$this->assertFalse(Checksum::verify(['name' => 'counter', 'id' => 'wire-1', 'data' => ['count' => 3]], $checksum));
		$this->assertFalse(Checksum::verify(['name' => 'other', 'id' => 'wire-1', 'data' => ['count' => 2]], $checksum));
		$this->assertFalse(Checksum::verify(['name' => 'counter', 'id' => 'wire-2', 'data' => ['count' => 2]], $checksum));
	}

	public function testLivewireKeyTakesPrecedenceOverAppKey(): void
	{
		$snapshot = ['name' => 'counter', 'id' => 'wire-1', 'data' => []];
		$_ENV['APP_KEY'] = 'app-secret';
		$withLivewireKey = Checksum::sign($snapshot);

		unset($_ENV['LIVEWIRE_KEY']);
		$withAppKey = Checksum::sign($snapshot);

		$this->assertNotSame($withLivewireKey, $withAppKey);
	}

	private function restoreEnvironmentValue(string $key, mixed $value): void
	{
		if ($value === null) {
			unset($_ENV[$key]);
			return;
		}

		$_ENV[$key] = $value;
	}
}
