<?php

declare(strict_types=1);

namespace App\Modules\AudioAnalysis\Controller;

use App\Modules\AudioAnalysis\Contract\AudioAnalysisGatewayInterface;
use App\Modules\AudioAnalysis\Contract\AudioAnalysisFailureLoggerInterface;
use App\Modules\AudioAnalysis\Contract\UploadedAudioProviderInterface;
use App\Modules\AudioAnalysis\Enumeration\AudioAnalysisErrorCode;
use App\Modules\AudioAnalysis\Exception\AudioAnalysisTimeoutException;
use App\Modules\AudioAnalysis\Exception\AudioAnalysisUnavailableException;
use App\Modules\AudioAnalysis\Exception\AudioUploadException;
use App\Modules\AudioAnalysis\Exception\InvalidAudioAnalysisResponseException;
use Clover\Annotation\Prefix;
use Clover\Annotation\Route;
use Clover\Enumeration\HTTPStatusCode;
use Clover\Framework\Component\BaseController;
use Clover\Framework\Component\Response;
use Clover\Framework\Component\TraceContext;

#[Prefix('/api/audio-analysis')]
final class AudioAnalysisController extends BaseController
{
	private const ANALYSIS_AUDIO_FIELD = 'audio';
	private const BASELINE_AUDIO_FIELD = 'baseline_audio';
	private const CURRENT_AUDIO_FIELD = 'current_audio';

	#[Route('POST', '/analyses')]
	public function analyze(
		AudioAnalysisGatewayInterface $audioAnalysisGateway,
		UploadedAudioProviderInterface $uploadedAudioProvider,
		AudioAnalysisFailureLoggerInterface $failureLogger
	): Response {
		try {
			$audio = $uploadedAudioProvider->get(self::ANALYSIS_AUDIO_FIELD);
			$response = $audioAnalysisGateway->analyze($audio);
		} catch (
			AudioUploadException|
			AudioAnalysisTimeoutException|
			AudioAnalysisUnavailableException|
			InvalidAudioAnalysisResponseException $exception
		) {
			return $this->createErrorResponse($exception, $failureLogger);
		}

		return $this->responseJson($response->toArray());
	}

	#[Route('POST', '/comparisons')]
	public function compare(
		AudioAnalysisGatewayInterface $audioAnalysisGateway,
		UploadedAudioProviderInterface $uploadedAudioProvider,
		AudioAnalysisFailureLoggerInterface $failureLogger
	): Response {
		try {
			$baselineAudio = $uploadedAudioProvider->get(self::BASELINE_AUDIO_FIELD);
			$currentAudio = $uploadedAudioProvider->get(self::CURRENT_AUDIO_FIELD);
			$response = $audioAnalysisGateway->compare($baselineAudio, $currentAudio);
		} catch (
			AudioUploadException|
			AudioAnalysisTimeoutException|
			AudioAnalysisUnavailableException|
			InvalidAudioAnalysisResponseException $exception
		) {
			return $this->createErrorResponse($exception, $failureLogger);
		}

		return $this->responseJson($response->toArray());
	}

	private function createErrorResponse(
		AudioUploadException|
		AudioAnalysisTimeoutException|
		AudioAnalysisUnavailableException|
		InvalidAudioAnalysisResponseException $exception,
		AudioAnalysisFailureLoggerInterface $failureLogger
	): Response {
		$correlationIdentifier = TraceContext::getTraceId();
		$failureLogger->log($exception, $correlationIdentifier);
		$statusCode = match (true) {
			$exception instanceof AudioUploadException => $exception->getStatusCode(),
			$exception instanceof AudioAnalysisTimeoutException => HTTPStatusCode::GATEWAY_TIMEOUT,
			$exception instanceof AudioAnalysisUnavailableException => HTTPStatusCode::SERVICE_UNAVAILABLE,
			default => HTTPStatusCode::BAD_GATEWAY,
		};

		$message = match (true) {
			$exception instanceof AudioUploadException => $exception->getMessage(),
			$exception instanceof AudioAnalysisTimeoutException => 'The acoustic analysis service timed out.',
			$exception instanceof AudioAnalysisUnavailableException => 'The acoustic analysis service is unavailable.',
			default => 'The acoustic analysis service returned an invalid response.',
		};
		$errorCode = match (true) {
			$exception instanceof AudioUploadException => AudioAnalysisErrorCode::UPLOAD_REJECTED,
			$exception instanceof AudioAnalysisTimeoutException => AudioAnalysisErrorCode::SERVICE_TIMEOUT,
			$exception instanceof AudioAnalysisUnavailableException => AudioAnalysisErrorCode::SERVICE_UNAVAILABLE,
			default => AudioAnalysisErrorCode::INVALID_SERVICE_RESPONSE,
		};

		return $this->responseJson([
			'error' => [
				'code' => $errorCode->value,
				'message' => $message,
				'correlation_id' => $correlationIdentifier,
			],
		])->setStatusCode($statusCode);
	}
}
