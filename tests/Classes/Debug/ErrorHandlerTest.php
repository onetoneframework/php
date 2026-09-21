<?php

declare(strict_types=1);

namespace Tests\Classes\Debug;

use Clover\Classes\Debug\ErrorHandler;
use LogicException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ErrorHandlerTest extends TestCase
{
	/** @var array<string, string|null> */
	private array $previousEnvironment = [];
	/** @var array<string, mixed> */
	private array $previousGet = [];
	/** @var array<string, mixed> */
	private array $previousPost = [];
	/** @var array<string, mixed> */
	private array $previousFiles = [];
	/** @var array<string, mixed> */
	private array $previousCookies = [];
	/** @var array<string, mixed> */
	private array $previousServer = [];
	/** @var array<string, mixed> */
	private array $previousSession = [];
	private bool $sessionWasDefined = false;

	protected function setUp(): void
	{
		$this->previousGet = $_GET;
		$this->previousPost = $_POST;
		$this->previousFiles = $_FILES;
		$this->previousCookies = $_COOKIE;
		$this->previousServer = $_SERVER;
		$this->sessionWasDefined = isset($_SESSION);
		$this->previousSession = $this->sessionWasDefined && is_array($_SESSION) ? $_SESSION : [];

		foreach (['APP_DEBUG', 'IS_DEBUGGABLE', 'STRUCTURED_LOG_ENABLED'] as $key) {
			$this->previousEnvironment[$key] = $_ENV[$key] ?? null;
		}
	}

	protected function tearDown(): void
	{
		$_GET = $this->previousGet;
		$_POST = $this->previousPost;
		$_FILES = $this->previousFiles;
		$_COOKIE = $this->previousCookies;
		$_SERVER = $this->previousServer;
		if ($this->sessionWasDefined) {
			$_SESSION = $this->previousSession;
		} else {
			unset($_SESSION);
		}

		foreach ($this->previousEnvironment as $key => $value) {
			if ($value === null) {
				unset($_ENV[$key]);
			} else {
				$_ENV[$key] = $value;
			}
		}
	}

	public function testRendersGenericHttpExceptionOutsideDebugMode(): void
	{
		$_ENV['APP_DEBUG'] = 'false';
		$_ENV['IS_DEBUGGABLE'] = 'false';
		$_ENV['STRUCTURED_LOG_ENABLED'] = 'false';

		$content = (string) (new ErrorHandler())->renderException(
			new RuntimeException('sensitive exception detail'),
			false
		);

		$this->assertStringContainsString('Internal Server Error', $content);
		$this->assertStringContainsString('Reference:', $content);
		$this->assertStringNotContainsString('sensitive exception detail', $content);
	}

	public function testRendersDetailedHttpExceptionWhenDebuggingIsEnabled(): void
	{
		$_ENV['APP_DEBUG'] = 'true';
		$_ENV['IS_DEBUGGABLE'] = 'false';

		$content = (string) (new ErrorHandler())->renderException(
			new RuntimeException('debug exception detail'),
			false
		);

		$this->assertStringContainsString('debug exception detail', $content);
		$this->assertStringContainsString(RuntimeException::class, $content);
	}

	public function testRendersExpandedDiagnosticContextAndRedactsSensitiveValues(): void
	{
		$_ENV['APP_DEBUG'] = 'true';
		$_ENV['IS_DEBUGGABLE'] = 'false';
		$bodySecret = implode('-', ['body', 'password', 'secret']);
		$cookieSecret = implode('-', ['cookie', 'secret']);
		$sessionSecret = implode('-', ['session', 'secret']);
		$authorizationSecret = implode('-', ['authorization', 'secret']);
		$_GET = ['search' => 'visible-query'];
		$_POST = [
			'profile' => [
				'display_name' => 'visible-name',
				'password' => $bodySecret,
			],
		];
		$_COOKIE = ['session_identifier' => $cookieSecret];
		$_SESSION = ['account' => $sessionSecret];
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_SERVER['REQUEST_URI'] = '/diagnostics?search=visible-query';
		$_SERVER['HTTP_X_DEBUG_HEADER'] = 'visible-header';
		$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $authorizationSecret;

		$content = (string) (new ErrorHandler())->renderException(
			new RuntimeException('diagnostic exception'),
			false
		);

		$this->assertStringContainsString('Diagnostic context', $content);
		$this->assertStringContainsString('Request headers', $content);
		$this->assertStringContainsString('Response headers', $content);
		$this->assertStringContainsString('Query parameters', $content);
		$this->assertStringContainsString('PHP configuration', $content);
		$this->assertStringContainsString('Loaded extensions', $content);
		$this->assertStringContainsString('Included files', $content);
		$this->assertStringContainsString('visible-query', $content);
		$this->assertStringContainsString('visible-name', $content);
		$this->assertStringContainsString('visible-header', $content);
		$this->assertStringContainsString('[REDACTED]', $content);
		$this->assertStringNotContainsString($bodySecret, $content);
		$this->assertStringNotContainsString($cookieSecret, $content);
		$this->assertStringNotContainsString($sessionSecret, $content);
		$this->assertStringNotContainsString($authorizationSecret, $content);
	}

	public function testRendersExceptionChainAndEscapesExceptionContent(): void
	{
		$_ENV['APP_DEBUG'] = 'true';
		$_ENV['IS_DEBUGGABLE'] = 'false';
		$previousException = new RuntimeException('previous <strong>message</strong>', 17);
		$exception = new LogicException('<script>alert("unsafe")</script>', 42, $previousException);

		$content = (string) (new ErrorHandler())->renderException($exception, false);

		$this->assertStringContainsString('Exception chain (2)', $content);
		$this->assertStringContainsString(LogicException::class, $content);
		$this->assertStringContainsString(RuntimeException::class, $content);
		$this->assertStringContainsString('&lt;script&gt;alert(&quot;unsafe&quot;)&lt;/script&gt;', $content);
		$this->assertStringContainsString('previous &lt;strong&gt;message&lt;/strong&gt;', $content);
		$this->assertStringNotContainsString('<script>alert("unsafe")</script>', $content);
	}
}
