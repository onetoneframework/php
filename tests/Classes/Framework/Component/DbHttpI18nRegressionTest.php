<?php

declare(strict_types=1);

namespace Clover\Tests\Framework\Component;

use Clover\Classes\Database\Driver\PHPDataObject;
use Clover\Classes\DependencyInjection\Container;
use Clover\Classes\Event\Dispatcher;
use Clover\Framework\Component\HttpKernel;
use Clover\Framework\Component\Request;
use Clover\Framework\Component\Translator;
use Clover\Framework\Context\ApplicationContext;
use PDOException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class DbHttpI18nRegressionTest extends TestCase
{
    private string $cacheFile = '';

    protected function tearDown(): void
    {
        $_ENV['APP_LOCALE'] = 'en';
        $_ENV['APP_LANG'] = 'en';
        $_ENV['APP_LANGUAGE'] = 'en';
        $_ENV['APP_FALLBACK_LOCALE'] = 'en';
        $_ENV['APP_MAINTENANCE'] = 'false';
        $_ENV['ROUTE_CACHE'] = 'false';
        $_ENV['ROUTE_CACHE_PATH'] = '';

        ApplicationContext::clearInterceptors();
        ApplicationContext::clearEnvironment();
        $this->resetTranslator();

        if ($this->cacheFile !== '' && file_exists($this->cacheFile)) {
            unlink($this->cacheFile);
            $this->cacheFile = '';
        }
    }

    public function testHttpKernelNotFoundMessageRespectsAcceptLanguage(): void
    {
        $_ENV['APP_LOCALE'] = 'en';
        $_ENV['APP_MAINTENANCE'] = 'false';
        $this->enableEmptyRouteCache();
        $this->resetTranslator();

        if (!defined('BASE_PATH')) {
            define('BASE_PATH', __DIR__ . '/../../../../root');
        }

        $container = $this->createMock(Container::class);
        $dispatcher = $this->createMock(Dispatcher::class);
        ApplicationContext::setContainer($container);

        $kernel = new HttpKernel($dispatcher, $container);
        $request = new Request([], [], [], [], [
            'REQUEST_URI' => '/',
            'HTTP_ACCEPT_LANGUAGE' => 'ko-KR,ko;q=0.9,en;q=0.7',
        ], '');

        $response = $kernel->handleRequest($request);

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('페이지를 찾을 수 없음', $response->getBody());
    }

    public function testMaintenanceMessageRespectsAcceptLanguage(): void
    {
        $_ENV['APP_LOCALE'] = 'en';
        $_ENV['APP_MAINTENANCE'] = 'true';
        $_ENV['ROUTE_CACHE'] = 'false';
        $_ENV['ROUTE_CACHE_PATH'] = '';
        $this->resetTranslator();

        if (!defined('BASE_PATH')) {
            define('BASE_PATH', __DIR__ . '/../../../../root');
        }

        $container = $this->createMock(Container::class);
        $dispatcher = $this->createMock(Dispatcher::class);
        ApplicationContext::setContainer($container);

        $kernel = new HttpKernel($dispatcher, $container);
        $request = new Request([], [], [], [], [
            'REQUEST_URI' => '/',
            'HTTP_ACCEPT_LANGUAGE' => 'ja-JP,ja;q=0.9,en;q=0.7',
        ], '');

        $response = $kernel->handleRequest($request);

        $this->assertSame(503, $response->getStatusCode());
        $this->assertSame('サービス利用不可', $response->getBody());
    }

    public function testPdoLocalizedMessageUsesTranslationWhenKeyExists(): void
    {
        $_ENV['APP_LOCALE'] = 'ko';
        $_ENV['APP_FALLBACK_LOCALE'] = 'en';
        $this->resetTranslator();

        $pdo = new PHPDataObject();
        $pdo->setHostName('db-host');

        $e = new PDOException('Connection refused');
        $e->errorInfo = ['HY000', '2002', 'SQLSTATE[HY000] [2002] Connection refused'];

        $localized = $pdo->getLocalizedErrorMessage($e);

        $this->assertSame('SQLSTATE[HY000] [2002] 연결이 거부되었습니다.', $localized);
    }

    public function testPdoLocalizedMessageFallsBackToExceptionMessageWhenKeyMissing(): void
    {
        $_ENV['APP_LOCALE'] = 'ko';
        $_ENV['APP_FALLBACK_LOCALE'] = 'en';
        $this->resetTranslator();

        $pdo = new PHPDataObject();
        $pdo->setHostName('db-host');

        $e = new PDOException('Raw database error');
        $e->errorInfo = ['HY000', '9999', 'Raw database error'];

        $localized = $pdo->getLocalizedErrorMessage($e);

        $this->assertSame('Raw database error', $localized);
    }

    public function testHttpKernelUsesAppLocaleWhenAcceptLanguageMissing(): void
    {
        $_ENV['APP_LOCALE'] = 'ja';
        $_ENV['APP_MAINTENANCE'] = 'false';
        $this->enableEmptyRouteCache();
        $this->resetTranslator();

        if (!defined('BASE_PATH')) {
            define('BASE_PATH', __DIR__ . '/../../../../root');
        }

        $container = $this->createMock(Container::class);
        $dispatcher = $this->createMock(Dispatcher::class);
        ApplicationContext::setContainer($container);

        $kernel = new HttpKernel($dispatcher, $container);
        $request = new Request([], [], [], [], [
            'REQUEST_URI' => '/',
        ], '');

        $response = $kernel->handleRequest($request);

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('ページが見つかりません', $response->getBody());
    }

    public function testHttpKernelUsesAppLangAliasVariable(): void
    {
        unset($_ENV['APP_LOCALE']);
        $_ENV['APP_LANG'] = 'ko-KR';
        $_ENV['APP_MAINTENANCE'] = 'false';
        $this->enableEmptyRouteCache();
        $this->resetTranslator();

        if (!defined('BASE_PATH')) {
            define('BASE_PATH', __DIR__ . '/../../../../root');
        }

        $container = $this->createMock(Container::class);
        $dispatcher = $this->createMock(Dispatcher::class);
        ApplicationContext::setContainer($container);

        $kernel = new HttpKernel($dispatcher, $container);
        $request = new Request([], [], [], [], [
            'REQUEST_URI' => '/',
        ], '');

        $response = $kernel->handleRequest($request);

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('페이지를 찾을 수 없음', $response->getBody());
    }

    public function testPdoLocalizedMessageInterpolatesArgumentsForKnownCode(): void
    {
        $_ENV['APP_LOCALE'] = 'en';
        $_ENV['APP_FALLBACK_LOCALE'] = 'en';
        $this->resetTranslator();

        $pdo = new PHPDataObject();
        $pdo->setHostName('db-host');

        $e = new PDOException('Access denied');
        $e->errorInfo = ['HY000', '1045', "Access denied for user 'root'@'localhost' (using password: YES)"];

        $localized = $pdo->getLocalizedErrorMessage($e);

        $this->assertStringContainsString('root@localhost', $localized);
        $this->assertStringContainsString('Access denied', $localized);
    }

    public function testPdoLocalizedMessageFallsBackToGenericEnglishWhenServerMessageEmpty(): void
    {
        $_ENV['APP_LOCALE'] = 'en';
        $_ENV['APP_FALLBACK_LOCALE'] = 'en';
        $this->resetTranslator();

        $pdo = new PHPDataObject();
        $pdo->setHostName('db-host');

        $e = new PDOException('');
        $e->errorInfo = ['HY000', '9999', ''];

        $localized = $pdo->getLocalizedErrorMessage($e);

        $this->assertSame('Database error (code: 9999)', $localized);
    }

    private function resetTranslator(): void
    {
        $reflection = new ReflectionClass(Translator::class);
        $property = $reflection->getProperty('translator');
        if (version_compare(PHP_VERSION, '8.1.0', '<')) {
            // @phpstan-ignore-next-line
            $property->setAccessible(true);
        }
        $property->setValue(null, null);
    }

    private function enableEmptyRouteCache(): void
    {
        $this->cacheFile = tempnam(sys_get_temp_dir(), 'db-http-i18n-route-cache-') . '.php';
        file_put_contents($this->cacheFile, "<?php\nreturn [];\n");
        $_ENV['ROUTE_CACHE'] = 'true';
        $_ENV['ROUTE_CACHE_PATH'] = $this->cacheFile;
    }
}
