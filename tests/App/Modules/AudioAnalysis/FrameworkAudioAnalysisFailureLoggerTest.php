<?php

declare(strict_types=1);

namespace Clover\Tests\App\Modules\AudioAnalysis;

use App\Modules\AudioAnalysis\Exception\AudioAnalysisTimeoutException;
use App\Modules\AudioAnalysis\Exception\AudioUploadException;
use App\Modules\AudioAnalysis\Exception\InvalidAudioAnalysisResponseException;
use App\Modules\AudioAnalysis\Infrastructure\FrameworkAudioAnalysisFailureLogger;
use Clover\Classes\Logging\Logger;
use Clover\Enumeration\HTTPStatusCode;
use PHPUnit\Framework\TestCase;
use Throwable;

final class FrameworkAudioAnalysisFailureLoggerTest extends TestCase
{
	private const CORRELATION_IDENTIFIER = 'trace-identifier';

	public function testLogsRejectedUploadAtInformationLevel(): void
	{
		$frameworkLogger = new AudioAnalysisFrameworkLoggerFake();
		$logger = new FrameworkAudioAnalysisFailureLogger($frameworkLogger);

		$logger->log(
			new AudioUploadException('Rejected.', HTTPStatusCode::UNSUPPORTED_MEDIA_TYPE),
			self::CORRELATION_IDENTIFIER
		);

		$this->assertSame(['information'], $frameworkLogger->levels);
		$this->assertSame(self::CORRELATION_IDENTIFIER, $frameworkLogger->contexts[0]['correlation_id']);
	}

	public function testLogsServiceFailureAtWarningLevel(): void
	{
		$frameworkLogger = new AudioAnalysisFrameworkLoggerFake();
		$logger = new FrameworkAudioAnalysisFailureLogger($frameworkLogger);

		$logger->log(new AudioAnalysisTimeoutException('Timeout.'), self::CORRELATION_IDENTIFIER);

		$this->assertSame(['warning'], $frameworkLogger->levels);
		$this->assertSame(self::CORRELATION_IDENTIFIER, $frameworkLogger->contexts[0]['correlation_id']);
	}

	public function testLogsInvalidResponseWithThrowableContext(): void
	{
		$frameworkLogger = new AudioAnalysisFrameworkLoggerFake();
		$logger = new FrameworkAudioAnalysisFailureLogger($frameworkLogger);
		$exception = new InvalidAudioAnalysisResponseException('Invalid response.');

		$logger->log($exception, self::CORRELATION_IDENTIFIER);

		$this->assertSame(['throwable'], $frameworkLogger->levels);
		$this->assertSame([$exception], $frameworkLogger->throwables);
		$this->assertSame(self::CORRELATION_IDENTIFIER, $frameworkLogger->contexts[0]['correlation_id']);
	}
}

final class AudioAnalysisFrameworkLoggerFake extends Logger
{
	/** @var string[] */
	public array $levels = [];
	/** @var array<int, array<string, mixed>> */
	public array $contexts = [];
	/** @var Throwable[] */
	public array $throwables = [];

	public function __construct()
	{
	}

	public function information(string|array $content, ?string $namespace = null, array $context = []): void
	{
		$this->levels[] = 'information';
		$this->contexts[] = $context;
	}

	public function warning(string|array $content, ?string $namespace = null, array $context = []): void
	{
		$this->levels[] = 'warning';
		$this->contexts[] = $context;
	}

	public function throwable(Throwable $throwable, ?string $namespace = null, array $context = []): void
	{
		$this->levels[] = 'throwable';
		$this->contexts[] = $context;
		$this->throwables[] = $throwable;
	}
}
