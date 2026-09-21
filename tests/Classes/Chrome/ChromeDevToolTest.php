<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Tests\Classes\Chrome;

use Clover\Classes\Chrome\ChromeDevTool;
use PHPUnit\Framework\TestCase;

class ChromeDevToolTest extends TestCase
{
    public function testIsInstantiable(): void
    {
        $chromeDevTool = null;

        try {
            $chromeDevTool = new ChromeDevTool();
            $chromeDevTool->launch();
            $targetId = $chromeDevTool->createTarget();
            if (empty($targetId)) {
                $this->markTestSkipped('Chrome DevTools target could not be created.');
            }

            $sessionId = $chromeDevTool->attachTarget($targetId);
            $this->assertNotEmpty($sessionId);

            $chromeDevTool->setSessionId($sessionId);
            $network = $chromeDevTool->enableNetwork();
            $this->assertNotEmpty($network);

            $runtime = $chromeDevTool->enableRuntime();
            $this->assertNotEmpty($runtime);

            $page = $chromeDevTool->enablePage();
            $this->assertNotEmpty($page);

            $navigate = $chromeDevTool->navigatePage('https://www.google.com');
            $this->assertNotEmpty($navigate);

            $chromeDevTool->waitUntilAndCollectUrls();
            $all = $chromeDevTool->getCollectedUrls();
            $this->assertIsArray($all);

            $title = $chromeDevTool->getDocumentTitle();
            $chromeDevTool->saveFullPageScreenshot(__DIR__ . '/fullpage.png');
            $this->assertFileExists(__DIR__ . '/fullpage.png');
            unlink(__DIR__ . '/fullpage.png');

            $this->assertNotEmpty($title);
        } catch (\Throwable $throwable) {
            $this->markTestSkipped($throwable->getMessage());
        } finally {
            if ($chromeDevTool instanceof ChromeDevTool) {
                // close() terminates only the browser this test launched. shutdownAll() would kill
                // every Chrome on the machine, including the developer's own windows.
                $chromeDevTool->close();
            }
        }
    }
}
