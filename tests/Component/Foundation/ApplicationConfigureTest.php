<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Tests\Component\Foundation;

use Clover\Component\Foundation\Application;
use Clover\Contract\KernelInterface;
use PHPUnit\Framework\TestCase;

/**
 * `Application::configure()` is the entry point's constructor - the analogue of Laravel's
 * `bootstrap/app.php`. It reads the provider list and runs the file where an application binds its
 * own kernel, and both have to happen before any kernel is resolved.
 */
final class ApplicationConfigureTest extends TestCase
{
	/** @var array<int, string> Paths created by the running test. */
	private array $paths = [];

	protected function tearDown(): void
	{
		Application::setInstance(null);

		foreach (array_reverse($this->paths) as $path) {
			if (is_file($path)) {
				unlink($path);
				continue;
			}

			if (is_dir($path)) {
				rmdir($path);
			}
		}

		$this->paths = [];
	}

	public function testProviderFileBecomesTheDeclaredProviderList(): void
	{
		$base = $this->makeApplicationTree([
			'providers.php' => '<?php return ["Alpha\\\\Provider", "Beta\\\\Provider"];',
		]);

		$application = Application::configure($base);

		$this->assertSame(['Alpha\\Provider', 'Beta\\Provider'], $application->getDeclaredProviders());
	}

	public function testKernelFileCanReplaceTheFrameworkKernel(): void
	{
		$replacement = ConfigureTestKernel::class;
		$base = $this->makeApplicationTree([
			'kernel.php' => '<?php return static function ($app): void {'
				. ' $app->singleton(' . var_export(KernelInterface::class, true) . ','
				. ' new ' . $replacement . '($app)); };',
		]);

		$application = Application::configure($base);

		$this->assertInstanceOf($replacement, $application->make(KernelInterface::class));
	}

	/**
	 * An application that adds neither file is a new application, not a broken one.
	 */
	public function testMissingConfigurationFilesLeaveTheFrameworkDefaults(): void
	{
		$base = $this->makeApplicationTree([]);

		$application = Application::configure($base);

		$this->assertSame([], $application->getDeclaredProviders());
		$this->assertInstanceOf(
			\Clover\Component\Kernel\HttpKernel::class,
			$application->make(KernelInterface::class)
		);
	}

	public function testPathsAreDerivedFromTheBasePath(): void
	{
		$base = $this->makeApplicationTree([]);
		$application = Application::configure($base);

		$separator = DIRECTORY_SEPARATOR;

		$this->assertSame($base . $separator . 'App', $application->getApplicationPath());
		$this->assertSame($base . $separator . 'App' . $separator . 'Configure', $application->getConfigurePath());
		$this->assertSame($base . $separator . 'App' . $separator . 'Cache', $application->getStoragePath());
		$this->assertSame($base, $application->getEnvironmentPath());
	}

	public function testProviderConfigurationThatDoesNotReturnArrayIsIgnored(): void
	{
		$base = $this->makeApplicationTree([
			'providers.php' => '<?php return "not-an-array";',
		]);

		$application = Application::configure($base);

		$this->assertSame([], $application->getDeclaredProviders());
	}

	public function testKernelConfigurationThatDoesNotReturnCallableLeavesDefaultKernel(): void
	{
		$base = $this->makeApplicationTree([
			'kernel.php' => '<?php return ["not", "callable"];',
		]);

		$application = Application::configure($base);

		$this->assertInstanceOf(
			\Clover\Component\Kernel\HttpKernel::class,
			$application->make(KernelInterface::class)
		);
	}

	public function testConfiguredPathsAreBoundIntoTheContainer(): void
	{
		$base = $this->makeApplicationTree([]);
		$application = Application::configure($base);
		$separator = DIRECTORY_SEPARATOR;

		$this->assertSame($base, $application->make('path'));
		$this->assertSame($base, $application->make('path.base'));
		$this->assertSame($base . $separator . 'config', $application->make('path.config'));
		$this->assertSame($base . $separator . 'App', $application->make('path.app'));
		$this->assertSame(
			$base . $separator . 'App' . $separator . 'Configure',
			$application->make('path.configure')
		);
		$this->assertSame(
			$base . $separator . 'App' . $separator . 'Cache',
			$application->make('path.storage')
		);
	}

	/**
	 * Create `<temp>/<unique>/App/Configure` holding the given files.
	 *
	 * @param array<string, string> $files Filename to contents.
	 *
	 * @return string The base path.
	 */
	private function makeApplicationTree(array $files): string
	{
		$base = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('clover_app_', true);
		$configure = $base . DIRECTORY_SEPARATOR . 'App' . DIRECTORY_SEPARATOR . 'Configure';

		mkdir($configure, 0o777, true);
		$this->paths[] = $base;
		$this->paths[] = $base . DIRECTORY_SEPARATOR . 'App';
		$this->paths[] = $configure;

		foreach ($files as $name => $contents) {
			$path = $configure . DIRECTORY_SEPARATOR . $name;
			file_put_contents($path, $contents);
			$this->paths[] = $path;
		}

		return $base;
	}
}

class ConfigureTestKernel extends \Clover\Component\Kernel\HttpKernel
{
	protected array $bootstrappers = [];
}
