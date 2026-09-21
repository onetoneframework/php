<?php

declare(strict_types=1);

namespace Clover\Tests\Exception;

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use Clover\Exception\Argument\ArgumentEmptyException;
use Clover\Exception\FileHandler\IOException;
use Clover\Exception\FileHandler\FileNotFoundException;
use PHPUnit\Framework\TestCase;

class ExceptionTest extends TestCase
{
    public function testIOExceptionConstructAndMessage(): void
    {
        $e = new IOException('IO failed', 1);
        $this->assertInstanceOf(\RuntimeException::class, $e);
        $this->assertSame('IO failed', $e->getMessage());
        $this->assertSame(1, $e->getCode());
    }

    public function testIOExceptionWithPrevious(): void
    {
        $previous = new \Exception('previous');
        $e = new IOException('wrapper', 0, $previous);
        $this->assertSame($previous, $e->getPrevious());
    }

    public function testArgumentEmptyException(): void
    {
        $e = new ArgumentEmptyException('Argument must not be empty');
        $this->assertInstanceOf(IOException::class, $e);
        $this->assertSame('Argument must not be empty', $e->getMessage());
    }

    public function testFileNotFoundException(): void
    {
        $e = new FileNotFoundException('File not found', 404);
        $this->assertInstanceOf(IOException::class, $e);
        $this->assertSame(404, $e->getCode());
    }
}
