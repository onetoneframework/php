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
use DOMElement;
use DOMNodeList;
use DOMXPath;
use function array_filter;
use function array_map;
use function array_reduce;
use function array_slice;
use function array_values;
use function count;

/**
 * An ordered collection of {@see CrawlerNode} objects with bulk-extraction helpers.
 */
final class CrawlerNodeList implements \Countable, \IteratorAggregate
{
    /** @var CrawlerNode[] */
    private array $nodes;

    private function __construct(CrawlerNode ...$nodes)
    {
        $this->nodes = $nodes;
    }

    public static function empty(): self
    {
        return new self();
    }

    /** 
     * Build a `CrawlerNodeList` from an array of `CrawlerNode` objects.
     * 
     * @param CrawlerNode[] $nodes  An array of `CrawlerNode` instances to include in the list.
     * @return self A new `CrawlerNodeList` containing the provided nodes.
     * @throws \InvalidArgumentException if any element in `$nodes` is not an instance
     **/
    public static function fromCrawlerNodes(array $nodes): self
    {
        return new self(...$nodes);
    }

    /**
     * Build a `CrawlerNodeList` from a `DOMNodeList` returned by an XPath query.
     * 
     * @param DOMNodeList $list The `DOMNodeList` to convert into a `CrawlerNodeList`.
     * @param DOMXPath $xpath The `DOMXPath` instance used for querying (needed for node context).
     * @param string $baseUrl The base URL for resolving relative links (passed to each `CrawlerNode`).
     * @return self A new `CrawlerNodeList` containing `CrawlerNode` instances wrapping the DOM nodes.
     */
    public static function fromDomNodeList(DOMNodeList $list, DOMXPath $xpath, string $baseUrl): self
    {
        $nodes = [];
        foreach ($list as $node) {
            if ($node instanceof DOMElement) {
                $nodes[] = new CrawlerNode($node, $xpath, $baseUrl);
            }
        }
        return new self(...$nodes);
    }

    /**
     * Return the number of nodes in the list.
     */
    public function count(): int
    {
        return count($this->nodes);
    }

    /**
     * Check if the list is empty (contains no nodes).
     */
    public function isEmpty(): bool
    {
        return $this->nodes === [];
    }

    /** 
     * Return an iterator over the nodes in the list.
     * 
     * @return \ArrayIterator<int, CrawlerNode> An iterator that yields each `CrawlerNode` in the list.
     **/
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->nodes);
    }

    /**
     * Return the first node in the list, or `null` if the list is empty.
     * 
     * @return CrawlerNode|null The first node in the list, or null if the list is empty.
     */
    public function first(): ?CrawlerNode
    {
        return $this->nodes[0] ?? null;
    }

    /**
     * Return the last node in the list, or `null` if the list is empty.
     * 
     * @return CrawlerNode|null The last node in the list, or null if the list is empty.
     */
    public function last(): ?CrawlerNode
    {
        return $this->nodes !== [] ? $this->nodes[count($this->nodes) - 1] : null;
    }

    /**
     * Return the node at the specified index, or `null` if the index is out of bounds.
     * 
     * @param int $index The zero-based index of the node to retrieve.
     * @return CrawlerNode|null The node at the specified index, or null if the index is out of bounds.
     */
    public function get(int $index): ?CrawlerNode
    {
        return $this->nodes[$index] ?? null;
    }

    /**
     * Return the node at the specified index, or `null` if the index is out of bounds.
     * Alias for `get()`.
     * 
     * @param int $n The zero-based index of the node to retrieve.
     * @return CrawlerNode|null The node at the specified index, or null if the index is out of bounds.
     */
    public function nth(int $n): ?CrawlerNode
    {
        return $this->nodes[$n] ?? null;
    }

    /**
     * Return the text content of every node.
     *
     * @return string[]
     */
    public function texts(): array
    {
        return array_map(static fn(CrawlerNode $n) => $n->text(), $this->nodes);
    }

    /**
     * Return the value of `$attr` for every node (`null` entries are kept).
     *
     * @param string $attr The name of the attribute to extract from each node (e.g. 'href', 'src').
     * @return array<string|null>
     */
    public function attrs(string $attr): array
    {
        return array_map(static fn(CrawlerNode $n) => $n->attr($attr), $this->nodes);
    }

    /**
     * Return resolved `href` links for all `<a>` nodes in the list.
     *
     * @return string[]
     */
    public function links(): array
    {
        return array_values(
            array_filter(
                array_map(static fn(CrawlerNode $n) => $n->link(), $this->nodes),
                static fn(?string $l) => $l !== null
            )
        );
    }

    /** 
     * Return the outer HTML of every node in the list.
     *
     * @return string[] 
     **/
    public function outerHtmls(): array
    {
        return array_map(static fn(CrawlerNode $n) => $n->outerHtml(), $this->nodes);
    }

    /** 
     * Return the inner HTML of every node in the list.
     * 
     * @return string[] 
     **/
    public function innerHtmls(): array
    {
        return array_map(static fn(CrawlerNode $n) => $n->innerHtml(), $this->nodes);
    }

    /** 
     * Return the tag name of every node in the list.
     *
     * @return string[]
     **/
    public function tags(): array
    {
        return array_map(static fn(CrawlerNode $n) => $n->tag(), $this->nodes);
    }

    /**
     * Return a new list containing only nodes for which `$predicate` returns true.
     *
     * @param callable(CrawlerNode): bool $predicate
     */
    public function filter(callable $predicate): self
    {
        return self::fromCrawlerNodes(
            array_values(array_filter($this->nodes, $predicate))
        );
    }

    /**
     * Apply `$callback` to every node and return the results in an array.
     * 
     * @param callable(CrawlerNode, int): mixed $callback
     * @return array<mixed>
     */
    public function map(callable $callback): array
    {
        $result = [];
        foreach ($this->nodes as $index => $node) {
            $result[] = $callback($node, $index);
        }
        return $result;
    }

    /**
     * Apply `$callback` to every node (with index) for side effects.
     * 
     * @param callable(CrawlerNode, int): void $callback
     */
    public function each(callable $callback): void
    {
        foreach ($this->nodes as $index => $node) {
            $callback($node, $index);
        }
    }

    /**
     * Reduce the list to a single value by applying `$callback` cumulatively to the nodes.
     * 
     * @param callable(mixed, CrawlerNode): mixed $callback A function that takes the accumulated value and the current node, and returns the new accumulated value.
     * @param mixed $initial The initial value to start the reduction with.
     * @return mixed The final accumulated value after processing all nodes.
     */
    public function reduce(callable $callback, mixed $initial = null): mixed
    {
        return array_reduce($this->nodes, $callback, $initial);
    }

    /**
     * Return a new `CrawlerNodeList` containing only the nodes from index `$offset` to `$offset + $length - 1`.
     * 
     * @param int $offset The zero-based index at which to start the slice.
     * @param int|null $length The number of nodes to include in the slice (if null, includes all nodes from offset to the end).
     * @return self A new `CrawlerNodeList` containing the sliced subset of nodes.
     */
    public function slice(int $offset, ?int $length = null): self
    {
        return self::fromCrawlerNodes(array_slice($this->nodes, $offset, $length));
    }

    /**
     * Return a new `CrawlerNodeList` containing only unique nodes (based on their DOM path).
     * 
     * @return self A new `CrawlerNodeList` containing only unique nodes.
     */
    public function unique(): self
    {
        $seen = [];
        $nodes = [];

        foreach ($this->nodes as $node) {
            $path = $node->domElement()->getNodePath();
            if ($path !== null && !isset($seen[$path])) {
                $seen[$path] = true;
                $nodes[] = $node;
            }
        }

        return self::fromCrawlerNodes($nodes);
    }

    /**
     * Check if the list contains a node that is the same as the given node (based on DOM identity).
     * 
     * @param CrawlerNode $node The node to check for presence in the list.
     * @return bool True if the list contains a node that is the same as `$node`, false otherwise.
     */
    public function contains(CrawlerNode $node): bool
    {
        foreach ($this->nodes as $n) {
            if ($n->domElement()->isSameNode($node->domElement())) {
                return true;
            }
        }
        return false;
    }

    /** 
     * Return all nodes in the list as an array.
     * 
     * @return CrawlerNode[] 
     **/
    public function all(): array
    {
        return $this->nodes;
    }
}
