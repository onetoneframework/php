<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Data;

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use Clover\Classes\Data\URLObject;
use PHPUnit\Framework\TestCase;

class URLObjectTest extends TestCase
{

	public function setUp(): void
	{
	}

	public function testFailure(): void
	{
		$string = new URLObject('https://www.google.com/?q=test');
        $this->assertEquals('https',  $string->getProtocol());
        $this->assertEquals('www.google.com',  $string->getDomain());
        $this->assertEquals('q=test',  $string->getQueryString());
        $this->assertEquals('https://www.google.com/',  $string->clearQueryParameters());
        $this->assertEquals('https://www.google.com/?test=test',  $string->setQueryString(['test' => 'test']));

		$string = new URLObject('http://localhost:8080');
        $this->assertEquals('8080',  $string->getPort());
        $this->assertEquals('http',  $string->getScheme());
	}
}
