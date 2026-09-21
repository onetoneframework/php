<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\XML;

use Clover\Classes\Data\StringObject;

use DOMComment;
use DOMDocument;
use DOMDocumentFragment;
use DOMElement;
use DOMNode;
use DOMNodeList;
use DOMText;
use DOMXPath;
use Exception;
use InvalidArgumentException;
use LibXMLError;
use RuntimeException;

use function is_array;
use function is_string;
use function array_map;
use function join;
use function sprintf;
use function trim;
use function strtolower;
use function preg_match;
use function preg_replace;
use function mb_convert_encoding;
use function libxml_use_internal_errors;
use function libxml_get_errors;
use function libxml_clear_errors;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function json_encode;
use function in_array;
use function count;
use function array_unique;
use function array_values;

/**
 * A comprehensive wrapper around PHP's DOMDocument and DOMXPath.
 *
 * Provides a fluent API for loading, parsing, querying, manipulating, and
 * serializing both XML and HTML documents. Includes XPath-based element
 * retrieval, form serialization, CSS selector support, node manipulation,
 * schema validation, and various conversion utilities.
 *
 * @package Clover\Classes\XML
 */
class DOM
{
    /**
     * @var DOMDocument The underlying DOM document instance.
     */
    private DOMDocument $dom;

    /**
     * @var DOMXPath|null The XPath query engine bound to the current document.
     */
    private ?DOMXPath $xpath = null;

    /**
     * @var array<string, string> Registered namespace prefix-to-URI mappings for XPath queries.
     */
    private array $namespaces = [];

    /**
     * @var LibXMLError[] Parse errors collected from the last load/parse operation.
     */
    private array $parseErrors = [];

    /**
     * Construct a new DOM wrapper.
     *
     * Creates a fresh DOMDocument with the given XML version and encoding.
     * Whitespace preservation is disabled by default to allow formatted output.
     *
     * @param string $version  The XML version declaration (default: '1.0').
     * @param string $encoding The character encoding (default: 'UTF-8').
     */
    public function __construct(string $version = '1.0', string $encoding = 'UTF-8')
    {
        $this->dom = new DOMDocument($version, $encoding);
        $this->dom->preserveWhiteSpace = false;
    }

    // =========================================================================
    // Loading & Parsing
    // =========================================================================

    /**
     * Parse an XML string into the DOM document.
     *
     * Replaces any previously loaded content. Errors are captured internally
     * and can be retrieved via getParseErrors(). On failure, a RuntimeException
     * is thrown with the first error message.
     *
     * @param string|StringObject $xmlString The XML content to parse.
     * @param int                 $options   Bitwise OR of libxml option constants.
     *
     * @return self Returns $this for method chaining.
     *
     * @throws InvalidArgumentException If the input string is empty.
     * @throws RuntimeException         If parsing fails.
     */
    public function parse(string|StringObject $xmlString, int $options = 0): self
    {
        if ($xmlString instanceof StringObject) {
            $xmlString = $xmlString->__toString();
        }

        if (empty(trim($xmlString))) {
            throw new InvalidArgumentException('XML string cannot be empty');
        }

        libxml_use_internal_errors(true);
        $result = $this->dom->loadXML($xmlString, $options);
        $this->parseErrors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors(false);

        if ($result === false) {
            $message = !empty($this->parseErrors) ? trim($this->parseErrors[0]->message) : 'Unknown XML parsing error';
            throw new RuntimeException(sprintf('Failed to parse XML: %s', $message));
        }

        $this->xpath = null;

        return $this;
    }

    /**
     * Load an XML file from disk into the DOM document.
     *
     * @param string $filePath The path to the XML file.
     * @param int    $options  Bitwise OR of libxml option constants.
     *
     * @return self Returns $this for method chaining.
     *
     * @throws InvalidArgumentException If the file does not exist.
     * @throws RuntimeException         If parsing fails.
     */
    public function loadXMLFile(string $filePath, int $options = 0): self
    {
        if (!file_exists($filePath)) {
            throw new InvalidArgumentException(sprintf('XML file not found: %s', $filePath));
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new RuntimeException(sprintf('Failed to read file: %s', $filePath));
        }

        return $this->parse($content, $options);
    }

    /**
     * Load an HTML string into the DOM document.
     *
     * Handles encoding issues by wrapping the content in a meta charset tag
     * when necessary. Parse errors are captured silently since HTML is often
     * not well-formed.
     *
     * @param string|StringObject $html    The HTML content to load.
     * @param int                 $options Bitwise OR of libxml option constants.
     *
     * @return self Returns $this for method chaining.
     *
     * @throws InvalidArgumentException If the input string is empty.
     */
    public function loadHTML(string|StringObject $html, int $options = 0): self
    {
        if ($html instanceof StringObject) {
            $html = $html->__toString();
        }

        if (empty(trim($html))) {
            throw new InvalidArgumentException('HTML string cannot be empty');
        }

        if (!preg_match('/<meta[^>]+charset/i', $html)) {
            $html = '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">' . $html;
        }

        libxml_use_internal_errors(true);
        $this->dom->loadHTML($html, $options);
        $this->parseErrors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors(false);

        $this->xpath = null;

        return $this;
    }

    /**
     * Load an HTML file from disk into the DOM document.
     *
     * @param string $filePath The path to the HTML file.
     * @param int    $options  Bitwise OR of libxml option constants.
     *
     * @return self Returns $this for method chaining.
     *
     * @throws InvalidArgumentException If the file does not exist.
     * @throws RuntimeException         If the file cannot be read.
     */
    public function loadHTMLFile(string $filePath, int $options = 0): self
    {
        if (!file_exists($filePath)) {
            throw new InvalidArgumentException(sprintf('HTML file not found: %s', $filePath));
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new RuntimeException(sprintf('Failed to read file: %s', $filePath));
        }

        return $this->loadHTML($content, $options);
    }

    // =========================================================================
    // State & Access
    // =========================================================================

    /**
     * Check whether a valid document has been loaded with at least one element.
     *
     * @return bool True if a document element exists.
     */
    public function isValid(): bool
    {
        return $this->dom->documentElement !== null;
    }

    /**
     * Retrieve the underlying DOMDocument instance.
     *
     * @return DOMDocument The raw DOM document.
     */
    public function getDocument(): DOMDocument
    {
        return $this->dom;
    }

    /**
     * Retrieve the document's root element as a DOMElement, or null if none exists.
     *
     * @return DOMElement|null The root element.
     */
    public function getDocumentElement(): ?DOMElement
    {
        return $this->dom->documentElement;
    }

    /**
     * Retrieve any parse errors collected during the last load/parse operation.
     *
     * @return LibXMLError[] Array of libxml error objects.
     */
    public function getParseErrors(): array
    {
        return $this->parseErrors;
    }

    /**
     * Check whether the last parse operation produced any errors.
     *
     * @return bool True if errors were recorded.
     */
    public function hasParseErrors(): bool
    {
        return !empty($this->parseErrors);
    }

    // =========================================================================
    // XPath Engine
    // =========================================================================

    /**
     * Initialize or reinitialize the internal DOMXPath engine from the current document.
     *
     * If namespaces have been registered, they are automatically applied to
     * the new XPath instance. This method is called automatically when needed
     * but can be invoked manually to force a reset.
     *
     * @param bool $registerNodeNS Whether to register node namespaces automatically.
     *
     * @return self Returns $this for method chaining.
     */
    public function setXPathFromDOM(bool $registerNodeNS = true): self
    {
        $this->xpath = new DOMXPath($this->dom, $registerNodeNS);

        foreach ($this->namespaces as $prefix => $uri) {
            $this->xpath->registerNamespace($prefix, $uri);
        }

        return $this;
    }

    /**
     * Lazily retrieve the DOMXPath engine, initializing it if necessary.
     *
     * @return DOMXPath The XPath engine.
     */
    private function getXPath(): DOMXPath
    {
        if ($this->xpath === null) {
            $this->setXPathFromDOM();
        }

        return $this->xpath;
    }

    /**
     * Register an XPath namespace prefix and URI.
     *
     * The registration is stored internally and applied both to the current
     * and any future XPath instances created for this document.
     *
     * @param string $prefix The namespace prefix.
     * @param string $uri    The namespace URI.
     *
     * @return self Returns $this for method chaining.
     */
    public function registerNamespace(string $prefix, string $uri): self
    {
        $this->namespaces[$prefix] = $uri;

        if ($this->xpath !== null) {
            $this->xpath->registerNamespace($prefix, $uri);
        }

        return $this;
    }

    // =========================================================================
    // XPath Querying
    // =========================================================================

    /**
     * Execute an XPath query and return the matching node list.
     *
     * Supports both single expression strings and arrays of expressions
     * combined with the XPath union operator (|).
     *
     * @param string|string[] $expression  One or more XPath expressions.
     * @param DOMNode|null    $contextNode Optional context node to scope the query.
     *
     * @return DOMNodeList The matching nodes.
     *
     * @throws RuntimeException If the XPath query fails.
     */
    public function getXPathNode(string|array $expression, ?DOMNode $contextNode = null): DOMNodeList
    {
        if (is_array($expression)) {
            $expression = join(' | ', $expression);
        }

        $result = $this->getXPath()->query($expression, $contextNode);

        if ($result === false) {
            throw new RuntimeException(sprintf('XPath query failed: %s', $expression));
        }

        return $result;
    }

    /**
     * Execute an XPath query and return a single matching node, or null.
     *
     * @param string       $expression  The XPath expression.
     * @param DOMNode|null $contextNode Optional context node.
     *
     * @return DOMNode|null The first matching node, or null.
     */
    public function getXPathNodeFirst(string $expression, ?DOMNode $contextNode = null): ?DOMNode
    {
        $nodes = $this->getXPathNode($expression, $contextNode);

        return $nodes->length > 0 ? $nodes->item(0) : null;
    }

    /**
     * Evaluate an XPath expression that returns a scalar (string, number, boolean).
     *
     * @param string       $expression  The XPath expression.
     * @param DOMNode|null $contextNode Optional context node.
     *
     * @return mixed The evaluated result.
     */
    public function evaluateXPath(string $expression, ?DOMNode $contextNode = null): mixed
    {
        return $this->getXPath()->evaluate($expression, $contextNode);
    }

    /**
     * Check whether any nodes match the given XPath expression.
     *
     * @param string       $expression  The XPath expression.
     * @param DOMNode|null $contextNode Optional context node.
     *
     * @return bool True if at least one node matches.
     */
    public function xpathExists(string $expression, ?DOMNode $contextNode = null): bool
    {
        return $this->getXPathNode($expression, $contextNode)->length > 0;
    }

    /**
     * Count the number of nodes matching an XPath expression.
     *
     * @param string       $expression  The XPath expression.
     * @param DOMNode|null $contextNode Optional context node.
     *
     * @return int The match count.
     */
    public function xpathCount(string $expression, ?DOMNode $contextNode = null): int
    {
        return $this->getXPathNode($expression, $contextNode)->length;
    }

    // =========================================================================
    // CSS Selector Support
    // =========================================================================

    /**
     * Query the document using a CSS selector, converted internally to XPath.
     *
     * Supports common CSS selectors: tag names, #id, .class, [attr],
     * [attr=value], descendant combinator, and direct child combinator (>).
     * For complex selectors beyond these, use getXPathNode() directly.
     *
     * @param string       $selector    A CSS selector string.
     * @param DOMNode|null $contextNode Optional context node.
     *
     * @return DOMNodeList The matching nodes.
     */
    public function querySelectorAll(string $selector, ?DOMNode $contextNode = null): DOMNodeList
    {
        $xpath = $this->cssToXPath($selector);

        return $this->getXPathNode($xpath, $contextNode);
    }

    /**
     * Query the document using a CSS selector and return the first match.
     *
     * @param string       $selector    A CSS selector string.
     * @param DOMNode|null $contextNode Optional context node.
     *
     * @return DOMNode|null The first matching node, or null.
     */
    public function querySelector(string $selector, ?DOMNode $contextNode = null): ?DOMNode
    {
        $nodes = $this->querySelectorAll($selector, $contextNode);

        return $nodes->length > 0 ? $nodes->item(0) : null;
    }

    /**
     * Convert a basic CSS selector to an equivalent XPath expression.
     *
     * Handles: tag, #id, .class, [attr], [attr=value], [attr~=value],
     * [attr^=value], [attr$=value], [attr*=value], descendant (space),
     * and child (>) combinators.
     *
     * @param string $selector The CSS selector.
     *
     * @return string The equivalent XPath expression.
     */
    private function cssToXPath(string $selector): string
    {
        $selector = trim($selector);

        $selector = preg_replace('/\s*>\s*/', '/', $selector);
        $selector = preg_replace('/\s+/', '//', $selector);

        $selector = preg_replace('/#([a-zA-Z0-9_-]+)/', '[@id="\1"]', $selector);
        $selector = preg_replace('/\.([a-zA-Z0-9_-]+)/', '[contains(concat(" ",normalize-space(@class)," ")," \1 ")]', $selector);

        $selector = preg_replace('/\[([a-zA-Z0-9_-]+)\~="([^"]+)"\]/', '[contains(concat(" ",@\1," ")," \2 ")]', $selector);
        $selector = preg_replace('/\[([a-zA-Z0-9_-]+)\^="([^"]+)"\]/', '[starts-with(@\1,"\2")]', $selector);
        $selector = preg_replace('/\[([a-zA-Z0-9_-]+)\$="([^"]+)"\]/', '[substring(@\1,string-length(@\1)-string-length("\2")+1)="\2"]', $selector);
        $selector = preg_replace('/\[([a-zA-Z0-9_-]+)\*="([^"]+)"\]/', '[contains(@\1,"\2")]', $selector);
        $selector = preg_replace('/\[([a-zA-Z0-9_-]+)="([^"]+)"\]/', '[@\1="\2"]', $selector);
        $selector = preg_replace('/\[([a-zA-Z0-9_-]+)\]/', '[@\1]', $selector);

        if (!str_starts_with($selector, '/') && !str_starts_with($selector, '.')) {
            $selector = './/' . $selector;
        }

        return $selector;
    }

    // =========================================================================
    // Element Shortcut Queries
    // =========================================================================

    /**
     * Find a form element by its id attribute.
     *
     * @param string $id The form's id attribute value.
     *
     * @return DOMElement|null The matching form element, or null.
     */
    public function getXPathFormById(string $id): ?DOMElement
    {
        $node = $this->getXPathNodeFirst(sprintf('//form[@id="%s"]', $id));

        return $node instanceof DOMElement ? $node : null;
    }

    /**
     * Find elements of a given tag name with a specific id attribute.
     *
     * @param string       $element     The tag name.
     * @param string       $id          The id attribute value.
     * @param DOMNode|null $contextNode Optional context node.
     *
     * @return DOMNodeList The matching nodes.
     */
    public function getElementById(string $element, string $id, ?DOMNode $contextNode = null): DOMNodeList
    {
        return $this->getXPathNode(sprintf("//%s[@id='%s']", $element, $id), $contextNode);
    }

    /**
     * Find elements by their class attribute (partial match).
     *
     * @param string       $className   The CSS class name to search for.
     * @param string       $tag         Optional tag name filter (default: '*' for any).
     * @param DOMNode|null $contextNode Optional context node.
     *
     * @return DOMNodeList The matching nodes.
     */
    public function getElementsByClassName(string $className, string $tag = '*', ?DOMNode $contextNode = null): DOMNodeList
    {
        $expression = sprintf(
            './/%s[contains(concat(" ",normalize-space(@class)," ")," %s ")]',
            $tag,
            $className
        );

        return $this->getXPathNode($expression, $contextNode);
    }

    /**
     * Find elements by tag name within an optional context.
     *
     * @param string       $tagName     The tag name.
     * @param DOMNode|null $contextNode Optional context node.
     *
     * @return DOMNodeList The matching nodes.
     */
    public function getElementsByTagName(string $tagName, ?DOMNode $contextNode = null): DOMNodeList
    {
        return $this->getXPathNode(sprintf('.//%s', $tagName), $contextNode);
    }

    /**
     * Find elements that have a specific attribute, regardless of value.
     *
     * @param string       $attribute   The attribute name.
     * @param string       $tag         Optional tag name filter (default: '*').
     * @param DOMNode|null $contextNode Optional context node.
     *
     * @return DOMNodeList The matching nodes.
     */
    public function getElementsByAttribute(string $attribute, string $tag = '*', ?DOMNode $contextNode = null): DOMNodeList
    {
        return $this->getXPathNode(sprintf('.//%s[@%s]', $tag, $attribute), $contextNode);
    }

    /**
     * Find elements with a specific attribute value.
     *
     * @param string       $attribute   The attribute name.
     * @param string       $value       The expected attribute value.
     * @param string       $tag         Optional tag name filter (default: '*').
     * @param DOMNode|null $contextNode Optional context node.
     *
     * @return DOMNodeList The matching nodes.
     */
    public function getElementsByAttributeValue(string $attribute, string $value, string $tag = '*', ?DOMNode $contextNode = null): DOMNodeList
    {
        return $this->getXPathNode(sprintf('.//%s[@%s="%s"]', $tag, $attribute, $value), $contextNode);
    }

    /**
     * Find elements whose text content contains the given substring.
     *
     * @param string       $text        The text substring to search for.
     * @param string       $tag         Optional tag name filter (default: '*').
     * @param DOMNode|null $contextNode Optional context node.
     *
     * @return DOMNodeList The matching nodes.
     */
    public function getElementsByTextContent(string $text, string $tag = '*', ?DOMNode $contextNode = null): DOMNodeList
    {
        return $this->getXPathNode(sprintf('.//%s[contains(text(),"%s")]', $tag, $text), $contextNode);
    }

    /**
     * Find one or more element types within an optional context.
     *
     * Accepts either a single tag name or an array of tag names, which are
     * combined into a union XPath expression.
     *
     * @param string|string[] $element     One or more tag names.
     * @param DOMNode|null    $contextNode Optional context node.
     *
     * @return DOMNodeList The matching nodes.
     */
    public function getXPathElements(string|array $element, ?DOMNode $contextNode = null): DOMNodeList
    {
        if (is_array($element)) {
            $element = array_map(fn(string $el) => './/' . $el, $element);
            $element = join(' | ', $element);
        } else {
            $element = './/' . $element;
        }

        return $this->getXPathNode($element, $contextNode);
    }

    public function getXPathByNames(string $name, ?DOMNode $contextNode = null): DOMNodeList
    {
        return $this->getXPathElements("*[@name='$name']", $contextNode);
    }

    /**
     * Find all input elements within an optional context.
     *
     * @param DOMNode|null $contextNode Optional context node.
     *
     * @return DOMNodeList The matching input elements.
     */
    public function getXPathInputs(?DOMNode $contextNode = null): DOMNodeList
    {
        return $this->getXPathNode('.//input', $contextNode);
    }

    /**
     * Find all table elements within an optional context.
     *
     * @param DOMNode|null $contextNode Optional context node.
     *
     * @return DOMNodeList The matching table elements.
     */
    public function getXPathTables(?DOMNode $contextNode = null): DOMNodeList
    {
        return $this->getXPathNode('.//table', $contextNode);
    }

    /**
     * Find all table data cells (td inside tr inside table) within an optional context.
     *
     * @param DOMNode|null $contextNode Optional context node.
     *
     * @return DOMNodeList The matching td elements.
     */
    public function getXPathTableDataCells(?DOMNode $contextNode = null): DOMNodeList
    {
        return $this->getXPathNode('.//table/tr/td', $contextNode);
    }

    /**
     * Find all data cells (td) inside table rows within an optional context.
     *
     * @param DOMNode|null $contextNode Optional context node.
     *
     * @return DOMNodeList The matching td elements.
     */
    public function getXPathDataCellRows(?DOMNode $contextNode = null): DOMNodeList
    {
        return $this->getXPathNode('.//tr/td', $contextNode);
    }

    /**
     * Find all table row (tr) elements within an optional context.
     *
     * @param DOMNode|null $contextNode Optional context node.
     *
     * @return DOMNodeList The matching tr elements.
     */
    public function getXPathRows(?DOMNode $contextNode = null): DOMNodeList
    {
        return $this->getXPathNode('.//tr', $contextNode);
    }

    /**
     * Find all data cell (td) elements within an optional context.
     *
     * @param DOMNode|null $contextNode Optional context node.
     *
     * @return DOMNodeList The matching td elements.
     */
    public function getXPathDataCells(?DOMNode $contextNode = null): DOMNodeList
    {
        return $this->getXPathNode('.//td', $contextNode);
    }

    /**
     * Find all table rows directly inside tables within an optional context.
     *
     * @param DOMNode|null $contextNode Optional context node.
     *
     * @return DOMNodeList The matching tr elements.
     */
    public function getXPathTableRows(?DOMNode $contextNode = null): DOMNodeList
    {
        return $this->getXPathNode('.//table/tr', $contextNode);
    }

    /**
     * Find all select elements within an optional context.
     *
     * @param DOMNode|null $contextNode Optional context node.
     *
     * @return DOMNodeList The matching select elements.
     */
    public function getXPathSelects(?DOMNode $contextNode = null): DOMNodeList
    {
        return $this->getXPathNode('.//select', $contextNode);
    }

    /**
     * Find all option elements within an optional context.
     *
     * @param DOMNode|null $contextNode Optional context node.
     *
     * @return DOMNodeList The matching option elements.
     */
    public function getXPathOptions(?DOMNode $contextNode = null): DOMNodeList
    {
        return $this->getXPathNode('.//option', $contextNode);
    }

    /**
     * Find all textarea elements within an optional context.
     *
     * @param DOMNode|null $contextNode Optional context node.
     *
     * @return DOMNodeList The matching textarea elements.
     */
    public function getXPathTextareas(?DOMNode $contextNode = null): DOMNodeList
    {
        return $this->getXPathNode('.//textarea', $contextNode);
    }

    /**
     * Find all anchor (a) elements within an optional context.
     *
     * @param DOMNode|null $contextNode Optional context node.
     *
     * @return DOMNodeList The matching anchor elements.
     */
    public function getXPathAnchors(?DOMNode $contextNode = null): DOMNodeList
    {
        return $this->getXPathNode('.//a', $contextNode);
    }

    /**
     * Find all image (img) elements within an optional context.
     *
     * @param DOMNode|null $contextNode Optional context node.
     *
     * @return DOMNodeList The matching img elements.
     */
    public function getXPathImages(?DOMNode $contextNode = null): DOMNodeList
    {
        return $this->getXPathNode('.//img', $contextNode);
    }

    // =========================================================================
    // Node Manipulation
    // =========================================================================

    /**
     * Create a new element and append it to a parent node (or the document root).
     *
     * @param string       $tagName The element tag name.
     * @param string|null  $value   Optional text content.
     * @param DOMNode|null $parent  The parent to append to (defaults to document element).
     *
     * @return DOMElement The newly created element.
     *
     * @throws RuntimeException If no document element exists and no parent is given.
     */
    public function createElement(string $tagName, ?string $value = null, ?DOMNode $parent = null): DOMElement
    {
        $element = $value !== null
            ? $this->dom->createElement($tagName, $value)
            : $this->dom->createElement($tagName);

        $target = $parent ?? $this->dom->documentElement;

        if ($target === null) {
            throw new RuntimeException('No document element available; provide a parent node');
        }

        $target->appendChild($element);
        $this->xpath = null;

        return $element;
    }

    /**
     * Set one or more attributes on a DOMElement.
     *
     * @param DOMElement          $element    The target element.
     * @param array<string,string> $attributes Key-value attribute pairs.
     *
     * @return self Returns $this for method chaining.
     */
    public function setAttributes(DOMElement $element, array $attributes): self
    {
        foreach ($attributes as $name => $value) {
            $element->setAttribute($name, $value);
        }

        return $this;
    }

    /**
     * Remove a node from the document.
     *
     * @param DOMNode $node The node to remove.
     *
     * @return self Returns $this for method chaining.
     */
    public function removeNode(DOMNode $node): self
    {
        if ($node->parentNode !== null) {
            $node->parentNode->removeChild($node);
            $this->xpath = null;
        }

        return $this;
    }

    /**
     * Remove all nodes matching an XPath expression.
     *
     * @param string       $expression  The XPath expression.
     * @param DOMNode|null $contextNode Optional context node.
     *
     * @return int The number of nodes removed.
     */
    public function removeNodesByXPath(string $expression, ?DOMNode $contextNode = null): int
    {
        $nodes = $this->getXPathNode($expression, $contextNode);
        $removed = 0;
        $nodesToRemove = [];

        foreach ($nodes as $node) {
            $nodesToRemove[] = $node;
        }

        foreach ($nodesToRemove as $node) {
            if ($node->parentNode !== null) {
                $node->parentNode->removeChild($node);
                $removed++;
            }
        }

        if ($removed > 0) {
            $this->xpath = null;
        }

        return $removed;
    }

    /**
     * Replace a node with a new element in the document.
     *
     * @param DOMNode     $oldNode The node to replace.
     * @param string      $tagName The replacement element's tag name.
     * @param string|null $value   Optional text content for the replacement.
     *
     * @return DOMElement The new replacement element.
     *
     * @throws RuntimeException If the node has no parent.
     */
    public function replaceNode(DOMNode $oldNode, string $tagName, ?string $value = null): DOMElement
    {
        if ($oldNode->parentNode === null) {
            throw new RuntimeException('Cannot replace a node with no parent');
        }

        $newElement = $value !== null
            ? $this->dom->createElement($tagName, $value)
            : $this->dom->createElement($tagName);

        $oldNode->parentNode->replaceChild($newElement, $oldNode);
        $this->xpath = null;

        return $newElement;
    }

    /**
     * Insert a new node before an existing reference node.
     *
     * @param DOMNode $referenceNode The node before which to insert.
     * @param string  $tagName       The new element's tag name.
     * @param string|null $value     Optional text content.
     *
     * @return DOMElement The newly inserted element.
     *
     * @throws RuntimeException If the reference node has no parent.
     */
    public function insertBefore(DOMNode $referenceNode, string $tagName, ?string $value = null): DOMElement
    {
        if ($referenceNode->parentNode === null) {
            throw new RuntimeException('Cannot insert before a node with no parent');
        }

        $newElement = $value !== null
            ? $this->dom->createElement($tagName, $value)
            : $this->dom->createElement($tagName);

        $referenceNode->parentNode->insertBefore($newElement, $referenceNode);
        $this->xpath = null;

        return $newElement;
    }

    /**
     * Insert a new node after an existing reference node.
     *
     * @param DOMNode     $referenceNode The node after which to insert.
     * @param string      $tagName       The new element's tag name.
     * @param string|null $value         Optional text content.
     *
     * @return DOMElement The newly inserted element.
     *
     * @throws RuntimeException If the reference node has no parent.
     */
    public function insertAfter(DOMNode $referenceNode, string $tagName, ?string $value = null): DOMElement
    {
        if ($referenceNode->parentNode === null) {
            throw new RuntimeException('Cannot insert after a node with no parent');
        }

        $newElement = $value !== null
            ? $this->dom->createElement($tagName, $value)
            : $this->dom->createElement($tagName);

        if ($referenceNode->nextSibling !== null) {
            $referenceNode->parentNode->insertBefore($newElement, $referenceNode->nextSibling);
        } else {
            $referenceNode->parentNode->appendChild($newElement);
        }

        $this->xpath = null;

        return $newElement;
    }

    /**
     * Wrap an existing node inside a new container element.
     *
     * @param DOMNode $node        The node to wrap.
     * @param string  $wrapperName The wrapper element's tag name.
     *
     * @return DOMElement The wrapper element.
     *
     * @throws RuntimeException If the node has no parent.
     */
    public function wrapNode(DOMNode $node, string $wrapperName): DOMElement
    {
        if ($node->parentNode === null) {
            throw new RuntimeException('Cannot wrap a node with no parent');
        }

        $wrapper = $this->dom->createElement($wrapperName);
        $node->parentNode->replaceChild($wrapper, $node);
        $wrapper->appendChild($node);
        $this->xpath = null;

        return $wrapper;
    }

    /**
     * Import a node from another DOMDocument into this document and append it.
     *
     * @param DOMNode      $foreignNode The node from another document.
     * @param bool         $deep        If true, import the entire subtree.
     * @param DOMNode|null $parent      The parent to append to (defaults to document element).
     *
     * @return DOMNode The imported node.
     */
    public function importNode(DOMNode $foreignNode, bool $deep = true, ?DOMNode $parent = null): DOMNode
    {
        $imported = $this->dom->importNode($foreignNode, $deep);
        $target = $parent ?? $this->dom->documentElement;

        if ($target !== null) {
            $target->appendChild($imported);
            $this->xpath = null;
        }

        return $imported;
    }

    /**
     * Clone a node within the same document and optionally append it to a parent.
     *
     * @param DOMNode      $node   The node to clone.
     * @param bool         $deep   If true, clone the entire subtree.
     * @param DOMNode|null $parent If provided, append the clone to this parent.
     *
     * @return DOMNode The cloned node.
     */
    public function cloneNode(DOMNode $node, bool $deep = true, ?DOMNode $parent = null): DOMNode
    {
        $clone = $node->cloneNode($deep);

        if ($parent !== null) {
            $parent->appendChild($clone);
            $this->xpath = null;
        }

        return $clone;
    }

    /**
     * Remove all child nodes from a given parent node.
     *
     * @param DOMNode $node The parent whose children should be removed.
     *
     * @return self Returns $this for method chaining.
     */
    public function removeAllChildren(DOMNode $node): self
    {
        while ($node->firstChild) {
            $node->removeChild($node->firstChild);
        }

        $this->xpath = null;

        return $this;
    }

    /**
     * Remove specified tag names from a node list, returning the cleaned nodes.
     *
     * Creates a temporary document fragment, clones the input nodes into it,
     * removes all instances of the specified tags, and returns the cleaned result.
     *
     * @param DOMNodeList $elements The source nodes to clean.
     * @param string[]    $tags     Tag names to remove.
     *
     * @return DOMNodeList The cleaned node list.
     */
    public function removeTags(DOMNodeList $elements, array $tags = []): DOMNodeList
    {
        $doc = new DOMDocument();
        $fragment = $doc->createDocumentFragment();

        foreach ($elements as $el) {
            $clonedNode = $doc->importNode($el, true);
            $fragment->appendChild($clonedNode);
        }

        $doc->appendChild($fragment);

        foreach ($tags as $tag) {
            $tagsToRemove = $doc->getElementsByTagName($tag);
            $nodesToRemove = [];

            for ($i = 0; $i < $tagsToRemove->length; $i++) {
                $nodesToRemove[] = $tagsToRemove->item($i);
            }

            foreach ($nodesToRemove as $node) {
                if ($node->parentNode !== null) {
                    $node->parentNode->removeChild($node);
                }
            }
        }

        return $doc->childNodes;
    }

    /**
     * Strip all HTML tags from a node, preserving only text content.
     *
     * @param DOMNode $node The node to strip tags from.
     *
     * @return string The plain text content.
     */
    public function getPlainText(DOMNode $node): string
    {
        return $node->textContent;
    }

    // =========================================================================
    // Table Extraction
    // =========================================================================

    /**
     * Extract structured data from an HTML table element.
     *
     * Reads all rows (tr) and their cells (td/th), returning a two-dimensional
     * array. If $useFirstRowAsHeaders is true, the first row's values become
     * associative keys for all subsequent rows.
     *
     * @param DOMNode $tableNode           The table element to extract from.
     * @param bool    $useFirstRowAsHeaders If true, use the first row as column headers.
     *
     * @return array<int, array<string|int, string>> The extracted table data.
     */
    public function extractTableData(DOMNode $tableNode, bool $useFirstRowAsHeaders = false): array
    {
        $rows = $this->getXPathNode('.//tr', $tableNode);
        $data = [];
        $headers = [];

        foreach ($rows as $rowIndex => $row) {
            $cells = $this->getXPathNode('.//td | .//th', $row);
            $rowData = [];

            foreach ($cells as $cell) {
                $rowData[] = trim($cell->textContent);
            }

            if ($useFirstRowAsHeaders && $rowIndex === 0) {
                $headers = $rowData;
                continue;
            }

            if (!empty($headers)) {
                $assocRow = [];
                foreach ($rowData as $i => $value) {
                    $key = $headers[$i] ?? $i;
                    $assocRow[$key] = $value;
                }
                $data[] = $assocRow;
            } else {
                $data[] = $rowData;
            }
        }

        return $data;
    }

    /**
     * Extract all tables in the document as structured arrays.
     *
     * @param bool $useFirstRowAsHeaders If true, use each table's first row as column headers.
     *
     * @return array<int, array<int, array>> Array of table data arrays.
     */
    public function extractAllTables(bool $useFirstRowAsHeaders = false): array
    {
        $tables = $this->getXPathTables();
        $result = [];

        foreach ($tables as $table) {
            $result[] = $this->extractTableData($table, $useFirstRowAsHeaders);
        }

        return $result;
    }

    // =========================================================================
    // Link & Image Extraction
    // =========================================================================

    /**
     * Extract all hyperlinks (href values) from anchor elements in the document.
     *
     * @param bool         $unique      If true, return only unique URLs.
     * @param DOMNode|null $contextNode Optional context node.
     *
     * @return array<int, array{href: string, text: string}> Array of link data.
     */
    public function extractLinks(bool $unique = false, ?DOMNode $contextNode = null): array
    {
        $anchors = $this->getXPathAnchors($contextNode);
        $links = [];
        $seen = [];

        foreach ($anchors as $anchor) {
            if (!$anchor instanceof DOMElement) {
                continue;
            }

            $href = $anchor->getAttribute('href');
            if ($href === '') {
                continue;
            }

            if ($unique && isset($seen[$href])) {
                continue;
            }

            $seen[$href] = true;
            $links[] = [
                'href' => $href,
                'text' => trim($anchor->textContent),
            ];
        }

        return $links;
    }

    /**
     * Extract all image sources from img elements in the document.
     *
     * @param DOMNode|null $contextNode Optional context node.
     *
     * @return array<int, array{src: string, alt: string}> Array of image data.
     */
    public function extractImages(?DOMNode $contextNode = null): array
    {
        $images = $this->getXPathImages($contextNode);
        $result = [];

        foreach ($images as $img) {
            if (!$img instanceof DOMElement) {
                continue;
            }

            $result[] = [
                'src' => $img->getAttribute('src'),
                'alt' => $img->getAttribute('alt'),
            ];
        }

        return $result;
    }

    // =========================================================================
    // Meta & Head Extraction
    // =========================================================================

    /**
     * Extract all meta tag content from the document head.
     *
     * Returns an array of associative arrays with the meta tag's attributes.
     *
     * @return array<int, array<string, string>> Array of meta attribute maps.
     */
    public function extractMetaTags(): array
    {
        $metas = $this->getXPathNode('//meta');
        $result = [];

        foreach ($metas as $meta) {
            if (!$meta instanceof DOMElement) {
                continue;
            }

            $entry = [];
            foreach ($meta->attributes as $attr) {
                $entry[$attr->nodeName] = $attr->nodeValue;
            }
            $result[] = $entry;
        }

        return $result;
    }

    /**
     * Extract the document title from the <title> tag.
     *
     * @return string|null The title text, or null if no title element exists.
     */
    public function extractTitle(): ?string
    {
        $title = $this->getXPathNodeFirst('//title');

        return $title !== null ? trim($title->textContent) : null;
    }

    // =========================================================================
    // Form Serialization
    // =========================================================================

    /**
     * Serialize an HTML form's input values into a key-value array.
     *
     * Extracts values from input, textarea, and select elements within the
     * specified form. For checkboxes and radio buttons, only checked items
     * are included. For select elements, the selected option's value is used
     * (or the first option if none is explicitly selected).
     *
     * @param string|StringObject $html The HTML content containing the form.
     * @param string              $id   The id attribute of the target form.
     *
     * @return array<string, string> The serialized form data.
     *
     * @throws RuntimeException If the specified form cannot be found.
     */
    public function getSerializeForm(string|StringObject $html, string $id): array
    {
        $dom = new self();
        $dom->loadHTML($html);

        $form = $dom->getXPathFormById($id);
        if ($form === null) {
            throw new RuntimeException(sprintf('Form with id "%s" not found', $id));
        }

        $data = [];

        $inputs = $dom->getXPathInputs($form);
        foreach ($inputs as $input) {
            if (!$input instanceof DOMElement) {
                continue;
            }

            $name = $input->getAttribute('name');
            if ($name === '') {
                continue;
            }

            $type = strtolower($input->getAttribute('type'));

            if ($type === 'checkbox' || $type === 'radio') {
                if (!$input->hasAttribute('checked')) {
                    continue;
                }
                $data[$name] = $input->getAttribute('value') ?: 'on';
            } elseif ($type !== 'submit' && $type !== 'button' && $type !== 'image' && $type !== 'reset') {
                $data[$name] = $input->getAttribute('value');
            }
        }

        $textareas = $dom->getXPathTextareas($form);
        foreach ($textareas as $textarea) {
            if (!$textarea instanceof DOMElement) {
                continue;
            }

            $name = $textarea->getAttribute('name');
            if ($name !== '') {
                $data[$name] = $textarea->nodeValue ?? '';
            }
        }

        $selects = $dom->getXPathSelects($form);
        foreach ($selects as $select) {
            if (!$select instanceof DOMElement) {
                continue;
            }

            $name = $select->getAttribute('name');
            if ($name === '') {
                continue;
            }

            $options = $dom->getXPathOptions($select);
            $selectedValue = '';

            foreach ($options as $option) {
                if (!$option instanceof DOMElement) {
                    continue;
                }

                if ($option->hasAttribute('selected')) {
                    $selectedValue = $option->getAttribute('value') ?: $option->textContent;
                    break;
                }
            }

            if ($selectedValue === '' && $options->length > 0) {
                $firstOption = $options->item(0);
                if ($firstOption instanceof DOMElement) {
                    $selectedValue = $firstOption->getAttribute('value') ?: $firstOption->textContent;
                }
            }

            $data[$name] = $selectedValue;
        }

        return $data;
    }

    // =========================================================================
    // Validation
    // =========================================================================

    /**
     * Validate the document against an XSD schema.
     *
     * @param string $xsd    The XSD content or file path.
     * @param bool   $isFile If true, treat $xsd as a file path.
     *
     * @return LibXMLError[] Array of validation errors (empty if valid).
     *
     * @throws InvalidArgumentException If $isFile is true and the file does not exist.
     */
    public function validateSchema(string $xsd, bool $isFile = false, int $flags = 0): array
    {
        libxml_use_internal_errors(true);

        if ($isFile) {
            if (!file_exists($xsd)) {
                throw new InvalidArgumentException(sprintf('XSD file not found: %s', $xsd));
            }
            $this->dom->schemaValidate($xsd, $flags);
        } else {
            $this->dom->schemaValidateSource($xsd, $flags);
        }

        $errors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors(false);

        return $errors;
    }

    /**
     * Validate the document against a RelaxNG schema.
     *
     * @param string $relaxNG The RelaxNG content or file path.
     * @param bool   $isFile  If true, treat as a file path.
     *
     * @return LibXMLError[] Array of validation errors (empty if valid).
     */
    public function validateRelaxNG(string $relaxNG, bool $isFile = false): array
    {
        libxml_use_internal_errors(true);

        if ($isFile) {
            if (!file_exists($relaxNG)) {
                throw new InvalidArgumentException(sprintf('RelaxNG file not found: %s', $relaxNG));
            }
            $this->dom->relaxNGValidate($relaxNG);
        } else {
            $this->dom->relaxNGValidateSource($relaxNG);
        }

        $errors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors(false);

        return $errors;
    }

    // =========================================================================
    // Serialization & Output
    // =========================================================================

    /**
     * Serialize the document to an XML string.
     *
     * @param bool $formatted If true, produce indented output.
     *
     * @return string The XML string.
     */
    public function toXML(bool $formatted = false, bool $reserveWhiteSpace = false): string
    {
        $this->dom->formatOutput = $formatted;
        $this->setPreserveWhiteSpace($reserveWhiteSpace);

        return $this->dom->saveXML() ?: '';
    }

    /**
     * Serialize the document to an HTML string.
     *
     * @param DOMNode|null $node Optional node to serialize (defaults to entire document).
     *
     * @return string The HTML string.
     */
    public function toHTML(?DOMNode $node = null): string
    {
        return $this->dom->saveHTML($node) ?: '';
    }

    /**
     * Save the document to a file on disk.
     *
     * @param string $filePath  The output file path.
     * @param bool   $formatted If true, produce indented XML output.
     * @param bool   $asHTML    If true, save as HTML instead of XML.
     *
     * @return bool True if the file was written successfully.
     */
    public function saveToFile(string $filePath, bool $formatted = false, bool $asHTML = false): bool
    {
        $content = $asHTML ? $this->toHTML() : $this->toXML($formatted);

        return file_put_contents($filePath, $content) !== false;
    }

    /**
     * Serialize the document to a nested associative array.
     *
     * Recursively converts elements, attributes, and text content into
     * an array structure suitable for JSON encoding or further processing.
     *
     * @param DOMNode|null $node The starting node (defaults to document element).
     *
     * @return array<string, mixed> The array representation.
     */
    public function toArray(?DOMNode $node = null): array
    {
        $node = $node ?? $this->dom->documentElement;

        if ($node === null) {
            return [];
        }

        $result = [];

        if ($node instanceof DOMElement && $node->hasAttributes()) {
            foreach ($node->attributes as $attr) {
                $result['@attributes'][$attr->nodeName] = $attr->nodeValue;
            }
        }

        if (!$node->hasChildNodes()) {
            return !empty($result) ? $result + ['@value' => ''] : [];
        }

        $hasElementChildren = false;
        foreach ($node->childNodes as $child) {
            if ($child->nodeType === XML_ELEMENT_NODE) {
                $hasElementChildren = true;
                break;
            }
        }

        if (!$hasElementChildren) {
            $text = trim($node->textContent);
            if (!empty($result)) {
                $result['@value'] = $text;
                return $result;
            }
            return [$text];
        }

        foreach ($node->childNodes as $child) {
            if ($child->nodeType !== XML_ELEMENT_NODE) {
                continue;
            }

            $name = $child->nodeName;
            $childArray = $this->toArray($child);

            if ($child->childNodes->length === 1 && $child->firstChild->nodeType === XML_TEXT_NODE) {
                if (!$child->hasAttributes()) {
                    $childArray = trim($child->textContent);
                }
            }

            if (isset($result[$name])) {
                if (!isset($result[$name][0])) {
                    $result[$name] = [$result[$name]];
                }
                $result[$name][] = $childArray;
            } else {
                $result[$name] = $childArray;
            }
        }

        return $result;
    }

    /**
     * Serialize the document to a JSON string.
     *
     * @param int $flags JSON encoding flags (e.g., JSON_PRETTY_PRINT).
     *
     * @return string The JSON string.
     */
    public function toJSON(int $flags = 0): string
    {
        return json_encode($this->toArray(), $flags) ?: '{}';
    }

    /**
     * Get the inner HTML of a node (all child nodes serialized as HTML).
     *
     * @param DOMNode $node The parent node.
     *
     * @return string The inner HTML string.
     */
    public function getInnerHTML(DOMNode $node): string
    {
        $inner = '';

        foreach ($node->childNodes as $child) {
            $inner .= $this->dom->saveHTML($child);
        }

        return $inner;
    }

    /**
     * Get the outer HTML of a node (the node itself plus all content).
     *
     * @param DOMNode $node The node to serialize.
     *
     * @return string The outer HTML string.
     */
    public function getOuterHTML(DOMNode $node): string
    {
        return $this->dom->saveHTML($node) ?: '';
    }

    /**
     * Produce the canonical (C14N) form of the document or a specific node.
     *
     * @param DOMNode|null $node         The node to canonicalize (defaults to document element).
     * @param bool         $exclusive    If true, use Exclusive C14N.
     * @param bool         $withComments If true, include comments.
     *
     * @return string The canonicalized XML string.
     */
    public function canonicalize(?DOMNode $node = null, bool $exclusive = false, bool $withComments = false): string
    {
        $target = $node ?? $this->dom->documentElement;

        if ($target === null) {
            return '';
        }

        $result = $target->C14N($exclusive, $withComments);

        return $result !== false ? $result : '';
    }

    // =========================================================================
    // Document Properties
    // =========================================================================

    /**
     * Enable or disable formatted (indented) output for subsequent serialization.
     *
     * @param bool $formatted Whether to enable formatted output.
     *
     * @return self Returns $this for method chaining.
     */
    public function setFormatOutput(bool $formatted): self
    {
        $this->dom->formatOutput = $formatted;

        return $this;
    }

    /**
     * Enable or disable whitespace preservation during parsing.
     *
     * @param bool $preserve Whether to preserve whitespace.
     *
     * @return self Returns $this for method chaining.
     */
    public function setPreserveWhiteSpace(bool $preserve): self
    {
        $this->dom->preserveWhiteSpace = $preserve;

        return $this;
    }

    /**
     * Set whether the document is a standalone document.
     *
     * @param bool $standalone The standalone declaration value.
     *
     * @return self Returns $this for method chaining.
     */
    public function setStandalone(bool $standalone): self
    {
        $this->dom->xmlStandalone = $standalone;

        return $this;
    }

    // =========================================================================
    // Iteration Helpers
    // =========================================================================

    /**
     * Convert a DOMNodeList to a plain PHP array of DOMNode objects.
     *
     * @param DOMNodeList $nodeList The node list to convert.
     *
     * @return DOMNode[] A standard PHP array.
     */
    public function nodeListToArray(DOMNodeList $nodeList): array
    {
        $array = [];

        foreach ($nodeList as $node) {
            $array[] = $node;
        }

        return $array;
    }

    /**
     * Apply a callback to each node in a DOMNodeList and collect the results.
     *
     * @param DOMNodeList $nodeList The source nodes.
     * @param callable(DOMNode): mixed $callback A transformation function.
     *
     * @return array The collected results.
     */
    public function mapNodeList(DOMNodeList $nodeList, callable $callback): array
    {
        if (!is_callable($callback)) {
            throw new Exception('Callback is not callable');
        }

        $result = [];

        foreach ($nodeList as $node) {
            $result[] = $callback($node);
        }

        return $result;
    }

    /**
     * Filter a DOMNodeList using a predicate callback.
     *
     * @param DOMNodeList $nodeList The source nodes.
     * @param callable(DOMNode): bool $callback A predicate function.
     *
     * @return DOMNode[] Nodes for which the callback returned true.
     */
    public function filterNodeList(DOMNodeList $nodeList, callable $callback): array
    {
        if (!is_callable($callback)) {
            throw new Exception('Callback is not callable');
        }

        $result = [];

        foreach ($nodeList as $node) {
            if ($callback($node)) {
                $result[] = $node;
            }
        }

        return $result;
    }

    /**
     * Iterate over a DOMNodeList, invoking a callback for each node.
     *
     * @param DOMNodeList $nodeList The source nodes.
     * @param callable(DOMNode, int): void $callback A function receiving each node and its index.
     *
     * @return self Returns $this for method chaining.
     */
    public function eachNode(DOMNodeList $nodeList, callable $callback): self
    {
        if (!is_callable($callback)) {
            throw new Exception('Callback is not callable');
        }

        $index = 0;

        foreach ($nodeList as $node) {
            $callback($node, $index++);
        }

        return $this;
    }

    /**
     * Walk the entire subtree of a node depth-first, invoking a callback on each descendant.
     *
     * @param DOMNode $node The root of the subtree to walk.
     * @param callable(DOMNode, int): void $callback A function receiving each node and its depth level.
     * @param int $depth The current depth (used internally for recursion).
     *
     * @return self Returns $this for method chaining.
     */
    public function walkTree(DOMNode $node, callable $callback, int $depth = 0): self
    {
        if (!is_callable($callback)) {
            throw new Exception('Callback is not callable');
        }

        $callback($node, $depth);

        foreach ($node->childNodes as $child) {
            $this->walkTree($child, $callback, $depth + 1);
        }

        return $this;
    }
}
