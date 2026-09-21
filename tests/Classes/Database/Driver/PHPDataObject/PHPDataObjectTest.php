<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Database\Driver\PHPDataObject;

use Clover\Classes\Database\Driver\PHPDataObject;
use Clover\Classes\Event\EventDispatcherAdapter;
use Clover\Framework\Component\Translator;
use Clover\Framework\Event\DatabaseLifecycleEvent;
use Clover\Framework\Event\KernelSpanFinished;
use Clover\Framework\Event\KernelSpanStarted;
use Exception;
use PDOException;
use PDOStatement;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;

final class PHPDataObjectTest extends TestCase
{
    protected function tearDown(): void
    {
        $_ENV['APP_LOCALE'] = 'en';
        $_ENV['APP_FALLBACK_LOCALE'] = 'en';
		unset($_ENV['PROFILER_ENABLED']);
        $this->resetTranslator();
    }

    public function testSetDriverThrowsExceptionForUnsupportedDriver(): void
    {
        $pdo = new PHPDataObject();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Driver is not supported');

        $pdo->setDriver('unsupported_driver_name');
    }

    public function testLocalizedErrorMessageUsesTranslationForKnownCode(): void
    {
        $_ENV['APP_LOCALE'] = 'en';
        $this->resetTranslator();

        $pdo = new PHPDataObject();
        $pdo->setHostName('db-host');

        $e = new PDOException('Access denied');
        $e->errorInfo = ['HY000', '1045', "Access denied for user 'root'@'localhost' (using password: YES)"];

        $message = $pdo->getLocalizedErrorMessage($e);

        $this->assertStringContainsString('Access denied', $message);
        $this->assertStringContainsString('root@localhost', $message);
    }

    public function testLocalizedErrorMessageFallsBackToGenericWhenServerMessageEmpty(): void
    {
        $_ENV['APP_LOCALE'] = 'en';
        $this->resetTranslator();

        $pdo = new PHPDataObject();
        $pdo->setHostName('db-host');

        $e = new PDOException('');
        $e->errorInfo = ['HY000', '9999', ''];

        $message = $pdo->getLocalizedErrorMessage($e);

        $this->assertSame('Database error (code: 9999)', $message);
    }

	public function testExecuteQueryDoesNotDispatchProfilerSpansWhenProfilerIsDisabled(): void
	{
		$_ENV['PROFILER_ENABLED'] = 'false';
		$events = [];
		$database = new FailingPreparePHPDataObject();
		$database->setEventDispatcher(new EventDispatcherAdapter(
			static function (object $event) use (&$events): void {
				$events[] = $event;
			}
		));

		$this->expectException(RuntimeException::class);

		try {
			$database->executeQuery('SELECT 1');
		} finally {
			self::assertCount(1, $events);
			self::assertInstanceOf(DatabaseLifecycleEvent::class, $events[0]);
		}
	}

	public function testExecuteQueryClosesProfilerSpanWhenPreparingQueryThrows(): void
	{
		$_ENV['PROFILER_ENABLED'] = 'true';
		$events = [];
		$database = new FailingPreparePHPDataObject();
		$database->setEventDispatcher(new EventDispatcherAdapter(
			static function (object $event) use (&$events): void {
				$events[] = $event;
			}
		));

		$this->expectException(RuntimeException::class);

		try {
			$database->executeQuery('SELECT 1');
		} finally {
			self::assertCount(3, $events);
			self::assertInstanceOf(DatabaseLifecycleEvent::class, $events[0]);
			self::assertInstanceOf(KernelSpanStarted::class, $events[1]);
			self::assertInstanceOf(KernelSpanFinished::class, $events[2]);
			self::assertSame($events[1]->token, $events[2]->token);
		}
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
}

final class FailingPreparePHPDataObject extends PHPDataObject
{
	public function prepare(string $query, array $options = []): PDOStatement|false
	{
		throw new RuntimeException('Preparing the query failed.');
	}
}
