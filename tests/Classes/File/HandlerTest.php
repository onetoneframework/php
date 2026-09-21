<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Tests\File;

use Clover\Classes\File\Handler;
use PHPUnit\Framework\TestCase;

class HandlerTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/onetone_tests_' . uniqid();
        if (!mkdir($this->tempDir)) {
            $this->markTestSkipped('Could not create temporary directory.');
        }
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? $this->removeDirectory("$dir/$file") : unlink("$dir/$file");
        }
        rmdir($dir);
    }

    public function testWriteAndRead(): void
    {
        $file = $this->tempDir . '/test.txt';
        $content = 'Hello World';
        
        $this->assertTrue(Handler::write($file, $content));
        $this->assertTrue(Handler::isExists($file));
        $this->assertEquals($content, Handler::getContent($file));
    }

    public function testAppendContent(): void
    {
        $file = $this->tempDir . '/append.txt';
        Handler::write($file, 'Hello');
        
        $this->assertTrue(Handler::appendContent($file, ' World'));
        $this->assertEquals('Hello World', Handler::getContent($file));
    }

    public function testDelete(): void
    {
        $file = $this->tempDir . '/delete.txt';
        Handler::write($file, 'content');
        
        $this->assertTrue(Handler::isExists($file));
        $this->assertTrue(Handler::delete($file));
        $this->assertFalse(Handler::isExists($file));
    }

    public function testCopy(): void
    {
        $src = $this->tempDir . '/src.txt';
        $dest = $this->tempDir . '/dest.txt';
        Handler::write($src, 'data');
        
        $this->assertTrue(Handler::copy($src, $dest));
        $this->assertTrue(Handler::isExists($dest));
        $this->assertEquals('data', Handler::getContent($dest));
    }

    public function testMove(): void
    {
        $src = $this->tempDir . '/move_src.txt';
        $dest = $this->tempDir . '/move_dest.txt';
        Handler::write($src, 'data');
        
        $this->assertTrue(Handler::move($src, $dest));
        $this->assertFalse(Handler::isExists($src));
        $this->assertTrue(Handler::isExists($dest));
        $this->assertEquals('data', Handler::getContent($dest));
    }

    public function testGetSize(): void
    {
        $file = $this->tempDir . '/size.txt';
        $content = '12345';
        Handler::write($file, $content);
        
        $this->assertEquals(5, Handler::getSize($file));
    }

    public function testGetExtension(): void
    {
        $file = $this->tempDir . '/image.png';
        
        $this->assertEquals('png', Handler::getExtension($file));
    }

    public function testGetBasename(): void
    {
        $file = '/path/to/file.txt';
        
        $this->assertEquals('file.txt', Handler::getBasename($file));
        $this->assertEquals('file', Handler::getBasename($file, '.txt'));
    }

    public function testIsWritable(): void
    {
        $file = $this->tempDir . '/writable.txt';
        Handler::write($file, 'content');
        
        $this->assertTrue(Handler::isWritable($file));
    }

    public function testIsReadable(): void
    {
        $file = $this->tempDir . '/readable.txt';
        Handler::write($file, 'content');
        
        $this->assertTrue(Handler::isReadable($file));
    }

    public function testIsEmpty(): void
    {
        $empty = $this->tempDir . '/empty.txt';
        Handler::write($empty, '');
        $this->assertTrue(Handler::isEmpty($empty));

        $notEmpty = $this->tempDir . '/not_empty.txt';
        Handler::write($notEmpty, 'data');
        $this->assertFalse(Handler::isEmpty($notEmpty));
    }

    public function testIsFile(): void
    {
        $file = $this->tempDir . '/file';
        Handler::write($file, 'content');
        
        $this->assertTrue(Handler::isFile($file));
        $this->assertFalse(Handler::isFile($this->tempDir));
    }

    public function testIsRegularFile(): void
    {
        $file = $this->tempDir . '/regular';
        Handler::write($file, 'content');
        
        $this->assertTrue(Handler::isRegularFile($file));
    }

    public function testReverseContent(): void
    {
        $file = $this->tempDir . '/reverse.txt';
        Handler::write($file, 'abc');
        
        // This method seems to modify file in place?
        // Let's check implementation if possible, assuming it reverses content.
        $this->assertTrue(Handler::reverseContent($file));
        $this->assertEquals('cba', Handler::getContent($file));
    }
    
    public function testClearStatusCache(): void
    {
        // Just verify it doesn't throw
        $file = $this->tempDir . '/cache.txt';
        Handler::write($file, 'test');
        Handler::clearStatusCache($file);
        $this->assertTrue(true);
    }
}
