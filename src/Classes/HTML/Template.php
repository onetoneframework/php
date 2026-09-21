<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\HTML;

use Closure;
use Clover\Classes\Data\HTMLObject;
use RuntimeException;
use InvalidArgumentException;
use function is_array;
use function sprintf;
use function strlen;
use function count;
use function in_array;
use function call_user_func;
use function is_string;

class Template
{
	private string $templateDir = '';
	private string $cacheDir = '';
	private bool $cacheEnabled = false;
	private array $data = [];
	private array $directives = [];
	private array $shared = [];
	private array $macros = [];
	private array $filters = [];
	private array $blocks = [];
	private array $blockStack = [];
	private ?string $parentTemplate = null;
	private ?Closure $escapeHandler = null;

	private string $echoOpen = '{{';
	private string $echoClose = '}}';
	private string $rawOpen = '{!!';
	private string $rawClose = '!!}';
	private string $statementOpen = '{%';
	private string $statementClose = '%}';
	private string $commentOpen = '{#';
	private string $commentClose = '#}';

	private bool $nAttributesEnabled = false;
	private string $nAttributePrefix = 'n:';
	private array $nAttributeHandlers = [];

	private bool $autoEscape = true;

	private const VOID_ELEMENTS = ['area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'param', 'source', 'track', 'wbr'];

	/**
	 * Initialise the template engine with a default HTML escape handler,
	 * built-in filters, and default n:attribute handlers.
	 */
	public function __construct()
	{
		$this->escapeHandler = fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
		$this->registerDefaultFilters();
		$this->registerDefaultNAttributeHandlers();
	}

	/**
	 * Register the built-in set of template filters
	 *
	 * Populates $this->filters with closures for common string, array, and
	 * formatting operations (e.g. upper, lower, date, json, truncate, slug).
	 */
	private function registerDefaultFilters(): void
	{
		$this->filters = [
			'upper' => fn($v) => strtoupper((string) $v),
			'lower' => fn($v) => strtolower((string) $v),
			'capitalize' => fn($v) => ucfirst((string) $v),
			'title' => fn($v) => ucwords((string) $v),
			'trim' => fn($v) => trim((string) $v),
			'length' => fn($v) => is_array($v) ? count($v) : strlen((string) $v),
			'escape' => fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'),
			'raw' => fn($v) => $v,
			'nl2br' => fn($v) => nl2br((string) $v),
			'json' => fn($v) => json_encode($v, JSON_UNESCAPED_UNICODE),
			'date' => fn($v, $f = 'Y-m-d') => date($f, is_numeric($v) ? (int) $v : strtotime((string) $v)),
			'number' => fn($v, $d = 0) => number_format((float) $v, $d),
			'default' => fn($v, $d = '') => $v ?: $d,
			'join' => fn($v, $g = ', ') => is_array($v) ? implode($g, $v) : $v,
			'split' => fn($v, $d = ',') => explode($d, (string) $v),
			'first' => fn($v) => is_array($v) ? reset($v) : $v,
			'last' => fn($v) => is_array($v) ? end($v) : $v,
			'reverse' => fn($v) => is_array($v) ? array_reverse($v) : strrev((string) $v),
			'slice' => fn($v, $s, $l = null) => is_array($v) ? array_slice($v, $s, $l) : substr((string) $v, $s, $l),
			'sort' => function ($v) {
				if (is_array($v)) {
					sort($v);
				}return $v;
			},
			'keys' => fn($v) => is_array($v) ? array_keys($v) : [],
			'values' => fn($v) => is_array($v) ? array_values($v) : [],
			'merge' => fn($v, ...$a) => is_array($v) ? array_merge($v, ...$a) : $v,
			'batch' => fn($v, $s) => is_array($v) ? array_chunk($v, $s) : [$v],
			'column' => fn($v, $k) => is_array($v) ? array_column($v, $k) : [],
			'spaceless' => fn($v) => preg_replace('/>\s+</', '><', (string) $v),
			'striptags' => fn($v) => strip_tags((string) $v),
			'truncate' => fn($v, $l = 100, $e = '...') => strlen((string) $v) > $l ? substr((string) $v, 0, $l) . $e : $v,
			'slug' => fn($v) => preg_replace('/[^a-z0-9]+/', '-', strtolower((string) $v)),
			'md5' => fn($v) => md5((string) $v),
			'base64' => fn($v) => base64_encode((string) $v),
			'urlencode' => fn($v) => urlencode((string) $v),
		];
	}

	/**
	 * Register the built-in set of n:attribute handlers
	 *
	 * Defines how prefixed attributes such as n:if, n:foreach, n:class, n:attr,
	 * n:block, and n:snippet are compiled into PHP control structures or HTML
	 * attribute expressions.
	 */
	private function registerDefaultNAttributeHandlers(): void
	{
		$this->nAttributeHandlers = [
			'if' => ['type' => 'wrapper', 'open' => 'if (%s):', 'close' => 'endif;'],
			'ifset' => ['type' => 'wrapper', 'open' => 'if (isset(%s)):', 'close' => 'endif;'],
			'foreach' => ['type' => 'wrapper', 'open' => 'foreach (%s):', 'close' => 'endforeach;'],
			'for' => ['type' => 'wrapper', 'open' => 'for (%s):', 'close' => 'endfor;'],
			'while' => ['type' => 'wrapper', 'open' => 'while (%s):', 'close' => 'endwhile;'],
			'tag-if' => ['type' => 'tag-conditional'],
			'inner-foreach' => ['type' => 'inner-wrapper', 'open' => 'foreach (%s):', 'close' => 'endforeach;'],
			'inner-if' => ['type' => 'inner-wrapper', 'open' => 'if (%s):', 'close' => 'endif;'],
			'class' => ['type' => 'attribute', 'attr' => 'class', 'helper' => '$__nClass'],
			'attr' => ['type' => 'dynamic-attrs'],
			'href' => ['type' => 'attribute', 'attr' => 'href'],
			'src' => ['type' => 'attribute', 'attr' => 'src'],
			'id' => ['type' => 'attribute', 'attr' => 'id'],
			'block' => ['type' => 'block'],
			'snippet' => ['type' => 'snippet'],
		];
	}

	/**
	 * Set the base directory from which template files are resolved
	 *
	 * @param string $dir Absolute path to the template directory
	 * @return self
	 * @throws InvalidArgumentException If the directory does not exist
	 */
	public function setTemplateDir(string $dir): self
	{
		if (!is_dir($dir)) {
			throw new InvalidArgumentException(sprintf('Template directory not found: %s', $dir));
		}

		$this->templateDir = rtrim($dir, '/\\');
		return $this;
	}

	/**
	 * Set the directory used to store compiled template cache files and enable caching
	 *
	 * @param string $dir Absolute path to a writable cache directory
	 * @return self
	 * @throws InvalidArgumentException If the directory does not exist or is not writable
	 */
	public function setCacheDir(string $dir): self
	{
		if (!is_dir($dir) || !is_writable($dir)) {
			throw new InvalidArgumentException(sprintf('Cache directory not writable: %s', $dir));
		}

		$this->cacheDir = rtrim($dir, '/\\');
		$this->cacheEnabled = true;
		return $this;
	}

	/**
	 * Enable or disable template caching
	 *
	 * Caching is only active when a cache directory has already been configured.
	 *
	 * @param bool $enabled True to enable caching (default), false to disable
	 * @return self
	 */
	public function enableCache(bool $enabled = true): self
	{
		$this->cacheEnabled = $enabled && !empty($this->cacheDir);
		return $this;
	}

	/**
	 * Set the opening and closing delimiters used for escaped echo expressions
	 *
	 * @param string $open  Opening delimiter (e.g. '{{')
	 * @param string $close Closing delimiter (e.g. '}}')
	 * @return self
	 */
	public function setEchoDelimiters(string $open, string $close): self
	{
		$this->echoOpen = $open;
		$this->echoClose = $close;
		return $this;
	}

	/**
	 * Set the opening and closing delimiters used for unescaped (raw) echo expressions
	 *
	 * @param string $open  Opening delimiter (e.g. '{!!')
	 * @param string $close Closing delimiter (e.g. '!!}')
	 * @return self
	 */
	public function setRawDelimiters(string $open, string $close): self
	{
		$this->rawOpen = $open;
		$this->rawClose = $close;
		return $this;
	}

	/**
	 * Set the opening and closing delimiters used for control-flow statement tags
	 *
	 * @param string $open  Opening delimiter (e.g. '{%')
	 * @param string $close Closing delimiter (e.g. '%}')
	 * @return self
	 */
	public function setStatementDelimiters(string $open, string $close): self
	{
		$this->statementOpen = $open;
		$this->statementClose = $close;
		return $this;
	}

	/**
	 * Set the opening and closing delimiters used for template comments
	 *
	 * @param string $open  Opening delimiter (e.g. '{#')
	 * @param string $close Closing delimiter (e.g. '#}')
	 * @return self
	 */
	public function setCommentDelimiters(string $open, string $close): self
	{
		$this->commentOpen = $open;
		$this->commentClose = $close;
		return $this;
	}

	/**
	 * Enable or disable processing of n:attribute directives in HTML tags
	 *
	 * @param bool $enabled True to enable n:attribute compilation (default), false to disable
	 * @return self
	 */
	public function enableNAttributes(bool $enabled = true): self
	{
		$this->nAttributesEnabled = $enabled;
		return $this;
	}

	/**
	 * Set the prefix string that identifies n:attribute directives in HTML tags
	 *
	 * @param string $prefix The attribute prefix (e.g. 'n:')
	 * @return self
	 */
	public function setNAttributePrefix(string $prefix): self
	{
		$this->nAttributePrefix = $prefix;
		return $this;
	}

	/**
	 * Register a custom n:attribute handler identified by the given name
	 *
	 * The handler closure receives the attribute value and should return
	 * the compiled PHP/HTML string for that attribute.
	 *
	 * @param string  $name    The attribute name (without the n: prefix)
	 * @param Closure $handler Closure that compiles the attribute
	 * @return self
	 */
	public function nAttribute(string $name, Closure $handler): self
	{
		$this->nAttributeHandlers[$name] = ['type' => 'custom', 'handler' => $handler];
		return $this;
	}

	/**
	 * Apply a predefined template-engine syntax style
	 *
	 * Supported styles: 'blade', 'twig', 'latte'.
	 * Any unknown style falls back to the default (Twig-like) delimiters.
	 *
	 * @param string $style Name of the syntax style to apply
	 * @return self
	 */
	public function useStyle(string $style): self
	{
		return match ($style) {
			'blade' => $this
				->setEchoDelimiters('{{', '}}')
				->setRawDelimiters('{!!', '!!}')
				->setStatementDelimiters('@', '')
				->setCommentDelimiters('{{--', '--}}')
				->enableNAttributes(false),
			'twig' => $this
				->setEchoDelimiters('{{', '}}')
				->setRawDelimiters('{{', '| raw }}')
				->setStatementDelimiters('{%', '%}')
				->setCommentDelimiters('{#', '#}')
				->enableNAttributes(false),
			'latte' => $this
				->setEchoDelimiters('{', '}')
				->setRawDelimiters('{!', '!}')
				->setStatementDelimiters('{', '}')
				->setCommentDelimiters('{*', '*}')
				->enableNAttributes(true)
				->setNAttributePrefix('n:'),
			default => $this
				->setEchoDelimiters('{{', '}}')
				->setRawDelimiters('{!!', '!!}')
				->setStatementDelimiters('{%', '%}')
				->setCommentDelimiters('{#', '#}')
				->enableNAttributes(false),
		};
	}

	/**
	 * Apply a configuration callback to this instance in a fluent chain
	 *
	 * The callback receives the Template instance and can call any setter on it.
	 *
	 * @param Closure $callback Callback that receives the Template instance
	 * @return self
	 */
	public function configure(Closure $callback): self
	{
		$callback($this);
		return $this;
	}

	/**
	 * Override the default HTML escape handler used for echo expressions
	 *
	 * @param Closure $handler Closure that accepts a value and returns the escaped string
	 * @return self
	 */
	public function setEscapeHandler(Closure $handler): self
	{
		$this->escapeHandler = $handler;
		return $this;
	}

	/**
	 * Enable or disable automatic HTML escaping of echo expressions
	 *
	 * When enabled (the default), {{ expr }} output is passed through the escape handler.
	 * When disabled, {{ expr }} behaves like a raw echo.
	 *
	 * @param bool $enabled True to enable auto-escaping, false to disable
	 * @return self
	 */
	public function setAutoEscape(bool $enabled): self
	{
		$this->autoEscape = $enabled;
		return $this;
	}

	/**
	 * Register a custom template directive
	 *
	 * The handler closure receives the expression string inside the directive
	 * tag and should return the compiled PHP snippet.
	 *
	 * @param string  $name    Directive name (used without delimiters in templates)
	 * @param Closure $handler Closure that compiles the directive expression
	 * @return self
	 */
	public function directive(string $name, Closure $handler): self
	{
		$this->directives[$name] = $handler;
		return $this;
	}

	/**
	 * Register a custom template filter
	 *
	 * Filters are applied with the pipe syntax, e.g. {{ value|name }}.
	 *
	 * @param string  $name    Filter name
	 * @param Closure $handler Closure that receives the value (and optional args) and returns the filtered result
	 * @return self
	 */
	public function filter(string $name, Closure $handler): self
	{
		$this->filters[$name] = $handler;
		return $this;
	}

	/**
	 * Share one or more variables across all templates rendered by this instance
	 *
	 * Shared variables are merged with per-render data on every render call.
	 *
	 * @param string|array $key   Variable name, or an associative array of name => value pairs
	 * @param mixed        $value Value to share when $key is a string
	 * @return self
	 */
	public function share(string|array $key, mixed $value = null): self
	{
		if (is_array($key)) {
			$this->shared = array_merge($this->shared, $key);
		} else {
			$this->shared[$key] = $value;
		}
		return $this;
	}

	/**
	 * Assign one or more template variables for the next render call
	 *
	 * @param string|array $key   Variable name, or an associative array of name => value pairs
	 * @param mixed        $value Value to assign when $key is a string
	 * @return self
	 */
	public function assign(string|array $key, mixed $value = null): self
	{
		if (is_array($key)) {
			$this->data = array_merge($this->data, $key);
		} else {
			$this->data[$key] = $value;
		}
		return $this;
	}

	/**
	 * Clear all previously assigned template variables
	 *
	 * Shared variables set via share() are not affected.
	 *
	 * @return self
	 */
	public function clear(): self
	{
		$this->data = [];
		return $this;
	}

	/**
	 * Render a template file and return the resulting HTML string
	 *
	 * Resolves the template path, compiles the source, and evaluates it
	 * with the merged set of shared, assigned, and call-time variables.
	 *
	 * @param string $template Template name or path
	 * @param array  $data     Additional variables available only for this render call
	 * @return string Rendered HTML output
	 * @throws RuntimeException If the template file cannot be read
	 * @throws InvalidArgumentException If the template file cannot be found
	 */
	public function render(string $template, array $data = []): string
	{
		$templatePath = $this->resolveTemplatePath($template);
		$content = file_get_contents($templatePath);
		if ($content === false) {
			throw new RuntimeException(sprintf('Failed to read template: %s', $template));
		}
		return $this->renderString($content, $data, $templatePath);
	}

	/**
	 * Compile and render a raw template string, returning an HTMLObject
	 *
	 * When caching is enabled and a $cacheKey is supplied, the compiled PHP
	 * is written to a cache file that is reused on subsequent calls as long as
	 * the source template has not been modified.
	 *
	 * @param string      $content  Raw template source string
	 * @param array       $data     Variables to expose to the template
	 * @param string|null $cacheKey Optional cache identifier (typically the template file path)
	 * @return HTMLObject Rendered output wrapped in an HTMLObject
	 */
	public function renderString(string $content, array $data = [], ?string $cacheKey = null): HTMLObject
	{
		$compiled = $this->compile($content);
		$mergedData = array_merge($this->shared, $this->data, $data);

		if ($this->cacheEnabled && $cacheKey !== null) {
			$cachePath = $this->getCachePath($cacheKey);
			$templateTime = is_file($cacheKey) ? filemtime($cacheKey) : 0;
			if (!file_exists($cachePath) || ($templateTime && filemtime($cachePath) < $templateTime)) {
				file_put_contents($cachePath, $compiled);
			}

			return new HTMLObject($this->evaluate($cachePath, $mergedData, true));
		}

		return new HTMLObject($this->evaluate($compiled, $mergedData, false));
	}

	/**
	 * Compile a raw template string into executable PHP source
	 *
	 * Processes template features in order: extends, comments, n:attributes
	 * (if enabled), custom directives, macros, blocks, statements, raw echo,
	 * and escaped echo.
	 *
	 * @param string $content Raw template source
	 * @return string Compiled PHP source ready for evaluation or caching
	 */
	public function compile(string $content): string
	{
		$content = $this->compileExtends($content);
		$content = $this->compileComments($content);

		if ($this->nAttributesEnabled) {
			$content = $this->compileNAttributes($content);
		}

		$content = $this->compileCustomDirectives($content);
		$content = $this->compileMacros($content);
		$content = $this->compileBlocks($content);
		$content = $this->compileStatements($content);
		$content = $this->compileRawEcho($content);
		$content = $this->compileEscapedEcho($content);

		return $content;
	}

	/**
	 * Parse an HTML tag starting at the given position in the source string
	 *
	 * Returns an associative array describing the tag, or null if the character
	 * at $pos is not the start of a valid HTML tag. The returned array contains:
	 *   - type         ('open' or 'close')
	 *   - tag          (lowercase tag name)
	 *   - tagOriginal  (original-case tag name; open tags only)
	 *   - attributes   (associative array of attribute name => value; open tags only)
	 *   - attrString   (raw attribute string; open tags only)
	 *   - selfClosing  (bool; open tags only)
	 *   - start        (byte offset of '<')
	 *   - end          (byte offset after the closing '>')
	 *   - full         (full raw tag string)
	 *
	 * @param string $html Source HTML string
	 * @param int    $pos  Byte offset at which to begin parsing
	 * @return array|null Parsed tag data, or null if no valid tag is found
	 */
	private function parseHtmlTag(string $html, int $pos): ?array
	{
		if ($pos >= strlen($html) || $html[$pos] !== '<') {
			return null;
		}

		$start = $pos;
		$pos++;

		if ($pos >= strlen($html)) {
			return null;
		}

		$isClosing = false;
		if ($html[$pos] === '/') {
			$isClosing = true;
			$pos++;
		}

		if (!preg_match('/[a-zA-Z]/', $html[$pos] ?? '')) {
			return null;
		}

		$tagNameStart = $pos;
		while ($pos < strlen($html) && preg_match('/[a-zA-Z0-9]/', $html[$pos])) {
			$pos++;
		}
		$tagName = substr($html, $tagNameStart, $pos - $tagNameStart);

		if ($isClosing) {
			while ($pos < strlen($html) && $html[$pos] !== '>') {
				$pos++;
			}
			if ($pos < strlen($html)) {
				$pos++;
			}
			return [
				'type' => 'close',
				'tag' => strtolower($tagName),
				'start' => $start,
				'end' => $pos,
				'full' => substr($html, $start, $pos - $start),
			];
		}

		$attributes = [];
		$attrString = '';

		while ($pos < strlen($html)) {
			while ($pos < strlen($html) && ctype_space($html[$pos])) {
				$attrString .= $html[$pos];
				$pos++;
			}

			if ($pos >= strlen($html)) {
				break;
			}

			if ($html[$pos] === '>' || ($html[$pos] === '/' && ($pos + 1) < strlen($html) && $html[$pos + 1] === '>')) {
				break;
			}

			$attrNameStart = $pos;
			while ($pos < strlen($html) && preg_match('/[a-zA-Z0-9_:\-]/', $html[$pos])) {
				$pos++;
			}
			$attrName = substr($html, $attrNameStart, $pos - $attrNameStart);

			if ($attrName === '') {
				$pos++;
				continue;
			}

			while ($pos < strlen($html) && ctype_space($html[$pos])) {
				$pos++;
			}

			$attrValue = null;
			if ($pos < strlen($html) && $html[$pos] === '=') {
				$pos++;
				while ($pos < strlen($html) && ctype_space($html[$pos])) {
					$pos++;
				}

				if ($pos < strlen($html)) {
					$quote = $html[$pos];
					if ($quote === '"' || $quote === "'") {
						$pos++;
						$valueStart = $pos;
						while ($pos < strlen($html) && $html[$pos] !== $quote) {
							$pos++;
						}
						$attrValue = substr($html, $valueStart, $pos - $valueStart);
						if ($pos < strlen($html)) {
							$pos++;
						}
					} else {
						$valueStart = $pos;
						while ($pos < strlen($html) && !ctype_space($html[$pos]) && $html[$pos] !== '>' && $html[$pos] !== '/') {
							$pos++;
						}
						$attrValue = substr($html, $valueStart, $pos - $valueStart);
					}
				}
			}

			$attributes[$attrName] = $attrValue;
			$attrString .= ' ' . $attrName . ($attrValue !== null ? '="' . $attrValue . '"' : '');
		}

		$selfClosing = false;
		if ($pos < strlen($html) && $html[$pos] === '/') {
			$selfClosing = true;
			$pos++;
		}

		if ($pos < strlen($html) && $html[$pos] === '>') {
			$pos++;
		}

		$tagNameLower = strtolower($tagName);
		if (in_array($tagNameLower, self::VOID_ELEMENTS, true)) {
			$selfClosing = true;
		}

		return [
			'type' => 'open',
			'tag' => $tagNameLower,
			'tagOriginal' => $tagName,
			'attributes' => $attributes,
			'attrString' => trim($attrString),
			'selfClosing' => $selfClosing,
			'start' => $start,
			'end' => $pos,
			'full' => substr($html, $start, $pos - $start),
		];
	}

	/**
	 * Find the byte offset of the closing tag that matches the given opening tag
	 *
	 * Tracks nesting depth so that nested tags of the same type are handled
	 * correctly. Returns null if no matching closing tag is found.
	 *
	 * @param string $html    Source HTML string
	 * @param int    $pos     Byte offset to begin searching from (after the opening tag)
	 * @param string $tagName Lowercase tag name to match (e.g. 'div')
	 * @return int|null Byte offset of the matching closing tag, or null if not found
	 */
	private function findClosingTag(string $html, int $pos, string $tagName): ?int
	{
		$depth = 1;
		$len = strlen($html);

		while ($pos < $len && $depth > 0) {
			$nextOpen = strpos($html, '<', $pos);
			if ($nextOpen === false) {
				break;
			}

			$parsed = $this->parseHtmlTag($html, $nextOpen);
			if ($parsed === null) {
				$pos = $nextOpen + 1;
				continue;
			}

			if ($parsed['tag'] === $tagName) {
				if ($parsed['type'] === 'open' && !$parsed['selfClosing']) {
					$depth++;
				} elseif ($parsed['type'] === 'close') {
					$depth--;
					if ($depth === 0) {
						return $nextOpen;
					}
				}
			}

			$pos = $parsed['end'];
		}

		return null;
	}

	/**
	 * Walk the HTML source and compile all n:attribute directives found in opening tags
	 *
	 * Iterates character-by-character, detects opening tags that carry one or
	 * more attributes with the configured n: prefix, extracts their inner
	 * content (recursively compiling nested n:attributes), and delegates
	 * compilation to processNAttributeElement().
	 *
	 * @param string $content Raw HTML/template source
	 * @return string Source with all n:attribute directives replaced by PHP code
	 */
	private function compileNAttributes(string $content): string
	{
		$result = '';
		$pos = 0;
		$len = strlen($content);
		$prefix = $this->nAttributePrefix;

		while ($pos < $len) {
			$nextTag = strpos($content, '<', $pos);

			if ($nextTag === false) {
				$result .= substr($content, $pos);
				break;
			}

			$result .= substr($content, $pos, $nextTag - $pos);

			$parsed = $this->parseHtmlTag($content, $nextTag);

			if ($parsed === null || $parsed['type'] !== 'open') {
				if ($parsed !== null) {
					$result .= $parsed['full'];
					$pos = $parsed['end'];
				} else {
					$result .= $content[$nextTag];
					$pos = $nextTag + 1;
				}
				continue;
			}

			$hasNAttr = false;
			foreach ($parsed['attributes'] as $name => $value) {
				if (str_starts_with($name, $prefix)) {
					$hasNAttr = true;
					break;
				}
			}

			if (!$hasNAttr) {
				$result .= $parsed['full'];
				$pos = $parsed['end'];
				continue;
			}

			$innerContent = '';
			$closeTagEnd = $parsed['end'];

			if (!$parsed['selfClosing']) {
				$closeTagStart = $this->findClosingTag($content, $parsed['end'], $parsed['tag']);
				if ($closeTagStart !== null) {
					$innerContent = substr($content, $parsed['end'], $closeTagStart - $parsed['end']);
					$closeParsed = $this->parseHtmlTag($content, $closeTagStart);
					$closeTagEnd = $closeParsed ? $closeParsed['end'] : ($closeTagStart + strlen('</' . $parsed['tag'] . '>'));

					$innerContent = $this->compileNAttributes($innerContent);
				}
			}

			$compiled = $this->processNAttributeElement($parsed, $innerContent);
			$result .= $compiled;
			$pos = $closeTagEnd;
		}

		return $result;
	}

	/**
	 * Compile a single HTML element that carries one or more n:attribute directives
	 *
	 * Separates n:* attributes from regular attributes, then generates the
	 * appropriate PHP wrapper code for control-flow directives (n:if, n:foreach,
	 * n:for, n:while), tag-conditional rendering (n:tag-if), inner-content
	 * wrappers (n:inner-foreach, n:inner-if), dynamic attribute helpers
	 * (n:class, n:attr, n:href, n:src, n:id), and block/snippet markers.
	 *
	 * @param array  $tag          Parsed tag data as returned by parseHtmlTag()
	 * @param string $innerContent Already-compiled inner HTML of the element
	 * @return string Compiled PHP/HTML string for the entire element
	 */
	private function processNAttributeElement(array $tag, string $innerContent): string
	{
		$prefix = $this->nAttributePrefix;
		$nAttrs = [];
		$regularAttrs = [];

		foreach ($tag['attributes'] as $name => $value) {
			if (str_starts_with($name, $prefix)) {
				$nAttrName = substr($name, strlen($prefix));
				$nAttrs[$nAttrName] = $value;
			} else {
				$regularAttrs[$name] = $value;
			}
		}

		$prefixCode = '';
		$suffixCode = '';
		$tagPrefixCode = '';
		$tagSuffixCode = '';
		$innerPrefixCode = '';
		$innerSuffixCode = '';

		$wrapperOrder = ['if', 'ifset', 'foreach', 'for', 'while'];
		foreach ($wrapperOrder as $attrName) {
			if (!isset($nAttrs[$attrName])) {
				continue;
			}

			$handler = $this->nAttributeHandlers[$attrName] ?? null;
			if ($handler && $handler['type'] === 'wrapper') {
				$expr = $nAttrs[$attrName];
				$prefixCode .= '<?php ' . sprintf($handler['open'], $expr) . ' ?>';
				$suffixCode = '<?php ' . $handler['close'] . ' ?>' . $suffixCode;
			}
			unset($nAttrs[$attrName]);
		}

		if (isset($nAttrs['tag-if'])) {
			$expr = $nAttrs['tag-if'];
			$tagPrefixCode = "<?php if ({$expr}): ?>";
			$tagSuffixCode = "<?php endif; ?>";
			unset($nAttrs['tag-if']);
		}

		$innerWrappers = ['inner-foreach', 'inner-if'];
		foreach ($innerWrappers as $attrName) {
			if (!isset($nAttrs[$attrName])) {
				continue;
			}

			$handler = $this->nAttributeHandlers[$attrName] ?? null;
			if ($handler && $handler['type'] === 'inner-wrapper') {
				$expr = $nAttrs[$attrName];
				$innerPrefixCode .= '<?php ' . sprintf($handler['open'], $expr) . ' ?>';
				$innerSuffixCode = '<?php ' . $handler['close'] . ' ?>' . $innerSuffixCode;
			}
			unset($nAttrs[$attrName]);
		}

		foreach ($nAttrs as $attrName => $attrValue) {
			$handler = $this->nAttributeHandlers[$attrName] ?? null;
			if (!$handler) {
				continue;
			}

			switch ($handler['type']) {
				case 'attribute':
					$targetAttr = $handler['attr'];
					if (isset($handler['helper'])) {
						$existingClass = $regularAttrs['class'] ?? '';
						unset($regularAttrs['class']);
						$regularAttrs[$targetAttr] = "<?php echo {$handler['helper']}('{$existingClass}', {$attrValue}); ?>";
					} else {
						$regularAttrs[$targetAttr] = "<?php echo \$__escape({$attrValue}); ?>";
					}
					break;

				case 'dynamic-attrs':
					$regularAttrs['__dynamic__'] = "<?php echo \$__nAttr({$attrValue}); ?>";
					break;

				case 'snippet':
					$snippetName = trim($attrValue, "\"'");
					$regularAttrs['id'] = "snippet-{$snippetName}";
					break;

				case 'block':
					$blockName = trim($attrValue, "\"'");
					$prefixCode .= "<?php \$__startBlock('{$blockName}'); ?>";
					$suffixCode = "<?php \$__endBlock(); ?>" . $suffixCode;
					break;

				case 'custom':
					break;
			}
		}

		$attrString = '';
		$dynamicAttrs = '';
		foreach ($regularAttrs as $name => $value) {
			if ($name === '__dynamic__') {
				$dynamicAttrs = $value;
				continue;
			}

			if ($value === null) {
				$attrString .= ' ' . $name;
			} else {
				$needsQuotes = !str_contains($value, '<?php');
				if ($needsQuotes) {
					$attrString .= ' ' . $name . '="' . $value . '"';
				} else {
					$attrString .= ' ' . $name . '="' . $value . '"';
				}
			}
		}
		$attrString .= $dynamicAttrs;

		$tagName = $tag['tagOriginal'];

		if ($tag['selfClosing']) {
			$tagHtml = "<{$tagName}{$attrString} />";
			if ($tagPrefixCode || $tagSuffixCode) {
				$tagHtml = $tagPrefixCode . $tagHtml . $tagSuffixCode;
			}
			return $prefixCode . $tagHtml . $suffixCode;
		}

		$openTag = $tagPrefixCode . "<{$tagName}{$attrString}>" . $tagSuffixCode;
		$closeTag = $tagPrefixCode . "</{$tagName}>" . $tagSuffixCode;
		$content = $innerPrefixCode . $innerContent . $innerSuffixCode;

		return $prefixCode . $openTag . $content . $closeTag . $suffixCode;
	}

	/**
	 * Compile the template inheritance declaration
	 *
	 * Looks for a {% extends 'parent' %} tag, stores the parent template name
	 * in $this->parentTemplate, and removes the tag from the source so it is
	 * not emitted into the compiled output.
	 *
	 * @param string $content Raw template source
	 * @return string Source with the extends tag removed
	 */
	private function compileExtends(string $content): string
	{
		$pattern = '/{%\s*extends\s+[\'"](.+?)[\'"]\s*%}/';
		if (preg_match($pattern, $content, $matches)) {
			$this->parentTemplate = $matches[1];
			$content = preg_replace($pattern, '', $content);
		}
		return $content;
	}

	/**
	 * Compile block and yield directives for template inheritance
	 *
	 * Converts {% block name %}...{% endblock %} tags into calls to the
	 * runtime $__startBlock / $__endBlock helpers, and converts
	 * {% yield name %} (with an optional default value) into calls to
	 * $__yieldBlock so that child templates can override named regions.
	 *
	 * @param string $content Template source after extends compilation
	 * @return string Source with block/yield directives replaced by PHP calls
	 */
	private function compileBlocks(string $content): string
	{
		$open = preg_quote($this->statementOpen, '/');
		$close = preg_quote($this->statementClose, '/');

		$pattern = "/{$open}\s*block\s+(\w+)\s*{$close}(.*?){$open}\s*endblock\s*{$close}/s";
		$content = preg_replace_callback($pattern, function ($matches) {
			return "<?php \$__startBlock('{$matches[1]}'); ?>{$matches[2]}<?php \$__endBlock(); ?>";
		}, $content);

		$pattern = "/{$open}\s*yield\s+(\w+)(?:\s*,\s*(.+?))?\s*{$close}/s";
		$content = preg_replace_callback($pattern, function ($matches) {
			$default = $matches[2] ?? "''";
			return "<?php echo \$__yieldBlock('{$matches[1]}', {$default}); ?>";
		}, $content);

		return $content;
	}

	/**
	 * Compile macro definition and call directives
	 *
	 * Converts {% macro name(args) %}...{% endmacro %} into a call to the
	 * runtime $__defineMacro helper, and converts {% call name(args) %} into
	 * a call to $__callMacro so that reusable template fragments can be
	 * defined and invoked inline.
	 *
	 * @param string $content Template source
	 * @return string Source with macro directives replaced by PHP calls
	 */
	private function compileMacros(string $content): string
	{
		$open = preg_quote($this->statementOpen, '/');
		$close = preg_quote($this->statementClose, '/');

		$pattern = "/{$open}\s*macro\s+(\w+)\s*\(([^)]*)\)\s*{$close}(.*?){$open}\s*endmacro\s*{$close}/s";
		$content = preg_replace_callback($pattern, function ($matches) {
			return "<?php \$__defineMacro('{$matches[1]}', function({$matches[2]}) use (\$__escape, \$__vars) { extract(\$__vars); ?>{$matches[3]}<?php }); ?>";
		}, $content);

		$pattern = "/{$open}\s*call\s+(\w+)\s*\(([^)]*)\)\s*{$close}/s";
		$content = preg_replace_callback($pattern, function ($matches) {
			return "<?php echo \$__callMacro('{$matches[1]}', [{$matches[2]}]); ?>";
		}, $content);

		return $content;
	}

	/**
	 * Strip all template comments from the source
	 *
	 * Removes everything between the configured comment delimiters
	 * (e.g. {# ... #}) so that comments are never emitted to the browser.
	 *
	 * @param string $content Raw template source
	 * @return string Source with all comment blocks removed
	 */
	private function compileComments(string $content): string
	{
		$pattern = sprintf('/%s.*?%s/s', preg_quote($this->commentOpen, '/'), preg_quote($this->commentClose, '/'));
		return preg_replace($pattern, '', $content);
	}

	/**
	 * Compile escaped echo expressions (e.g. {{ expr }}) into PHP echo statements
	 *
	 * Expressions that begin with a control-flow keyword are left untouched.
	 * Filter pipes within the expression are compiled first. When auto-escape
	 * is enabled the value is wrapped with the escape handler; otherwise it
	 * is echoed directly.
	 *
	 * @param string $content Template source
	 * @return string Source with escaped echo tags replaced by PHP echo statements
	 */
	private function compileEscapedEcho(string $content): string
	{
		$open = preg_quote($this->echoOpen, '/');
		$close = preg_quote($this->echoClose, '/');

		return preg_replace_callback("/{$open}\s*(.+?)\s*{$close}/s", function ($matches) {
			$expr = trim($matches[1]);
			if (preg_match('/^(if|foreach|for|while|else|end|block|macro|include|extends)\b/', $expr)) {
				return $matches[0];
			}
			$expr = $this->compileFilters($expr);
			return $this->autoEscape
				? "<?php echo \$__escape({$expr}); ?>"
				: "<?php echo {$expr}; ?>";
		}, $content);
	}

	/**
	 * Compile raw (unescaped) echo expressions (e.g. {!! expr !!}) into PHP echo statements
	 *
	 * The expression is passed through filter compilation but is never wrapped
	 * with the escape handler, allowing trusted HTML to be output as-is.
	 *
	 * @param string $content Template source
	 * @return string Source with raw echo tags replaced by unescaped PHP echo statements
	 */
	private function compileRawEcho(string $content): string
	{
		$open = preg_quote($this->rawOpen, '/');
		$close = preg_quote($this->rawClose, '/');

		return preg_replace_callback("/{$open}\s*(.+?)\s*{$close}/s", function ($matches) {
			$expr = $this->compileFilters(trim($matches[1]));
			return "<?php echo {$expr}; ?>";
		}, $content);
	}

	/**
	 * Compile pipe-separated filter chains within a template expression
	 *
	 * Parses the expression for pipe characters and converts each filter token
	 * into a nested call to the runtime $__filter helper, forwarding any
	 * parenthesised arguments. Returns the expression unchanged when no pipes
	 * are present.
	 *
	 * @param string $expr Raw expression string (e.g. 'name|upper|truncate(20)')
	 * @return string PHP expression string with filters compiled to $__filter() calls
	 */
	private function compileFilters(string $expr): string
	{
		if (!str_contains($expr, '|')) {
			return $expr;
		}

		$parts = preg_split('/\s*\|\s*/', $expr);
		$value = array_shift($parts);

		foreach ($parts as $filter) {
			if (preg_match('/^(\w+)(?:\((.+)\))?$/', $filter, $m)) {
				$args = isset($m[2]) ? ', ' . $m[2] : '';
				$value = "\$__filter('{$m[1]}', {$value}{$args})";
			}
		}

		return $value;
	}

	/**
	 * Compile control-flow statement tags into PHP code
	 *
	 * Handles if / elseif / else / endif, foreach / endforeach, for / endfor,
	 * while / endwhile, include, set, and dump directives delimited by the
	 * configured statement delimiters. When the closing delimiter is empty
	 * the source is delegated to compileBladeStyleStatements() instead.
	 *
	 * @param string $content Template source
	 * @return string Source with statement tags replaced by PHP control structures
	 */
	private function compileStatements(string $content): string
	{
		if ($this->statementClose === '') {
			return $this->compileBladeStyleStatements($content);
		}

		$open = preg_quote($this->statementOpen, '/');
		$close = preg_quote($this->statementClose, '/');

		$statements = [
			'if' => fn($e) => "<?php if ({$e}): ?>",
			'elseif' => fn($e) => "<?php elseif ({$e}): ?>",
			'else' => fn() => '<?php else: ?>',
			'endif' => fn() => '<?php endif; ?>',
			'foreach' => fn($e) => "<?php foreach ({$e}): ?>",
			'endforeach' => fn() => '<?php endforeach; ?>',
			'for' => fn($e) => "<?php for ({$e}): ?>",
			'endfor' => fn() => '<?php endfor; ?>',
			'while' => fn($e) => "<?php while ({$e}): ?>",
			'endwhile' => fn() => '<?php endwhile; ?>',
			'include' => fn($e) => "<?php echo \$__include({$e}); ?>",
			'set' => fn($e) => "<?php {$e}; ?>",
			'dump' => fn($e) => "<?php var_dump({$e}); ?>",
		];

		foreach ($statements as $keyword => $compiler) {
			$pattern = "/{$open}\s*{$keyword}(?:\s*\((.+?)\)|\s+(.+?))?\s*{$close}/s";
			$content = preg_replace_callback($pattern, function ($matches) use ($compiler) {
				$expr = $matches[1] ?? ($matches[2] ?? '');
				return call_user_func($compiler, trim($expr));
			}, $content);
		}

		return $content;
	}

	/**
	 * Compile Blade-style @ directives into PHP control structures
	 *
	 * Handles the same core control-flow keywords as compileStatements() plus
	 * Blade-specific extensions: @isset / @endisset and @unless / @endunless.
	 * Used automatically when the statement close delimiter is an empty string.
	 *
	 * @param string $content Template source using @keyword(...) syntax
	 * @return string Source with @ directives replaced by PHP control structures
	 */
	private function compileBladeStyleStatements(string $content): string
	{
		$statements = [
			'if' => fn($e) => "<?php if ({$e}): ?>",
			'elseif' => fn($e) => "<?php elseif ({$e}): ?>",
			'else' => fn() => '<?php else: ?>',
			'endif' => fn() => '<?php endif; ?>',
			'foreach' => fn($e) => "<?php foreach ({$e}): ?>",
			'endforeach' => fn() => '<?php endforeach; ?>',
			'for' => fn($e) => "<?php for ({$e}): ?>",
			'endfor' => fn() => '<?php endfor; ?>',
			'while' => fn($e) => "<?php while ({$e}): ?>",
			'endwhile' => fn() => '<?php endwhile; ?>',
			'include' => fn($e) => "<?php echo \$__include({$e}); ?>",
			'isset' => fn($e) => "<?php if (isset({$e})): ?>",
			'endisset' => fn() => '<?php endif; ?>',
			'unless' => fn($e) => "<?php if (!({$e})): ?>",
			'endunless' => fn() => '<?php endif; ?>',
		];

		foreach ($statements as $keyword => $compiler) {
			$pattern = '/@' . $keyword . '(?:\s*\((.+?)\))?/s';
			$content = preg_replace_callback($pattern, function ($matches) use ($compiler) {
				return call_user_func($compiler, trim($matches[1] ?? ""));
			}, $content);
		}

		return $content;
	}

	/**
	 * Compile all user-registered custom directives into PHP snippets
	 *
	 * Iterates over the directives registered via directive() and replaces each
	 * occurrence in the template source by invoking the associated handler
	 * closure with the directive's expression string. Both Blade-style (@name)
	 * and delimiter-style ({%name%}) patterns are supported depending on the
	 * configured statement close delimiter.
	 *
	 * @param string $content Template source
	 * @return string Source with custom directive tags replaced by their compiled output
	 */
	private function compileCustomDirectives(string $content): string
	{
		foreach ($this->directives as $name => $handler) {
			if ($this->statementClose === '') {
				$pattern = '/@' . preg_quote($name, '/') . '(?:\s*\((.+?)\))?/s';
			} else {
				$open = preg_quote($this->statementOpen, '/');
				$close = preg_quote($this->statementClose, '/');
				$pattern = "/{$open}\s*" . preg_quote($name, '/') . "(?:\s*\((.+?)\)|\s+(.+?))?\s*{$close}/s";
			}

			$content = preg_replace_callback($pattern, function ($matches) use ($handler) {
				return call_user_func($handler, trim($matches[1] ?? ($matches[2] ?? '')));
			}, $content);
		}

		return $content;
	}

	/**
	 * Evaluate compiled PHP template source and return the rendered output string
	 *
	 * Sets up all runtime helpers ($__escape, $__filter, $__include, $__nClass,
	 * $__nAttr, $__startBlock, $__endBlock, $__yieldBlock, $__defineMacro,
	 * $__callMacro), extracts template variables into the local scope, then
	 * either includes a compiled cache file ($isFile = true) or evals the
	 * compiled string directly. After rendering, if a parent template was
	 * declared via extends, it is rendered recursively with the captured blocks.
	 *
	 * @param string $compiled  Compiled PHP source string, or path to a compiled cache file
	 * @param array  $data      Merged template variables
	 * @param bool   $isFile    True when $compiled is a file path, false when it is a PHP string
	 * @return string Rendered HTML output
	 * @throws RuntimeException If an error occurs during template execution
	 */
	private function evaluate(string $compiled, array $data, bool $isFile): string
	{
		$__escape = $this->escapeHandler;
		$__vars = $data;
		$__filters = $this->filters;
		$__macros = [];
		$__blocks = &$this->blocks;
		$__blockStack = &$this->blockStack;

		$__filter = function (string $name, mixed $value, mixed ...$args) use ($__filters) {
			if (!isset($__filters[$name])) {
				throw new RuntimeException(sprintf('Unknown filter: %s', $name));
			}
			return $__filters[$name]($value, ...$args);
		};

		$__include = fn($template, $includeData = []) => $this->render($template, array_merge($data, $includeData));

		$__nClass = function (string $existing, array|string $classes) use ($__escape) {
			$result = $existing ? [$existing] : [];
			if (is_string($classes)) {
				$result[] = $classes;
			} else {
				foreach ($classes as $class => $condition) {
					if (is_numeric($class)) {
						$result[] = $condition;
					} elseif ($condition) {
						$result[] = $class;
					}
				}
			}
			return $__escape(implode(' ', $result));
		};

		$__nAttr = function (array $attrs) use ($__escape) {
			$result = '';
			foreach ($attrs as $name => $value) {
				if ($value === true) {
					$result .= " {$name}";
				} elseif ($value !== false && $value !== null) {
					$result .= " {$name}=\"" . $__escape($value) . "\"";
				}
			}
			return $result;
		};

		$__startBlock = function (string $name) use (&$__blockStack) {
			$__blockStack[] = $name;
			ob_start();
		};

		$__endBlock = function () use (&$__blocks, &$__blockStack) {
			$name = array_pop($__blockStack);
			$content = ob_get_clean();
			if (!isset($__blocks[$name])) {
				$__blocks[$name] = $content;
			}
		};

		$__yieldBlock = fn(string $name, string $default = '') => $__blocks[$name] ?? $default;

		$__defineMacro = function (string $name, Closure $macro) use (&$__macros) {
			$__macros[$name] = $macro;
		};

		$__callMacro = function (string $name, array $args = []) use (&$__macros) {
			if (!isset($__macros[$name])) {
				throw new RuntimeException(sprintf('Unknown macro: %s', $name));
			}
			ob_start();
			$__macros[$name](...$args);
			return ob_get_clean();
		};

		extract($data, EXTR_SKIP);

		ob_start();
		try {
			if ($isFile) {
				include $compiled;
			} else {
				eval ('?>' . $compiled);
			}
		} catch (\Throwable $e) {
			ob_end_clean();
			throw new RuntimeException(sprintf('Template execution error: %s', $e->getMessage()), 0, $e);
		}

		$output = ob_get_clean();

		if ($this->parentTemplate !== null) {
			$parent = $this->parentTemplate;
			$this->parentTemplate = null;
			$output = $this->render($parent, $data);
		}

		return $output;
	}

	/**
	 * Resolve a template name or relative path to an absolute file path
	 *
	 * First checks whether $template is already a readable absolute path.
	 * If not, looks inside the configured template directory, trying the name
	 * as-is and then appending common extensions (.html, .tpl, .php, .latte,
	 * .phtml) until a readable file is found.
	 *
	 * @param string $template Template name or path to resolve
	 * @return string Absolute path to the template file
	 * @throws InvalidArgumentException If no matching template file can be found
	 */
	private function resolveTemplatePath(string $template): string
	{
		if (is_file($template) && is_readable($template)) {
			return $template;
		}

		if (!empty($this->templateDir)) {
			$path = $this->templateDir . '/' . $template;
			if (is_file($path) && is_readable($path)) {
				return $path;
			}

			foreach (['.html', '.tpl', '.php', '.latte', '.phtml'] as $ext) {
				$fullPath = $path . $ext;
				if (is_file($fullPath) && is_readable($fullPath)) {
					return $fullPath;
				}
			}
		}

		throw new InvalidArgumentException(sprintf('Template not found: %s', $template));
	}

	/**
	 * Build the cache file path for a given template file path
	 *
	 * The cache file name is derived from an MD5 hash of the template path,
	 * ensuring uniqueness while avoiding filesystem-unsafe characters.
	 *
	 * @param string $templatePath Absolute path to the original template file
	 * @return string Absolute path to the corresponding compiled cache file
	 */
	private function getCachePath(string $templatePath): string
	{
		return $this->cacheDir . '/' . md5($templatePath) . '.php';
	}

	/**
	 * Check whether a given template can be resolved to a readable file
	 *
	 * @param string $template Template name or path
	 * @return bool True if the template exists and is readable, false otherwise
	 */
	public function exists(string $template): bool
	{
		try {
			$this->resolveTemplatePath($template);
			return true;
		} catch (InvalidArgumentException) {
			return false;
		}
	}

	/**
	 * Return the compiled PHP source for a template file without evaluating it
	 *
	 * Useful for inspecting or caching the compiled output externally.
	 *
	 * @param string $template Template name or path
	 * @return string Compiled PHP source string
	 * @throws InvalidArgumentException If the template file cannot be found
	 */
	public function getCompiled(string $template): string
	{
		$templatePath = $this->resolveTemplatePath($template);
		return $this->compile(file_get_contents($templatePath));
	}

	/**
	 * Delete all compiled cache files from the configured cache directory
	 *
	 * Does nothing when no cache directory has been set or the directory does
	 * not exist.
	 *
	 * @return self
	 */
	public function clearCache(): self
	{
		if (empty($this->cacheDir) || !is_dir($this->cacheDir)) {
			return $this;
		}

		foreach (glob($this->cacheDir . '/*.php') as $file) {
			if (is_file($file)) {
				unlink($file);
			}
		}
		return $this;
	}

	/**
	 * Return the names of all currently registered filters
	 *
	 * @return string[] List of filter names available for use in templates
	 */
	public function getFilters(): array
	{
		return array_keys($this->filters);
	}

	/**
	 * Return the names of all currently registered n:attribute handlers
	 *
	 * @return string[] List of n:attribute handler names available for use in templates
	 */
	public function getNAttributeHandlers(): array
	{
		return array_keys($this->nAttributeHandlers);
	}
}
