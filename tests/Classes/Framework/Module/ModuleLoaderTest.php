<?php

declare(strict_types=1);

namespace Clover\Tests\Framework\Module;

use Clover\Classes\DependencyInjection\Container;
use Clover\Classes\Directory\Handler as DirectoryHandler;
use Clover\Classes\File\Handler as FileHandler;
use Clover\Framework\Module\Exception\ModuleCircularDependencyException;
use Clover\Framework\Module\ModuleLoader;
use Clover\Framework\Module\ModuleManager;
use Clover\Framework\Module\ModuleRegistry;
use Clover\Implement\ModuleInterface;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use function sprintf;

final class ModuleLoaderTest extends TestCase
{
	private string $temporaryDirectory = '';

	protected function setUp(): void
	{
		$this->temporaryDirectory = DirectoryHandler::createTemporary('module-loader-');
	}

	protected function tearDown(): void
	{
		if ($this->temporaryDirectory !== '' && DirectoryHandler::exists($this->temporaryDirectory)) {
			DirectoryHandler::delete($this->temporaryDirectory);
		}
	}

	public function testLoadsModulesInDependencyOrder(): void
	{
		$this->createFixtureModule('board', []);
		$this->createFixtureModule('comment', ['board']);
		$this->writeModuleConfiguration([
			'enabled' => ['board', 'comment'],
			'disabled' => [],
			'order' => [],
		]);

		$registry = $this->loadModules();
		$loadedOrder = array_map(
			static fn(ModuleInterface $module): string => $module->getName(),
			$registry->all()
		);

		$this->assertSame(['board', 'comment'], $loadedOrder);
	}

	public function testDisabledModulesAreSkipped(): void
	{
		$this->createFixtureModule('board', []);
		$this->createFixtureModule('comment', ['board']);
		$this->writeModuleConfiguration([
			'enabled' => null,
			'disabled' => ['comment'],
			'order' => [],
		]);

		$registry = $this->loadModules();

		$this->assertTrue($registry->has('board'));
		$this->assertFalse($registry->has('comment'));
	}

	public function testMissingDependencyThrowsRuntimeException(): void
	{
		$this->createFixtureModule('comment', ['board']);
		$this->writeModuleConfiguration([
			'enabled' => ['comment'],
			'disabled' => [],
			'order' => [],
		]);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('requires module "board"');

		$this->loadModules();
	}

	public function testCircularDependencyThrowsDedicatedException(): void
	{
		$this->createFixtureModule('alpha', ['beta']);
		$this->createFixtureModule('beta', ['alpha']);
		$this->writeModuleConfiguration([
			'enabled' => ['alpha', 'beta'],
			'disabled' => [],
			'order' => [],
		]);

		$this->expectException(ModuleCircularDependencyException::class);

		$this->loadModules();
	}

	public function testInvalidDependencyConfigurationIsRejected(): void
	{
		$this->createFixtureModule('board', []);
		$this->writeModuleConfiguration([
			'enabled' => ['board'],
			'disabled' => [],
			'order' => [],
		]);
		$moduleDirectoryName = 'loader-'
			. str_replace(['.', '-'], '', basename($this->temporaryDirectory))
			. '-board';
		$dependencyConfigurationPath = $this->temporaryDirectory
			. DIRECTORY_SEPARATOR
			. 'Modules'
			. DIRECTORY_SEPARATOR
			. $moduleDirectoryName
			. DIRECTORY_SEPARATOR
			. 'Configure'
			. DIRECTORY_SEPARATOR
			. 'dependencies.php';
		$this->assertTrue(FileHandler::write($dependencyConfigurationPath, "<?php\n\nreturn [];\n"));

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('must return a callable');

		$this->loadModules();
	}

	public function testModuleManagerResolvesRegisteredServices(): void
	{
		$this->createFixtureModule('board', [], true);
		$this->writeModuleConfiguration([
			'enabled' => ['board'],
			'disabled' => [],
			'order' => [],
		]);

		$container = new Container();
		$registry = $this->loadModules($container);
		$moduleManager = $container->get(ModuleManager::class);
		$this->assertInstanceOf(ModuleManager::class, $moduleManager);
		$service = $moduleManager->service('board', BoardFixtureService::class);

		$this->assertInstanceOf(BoardFixtureService::class, $service);
		$this->assertSame('fixture', $service->label());
		$this->assertSame($registry, $container->get(ModuleRegistry::class));
		$this->assertSame($moduleManager, $container->get(ModuleManager::class));
	}

	public function testModuleManagerRejectsServicesThatAreNotExported(): void
	{
		$this->createFixtureModule('board', [], true);
		$this->writeModuleConfiguration([
			'enabled' => ['board'],
			'disabled' => [],
			'order' => [],
		]);

		$container = new Container();
		$registry = $this->loadModules($container);
		$moduleManager = new ModuleManager($registry, $container);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('is not exported');

		$moduleManager->service('board', ModuleLoaderTest::class);
	}

	/**
	 * @param string[] $dependencies
	 */
	private function createFixtureModule(string $moduleName, array $dependencies, bool $withService = false): void
	{
		$fixtureScope = str_replace(['.', '-'], '', basename($this->temporaryDirectory));
		$moduleDirectoryName = 'loader-' . $fixtureScope . '-' . $moduleName;
		$namespaceSegment = $this->toNamespaceSegment($moduleDirectoryName);
		$className = $namespaceSegment . 'Module';
		$moduleDirectory = $this->temporaryDirectory
			. DIRECTORY_SEPARATOR
			. 'Modules'
			. DIRECTORY_SEPARATOR
			. $moduleDirectoryName;
		$configureDirectory = $moduleDirectory . DIRECTORY_SEPARATOR . 'Configure';
		DirectoryHandler::ensureExists($configureDirectory, 0777);

		$dependencyExport = $this->exportDependencyList($dependencies);
		$exportedServiceIdentifiers = $withService
			? '[\\Clover\\Tests\\Framework\\Module\\BoardFixtureService::class]'
			: '[]';
		$moduleSource = <<<PHP
<?php

declare(strict_types=1);

namespace App\Modules\\{$namespaceSegment};

use Clover\Framework\Module\AbstractModule;

final class {$className} extends AbstractModule
{
	public function getName(): string
	{
		return '{$moduleName}';
	}

	public function getDependencies(): array
	{
		return {$dependencyExport};
	}

	public function getExportedServiceIdentifiers(): array
	{
		return {$exportedServiceIdentifiers};
	}

	public function boot(\Clover\Classes\DependencyInjection\Container \$container): void
	{
		\$moduleManager = \$container->get(\Clover\Framework\Module\ModuleManager::class);

		if (!\$moduleManager->has(\$this->getName())) {
			throw new \RuntimeException('Module manager was not available during module boot.');
		}
	}
}
PHP;

		$this->assertTrue(FileHandler::write(
			$moduleDirectory . DIRECTORY_SEPARATOR . $className . '.php',
			$moduleSource
		));
		require_once $moduleDirectory . DIRECTORY_SEPARATOR . $className . '.php';

		if (!$withService) {
			return;
		}

		$dependenciesSource = <<<PHP
<?php

declare(strict_types=1);

use Clover\Classes\DependencyInjection\Container;
use Clover\Tests\Framework\Module\BoardFixtureService;

return static function (Container \$container): void {
	\$container->set(BoardFixtureService::class, static fn(): BoardFixtureService => new BoardFixtureService());
};
PHP;
		$this->assertTrue(FileHandler::write(
			$configureDirectory . DIRECTORY_SEPARATOR . 'dependencies.php',
			$dependenciesSource
		));
	}

	/**
	 * @param array<string, mixed> $configuration
	 */
	private function writeModuleConfiguration(array $configuration): void
	{
		$configureDirectory = $this->temporaryDirectory
			. DIRECTORY_SEPARATOR
			. 'Configure';
		DirectoryHandler::ensureExists($configureDirectory, 0777);

		$export = var_export($configuration, true);
		$this->assertTrue(FileHandler::write(
			$configureDirectory . DIRECTORY_SEPARATOR . 'modules.php',
			sprintf("<?php\n\nreturn %s;\n", $export)
		));
	}

	private function loadModules(?Container $container = null): ModuleRegistry
	{
		$container ??= new Container();
		$loader = new ModuleLoader(
			$this->temporaryDirectory . DIRECTORY_SEPARATOR . 'Modules',
			$this->temporaryDirectory . DIRECTORY_SEPARATOR . 'Configure' . DIRECTORY_SEPARATOR . 'modules.php'
		);

		return $loader->load($container);
	}

	/**
	 * @param string[] $dependencies
	 */
	private function exportDependencyList(array $dependencies): string
	{
		return var_export($dependencies, true);
	}

	private function toNamespaceSegment(string $moduleName): string
	{
		$normalizedName = str_replace(['-', '_'], ' ', $moduleName);

		return str_replace(' ', '', ucwords($normalizedName));
	}
}

final class BoardFixtureService
{
	public function label(): string
	{
		return 'fixture';
	}
}
