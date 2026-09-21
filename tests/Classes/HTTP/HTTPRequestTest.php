<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\HTTP;

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use PHPUnit\Framework\TestCase;
use Clover\Classes\HTTP\Request as HTTPRequest;

class HTTPRequestTest extends TestCase
{

	public function setUp(): void
	{
	}

	public function testFailure(): void
	{
		$server = $_SERVER;

		try {
			unset($_SERVER['HTTPS']);
			$_SERVER['HTTP_HOST'] = 'localhost';
			$_SERVER['REQUEST_URI'] = '/unknown';

			$this->assertEquals('http://localhost/unknown', HTTPRequest::getRequestURL()->__toString());
		} finally {
			$_SERVER = $server;
		}
	}
}
