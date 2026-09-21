<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Directory;

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use PHPUnit\Framework\TestCase;
use Clover\Classes\Directory\Handler as DirectoryHandler;
use Clover\Exception\DirectoryHandler\DirectoryIsNotExistsException;

class DirectoryHandlerTest extends TestCase
{
    private string $tempRoot;

    protected function setUp(): void
    {
        $this->tempRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'dir_handler_test_' . uniqid('', true);
        mkdir($this->tempRoot, 0777, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempRoot)) {
            DirectoryHandler::delete($this->tempRoot);
        }
        DirectoryHandler::setMaxDepth(-1);
    }

    public function testExistsAndIsEmpty(): void
    {
        $emptyDir = $this->tempRoot . DIRECTORY_SEPARATOR . 'empty';
        $filledDir = $this->tempRoot . DIRECTORY_SEPARATOR . 'filled';
        mkdir($emptyDir, 0777, true);
        mkdir($filledDir, 0777, true);
        file_put_contents($filledDir . DIRECTORY_SEPARATOR . 'a.txt', 'a');

        $this->assertTrue(DirectoryHandler::exists($emptyDir));
        $this->assertTrue(DirectoryHandler::isEmpty($emptyDir));
        $this->assertFalse(DirectoryHandler::isEmpty($filledDir));
        $this->assertFalse(DirectoryHandler::exists($this->tempRoot . DIRECTORY_SEPARATOR . 'missing'));
    }

    public function testGetFileCountCountsImmediateChildrenOnly(): void
    {
        $dir = $this->tempRoot . DIRECTORY_SEPARATOR . 'count';
        mkdir($dir, 0777, true);
        mkdir($dir . DIRECTORY_SEPARATOR . 'sub', 0777, true);
        file_put_contents($dir . DIRECTORY_SEPARATOR . 'a.txt', 'a');
        file_put_contents($dir . DIRECTORY_SEPARATOR . 'b.log', 'b');
        file_put_contents($dir . DIRECTORY_SEPARATOR . 'sub' . DIRECTORY_SEPARATOR . 'nested.txt', 'nested');

        $this->assertSame(3, DirectoryHandler::getFileCount($dir));
    }

    public function testGetFileCountThrowsForMissingDirectory(): void
    {
        $this->expectException(DirectoryIsNotExistsException::class);
        DirectoryHandler::getFileCount($this->tempRoot . DIRECTORY_SEPARATOR . 'missing');
    }

    public function testGetFileCountRecursiveSupportsExtensionFilterAndDepth(): void
    {
        $dir = $this->tempRoot . DIRECTORY_SEPARATOR . 'recursive';
        mkdir($dir . DIRECTORY_SEPARATOR . 'sub' . DIRECTORY_SEPARATOR . 'deep', 0777, true);
        file_put_contents($dir . DIRECTORY_SEPARATOR . 'root.php', '<?php');
        file_put_contents($dir . DIRECTORY_SEPARATOR . 'root.txt', 't');
        file_put_contents($dir . DIRECTORY_SEPARATOR . 'sub' . DIRECTORY_SEPARATOR . 'sub.php', '<?php');
        file_put_contents($dir . DIRECTORY_SEPARATOR . 'sub' . DIRECTORY_SEPARATOR . 'deep' . DIRECTORY_SEPARATOR . 'deep.php', '<?php');

        $this->assertSame(4, DirectoryHandler::getFileCountRecursive($dir));
        $this->assertSame(3, DirectoryHandler::getFileCountRecursive($dir, ['php']));

        DirectoryHandler::setMaxDepth(0);
        $this->assertSame(2, DirectoryHandler::getFileCountRecursive($dir));
    }

    public function testClearFilesRemovesOnlyFilesAndKeepsDirectories(): void
    {
        $dir = $this->tempRoot . DIRECTORY_SEPARATOR . 'clear';
        $sub = $dir . DIRECTORY_SEPARATOR . 'sub';
        mkdir($sub, 0777, true);
        file_put_contents($dir . DIRECTORY_SEPARATOR . 'a.txt', 'a');
        file_put_contents($sub . DIRECTORY_SEPARATOR . 'b.txt', 'b');

        $this->assertTrue(DirectoryHandler::clearFiles($dir));
        $this->assertTrue(is_dir($sub));
        $this->assertSame(0, DirectoryHandler::getFileCountRecursive($dir));
    }
}
