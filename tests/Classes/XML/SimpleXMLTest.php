<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\XML;

use Clover\Classes\XML\SimpleXML;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SimpleXMLTest extends TestCase
{
	public function testIsXmlDistinguishesWellFormedAndMalformedInput(): void
	{
		$this->assertTrue(SimpleXML::isXML('<root><item/></root>'));
		$this->assertFalse(SimpleXML::isXML('<root><item></root>'));
	}

	public function testFromStringExposesChildrenAttributesAndIteration(): void
	{
		$xml = SimpleXML::fromString('<root kind="sample"><item>A</item><item>B</item></root>');

		$this->assertTrue($xml->isValid());
		$this->assertSame('root', $xml->getName());
		$this->assertSame(['kind' => 'sample'], $xml->getAttributes());
		$this->assertSame(2, $xml->count());
		$this->assertCount(2, $xml->getChildrenByName('item'));
		$this->assertSame(['A', 'B'], array_map(static fn(SimpleXML $item): string => $item->getText(), $xml->getChildrenByName('item')));
	}

	public function testCreateAndAddChildMutateDocumentThroughPublicApi(): void
	{
		$xml = SimpleXML::create('root');
		$child = $xml->addChild('item', 'value');
		$child->setAttribute('id', '7');

		$this->assertTrue($xml->has('item'));
		$this->assertSame('value', $xml->item->getText());
		$this->assertSame(['id' => '7'], $child->getAttributes());
		$this->assertSame([
			'item' => [
				'@attributes' => ['id' => '7'],
				'@value' => 'value',
			],
		], $xml->toArray());
	}

	public function testRegisteredNamespaceSupportsXPathAndScalarEvaluation(): void
	{
		$xml = SimpleXML::fromString('<feed xmlns="urn:test"><entry>A</entry><entry>B</entry></feed>');
		$xml->registerNamespace('t', 'urn:test');

		$this->assertCount(2, $xml->xpath('//t:entry'));
		$this->assertTrue($xml->xpathExists('//t:entry[text()="B"]'));
		$this->assertSame(2.0, $xml->xpathEvaluate('count(//t:entry)'));
	}

	public function testEmptyInputIsRejectedByFactory(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('XML string cannot be empty');

		SimpleXML::fromString('');
	}
}
