<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Data;

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use Clover\Classes\Data\CSVHandler;
use PHPUnit\Framework\TestCase;
use Clover\Classes\File\Handler as FileHandler;

class CsvTest extends TestCase
{

	public function setUp(): void
	{
	}

	public function testDate(): void
	{
        $csv = FileHandler::read(__DIR__.'/../../countries.csv');
        $csv = CSVHandler::decode($csv);
        $this->assertEquals($csv[1][1], "Afghanistan");
	}
}
