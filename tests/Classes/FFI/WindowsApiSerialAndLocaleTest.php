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
use ReflectionMethod;
use ReflectionNamedType;
use function extension_loaded;

/**
 * The serial-port and time-zone/locale additions to WindowsAPI.
 *
 * The live half needs hardware or a machine setting and only runs on Windows
 * behind RUN_WINDOWS_FFI_TESTS; the serial tests need a port to exist at all,
 * and skip when none does. The structural half runs everywhere.
 *
 * The one thing here that can be checked properly without Windows is the signed
 * conversion, and it is the part most likely to be wrong: a LONG comes back
 * through FFI as the unsigned number its bits spell, so a time zone bias of
 * -540 arrives as 4294966756 and every offset west of UTC would read as a very
 * large positive number.
 */
final class WindowsApiSerialAndLocaleTest extends TestCase
{
    use SharesOneWindowsApi;

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function wrapperProvider(): array
    {
        return [
            'openSerialPort' => ['openSerialPort', '?FFI\CData'],
            'getSerialPortConfig' => ['getSerialPortConfig', 'array|false'],
            'configureSerialPort' => ['configureSerialPort', 'bool'],
            'setSerialTimeouts' => ['setSerialTimeouts', 'bool'],
            'getSerialTimeouts' => ['getSerialTimeouts', 'array|false'],
            'setSerialQueueSizes' => ['setSerialQueueSizes', 'bool'],
            'writeSerialPort' => ['writeSerialPort', 'int|false'],
            'readSerialPort' => ['readSerialPort', 'string|false'],
            'purgeSerialPort' => ['purgeSerialPort', 'bool'],
            'getSerialQueueStatus' => ['getSerialQueueStatus', 'array|false'],
            'getSerialModemStatus' => ['getSerialModemStatus', 'array|false'],
            'escapeSerialFunction' => ['escapeSerialFunction', 'bool'],
            'getTimeZoneInformation' => ['getTimeZoneInformation', 'array|false'],
            'getUserLocaleName' => ['getUserLocaleName', 'string|false'],
            'getSystemLocaleName' => ['getSystemLocaleName', 'string|false'],
            'getLocaleInfo' => ['getLocaleInfo', 'string|false'],
            'getUserGeoId' => ['getUserGeoId', 'int|false'],
            'getGeoInfo' => ['getGeoInfo', 'string|false'],
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
            'GetCommState' => ['GetCommState'],
            'SetCommState' => ['SetCommState'],
            'GetCommTimeouts' => ['GetCommTimeouts'],
            'SetCommTimeouts' => ['SetCommTimeouts'],
            'SetupComm' => ['SetupComm'],
            'PurgeComm' => ['PurgeComm'],
            'ClearCommError' => ['ClearCommError'],
            'GetCommModemStatus' => ['GetCommModemStatus'],
            'EscapeCommFunction' => ['EscapeCommFunction'],
            'GetTimeZoneInformation' => ['GetTimeZoneInformation'],
            'GetUserDefaultLocaleName' => ['GetUserDefaultLocaleName'],
            'GetSystemDefaultLocaleName' => ['GetSystemDefaultLocaleName'],
            'GetLocaleInfoEx' => ['GetLocaleInfoEx'],
            'GetUserGeoID' => ['GetUserGeoID'],
            'GetGeoInfoW' => ['GetGeoInfoW'],
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
     * @return array<string, array{0: int, 1: int}>
     */
    public static function signedLongProvider(): array
    {
        return [
            'zero' => [0, 0],
            'positive' => [540, 540],
            'the Korean bias, as FFI reports it' => [4294966756, -540],
            'minus one' => [4294967295, -1],
            'the most negative LONG' => [2147483648, -2147483648],
            'the largest positive LONG' => [2147483647, 2147483647],
            'already negative stays put' => [-540, -540],
        ];
    }

    /**
     * @dataProvider signedLongProvider
     */
    public function testAThirtyTwoBitValueIsReinterpretedAsSigned(int $raw, int $expected): void
    {
        $method = new ReflectionMethod(WindowsAPI::class, 'toSignedLong');
        $method->setAccessible(true);

        $this->assertSame(
            $expected,
            $method->invoke($this->uninitialisedWindowsApi(), $raw)
        );
    }

    /**
     * Every serial wrapper takes the handle first, so a caller cannot pass the
     * arguments in the order the C function happens to want.
     */
    public function testEverySerialWrapperTakesTheHandleFirst(): void
    {
        $reflection = new ReflectionClass(WindowsAPI::class);

        $handleTakers = [
            'getSerialPortConfig', 'configureSerialPort', 'setSerialTimeouts', 'getSerialTimeouts',
            'setSerialQueueSizes', 'writeSerialPort', 'readSerialPort', 'purgeSerialPort',
            'getSerialQueueStatus', 'getSerialModemStatus', 'escapeSerialFunction',
        ];

        foreach ($handleTakers as $method) {
            $parameter = $reflection->getMethod($method)->getParameters()[0];

            $this->assertSame('handle', $parameter->getName(), $method . '() must take the handle first.');

            $type = $parameter->getType();
            $this->assertInstanceOf(ReflectionNamedType::class, $type);
            $this->assertSame('FFI\CData', $type->getName());
        }
    }

    /**
     * A read with no timeout configured blocks until a byte arrives, so the
     * defaults must not be all zeroes.
     */
    public function testTheSerialTimeoutDefaultsDoNotBlockForever(): void
    {
        $parameters = (new ReflectionClass(WindowsAPI::class))->getMethod('setSerialTimeouts')->getParameters();

        $defaults = [];

        foreach (array_slice($parameters, 1) as $parameter) {
            $this->assertTrue($parameter->isDefaultValueAvailable(), $parameter->getName() . ' must have a default.');
            $defaults[] = $parameter->getDefaultValue();
        }

        $this->assertNotEmpty($defaults);
        $this->assertGreaterThan(0, array_sum($defaults), 'All-zero timeouts mean a read never returns on a silent line.');
    }

    /**
     * An instance without the constructor, so the pure helpers can be exercised
     * on a machine with no Windows and no FFI.
     */
    private function uninitialisedWindowsApi(): WindowsAPI
    {
        return (new ReflectionClass(WindowsAPI::class))->newInstanceWithoutConstructor();
    }

    #region live calls (Windows only)


    public function testTheTimeZoneIsReportedWithAPlausibleOffset(): void
    {
        $zone = $this->windowsApiOrSkip()->getTimeZoneInformation();

        $this->assertIsArray($zone);
        $this->assertNotSame('', $zone['standardName']);
        $this->assertContains($zone['current'], ['standard', 'daylight', 'unknown']);

        // No inhabited zone is more than 14 hours from UTC in either direction.
        $this->assertGreaterThanOrEqual(-14 * 60, $zone['currentBias']);
        $this->assertLessThanOrEqual(14 * 60, $zone['currentBias']);
    }

    /**
     * The effective offset has to be the base bias plus whichever seasonal bias
     * is in force, and it has to land on a real UTC offset.
     *
     * This deliberately does not compare against PHP's own timezone: PHP's
     * default is whatever php.ini says - UTC unless someone set it - not what
     * the machine is configured for, so agreeing with it would prove nothing
     * and disagreeing with it proves nothing either.
     */
    public function testTheOffsetIsInternallyConsistentAndARealUtcOffset(): void
    {
        $zone = $this->windowsApiOrSkip()->getTimeZoneInformation();

        $this->assertIsArray($zone);

        $seasonal = $zone['current'] === 'daylight' ? $zone['daylightBias'] : $zone['standardBias'];
        $this->assertSame($zone['bias'] + $seasonal, $zone['currentBias']);

        // Every UTC offset in use is a whole number of quarter hours.
        $this->assertSame(0, $zone['currentBias'] % 15);
    }

    public function testTheLocaleAndRegionAreReported(): void
    {
        $windowsApi = $this->windowsApiOrSkip();

        $userLocale = $windowsApi->getUserLocaleName();
        $systemLocale = $windowsApi->getSystemLocaleName();

        $this->assertIsString($userLocale);
        $this->assertMatchesRegularExpression('/^[a-z]{2,3}(-[A-Za-z0-9]+)*$/', $userLocale);
        $this->assertIsString($systemLocale);

        $geoId = $windowsApi->getUserGeoId();
        $this->assertIsInt($geoId);
        $this->assertGreaterThan(0, $geoId);

        // GEO_ISO2
        $country = $windowsApi->getGeoInfo($geoId, 4);
        $this->assertIsString($country);
        $this->assertMatchesRegularExpression('/^[A-Z]{2}$/', $country);
    }

    public function testALocaleFieldCanBeReadBack(): void
    {
        // LOCALE_SDECIMAL - every locale has one, and it is never empty.
        $separator = $this->windowsApiOrSkip()->getLocaleInfo(0x0E, 'en-US');

        $this->assertSame('.', $separator);
    }

    public function testASerialPortRoundTripsItsConfiguration(): void
    {
        $windowsApi = $this->windowsApiOrSkip();

        $handle = null;

        foreach (['COM1', 'COM2', 'COM3', 'COM4'] as $candidate) {
            $handle = $windowsApi->openSerialPort($candidate);

            if ($handle !== null) {
                break;
            }
        }

        if ($handle === null) {
            $this->markTestSkipped('No serial port is present on this machine.');
        }

        $this->assertTrue($windowsApi->configureSerialPort($handle, 9600, 8, 0, 0));

        $config = $windowsApi->getSerialPortConfig($handle);
        $this->assertIsArray($config);
        $this->assertSame(9600, $config['baudRate']);
        $this->assertSame(8, $config['byteSize']);

        $this->assertTrue($windowsApi->setSerialTimeouts($handle));

        $timeouts = $windowsApi->getSerialTimeouts($handle);
        $this->assertIsArray($timeouts);
        $this->assertSame(50, $timeouts['readInterval']);

        $status = $windowsApi->getSerialQueueStatus($handle);
        $this->assertIsArray($status);
        $this->assertGreaterThanOrEqual(0, $status['inQueue']);

        $this->assertTrue($windowsApi->purgeSerialPort($handle));
    }

    #endregion
}
