<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Tests\FFI;

use Clover\Classes\FFI\Windows\CustomWindow;
use Clover\Classes\FFI\Windows\WindowsAPI;
use Clover\Tests\Classes\FFI\SharedWindowsApi;
use PHPUnit\Framework\TestCase;
use function count;
use function extension_loaded;
use function is_file;
use function sprintf;

class FFITest extends TestCase
{
    public function setUp(): void
    {
        if (!defined('BASE_PATH')) {
            define('BASE_PATH', __DIR__ . "/../../../root");
        }
    }

    public function testFFI(): void
    {
        if (!extension_loaded('ffi')) {
            $this->markTestSkipped('FFI extension is not available.');
        }

        if (($_ENV['RUN_WINDOWS_FFI_TESTS'] ?? 'false') !== 'true') {
            $this->markTestSkipped('Windows FFI integration tests are disabled by default.');
        }

        $countriesPath = __DIR__ . '/../../countries.csv';
        if (!is_file($countriesPath)) {
            $this->markTestSkipped('FFI fixture file is not available.');
        }

        // The process-wide instance, not one of this test's own. Releasing an
        // FFI handle built from windows.h corrupts PHP's heap on this build, so
        // a test that constructs its own and drops it takes down whichever test
        // touches FFI next. See SharedWindowsApi.
        [$ffi, $reason] = SharedWindowsApi::instanceOrReason();

        if ($ffi === null) {
            $this->markTestSkipped((string) $reason);
            return;
        }

        $computerName = $ffi->getComputerName();
        $this->assertNotEmpty($computerName);

        $currentDirectory = $ffi->getCurrentDirectory();
        $this->assertNotEmpty($currentDirectory);

        $userName = $ffi->getUserName();
        $this->assertNotEmpty($userName);

        $installedProgramList = $ffi->getInstalledProgramList();
        $this->assertIsArray($installedProgramList);

        $processList = $ffi->getProcessList();
        $this->assertIsArray($processList);

        $processorCount = $ffi->getProcessorCount();
        $this->assertGreaterThan(0, $processorCount);

        $monitorCount = $ffi->getMonitorCount();
        $this->assertGreaterThan(0, $monitorCount);

        $screenWidth = $ffi->getScreenWidth();
        $this->assertGreaterThan(0, $screenWidth);

        $supportedScreenResolutionList = $ffi->getSupportedScreenResolutionList();
        $this->assertIsArray($supportedScreenResolutionList);

        $batteryStatus = $ffi->getBatteryStatus();
        $this->assertIsArray($batteryStatus);

        $userDefaultLanguageID = $ffi->getUserDefaultLanguageID();
        $this->assertNotEmpty($userDefaultLanguageID);

        $windowsDirectory = $ffi->getWindowsDirectory();
        $this->assertNotEmpty($windowsDirectory);

        $mousePosition = $ffi->getMousePosition();
        $this->assertIsNumeric($mousePosition['x']);
        $this->assertIsNumeric($mousePosition['y']);

        $networkInterface = $ffi->getNetworkInterface();
        $this->assertNotEmpty($networkInterface);

        $diskFreeSpaces = $ffi->getDiskFreeSpaces();
        $this->assertGreaterThan(0, count($diskFreeSpaces));

        $logicalDrives = $ffi->getLogicalDrives();
        $this->assertIsArray($logicalDrives);

        $localTime = $ffi->getLocalTime();
        $this->assertNotEmpty($localTime);

        $fileExists = $ffi->isFileExists($countriesPath);
        $this->assertTrue($fileExists);

        $fileSize = $ffi->getFileSize($countriesPath);
        $this->assertEquals($fileSize, 102214);

        $volume = $ffi->getVolume();
        $this->assertNotEmpty($volume);

        $volume = $ffi->getMasterVolume();
        $this->assertIsFloat($volume);
    }
}
