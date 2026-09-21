<?php

declare(strict_types=1);

namespace Clover\Tests\App\Modules\AudioAnalysis;

use App\Modules\AudioAnalysis\Contract\AudioAnalysisGatewayInterface;
use App\Modules\AudioAnalysis\Contract\AudioAnalysisFailureLoggerInterface;
use App\Modules\AudioAnalysis\Contract\UploadedAudioProviderInterface;
use App\Modules\AudioAnalysis\Controller\AudioAnalysisController;
use App\Modules\AudioAnalysis\DataTransferObject\AudioAnalysisResponse;
use App\Modules\AudioAnalysis\DataTransferObject\UploadedAudio;
use App\Modules\AudioAnalysis\Exception\AudioAnalysisTimeoutException;
use App\Modules\AudioAnalysis\Exception\AudioAnalysisUnavailableException;
use App\Modules\AudioAnalysis\Exception\AudioUploadException;
use App\Modules\AudioAnalysis\Exception\InvalidAudioAnalysisResponseException;
use Clover\Classes\DependencyInjection\Container;
use Clover\Classes\Routing\RouteAnnotationReader;
use Clover\Classes\Routing\RouteExecutor;
use Clover\Enumeration\HTTPStatusCode;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class AudioAnalysisControllerTest extends TestCase
{
	public function testAnalyzeReturnsGatewayPayload(): void
	{
		$gateway = new ControllerAudioAnalysisGatewayFake($this->createAnalysisResponse());
		$provider = new ControllerUploadedAudioProviderFake($this->createUploadedAudio());
		$failureLogger = new ControllerAudioAnalysisFailureLoggerFake();

		$response = (new AudioAnalysisController())->analyze($gateway, $provider, $failureLogger);

		$this->assertSame(HTTPStatusCode::OK, $response->getStatusCode());
		$this->assertSame('audio', $provider->requestedFields[0]);
		$this->assertSame(AudioAnalysisPayloadFixture::ANALYSIS_IDENTIFIER, $gateway->analyzedAudioIdentifier);
		$this->assertSame(
			AudioAnalysisPayloadFixture::ANALYSIS_IDENTIFIER,
			$this->decodeBody($response->getBody())['analysis_id']
		);
		$this->assertSame([], $failureLogger->exceptions);
	}

	public function testCompareUsesBothPublicUploadFields(): void
	{
		$gateway = new ControllerAudioAnalysisGatewayFake($this->createComparisonResponse());
		$provider = new ControllerUploadedAudioProviderFake($this->createUploadedAudio());
		$failureLogger = new ControllerAudioAnalysisFailureLoggerFake();

		$response = (new AudioAnalysisController())->compare($gateway, $provider, $failureLogger);

		$this->assertSame(HTTPStatusCode::OK, $response->getStatusCode());
		$this->assertSame(['baseline_audio', 'current_audio'], $provider->requestedFields);
		$this->assertSame(
			AudioAnalysisPayloadFixture::COMPARISON_IDENTIFIER,
			$this->decodeBody($response->getBody())['comparison_id']
		);
		$this->assertSame([], $failureLogger->exceptions);
	}

	public function testRouteExecutorInjectsControllerInterfaceDependencies(): void
	{
		$gateway = new ControllerAudioAnalysisGatewayFake($this->createAnalysisResponse());
		$provider = new ControllerUploadedAudioProviderFake($this->createUploadedAudio());
		$failureLogger = new ControllerAudioAnalysisFailureLoggerFake();
		$container = new Container();
		$container->set(ControllerAudioAnalysisGatewayFake::class, $gateway);
		$container->set(ControllerUploadedAudioProviderFake::class, $provider);
		$container->set(ControllerAudioAnalysisFailureLoggerFake::class, $failureLogger);
		$executor = new RouteExecutor(
			AudioAnalysisController::class,
			'analyze',
			null,
			[],
			$container
		);

		$response = $executor();

		$this->assertSame(HTTPStatusCode::OK, $response->getStatusCode());
		$this->assertSame(['audio'], $provider->requestedFields);
		$this->assertSame([], $failureLogger->exceptions);
	}

	/**
	 * @return array<string, array{0: RuntimeException, 1: int, 2: bool}>
	 */
	public static function provideErrorMappings(): array
	{
		return [
			'upload failure' => [
				new AudioUploadException('Only WAV audio uploads are supported.', HTTPStatusCode::UNSUPPORTED_MEDIA_TYPE),
				HTTPStatusCode::UNSUPPORTED_MEDIA_TYPE,
				true,
			],
			'upstream timeout' => [
				new AudioAnalysisTimeoutException('Timeout'),
				HTTPStatusCode::GATEWAY_TIMEOUT,
				false,
			],
			'upstream unavailable' => [
				new AudioAnalysisUnavailableException('Unavailable'),
				HTTPStatusCode::SERVICE_UNAVAILABLE,
				false,
			],
			'invalid upstream response' => [
				new InvalidAudioAnalysisResponseException('Invalid response'),
				HTTPStatusCode::BAD_GATEWAY,
				false,
			],
		];
	}

	#[DataProvider('provideErrorMappings')]
	public function testMapsDomainErrorStatus(
		RuntimeException $exception,
		int $expectedStatusCode,
		bool $providerFailure
	): void {
		$gatewayResult = $providerFailure ? $this->createAnalysisResponse() : $exception;
		$gateway = new ControllerAudioAnalysisGatewayFake($gatewayResult);
		$providerResult = $this->createUploadedAudio();
		if ($providerFailure && $exception instanceof AudioUploadException) {
			$providerResult = $exception;
		}
		$provider = new ControllerUploadedAudioProviderFake($providerResult);
		$failureLogger = new ControllerAudioAnalysisFailureLoggerFake();

		$response = (new AudioAnalysisController())->analyze($gateway, $provider, $failureLogger);

		$this->assertSame($expectedStatusCode, $response->getStatusCode());
		$errorPayload = $this->decodeBody($response->getBody())['error'];
		$this->assertIsArray($errorPayload);
		$this->assertIsString($errorPayload['code']);
		$this->assertIsString($errorPayload['message']);
		$this->assertNotSame('', $errorPayload['correlation_id']);
		$this->assertSame([$exception], $failureLogger->exceptions);
		$this->assertSame([$errorPayload['correlation_id']], $failureLogger->correlationIdentifiers);
	}

	public function testDeclaresPublicPostRoutes(): void
	{
		$routes = (new RouteAnnotationReader())->read(AudioAnalysisController::class);
		$routesByHandler = [];

		foreach ($routes as $route) {
			if (is_array($route->holder) && isset($route->holder[1])) {
				$routesByHandler[$route->holder[1]] = $route;
			}
		}

		$this->assertCount(2, $routesByHandler);
		$this->assertSame('POST', $routesByHandler['analyze']->method);
		$this->assertSame('/api/audio-analysis/analyses', $routesByHandler['analyze']->pattern);
		$this->assertSame('POST', $routesByHandler['compare']->method);
		$this->assertSame('/api/audio-analysis/comparisons', $routesByHandler['compare']->pattern);
	}

	private function createUploadedAudio(): UploadedAudio
	{
		return new UploadedAudio('temporary-audio', 'sample.wav', 'audio/wav');
	}

	private function createAnalysisResponse(): AudioAnalysisResponse
	{
		return AudioAnalysisResponse::fromPayload(AudioAnalysisPayloadFixture::analysis());
	}

	private function createComparisonResponse(): AudioAnalysisResponse
	{
		return AudioAnalysisResponse::fromPayload(AudioAnalysisPayloadFixture::comparison());
	}

	/**
	 * @return array<string, mixed>
	 */
	private function decodeBody(mixed $body): array
	{
		if (!is_string($body)) {
			throw new RuntimeException('The controller response body is not a string.');
		}

		$decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
		if (!is_array($decoded)) {
			throw new RuntimeException('The controller response body is not a JSON object.');
		}

		return $decoded;
	}
}

final class ControllerAudioAnalysisGatewayFake implements AudioAnalysisGatewayInterface
{
	public string $analyzedAudioIdentifier = '';

	public function __construct(private AudioAnalysisResponse|RuntimeException $result)
	{
	}

	public function analyze(UploadedAudio $audio): AudioAnalysisResponse
	{
		$this->analyzedAudioIdentifier = $this->result instanceof AudioAnalysisResponse
			? (string) $this->result->toArray()['analysis_id']
			: '';

		if ($this->result instanceof RuntimeException) {
			throw $this->result;
		}

		return $this->result;
	}

	public function compare(UploadedAudio $baselineAudio, UploadedAudio $currentAudio): AudioAnalysisResponse
	{
		if ($this->result instanceof RuntimeException) {
			throw $this->result;
		}

		return $this->result;
	}
}

final class ControllerUploadedAudioProviderFake implements UploadedAudioProviderInterface
{
	/** @var string[] */
	public array $requestedFields = [];

	public function __construct(private UploadedAudio|AudioUploadException $result)
	{
	}

	public function get(string $fieldName): UploadedAudio
	{
		$this->requestedFields[] = $fieldName;

		if ($this->result instanceof AudioUploadException) {
			throw $this->result;
		}

		return $this->result;
	}
}

final class ControllerAudioAnalysisFailureLoggerFake implements AudioAnalysisFailureLoggerInterface
{
	/** @var RuntimeException[] */
	public array $exceptions = [];
	/** @var string[] */
	public array $correlationIdentifiers = [];

	public function log(RuntimeException $exception, string $correlationIdentifier): void
	{
		$this->exceptions[] = $exception;
		$this->correlationIdentifiers[] = $correlationIdentifier;
	}
}
