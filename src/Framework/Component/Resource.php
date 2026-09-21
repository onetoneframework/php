<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Framework\Component;

use Clover\Classes\File\Functions as FileFunctions;

use function sprintf;
use function is_array;
use function defined;

/**
 * Resource Component
 */
class Resource
{
    /**
     * @var array<string, string>
     */
    private array $adoptedCssFiles = [];

    /**
     * @var array<string, string>
     */
    private array $adoptedScriptFiles = [];

    /**
     * @var array<string, string>
     */
    private array $adoptedBodyCssFiles = [];

    /**
     * @var array<string, string>
     */
    private array $adoptedBodyScriptFiles = [];

    /**
     * @var array<string, string>
     */
    private array $inlineCss = [];

    /**
     * @var array<string, string>
     */
    private array $inlineScripts = [];

    /**
     * @var array<string, string>
     */
    private array $bodyInlineScripts = [];

    private array $metaTags = [];

    private array $linkTags = [];

    /**
     * @var array<string, string>
     */
    private array $resourceHints = [];

    private ?string $title = null;

    private ?string $titleSeparator = ' | ';

    private ?string $titleSuffix = null;

    private ?string $baseUrl = null;

    private ?string $charset = 'UTF-8';

    private ?string $language = null;

    private ?string $canonical = null;

    private array $alternateLinks = [];

	private ?string $assetVersion;

    /**
     * Constructor
	 *
	 * @param string|null $assetVersion Stable version identifier for local assets.
     */
	public function __construct(?string $assetVersion = null)
    {
		$normalizedAssetVersion = $assetVersion === null ? '' : trim($assetVersion);
		$this->assetVersion = $normalizedAssetVersion === '' ? null : $normalizedAssetVersion;
    }

    /**
     * Set title
     *
     * @param string $title
     *
     * @return static
     */
    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string|null
     */
    public function getTitle(): ?string
    {
        if ($this->title === null) {
            return null;
        }

        if ($this->titleSuffix !== null) {
            return $this->title . $this->titleSeparator . $this->titleSuffix;
        }

        return $this->title;
    }

    /**
     * Set title suffix
     *
     * @param string $suffix
     * @param string $separator
     *
     * @return static
     */
    public function setTitleSuffix(string $suffix, string $separator = ' | '): static
    {
        $this->titleSuffix = $suffix;
        $this->titleSeparator = $separator;

        return $this;
    }

    /**
     * Set charset
     *
     * @param string $charset
     *
     * @return static
     */
    public function setCharset(string $charset): static
    {
        $this->charset = $charset;

        return $this;
    }

    /**
     * Get charset
     *
     * @return string|null
     */
    public function getCharset(): ?string
    {
        return $this->charset;
    }

    /**
     * Set language
     *
     * @param string $language
     *
     * @return static
     */
    public function setLanguage(string $language): static
    {
        $this->language = $language;

        return $this;
    }

    /**
     * Get language
     *
     * @return string|null
     */
    public function getLanguage(): ?string
    {
        return $this->language;
    }

    /**
     * Set base URL
     *
     * @param string $url
     *
     * @return static
     */
    public function setBaseUrl(string $url): static
    {
        $this->baseUrl = rtrim($url, '/');

        return $this;
    }

    /**
     * Get base URL
     *
     * @return string|null
     */
    public function getBaseUrl(): ?string
    {
        return $this->baseUrl;
    }

    /**
     * Set canonical URL
     *
     * @param string $url
     *
     * @return static
     */
    public function setCanonical(string $url): static
    {
        $this->canonical = $url;

        return $this;
    }

    /**
     * Get canonical URL
     *
     * @return string|null
     */
    public function getCanonical(): ?string
    {
        return $this->canonical;
    }

    /**
     * Add alternate link (for hreflang)
     *
     * @param string $href
     * @param string $hreflang
     *
     * @return static
     */
    public function addAlternateLink(string $href, string $hreflang): static
    {
        $this->alternateLinks[] = [
            'rel' => 'alternate',
            'href' => $href,
            'hreflang' => $hreflang
        ];

        return $this;
    }

    /**
     * Add generic CSS file
     *
     * @param string $file
     *
     * @return static
     */
    public function addGenericCssFile(string $file): static
    {
        $this->adoptedCssFiles[] = [
            'rel' => 'stylesheet',
            'href' => $this->appendTimestampToResource($file),
            'type' => 'text/css',
            'media' => 'all'
        ];

        return $this;
    }

    /**
     * Add CSS file with options
     *
     * @param string $file
     * @param array $options
     *
     * @return static
     */
    public function addCssFileWithOptions(string $file, array $options = []): static
    {
        $attributes = array_merge([
            'rel' => 'stylesheet',
            'href' => $this->appendTimestampToResource($file),
            'type' => 'text/css',
            'media' => $options['media'] ?? 'all'
        ], $options);

        unset($attributes['media']);
        $attributes['media'] = $options['media'] ?? 'all';

        if (isset($options['integrity'])) {
            $attributes['integrity'] = $options['integrity'];
            $attributes['crossorigin'] = $options['crossorigin'] ?? 'anonymous';
        }

        $this->adoptedCssFiles[] = $attributes;

        return $this;
    }

    /**
     * Add CSS file to body
     *
     * @param string $file
     * @param array $options
     *
     * @return static
     */
    public function addBodyCssFile(string $file, array $options = []): static
    {
        $attributes = array_merge([
            'rel' => 'stylesheet',
            'href' => $this->appendTimestampToResource($file),
            'type' => 'text/css',
            'media' => 'all'
        ], $options);

        $this->adoptedBodyCssFiles[] = $attributes;

        return $this;
    }

    /**
     * Add inline CSS
     *
     * @param string $css
     * @param string|null $id
     *
     * @return static
     */
    public function addInlineCss(string $css, ?string $id = null): static
    {
        $this->inlineCss[] = [
            'content' => $css,
            'id' => $id
        ];

        return $this;
    }

    /**
     * Append timestamp to resource URL
     *
     * @param string $file
     *
     * @return string
     */
    protected function appendTimestampToResource(string $file): string
    {
        if (str_starts_with($file, 'http') || str_starts_with($file, '//')) {
            return $file;
        }

		if ($this->assetVersion !== null) {
			return $this->toPublicAbsoluteUrl(
				$this->appendCacheParameter($file, 'v', $this->assetVersion)
			);
		}

		$resourcePath = substr($file, 0, strcspn($file, '?#'));
		$fullPath = (defined('BASE_PATH') ? BASE_PATH : '') . $resourcePath;

        if (!file_exists($fullPath)) {
            return $this->toPublicAbsoluteUrl($file);
        }

        $createdDate = FileFunctions::getCreatedDate($fullPath);

		return $this->toPublicAbsoluteUrl(
			$this->appendCacheParameter($file, 't', sprintf('%d', $createdDate))
		);
    }

	/**
	 * Append a cache identifier without changing existing query parameters or fragments.
	 *
	 * @param string $resourceUrl Local resource URL.
	 * @param string $parameterName Cache parameter name.
	 * @param string $parameterValue Cache parameter value.
	 *
	 * @return string Resource URL containing the cache parameter.
	 */
	private function appendCacheParameter(string $resourceUrl, string $parameterName, string $parameterValue): string
	{
		$fragmentPosition = strpos($resourceUrl, '#');
		if ($fragmentPosition === false) {
			$urlWithoutFragment = $resourceUrl;
			$fragment = '';
		} else {
			$urlWithoutFragment = substr($resourceUrl, 0, $fragmentPosition);
			$fragment = substr($resourceUrl, $fragmentPosition);
		}

		$querySeparator = str_contains($urlWithoutFragment, '?') ? '&' : '?';
		if (str_ends_with($urlWithoutFragment, '?') || str_ends_with($urlWithoutFragment, '&')) {
			$querySeparator = '';
		}

		return $urlWithoutFragment
			. $querySeparator
			. rawurlencode($parameterName)
			. '='
			. rawurlencode($parameterValue)
			. $fragment;
	}

    /**
     * Prefix app-relative href/src with public origin (APP_PUBLIC_URL or {@see setBaseUrl}).
     */
    private function toPublicAbsoluteUrl(string $pathWithQuery): string
    {
        if (str_starts_with($pathWithQuery, 'http') || str_starts_with($pathWithQuery, '//')) {
            return $pathWithQuery;
        }

        $base = $this->baseUrl !== null && $this->baseUrl !== ''
            ? rtrim($this->baseUrl, '/')
            : AppUrl::base();

        $qPos = strpos($pathWithQuery, '?');
        if ($qPos !== false) {
            $pathPart = substr($pathWithQuery, 0, $qPos);
            $queryPart = substr($pathWithQuery, $qPos);
        } else {
            $pathPart = $pathWithQuery;
            $queryPart = '';
        }

        if ($pathPart !== '' && !str_starts_with($pathPart, '/')) {
            $pathPart = '/' . $pathPart;
        }

        return $base . $pathPart . $queryPart;
    }

    /**
     * Add generic JavaScript file
     *
     * @param string $file
     *
     * @return static
     */
    public function addGenericJavascriptFile(string $file): static
    {
        $this->adoptedScriptFiles[] = ['src' => $this->appendTimestampToResource($file)];

        return $this;
    }

    /**
     * Add module JavaScript file
     *
     * @param string $file
     *
     * @return static
     */
    public function addModuleJavascriptFile(string $file): static
    {
        $this->adoptedScriptFiles[] = ['src' => $this->appendTimestampToResource($file), 'type' => 'module'];

        return $this;
    }

    /**
     * Add JavaScript file with options
     *
     * @param string $file
     * @param array $options
     *
     * @return static
     */
    public function addJavascriptFileWithOptions(string $file, array $options = []): static
    {
        $attributes = ['src' => $this->appendTimestampToResource($file)];

        if (isset($options['async']) && $options['async']) {
            $attributes['async'] = true;
        }

        if (isset($options['defer']) && $options['defer']) {
            $attributes['defer'] = true;
        }

        if (isset($options['type'])) {
            $attributes['type'] = $options['type'];
        }

        if (isset($options['integrity'])) {
            $attributes['integrity'] = $options['integrity'];
            $attributes['crossorigin'] = $options['crossorigin'] ?? 'anonymous';
        }

        if (isset($options['nomodule']) && $options['nomodule']) {
            $attributes['nomodule'] = true;
        }

        $this->adoptedScriptFiles[] = $attributes;

        return $this;
    }

    /**
     * Add async JavaScript file
     *
     * @param string $file
     *
     * @return static
     */
    public function addAsyncJavascriptFile(string $file): static
    {
        return $this->addJavascriptFileWithOptions($file, ['async' => true]);
    }

    /**
     * Add deferred JavaScript file
     *
     * @param string $file
     *
     * @return static
     */
    public function addDeferredJavascriptFile(string $file): static
    {
        return $this->addJavascriptFileWithOptions($file, ['defer' => true]);
    }

    /**
     * Add JavaScript file to body
     *
     * @param string $file
     * @param array $options
     *
     * @return static
     */
    public function addBodyJavascriptFile(string $file, array $options = []): static
    {
        $attributes = array_merge(['src' => $this->appendTimestampToResource($file)], $options);
        $this->adoptedBodyScriptFiles[] = $attributes;

        return $this;
    }

    /**
     * Add inline script
     *
     * @param string $script
     * @param bool $inBody
     * @param string|null $id
     *
     * @return static
     */
    public function addInlineScript(string $script, bool $inBody = false, ?string $id = null): static
    {
        $data = [
            'content' => $script,
            'id' => $id
        ];

        if ($inBody) {
            $this->bodyInlineScripts[] = $data;
        } else {
            $this->inlineScripts[] = $data;
        }

        return $this;
    }

    /**
     * Add JavaScript file
     *
     * @param array $map
     *
     * @return static
     */
    public function addJavascriptFile(array $map): static
    {
        $this->adoptedScriptFiles[] = $map;

        return $this;
    }

    /**
     * Add CSS file
     *
     * @param array $map
     *
     * @return static
     */
    public function addCssFile(array $map): static
    {
        $this->adoptedCssFiles[] = $map;

        return $this;
    }

    /**
     * Add meta tag
     *
     * @param array $map
     *
     * @return static
     */
    public function addMetaTag(array $map): static
    {
        $this->metaTags[] = $map;

        return $this;
    }

    /**
     * Add meta tag by name
     *
     * @param string $name
     * @param string $content
     *
     * @return static
     */
    public function addMetaName(string $name, string $content): static
    {
        $this->metaTags[] = ['name' => $name, 'content' => $content];

        return $this;
    }

    /**
     * Add meta tag by property (for Open Graph)
     *
     * @param string $property
     * @param string $content
     *
     * @return static
     */
    public function addMetaProperty(string $property, string $content): static
    {
        $this->metaTags[] = ['property' => $property, 'content' => $content];

        return $this;
    }

    /**
     * Add http-equiv meta tag
     *
     * @param string $httpEquiv
     * @param string $content
     *
     * @return static
     */
    public function addMetaHttpEquiv(string $httpEquiv, string $content): static
    {
        $this->metaTags[] = ['http-equiv' => $httpEquiv, 'content' => $content];

        return $this;
    }

    /**
     * Set description meta tag
     *
     * @param string $description
     *
     * @return static
     */
    public function setDescription(string $description): static
    {
        return $this->addMetaName('description', $description);
    }

    /**
     * Set keywords meta tag
     *
     * @param array|string $keywords
     *
     * @return static
     */
    public function setKeywords(array|string $keywords): static
    {
        if (is_array($keywords)) {
            $keywords = implode(', ', $keywords);
        }

        return $this->addMetaName('keywords', $keywords);
    }

    /**
     * Set robots meta tag
     *
     * @param string $value
     *
     * @return static
     */
    public function setRobots(string $value): static
    {
        return $this->addMetaName('robots', $value);
    }

    /**
     * Set viewport meta tag
     *
     * @param string $content
     *
     * @return static
     */
    public function setViewport(string $content = 'width=device-width, initial-scale=1.0'): static
    {
        return $this->addMetaName('viewport', $content);
    }

    /**
     * Set Open Graph meta tags
     *
     * @param array $data
     *
     * @return static
     */
    public function setOpenGraph(array $data): static
    {
        $mapping = [
            'title' => 'og:title',
            'description' => 'og:description',
            'type' => 'og:type',
            'url' => 'og:url',
            'image' => 'og:image',
            'site_name' => 'og:site_name',
            'locale' => 'og:locale'
        ];

        foreach ($data as $key => $value) {
            $property = $mapping[$key] ?? 'og:' . $key;
            $this->addMetaProperty($property, $value);
        }

        return $this;
    }

    /**
     * Set Twitter Card meta tags
     *
     * @param array $data
     *
     * @return static
     */
    public function setTwitterCard(array $data): static
    {
        $mapping = [
            'card' => 'twitter:card',
            'site' => 'twitter:site',
            'creator' => 'twitter:creator',
            'title' => 'twitter:title',
            'description' => 'twitter:description',
            'image' => 'twitter:image'
        ];

        foreach ($data as $key => $value) {
            $name = $mapping[$key] ?? 'twitter:' . $key;
            $this->addMetaName($name, $value);
        }

        return $this;
    }

    /**
     * Add link tag
     *
     * @param array $attributes
     *
     * @return static
     */
    public function addLinkTag(array $attributes): static
    {
        $this->linkTags[] = $attributes;

        return $this;
    }

    /**
     * Add favicon
     *
     * @param string $href
     * @param string $type
     *
     * @return static
     */
    public function addFavicon(string $href, string $type = 'image/x-icon'): static
    {
        $this->linkTags[] = [
            'rel' => 'icon',
            'href' => $href,
            'type' => $type
        ];

        return $this;
    }

    /**
     * Add Apple touch icon
     *
     * @param string $href
     * @param string|null $sizes
     *
     * @return static
     */
    public function addAppleTouchIcon(string $href, ?string $sizes = null): static
    {
        $attributes = [
            'rel' => 'apple-touch-icon',
            'href' => $href
        ];

        if ($sizes !== null) {
            $attributes['sizes'] = $sizes;
        }

        $this->linkTags[] = $attributes;

        return $this;
    }

    /**
     * Add preload resource hint
     *
     * @param string $href
     * @param string $as
     * @param array $options
     *
     * @return static
     */
    public function addPreload(string $href, string $as, array $options = []): static
    {
        $attributes = array_merge([
            'rel' => 'preload',
            'href' => $href,
            'as' => $as
        ], $options);

        if ($as === 'font') {
            $attributes['crossorigin'] = $options['crossorigin'] ?? 'anonymous';
        }

        $this->resourceHints[] = $attributes;

        return $this;
    }

    /**
     * Add prefetch resource hint
     *
     * @param string $href
     *
     * @return static
     */
    public function addPrefetch(string $href): static
    {
        $this->resourceHints[] = [
            'rel' => 'prefetch',
            'href' => $href
        ];

        return $this;
    }

    /**
     * Add preconnect resource hint
     *
     * @param string $href
     * @param bool $crossorigin
     *
     * @return static
     */
    public function addPreconnect(string $href, bool $crossorigin = false): static
    {
        $attributes = [
            'rel' => 'preconnect',
            'href' => $href
        ];

        if ($crossorigin) {
            $attributes['crossorigin'] = 'anonymous';
        }

        $this->resourceHints[] = $attributes;

        return $this;
    }

    /**
     * Add dns-prefetch resource hint
     *
     * @param string $href
     *
     * @return static
     */
    public function addDnsPrefetch(string $href): static
    {
        $this->resourceHints[] = [
            'rel' => 'dns-prefetch',
            'href' => $href
        ];

        return $this;
    }

    /**
     * Check if CSS file exists
     *
     * @param string $href
     *
     * @return bool
     */
    public function hasCssFile(string $href): bool
    {
        foreach ($this->adoptedCssFiles as $css) {
            if (isset($css['href']) && str_contains($css['href'], $href)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if JavaScript file exists
     *
     * @param string $src
     *
     * @return bool
     */
    public function hasJavascriptFile(string $src): bool
    {
        foreach ($this->adoptedScriptFiles as $script) {
            if (isset($script['src']) && str_contains($script['src'], $src)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Remove CSS file
     *
     * @param string $href
     *
     * @return static
     */
    public function removeCssFile(string $href): static
    {
        $this->adoptedCssFiles = array_filter($this->adoptedCssFiles, function ($css) use ($href) {
            return !isset($css['href']) || !str_contains($css['href'], $href);
        });

        return $this;
    }

    /**
     * Remove JavaScript file
     *
     * @param string $src
     *
     * @return static
     */
    public function removeJavascriptFile(string $src): static
    {
        $this->adoptedScriptFiles = array_filter($this->adoptedScriptFiles, function ($script) use ($src) {
            return !isset($script['src']) || !str_contains($script['src'], $src);
        });

        return $this;
    }

    /**
     * Clear all CSS files
     *
     * @return static
     */
    public function clearCssFiles(): static
    {
        $this->adoptedCssFiles = [];
        $this->adoptedBodyCssFiles = [];

        return $this;
    }

    /**
     * Clear all JavaScript files
     *
     * @return static
     */
    public function clearJavascriptFiles(): static
    {
        $this->adoptedScriptFiles = [];
        $this->adoptedBodyScriptFiles = [];

        return $this;
    }

    /**
     * Clear all meta tags
     *
     * @return static
     */
    public function clearMetaTags(): static
    {
        $this->metaTags = [];

        return $this;
    }

    /**
     * Clear all resources
     *
     * @return static
     */
    public function clear(): static
    {
        $this->adoptedCssFiles = [];
        $this->adoptedScriptFiles = [];
        $this->adoptedBodyCssFiles = [];
        $this->adoptedBodyScriptFiles = [];
        $this->inlineCss = [];
        $this->inlineScripts = [];
        $this->bodyInlineScripts = [];
        $this->metaTags = [];
        $this->linkTags = [];
        $this->resourceHints = [];
        $this->alternateLinks = [];
        $this->title = null;
        $this->canonical = null;

        return $this;
    }

    /**
     * Extract resource data
     *
     * @return array<string, string>
     */
    public function extract(): array
    {
        return [
            'charset' => $this->charset,
            'language' => $this->language,
            'title' => $this->getTitle(),
            'baseUrl' => $this->baseUrl,
            'canonical' => $this->canonical,
            'metaTags' => $this->metaTags,
            'linkTags' => $this->linkTags,
            'resourceHints' => $this->resourceHints,
            'alternateLinks' => $this->alternateLinks,
            'cssMap' => $this->adoptedCssFiles,
            'bodyCssMap' => $this->adoptedBodyCssFiles,
            'inlineCss' => $this->inlineCss,
            'scriptMap' => $this->adoptedScriptFiles,
            'bodyScriptMap' => $this->adoptedBodyScriptFiles,
            'inlineScripts' => $this->inlineScripts,
            'bodyInlineScripts' => $this->bodyInlineScripts
        ];
    }

    /**
     * Render HTML attributes from array
     *
     * @param array $attributes
     *
     * @return string
     */
    protected function renderAttributes(array $attributes): string
    {
        $parts = [];

        foreach ($attributes as $key => $value) {
            if ($value === true) {
                $parts[] = $key;
            } elseif ($value !== false && $value !== null) {
                $parts[] = sprintf('%s="%s"', $key, htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'));
            }
        }

        return implode(' ', $parts);
    }

    /**
     * Render head HTML
     *
     * @return string
     */
    public function renderHead(): string
    {
        $html = [];

        if ($this->charset) {
            $html[] = sprintf('<meta charset="%s">', htmlspecialchars($this->charset, ENT_QUOTES, 'UTF-8'));
        }

        if ($this->title !== null) {
            $html[] = sprintf('<title>%s</title>', htmlspecialchars($this->getTitle(), ENT_QUOTES, 'UTF-8'));
        }

        if ($this->baseUrl) {
            $html[] = sprintf('<base href="%s">', htmlspecialchars($this->baseUrl, ENT_QUOTES, 'UTF-8'));
        }

        foreach ($this->metaTags as $meta) {
            $html[] = sprintf('<meta %s>', $this->renderAttributes($meta));
        }

        if ($this->canonical) {
            $html[] = sprintf('<link rel="canonical" href="%s">', htmlspecialchars($this->canonical, ENT_QUOTES, 'UTF-8'));
        }

        foreach ($this->alternateLinks as $link) {
            $html[] = sprintf('<link %s>', $this->renderAttributes($link));
        }

        foreach ($this->resourceHints as $hint) {
            $html[] = sprintf('<link %s>', $this->renderAttributes($hint));
        }

        foreach ($this->linkTags as $link) {
            $html[] = sprintf('<link %s>', $this->renderAttributes($link));
        }

        foreach ($this->adoptedCssFiles as $css) {
            $html[] = sprintf('<link %s>', $this->renderAttributes($css));
        }

        foreach ($this->inlineCss as $css) {
            $id = $css['id'] ? sprintf(' id="%s"', htmlspecialchars($css['id'], ENT_QUOTES, 'UTF-8')) : '';
            $html[] = sprintf('<style%s>%s</style>', $id, $css['content']);
        }

        foreach ($this->adoptedScriptFiles as $script) {
            $html[] = sprintf('<script %s></script>', $this->renderAttributes($script));
        }

        foreach ($this->inlineScripts as $script) {
            $id = $script['id'] ? sprintf(' id="%s"', htmlspecialchars($script['id'], ENT_QUOTES, 'UTF-8')) : '';
            $html[] = sprintf('<script%s>%s</script>', $id, $script['content']);
        }

        return implode("\n", $html);
    }

    /**
     * Render body end HTML
     *
     * @return string
     */
    public function renderBodyEnd(): string
    {
        $html = [];

        foreach ($this->adoptedBodyCssFiles as $css) {
            $html[] = sprintf('<link %s>', $this->renderAttributes($css));
        }

        foreach ($this->adoptedBodyScriptFiles as $script) {
            $html[] = sprintf('<script %s></script>', $this->renderAttributes($script));
        }

        foreach ($this->bodyInlineScripts as $script) {
            $id = $script['id'] ? sprintf(' id="%s"', htmlspecialchars($script['id'], ENT_QUOTES, 'UTF-8')) : '';
            $html[] = sprintf('<script%s>%s</script>', $id, $script['content']);
        }

        return implode("\n", $html);
    }
}
