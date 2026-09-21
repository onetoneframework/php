<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\LLM;

use Clover\Classes\LLM\Claude\ResponseBody as ClaudeResponseBody;
use Clover\Classes\LLM\Gemini\GeneratedImage;
use Clover\Classes\LLM\Grok\ResponseBody as GrokResponseBody;
use Clover\Classes\LLM\Perplexity\ResponseBody as PerplexityResponseBody;
use Exception;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ResponseValueObjectsTest extends TestCase
{
	public function testClaudeResponseExposesTextMetadataAndErrors(): void
	{
		$response = new ClaudeResponseBody([
			'content' => [
				['type' => 'image', 'source' => 'ignored'],
				['type' => 'text', 'text' => 'Hello from Claude'],
			],
			'usage' => ['input_tokens' => 5, 'output_tokens' => 3],
			'stop_reason' => 'end_turn',
		]);

		$this->assertFalse($response->hasError());
		$this->assertSame('Hello from Claude', (string) $response->getText());
		$this->assertSame(['input_tokens' => 5, 'output_tokens' => 3], $response->getUsage());
		$this->assertSame('end_turn', $response->getStopReason());

		$error = new ClaudeResponseBody(['error' => ['type' => 'invalid_request_error', 'message' => 'Bad request']]);
		$this->assertTrue($error->hasError());
		$this->assertSame('Bad request', (string) $error->getError()?->getMessage());
	}

	#[DataProvider('chatCompletionResponseProvider')]
	public function testChatCompletionStyleResponseBodiesExposeFirstChoiceText(string $class): void
	{
		$response = new $class([
			'choices' => [[
				'index' => 0,
				'message' => ['role' => 'assistant', 'content' => 'Hello'],
				'finish_reason' => 'stop',
			]],
		]);

		$this->assertFalse($response->hasError());
		$this->assertSame('Hello', (string) $response->getText());
	}

	public static function chatCompletionResponseProvider(): array
	{
		return [
			'grok' => [GrokResponseBody::class],
			'perplexity' => [PerplexityResponseBody::class],
		];
	}

	#[DataProvider('chatCompletionResponseProvider')]
	public function testChatCompletionStyleResponseBodiesRejectMissingChoices(string $class): void
	{
		$response = new $class([]);

		$this->expectException(Exception::class);
		$this->expectExceptionMessage('Choices is empty');
		$response->getText();
	}

	public function testGeneratedImageDecodesBytesAndDerivesExtensions(): void
	{
		$bytes = "\x89PNG\r\n";
		$image = GeneratedImage::from([
			'bytesBase64Encoded' => base64_encode($bytes),
			'mimeType' => 'image/png',
		]);

		$this->assertSame(base64_encode($bytes), $image->getBase64());
		$this->assertSame($bytes, $image->getBytes());
		$this->assertSame('image/png', $image->getMimeType());
		$this->assertSame('png', $image->getExtension());
		$this->assertSame('jpg', (new GeneratedImage('', 'image/jpeg'))->getExtension());
		$this->assertSame('bin', (new GeneratedImage('', 'application/octet-stream'))->getExtension());
	}

	public function testGeneratedImageRequiresBothPredictionFields(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Imagen prediction is missing');

		GeneratedImage::from(['mimeType' => 'image/png']);
	}
}
