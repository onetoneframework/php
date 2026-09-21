<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Tests\Classes\FFI;

use Clover\Classes\FFI\Windows\WindowsAPI;
use Clover\Interface\FFI\FFIWindowsAPIInterface;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionNamedType;
use function extension_loaded;

/**
 * The service-control and event-log additions to WindowsAPI.
 *
 * WindowsAPI already opened, started, stopped and status-checked a service; what
 * it could not do was find one without being told its name, see how it starts,
 * or read anything the machine logged. That is what these add.
 *
 * The live half needs a real SCM and only runs on Windows behind
 * RUN_WINDOWS_FFI_TESTS. It reads; nothing here starts, stops, reconfigures or
 * deletes a service, and nothing clears a log.
 */
final class WindowsApiServiceAndEventLogTest extends TestCase
{
    use SharesOneWindowsApi;

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function wrapperProvider(): array
    {
        return [
            'enumerateServices' => ['enumerateServices', 'array|false'],
            'getServiceConfig' => ['getServiceConfig', 'array|false'],
            'getServiceDescription' => ['getServiceDescription', 'string|false'],
            'queryServiceStatusEx' => ['queryServiceStatusEx', 'array|false'],
            'controlService' => ['controlService', 'array|false'],
            'setServiceStartType' => ['setServiceStartType', 'bool'],
            'deleteService' => ['deleteService', 'bool'],
            'waitForServiceState' => ['waitForServiceState', 'bool'],
            'openEventLog' => ['openEventLog', '?FFI\CData'],
            'closeEventLog' => ['closeEventLog', 'bool'],
            'getEventLogRecordCount' => ['getEventLogRecordCount', 'int|false'],
            'getOldestEventLogRecord' => ['getOldestEventLogRecord', 'int|false'],
            'readEventLogEntries' => ['readEventLogEntries', 'array|false'],
            'backupEventLog' => ['backupEventLog', 'bool'],
            'clearEventLog' => ['clearEventLog', 'bool'],
        ];
    }

    /**
     * @dataProvider wrapperProvider
     */
    public function testEachWrapperIsAPublicMethodWithTheExpectedReturnType(string $method, string $returnType): void
    {
        $reflection = new ReflectionClass(WindowsAPI::class);

        $this->assertTrue($reflection->hasMethod($method), $method . '() must exist on WindowsAPI.');

        $reflected = $reflection->getMethod($method);
        $this->assertTrue($reflected->isPublic(), $method . '() must be public.');
        $this->assertSame($returnType, (string) $reflected->getReturnType());
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function dllFunctionProvider(): array
    {
        return [
            'EnumServicesStatusExW' => ['EnumServicesStatusExW'],
            'QueryServiceConfigW' => ['QueryServiceConfigW'],
            'QueryServiceConfig2W' => ['QueryServiceConfig2W'],
            'QueryServiceStatusEx' => ['QueryServiceStatusEx'],
            'ChangeServiceConfigW' => ['ChangeServiceConfigW'],
            'DeleteService' => ['DeleteService'],
            'OpenEventLogW' => ['OpenEventLogW'],
            'CloseEventLog' => ['CloseEventLog'],
            'GetNumberOfEventLogRecords' => ['GetNumberOfEventLogRecords'],
            'GetOldestEventLogRecord' => ['GetOldestEventLogRecord'],
            'ReadEventLogW' => ['ReadEventLogW'],
            'BackupEventLogW' => ['BackupEventLogW'],
            'ClearEventLogW' => ['ClearEventLogW'],
        ];
    }

    /**
     * @dataProvider dllFunctionProvider
     */
    public function testTheInterfaceDeclaresEveryDllFunctionTheWrappersCall(string $function): void
    {
        $this->assertTrue(
            (new ReflectionClass(FFIWindowsAPIInterface::class))->hasMethod($function),
            $function . '() must be declared on FFIWindowsAPIInterface so the typed library handle resolves it.'
        );
    }

    /**
     * clearEventLog() destroys records, so the backup path has to be optional
     * in the signature and explicit at the call site - never a default that
     * quietly discards.
     */
    public function testClearingALogTakesAnExplicitBackupPathThatDefaultsToNone(): void
    {
        $parameter = (new ReflectionClass(WindowsAPI::class))->getMethod('clearEventLog')->getParameters()[1];

        $this->assertSame('backupPath', $parameter->getName());
        $this->assertTrue($parameter->allowsNull());
        $this->assertTrue($parameter->isDefaultValueAvailable());
        $this->assertNull($parameter->getDefaultValue());
    }

    /**
     * waitForServiceState() polls, and a poll loop with no ceiling is a hang.
     */
    public function testWaitingForAServiceStateIsBounded(): void
    {
        $parameters = (new ReflectionClass(WindowsAPI::class))->getMethod('waitForServiceState')->getParameters();

        $this->assertSame('timeoutMillis', $parameters[2]->getName());
        $this->assertTrue($parameters[2]->isDefaultValueAvailable());
        $this->assertGreaterThan(0, $parameters[2]->getDefaultValue());

        $type = $parameters[2]->getType();
        $this->assertInstanceOf(ReflectionNamedType::class, $type);
        $this->assertSame('int', $type->getName());
    }

    /**
     * The two pointer readers are helpers, not surface: an OS-owned pointer
     * handed in from outside the class is exactly how a read runs off the end
     * of a mapping.
     */
    public function testThePointerReadersStayPrivateAndCarryTheirOwnCeiling(): void
    {
        $reflection = new ReflectionClass(WindowsAPI::class);

        foreach (['readWideString', 'readWideStringSequence'] as $method) {
            $this->assertTrue($reflection->hasMethod($method));
            $this->assertTrue($reflection->getMethod($method)->isPrivate(), $method . '() must not be callable from outside.');
        }

        $ceiling = $reflection->getMethod('readWideString')->getParameters()[1];
        $this->assertSame('maxChars', $ceiling->getName());
        $this->assertTrue($ceiling->isDefaultValueAvailable());
        $this->assertGreaterThan(0, $ceiling->getDefaultValue());
    }

    #region live calls (Windows only)


    public function testTheServiceListIncludesTheServiceControlManagerItself(): void
    {
        $windowsApi = $this->windowsApiOrSkip();

        // SC_MANAGER_ENUMERATE_SERVICE | SC_MANAGER_CONNECT
        $hScm = $windowsApi->openSCManager(0x0005);
        $this->assertNotNull($hScm, 'Enumerating needs no elevation.');

        $services = $windowsApi->enumerateServices($hScm);
        $windowsApi->closeServiceHandle($hScm);

        $this->assertIsArray($services);
        $this->assertNotEmpty($services);

        $names = array_column($services, 'name');
        $this->assertContains('Schedule', $names, 'The Task Scheduler service is present on every Windows install.');
    }

    public function testARunningServiceReportsAProcessIdAndAConfiguration(): void
    {
        $windowsApi = $this->windowsApiOrSkip();

        $hScm = $windowsApi->openSCManager(0x0005);
        $this->assertNotNull($hScm);

        // SERVICE_QUERY_CONFIG | SERVICE_QUERY_STATUS
        $hService = $windowsApi->openService($hScm, 'Schedule', 0x0005);
        $this->assertNotNull($hService);

        $status = $windowsApi->queryServiceStatusEx($hService);
        $config = $windowsApi->getServiceConfig($hService);

        $windowsApi->closeServiceHandle($hService);
        $windowsApi->closeServiceHandle($hScm);

        $this->assertIsArray($status);
        $this->assertSame(4, $status['currentState'], 'Task Scheduler runs by default.');
        $this->assertGreaterThan(0, $status['processId'], 'A running service is hosted by a process.');

        $this->assertIsArray($config);
        $this->assertNotSame('', $config['binaryPath']);
        $this->assertSame(2, $config['startType'], 'Task Scheduler starts automatically.');
    }

    public function testTheApplicationLogHasRecordsAndTheyCanBeRead(): void
    {
        $windowsApi = $this->windowsApiOrSkip();

        $log = $windowsApi->openEventLog('Application');
        $this->assertNotNull($log);

        $count = $windowsApi->getEventLogRecordCount($log);
        $oldest = $windowsApi->getOldestEventLogRecord($log);
        $entries = $windowsApi->readEventLogEntries($log, 5);

        $windowsApi->closeEventLog($log);

        $this->assertIsInt($count);
        $this->assertGreaterThan(0, $count);
        $this->assertIsInt($oldest);

        $this->assertIsArray($entries);
        $this->assertNotEmpty($entries);
        $this->assertLessThanOrEqual(5, count($entries));

        $first = $entries[0];
        $this->assertGreaterThanOrEqual($oldest, $first['recordNumber']);
        $this->assertNotSame('', $first['source'], 'Every record names the source that wrote it.');
        $this->assertContains($first['eventType'], [0, 1, 2, 4, 8, 16]);
    }

    public function testReadingBackwardsReturnsNewestFirst(): void
    {
        $windowsApi = $this->windowsApiOrSkip();

        $log = $windowsApi->openEventLog('Application');
        $this->assertNotNull($log);

        $entries = $windowsApi->readEventLogEntries($log, 10);
        $windowsApi->closeEventLog($log);

        $this->assertIsArray($entries);

        if (count($entries) < 2) {
            $this->markTestSkipped('The log holds too few records to order.');
        }

        $numbers = array_column($entries, 'recordNumber');
        $descending = $numbers;
        rsort($descending);

        $this->assertSame($descending, $numbers, 'EVENTLOG_BACKWARDS_READ walks from newest to oldest.');
    }

    #endregion
}
