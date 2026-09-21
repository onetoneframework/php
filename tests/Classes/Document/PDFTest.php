<?php

declare(strict_types = 1)
;

namespace Clover\Tests\Classes\Document;

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use PHPUnit\Framework\TestCase;
use Clover\Classes\Document\PDF;
use Clover\Classes\Directory\Handler as DirectoryHandler;

class PDFTest extends TestCase
{
	private const OUTPUT_DIRECTORY_RANDOM_BYTES = 8;

    public function testExtractImages(): void
    {
        $pdf = new PDF();
        $outputDirectory = sys_get_temp_dir()
            . DIRECTORY_SEPARATOR
            . 'onetone-pdf-images-'
            . bin2hex(random_bytes(self::OUTPUT_DIRECTORY_RANDOM_BYTES));

        try {
            $images = $pdf->extractImages(__DIR__ . DIRECTORY_SEPARATOR . 'pdf.pdf', $outputDirectory);

            $this->assertDirectoryExists($outputDirectory);
            $this->assertNotEmpty($images);
        } finally {
            if (DirectoryHandler::isDirectory($outputDirectory)) {
                $this->assertTrue(DirectoryHandler::delete($outputDirectory));
            }
        }
    }
}
