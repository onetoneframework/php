<?php

declare(strict_types=1);

namespace Clover\Tests\Framework\Component;

use Clover\Framework\Component\TrustedProxies;
use PHPUnit\Framework\TestCase;

final class TrustedProxiesTest extends TestCase
{
	private array $originalEnvironment = [];

	protected function setUp(): void
	{
		$this->originalEnvironment = $_ENV;
	}

	protected function tearDown(): void
	{
		$_ENV = $this->originalEnvironment;
	}

	public function testEmptyListTrustsNoPeer(): void
	{
		$trustedProxies = TrustedProxies::fromList([]);

		$this->assertTrue($trustedProxies->isEmpty());
		$this->assertFalse($trustedProxies->trusts('127.0.0.1'));
	}

	public function testListNormalizationIgnoresBlankAndNonStringEntries(): void
	{
		$trustedProxies = TrustedProxies::fromList([
			'   ',
			42,
			null,
			' 192.0.2.10 ',
		]);

		$this->assertFalse($trustedProxies->isEmpty());
		$this->assertTrue($trustedProxies->trusts('192.0.2.10'));
	}

	public function testExactIpv4AddressMatchesOnlyConfiguredPeer(): void
	{
		$trustedProxies = TrustedProxies::fromList(['192.0.2.10']);

		$this->assertTrue($trustedProxies->trusts('192.0.2.10'));
		$this->assertFalse($trustedProxies->trusts('192.0.2.11'));
	}

	public function testEquivalentIpv6SpellingsMatchInBinaryForm(): void
	{
		$trustedProxies = TrustedProxies::fromList(['[2001:db8::1]']);

		$this->assertTrue($trustedProxies->trusts('2001:0db8:0000:0000:0000:0000:0000:0001'));
		$this->assertTrue($trustedProxies->trusts('[2001:db8::1]'));
	}

	public function testIpv4CidrMatchesInsidePrefixAndRejectsOutsidePeer(): void
	{
		$trustedProxies = TrustedProxies::fromList(['10.20.0.0/16']);

		$this->assertTrue($trustedProxies->trusts('10.20.255.254'));
		$this->assertFalse($trustedProxies->trusts('10.21.0.1'));
	}

	public function testIpv6CidrSupportsNonByteAlignedPrefixes(): void
	{
		$trustedProxies = TrustedProxies::fromList(['2001:db8:8000::/33']);

		$this->assertTrue($trustedProxies->trusts('2001:db8:ffff::1'));
		$this->assertFalse($trustedProxies->trusts('2001:db8:7fff::1'));
	}

	public function testCidrDoesNotCrossAddressFamilies(): void
	{
		$trustedProxies = TrustedProxies::fromList(['10.0.0.0/8']);

		$this->assertFalse($trustedProxies->trusts('::ffff:10.0.0.1'));
	}

	public function testZeroLengthPrefixTrustsAnyPeerInSameAddressFamily(): void
	{
		$trustedProxies = TrustedProxies::fromList(['0.0.0.0/0']);

		$this->assertTrue($trustedProxies->trusts('203.0.113.25'));
		$this->assertFalse($trustedProxies->trusts('2001:db8::25'));
	}

	public function testMalformedEntriesAndPeersAreRejected(): void
	{
		$trustedProxies = TrustedProxies::fromList([
			'10.0.0.0/not-a-prefix',
			'10.0.0.0/33',
			'not-an-address',
		]);

		$this->assertFalse($trustedProxies->trusts('10.0.0.1'));
		$this->assertFalse($trustedProxies->trusts('not-an-address'));
		$this->assertFalse($trustedProxies->trusts(null));
	}

	public function testFromEnvironmentParsesCommaSeparatedEntriesPerCall(): void
	{
		$_ENV['APP_TRUSTED_PROXIES'] = '192.0.2.10, 2001:db8::/32';

		$first = TrustedProxies::fromEnv();
		$this->assertTrue($first->trusts('192.0.2.10'));
		$this->assertTrue($first->trusts('2001:db8::99'));

		$_ENV['APP_TRUSTED_PROXIES'] = '198.51.100.20';
		$second = TrustedProxies::fromEnv();

		$this->assertFalse($second->trusts('192.0.2.10'));
		$this->assertTrue($second->trusts('198.51.100.20'));
	}
}
