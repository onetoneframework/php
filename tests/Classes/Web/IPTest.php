<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Web;

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use Clover\Classes\Web\InternetProtocol;
use PHPUnit\Framework\TestCase;

class IPTest extends TestCase
{

	public function setUp(): void
	{
	}

	public function testV4(): void
	{
		$this->assertEquals(true, InternetProtocol::isVersion4('127.0.0.1'));
		$this->assertEquals(false, InternetProtocol::isVersion4('127.0.0.1.'));
		$this->assertEquals(true, InternetProtocol::isVersion6('[2001:0db8:85a3:0000:0000:8a2e:0370:7334]'));
		$this->assertEquals(true, InternetProtocol::isVersion6('[::ffff:192.0.2.128]'));
		$this->assertEquals(true, InternetProtocol::isVersion6('[::1]'));
		$this->assertEquals(true, InternetProtocol::isVersion6('[fe80::1%eth0]'));
		$this->assertEquals(false, InternetProtocol::isVersion6('192.168.0.7'));
	}
}
