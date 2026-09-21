<?php

declare(strict_types=1);

namespace App\Modules\AudioAnalysis\Infrastructure;

use App\Modules\AudioAnalysis\Contract\AudioAnalysisGatewayInterface;
use App\Modules\AudioAnalysis\DataTransferObject\AudioAnalysisConfiguration;
use App\Modules\AudioAnalysis\DataTransferObject\AudioAnalysisResponse;
use App\Modules\AudioAnalysis\DataTransferObject\UploadedAudio;
use App\Modules\AudioAnalysis\Exception\AudioAnalysisTimeoutException;
use App\Modules\AudioAnalysis\Exception\AudioAnalysisUnavailableException;
use App\Modules\AudioAnalysis\Exception\AudioUploadException;
use App\Modules\AudioAnalysis\Exception\InvalidAudioAnalysisResponseException;
use Closure;
use Clover\Classes\Data\JSONHandler;
use Clover\Classes\Data\StringObject;
use Clover\Enumeration\HTTPStatusCode;
use Clover\Implement\ClientURLInterface;
use Exception;

use function ctype_digit;
use function in_array;
use function is_array;
use function is_int;
use function is_object;
use function is_string;
use function method_exists;
use function strlen;
use function strtolower;
use function str_contains;
use function trim;

final class FastApiAudioAnalysisGateway implements AudioAnalysisGatewayInterface
{
	private const ANALYSIS_PATH = '/v1/analyses';
	private const COMPARISON_PATH = '/v1/comparisons';
	private const MINIMUM_SUCCESS_STATUS = 200;
	private const MAXIMUM_SUCCESS_STATUS = 299;
	private const CURL_OPERATION_TIMED_OUT = 28;
	private const MAXIMUM_UPSTREAM_ERROR_MESSAGE_BYTES = 300;
	private const SAFE_UPLOAD_ERROR_CODES = [
		FastApiAudioErrorCode::EMPTY_UPLOAD,
		FastApiAudioErrorCode::FILE_TOO_LARGE,
		FastApiAudioErrorCode::INVALID_WAVE,
		FastApiAudioErrorCode::UNSUPPORTED_MEDIA_TYPE,
		FastApiAudioErrorCode::UNSUPPORTED_RECORDING,
		FastApiAudioErrorCode::ANALYSIS_REJECTED,
		FastApiAudioErrorCode::INVALID_REQUEST,
	];

	private const REQUIRED_OPTION_METHODS = [
		'setURL',
		'setHeaders',
		'setPostMethod',
		'setReturnTransfer',
		'setConnectionTimeout',
		'setTimeout',
		'setPostFields',
		'setPostFileField',
	];

	/**
	 * @param Closure(string): ClientURLInterface $clientFactory
	 */
	public function __construct(
		private AudioAnalysisConfiguration $configuration,
		private Closure $clientFactory
	) {
	}

	public function analyze(UploadedAudio $audio): AudioAnalysisResponse
	{
		return $this->sendMultipart(
			self::ANALYSIS_PATH,
			['audio' => $audio]
		);
	}

	public function compare(UploadedAudio $baselineAudio, UploadedAudio $currentAudio): AudioAnalysisResponse
	{
		return $this->sendMultipart(
			self::COMPARISON_PATH,
			[
				'baseline_audio' => $baselineAudio,
				'current_audio' => $currentAudio,
			]
		);
	}

	/**
	 * @param array<string, UploadedAudio> $audioFields
	 */
	private function sendMultipart(string $path, array $audioFields): AudioAnalysisResponse
	{
		$endpoint = $this->configuration->createEndpoint($path);
		$client = $this->createClient($endpoint);

		try {
			$options = $client->option();
			if (!is_object($options)) {
				throw new InvalidAudioAnalysisResponseException('The HTTP client options are unavailable.');
			}

			$this->configureOptions($options, $endpoint, $audioFields);

			try {
				$rawResponse = $client->execute();
			} catch (Exception $exception) {
				throw $this->mapTransportException($client, $exception);
			}

			$statusCode = $this->readStatusCode($client);
			if ($statusCode === HTTPStatusCode::REQUEST_ENTITY_TOO_LARGE) {
				throw new AudioUploadException(
					'The audio upload exceeds the configured size limit.',
					HTTPStatusCode::REQUEST_ENTITY_TOO_LARGE
				);
			}

			if ($statusCode === HTTPStatusCode::UNSUPPORTED_MEDIA_TYPE) {
				throw new AudioUploadException(
					'Only WAV audio uploads are supported.',
					HTTPStatusCode::UNSUPPORTED_MEDIA_TYPE
				);
			}

			if ($statusCode === HTTPStatusCode::UNPROCESSABLE_ENTITY) {
				throw new AudioUploadException(
					$this->resolveUploadErrorMessage(
						$rawResponse,
						'The WAV file could not be analyzed. Use PCM or 32-bit float WAV with one or two channels, 16–192 kHz, and 0.25 seconds–1 hour of non-silent audio.'
					),
					HTTPStatusCode::UNPROCESSABLE_ENTITY
				);
			}

			if ($statusCode < self::MINIMUM_SUCCESS_STATUS || $statusCode > self::MAXIMUM_SUCCESS_STATUS) {
				throw new AudioAnalysisUnavailableException('The acoustic analysis service rejected the request.');
			}

			return $this->decodeResponse($rawResponse);
		} finally {
			$client->close();
		}
	}

	private function createClient(string $endpoint): ClientURLInterface
	{
		try {
			$client = ($this->clientFactory)($endpoint);
		} catch (Exception $exception) {
			throw new AudioAnalysisUnavailableException(
				'The acoustic analysis service client could not be initialized.',
				0,
				$exception
			);
		}

		if (!$client instanceof ClientURLInterface) {
			throw new AudioAnalysisUnavailableException('The acoustic analysis service client is invalid.');
		}

		return $client;
	}

	/**
	 * @param array<string, UploadedAudio> $audioFields
	 */
	private function configureOptions(object $options, string $endpoint, array $audioFields): void
	{
		foreach (self::REQUIRED_OPTION_METHODS as $methodName) {
			if (!method_exists($options, $methodName)) {
				throw new InvalidAudioAnalysisResponseException('The HTTP client does not support multipart requests.');
			}
		}

		$options->setURL($endpoint);
		$options->setHeaders(['Accept: application/json']);
		$options->setPostMethod(true);
		$options->setReturnTransfer(true);
		$options->setConnectionTimeout($this->configuration->getConnectionTimeoutSeconds());
		$options->setTimeout($this->configuration->getRequestTimeoutSeconds());
		$options->setPostFields([]);

		foreach ($audioFields as $fieldName => $audio) {
			$options->setPostFileField(
				$fieldName,
				$audio->getTemporaryPath(),
				$audio->getMimeType(),
				$audio->getOriginalFileName()
			);
		}
	}

	private function readStatusCode(ClientURLInterface $client): int
	{
		$information = $client->information();
		if (!is_object($information) || !method_exists($information, 'getStatusCode')) {
			throw new InvalidAudioAnalysisResponseException('The upstream HTTP status is unavailable.');
		}

		$statusCode = $information->getStatusCode();
		if (is_int($statusCode)) {
			return $statusCode;
		}

		if (is_string($statusCode) && ctype_digit($statusCode)) {
			return (int) $statusCode;
		}

		throw new InvalidAudioAnalysisResponseException('The upstream HTTP status is invalid.');
	}

	private function decodeResponse(mixed $rawResponse): AudioAnalysisResponse
	{
		$responseBody = $this->normalizeResponseBody($rawResponse);

		try {
			$decodedPayload = JSONHandler::decodeToArray($responseBody, true)->toPHPObject();
		} catch (Exception $exception) {
			throw new InvalidAudioAnalysisResponseException(
				'The acoustic analysis response is not valid JSON.',
				0,
				$exception
			);
		}

		if (!is_array($decodedPayload)) {
			throw new InvalidAudioAnalysisResponseException('The acoustic analysis response must be a JSON object.');
		}

		return AudioAnalysisResponse::fromPayload($decodedPayload);
	}

	private function normalizeResponseBody(mixed $rawResponse): string
	{
		if ($rawResponse instanceof StringObject) {
			return $rawResponse->__toString();
		}

		if (is_string($rawResponse)) {
			return $rawResponse;
		}

		throw new InvalidAudioAnalysisResponseException('The acoustic analysis response body is invalid.');
	}

	private function resolveUploadErrorMessage(mixed $rawResponse, string $fallbackMessage): string
	{
		try {
			$responseBody = $this->normalizeResponseBody($rawResponse);
			$decodedPayload = JSONHandler::decodeToArray($responseBody, true)->toPHPObject();
		} catch (Exception) {
			return $fallbackMessage;
		}

		if (!is_array($decodedPayload) || !isset($decodedPayload['error']) || !is_array($decodedPayload['error'])) {
			return $fallbackMessage;
		}

		$errorCodeValue = $decodedPayload['error']['code'] ?? '';
		$errorMessage = $decodedPayload['error']['message'] ?? '';
		if (!is_string($errorCodeValue) || !is_string($errorMessage)) {
			return $fallbackMessage;
		}

		$errorCode = FastApiAudioErrorCode::tryFrom($errorCodeValue);
		$normalizedMessage = trim($errorMessage);
		if (
			!in_array($errorCode, self::SAFE_UPLOAD_ERROR_CODES, true)
			|| $normalizedMessage === ''
			|| strlen($normalizedMessage) > self::MAXIMUM_UPSTREAM_ERROR_MESSAGE_BYTES
		) {
			return $fallbackMessage;
		}

		return $normalizedMessage;
	}

	private function mapTransportException(
		ClientURLInterface $client,
		Exception $exception
	): AudioAnalysisTimeoutException|AudioAnalysisUnavailableException {
		$errorNumber = $client->getLastErrorNumber();
		$isTimeoutNumber = $errorNumber === self::CURL_OPERATION_TIMED_OUT
			|| (is_string($errorNumber) && ctype_digit($errorNumber) && (int) $errorNumber === self::CURL_OPERATION_TIMED_OUT);
		$isTimeoutMessage = str_contains(strtolower($exception->getMessage()), 'timed out');

		if ($isTimeoutNumber || $isTimeoutMessage) {
			return new AudioAnalysisTimeoutException(
				'The acoustic analysis service timed out.',
				0,
				$exception
			);
		}

		return new AudioAnalysisUnavailableException(
			'The acoustic analysis service is unavailable.',
			0,
			$exception
		);
	}
}
