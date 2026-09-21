<?php

declare(strict_types=1);

use App\Modules\AudioAnalysis\Contract\AudioAnalysisGatewayInterface;
use App\Modules\AudioAnalysis\Contract\AudioAnalysisFailureLoggerInterface;
use App\Modules\AudioAnalysis\Contract\UploadedAudioProviderInterface;
use App\Modules\AudioAnalysis\Contract\UploadedFileInspectorInterface;
use App\Modules\AudioAnalysis\DataTransferObject\AudioAnalysisConfiguration;
use App\Modules\AudioAnalysis\Infrastructure\FastApiAudioAnalysisGateway;
use App\Modules\AudioAnalysis\Infrastructure\FrameworkAudioAnalysisFailureLogger;
use App\Modules\AudioAnalysis\Infrastructure\PhpUploadedAudioProvider;
use App\Modules\AudioAnalysis\Infrastructure\PhpUploadedFileInspector;
use Clover\Classes\ClientURL;
use Clover\Classes\DependencyInjection\Container;
use Clover\Classes\Logging\Logger;
use Clover\Implement\ClientURLInterface;

return static function (Container $container): void {
	$readEnvironment = static function (string $name, string $defaultValue): string {
		if (array_key_exists($name, $_ENV)) {
			if (!is_string($_ENV[$name]) || trim($_ENV[$name]) === '') {
				throw new RuntimeException('An acoustic analysis environment value is invalid.');
			}

			return trim($_ENV[$name]);
		}

		$value = getenv($name);
		if ($value === false) {
			return $defaultValue;
		}

		if (trim($value) === '') {
			throw new RuntimeException('An acoustic analysis environment value is invalid.');
		}

		return trim($value);
	};

	$readPositiveInteger = static function (string $name, int $defaultValue) use ($readEnvironment): int {
		$value = filter_var($readEnvironment($name, (string) $defaultValue), FILTER_VALIDATE_INT);
		if (!is_int($value) || $value <= 0) {
			throw new RuntimeException('An acoustic analysis numeric environment value is invalid.');
		}

		return $value;
	};

	$configuration = new AudioAnalysisConfiguration(
		$readEnvironment('ACOUSTIC_ANALYSIS_BASE_URL', AudioAnalysisConfiguration::DEFAULT_BASE_URL),
		$readPositiveInteger(
			'ACOUSTIC_ANALYSIS_CONNECT_TIMEOUT_SECONDS',
			AudioAnalysisConfiguration::DEFAULT_CONNECTION_TIMEOUT_SECONDS
		),
		$readPositiveInteger(
			'ACOUSTIC_ANALYSIS_REQUEST_TIMEOUT_SECONDS',
			AudioAnalysisConfiguration::DEFAULT_REQUEST_TIMEOUT_SECONDS
		)
	);
	$maximumUploadBytes = $readPositiveInteger(
		'ACOUSTIC_ANALYSIS_MAX_UPLOAD_BYTES',
		PhpUploadedAudioProvider::DEFAULT_MAXIMUM_SIZE_BYTES
	);
	$clientFactory = static function (string $url): ClientURLInterface {
		return new ClientURL($url);
	};
	$uploadedFileInspector = new PhpUploadedFileInspector();
	$uploadedAudioProvider = new PhpUploadedAudioProvider($uploadedFileInspector, $maximumUploadBytes);
	$audioAnalysisGateway = new FastApiAudioAnalysisGateway($configuration, $clientFactory);
	$frameworkLogger = $container->get(Logger::class);
	if (!$frameworkLogger instanceof Logger) {
		throw new RuntimeException('The framework logger is unavailable.');
	}
	$failureLogger = new FrameworkAudioAnalysisFailureLogger($frameworkLogger);

	if (!$container->set(AudioAnalysisConfiguration::class, $configuration)) {
		throw new RuntimeException('The acoustic analysis configuration could not be registered.');
	}

	if (!$container->set(PhpUploadedFileInspector::class, $uploadedFileInspector)) {
		throw new RuntimeException('The uploaded file inspector could not be registered.');
	}

	if (!$container->set(PhpUploadedAudioProvider::class, $uploadedAudioProvider)) {
		throw new RuntimeException('The uploaded audio provider could not be registered.');
	}

	if (!$container->set(FastApiAudioAnalysisGateway::class, $audioAnalysisGateway)) {
		throw new RuntimeException('The acoustic analysis gateway could not be registered.');
	}

	if (!$container->set(FrameworkAudioAnalysisFailureLogger::class, $failureLogger)) {
		throw new RuntimeException('The acoustic analysis failure logger could not be registered.');
	}

	$container->bind(UploadedFileInspectorInterface::class, PhpUploadedFileInspector::class);
	$container->bind(UploadedAudioProviderInterface::class, PhpUploadedAudioProvider::class);
	$container->bind(AudioAnalysisGatewayInterface::class, FastApiAudioAnalysisGateway::class);
	$container->bind(AudioAnalysisFailureLoggerInterface::class, FrameworkAudioAnalysisFailureLogger::class);
};
