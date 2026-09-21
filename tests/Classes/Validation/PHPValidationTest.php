<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Tests\Validation;

use Clover\Validation\PHPValidation;
use PHPUnit\Framework\TestCase;

class PHPValidationTest extends TestCase
{
    public function testGetVersion(): void
    {
        $version = PHPValidation::getVersion();
        $this->assertIsString($version);
        $this->assertNotEmpty($version);
        // Should match x.y.z format roughly
        $this->assertMatchesRegularExpression('/^\d+\.\d+\.\d+/', $version);
    }

    public function testVersionGreaterThanCurrent(): void
    {
        // Current version is likely 8.x
        // Method checks if argument > current
        // 5.0.0 > 8.x is False
        $this->assertFalse(PHPValidation::versionGreaterThanCurrent('5.0.0'));
        
        // 99.0.0 > 8.x is True (assuming current is not 99)
        $this->assertTrue(PHPValidation::versionGreaterThanCurrent('99.0.0'));
    }

    public function testVersionCompare(): void
    {
        $this->assertTrue(PHPValidation::versionCompare('8.0', '7.0'));
        $this->assertTrue(PHPValidation::versionCompare('8.0', '8.0'));
        $this->assertFalse(PHPValidation::versionCompare('7.0', '8.0'));
        
        $this->assertTrue(PHPValidation::versionCompare('8.1.0', '8.0.99'));
    }
    
    public function testGetVersionReturnsStringOrBool(): void
    {
        $version = PHPValidation::getVersion();
        $this->assertTrue(is_string($version) || is_bool($version));
        if (is_string($version)) {
            $this->assertMatchesRegularExpression('/^\d+\.\d+(\.\d+)?/', $version);
        }
    }

    public function testVersionCompareWhenFirstGreaterOrEqual(): void
    {
        $this->assertTrue(PHPValidation::versionCompare('8.2', '8.1'));
        $this->assertTrue(PHPValidation::versionCompare('8.1', '8.1'));
        $this->assertFalse(PHPValidation::versionCompare('8.0', '8.1'));
    }

    public function testVersionGreaterThanCurrentWhenGivenVersionIsHigher(): void
    {
        $current = PHPValidation::getVersion();
        if (is_string($current)) {
            $higher = '99.99.99';
            $this->assertTrue(PHPValidation::versionGreaterThanCurrent($higher));
        }
    }

    public function testVersionGreaterThanCurrentWhenGivenVersionIsLower(): void
    {
        $lower = '0.0.0';
        $this->assertFalse(PHPValidation::versionGreaterThanCurrent($lower));
    }
}
