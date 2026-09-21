<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Event;

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use Clover\Classes\Event\EventLoop;
use Clover\Classes\Event\Promise;
use PHPUnit\Framework\TestCase;
use Clover\Classes\File\Handler as FileHandler;

class AsyncFileTest extends TestCase
{
    private string $testDir;

    protected function setUp(): void
    {
        parent::setUp();
        EventLoop::reset();

        $this->testDir = sys_get_temp_dir() . '/async_file_test_' . uniqid();
        if (!is_dir($this->testDir)) {
            mkdir($this->testDir, 0777, true);
        }
    }

    protected function tearDown(): void
    {
        if (is_dir($this->testDir)) {
            $files = glob($this->testDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    @unlink($file);
                }
            }
            @rmdir($this->testDir);
        }

        EventLoop::reset();
        parent::tearDown();
    }

    private function getTestPath(string $filename): string
    {
        return $this->testDir . '/' . $filename;
    }

    public function testWriteFileBasic(): void
    {
        $filepath = $this->getTestPath('test_write.txt');
        $content = 'Hello, Async World!';
        $result = null;

        FileHandler::writeFileAsync($filepath, $content)
            ->then(function ($success) use (&$result) {
                $result = $success;
            });

        EventLoop::run();

        $this->assertTrue($result);
        $this->assertFileExists($filepath);
        $this->assertSame($content, file_get_contents($filepath));
    }

    public function testReadFileBasic(): void
    {
        $filepath = $this->getTestPath('test_read.txt');
        $content = 'Test content for reading';
        file_put_contents($filepath, $content);

        $result = null;

        FileHandler::readFileAsync($filepath)
            ->then(function ($readContent) use (&$result) {
                $result = $readContent;
            });

        EventLoop::run();

        $this->assertSame($content, $result);
    }

    public function testWriteThenRead(): void
    {
        $filepath = $this->getTestPath('test_chain.txt');
        $content = 'Write then read test';
        $result = null;

        FileHandler::writeFileAsync($filepath, $content)
            ->then(function () use ($filepath) {
                return FileHandler::readFileAsync($filepath);
            })
            ->then(function ($readContent) use (&$result) {
                $result = $readContent;
            });

        EventLoop::run();

        $this->assertSame($content, $result);
    }

    public function testReadNonExistentFile(): void
    {
        $filepath = $this->getTestPath('non_existent.txt');
        $error = null;

        FileHandler::readFileAsync($filepath)
            ->then(function ($content) {
                $this->fail('Should not succeed');
            })
            ->catch(function ($e) use (&$error) {
                $error = $e;
            });

        EventLoop::run();

        $this->assertInstanceOf(\RuntimeException::class, $error);
        $this->assertStringContainsString('File not found', $error->getMessage());
    }

    public function testWriteLargeFile(): void
    {
        $filepath = $this->getTestPath('large_file.txt');

        $content = str_repeat('A', 1024 * 1024);
        $result = null;

        FileHandler::writeFileAsync($filepath, $content)
            ->then(function ($success) use (&$result) {
                $result = $success;
            });

        EventLoop::run();

        $this->assertTrue($result);
        $this->assertFileExists($filepath);
        $this->assertSame(strlen($content), filesize($filepath));
    }

    public function testReadLargeFile(): void
    {
        $filepath = $this->getTestPath('large_read.txt');

        $content = str_repeat('B', 1024 * 1024);
        file_put_contents($filepath, $content);

        $result = null;

        FileHandler::readFileAsync($filepath)
            ->then(function ($readContent) use (&$result) {
                $result = $readContent;
            });

        EventLoop::run();

        $this->assertSame(strlen($content), strlen($result));
        $this->assertSame($content, $result);
    }

    public function testParallelWrites(): void
    {
        $results = (object) ['success' => []];

        $promises = [];
        for ($i = 1; $i <= 5; $i++) {
            $filepath = $this->getTestPath("parallel_$i.txt");
            $content = "Content $i";

            $promises[] = FileHandler::writeFileAsync($filepath, $content)
                ->then(onFulfilled: function ($success) use (&$results, $i) {
                    $results->success[$i] = $success;
                });
        }

        Promise::all($promises)->then(function () {
        });

        EventLoop::run();

        $this->assertCount(5, $results->success);

        for ($i = 1; $i <= 5; $i++) {
            $filepath = $this->getTestPath("parallel_$i.txt");
            $this->assertFileExists($filepath);
            $this->assertSame("Content $i", file_get_contents($filepath));
        }
    }

    public function testUtf8Content(): void
    {
        $filepath = $this->getTestPath('utf8.txt');
        $content = "こんにちは Hello! مرحبا";
        $result = null;

        FileHandler::writeFileAsync($filepath, $content)
            ->then(function () use ($filepath) {
                return FileHandler::readFileAsync($filepath);
            })
            ->then(function ($readContent) use (&$result) {
                $result = $readContent;
            });

        EventLoop::run();

        $this->assertSame($content, $result);
    }

    public function testErrorRecovery(): void
    {
        $results = (object) ['values' => []];

        FileHandler::readFileAsync($this->getTestPath('non_existent.txt'))
            ->catch(function ($e) use (&$results) {
                $results->values[] = 'error_handled';
                return 'recovered';
            })
            ->then(function ($value) use (&$results) {
                $results->values[] = $value;
            });

        EventLoop::run();

        $this->assertSame(['error_handled', 'recovered'], $results->values);
    }

    public function testComplexWorkflow(): void
    {
        $filepath1 = $this->getTestPath('workflow1.txt');
        $filepath2 = $this->getTestPath('workflow2.txt');
        $results = (object) ['steps' => []];

        FileHandler::writeFileAsync($filepath1, 'Step 1')
            ->then(function () use ($filepath1, &$results) {
                $results->steps[] = 'write1';
                return FileHandler::readFileAsync($filepath1);
            })
            ->then(function ($content) use ($filepath2, &$results) {
                $results->steps[] = 'read1: ' . $content;
                return FileHandler::writeFileAsync($filepath2, $content . ' + Step 2');
            })
            ->then(function () use ($filepath2, &$results) {
                $results->steps[] = 'write2';
                return FileHandler::readFileAsync($filepath2);
            })
            ->then(function ($content) use (&$results) {
                $results->steps[] = 'read2: ' . $content;
            });

        EventLoop::run();

        $this->assertCount(4, $results->steps);
        $this->assertSame('write1', $results->steps[0]);
        $this->assertSame('read1: Step 1', $results->steps[1]);
        $this->assertSame('write2', $results->steps[2]);
        $this->assertSame('read2: Step 1 + Step 2', $results->steps[3]);
    }
}
