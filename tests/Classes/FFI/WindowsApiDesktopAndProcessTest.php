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
 * The DPI/monitor, clipboard, shell, job-object and multimedia-timer additions
 * to WindowsAPI.
 *
 * As with the system-information batch, the live calls bind to user32, shell32,
 * kernel32 and winmm and only run on Windows behind RUN_WINDOWS_FFI_TESTS. The
 * structural half runs everywhere and pins the two things that break silently:
 * the wrapper's shape, and the interface declaration without which the call is
 * an undefined method the first time it is reached.
 */
final class WindowsApiDesktopAndProcessTest extends TestCase
{
    use SharesOneWindowsApi;

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function wrapperProvider(): array
    {
        return [
            'getDpiForWindow' => ['getDpiForWindow', 'int'],
            'getSystemDpi' => ['getSystemDpi', 'int'],
            'getMonitorInfoForWindow' => ['getMonitorInfoForWindow', 'array|false'],
            'getClipboardFormatList' => ['getClipboardFormatList', 'array'],
            'getClipboardSequenceNumber' => ['getClipboardSequenceNumber', 'int'],
            'getRecycleBinInfo' => ['getRecycleBinInfo', 'array|false'],
            'getUserNotificationState' => ['getUserNotificationState', 'int|false'],
            'getProcessHandleCount' => ['getProcessHandleCount', 'int|false'],
            'createJobObject' => ['createJobObject', '?FFI\CData'],
            'assignProcessToJobObject' => ['assignProcessToJobObject', 'bool'],
            'terminateJobObject' => ['terminateJobObject', 'bool'],
            'getMultimediaTime' => ['getMultimediaTime', 'int'],
            'beginTimePeriod' => ['beginTimePeriod', 'bool'],
            'endTimePeriod' => ['endTimePeriod', 'bool'],
            'sendMciCommand' => ['sendMciCommand', 'string|false'],
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
            'GetDpiForWindow' => ['GetDpiForWindow'],
            'GetDpiForSystem' => ['GetDpiForSystem'],
            'MonitorFromWindow' => ['MonitorFromWindow'],
            'GetMonitorInfoW' => ['GetMonitorInfoW'],
            'EnumClipboardFormats' => ['EnumClipboardFormats'],
            'GetClipboardFormatNameW' => ['GetClipboardFormatNameW'],
            'GetClipboardSequenceNumber' => ['GetClipboardSequenceNumber'],
            'SHQueryRecycleBinW' => ['SHQueryRecycleBinW'],
            'SHQueryUserNotificationState' => ['SHQueryUserNotificationState'],
            'GetProcessHandleCount' => ['GetProcessHandleCount'],
            'CreateJobObjectW' => ['CreateJobObjectW'],
            'AssignProcessToJobObject' => ['AssignProcessToJobObject'],
            'TerminateJobObject' => ['TerminateJobObject'],
            'timeGetTime' => ['timeGetTime'],
            'timeBeginPeriod' => ['timeBeginPeriod'],
            'timeEndPeriod' => ['timeEndPeriod'],
            'mciSendStringW' => ['mciSendStringW'],
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
     * beginTimePeriod() raises the system timer resolution process-wide and the
     * effect outlives the process that asked unless it is released, so the pair
     * has to stay a pair.
     */
    public function testTheTimerResolutionRequestHasAMatchingRelease(): void
    {
        $reflection = new ReflectionClass(WindowsAPI::class);

        foreach (['beginTimePeriod', 'endTimePeriod'] as $method) {
            $parameter = $reflection->getMethod($method)->getParameters()[0];

            $this->assertSame('milliseconds', $parameter->getName());
            $this->assertTrue($parameter->isDefaultValueAvailable());
            $this->assertSame(1, $parameter->getDefaultValue(), 'Both default to the same period, or a paired call drifts.');

            $type = $parameter->getType();
            $this->assertInstanceOf(ReflectionNamedType::class, $type);
            $this->assertSame('int', $type->getName());
        }
    }

    #region live calls (Windows only)


    public function testTheSystemDpiIsAPlausibleValue(): void
    {
        $dpi = $this->windowsApiOrSkip()->getSystemDpi();

        $this->assertGreaterThanOrEqual(96, $dpi, '96 is the unscaled baseline; scaling only raises it.');
        $this->assertLessThanOrEqual(960, $dpi);
    }

    public function testTheClipboardSequenceNumberIsStableWhileNothingChanges(): void
    {
        $windowsApi = $this->windowsApiOrSkip();

        $this->assertSame(
            $windowsApi->getClipboardSequenceNumber(),
            $windowsApi->getClipboardSequenceNumber()
        );
    }

    public function testTheRecycleBinReportsSizeAndCount(): void
    {
        $info = $this->windowsApiOrSkip()->getRecycleBinInfo('C:\\');

        $this->assertIsArray($info);
        $this->assertGreaterThanOrEqual(0, $info['bytes']);
        $this->assertGreaterThanOrEqual(0, $info['items']);
    }

    public function testTheMultimediaClockAdvances(): void
    {
        $windowsApi = $this->windowsApiOrSkip();

        $first = $windowsApi->getMultimediaTime();
        usleep(20_000);
        $second = $windowsApi->getMultimediaTime();

        $this->assertGreaterThan($first, $second);
    }

    public function testATimerResolutionRequestIsAcceptedAndReleased(): void
    {
        $windowsApi = $this->windowsApiOrSkip();

        $this->assertTrue($windowsApi->beginTimePeriod(1));
        $this->assertTrue($windowsApi->endTimePeriod(1));
    }

    public function testAJobObjectCanBeCreatedAndTornDown(): void
    {
        $windowsApi = $this->windowsApiOrSkip();

        $job = $windowsApi->createJobObject();

        $this->assertNotNull($job, 'An unnamed job object needs no privileges.');
        $this->assertTrue($windowsApi->terminateJobObject($job, 0), 'An empty job terminates cleanly.');
    }

    public function testAnMciCommandThatIsNonsenseIsRejected(): void
    {
        $this->assertFalse($this->windowsApiOrSkip()->sendMciCommand('this is not an mci command'));
    }

    #endregion
}
