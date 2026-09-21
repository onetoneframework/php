<?php

declare(strict_types=1);

namespace Clover\Tests\Framework\Component;

use Clover\Framework\Component\Renderer;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class RendererTest extends TestCase
{
    private array $tempFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    public function testRenderReturnsInterpretedPhpTemplateContent(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'renderer_');
        $this->assertNotFalse($file);
        $this->tempFiles[] = $file;

        file_put_contents($file, 'Hello <?= $name ?>');

        $renderer = new Renderer();
        $result = $renderer->render($file, ['name' => 'Framework']);

        $this->assertSame('Hello Framework', trim((string) $result));
    }

    public function testRenderWithCTemplateThrowsWhenExtensionNotLoaded(): void
    {
        $renderer = new Renderer();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('template engine could not be found');
        $renderer->renderWithCTemplate('ignored.tpl', []);
    }
}
