<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Protocol;

use Clover\Classes\Protocol\Internet;
use Clover\Classes\Protocol\PHP;
use PHPUnit\Framework\TestCase;

final class ProtocolTest extends TestCase
{
	public function testPhpProtocolAccessorsReturnCanonicalStreamUris(): void
	{
		$protocol = new PHP();

		$this->assertSame('php://stderr', $protocol->getStandardError());
		$this->assertSame('php://stdout', $protocol->getStandardOutput());
		$this->assertSame('php://stdin', $protocol->getStandardInput());
		$this->assertSame('php://filter', $protocol->getFilter());
		$this->assertSame('php://temp', $protocol->getTemporary());
		$this->assertSame('php://memory', $protocol->getMemory());
		$this->assertSame('php://input', $protocol->getInput());
		$this->assertSame('php://output', $protocol->getOutput());
	}

	public function testInternetValidationHonorsRequestedAddressFamily(): void
	{
		$internet = new Internet();

		$this->assertTrue($internet->isValid('192.0.2.10'));
		$this->assertFalse($internet->isValid('2001:db8::1'));
		$this->assertTrue($internet->isValid('2001:db8::1', 'ipv6'));
		$this->assertFalse($internet->isValid('192.0.2.10', 'ipv6'));
		$this->assertFalse($internet->isValid('not-an-address'));
	}

	public function testIpv6ProtocolDetectorHandlesCompressedAddress(): void
	{
		$this->assertSame(1, Internet::isV6Protocol('2001:db8::1'));
		$this->assertSame(0, Internet::isV6Protocol('192.0.2.10'));
	}
}
