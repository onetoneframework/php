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
use SimpleXMLElement;
use stdClass;
use RuntimeException;
use InvalidArgumentException;
use Iterator;
use Countable;
use DOMDocument;
use DOMXPath;
use XSLTProcessor;
use LibXMLError;
use Exception;

use function is_array;
use function count;
use function array_map;
use function array_unique;
use function iterator_to_array;
use function file_exists;
use function file_put_contents;
use function file_get_contents;
use function libxml_use_internal_errors;
use function libxml_get_errors;
use function libxml_clear_errors;
use function simplexml_load_string;
use function simplexml_load_file;
use function simplexml_import_dom;
use function sprintf;
use function dom_import_simplexml;
use function is_string;
use function is_int;
use function is_null;
use function trim;
use function preg_replace;
use function preg_match;
use function htmlspecialchars;
use function json_encode;
use function array_slice;
use function array_values;
use function usort;
use function array_merge;
use function array_column;
use function implode;
use function rtrim;

/**
 * A fluent, feature-rich wrapper around PHP's SimpleXMLElement.
 *
 * Provides a chainable API for parsing, querying, manipulating, transforming,
 * and serializing XML documents. Implements Iterator and Countable for seamless
 * traversal of child elements.
 *
 * Features include XPath querying with namespace support, schema validation
 * (XSD/DTD/RelaxNG), XSLT transformation, canonical serialization (C14N),
 * structural merging, deep recursive searching, child sorting, batch mutation,
 * and functional collection operations (map, filter, reduce, pluck, groupBy, etc.).
 *
 * @package Clover\Classes\XML
 */
class SimpleXML implements Iterator, Countable
{
	/**
	 * @var SimpleXMLElement|bool|null The underlying SimpleXMLElement instance.
	 */
	private SimpleXMLElement|bool|null $data;

	/**
	 * @var array<string, string> Registered XPath namespace prefix-to-URI mappings.
	 */
	private array $namespaces = [];

	/**
	 * @var int Current position of the internal iterator.
	 */
	private int $iteratorPosition = 0;

	/**
	 * @var SimpleXMLElement[] Cached array of child elements for iteration.
	 */
	private array $iteratorChildren = [];

	/**
	 * Construct a new SimpleXML wrapper.
	 *
	 * Accepts a raw SimpleXMLElement, an XML string to parse, or null/false
	 * for an empty (invalid) state. If a string is provided, it is immediately
	 * parsed into a SimpleXMLElement.
	 *
	 * @param SimpleXMLElement|string|bool|null $data An XML string, an existing SimpleXMLElement, or null/false.
	 */
	public function __construct(SimpleXMLElement|string|bool|null $data = '<root/>')
	{
		if (is_string($data)) {
			$this->data = new SimpleXMLElement($data);
		} else {
			$this->data = $data;
		}
	}

	// =========================================================================
	// Static Factory Methods
	// =========================================================================

	/**
	 * Test whether a given string is well-formed XML.
	 *
	 * Temporarily enables internal libxml error handling, attempts to parse
	 * the string, and returns true only if parsing succeeds with no errors.
	 *
	 * @param string|StringObject $xml The XML string to validate.
	 *
	 * @return bool True if the string is valid XML, false otherwise.
	 */
	public static function isXML(string|StringObject $xml): bool
	{
		if ($xml instanceof StringObject) {
			$xml = $xml->__toString();
		}

		libxml_use_internal_errors(true);
		$doc = simplexml_load_string($xml);
		$errors = libxml_get_errors();
		libxml_clear_errors();
		libxml_use_internal_errors(false);

		return $doc !== false && empty($errors);
	}

	/**
	 * Create an instance by parsing an XML string.
	 *
	 * @param string|StringObject $text The XML string to parse.
	 *
	 * @return self A new instance wrapping the parsed document.
	 *
	 * @throws InvalidArgumentException If the string is empty.
	 * @throws RuntimeException         If the XML cannot be parsed.
	 */
	public static function fromString(string|StringObject $text): self
	{
		$instance = new self();
		$instance->parse($text);

		return $instance;
	}

	/**
	 * Create an instance by loading an XML file from disk.
	 *
	 * @param string $filePath Absolute or relative path to the XML file.
	 *
	 * @return self A new instance wrapping the loaded document.
	 *
	 * @throws InvalidArgumentException If the file does not exist.
	 * @throws RuntimeException         If the file cannot be parsed.
	 */
	public static function loadFile(string $filePath): self
	{
		$instance = new self();
		$instance->fromFile($filePath);

		return $instance;
	}

	/**
	 * Create a new XML document with a specified root element.
	 *
	 * Builds the XML declaration and root element, optionally including
	 * a default namespace, then parses the result into a new instance.
	 *
	 * @param string      $rootElement The name of the root element.
	 * @param string|null $namespace   Optional default namespace URI for the root element.
	 * @param string      $version     XML version (default: '1.0').
	 * @param string      $encoding    Character encoding (default: 'UTF-8').
	 *
	 * @return self A new instance with the constructed root document.
	 */
	public static function create(string $rootElement, ?string $namespace = null, string $version = '1.0', string $encoding = 'UTF-8'): self
	{
		$xmlString = sprintf('<?xml version="%s" encoding="%s"?>', $version, $encoding);

		if ($namespace !== null) {
			$xmlString .= sprintf('<%s xmlns="%s"></%s>', $rootElement, $namespace, $rootElement);
		} else {
			$xmlString .= sprintf('<%s></%s>', $rootElement, $rootElement);
		}

		return self::fromString($xmlString);
	}

	// =========================================================================
	// Parsing & Loading
	// =========================================================================

	/**
	 * Parse an XML string into the internal SimpleXMLElement.
	 *
	 * Replaces any previously held data. Uses internal libxml error handling
	 * to capture and report parse errors as exceptions.
	 *
	 * @param string|StringObject $text The XML string to parse.
	 *
	 * @return self Returns $this for method chaining.
	 *
	 * @throws InvalidArgumentException If the input string is empty or whitespace-only.
	 * @throws RuntimeException         If the XML cannot be parsed.
	 */
	public function parse(string|StringObject $text): self
	{
		if ($text instanceof StringObject) {
			$text = $text->__toString();
		}

		if (empty(trim($text))) {
			throw new InvalidArgumentException('XML string cannot be empty');
		}

		libxml_use_internal_errors(true);
		$this->data = simplexml_load_string($text);
		$errors = libxml_get_errors();
		libxml_clear_errors();
		libxml_use_internal_errors(false);

		if ($this->data === false) {
			$errorMessage = !empty($errors) ? $errors[0]->message : 'Unknown parsing error';
			throw new RuntimeException(sprintf('Failed to parse XML: %s', trim($errorMessage)));
		}

		return $this;
	}

	/**
	 * Load and parse an XML file from disk into the internal SimpleXMLElement.
	 *
	 * @param string $filePath Path to the XML file.
	 *
	 * @return self Returns $this for method chaining.
	 *
	 * @throws InvalidArgumentException If the file does not exist.
	 * @throws RuntimeException         If the file cannot be parsed.
	 */
	public function fromFile(string $filePath): self
	{
		if (!file_exists($filePath)) {
			throw new InvalidArgumentException(sprintf('File not found: %s', $filePath));
		}

		libxml_use_internal_errors(true);
		$this->data = simplexml_load_file($filePath);
		$errors = libxml_get_errors();
		libxml_clear_errors();
		libxml_use_internal_errors(false);

		if ($this->data === false) {
			$errorMessage = !empty($errors) ? $errors[0]->message : 'Unknown parsing error';
			throw new RuntimeException(sprintf('Failed to load XML file: %s', trim($errorMessage)));
		}

		return $this;
	}

	// =========================================================================
	// State Inspection
	// =========================================================================

	/**
	 * Check whether the internal data holds a valid SimpleXMLElement.
	 *
	 * @return bool True if valid XML data is present.
	 */
	public function isValid(): bool
	{
		return $this->data instanceof SimpleXMLElement;
	}

	/**
	 * Alias for isValid(). Check whether parsed XML data exists.
	 *
	 * @return bool True if the internal SimpleXMLElement is set.
	 */
	public function hasData(): bool
	{
		return $this->data instanceof SimpleXMLElement;
	}

	/**
	 * Retrieve the raw internal SimpleXMLElement (or null/false if not set).
	 *
	 * @return SimpleXMLElement|bool|null The underlying XML element.
	 */
	public function getData(): SimpleXMLElement|bool|null
	{
		return $this->data;
	}

	/**
	 * Retrieve the internal SimpleXMLElement, throwing if none is available.
	 *
	 * @return SimpleXMLElement The validated XML element.
	 *
	 * @throws RuntimeException If no valid XML data is loaded.
	 */
	public function getValidData(): SimpleXMLElement
	{
		if (!$this->data instanceof SimpleXMLElement) {
			throw new RuntimeException('No valid XML data available');
		}

		return $this->data;
	}

	// =========================================================================
	// Element Access
	// =========================================================================

	/**
	 * Access a direct child element by name, wrapped in a new SimpleXML instance.
	 *
	 * @param string $key The child element name.
	 *
	 * @return self A new instance wrapping the child (or null if not found).
	 */
	public function get(string $key): self
	{
		$data = $this->getValidData();

		return new self($data->$key ?? null);
	}

	/**
	 * Check whether a direct child element with the given name exists.
	 *
	 * @param string $key The child element name.
	 *
	 * @return bool True if the child element exists.
	 */
	public function has(string $key): bool
	{
		if (!$this->data instanceof SimpleXMLElement) {
			return false;
		}

		return isset($this->data->$key);
	}

	/**
	 * Retrieve the children of the current element, optionally filtered by namespace.
	 *
	 * @param string|null $namespaceOrPrefix Namespace URI or prefix to filter children.
	 * @param bool        $isPrefix          If true, treat the first argument as a prefix rather than URI.
	 *
	 * @return self A new instance wrapping the children collection.
	 */
	public function getChildren(?string $namespaceOrPrefix = null, bool $isPrefix = false): self
	{
		$data = $this->getValidData()->children($namespaceOrPrefix, $isPrefix);

		return new self($data);
	}

	/**
	 * Retrieve all direct children whose element name matches the given name.
	 *
	 * @param string $name The element name to match.
	 *
	 * @return self[] Array of matching children, each wrapped in a SimpleXML instance.
	 */
	public function getChildrenByName(string $name): array
	{
		if (!$this->data instanceof SimpleXMLElement) {
			return [];
		}

		$result = [];
		foreach ($this->data->children() as $child) {
			if ($child->getName() === $name) {
				$result[] = new self($child);
			}
		}

		return $result;
	}

	/**
	 * Check whether the current element has any child elements.
	 *
	 * @return bool True if at least one child element exists.
	 */
	public function hasChildren(): bool
	{
		if (!$this->data instanceof SimpleXMLElement) {
			return false;
		}

		return $this->data->count() > 0;
	}

	/**
	 * Count the number of direct child elements.
	 *
	 * @return int The child element count.
	 */
	public function count(): int
	{
		if (!$this->data instanceof SimpleXMLElement) {
			return 0;
		}

		return $this->data->count();
	}

	/**
	 * Get the tag name of the current element.
	 *
	 * @return string|null The element name, or null if no valid data is held.
	 */
	public function getName(): ?string
	{
		if (!$this->data instanceof SimpleXMLElement) {
			return null;
		}

		return $this->data->getName();
	}

	/**
	 * Check whether the current element is empty (no children and no text content).
	 *
	 * @return bool True if the element has no children and its text content is empty.
	 */
	public function isEmpty(): bool
	{
		if (!$this->data instanceof SimpleXMLElement) {
			return true;
		}

		return $this->data->count() === 0 && trim((string) $this->data) === '';
	}

	/**
	 * Count the total number of descendant elements recursively.
	 *
	 * Traverses the entire subtree below the current element and returns
	 * the total number of elements encountered at all levels of nesting.
	 *
	 * @return int The total descendant count.
	 */
	public function countDescendants(): int
	{
		if (!$this->data instanceof SimpleXMLElement) {
			return 0;
		}

		$count = 0;
		foreach ($this->data->children() as $child) {
			$count++;
			$count += (new self($child))->countDescendants();
		}

		return $count;
	}

	/**
	 * Get an array of all unique child element tag names.
	 *
	 * @return string[] List of distinct child element names.
	 */
	public function getChildNames(): array
	{
		if (!$this->data instanceof SimpleXMLElement) {
			return [];
		}

		$names = [];
		foreach ($this->data->children() as $child) {
			$names[] = $child->getName();
		}

		return array_values(array_unique($names));
	}

	/**
	 * Retrieve a slice of child elements by offset and length.
	 *
	 * Works like array_slice on the list of direct child elements.
	 *
	 * @param int      $offset Starting index (supports negative offsets).
	 * @param int|null $length Maximum number of children to return. Null returns all from offset.
	 *
	 * @return self[] Array of sliced children wrapped in SimpleXML instances.
	 */
	public function slice(int $offset, ?int $length = null): array
	{
		if (!$this->data instanceof SimpleXMLElement) {
			return [];
		}

		$children = iterator_to_array($this->data->children());
		$sliced = array_slice($children, $offset, $length);

		return array_map(fn($child) => new self($child), $sliced);
	}

	// =========================================================================
	// Attributes
	// =========================================================================

	/**
	 * Retrieve the value of a single attribute by name, optionally within a namespace.
	 *
	 * @param string      $name      The attribute name.
	 * @param string|null $namespace Optional namespace URI to scope the lookup.
	 *
	 * @return string|null The attribute value, or null if not present.
	 */
	public function getAttribute(string $name, ?string $namespace = null): ?string
	{
		if (!$this->data instanceof SimpleXMLElement) {
			return null;
		}

		$attributes = $this->data->attributes($namespace);
		if ($attributes === null || !isset($attributes[$name])) {
			return null;
		}

		return (string) $attributes[$name];
	}

	/**
	 * Retrieve all attributes as an associative array of name => value strings.
	 *
	 * @param string|null $namespace Optional namespace URI to scope the attribute lookup.
	 *
	 * @return array<string, string> The attribute map.
	 */
	public function getAttributes(?string $namespace = null): array
	{
		if (!$this->data instanceof SimpleXMLElement) {
			return [];
		}

		$attributes = $this->data->attributes($namespace);
		if ($attributes === null) {
			return [];
		}

		$result = [];
		foreach ($attributes as $key => $value) {
			$result[$key] = (string) $value;
		}

		return $result;
	}

	/**
	 * Check whether a specific attribute exists on the current element.
	 *
	 * @param string      $name      The attribute name.
	 * @param string|null $namespace Optional namespace URI.
	 *
	 * @return bool True if the attribute is present.
	 */
	public function hasAttribute(string $name, ?string $namespace = null): bool
	{
		return $this->getAttribute($name, $namespace) !== null;
	}

	/**
	 * Set or add an attribute on the current element.
	 *
	 * If the attribute already exists (and no namespace is given), its value
	 * is updated in place. Otherwise a new attribute is added via addAttribute().
	 *
	 * @param string      $name      The attribute name.
	 * @param string      $value     The attribute value.
	 * @param string|null $namespace Optional namespace URI for the new attribute.
	 *
	 * @return self Returns $this for method chaining.
	 *
	 * @throws RuntimeException If no valid XML data is loaded.
	 */
	public function setAttribute(string $name, string $value, ?string $namespace = null): self
	{
		$data = $this->getValidData();

		if ($namespace !== null) {
			$data->addAttribute($name, $value, $namespace);
		} else {
			$attributes = $data->attributes();
			if ($attributes !== null && isset($attributes[$name])) {
				$attributes[$name] = $value;
			} else {
				$data->addAttribute($name, $value);
			}
		}

		return $this;
	}

	/**
	 * Set multiple attributes at once from an associative array.
	 *
	 * @param array<string, string> $attributes Key-value pairs of attribute names and values.
	 * @param string|null           $namespace  Optional namespace URI applied to all attributes.
	 *
	 * @return self Returns $this for method chaining.
	 */
	public function setAttributes(array $attributes, ?string $namespace = null): self
	{
		foreach ($attributes as $name => $value) {
			$this->setAttribute($name, $value, $namespace);
		}

		return $this;
	}

	/**
	 * Remove an attribute from the current element by name.
	 *
	 * Uses the underlying SimpleXMLElement unset mechanism.
	 *
	 * @param string $name The attribute name to remove.
	 *
	 * @return self Returns $this for method chaining.
	 *
	 * @throws RuntimeException If no valid XML data is loaded.
	 */
	public function removeAttribute(string $name): self
	{
		$data = $this->getValidData();
		$attributes = $data->attributes();

		if ($attributes !== null && isset($attributes[$name])) {
			unset($attributes[$name]);
		}

		return $this;
	}

	// =========================================================================
	// Child Manipulation
	// =========================================================================

	/**
	 * Add a child element with optional text content and namespace.
	 *
	 * Text content is automatically escaped for XML safety using htmlspecialchars().
	 *
	 * @param string      $name      The child element name.
	 * @param string|null $value     Optional text content for the new child.
	 * @param string|null $namespace Optional namespace URI for the child element.
	 *
	 * @return self A new instance wrapping the newly created child element.
	 *
	 * @throws RuntimeException If no valid XML data is loaded.
	 */
	public function addChild(string $name, ?string $value = null, ?string $namespace = null): self
	{
		$data = $this->getValidData();
		$child = $data->addChild($name, $value !== null ? htmlspecialchars($value, ENT_XML1) : null, $namespace);

		return new self($child);
	}

	/**
	 * Add a child element containing a CDATA section.
	 *
	 * Uses the DOM layer to insert a CDATA node, since SimpleXML does not
	 * natively support CDATA creation.
	 *
	 * @param string      $name      The child element name.
	 * @param string      $value     The raw text to wrap in CDATA.
	 * @param string|null $namespace Optional namespace URI for the child element.
	 *
	 * @return self A new instance wrapping the newly created CDATA child.
	 *
	 * @throws RuntimeException If no valid XML data is loaded.
	 */
	public function addChildWithCDATA(string $name, string $value, ?string $namespace = null): self
	{
		$data = $this->getValidData();
		$child = $data->addChild($name, null, $namespace);

		if ($child !== null) {
			$dom = dom_import_simplexml($child);
			$ownerDocument = $dom->ownerDocument;
			if ($ownerDocument !== null) {
				$dom->appendChild($ownerDocument->createCDATASection($value));
			}
		}

		return new self($child);
	}

	/**
	 * Add a processing instruction (e.g., <?xml-stylesheet ?>) to the document.
	 *
	 * Inserts the PI before the document element via the DOM layer.
	 *
	 * @param string $target The PI target (e.g., 'xml-stylesheet').
	 * @param string $data   The PI data string (e.g., 'type="text/xsl" href="style.xsl"').
	 *
	 * @return self Returns $this for method chaining.
	 *
	 * @throws RuntimeException If no valid XML data is loaded.
	 */
	public function addProcessingInstruction(string $target, string $data): self
	{
		$xmlData = $this->getValidData();
		$dom = dom_import_simplexml($xmlData)->ownerDocument;

		if ($dom !== null) {
			$pi = $dom->createProcessingInstruction($target, $data);
			$dom->insertBefore($pi, $dom->documentElement);
		}

		return $this;
	}

	/**
	 * Add an XML comment as a child of the current element.
	 *
	 * @param string $text The comment text.
	 *
	 * @return self Returns $this for method chaining.
	 *
	 * @throws RuntimeException If no valid XML data is loaded.
	 */
	public function addComment(string $text): self
	{
		$xmlData = $this->getValidData();
		$dom = dom_import_simplexml($xmlData);
		$ownerDocument = $dom->ownerDocument;

		if ($ownerDocument !== null) {
			$comment = $ownerDocument->createComment($text);
			$dom->appendChild($comment);
		}

		return $this;
	}

	/**
	 * Replace the text content of the current element.
	 *
	 * Directly sets the nodeValue of the underlying DOM node, which
	 * replaces all existing text content (but not child elements).
	 *
	 * @param string $value The new text content.
	 *
	 * @return self Returns $this for method chaining.
	 *
	 * @throws RuntimeException If no valid XML data is loaded.
	 */
	public function setValue(string $value): self
	{
		$data = $this->getValidData();
		$dom = dom_import_simplexml($data);
		$dom->nodeValue = $value;

		return $this;
	}

	/**
	 * Remove the current element from its parent document.
	 *
	 * After removal, the internal data is set to null and this instance
	 * becomes invalid (isValid() returns false).
	 *
	 * @return void
	 *
	 * @throws RuntimeException If no valid XML data is loaded.
	 */
	public function remove(): void
	{
		$data = $this->getValidData();
		$dom = dom_import_simplexml($data);
		$parent = $dom->parentNode;

		if ($parent !== null) {
			$parent->removeChild($dom);
		}

		$this->data = null;
	}

	/**
	 * Remove all direct child elements from the current element.
	 *
	 * Iterates children in reverse order to safely remove each via DOM.
	 *
	 * @return self Returns $this for method chaining.
	 *
	 * @throws RuntimeException If no valid XML data is loaded.
	 */
	public function removeAllChildren(): self
	{
		$data = $this->getValidData();
		$dom = dom_import_simplexml($data);

		while ($dom->firstChild) {
			$dom->removeChild($dom->firstChild);
		}

		return $this;
	}

	/**
	 * Remove all child elements that match a given tag name.
	 *
	 * @param string $name The element tag name to remove.
	 *
	 * @return self Returns $this for method chaining.
	 */
	public function removeChildrenByName(string $name): self
	{
		if (!$this->data instanceof SimpleXMLElement) {
			return $this;
		}

		$dom = dom_import_simplexml($this->data);
		$toRemove = [];

		foreach ($dom->childNodes as $child) {
			if ($child->nodeType === XML_ELEMENT_NODE && $child->localName === $name) {
				$toRemove[] = $child;
			}
		}

		foreach ($toRemove as $node) {
			$dom->removeChild($node);
		}

		return $this;
	}

	/**
	 * Insert a new sibling element before the current element.
	 *
	 * Creates the new element via DOM and inserts it before the current node
	 * in the parent's child list.
	 *
	 * @param string      $name  The new element's tag name.
	 * @param string|null $value Optional text content.
	 *
	 * @return self A new instance wrapping the inserted sibling.
	 *
	 * @throws RuntimeException If no valid XML data is loaded or the element has no parent.
	 */
	public function insertBefore(string $name, ?string $value = null): self
	{
		$data = $this->getValidData();
		$dom = dom_import_simplexml($data);
		$parent = $dom->parentNode;

		if ($parent === null || $parent->ownerDocument === null) {
			throw new RuntimeException('Cannot insert before an element with no parent');
		}

		$newNode = $parent->ownerDocument->createElement($name);
		if ($value !== null) {
			$newNode->nodeValue = $value;
		}

		$parent->insertBefore($newNode, $dom);

		return new self(simplexml_import_dom($newNode));
	}

	/**
	 * Insert a new sibling element after the current element.
	 *
	 * If the current element is the last child, the new element is appended.
	 * Otherwise it is inserted before the next sibling.
	 *
	 * @param string      $name  The new element's tag name.
	 * @param string|null $value Optional text content.
	 *
	 * @return self A new instance wrapping the inserted sibling.
	 *
	 * @throws RuntimeException If no valid XML data is loaded or the element has no parent.
	 */
	public function insertAfter(string $name, ?string $value = null): self
	{
		$data = $this->getValidData();
		$dom = dom_import_simplexml($data);
		$parent = $dom->parentNode;

		if ($parent === null || $parent->ownerDocument === null) {
			throw new RuntimeException('Cannot insert after an element with no parent');
		}

		$newNode = $parent->ownerDocument->createElement($name);
		if ($value !== null) {
			$newNode->nodeValue = $value;
		}

		if ($dom->nextSibling !== null) {
			$parent->insertBefore($newNode, $dom->nextSibling);
		} else {
			$parent->appendChild($newNode);
		}

		return new self(simplexml_import_dom($newNode));
	}

	/**
	 * Replace the current element with a new element in the parent document.
	 *
	 * Creates a new element with the given name and value, replaces the current
	 * DOM node in its parent, and returns a wrapper around the replacement.
	 *
	 * @param string      $name  The replacement element's tag name.
	 * @param string|null $value Optional text content for the replacement.
	 *
	 * @return self A new instance wrapping the replacement element.
	 *
	 * @throws RuntimeException If no valid XML data or no parent node exists.
	 */
	public function replaceWith(string $name, ?string $value = null): self
	{
		$data = $this->getValidData();
		$dom = dom_import_simplexml($data);
		$parent = $dom->parentNode;

		if ($parent === null || $parent->ownerDocument === null) {
			throw new RuntimeException('Cannot replace an element with no parent');
		}

		$newNode = $parent->ownerDocument->createElement($name);
		if ($value !== null) {
			$newNode->nodeValue = $value;
		}

		$parent->replaceChild($newNode, $dom);
		$this->data = null;

		return new self(simplexml_import_dom($newNode));
	}

	/**
	 * Wrap the current element inside a new parent element.
	 *
	 * Removes the current element from its parent, creates a new wrapper element,
	 * appends the current element as a child of the wrapper, and inserts the wrapper
	 * where the current element used to be.
	 *
	 * @param string $wrapperName The tag name of the new wrapping element.
	 *
	 * @return self A new instance wrapping the outer wrapper element.
	 *
	 * @throws RuntimeException If no valid data or parent exists.
	 */
	public function wrap(string $wrapperName): self
	{
		$data = $this->getValidData();
		$dom = dom_import_simplexml($data);
		$parent = $dom->parentNode;

		if ($parent === null || $parent->ownerDocument === null) {
			throw new RuntimeException('Cannot wrap an element with no parent');
		}

		$wrapper = $parent->ownerDocument->createElement($wrapperName);
		$parent->replaceChild($wrapper, $dom);
		$wrapper->appendChild($dom);

		return new self(simplexml_import_dom($wrapper));
	}

	/**
	 * Import and append another SimpleXML document's root children into this element.
	 *
	 * Deep-copies each child element from the source into the current element
	 * via the DOM importNode mechanism.
	 *
	 * @param self $source The SimpleXML instance whose children will be imported.
	 *
	 * @return self Returns $this for method chaining.
	 *
	 * @throws RuntimeException If either instance lacks valid XML data.
	 */
	public function importFrom(self $source): self
	{
		$targetData = $this->getValidData();
		$sourceData = $source->getValidData();

		$targetDom = dom_import_simplexml($targetData);
		$sourceDom = dom_import_simplexml($sourceData);

		if ($targetDom->ownerDocument === null) {
			throw new RuntimeException('Target element has no owner document');
		}

		foreach ($sourceDom->childNodes as $child) {
			$imported = $targetDom->ownerDocument->importNode($child, true);
			$targetDom->appendChild($imported);
		}

		return $this;
	}

	// =========================================================================
	// XPath & Namespace
	// =========================================================================

	/**
	 * Execute an XPath query against the current element.
	 *
	 * All previously registered namespaces are applied before querying.
	 * Returns an array of matching elements, each wrapped in a SimpleXML instance.
	 *
	 * @param string $expression The XPath expression to evaluate.
	 *
	 * @return self[] Array of matched elements.
	 *
	 * @throws RuntimeException If no valid XML data is loaded.
	 */
	public function xpath(string $expression): array
	{
		$data = $this->getValidData();

		foreach ($this->namespaces as $prefix => $uri) {
			$data->registerXPathNamespace($prefix, $uri);
		}

		$result = $data->xpath($expression);

		if ($result === false) {
			return [];
		}

		return array_map(fn($element) => new self($element), $result);
	}

	/**
	 * Execute an XPath query and return the first matching element, or null.
	 *
	 * @param string $expression The XPath expression.
	 *
	 * @return self|null The first match, or null if no elements matched.
	 */
	public function xpathFirst(string $expression): ?self
	{
		$results = $this->xpath($expression);

		return !empty($results) ? $results[0] : null;
	}

	/**
	 * Check whether any elements match the given XPath expression.
	 *
	 * @param string $expression The XPath expression to test.
	 *
	 * @return bool True if at least one element matches.
	 */
	public function xpathExists(string $expression): bool
	{
		return !empty($this->xpath($expression));
	}

	/**
	 * Evaluate an XPath expression that returns a scalar value (string, number, boolean).
	 *
	 * Uses DOMXPath::evaluate() to compute the result of expressions like
	 * count(), sum(), string(), boolean(), and so on.
	 *
	 * @param string $expression An XPath expression that yields a scalar result.
	 *
	 * @return string|float|bool|null The evaluated result, or null if evaluation fails.
	 *
	 * @throws RuntimeException If no valid XML data is loaded.
	 */
	public function xpathEvaluate(string $expression): string|float|bool|null
	{
		$data = $this->getValidData();
		$dom = dom_import_simplexml($data)->ownerDocument;

		if ($dom === null) {
			return null;
		}

		$xpath = new DOMXPath($dom);

		foreach ($this->namespaces as $prefix => $uri) {
			$xpath->registerNamespace($prefix, $uri);
		}

		$result = $xpath->evaluate($expression);

		if ($result === false) {
			return null;
		}

		return $result;
	}

	/**
	 * Register an XPath namespace prefix and URI for use in subsequent queries.
	 *
	 * The mapping is stored internally and applied automatically before each
	 * XPath query and evaluation.
	 *
	 * @param string $prefix The namespace prefix (e.g., 'atom').
	 * @param string $uri    The namespace URI (e.g., 'http://www.w3.org/2005/Atom').
	 *
	 * @return self Returns $this for method chaining.
	 */
	public function registerNamespace(string $prefix, string $uri): self
	{
		$this->namespaces[$prefix] = $uri;

		if ($this->data instanceof SimpleXMLElement) {
			$this->data->registerXPathNamespace($prefix, $uri);
		}

		return $this;
	}

	/**
	 * Retrieve namespace declarations used by the current element and optionally its descendants.
	 *
	 * @param bool $recursive If true, collect namespaces from the entire subtree.
	 *
	 * @return array<string, string> Prefix-to-URI namespace map.
	 */
	public function getNamespaces(bool $recursive = false): array
	{
		if (!$this->data instanceof SimpleXMLElement) {
			return [];
		}

		return $this->data->getNamespaces($recursive);
	}

	/**
	 * Retrieve namespace declarations from the document root and optionally from all descendants.
	 *
	 * @param bool $recursive If true, collect namespaces from the entire document tree.
	 * @param bool $fromRoot  If true (default), start collection from the document root.
	 *
	 * @return array<string, string> Prefix-to-URI namespace map.
	 */
	public function getDocNamespaces(bool $recursive = false, bool $fromRoot = true): array
	{
		if (!$this->data instanceof SimpleXMLElement) {
			return [];
		}

		return $this->data->getDocNamespaces($recursive, $fromRoot);
	}

	// =========================================================================
	// Text & Content
	// =========================================================================

	/**
	 * Retrieve the text content of the current element.
	 *
	 * Returns the concatenated text nodes of the element, excluding child
	 * element content. If no valid data is present, returns an empty string.
	 *
	 * @return string The element's text content.
	 */
	public function getText(): string
	{
		if (!$this->data instanceof SimpleXMLElement) {
			return '';
		}

		return (string) $this->data;
	}

	/**
	 * Retrieve the inner XML markup of the current element (child nodes serialized as XML).
	 *
	 * Uses the DOM layer to serialize each child node individually, including
	 * element children, text nodes, comments, and CDATA sections.
	 *
	 * @return string The inner XML string, or empty if no valid data.
	 */
	public function getInnerXML(): string
	{
		if (!$this->data instanceof SimpleXMLElement) {
			return '';
		}

		$dom = dom_import_simplexml($this->data);
		$inner = '';

		foreach ($dom->childNodes as $child) {
			$inner .= $dom->ownerDocument->saveXML($child);
		}

		return $inner;
	}

	/**
	 * Retrieve the outer XML of the current element (the element itself and all its content).
	 *
	 * @return string The outer XML string.
	 */
	public function getOuterXML(): string
	{
		if (!$this->data instanceof SimpleXMLElement) {
			return '';
		}

		$dom = dom_import_simplexml($this->data);

		return $dom->ownerDocument->saveXML($dom) ?: '';
	}

	// =========================================================================
	// Serialization
	// =========================================================================

	/**
	 * Serialize the current element (or the entire document) to an XML string.
	 *
	 * If $formatted is true, the output is pretty-printed with indentation
	 * via DOMDocument's formatOutput feature.
	 *
	 * @param bool $formatted If true, produce human-readable indented output.
	 *
	 * @return string The serialized XML string.
	 *
	 * @throws RuntimeException If no valid XML data is loaded.
	 */
	public function toXML(bool $formatted = false): string
	{
		$data = $this->getValidData();
		$xml = $data->asXML();

		if ($xml === false) {
			return '';
		}

		if ($formatted) {
			$dom = new DOMDocument('1.0', 'UTF-8');
			$dom->preserveWhiteSpace = false;
			$dom->formatOutput = true;
			$dom->loadXML($xml);

			return $dom->saveXML() ?: $xml;
		}

		return $xml;
	}

	/**
	 * Write the serialized XML to a file on disk.
	 *
	 * @param string $filePath  Path to the output file.
	 * @param bool   $formatted If true, pretty-print the output.
	 *
	 * @return bool True if the file was written successfully.
	 */
	public function saveToFile(string $filePath, bool $formatted = false): bool
	{
		$xml = $this->toXML($formatted);

		return file_put_contents($filePath, $xml) !== false;
	}

	/**
	 * Produce the canonical (C14N) form of the current element's XML.
	 *
	 * Canonical XML is useful for digital signature verification and document
	 * comparison, as it normalizes whitespace, attribute ordering, and namespace
	 * declarations according to the W3C Canonical XML specification.
	 *
	 * @param bool     $exclusive       If true, use Exclusive C14N (omits unused ancestor namespaces).
	 * @param bool     $withComments    If true, include comments in the canonical output.
	 * @param string[] $xpathFilter     Optional array of XPath expressions to select nodes.
	 * @param string[] $prefixNamespaces Optional namespace prefixes to include (exclusive mode only).
	 *
	 * @return string The canonicalized XML string, or empty if data is invalid.
	 */
	public function canonicalize(bool $exclusive = false, bool $withComments = false, array $xpathFilter = [], array $prefixNamespaces = []): string
	{
		if (!$this->data instanceof SimpleXMLElement) {
			return '';
		}

		$dom = dom_import_simplexml($this->data);

		$result = $dom->C14N(
			$exclusive,
			$withComments,
			!empty($xpathFilter) ? ['query' => $xpathFilter[0]] : null,
			!empty($prefixNamespaces) ? $prefixNamespaces : null
		);

		return $result !== false ? $result : '';
	}

	// =========================================================================
	// Conversion
	// =========================================================================

	/**
	 * Convert the XML structure to a nested stdClass object.
	 *
	 * Attributes are placed in an '@attributes' property. Child elements with
	 * duplicate sibling names are collected into arrays. Leaf elements with
	 * attributes produce an object with '@attributes' and '@value' keys.
	 *
	 * @param SimpleXMLElement|null $xml Optional element to convert (defaults to internal data).
	 *
	 * @return stdClass The converted object.
	 */
	public function toObjectData(?SimpleXMLElement $xml = null): stdClass
	{
		$object = new stdClass();
		$xml = $xml ?? ($this->data instanceof SimpleXMLElement ? $this->data : null);

		if ($xml === null) {
			return $object;
		}

		$attributes = $xml->attributes();
		if ($attributes !== null && $attributes->count() > 0) {
			$object->{'@attributes'} = [];
			foreach ($attributes as $key => $value) {
				$object->{'@attributes'}[$key] = (string) $value;
			}
		}

		foreach ($xml->children() as $index => $element) {
			$value = null;

			if ($element->children()->count() > 0) {
				$childNames = array_map(fn($child) => $child->getName(), iterator_to_array($element->children()));
				$hasDuplicates = count(array_unique($childNames)) < count($childNames);

				if ($hasDuplicates) {
					$value = [];
					foreach ($element->children() as $child) {
						$value[] = $this->toObjectData($child);
					}
				} else {
					$value = $this->toObjectData($element);
				}
			} else {
				$value = (string) $element;

				$elementAttrs = $element->attributes();
				if ($elementAttrs !== null && $elementAttrs->count() > 0) {
					$attrArray = [];
					foreach ($elementAttrs as $key => $attrValue) {
						$attrArray[$key] = (string) $attrValue;
					}
					$value = [
						'@attributes' => $attrArray,
						'@value' => (string) $element
					];
				}
			}

			if (isset($object->$index)) {
				if (!is_array($object->$index)) {
					$object->$index = [$object->$index];
				}
				$object->$index[] = $value;
			} else {
				$object->$index = $value;
			}
		}

		return $object;
	}

	/**
	 * Convert the XML structure to a nested associative array.
	 *
	 * Attributes are placed under the '@attributes' key. Leaf elements with
	 * attributes produce an array with '@attributes' and '@value'. Multiple
	 * sibling elements with the same name are collected into indexed arrays.
	 *
	 * @param SimpleXMLElement|null $xml Optional element to convert (defaults to internal data).
	 *
	 * @return array<string, mixed> The converted array.
	 */
	public function toArray(?SimpleXMLElement $xml = null): array
	{
		$xml = $xml ?? ($this->data instanceof SimpleXMLElement ? $this->data : null);

		if ($xml === null) {
			return [];
		}

		$result = [];

		$attributes = $xml->attributes();
		if ($attributes !== null && $attributes->count() > 0) {
			foreach ($attributes as $key => $value) {
				$result['@attributes'][$key] = (string) $value;
			}
		}

		foreach ($xml->children() as $name => $child) {
			$childArray = $child->children()->count() > 0
				? $this->toArray($child)
				: (string) $child;

			if (is_string($childArray)) {
				$childAttrs = $child->attributes();
				if ($childAttrs !== null && $childAttrs->count() > 0) {
					$attrArray = [];
					foreach ($childAttrs as $key => $value) {
						$attrArray[$key] = (string) $value;
					}
					$childArray = [
						'@attributes' => $attrArray,
						'@value' => $childArray
					];
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
	 * Serialize the XML structure to a JSON string.
	 *
	 * Converts to an associative array first, then encodes with the given flags.
	 *
	 * @param int $flags JSON encoding flags (e.g., JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).
	 *
	 * @return string The JSON representation, or '{}' on encoding failure.
	 */
	public function toJSON(int $flags = 0): string
	{
		return json_encode($this->toArray(), $flags) ?: '{}';
	}

	/**
	 * Populate the current XML element from an associative or indexed array.
	 *
	 * Array keys become element names (sanitized for XML compliance).
	 * Integer keys produce elements named 'item'. Nested arrays create
	 * nested child elements recursively.
	 *
	 * @param array<string|int, mixed> $array The source data.
	 *
	 * @return static Returns $this for method chaining.
	 *
	 * @throws RuntimeException If no valid XML data is loaded.
	 */
	public function fromArray(array $array): static
	{
		$root = $this->getValidData();

		$add = function ($parent, $data) use (&$add) {
			foreach ($data as $key => $value) {
				if (is_int($key)) {
					$name = 'item';
				} else {
					$name = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $key);
					if ($name === '' || preg_match('/^[0-9]/', $name)) {
						$name = "item_{$name}";
					}
				}

				if (is_array($value)) {
					$child = $parent->addChild($name);
					$add($child, $value);
				} else {
					$parent->addChild($name, htmlspecialchars((string) $value, ENT_XML1 | ENT_COMPAT, 'UTF-8'));
				}
			}
		};

		$add($root, $array);

		return $this;
	}

	// =========================================================================
	// Validation
	// =========================================================================

	/**
	 * Validate the document against an XSD (XML Schema Definition) file or string.
	 *
	 * Returns an array of LibXMLError objects describing any validation failures.
	 * An empty array indicates successful validation.
	 *
	 * @param string $xsd      The XSD content string or file path.
	 * @param bool   $isFile   If true, treat $xsd as a file path; otherwise as an inline schema string.
	 *
	 * @return LibXMLError[] Array of validation errors (empty if valid).
	 *
	 * @throws RuntimeException         If no valid XML data is loaded.
	 * @throws InvalidArgumentException If $isFile is true and the file does not exist.
	 */
	public function validateSchema(string $xsd, bool $isFile = false): array
	{
		$data = $this->getValidData();
		$dom = new DOMDocument();
		$dom->loadXML($data->asXML());

		libxml_use_internal_errors(true);

		if ($isFile) {
			if (!file_exists($xsd)) {
				throw new InvalidArgumentException(sprintf('XSD file not found: %s', $xsd));
			}
			$dom->schemaValidate($xsd);
		} else {
			$dom->schemaValidateSource($xsd);
		}

		$errors = libxml_get_errors();
		libxml_clear_errors();
		libxml_use_internal_errors(false);

		return $errors;
	}

	/**
	 * Validate the document against a DTD (Document Type Definition).
	 *
	 * @param string $dtd The DTD content as an inline string.
	 *
	 * @return LibXMLError[] Array of validation errors (empty if valid).
	 *
	 * @throws RuntimeException If no valid XML data is loaded.
	 */
	public function validateDTD(string $dtd): array
	{
		$data = $this->getValidData();
		$xml = $data->asXML();

		$internalSubset = sprintf('<!DOCTYPE %s [%s]>', $data->getName(), $dtd);
		$xmlWithDTD = preg_replace('/<\?xml[^?]*\?>/', "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n{$internalSubset}", $xml, 1);

		$dom = new DOMDocument();
		libxml_use_internal_errors(true);
		$dom->loadXML($xmlWithDTD);
		$dom->validate();

		$errors = libxml_get_errors();
		libxml_clear_errors();
		libxml_use_internal_errors(false);

		return $errors;
	}

	/**
	 * Validate the document against a RelaxNG schema file or string.
	 *
	 * @param string $relaxNG The RelaxNG schema content or file path.
	 * @param bool   $isFile  If true, treat the input as a file path.
	 *
	 * @return LibXMLError[] Array of validation errors (empty if valid).
	 *
	 * @throws RuntimeException         If no valid XML data is loaded.
	 * @throws InvalidArgumentException If $isFile is true and the file does not exist.
	 */
	public function validateRelaxNG(string $relaxNG, bool $isFile = false): array
	{
		$data = $this->getValidData();
		$dom = new DOMDocument();
		$dom->loadXML($data->asXML());

		libxml_use_internal_errors(true);

		if ($isFile) {
			if (!file_exists($relaxNG)) {
				throw new InvalidArgumentException(sprintf('RelaxNG file not found: %s', $relaxNG));
			}
			$dom->relaxNGValidate($relaxNG);
		} else {
			$dom->relaxNGValidateSource($relaxNG);
		}

		$errors = libxml_get_errors();
		libxml_clear_errors();
		libxml_use_internal_errors(false);

		return $errors;
	}

	// =========================================================================
	// Transformation
	// =========================================================================

	/**
	 * Apply an XSLT stylesheet to transform the current document.
	 *
	 * Uses PHP's XSLTProcessor to apply the stylesheet. Optional parameters
	 * can be passed as key-value pairs.
	 *
	 * @param string              $xslt       The XSLT stylesheet as an XML string.
	 * @param array<string,string> $parameters Optional XSLT parameters (name => value).
	 *
	 * @return self A new instance wrapping the transformation result.
	 *
	 * @throws RuntimeException If no valid XML data is loaded or the transformation fails.
	 */
	public function transform(string $xslt, array $parameters = []): self
	{
		if (!class_exists('XSLTProcessor')) {
			throw new RuntimeException('XSLTProcessor extension is not available');
		}

		$data = $this->getValidData();

		$xslDoc = new DOMDocument();
		$xslDoc->loadXML($xslt);

		$xmlDoc = new DOMDocument();
		$xmlDoc->loadXML($data->asXML());

		$processor = new XSLTProcessor();
		$processor->importStyleSheet($xslDoc);

		foreach ($parameters as $name => $value) {
			$processor->setParameter('', $name, $value);
		}

		$result = $processor->transformToXml($xmlDoc);

		if ($result === false || $result === null) {
			throw new RuntimeException('XSLT transformation failed');
		}

		return self::fromString($result);
	}

	/**
	 * Apply an XSLT stylesheet from a file path.
	 *
	 * @param string              $filePath   Path to the XSLT file.
	 * @param array<string,string> $parameters Optional XSLT parameters.
	 *
	 * @return self A new instance wrapping the transformation result.
	 *
	 * @throws InvalidArgumentException If the file does not exist.
	 * @throws RuntimeException         If the transformation fails.
	 */
	public function transformFromFile(string $filePath, array $parameters = []): self
	{
		if (!file_exists($filePath)) {
			throw new InvalidArgumentException(sprintf('XSLT file not found: %s', $filePath));
		}

		$xslt = file_get_contents($filePath);
		if ($xslt === false) {
			throw new RuntimeException(sprintf('Failed to read XSLT file: %s', $filePath));
		}

		return $this->transform($xslt, $parameters);
	}

	// =========================================================================
	// Merge & Diff
	// =========================================================================

	/**
	 * Merge another XML document's root-level children into this document.
	 *
	 * Children from the source that share a tag name with existing children
	 * can either overwrite (replace) or be appended alongside, controlled by
	 * the $overwrite parameter.
	 *
	 * @param self $other     The source document to merge from.
	 * @param bool $overwrite If true, replace existing children with the same name; if false, append.
	 *
	 * @return self Returns $this for method chaining.
	 *
	 * @throws RuntimeException If either instance lacks valid XML data.
	 */
	public function merge(self $other, bool $overwrite = false): self
	{
		$targetData = $this->getValidData();
		$sourceData = $other->getValidData();

		$targetDom = dom_import_simplexml($targetData);
		$sourceDom = dom_import_simplexml($sourceData);

		if ($targetDom->ownerDocument === null) {
			throw new RuntimeException('Target element has no owner document');
		}

		foreach ($sourceDom->childNodes as $sourceChild) {
			if ($sourceChild->nodeType !== XML_ELEMENT_NODE) {
				continue;
			}

			$imported = $targetDom->ownerDocument->importNode($sourceChild, true);

			if ($overwrite) {
				$existing = null;
				foreach ($targetDom->childNodes as $targetChild) {
					if ($targetChild->nodeType === XML_ELEMENT_NODE && $targetChild->localName === $sourceChild->localName) {
						$existing = $targetChild;
						break;
					}
				}

				if ($existing !== null) {
					$targetDom->replaceChild($imported, $existing);
				} else {
					$targetDom->appendChild($imported);
				}
			} else {
				$targetDom->appendChild($imported);
			}
		}

		return $this;
	}

	/**
	 * Compute a structural diff between this document and another.
	 *
	 * Compares child elements by tag name and text content at the first level.
	 * Returns an array with keys 'added', 'removed', and 'modified', each
	 * containing arrays of element names.
	 *
	 * @param self $other The document to compare against.
	 *
	 * @return array{added: string[], removed: string[], modified: string[]} The diff result.
	 */
	public function diff(self $other): array
	{
		$thisArray = $this->toArray();
		$otherArray = $other->toArray();

		$added = [];
		$removed = [];
		$modified = [];

		foreach ($otherArray as $key => $value) {
			if ($key === '@attributes') {
				continue;
			}
			if (!isset($thisArray[$key])) {
				$added[] = $key;
			} elseif ($thisArray[$key] !== $value) {
				$modified[] = $key;
			}
		}

		foreach ($thisArray as $key => $value) {
			if ($key === '@attributes') {
				continue;
			}
			if (!isset($otherArray[$key])) {
				$removed[] = $key;
			}
		}

		return [
			'added' => $added,
			'removed' => $removed,
			'modified' => $modified,
		];
	}

	// =========================================================================
	// Functional Collection Operations
	// =========================================================================

	/**
	 * Return the first child element, or null if there are no children.
	 *
	 * @return self|null The first child wrapped in a SimpleXML instance, or null.
	 */
	public function first(): ?self
	{
		if (!$this->data instanceof SimpleXMLElement) {
			return null;
		}

		$children = $this->data->children();
		foreach ($children as $child) {
			return new self($child);
		}

		return null;
	}

	/**
	 * Return the last child element, or null if there are no children.
	 *
	 * @return self|null The last child wrapped in a SimpleXML instance, or null.
	 */
	public function last(): ?self
	{
		if (!$this->data instanceof SimpleXMLElement) {
			return null;
		}

		$children = iterator_to_array($this->data->children());
		$last = end($children);

		return $last !== false ? new self($last) : null;
	}

	/**
	 * Return the nth child element (zero-indexed), or null if out of bounds.
	 *
	 * @param int $index The zero-based child index.
	 *
	 * @return self|null The child at the given index, or null.
	 */
	public function nth(int $index): ?self
	{
		if (!$this->data instanceof SimpleXMLElement) {
			return null;
		}

		$children = iterator_to_array($this->data->children());

		return isset($children[$index]) ? new self($children[$index]) : null;
	}

	/**
	 * Find the first child element matching a callback predicate.
	 *
	 * @param callable(self): bool $callback A function receiving a SimpleXML child and returning true to select it.
	 *
	 * @return self|null The first matching child, or null if none match.
	 */
	public function find(callable $callback): ?self
	{
		if (!$this->data instanceof SimpleXMLElement) {
			return null;
		}

		foreach ($this->data->children() as $child) {
			$wrapped = new self($child);
			if ($callback($wrapped)) {
				return $wrapped;
			}
		}

		return null;
	}

	/**
	 * Recursively search the entire subtree for the first element matching a callback.
	 *
	 * Performs a depth-first traversal, testing each descendant element against
	 * the callback. Returns the first match encountered, or null.
	 *
	 * @param callable(self): bool $callback A predicate function.
	 *
	 * @return self|null The first matching descendant, or null.
	 */
	public function findDeep(callable $callback): ?self
	{
		if (!$this->data instanceof SimpleXMLElement) {
			return null;
		}

		foreach ($this->data->children() as $child) {
			$wrapped = new self($child);

			if ($callback($wrapped)) {
				return $wrapped;
			}

			$deepResult = $wrapped->findDeep($callback);
			if ($deepResult !== null) {
				return $deepResult;
			}
		}

		return null;
	}

	/**
	 * Recursively collect all descendant elements matching a callback predicate.
	 *
	 * @param callable(self): bool $callback A predicate function.
	 *
	 * @return self[] All matching descendants from a depth-first traversal.
	 */
	public function findAllDeep(callable $callback): array
	{
		if (!$this->data instanceof SimpleXMLElement) {
			return [];
		}

		$results = [];

		foreach ($this->data->children() as $child) {
			$wrapped = new self($child);

			if ($callback($wrapped)) {
				$results[] = $wrapped;
			}

			$results = array_merge($results, $wrapped->findAllDeep($callback));
		}

		return $results;
	}

	/**
	 * Filter direct children, returning only those for which the callback returns true.
	 *
	 * @param callable(self): bool $callback A predicate function.
	 *
	 * @return self[] Array of matching children.
	 */
	public function filter(callable $callback): array
	{
		if (!$this->data instanceof SimpleXMLElement) {
			return [];
		}

		$result = [];
		foreach ($this->data->children() as $child) {
			$wrapped = new self($child);
			if ($callback($wrapped)) {
				$result[] = $wrapped;
			}
		}

		return $result;
	}

	/**
	 * Transform each direct child element using a callback and collect the results.
	 *
	 * @param callable(self): mixed $callback A function receiving each child and returning a transformed value.
	 *
	 * @return array The collected transformation results.
	 */
	public function map(callable $callback): array
	{
		if (!$this->data instanceof SimpleXMLElement) {
			return [];
		}

		$result = [];
		foreach ($this->data->children() as $child) {
			$result[] = $callback(new self($child));
		}

		return $result;
	}

	/**
	 * Reduce the child elements to a single value using an accumulator callback.
	 *
	 * @param callable(mixed, self): mixed $callback A reducer function receiving the carry value and current child.
	 * @param mixed                        $initial  The initial accumulator value.
	 *
	 * @return mixed The final accumulated result.
	 */
	public function reduce(callable $callback, mixed $initial = null): mixed
	{
		if (!$this->data instanceof SimpleXMLElement) {
			return $initial;
		}

		$carry = $initial;
		foreach ($this->data->children() as $child) {
			$carry = $callback($carry, new self($child));
		}

		return $carry;
	}

	/**
	 * Extract a single value from each child element using a callback.
	 *
	 * Commonly used to collect text content or attribute values from a list
	 * of sibling elements (similar to array_column for XML).
	 *
	 * @param callable(self): mixed $extractor A function that extracts a value from each child.
	 *
	 * @return array The collected values.
	 */
	public function pluck(callable $extractor): array
	{
		return $this->map($extractor);
	}

	/**
	 * Group child elements by a key derived from each child via a callback.
	 *
	 * Returns an associative array where keys are the grouping values and
	 * values are arrays of SimpleXML instances belonging to that group.
	 *
	 * @param callable(self): string $keyCallback A function that returns the group key for each child.
	 *
	 * @return array<string, self[]> Grouped children.
	 */
	public function groupBy(callable $keyCallback): array
	{
		if (!$this->data instanceof SimpleXMLElement) {
			return [];
		}

		$groups = [];
		foreach ($this->data->children() as $child) {
			$wrapped = new self($child);
			$key = $keyCallback($wrapped);
			$groups[$key][] = $wrapped;
		}

		return $groups;
	}

	/**
	 * Check whether every direct child satisfies the given predicate.
	 *
	 * Returns true if the callback returns true for all children, or if
	 * there are no children at all.
	 *
	 * @param callable(self): bool $callback A predicate function.
	 *
	 * @return bool True if all children match.
	 */
	public function every(callable $callback): bool
	{
		if (!$this->data instanceof SimpleXMLElement) {
			return true;
		}

		foreach ($this->data->children() as $child) {
			if (!$callback(new self($child))) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Check whether at least one direct child satisfies the given predicate.
	 *
	 * @param callable(self): bool $callback A predicate function.
	 *
	 * @return bool True if any child matches.
	 */
	public function some(callable $callback): bool
	{
		if (!$this->data instanceof SimpleXMLElement) {
			return false;
		}

		foreach ($this->data->children() as $child) {
			if ($callback(new self($child))) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Iterate over each direct child, invoking the callback for side effects.
	 *
	 * Unlike map(), the return value of the callback is ignored. Returns $this
	 * for method chaining.
	 *
	 * @param callable(self): void $callback A function to invoke on each child.
	 *
	 * @return self Returns $this for method chaining.
	 */
	public function each(callable $callback): self
	{
		if (!$this->data instanceof SimpleXMLElement) {
			return $this;
		}

		foreach ($this->data->children() as $child) {
			$callback(new self($child));
		}

		return $this;
	}

	/**
	 * Sort direct children in-place using a comparison callback.
	 *
	 * Temporarily removes all children, sorts them, and re-appends them
	 * in the sorted order via the DOM layer.
	 *
	 * @param callable(self, self): int $comparator A comparison function returning <0, 0, or >0.
	 *
	 * @return self Returns $this for method chaining.
	 *
	 * @throws RuntimeException If no valid XML data is loaded.
	 */
	public function sortChildren(callable $comparator): self
	{
		$data = $this->getValidData();
		$dom = dom_import_simplexml($data);

		$children = [];
		foreach ($dom->childNodes as $child) {
			if ($child->nodeType === XML_ELEMENT_NODE) {
				$children[] = $child;
			}
		}

		foreach ($children as $child) {
			$dom->removeChild($child);
		}

		usort($children, function ($a, $b) use ($comparator) {
			return $comparator(
				new self(simplexml_import_dom($a)),
				new self(simplexml_import_dom($b))
			);
		});

		foreach ($children as $child) {
			$dom->appendChild($child);
		}

		return $this;
	}

	/**
	 * Apply a mutator callback to every direct child element for in-place batch modification.
	 *
	 * The callback receives each child as a mutable SimpleXML reference.
	 *
	 * @param callable(self): void $callback A mutator function applied to each child.
	 *
	 * @return self Returns $this for method chaining.
	 */
	public function mutateEach(callable $callback): self
	{
		if (!$this->data instanceof SimpleXMLElement) {
			return $this;
		}

		foreach ($this->data->children() as $child) {
			$callback(new self($child));
		}

		return $this;
	}

	/**
	 * Invoke a callback with $this as the argument and return $this unchanged.
	 *
	 * Useful for inserting side effects (e.g., debugging, logging) into a
	 * method chain without breaking the fluent flow.
	 *
	 * @param callable(self): void $callback A function for side effects (e.g., var_dump, logging).
	 *
	 * @return self Returns $this for method chaining.
	 */
	public function tap(callable $callback): self
	{
		$callback($this);

		return $this;
	}

	/**
	 * Conditionally execute a callback on $this. If the condition is true,
	 * the callback is invoked with $this; otherwise $this is returned unchanged.
	 *
	 * @param bool                 $condition The boolean condition to test.
	 * @param callable(self): self $callback  A function that receives and may modify $this.
	 *
	 * @return self Returns $this (possibly modified by the callback).
	 */
	public function when(bool $condition, callable $callback): self
	{
        if (!is_callable($callback)) {
            throw new Exception('Callback is not callable');
        }

		if ($condition) {
			return $callback($this);
		}

		return $this;
	}

	// =========================================================================
	// Navigation
	// =========================================================================

	/**
	 * Navigate to the parent element of the current element.
	 *
	 * Uses the DOM layer to access the parent node. Returns null if the
	 * current element is the document root or has no parent.
	 *
	 * @return self|null The parent element, or null if at the root.
	 */
	public function parent(): ?self
	{
		if (!$this->data instanceof SimpleXMLElement) {
			return null;
		}

		$dom = dom_import_simplexml($this->data);
		$parent = $dom->parentNode;

		if ($parent === null || $parent->nodeType !== XML_ELEMENT_NODE) {
			return null;
		}

		return new self(simplexml_import_dom($parent));
	}

	/**
	 * Retrieve all sibling elements (children of the same parent, excluding self).
	 *
	 * Identifies self by matching both tag name and text content among the
	 * parent's children.
	 *
	 * @return self[] Array of sibling elements.
	 */
	public function siblings(): array
	{
		$parent = $this->parent();
		if ($parent === null) {
			return [];
		}

		$currentName = $this->getName();
		$currentText = $this->getText();

		return $parent->filter(function (self $child) use ($currentName, $currentText) {
			return !($child->getName() === $currentName && $child->getText() === $currentText);
		});
	}

	/**
	 * Navigate to the next sibling element.
	 *
	 * @return self|null The next sibling element, or null if this is the last child.
	 */
	public function nextSibling(): ?self
	{
		if (!$this->data instanceof SimpleXMLElement) {
			return null;
		}

		$dom = dom_import_simplexml($this->data);
		$next = $dom->nextSibling;

		while ($next !== null && $next->nodeType !== XML_ELEMENT_NODE) {
			$next = $next->nextSibling;
		}

		return $next !== null ? new self(simplexml_import_dom($next)) : null;
	}

	/**
	 * Navigate to the previous sibling element.
	 *
	 * @return self|null The previous sibling element, or null if this is the first child.
	 */
	public function previousSibling(): ?self
	{
		if (!$this->data instanceof SimpleXMLElement) {
			return null;
		}

		$dom = dom_import_simplexml($this->data);
		$prev = $dom->previousSibling;

		while ($prev !== null && $prev->nodeType !== XML_ELEMENT_NODE) {
			$prev = $prev->previousSibling;
		}

		return $prev !== null ? new self(simplexml_import_dom($prev)) : null;
	}

	/**
	 * Collect all ancestor elements from the current element up to the document root.
	 *
	 * Returns an array ordered from the immediate parent to the root element.
	 *
	 * @return self[] Ancestor elements in bottom-up order.
	 */
	public function ancestors(): array
	{
		$ancestors = [];
		$current = $this;

		while (($parent = $current->parent()) !== null) {
			$ancestors[] = $parent;
			$current = $parent;
		}

		return $ancestors;
	}

	/**
	 * Compute the depth of the current element in the document tree.
	 *
	 * The root element has depth 0. Each level of nesting adds 1.
	 *
	 * @return int The nesting depth.
	 */
	public function depth(): int
	{
		return count($this->ancestors());
	}

	// =========================================================================
	// Cloning
	// =========================================================================

	/**
	 * Create a deep clone of the current element as an independent document.
	 *
	 * Serializes the current element to XML and re-parses it, producing a
	 * completely detached copy with no shared references.
	 *
	 * @return self A new independent instance with identical content.
	 */
	public function clone(): self
	{
		if (!$this->data instanceof SimpleXMLElement) {
			return new self(null);
		}

		$xml = $this->data->asXML();

		return $xml !== false ? self::fromString($xml) : new self(null);
	}

	// =========================================================================
	// Iterator Implementation
	// =========================================================================

	/**
	 * Rewind the iterator to the first child element.
	 *
	 * Caches the child elements as an array for indexed access during iteration.
	 *
	 * @return void
	 */
	public function rewind(): void
	{
		$this->iteratorPosition = 0;
		$this->iteratorChildren = $this->data instanceof SimpleXMLElement
			? iterator_to_array($this->data->children())
			: [];
	}

	/**
	 * Return the current child element in the iteration.
	 *
	 * @return self The current child wrapped in a SimpleXML instance.
	 */
	public function current(): self
	{
		return new self($this->iteratorChildren[$this->iteratorPosition] ?? null);
	}

	/**
	 * Return the current iteration key (zero-based integer index).
	 *
	 * @return int The current index.
	 */
	public function key(): int
	{
		return $this->iteratorPosition;
	}

	/**
	 * Advance the iterator to the next child element.
	 *
	 * @return void
	 */
	public function next(): void
	{
		++$this->iteratorPosition;
	}

	/**
	 * Check whether the current iterator position is valid.
	 *
	 * @return bool True if the current position points to an existing child.
	 */
	public function valid(): bool
	{
		return isset($this->iteratorChildren[$this->iteratorPosition]);
	}

	// =========================================================================
	// Magic Methods
	// =========================================================================

	/**
	 * Return the text content of the element when cast to string.
	 *
	 * @return string The element's text content.
	 */
	public function __toString(): string
	{
		return $this->getText();
	}

	/**
	 * Property-style access to child elements (e.g., $xml->childName).
	 *
	 * @param string $name The child element name.
	 *
	 * @return self A new instance wrapping the accessed child.
	 */
	public function __get(string $name): self
	{
		return $this->get($name);
	}

	/**
	 * Property-style existence check for child elements (e.g., isset($xml->childName)).
	 *
	 * @param string $name The child element name.
	 *
	 * @return bool True if the child element exists.
	 */
	public function __isset(string $name): bool
	{
		return $this->has($name);
	}

	/**
	 * Produce a human-readable dump of the XML structure for debugging.
	 *
	 * Returns the pretty-printed XML string, or a descriptive message
	 * if no valid data is loaded.
	 *
	 * @return array Debug-friendly representation.
	 */
	public function __debugInfo(): array
	{
		return [
			'valid' => $this->isValid(),
			'name' => $this->getName(),
			'text' => $this->getText(),
			'childCount' => $this->count(),
			'attributes' => $this->getAttributes(),
			'xml' => $this->isValid() ? $this->toXML(true) : null,
		];
	}
}
