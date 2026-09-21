<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Linker;

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use Clover\Classes\Linker\IDELink;
use PHPUnit\Framework\TestCase;

class IDELinkTest extends TestCase
{
    public function testBuildVisualStudioCodeUriUsesRealPathAndLineColumn(): void
    {
        $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'idelink_test_' . uniqid('', true);
        mkdir($dir, 0777, true);
        $path = $dir . DIRECTORY_SEPARATOR . 'sample file.php';
        file_put_contents($path, '<?php');

        try {
            $real = realpath($path);
            $this->assertNotFalse($real);
            $uri = IDELink::buildVisualStudioCodeUri($path, 12, 3);
            $normalized = str_replace('\\', '/', $real);
            $encodedName = rawurlencode('sample file.php');
            $this->assertStringStartsWith('vscode://file/', $uri);
            $this->assertStringContainsString($encodedName, $uri);
            $this->assertStringEndsWith(':12:3', $uri);
            $this->assertStringContainsString(str_replace('\\', '/', dirname($real)), str_replace('\\', '/', $uri));
        } finally {
            @unlink($path);
            @rmdir($dir);
        }
    }

    public function testBuildVisualStudioCodeUriClampsNonPositiveLineAndColumn(): void
    {
        $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'idelink_test2_' . uniqid('', true);
        mkdir($dir, 0777, true);
        $path = $dir . DIRECTORY_SEPARATOR . 'a.php';
        file_put_contents($path, '<?php');

        try {
            $uri = IDELink::buildVisualStudioCodeUri($path, 0, 0);
            $this->assertStringEndsWith(':1:1', $uri);
        } finally {
            @unlink($path);
            @rmdir($dir);
        }
    }

    public function testGenerateDelegatesToBuildVisualStudioCodeUriForVsCode(): void
    {
        $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'idelink_test3_' . uniqid('', true);
        mkdir($dir, 0777, true);
        $path = $dir . DIRECTORY_SEPARATOR . 'b.php';
        file_put_contents($path, '<?php');

        try {
            $link = new IDELink();
            $this->assertSame(IDELink::buildVisualStudioCodeUri($path, 5), $link->generate($path, 5));
        } finally {
            @unlink($path);
            @rmdir($dir);
        }
    }
}
