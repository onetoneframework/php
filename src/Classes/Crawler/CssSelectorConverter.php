<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Cralwer;
use function addslashes;
use function array_filter;
use function array_map;
use function array_values;
use function preg_match;
use function sprintf;
use function strtolower;
use function trim;
use function strlen;
use function in_array;

/**
 * Translates a (subset of) CSS selector syntax into an XPath 1.0 expression.
 */
final class CssSelectorConverter
{
    /**
     * Convert a CSS selector string to an XPath 1.0 expression.
     *
     * @param string $css The CSS selector to convert.
     * @param bool $scopedToCurrentNode  When `true` the XPath is prefixed with `.`
     *                                    so it is evaluated relative to a context node.
     */
    public static function toXPath(string $css, bool $scopedToCurrentNode = false): string
    {
        // Handle comma-separated selector groups
        $parts = array_map('trim', explode(',', $css));

        $xpathParts = array_map(
            static fn(string $s) => self::convertSingle($s, $scopedToCurrentNode),
            $parts
        );

        return implode(' | ', $xpathParts);
    }

    /**
     * Convert a single CSS selector (no commas) to XPath, with combinator handling.
     *
     * @param string $css The CSS selector segment to convert.
     * @param bool $scoped Whether to prefix the XPath with '.' for relative evaluation.
     * @return string The XPath expression corresponding to the given CSS selector segment.
     */
    private static function convertSingle(string $css, bool $scoped): string
    {
        $css = trim($css);
        $prefix = $scoped ? '.' : '';

        // Split on combinators: ' ', '>', '+', '~'
        // We walk token by token so we can track the combinator between segments.
        $tokens = self::tokenize($css);
        $xpathParts = [];
        $combinator = 'descendant'; // first segment is always a descendant from root

        foreach ($tokens as $token) {
            if ($token === '>') {
                $combinator = 'child';
                continue;
            }
            if ($token === '+') {
                $combinator = 'adjacent';
                continue;
            }
            if ($token === '~') {
                $combinator = 'sibling';
                continue;
            }

            $segment = self::segmentToXPath($token);

            $xpathParts[] = match ($combinator) {
                'child' => '/' . $segment,
                'adjacent' => '/following-sibling::*[1][self::' . $segment . ']',
                'sibling' => '/following-sibling::' . $segment,
                default => '//' . $segment, // descendant
            };

            $combinator = 'descendant';
        }

        return $prefix . implode('', $xpathParts);
    }

    /**
     * Split a single CSS selector (no commas) into tokens, preserving combinators.
     *
     * @param string $css The CSS selector to tokenize.
     * @return string[]
     */
    private static function tokenize(string $css): array
    {
        $tokens = [];
        $current = '';
        $len = strlen($css);

        for ($i = 0; $i < $len; $i++) {
            $ch = $css[$i];

            // Inside brackets [attr=…] – pass through verbatim
            if ($ch === '[') {
                $end = strpos($css, ']', $i);
                if ($end === false) {
                    $current .= substr($css, $i);
                    $i = $len;
                } else {
                    $current .= substr($css, $i, $end - $i + 1);
                    $i = $end;
                }
                continue;
            }

            // Inside :not(…) – pass through verbatim
            if ($ch === '(') {
                $end = strpos($css, ')', $i);
                if ($end === false) {
                    $current .= substr($css, $i);
                    $i = $len;
                } else {
                    $current .= substr($css, $i, $end - $i + 1);
                    $i = $end;
                }
                continue;
            }

            if ($ch === '>') {
                if ($current !== '') {
                    $tokens[] = trim($current);
                    $current = '';
                }
                $tokens[] = '>';
                continue;
            }
            if ($ch === '+') {
                if ($current !== '') {
                    $tokens[] = trim($current);
                    $current = '';
                }
                $tokens[] = '+';
                continue;
            }
            if ($ch === '~') {
                if ($current !== '') {
                    $tokens[] = trim($current);
                    $current = '';
                }
                $tokens[] = '~';
                continue;
            }

            if ($ch === ' ') {
                if ($current !== '') {
                    $tokens[] = trim($current);
                    $current = '';
                }
                // Skip combinators that are already explicit
                while ($i + 1 < $len && $css[$i + 1] === ' ') {
                    $i++;
                }
                if ($i + 1 < $len && !in_array($css[$i + 1], ['>', '+', '~'], true)) {
                    $tokens[] = ' '; // descendant combinator
                }
                continue;
            }

            $current .= $ch;
        }

        if ($current !== '') {
            $tokens[] = trim($current);
        }

        // Remove bare space tokens – they indicate descendant, tracked via combinator state
        return array_values(array_filter($tokens, static fn(string $t) => $t !== '' && $t !== ' '));
    }

    /**
     * Translate a single simple selector (no combinator) to an XPath node-test + predicate string.
     *
     * e.g. `div.foo#bar[data-x^="y"]:first-child`  →  `div[contains(…)][…]`
     * 
     * @param string $segment The CSS selector segment to convert (e.g. `div.foo#bar[data-x^="y"]:first-child`).
     * @return string The XPath node-test and predicates corresponding to the given CSS selector segment (e.g. `div[contains(…)][…]`).
     */
    private static function segmentToXPath(string $segment): string
    {
        $tag = '*';
        if (preg_match('/^([a-zA-Z][a-zA-Z0-9\-]*)/', $segment, $m)) {
            $tag = strtolower($m[1]);
            $segment = substr($segment, strlen($m[1]));
        }

        $predicates = [];

        while (preg_match('/^#([a-zA-Z0-9_\-]+)/', $segment, $m)) {
            $predicates[] = sprintf('@id="%s"', $m[1]);
            $segment = substr($segment, strlen($m[0]));
        }

        while (preg_match('/^\.([a-zA-Z0-9_\-]+)/', $segment, $m)) {
            $predicates[] = sprintf(
                'contains(concat(" ", normalize-space(@class), " "), " %s ")',
                $m[1]
            );
            $segment = substr($segment, strlen($m[0]));
        }

        while (preg_match('/^\[([a-zA-Z0-9_\-:]+)([~|^$*]?=)?("([^"]*?)"|\'([^\']*?)\'|([^\]]*?))?\]/', $segment, $m)) {
            $attr = $m[1];
            $op = $m[2] ?? '';
            $val = $m[4] ?? $m[5] ?? $m[6] ?? null;

            if ($op === '' && $val === null) {
                $predicates[] = sprintf('@%s', $attr);
            } elseif ($op === '=') {
                $predicates[] = sprintf('@%s="%s"', $attr, $val);
            } elseif ($op === '^=') {
                $predicates[] = sprintf('starts-with(@%s, "%s")', $attr, $val);
            } elseif ($op === '$=') {
                $predicates[] = sprintf('substring(@%s, string-length(@%s) - %d) = "%s"', $attr, $attr, strlen($val) - 1, $val);
            } elseif ($op === '*=') {
                $predicates[] = sprintf('contains(@%s, "%s")', $attr, $val);
            } elseif ($op === '~=') {
                $predicates[] = sprintf('contains(concat(" ", normalize-space(@%s), " "), " %s ")', $attr, $val);
            } elseif ($op === '|=') {
                $predicates[] = sprintf('@%s="%s" or starts-with(@%s, "%s-")', $attr, $val, $attr, $val);
            }

            $segment = substr($segment, strlen($m[0]));
        }

        while (preg_match('/^:([a-z\-]+)(\(([^)]*)\))?/', $segment, $m)) {
            $pseudo = $m[1];
            $arg = $m[3] ?? null;

            $predicates[] = match ($pseudo) {
                'first-child' => 'position() = 1',
                'last-child' => 'position() = last()',
                'only-child' => 'not(preceding-sibling::*) and not(following-sibling::*)',
                'nth-child' => self::nthExpressionXPath($arg ?? '1', 'position()'),
                'nth-last-child' => self::nthExpressionXPath($arg ?? '1', '(last() - position() + 1)'),
                'first-of-type' => $tag === '*'
                ? 'not(preceding-sibling::*)'
                : sprintf('not(preceding-sibling::%s)', $tag),
                'last-of-type' => $tag === '*'
                ? 'not(following-sibling::*)'
                : sprintf('not(following-sibling::%s)', $tag),
                'only-of-type' => $tag === '*'
                ? 'not(preceding-sibling::* or following-sibling::*)'
                : sprintf('not(preceding-sibling::%s) and not(following-sibling::%s)', $tag, $tag),
                'nth-of-type' => self::nthOfTypeXPath($arg ?? '1', $tag),
                'nth-last-of-type' => self::nthLastOfTypeXPath($arg ?? '1', $tag),
                'empty' => 'not(node())',
                'not' => 'not(' . self::segmentToXPath($arg ?? '*') . ')',
                'contains' => sprintf('contains(., "%s")', addslashes($arg ?? '')),
                'checked' => '@checked',
                'disabled' => '@disabled',
                'enabled' => 'not(@disabled)',
                'selected' => '@selected',
                'required' => '@required',
                'optional' => 'not(@required)',
                'hidden' => '@type="hidden"',
                default => 'true()',
            };

            $segment = substr($segment, strlen($m[0]));
        }

        return $tag . ($predicates !== [] ? '[' . implode('][', $predicates) . ']' : '');
    }

    /**
     * Convert an `:nth-child(…)` argument (`odd`, `even`, `3`, `2n+1`, …) to
     * an XPath `position()` expression.
     * 
     * @param string $arg The argument to `:nth-child(…)` (e.g. `odd`, `even`, `3`, `2n+1`).
     * @param string $posExpr The XPath expression representing the position to compare against (e.g. `position()`, `(last() - position() + 1)`, or a more complex expression for `:nth-of-type`).
     * @return string An XPath expression that evaluates to true for elements matching the given `:nth-child(…)` argument (e.g. `position() mod 2 = 1` for `odd`, `position() = 3` for `3`, `(position() - 1) mod 2 = 0 and position() >= 1` for `2n+1`, etc.).
     */
    private static function nthExpressionXPath(string $arg, string $posExpr): string
    {
        $arg = trim(strtolower($arg));

        if ($arg === 'odd') {
            return sprintf('%s mod 2 = 1', $posExpr);
        }
        if ($arg === 'even') {
            return sprintf('%s mod 2 = 0', $posExpr);
        }
        if (preg_match('/^(\d+)$/', $arg, $m)) {
            return sprintf('%s = %d', $posExpr, (int) $m[1]);
        }
        if (preg_match('/^(-?\d*)n([+\-]\d+)?$/', $arg, $m)) {
            $a = $m[1] === '' || $m[1] === '-' ? ($m[1] === '-' ? -1 : 1) : (int) $m[1];
            $b = isset($m[2]) ? (int) $m[2] : 0;

            if ($a === 0) {
                return sprintf('%s = %d', $posExpr, $b);
            }

            return sprintf('(%s - %d) mod %d = 0 and %s >= %d', $posExpr, $b, $a, $posExpr, $b);
        }

        return 'true()';
    }

    /**
     * Convert an `:nth-child(…)` argument to an XPath expression.
     * 
     * @param string $arg The argument to `:nth-child(…)` (e.g. `odd`, `even`, `3`, `2n+1`).
     * @return string An XPath expression that evaluates to true for elements matching the given `:nth-child(…)` argument.
     */
    private static function nthChildXPath(string $arg): string
    {
        return self::nthExpressionXPath($arg, 'position()');
    }

    /**
     * Convert an `:nth-of-type(…)` argument to an XPath expression.
     * 
     * @param string $arg The argument to `:nth-of-type(…)` (e.g. `odd`, `even`, `3`, `2n+1`).
     * @param string $tag The tag name of the elements being evaluated (e.g. `div`, `*`).
     * @return string An XPath expression that evaluates to true for elements matching the given `:nth-of-type(…)` argument.
     */
    private static function nthOfTypeXPath(string $arg, string $tag): string
    {
        $sibling = $tag === '*' ? 'preceding-sibling::*' : sprintf('preceding-sibling::%s', $tag);
        return self::nthExpressionXPath($arg, sprintf('(count(%s) + 1)', $sibling));
    }

    /**
     * Convert an `:nth-last-child(…)` argument to an XPath expression.
     * 
     * @param string $arg The argument to `:nth-last-child(…)` (e.g. `odd`, `even`, `3`, `2n+1`).
     * @return string An XPath expression that evaluates to true for elements matching the given `:nth-last-child(…)` argument.
     */
    private static function nthLastOfTypeXPath(string $arg, string $tag): string
    {
        $sibling = $tag === '*' ? 'following-sibling::*' : sprintf('following-sibling::%s', $tag);
        return self::nthExpressionXPath($arg, sprintf('(count(%s) + 1)', $sibling));
    }
}
