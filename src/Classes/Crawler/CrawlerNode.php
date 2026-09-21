<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Crawler;

use Clover\Classes\Cralwer\CrawlerNodeList;
use Clover\Classes\Cralwer\CssSelectorConverter;
use DOMElement;
use DOMXPath;
use function array_filter;
use function array_values;
use function preg_match;
use function rtrim;
use function sprintf;
use function strtolower;
use function trim;
use function in_array;
use function str_starts_with;
use function substr;

/**
 * Wraps a single {@see DOMElement} and exposes a traversal / extraction API.
 */
final class CrawlerNode
{
    public function __construct(
        private readonly DOMElement $element,
        private readonly DOMXPath $xpath,
        private readonly string $baseUrl,
    ) {
    }

    /**
     * Return the visible text content of this element (all descendant text nodes joined).
     * 
     * @param bool $trim Whether to trim leading/trailing whitespace (default: true).
     * @return string The text content of this element.
     */
    public function text(bool $trim = true): string
    {
        $text = $this->element->textContent;
        return $trim ? trim($text) : $text;
    }

    /**
     * Return the text directly inside this element, excluding any descendant elements.
     *
     * @param bool $trim Whether to trim leading/trailing whitespace (default: true).
     * @return string The own text content of this element.
     */
    public function ownText(bool $trim = true): string
    {
        $text = '';
        foreach ($this->element->childNodes as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                $text .= $child->nodeValue;
            }
        }
        return $trim ? trim($text) : $text;
    }

    /**
     * Return the outer HTML of this element.
     */
    public function outerHtml(): string
    {
        $doc = $this->element->ownerDocument;
        return $doc !== null ? ($doc->saveHTML($this->element) ?: '') : '';
    }

    /**
     * Return the inner HTML (children serialised to HTML).
     */
    public function innerHtml(): string
    {
        $html = '';
        foreach ($this->element->childNodes as $child) {
            $doc = $child->ownerDocument;
            if ($doc !== null) {
                $html .= $doc->saveHTML($child);
            }
        }
        return $html;
    }

    public function html(): string
    {
        return $this->innerHtml();
    }

    /**
     * Return the value of an attribute, or `null` if the attribute is absent.
     * 
     * @param string $name The name of the attribute to retrieve.
     * @return string|null The attribute value, or null if not present.
     */
    public function attr(string $name): ?string
    {
        if (!$this->element->hasAttribute($name)) {
            return null;
        }
        return $this->element->getAttribute($name);
    }

    /**
     * Return all attributes as an associative array.
     *
     * @return array<string, string>
     */
    public function attrs(): array
    {
        $map = [];
        if ($this->element->hasAttributes()) {
            foreach ($this->element->attributes as $attr) {
                $map[$attr->name] = $attr->value;
            }
        }
        return $map;
    }

    /**
     * Return whether this element has the given attribute (regardless of value).
     * 
     * @param string $name The name of the attribute to check for.
     * @return bool True if the attribute exists on this element, false otherwise.
     */
    public function hasAttr(string $name): bool
    {
        return $this->element->hasAttribute($name);
    }

    /**
     * Return the value of a `data-*` attribute, or all `data-*` attributes as an associative array.
     * 
     * @param string $key The key of the `data-*` attribute to retrieve (e.g. 'id' for `data-id`). If empty, all `data-*` attributes are returned.
     * @return string|array<string,string>|null
     */
    public function data(string $key = ''): string|array|null
    {
        if ($key !== '') {
            return $this->attr('data-' . $key);
        }

        $result = [];
        foreach ($this->attrs() as $name => $value) {
            if (str_starts_with($name, 'data-')) {
                $result[substr($name, 5)] = $value;
            }
        }
        return $result;
    }

    /**
     * Return whether this element has the given CSS class.
     * 
     * @param string $className The CSS class name to check for (without the dot).
     * @return bool True if this element has the class, false otherwise.
     */
    public function hasClass(string $className): bool
    {
        $classes = array_filter(
            explode(' ', $this->attr('class') ?? ''),
            static fn(string $c) => $c !== ''
        );
        return in_array($className, array_values($classes), true);
    }

    /**
     * Return the tag name (lower-cased).
     */
    public function tag(): string
    {
        return strtolower($this->element->tagName);
    }

    /**
     * Return the 0-based index of this element among its sibling elements.
     */
    public function index(): int
    {
        $i = 0;
        $sibling = $this->element->previousSibling;
        while ($sibling !== null) {
            if ($sibling instanceof DOMElement) {
                $i++;
            }
            $sibling = $sibling->previousSibling;
        }
        return $i;
    }

    /**
     * Return the parent element, or `null` for the root.
     */
    public function parent(): ?self
    {
        $parent = $this->element->parentNode;
        if (!($parent instanceof DOMElement)) {
            return null;
        }
        return new self($parent, $this->xpath, $this->baseUrl);
    }

    /**
     * Return all direct child *elements* (text/comment nodes are skipped).
     */
    public function children(): CrawlerNodeList
    {
        $nodes = [];
        foreach ($this->element->childNodes as $child) {
            if ($child instanceof DOMElement) {
                $nodes[] = new self($child, $this->xpath, $this->baseUrl);
            }
        }
        return CrawlerNodeList::fromCrawlerNodes($nodes);
    }

    /**
     * Return all sibling elements (excluding this element).
     * 
     * @param string $cssSelector Optional CSS selector to filter siblings by.
     * @return CrawlerNodeList A list of sibling nodes matching the criteria.
     */
    public function siblings(string $cssSelector = ''): CrawlerNodeList
    {
        $parent = $this->element->parentNode;
        if (!($parent instanceof DOMElement)) {
            return CrawlerNodeList::empty();
        }

        $nodes = [];
        foreach ($parent->childNodes as $child) {
            if ($child instanceof DOMElement && !$child->isSameNode($this->element)) {
                $nodes[] = new self($child, $this->xpath, $this->baseUrl);
            }
        }

        $list = CrawlerNodeList::fromCrawlerNodes($nodes);

        if ($cssSelector === '') {
            return $list;
        }

        return $list->filter(fn(self $n) => $n->is($cssSelector));
    }

    /**
     * Return all next sibling elements, optionally filtered by a CSS selector.
     * 
     * @param string $cssSelector Optional CSS selector to filter siblings by.
     * @return CrawlerNodeList A list of next sibling nodes matching the criteria.
     */
    public function nextSiblings(string $cssSelector = ''): CrawlerNodeList
    {
        $nodes = [];
        $sibling = $this->element->nextSibling;

        while ($sibling !== null) {
            if ($sibling instanceof DOMElement) {
                $nodes[] = new self($sibling, $this->xpath, $this->baseUrl);
            }
            $sibling = $sibling->nextSibling;
        }

        $list = CrawlerNodeList::fromCrawlerNodes($nodes);

        if ($cssSelector === '') {
            return $list;
        }

        return $list->filter(fn(self $n) => $n->is($cssSelector));
    }

    /**
     * Return all previous sibling elements, optionally filtered by a CSS selector.
     * 
     * @param string $cssSelector Optional CSS selector to filter siblings by.
     * @return CrawlerNodeList A list of previous sibling nodes matching the criteria.
     */
    public function previousSiblings(string $cssSelector = ''): CrawlerNodeList
    {
        $nodes = [];
        $sibling = $this->element->previousSibling;

        while ($sibling !== null) {
            if ($sibling instanceof DOMElement) {
                array_unshift($nodes, new self($sibling, $this->xpath, $this->baseUrl));
            }
            $sibling = $sibling->previousSibling;
        }

        $list = CrawlerNodeList::fromCrawlerNodes($nodes);

        if ($cssSelector === '') {
            return $list;
        }

        return $list->filter(fn(self $n) => $n->is($cssSelector));
    }

    /**
     * Return the next sibling element, or `null`.
     */
    public function nextSibling(): ?self
    {
        $sibling = $this->element->nextSibling;
        while ($sibling !== null && !($sibling instanceof DOMElement)) {
            $sibling = $sibling->nextSibling;
        }
        return ($sibling instanceof DOMElement) ? new self($sibling, $this->xpath, $this->baseUrl) : null;
    }

    /**
     * Return the previous sibling element, or `null`.
     */
    public function previousSibling(): ?self
    {
        $sibling = $this->element->previousSibling;
        while ($sibling !== null && !($sibling instanceof DOMElement)) {
            $sibling = $sibling->previousSibling;
        }
        return ($sibling instanceof DOMElement) ? new self($sibling, $this->xpath, $this->baseUrl) : null;
    }

    /**
     * Return all ancestor elements, optionally filtered by a CSS selector.
     * 
     * @param string $cssSelector Optional CSS selector to filter ancestors by.
     * @return CrawlerNodeList A list of ancestor nodes matching the criteria.
     */
    public function ancestors(string $cssSelector = ''): CrawlerNodeList
    {
        $nodes = [];
        $current = $this->element->parentNode;

        while ($current instanceof DOMElement) {
            $node = new self($current, $this->xpath, $this->baseUrl);
            if ($cssSelector === '' || $node->is($cssSelector)) {
                $nodes[] = $node;
            }
            $current = $current->parentNode;
        }

        return CrawlerNodeList::fromCrawlerNodes($nodes);
    }

    /**
     * Return the closest ancestor (including this element) that matches the CSS selector, or `null` if none match.
     * 
     * @param string $cssSelector The CSS selector to match against this element and its ancestors.
     * @return self|null The closest matching element, or null if no match is found.
     */
    public function closest(string $cssSelector): ?self
    {
        $node = $this;
        while ($node !== null) {
            if ($node->is($cssSelector)) {
                return $node;
            }
            $node = $node->parent();
        }
        return null;
    }

    public function is(string $cssSelector): bool
    {
        $xpathExpr = CssSelectorConverter::toXPath($cssSelector);
        $result = $this->xpath->query($xpathExpr);

        if ($result === false) {
            return false;
        }

        foreach ($result as $node) {
            if ($node->isSameNode($this->element)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Run a CSS selector *scoped to this node* and return all matches.
     * 
     * @param string $cssSelector The CSS selector to match descendant elements against.
     * @return CrawlerNodeList A list of matching nodes (empty if no matches).
     */
    public function select(string $cssSelector): CrawlerNodeList
    {
        $xpathExpr = CssSelectorConverter::toXPath($cssSelector, scopedToCurrentNode: true);
        $result = $this->xpath->query($xpathExpr, $this->element);

        if ($result === false) {
            return CrawlerNodeList::empty();
        }

        return CrawlerNodeList::fromDomNodeList($result, $this->xpath, $this->baseUrl);
    }

    /**
     * Return the first descendant matching a CSS selector, or `null`.
     * 
     * @param string $cssSelector The CSS selector to match descendant elements against.
     * @return CrawlerNode|null The first matching node, or null if no matches.
     */
    public function selectFirst(string $cssSelector): ?self
    {
        return $this->select($cssSelector)->first();
    }

    /**
     * Find the first descendant with the given tag + attributes.
     *
     * @param string $tag The tag name to search for (e.g. 'a', 'div', '*').
     * @param array<string, string> $attributes Key/value pairs that must all match (e.g. ['href' => 'https://example.com']).
     * @return CrawlerNode|null The first matching node, or null if no matches.
     */
    public function find(string $tag, array $attributes = []): ?self
    {
        return $this->findAll($tag, $attributes)->first();
    }

    /**
     * Find all descendants with the given tag + attributes.
     *
     * @param string $tag The tag name to search for (e.g. 'a', 'div', '*').
     * @param array<string, string> $attributes Key/value pairs that must all match (e.g. ['href' => 'https://example.com']).
     * @return CrawlerNodeList A list of matching nodes (empty if no matches).
     */
    public function findAll(string $tag, array $attributes = []): CrawlerNodeList
    {
        $tagPart = ($tag === '*') ? './/*' : './/' . $tag;
        $filters = '';

        foreach ($attributes as $attr => $value) {
            if ($attr === 'class') {
                $filters .= sprintf('[contains(concat(" ", normalize-space(@class), " "), " %s ")]', $value);
            } else {
                $filters .= sprintf('[@%s="%s"]', $attr, $value);
            }
        }

        $result = $this->xpath->query($tagPart . $filters, $this->element);

        if ($result === false) {
            return CrawlerNodeList::empty();
        }

        return CrawlerNodeList::fromDomNodeList($result, $this->xpath, $this->baseUrl);
    }

    /**
     * Resolve the `href` attribute against the document base URL.
     * Returns `null` if there is no `href`.
     */
    public function link(): ?string
    {
        $href = $this->attr('href');

        if ($href === null) {
            return null;
        }

        if ($this->baseUrl !== '' && !preg_match('#^https?://#i', $href)) {
            return rtrim($this->baseUrl, '/') . '/' . ltrim($href, '/');
        }

        return $href;
    }

    /**
     * Get the underlying `DOMElement` wrapped by this `CrawlerNode`.
     */
    public function domElement(): DOMElement
    {
        return $this->element;
    }
}
