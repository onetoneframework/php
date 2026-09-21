<?php

declare(strict_types=1);

namespace Clover\Tests\Framework\Component;

use Clover\Framework\Component\PublicPath;
use PHPUnit\Framework\TestCase;

final class PublicPathTest extends TestCase
{
    public function testFromBaseNormalizesToLeadingSlashUrlPath(): void
    {
        $this->assertSame('/App/Frontend/dist/app.css', PublicPath::fromBase('App/Frontend/dist/app.css'));
        $this->assertSame('/App/Frontend/dist/app.css', PublicPath::fromBase('/App/Frontend/dist/app.css'));
    }

    public function testFromBaseAcceptsBackslashes(): void
    {
        $this->assertSame('/App/View/foo.php', PublicPath::fromBase('App\\View\\foo.php'));
    }

    public function testFromBaseEmptyStringIsRootPath(): void
    {
        $this->assertSame('/', PublicPath::fromBase(''));
    }
}
