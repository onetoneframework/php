<?php

declare(strict_types=1);

namespace Clover\Tests\Annotation;

use Clover\Annotation\ContentType;
use Clover\Enumeration\ContentType as ContentTypeValue;
use PHPUnit\Framework\TestCase;

final class ContentTypeTest extends TestCase
{
	public function testContentTypePreservesLiteralMediaType(): void
	{
		$contentType = new ContentType('application/json');

		$this->assertSame('application/json', $contentType->value);
	}

	public function testContentTypeAcceptsFrameworkContentTypeConstant(): void
	{
		$contentType = new ContentType(ContentTypeValue::TEXT_PLAIN);

		$this->assertSame(ContentTypeValue::TEXT_PLAIN, $contentType->value);
	}
}
