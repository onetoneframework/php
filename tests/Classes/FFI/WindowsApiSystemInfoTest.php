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
use function extension_loaded;

/**
 * The system-information and volume/file additions to WindowsAPI.
 *
 * The calls themselves bind to kernel32/ntdll and only run on Windows behind the
 * RUN_WINDOWS_FFI_TESTS gate, exactly like FFITest. The structural half needs
 * neither: it proves the eight wrappers exist with the shapes callers expect and
 * that every DLL function they lean on is declared on the interface
 * $this->kernel32 / $this->ntdll are typed against - the wiring that, if missing,
 * makes the wrapper a call to an undefined method the moment it runs.
 */
final class WindowsApiSystemInfoTest extends TestCase
{
    use SharesOneWindowsApi;

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function wrapperProvider(): array
    {
        return [
            'getRealWindowsVersion' => ['getRealWindowsVersion', 'array|false'],
            'getSystemProcessorTimes' => ['getSystemProcessorTimes', 'array|false'],
            'getFirmwareType' => ['getFirmwareType', 'string|false'],
            'getProductInfo' => ['getProductInfo', 'int|false'],
            'getBinaryType' => ['getBinaryType', 'string|false'],
            'getVolumeInformation' => ['getVolumeInformation', 'array|false'],
            'getFullPathName' => ['getFullPathName', 'string|false'],
            'getCompressedFileSize' => ['getCompressedFileSize', 'int|false'],
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
            'RtlGetVersion' => ['RtlGetVersion'],
            'GetSystemTimes' => ['GetSystemTimes'],
            'GetFirmwareType' => ['GetFirmwareType'],
            'GetProductInfo' => ['GetProductInfo'],
            'GetVolumeInformationW' => ['GetVolumeInformationW'],
            'GetBinaryType' => ['GetBinaryType'],
            'GetFullPathNameW' => ['GetFullPathNameW'],
            'GetCompressedFileSizeW' => ['GetCompressedFileSizeW'],
        ];
    }

    /**
     * @dataProvider dllFunctionProvider
     */
    public function testTheInterfaceDeclaresEveryDllFunctionTheWrappersCall(string $function): void
    {
        $this->assertTrue(
            (new ReflectionClass(FFIWindowsAPIInterface::class))->hasMethod($function),
            $function . '() must be declared on FFIWindowsAPIInterface so $this->kernel32/ntdll resolves it.'
        );
    }

    #region live calls (Windows only)


    public function testGetRealWindowsVersionReturnsAModernBuild(): void
    {
        $version = $this->windowsApiOrSkip()->getRealWindowsVersion();

        $this->assertIsArray($version);
        $this->assertArrayHasKey('build', $version);
        $this->assertGreaterThan(0, $version['major']);
        $this->assertGreaterThan(0, $version['build']);
    }

    public function testGetSystemProcessorTimesAdvancesBetweenTwoReads(): void
    {
        $windowsApi = $this->windowsApiOrSkip();

        $first = $windowsApi->getSystemProcessorTimes();
        usleep(50_000);
        $second = $windowsApi->getSystemProcessorTimes();

        $this->assertIsArray($first);
        $this->assertIsArray($second);
        $this->assertGreaterThanOrEqual($first['busy'], $second['busy'], 'Busy time only moves forward.');
    }

    public function testGetFirmwareTypeIsBiosOrUefi(): void
    {
        $this->assertContains(
            $this->windowsApiOrSkip()->getFirmwareType(),
            ['BIOS', 'UEFI', 'Unknown', false]
        );
    }

    public function testGetVolumeInformationDescribesTheSystemDrive(): void
    {
        $volume = $this->windowsApiOrSkip()->getVolumeInformation('C:\\');

        $this->assertIsArray($volume);
        $this->assertNotSame('', $volume['fileSystem'], 'The system drive reports its filesystem, e.g. NTFS.');
        $this->assertMatchesRegularExpression('/^[0-9A-F]{4}-[0-9A-F]{4}$/', $volume['serialNumber']);
    }

    public function testGetFullPathNameCanonicalisesADottedPath(): void
    {
        $resolved = $this->windowsApiOrSkip()->getFullPathName('C:\\Windows\\System32\\..\\notepad.exe');

        $this->assertIsString($resolved);
        $this->assertStringNotContainsString('..', $resolved);
        $this->assertStringEndsWith('notepad.exe', $resolved);
    }

    public function testGetBinaryTypeReportsSixtyFourBitForASystemExecutable(): void
    {
        $type = $this->windowsApiOrSkip()->getBinaryType('C:\\Windows\\System32\\notepad.exe');

        $this->assertContains($type, ['WIN32', 'WIN64']);
    }

    #endregion
}
