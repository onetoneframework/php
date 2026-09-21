<?php

declare(strict_types=1);

namespace Clover\Tests\App\Modules\AudioAnalysis;

use App\Modules\AudioAnalysis\DataTransferObject\AudioAnalysisConfiguration;
use App\Modules\AudioAnalysis\DataTransferObject\UploadedAudio;
use App\Modules\AudioAnalysis\Exception\AudioAnalysisTimeoutException;
use App\Modules\AudioAnalysis\Exception\AudioAnalysisUnavailableException;
use App\Modules\AudioAnalysis\Exception\AudioUploadException;
use App\Modules\AudioAnalysis\Exception\InvalidAudioAnalysisResponseException;
use App\Modules\AudioAnalysis\Infrastructure\FastApiAudioAnalysisGateway;
use Clover\Classes\Data\JSONHandler;
use Clover\Classes\Data\StringObject;
use Clover\Enumeration\HTTPStatusCode;
use Clover\Implement\ClientURLInterface;
use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

use function is_string;
use function str_contains;

final class FastApiAudioAnalysisGatewayTest extends TestCase
{
	private const CONNECTION_TIMEOUT_SECONDS = 4;
	private const REQUEST_TIMEOUT_SECONDS = 90;

	public function testAnalyzeConfiguresMultipartRequestAndClearsStaleFields(): void
	{
		$options = new GatewayOptionFake();
		$options->postFields = ['stale_audio' => 'stale-value'];
		$client = new GatewayClientFake(
			$options,
			$this->encode(AudioAnalysisPayloadFixture::analysis()),
			HTTPStatusCode::OK
		);
		$requestedUrl = '';
		$gateway = $this->createGateway($client, $requestedUrl);
		$audio = new UploadedAudio('temporary-audio', 'sample.wav', 'audio/wav');

		$response = $gateway->analyze($audio);

		$this->assertSame(AudioAnalysisPayloadFixture::ANALYSIS_IDENTIFIER, $response->toArray()['analysis_id']);
		$this->assertSame('http://analysis:8000/v1/analyses', $requestedUrl);
		$this->assertSame([
			'setURL',
			'setHeaders',
			'setPostMethod',
			'setReturnTransfer',
			'setConnectionTimeout',
			'setTimeout',
			'setPostFields',
			'setPostFileField',
		], $options->calls);
		$this->assertSame(['Accept: application/json'], $options->headers);
		$this->assertSame(self::CONNECTION_TIMEOUT_SECONDS, $options->connectionTimeoutSeconds);
		$this->assertSame(self::REQUEST_TIMEOUT_SECONDS, $options->requestTimeoutSeconds);
		$this->assertSame(['audio'], array_keys($options->postFields));
		$this->assertSame([
			'path' => 'temporary-audio',
			'mime_type' => 'audio/wav',
			'posted_file_name' => 'sample.wav',
		], $options->files['audio']);
		$this->assertTrue($client->closed);
	}

	public function testComparePostsBothNamedAudioFields(): void
	{
		$options = new GatewayOptionFake();
		$client = new GatewayClientFake(
			$options,
			$this->encode(AudioAnalysisPayloadFixture::comparison()),
			HTTPStatusCode::OK
		);
		$requestedUrl = '';
		$gateway = $this->createGateway($client, $requestedUrl);

		$response = $gateway->compare(
			new UploadedAudio('baseline-temporary', 'baseline.wav', 'audio/wav'),
			new UploadedAudio('current-temporary', 'current.wav', 'audio/wav')
		);

		$this->assertSame(
			AudioAnalysisPayloadFixture::COMPARISON_IDENTIFIER,
			$response->toArray()['comparison_id']
		);
		$this->assertSame('http://analysis:8000/v1/comparisons', $requestedUrl);
		$this->assertSame(['baseline_audio', 'current_audio'], array_keys($options->files));
		$this->assertSame('baseline.wav', $options->files['baseline_audio']['posted_file_name']);
		$this->assertSame('current.wav', $options->files['current_audio']['posted_file_name']);
		$this->assertTrue($client->closed);
	}

	/**
	 * @return array<string, array{0: int}>
	 */
	public static function provideSafeUpstreamUploadStatuses(): array
	{
		return [
			'payload too large' => [HTTPStatusCode::REQUEST_ENTITY_TOO_LARGE],
			'unsupported media type' => [HTTPStatusCode::UNSUPPORTED_MEDIA_TYPE],
			'unprocessable audio' => [HTTPStatusCode::UNPROCESSABLE_ENTITY],
		];
	}

	#[DataProvider('provideSafeUpstreamUploadStatuses')]
	public function testMapsSafeUpstreamUploadStatus(int $statusCode): void
	{
		$options = new GatewayOptionFake();
		$client = new GatewayClientFake($options, '{}', $statusCode);
		$requestedUrl = '';
		$gateway = $this->createGateway($client, $requestedUrl);

		try {
			$gateway->analyze(new UploadedAudio('temporary-audio', 'sample.wav', 'audio/wav'));
			$this->fail('A safe upstream upload rejection must be surfaced.');
		} catch (AudioUploadException $exception) {
			$this->assertSame($statusCode, $exception->getStatusCode());
			$this->assertTrue($client->closed);
		}
	}

	public function testMapsUpstreamServerErrorToUnavailable(): void
	{
		$options = new GatewayOptionFake();
		$client = new GatewayClientFake($options, '{}', HTTPStatusCode::INTERNAL_SERVER_ERROR);
		$requestedUrl = '';
		$gateway = $this->createGateway($client, $requestedUrl);

		$this->expectException(AudioAnalysisUnavailableException::class);

		$gateway->analyze(new UploadedAudio('temporary-audio', 'sample.wav', 'audio/wav'));
	}

	public function testSurfacesSafeUpstreamAudioValidationMessage(): void
	{
		$options = new GatewayOptionFake();
		$client = new GatewayClientFake(
			$options,
			$this->encode([
				'error' => [
					'code' => 'unsupported_recording',
					'message' => 'The WAV sample rate must be between 16000 and 192000 Hz.',
					'correlation_id' => 'ad55cb86-0816-48ac-ac5b-4f3d8f1aeccc',
				],
			]),
			HTTPStatusCode::UNPROCESSABLE_ENTITY
		);
		$requestedUrl = '';
		$gateway = $this->createGateway($client, $requestedUrl);

		try {
			$gateway->analyze(new UploadedAudio('temporary-audio', 'sample.wav', 'audio/wav'));
			$this->fail('A validated upstream rejection message must be surfaced.');
		} catch (AudioUploadException $exception) {
			$this->assertSame(
				'The WAV sample rate must be between 16000 and 192000 Hz.',
				$exception->getMessage()
			);
			$this->assertTrue($client->closed);
		}
	}

	public function testHidesUntrustedUpstreamAudioErrorMessage(): void
	{
		$options = new GatewayOptionFake();
		$client = new GatewayClientFake(
			$options,
			$this->encode([
				'error' => [
					'code' => 'internal_error',
					'message' => 'Sensitive internal detail.',
					'correlation_id' => 'ad55cb86-0816-48ac-ac5b-4f3d8f1aeccc',
				],
			]),
			HTTPStatusCode::UNPROCESSABLE_ENTITY
		);
		$requestedUrl = '';
		$gateway = $this->createGateway($client, $requestedUrl);

		try {
			$gateway->analyze(new UploadedAudio('temporary-audio', 'sample.wav', 'audio/wav'));
			$this->fail('An unsafe upstream error must use the public fallback message.');
		} catch (AudioUploadException $exception) {
			$this->assertStringNotContainsString('Sensitive internal detail.', $exception->getMessage());
		$this->assertStringContainsString('PCM or 32-bit float WAV', $exception->getMessage());
		}
	}

	public function testRejectsMalformedJsonResponse(): void
	{
		$options = new GatewayOptionFake();
		$client = new GatewayClientFake($options, '{invalid-json', HTTPStatusCode::OK);
		$requestedUrl = '';
		$gateway = $this->createGateway($client, $requestedUrl);

		$this->expectException(InvalidAudioAnalysisResponseException::class);

		$gateway->analyze(new UploadedAudio('temporary-audio', 'sample.wav', 'audio/wav'));
	}

	public function testRejectsUnexpectedJsonSchema(): void
	{
		$options = new GatewayOptionFake();
		$client = new GatewayClientFake($options, '{"analysis_id":"missing-fields"}', HTTPStatusCode::OK);
		$requestedUrl = '';
		$gateway = $this->createGateway($client, $requestedUrl);

		$this->expectException(InvalidAudioAnalysisResponseException::class);

		$gateway->analyze(new UploadedAudio('temporary-audio', 'sample.wav', 'audio/wav'));
	}

	public function testMapsNetworkTimeout(): void
	{
		$options = new GatewayOptionFake();
		$client = new GatewayClientFake($options, new Exception('The operation timed out.'), 0);
		$requestedUrl = '';
		$gateway = $this->createGateway($client, $requestedUrl);

		$this->expectException(AudioAnalysisTimeoutException::class);

		$gateway->analyze(new UploadedAudio('temporary-audio', 'sample.wav', 'audio/wav'));
	}

	public function testMapsNetworkFailureToUnavailable(): void
	{
		$options = new GatewayOptionFake();
		$client = new GatewayClientFake($options, new Exception('Connection refused.'), 0);
		$requestedUrl = '';
		$gateway = $this->createGateway($client, $requestedUrl);

		$this->expectException(AudioAnalysisUnavailableException::class);

		$gateway->analyze(new UploadedAudio('temporary-audio', 'sample.wav', 'audio/wav'));
	}

	private function createGateway(GatewayClientFake $client, string &$requestedUrl): FastApiAudioAnalysisGateway
	{
		$configuration = new AudioAnalysisConfiguration(
			'http://analysis:8000',
			self::CONNECTION_TIMEOUT_SECONDS,
			self::REQUEST_TIMEOUT_SECONDS
		);
		$clientFactory = static function (string $url) use ($client, &$requestedUrl): ClientURLInterface {
			$requestedUrl = $url;

			return $client;
		};

		return new FastApiAudioAnalysisGateway($configuration, $clientFactory);
	}

	/**
	 * @param array<string, mixed> $payload
	 */
	private function encode(array $payload): string
	{
		$encoded = JSONHandler::encode($payload);
		if (!is_string($encoded)) {
			throw new RuntimeException('The gateway test fixture could not be encoded.');
		}

		return $encoded;
	}
}

final class GatewayOptionFake
{
	/** @var string[] */
	public array $calls = [];
	/** @var string[] */
	public array $headers = [];
	/** @var array<string, mixed> */
	public array $postFields = [];
	/** @var array<string, array{path: string, mime_type: string, posted_file_name: string}> */
	public array $files = [];
	public int $connectionTimeoutSeconds = 0;
	public int $requestTimeoutSeconds = 0;
	public string $url = '';

	public function setURL(string $url): self
	{
		$this->calls[] = 'setURL';
		$this->url = $url;

		return $this;
	}

	/**
	 * @param string[] $headers
	 */
	public function setHeaders(array $headers = []): self
	{
		$this->calls[] = 'setHeaders';
		$this->headers = $headers;

		return $this;
	}

	public function setPostMethod(bool $enabled = true): self
	{
		$this->calls[] = 'setPostMethod';

		return $this;
	}

	public function setReturnTransfer(bool $enabled = true): self
	{
		$this->calls[] = 'setReturnTransfer';

		return $this;
	}

	public function setConnectionTimeout(int $seconds): self
	{
		$this->calls[] = 'setConnectionTimeout';
		$this->connectionTimeoutSeconds = $seconds;

		return $this;
	}

	public function setTimeout(int $seconds): self
	{
		$this->calls[] = 'setTimeout';
		$this->requestTimeoutSeconds = $seconds;

		return $this;
	}

	/**
	 * @param array<string, mixed> $fields
	 */
	public function setPostFields(array $fields): self
	{
		$this->calls[] = 'setPostFields';
		$this->postFields = $fields;

		return $this;
	}

	public function setPostFileField(
		string $fieldName,
		string $temporaryPath,
		string $mimeType,
		string $postedFileName
	): self {
		$this->calls[] = 'setPostFileField';
		$file = [
			'path' => $temporaryPath,
			'mime_type' => $mimeType,
			'posted_file_name' => $postedFileName,
		];
		$this->files[$fieldName] = $file;
		$this->postFields[$fieldName] = $file;

		return $this;
	}
}

final class GatewayClientFake implements ClientURLInterface
{
	private const TIMEOUT_ERROR_NUMBER = 28;
	private const UNAVAILABLE_ERROR_NUMBER = 7;

	public bool $closed = false;

	public function __construct(
		private GatewayOptionFake $options,
		private string|Exception $executionResult,
		private int $statusCode
	) {
	}

	public function close(): void
	{
		$this->closed = true;
	}

	public function execute(): mixed
	{
		if ($this->executionResult instanceof Exception) {
			throw $this->executionResult;
		}

		return $this->executionResult;
	}

	public function getLastErrorMessage(): string
	{
		return $this->executionResult instanceof Exception ? $this->executionResult->getMessage() : '';
	}

	public function getLastErrorNumber(): int
	{
		if ($this->executionResult instanceof Exception
			&& str_contains($this->executionResult->getMessage(), 'timed out')) {
			return self::TIMEOUT_ERROR_NUMBER;
		}

		return self::UNAVAILABLE_ERROR_NUMBER;
	}

	public function getSession(): bool
	{
		return true;
	}

	public function information(): object
	{
		return new GatewayInformationFake($this->statusCode);
	}

	public function initialize(string|StringObject|null $instance = null): mixed
	{
		return $this;
	}

	public function option(): object
	{
		return $this->options;
	}

	public function reset(): void
	{
	}

	public function setOption(int $option, mixed $value): bool
	{
		return true;
	}
}

final class GatewayInformationFake
{
	public function __construct(private int $statusCode)
	{
	}

	public function getStatusCode(): int
	{
		return $this->statusCode;
	}
}
