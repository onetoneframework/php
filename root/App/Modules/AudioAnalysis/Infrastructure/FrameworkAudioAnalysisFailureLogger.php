<?php

declare(strict_types=1);

namespace App\Modules\AudioAnalysis\Infrastructure;

use App\Modules\AudioAnalysis\Contract\AudioAnalysisFailureLoggerInterface;
use App\Modules\AudioAnalysis\Exception\AudioUploadException;
use App\Modules\AudioAnalysis\Exception\InvalidAudioAnalysisResponseException;
use Clover\Classes\Logging\Logger;
use RuntimeException;

final class FrameworkAudioAnalysisFailureLogger implements AudioAnalysisFailureLoggerInterface
{
	private const LOG_NAMESPACE = 'audio-analysis';

	public function __construct(private Logger $logger)
	{
	}

	public function log(RuntimeException $exception, string $correlationIdentifier): void
	{
		$context = [
			'correlation_id' => $correlationIdentifier,
			'exception_type' => $exception::class,
		];

		if ($exception instanceof AudioUploadException) {
			$this->logger->information('An acoustic analysis upload was rejected.', self::LOG_NAMESPACE, $context);
			return;
		}

		if ($exception instanceof InvalidAudioAnalysisResponseException) {
			$this->logger->throwable($exception, self::LOG_NAMESPACE, $context);
			return;
		}

		$this->logger->warning('The acoustic analysis service could not complete a request.', self::LOG_NAMESPACE, $context);
	}
}
