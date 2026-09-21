<?php

declare(strict_types=1);

namespace Clover\Tests\Framework\Component;

use Clover\Framework\Component\Resource;
use PHPUnit\Framework\TestCase;

final class ResourceTest extends TestCase
{
	protected function setUp(): void
	{
		if (!defined('BASE_PATH')) {
			define(
				'BASE_PATH',
				__DIR__
					. DIRECTORY_SEPARATOR . '..'
					. DIRECTORY_SEPARATOR . '..'
					. DIRECTORY_SEPARATOR . '..'
					. DIRECTORY_SEPARATOR . '..'
					. DIRECTORY_SEPARATOR . 'root'
			);
		}
	}

    public function testTitleSuffixAndExtractState(): void
    {
        $resource = (new Resource())
            ->setTitle('Dashboard')
            ->setTitleSuffix('XF', ' - ')
            ->setCharset('UTF-8')
            ->setLanguage('ko')
            ->setBaseUrl('https://example.com/')
            ->setCanonical('https://example.com/dashboard')
            ->setDescription('desc')
            ->setKeywords(['one', 'two']);

        $extracted = $resource->extract();

        $this->assertSame('Dashboard - XF', $resource->getTitle());
        $this->assertSame('UTF-8', $extracted['charset']);
        $this->assertSame('ko', $extracted['language']);
        $this->assertSame('https://example.com', $extracted['baseUrl']);
        $this->assertSame('https://example.com/dashboard', $extracted['canonical']);
        $metaTags = $extracted['metaTags'];
        if (!is_array($metaTags)) {
            $this->fail('metaTags must be an array');
        }
        $this->assertSame(2, count($metaTags));
    }

    public function testCssAndJavascriptLifecycleMethods(): void
    {
        $resource = new Resource();

        $resource
            ->addGenericCssFile('/assets/app.css')
            ->addGenericJavascriptFile('/assets/app.js');

        $this->assertTrue($resource->hasCssFile('/assets/app.css'));
        $this->assertTrue($resource->hasJavascriptFile('/assets/app.js'));

        $resource->removeCssFile('/assets/app.css')->removeJavascriptFile('/assets/app.js');

        $this->assertFalse($resource->hasCssFile('/assets/app.css'));
        $this->assertFalse($resource->hasJavascriptFile('/assets/app.js'));
    }

    public function testRenderHeadAndBodyContainExpectedTags(): void
    {
        $resource = (new Resource())
            ->setTitle('Home')
            ->addMetaName('description', 'home page')
            ->addFavicon('/favicon.ico')
            ->addInlineCss('body{margin:0;}', 'inline-css')
            ->addInlineScript('window.X=1;', false, 'inline-head-js')
            ->addBodyJavascriptFile('/assets/body.js', ['defer' => true])
            ->addInlineScript('window.Y=2;', true, 'inline-body-js');

        $head = $resource->renderHead();
        $bodyEnd = $resource->renderBodyEnd();

        $this->assertStringContainsString('<title>Home</title>', $head);
        $this->assertStringContainsString('<meta name="description" content="home page">', $head);
        $this->assertStringContainsString('<link rel="icon" href="/favicon.ico" type="image/x-icon">', $head);
        $this->assertStringContainsString('<style id="inline-css">body{margin:0;}</style>', $head);
        $this->assertStringContainsString('<script id="inline-head-js">window.X=1;</script>', $head);
        $this->assertStringContainsString('<script src="http://localhost:8080/assets/body.js" defer></script>', $bodyEnd);
        $this->assertStringContainsString('<script id="inline-body-js">window.Y=2;</script>', $bodyEnd);
    }

	public function testAssetVersionPreservesExistingQueryAndFragment(): void
	{
		$resource = (new Resource(' build 42/alpha '))
			->setBaseUrl('https://assets.example.test')
			->addGenericCssFile('/assets/app.css?theme=dark#main');
		$cssFiles = $resource->extract()['cssMap'];

		$this->assertIsArray($cssFiles);
		$this->assertSame(
			'https://assets.example.test/assets/app.css?theme=dark&v=build%2042%2Falpha#main',
			$cssFiles[0]['href']
		);
	}

	public function testAssetVersionHandlesTrailingQuerySeparator(): void
	{
		$resource = (new Resource('build'))
			->setBaseUrl('https://assets.example.test')
			->addGenericJavascriptFile('/assets/app.js?#loader');
		$scriptFiles = $resource->extract()['scriptMap'];

		$this->assertIsArray($scriptFiles);
		$this->assertSame(
			'https://assets.example.test/assets/app.js?v=build#loader',
			$scriptFiles[0]['src']
		);
	}

	public function testAssetVersionDoesNotChangeExternalResources(): void
	{
		$resource = (new Resource('build'))
			->addGenericCssFile('https://cdn.example.test/app.css?theme=dark#main')
			->addGenericJavascriptFile('//cdn.example.test/app.js');
		$extracted = $resource->extract();

		$this->assertSame(
			'https://cdn.example.test/app.css?theme=dark#main',
			$extracted['cssMap'][0]['href']
		);
		$this->assertSame('//cdn.example.test/app.js', $extracted['scriptMap'][0]['src']);
	}

	public function testEmptyAssetVersionKeepsTimestampCompatibilityWithQueryAndFragment(): void
	{
		$resource = (new Resource('   '))
			->setBaseUrl('https://assets.example.test')
			->addGenericCssFile('/favicon.ico?theme=dark#icon');
		$cssFiles = $resource->extract()['cssMap'];

		$this->assertIsArray($cssFiles);
		$this->assertMatchesRegularExpression(
			'~^https://assets\.example\.test/favicon\.ico\?theme=dark&t=\d+#icon$~',
			$cssFiles[0]['href']
		);
	}
}
