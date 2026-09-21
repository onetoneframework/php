<?php

declare(strict_types=1);

namespace Clover\Tests\Framework\Component;

use Clover\Framework\Component\Response;
use Clover\Framework\Component\Translator;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;

final class ResponseTest extends TestCase
{
    protected function tearDown(): void
    {
        $_ENV['APP_LOCALE'] = 'en';
        $_ENV['APP_FALLBACK_LOCALE'] = 'en';
        $this->resetTranslator();
    }

    public function testJsonResponseHasExpectedTypeBodyAndStatus(): void
    {
        $response = Response::json(['ok' => true], 201);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('json', $response->getType());
        $this->assertSame('{"ok":true}', $response->getBody());
        $this->assertSame('application/json; charset=utf-8', $response->getHeader('Content-Type'));
    }

    public function testJsonEncodingFailureMessageIsLocalized(): void
    {
        $_ENV['APP_LOCALE'] = 'ko';
        $this->resetTranslator();

        $recursive = [];
        $recursive['self'] = &$recursive;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('JSON 인코딩에 실패했습니다');

        Response::json($recursive);
    }

	public function testRedirectSetsLocationHeaderAndStatus(): void
	{
        $response = Response::redirect('/target', 302);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('/target', $response->getHeader('Location'));
		$this->assertSame('text/html; charset=utf-8', $response->getHeader('Content-Type'));
	}

	public function testHeaderOperationsAreCaseInsensitive(): void
	{
		$response = new Response();
		$response->setHeader('x-request-id', 'first');
		$response->setHeader('X-Request-Id', 'second');

		$this->assertTrue($response->hasHeader('X-REQUEST-ID'));
		$this->assertSame('second', $response->getHeader('x-request-id'));
		$this->assertCount(1, array_filter(
			$response->getResponseHeaders(),
			static fn (string $name): bool => strcasecmp($name, 'X-Request-Id') === 0,
			ARRAY_FILTER_USE_KEY
		));

		$response->removeHeader('x-REQUEST-id');
		$this->assertFalse($response->hasHeader('X-Request-Id'));
	}

    private function resetTranslator(): void
    {
        $reflection = new ReflectionClass(Translator::class);
        $property = $reflection->getProperty('translator');
        if (version_compare(PHP_VERSION, '8.1.0', '<')) {
            // @phpstan-ignore-next-line
            $property->setAccessible(true);
        }
        $property->setValue(null, null);
    }
}
