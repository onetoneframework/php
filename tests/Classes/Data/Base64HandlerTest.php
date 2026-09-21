<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Data;

use Clover\Classes\Data\Base64Handler;
use PHPUnit\Framework\TestCase;

final class Base64HandlerTest extends TestCase
{
    public function testEncodeDecodeAndIsBase64(): void
    {
        $raw = 'hello-framework';
        $encoded = Base64Handler::encode($raw);

        $this->assertTrue(Base64Handler::isBase64($encoded));
        $this->assertSame($raw, Base64Handler::decode($encoded));
        $this->assertFalse(Base64Handler::isBase64('not-base64'));
    }

    public function testUrlEncodeAndUrlDecodeWithoutPadding(): void
    {
        $raw = 'token.payload';
        $urlEncoded = Base64Handler::urlEncode($raw, false);

        $this->assertStringNotContainsString('=', $urlEncoded);
        $this->assertSame($raw, Base64Handler::urlDecode($urlEncoded));
    }
}
