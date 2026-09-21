<?php

declare(strict_types=1);

namespace Clover\Tests\App\Modules\AudioAnalysis;

use App\Modules\AudioAnalysis\DataTransferObject\AudioAnalysisConfiguration;
use App\Modules\AudioAnalysis\DataTransferObject\AudioAnalysisResponse;
use App\Modules\AudioAnalysis\DataTransferObject\UploadedAudio;
use App\Modules\AudioAnalysis\Exception\InvalidAudioAnalysisResponseException;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class AudioAnalysisDataTransferObjectTest extends TestCase
{
	private const HIGH_SAMPLE_RATE_HZ = 96_000;
	private const EXCESSIVE_SAMPLE_RATE_HZ = 192_001;
	private const EXCESSIVE_WAVEFORM_POINT_COUNT = 1_201;

	public function testConfigurationNormalizesUrlAndCreatesEndpoint(): void
	{
		$configuration = new AudioAnalysisConfiguration('http://analysis:8000/', 4, 90);

		$this->assertSame('http://analysis:8000', $configuration->getBaseUrl());
		$this->assertSame(4, $configuration->getConnectionTimeoutSeconds());
		$this->assertSame(90, $configuration->getRequestTimeoutSeconds());
		$this->assertSame('http://analysis:8000/v1/analyses', $configuration->createEndpoint('/v1/analyses'));
	}

	public function testConfigurationRejectsInvalidUrl(): void
	{
		$this->expectException(InvalidArgumentException::class);

		new AudioAnalysisConfiguration('not-a-url', 4, 90);
	}

	public function testConfigurationRejectsNonHttpUrl(): void
	{
		$this->expectException(InvalidArgumentException::class);

		new AudioAnalysisConfiguration('ftp://analysis.example/audio', 4, 90);
	}

	public function testConfigurationRejectsNonPositiveTimeout(): void
	{
		$this->expectException(InvalidArgumentException::class);

		new AudioAnalysisConfiguration('http://analysis:8000', 0, 90);
	}

	public function testConfigurationRejectsRequestTimeoutAbovePublicBoundary(): void
	{
		$this->expectException(InvalidArgumentException::class);

		new AudioAnalysisConfiguration(
			'http://analysis:8000',
			4,
			AudioAnalysisConfiguration::DEFAULT_REQUEST_TIMEOUT_SECONDS + 1
		);
	}

	public function testUploadedAudioExposesTransportMetadata(): void
	{
		$audio = new UploadedAudio('temporary-audio', 'sample.wav', 'audio/wav');

		$this->assertSame('temporary-audio', $audio->getTemporaryPath());
		$this->assertSame('sample.wav', $audio->getOriginalFileName());
		$this->assertSame('audio/wav', $audio->getMimeType());
	}

	public function testAnalysisResponsePreservesValidAnalysisPayload(): void
	{
		$payload = AudioAnalysisPayloadFixture::analysis();

		$response = AudioAnalysisResponse::fromPayload($payload);

		$this->assertSame($payload, $response->toArray());
	}

	public function testAnalysisResponseAcceptsHighInputSampleRate(): void
	{
		$payload = AudioAnalysisPayloadFixture::analysis();
		$payload['recording']['sample_rate_hz'] = self::HIGH_SAMPLE_RATE_HZ;

		$response = AudioAnalysisResponse::fromPayload($payload);

		$this->assertSame(self::HIGH_SAMPLE_RATE_HZ, $response->toArray()['recording']['sample_rate_hz']);
	}

	public function testAnalysisResponseRejectsExcessiveInputSampleRate(): void
	{
		$payload = AudioAnalysisPayloadFixture::analysis();
		$payload['recording']['sample_rate_hz'] = self::EXCESSIVE_SAMPLE_RATE_HZ;

		$this->expectException(InvalidAudioAnalysisResponseException::class);

		AudioAnalysisResponse::fromPayload($payload);
	}

	public function testComparisonResponseAcceptsSnapshotsWithoutNotice(): void
	{
		$payload = AudioAnalysisPayloadFixture::comparison();

		$response = AudioAnalysisResponse::fromPayload($payload);

		$this->assertSame($payload, $response->toArray());
	}

	public function testResponseRejectsResultWrapper(): void
	{
		$this->expectException(InvalidAudioAnalysisResponseException::class);

		AudioAnalysisResponse::fromPayload(['result' => AudioAnalysisPayloadFixture::analysis()]);
	}

	public function testResponseRejectsIncompleteComparisonSnapshot(): void
	{
		$payload = AudioAnalysisPayloadFixture::comparison();
		unset($payload['baseline']['measurements']);

		$this->expectException(InvalidAudioAnalysisResponseException::class);

		AudioAnalysisResponse::fromPayload($payload);
	}

	public function testResponseRejectsNonUuidIdentifier(): void
	{
		$payload = AudioAnalysisPayloadFixture::analysis('predictable-internal-id');

		$this->expectException(InvalidAudioAnalysisResponseException::class);

		AudioAnalysisResponse::fromPayload($payload);
	}

	public function testResponseRejectsMalformedMeasurement(): void
	{
		$payload = AudioAnalysisPayloadFixture::analysis();
		unset($payload['measurements'][0]['unit']);

		$this->expectException(InvalidAudioAnalysisResponseException::class);

		AudioAnalysisResponse::fromPayload($payload);
	}

	public function testResponseRejectsUnboundedChartPayload(): void
	{
		$payload = AudioAnalysisPayloadFixture::analysis();
		$payload['charts']['waveform'] = array_fill(
			0,
			self::EXCESSIVE_WAVEFORM_POINT_COUNT,
			$payload['charts']['waveform'][0]
		);

		$this->expectException(InvalidAudioAnalysisResponseException::class);

		AudioAnalysisResponse::fromPayload($payload);
	}

	public function testResponseRejectsUnavailableTrackWithNonZeroPlaceholder(): void
	{
		$payload = AudioAnalysisPayloadFixture::analysis();
		$payload['charts']['tracks'][0]['fundamental_frequency_available'] = false;

		$this->expectException(InvalidAudioAnalysisResponseException::class);

		AudioAnalysisResponse::fromPayload($payload);
	}
}
