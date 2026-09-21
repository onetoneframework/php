<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\XML;

use Clover\Classes\XML\DOM;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class DOMTest extends TestCase
{
	private const XML = <<<'XML'
<catalog>
	<book id="one" class="featured"><title>Alpha</title><price>10</price></book>
	<book id="two"><title>Beta</title><price>20</price></book>
</catalog>
XML;

	public function testParseExposesDocumentAndXPathQueries(): void
	{
		$dom = (new DOM())->parse(self::XML);

		$this->assertTrue($dom->isValid());
		$this->assertSame('catalog', $dom->getDocumentElement()?->tagName);
		$this->assertSame(2, $dom->xpathCount('//book'));
		$this->assertTrue($dom->xpathExists('//book[@id="two"]'));
		$this->assertSame(2.0, $dom->evaluateXPath('count(//book)'));
		$this->assertFalse($dom->hasParseErrors());
	}

	public function testCssSelectorsAndConvenienceQueriesReturnExpectedNodes(): void
	{
		$dom = (new DOM())->parse(self::XML);
		$featured = $dom->querySelector('book.featured');

		$this->assertSame(1, $dom->querySelectorAll('book.featured')->length);
		$this->assertSame('Alpha10', trim((string) $featured?->textContent));
		$this->assertSame('one', $featured?->attributes?->getNamedItem('id')?->nodeValue);
		$this->assertSame(1, $dom->getElementsByClassName('featured', 'book')->length);
		$this->assertSame(1, $dom->getElementsByAttributeValue('id', 'two', 'book')->length);
		$this->assertSame(4, $dom->getXPathElements(['title', 'price'])->length);
	}

	public function testRegisteredNamespaceIsUsedByXPathQueries(): void
	{
		$dom = (new DOM())->parse('<feed xmlns="urn:test"><entry id="1"/><entry id="2"/></feed>');
		$dom->registerNamespace('t', 'urn:test');

		$this->assertSame(2, $dom->xpathCount('//t:entry'));
		$this->assertSame('2', $dom->getXPathNodeFirst('//t:entry[@id="2"]')?->attributes?->getNamedItem('id')?->nodeValue);
	}

	public function testEmptyXmlIsRejected(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('XML string cannot be empty');

		(new DOM())->parse('   ');
	}
}
