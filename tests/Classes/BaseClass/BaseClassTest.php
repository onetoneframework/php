<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\BaseClass;

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use Clover\Classes\BaseClass;
use PHPUnit\Framework\TestCase;

class BaseClassTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($_ENV['USE_PROXY']);
        parent::tearDown();
    }

    public function testSetBaseProxyWithoutEnvReturnsSameObject(): void
    {
        $obj = new \stdClass();
        $result = BaseClass::setBaseProxy($obj);
        $this->assertSame($obj, $result);
    }

    public function testSetBaseProxyWithUseProxyTrueReturnsProxy(): void
    {
        $_ENV['USE_PROXY'] = 'true';
        $obj = new \stdClass();
        $result = BaseClass::setBaseProxy($obj);
        $this->assertNotSame($obj, $result);
        $this->assertInstanceOf(\Clover\Classes\Proxy\BaseProxy::class, $result);
    }

    public function testSetBaseProxyWithUseProxyFalseReturnsSameObject(): void
    {
        $_ENV['USE_PROXY'] = 'false';
        $obj = new \stdClass();
        $result = BaseClass::setBaseProxy($obj);
        $this->assertSame($obj, $result);
    }

    public function testBaseClassCanBeInstantiated(): void
    {
        $base = new BaseClass();
        $this->assertInstanceOf(BaseClass::class, $base);
    }
}
