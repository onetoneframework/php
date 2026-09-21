<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\File;

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use PHPUnit\Framework\TestCase;
use Clover\Classes\File\Handler as FileHandler;
use Clover\Exception\FileHandler\FileNotFoundException;

class FileHandlerTest extends TestCase
{
    protected FileHandler $factory;
    protected string $tempDir;
    protected string $filePath;

    protected function setUp(): void
    {
        $this->factory = new FileHandler();
        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'file_handler_test_' . uniqid('', true);
        mkdir($this->tempDir, 0777, true);
        $this->filePath = $this->tempDir . DIRECTORY_SEPARATOR . 'testFile.txt';
        $this->factory->write($this->filePath, 'testSuccess');
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempDir)) {
            $paths = glob($this->tempDir . DIRECTORY_SEPARATOR . '*');
            if (is_array($paths)) {
                foreach ($paths as $path) {
                    if (is_file($path)) {
                        @unlink($path);
                    } elseif (is_dir($path)) {
                        $inner = glob($path . DIRECTORY_SEPARATOR . '*');
                        if (is_array($inner)) {
                            foreach ($inner as $item) {
                                if (is_file($item)) {
                                    @unlink($item);
                                }
                            }
                        }
                        @rmdir($path);
                    }
                }
            }
            @rmdir($this->tempDir);
        }
    }

    public function testWriteReadAndAppendContent(): void
    {
        $this->assertSame('testSuccess', $this->factory->readAllContent($this->filePath)->getRawData());

        $this->factory->appendContent($this->filePath, '-tail');
        $this->assertSame('testSuccess-tail', $this->factory->readAllContent($this->filePath)->getRawData());
    }

    public function testTypeSizeBasenameExtensionAndInodeMetadata(): void
    {
        $this->assertSame('file', $this->factory->getType($this->filePath));
        $this->assertIsInt($this->factory->getSize($this->filePath));
        $this->assertSame('testFile.txt', $this->factory->getBasename($this->filePath));
        $this->assertSame('txt', $this->factory->getExtension($this->filePath));
        $this->assertSame('txt', $this->factory->getExtensionByFilePath($this->filePath));
        $this->assertIsInt($this->factory->getInode($this->filePath));
        $this->assertIsInt($this->factory->getCreatedDate($this->filePath));
        $this->assertIsInt($this->factory->getLastAccessDate($this->filePath));
        $this->assertIsInt($this->factory->getLastModifiedTime($this->filePath));
    }

    public function testIsContainFolderTrueAndFalseCases(): void
    {
        $inside = $this->tempDir . DIRECTORY_SEPARATOR . 'inner.php';
        file_put_contents($inside, '<?php');

        $outsideDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'file_handler_outside_' . uniqid('', true);
        mkdir($outsideDir, 0777, true);
        $outside = $outsideDir . DIRECTORY_SEPARATOR . 'out.php';
        file_put_contents($outside, '<?php');

        $this->assertTrue($this->factory->isContainFolder($this->tempDir, $inside));
        $this->assertFalse($this->factory->isContainFolder($this->tempDir, $outside));

        @unlink($outside);
        @rmdir($outsideDir);
    }

    public function testCopyAndMoveFlow(): void
    {
        $copyPath = $this->tempDir . DIRECTORY_SEPARATOR . 'copied.txt';
        $movedPath = $this->tempDir . DIRECTORY_SEPARATOR . 'moved.txt';

        $this->assertTrue($this->factory->copy($this->filePath, $copyPath));
        $this->assertSame('testSuccess', $this->factory->readAllContent($copyPath)->getRawData());

        $this->assertTrue($this->factory->move($copyPath, $movedPath));
        $this->assertFalse(file_exists($copyPath));
        $this->assertTrue(file_exists($movedPath));
    }

    public function testIsEmptyAndDelete(): void
    {
        $emptyPath = $this->tempDir . DIRECTORY_SEPARATOR . 'empty.txt';
        $this->factory->write($emptyPath, '');
        $this->assertTrue($this->factory->isEmpty($emptyPath));

        $this->factory->appendContent($emptyPath, 'x');
        $this->assertFalse($this->factory->isEmpty($emptyPath));

        $this->assertTrue($this->factory->delete($emptyPath));
        $this->assertFalse(file_exists($emptyPath));
    }

    public function testDeleteThrowsForMissingFile(): void
    {
        $missing = $this->tempDir . DIRECTORY_SEPARATOR . 'missing.txt';

        $this->expectException(FileNotFoundException::class);
        $this->factory->delete($missing);
    }

    public function testIsEqualDetectsSameAndDifferentFiles(): void
    {
        $same = $this->tempDir . DIRECTORY_SEPARATOR . 'same.txt';
        $diff = $this->tempDir . DIRECTORY_SEPARATOR . 'diff.txt';
        file_put_contents($same, 'testSuccess');
        file_put_contents($diff, 'DIFFERENT');

        $this->assertTrue($this->factory->isEqual($this->filePath, $same));
        $this->assertFalse($this->factory->isEqual($this->filePath, $diff));
    }
}
