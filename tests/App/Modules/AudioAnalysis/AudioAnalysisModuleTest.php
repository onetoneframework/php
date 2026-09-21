<?php

declare(strict_types=1);

namespace Clover\Tests\App\Modules\AudioAnalysis;

use App\Modules\AudioAnalysis\AudioAnalysisModule;
use App\Modules\AudioAnalysis\Contract\AudioAnalysisGatewayInterface;
use App\Modules\AudioAnalysis\Contract\AudioAnalysisFailureLoggerInterface;
use App\Modules\AudioAnalysis\Contract\UploadedAudioProviderInterface;
use App\Modules\AudioAnalysis\Contract\UploadedFileInspectorInterface;
use App\Modules\AudioAnalysis\DataTransferObject\AudioAnalysisConfiguration;
use App\Modules\AudioAnalysis\Infrastructure\FastApiAudioAnalysisGateway;
use App\Modules\AudioAnalysis\Infrastructure\FrameworkAudioAnalysisFailureLogger;
use App\Modules\AudioAnalysis\Infrastructure\PhpUploadedAudioProvider;
use App\Modules\AudioAnalysis\Infrastructure\PhpUploadedFileInspector;
use Clover\Classes\DependencyInjection\Container;
use Clover\Classes\Logging\Logger;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

use function array_key_exists;
use function sys_get_temp_dir;

final class AudioAnalysisModuleTest extends TestCase
{
	private const ENVIRONMENT_VALUES = [
		'ACOUSTIC_ANALYSIS_BASE_URL' => 'http://local-analysis:8100',
		'ACOUSTIC_ANALYSIS_CONNECT_TIMEOUT_SECONDS' => '5',
		'ACOUSTIC_ANALYSIS_REQUEST_TIMEOUT_SECONDS' => '17',
		'ACOUSTIC_ANALYSIS_MAX_UPLOAD_BYTES' => '2048',
	];

	/** @var array<string, mixed> */
	private array $previousEnvironmentValues = [];
	/** @var array<string, bool> */
	private array $previousEnvironmentPresence = [];

	protected function setUp(): void
	{
		foreach (self::ENVIRONMENT_VALUES as $name => $value) {
			$this->previousEnvironmentPresence[$name] = array_key_exists($name, $_ENV);
			$this->previousEnvironmentValues[$name] = $_ENV[$name] ?? false;
			$_ENV[$name] = $value;
		}
	}

	protected function tearDown(): void
	{
		foreach (self::ENVIRONMENT_VALUES as $name => $value) {
			if ($this->previousEnvironmentPresence[$name]) {
				$_ENV[$name] = $this->previousEnvironmentValues[$name];
			} else {
				unset($_ENV[$name]);
			}
		}
	}

	public function testDeclaresModuleIdentityAndExports(): void
	{
		$module = new AudioAnalysisModule($this->getModulePath());

		$this->assertSame('audio-analysis', $module->getName());
		$this->assertSame([
			AudioAnalysisGatewayInterface::class,
			UploadedAudioProviderInterface::class,
		], $module->getExportedServiceIdentifiers());
	}

	public function testRegistersMethodInjectableServices(): void
	{
		$container = $this->createContainer();
		$module = new AudioAnalysisModule($this->getModulePath());

		$module->register($container);

		$configuration = $container->get(AudioAnalysisConfiguration::class);
		$gateway = $container->get(AudioAnalysisGatewayInterface::class);
		$provider = $container->get(UploadedAudioProviderInterface::class);
		$inspector = $container->get(UploadedFileInspectorInterface::class);
		$failureLogger = $container->get(AudioAnalysisFailureLoggerInterface::class);
		$this->assertInstanceOf(AudioAnalysisConfiguration::class, $configuration);
		$this->assertSame('http://local-analysis:8100', $configuration->getBaseUrl());
		$this->assertSame(5, $configuration->getConnectionTimeoutSeconds());
		$this->assertSame(17, $configuration->getRequestTimeoutSeconds());
		$this->assertInstanceOf(FastApiAudioAnalysisGateway::class, $gateway);
		$this->assertInstanceOf(PhpUploadedAudioProvider::class, $provider);
		$this->assertInstanceOf(PhpUploadedFileInspector::class, $inspector);
		$this->assertInstanceOf(FrameworkAudioAnalysisFailureLogger::class, $failureLogger);
		$this->assertSame($gateway, $container->getByType(AudioAnalysisGatewayInterface::class));
		$this->assertSame($provider, $container->getByType(UploadedAudioProviderInterface::class));
	}

	public function testRejectsInvalidMaximumUploadEnvironmentValue(): void
	{
		$_ENV['ACOUSTIC_ANALYSIS_MAX_UPLOAD_BYTES'] = 'invalid';
		$container = $this->createContainer();
		$module = new AudioAnalysisModule($this->getModulePath());

		$this->expectException(RuntimeException::class);

		$module->register($container);
	}

	public function testRejectsMaximumUploadEnvironmentValueAbovePublicBoundary(): void
	{
		$_ENV['ACOUSTIC_ANALYSIS_MAX_UPLOAD_BYTES'] = (string) (
			PhpUploadedAudioProvider::DEFAULT_MAXIMUM_SIZE_BYTES + 1
		);
		$container = $this->createContainer();
		$module = new AudioAnalysisModule($this->getModulePath());

		$this->expectException(InvalidArgumentException::class);

		$module->register($container);
	}

	public function testRejectsRequestTimeoutEnvironmentValueAbovePublicBoundary(): void
	{
		$_ENV['ACOUSTIC_ANALYSIS_REQUEST_TIMEOUT_SECONDS'] = (string) (
			AudioAnalysisConfiguration::DEFAULT_REQUEST_TIMEOUT_SECONDS + 1
		);
		$container = $this->createContainer();
		$module = new AudioAnalysisModule($this->getModulePath());

		$this->expectException(InvalidArgumentException::class);

		$module->register($container);
	}

	private function getModulePath(): string
	{
		return dirname(__DIR__, 4)
			. DIRECTORY_SEPARATOR . 'root'
			. DIRECTORY_SEPARATOR . 'App'
			. DIRECTORY_SEPARATOR . 'Modules'
			. DIRECTORY_SEPARATOR . 'AudioAnalysis';
	}

	private function createContainer(): Container
	{
		$container = new Container();
		$container->set(Logger::class, new Logger(
			fileLocation: sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'audio-analysis-module-test.log'
		));
		return $container;
	}
}
