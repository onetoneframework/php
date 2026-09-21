<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Barcode;

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use Clover\Classes\Barcode\BarcodeHandler;
use PHPUnit\Framework\TestCase;

class BarcodeTest extends TestCase
{
    public function testRouteConstructorDefaults(): void
    {
        BarcodeHandler::drawBarcode('code128auto', __DIR__.'/barcode.png');
        $this->assertFileExists(__DIR__.'/barcode.png');
        unlink(__DIR__.'/barcode.png');
    }
}
