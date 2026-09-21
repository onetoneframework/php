<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Cralwer;
use Clover\Classes\Crawler\CrawlerNode;
use DOMDocument;
use DOMXPath;
use function addslashes;
use function libxml_clear_errors;
use function libxml_use_internal_errors;
use function sprintf;

/**
 * A parsed HTML/XML document; entry-point for all selection methods.
 */
final class CrawlerDocument
{
    private function __construct(
        private readonly DOMDocument $dom,
        private readonly DOMXPath $xpath,
        private readonly string $baseUrl,
    ) {
    }

    /**
     * Parse raw HTML/XML without making an HTTP request.
     * 
     * @param string $html The HTML or XML content to parse.
     * @param string $baseUrl Optional base URL for resolving relative links.
     * @return CrawlerDocument The parsed document ready for element selection.
     */
    public static function fromHtml(string $html, string $baseUrl = ''): self
    {
        $dom = new DOMDocument('1.0', 'UTF-8');

        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET);
        libxml_clear_errors();

        return new self($dom, new DOMXPath($dom), $baseUrl);
    }

    /**
     * Select all elements matching a CSS selector string.
     *
     * Supports: tag, #id, .class, [attr], [attr=val], [attr^=], [attr$=],
     *           [attr*=], tag.class, tag#id, descendant ( ), child (>),
     *           adjacent sibling (+), general sibling (~), and comma lists.
     * 
     * @param string $cssSelector The CSS selector to match elements against.
     * @return CrawlerNodeList A list of matching nodes (empty if no matches).
     */
    public function select(string $cssSelector): CrawlerNodeList
    {
        $xpath = CssSelectorConverter::toXPath($cssSelector);
        $result = $this->xpath->query($xpath);

        if ($result === false) {
            return CrawlerNodeList::empty();
        }

        return CrawlerNodeList::fromDomNodeList($result, $this->xpath, $this->baseUrl);
    }

    /**
     * Select the first element matching a CSS selector, or `null` if none.
     * 
     * @param string $cssSelector The CSS selector to match the element against.
     * @return CrawlerNode|null The first matching node, or null if no matches.
     */
    public function selectFirst(string $cssSelector): ?CrawlerNode
    {
        return $this->select($cssSelector)->first();
    }

    /**
     * Check if any elements match the given CSS selector.
     * 
     * @param string $cssSelector The CSS selector to check for matches.
     * @return bool True if at least one element matches, false otherwise.
     */
    public function exists(string $cssSelector): bool
    {
        return !$this->select($cssSelector)->isEmpty();
    }

    /**
     * Return the first element with the given tag name and optional attributes.
     *
     * ```php
     * $node = $doc->find('a', ['href' => 'https://example.com']);
     * $node = $doc->find('meta', ['name' => 'description']);
     * ```
     *
     * @param string $tag The tag name to search for (e.g. 'a', 'div', '*').
     * @param array<string, string> $attributes  Key/value pairs that must all match.
     * @return CrawlerNode|null The first matching node, or null if no matches.
     */
    public function find(string $tag, array $attributes = []): ?CrawlerNode
    {
        return $this->findAll($tag, $attributes)->first();
    }

    /**
     * Return all elements with the given tag name and optional attributes.
     *
     * @param string $tag The tag name to search for (e.g. 'a', 'div', '*').
     * @param array<string, string> $attributes
     * @return CrawlerNodeList A list of matching nodes (empty if no matches).
     */
    public function findAll(string $tag, array $attributes = []): CrawlerNodeList
    {
        $xpathExpr = self::buildTagXPath($tag, $attributes);
        $result = $this->xpath->query($xpathExpr);

        if ($result === false) {
            return CrawlerNodeList::empty();
        }

        return CrawlerNodeList::fromDomNodeList($result, $this->xpath, $this->baseUrl);
    }

    /**
     * Return all elements whose text content contains the given string.
     *
     * @param string $text The text to search for within element content.
     * @param string $tag Optional tag name to filter by (default: '*' for any tag).
     * @return CrawlerNodeList A list of matching nodes (empty if no matches).
     */
    public function findByText(string $text, string $tag = '*'): CrawlerNodeList
    {
        $tagPart = ($tag === '*') ? '//*' : '//' . $tag;
        $result = $this->xpath->query($tagPart . '[contains(., "' . addslashes($text) . '")]');

        if ($result === false) {
            return CrawlerNodeList::empty();
        }

        return CrawlerNodeList::fromDomNodeList($result, $this->xpath, $this->baseUrl);
    }

    /**
     * Return the content of a `<meta>` tag by its `name` or `property` attribute.
     *
     * @param string $name The value of the `name` or `property` attribute to search for.
     * @return string|null The content of the matching meta tag, or null if not found.
     */
    public function meta(string $name): ?string
    {
        $node = $this->find('meta', ['name' => $name])
            ?? $this->find('meta', ['property' => $name]);

        return $node?->attr('content');
    }

    /**
     * Convenience methods for common tags.
     */
    public function links(): CrawlerNodeList
    {
        return $this->findAll('a');
    }

    /**
     * Convenience method to get all image elements.
     */
    public function images(): CrawlerNodeList
    {
        return $this->findAll('img');
    }

    /**
     * Convenience method to get all form elements.
     */
    public function forms(): CrawlerNodeList
    {
        return $this->findAll('form');
    }

    /**
     * Return all text nodes in the document (stripped of extra whitespace).
     *
     * @return string[]
     */
    public function texts(): array
    {
        return $this->select('*')->texts();
    }

    /**
     * Return the raw outer HTML of the entire document body.
     */
    public function html(): string
    {
        $body = $this->dom->getElementsByTagName('body')->item(0);
        return $body !== null ? $this->dom->saveHTML($body) : $this->dom->saveHTML();
    }

    /**
     * Return the document `<title>` text, or an empty string if absent.
     */
    public function title(): string
    {
        $node = $this->selectFirst('title');
        return $node?->text() ?? '';
    }

    /**
     * Attempt to determine the document's character encoding from `<meta>` tags.
     *
     * Checks for `<meta charset="...">` and `<meta http-equiv="Content-Type" content="...; charset=...">`.
     *
     * @return string|null The detected charset (e.g. 'UTF-8'), or null if not found.
     */
    public function charset(): ?string
    {
        $node = $this->find('meta', ['charset' => '']);
        if ($node !== null) {
            return $node->attr('charset');
        }

        $node = $this->find('meta', ['http-equiv' => 'Content-Type']);
        if ($node !== null) {
            $content = $node->attr('content') ?? '';
            if (preg_match('/charset=([^\s;]+)/i', $content, $m)) {
                return $m[1];
            }
        }

        return null;
    }

    /**
     * Get the base URL of the document (used for resolving relative links).
     *
     * @return string The base URL, or an empty string if not set.
     */
    public function baseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * Build an XPath expression for a tag + attribute map.
     *
     * @param string $tag The tag name to search for (e.g. 'a', 'div', '*').
     * @param array<string, string> $attributes Key/value pairs that must all match (e.g. ['href' => 'https://example.com']).
     * @return string The XPath expression to select matching elements.
     */
    private static function buildTagXPath(string $tag, array $attributes): string
    {
        $tagPart = ($tag === '*') ? '//*' : '//' . $tag;
        $filters = '';

        foreach ($attributes as $attr => $value) {
            if ($attr === 'class') {
                $filters .= sprintf('[contains(concat(" ", normalize-space(@class), " "), " %s ")]', $value);
            } else {
                $filters .= sprintf('[@%s="%s"]', $attr, $value);
            }
        }

        return $tagPart . $filters;
    }
}
