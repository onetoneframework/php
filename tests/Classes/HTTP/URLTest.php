<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Tests\Classes\HTTP;

use Clover\Classes\HTTP\URL;
use Clover\Enumeration\ISO2Code;
use PHPUnit\Framework\TestCase;
use stdClass;

final class URLTest extends TestCase
{
	public function testGenerateEncodedQueryStringSupportsBothEncodingStandards(): void
	{
		$data = ['query' => 'framework test', 'page' => 2];

		self::assertSame(
			'query=framework+test&page=2',
			URL::generateEncodedQueryString($data)
		);
		self::assertSame(
			'query=framework%20test&page=2',
			URL::generateEncodedQueryString($data, '', '&', PHP_QUERY_RFC3986)
		);
	}

	public function testGenerateEncodedQueryStringSupportsObjectsAndNumericPrefixes(): void
	{
		$object = new stdClass();
		$object->search = 'event bus';

		self::assertSame('search=event+bus', URL::generateEncodedQueryString($object));
		self::assertSame(
			'item_0=first;item_1=second',
			URL::generateEncodedQueryString(['first', 'second'], 'item_', ';')
		);
	}

	public function testMusicAndVideoLinksUseProvidedIdentifiers(): void
	{
		self::assertSame(
			'https://music.apple.com/US/album/123',
			URL::getItunesLink(ISO2Code::UNITED_STATES_OF_AMERICA, 123)
		);
		self::assertSame('https://www.amazon.co.jp/dp/ASIN123', URL::getAmazoneJpMusicLink('ASIN123'));
		self::assertSame('http://dlsoft.dmm.co.jp/music/detail/music-1', URL::getDMMMusicLink('music-1'));
		self::assertSame(
			'https://www.dlsite.com/pro/work/=/product_id/RJ123.html',
			URL::getDlsiteProductLink('RJ123')
		);
		self::assertSame('https://www.youtube.com/watch?v=video-1', URL::getYoutubeLink('video-1'));
	}
}
