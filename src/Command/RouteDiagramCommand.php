<?php

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

declare(strict_types=1);

use Clover\Classes\Data\ArrayObject;
use Clover\Classes\Routing\Router;
use Clover\Implement\CommandInterface;
use Clover\Classes\CLI\Input;

class RouteDiagramCommand implements CommandInterface
{
    private const CONTROL_FLOW = [
        T_IF => 'if',
        T_ELSEIF => 'elseif',
        T_ELSE => 'else',
        T_SWITCH => 'switch',
        T_FOR => 'for',
        T_FOREACH => 'foreach',
        T_WHILE => 'while',
        T_DO => 'do',
        T_TRY => 'try',
        T_CATCH => 'catch',
        T_FINALLY => 'finally',
        T_RETURN => 'return',
        T_THROW => 'throw',
        T_CASE => 'case',
        T_DEFAULT => 'default',
    ];

    private array $tokens = [];
    private int $pos = 0;

    public function getName(): string
    {
        return "route:diagram";
    }
    public function getDescription(): string
    {
        return "Display controller function flow diagram";
    }
    public function configure(): void
    {
    }

    public function run(Input $input): bool
    {
        $router = new Router();
        $router->fromDirectory(sprintf("%s/App/Controller", BASE_PATH));
        $routeMap = $router->map();

        foreach ($this->groupRoutesByClass($routeMap) as $class => $routes) {
            $file = $this->resolveClassFile($class);
            if (!$file)
                continue;

            $allTokens = token_get_all(file_get_contents($file));
            echo "\033[1;36m{$class}\033[0m\n";

            foreach ($routes as $route) {
                $httpMethod = strtoupper((string) ($route['method'] ?? ''));
                $pattern = (string) ($route['pattern'] ?? '');
                $caller = (string) ($route['caller'] ?? '');

                echo sprintf("  \033[33m[%s]\033[0m %s → %s()\n", $httpMethod, $pattern, $caller);

                $body = $this->extractMethodBody($allTokens, $caller);
                $this->tokens = $body;
                $this->pos = 0;
                $nodes = $this->parseBlock();
                $this->renderNodes($nodes, "      ");
            }

            echo "\n";
        }

        return true;
    }

    private function groupRoutesByClass(ArrayObject|array $routeMap): array
    {
        $grouped = [];
        foreach ($routeMap as $route) {
            $class = $route['class'] ?? '';
            if ($class)
                $grouped[$class][] = $route;
        }
        return $grouped;
    }

    private function resolveClassFile(string $class): ?string
    {
        try {
            return (new \ReflectionClass($class))->getFileName() ?: null;
        } catch (\ReflectionException) {
            return null;
        }
    }

    private function extractMethodBody(array $tokens, string $methodName): array
    {
        $count = count($tokens);

        for ($i = 0; $i < $count; $i++) {
            if (!is_array($tokens[$i]) || $tokens[$i][0] !== T_FUNCTION)
                continue;

            $j = $i + 1;
            $this->skipWs($tokens, $j);

            if (!is_array($tokens[$j]) || $tokens[$j][0] !== T_STRING || $tokens[$j][1] !== $methodName)
                continue;

            while ($j < $count && !(is_string($tokens[$j]) && $tokens[$j] === '{'))
                $j++;

            return $this->sliceBraceBlock($tokens, $j);
        }

        return [];
    }

    private function sliceBraceBlock(array $tokens, int $pos): array
    {
        $count = count($tokens);
        $depth = 0;
        $result = [];

        while ($pos < $count) {
            $t = $tokens[$pos];
            $ch = is_string($t) ? $t : null;

            if ($ch === '{') {
                $depth++;
                $pos++;
                if ($depth === 1)
                    continue;
                $result[] = $t;
            } elseif ($ch === '}') {
                $depth--;
                if ($depth === 0)
                    break;
                $result[] = $t;
                $pos++;
            } else {
                $result[] = $t;
                $pos++;
            }
        }

        return $result;
    }

    private function skipWs(array $tokens, int &$pos): void
    {
        $count = count($tokens);
        while ($pos < $count && is_array($tokens[$pos]) && $tokens[$pos][0] === T_WHITESPACE) {
            $pos++;
        }
    }

    private function parseBlock(): array
    {
        $nodes = [];
        $count = count($this->tokens);

        while ($this->pos < $count) {
            $t = $this->tokens[$this->pos];

            if (!is_array($t)) {
                $this->pos++;
                continue;
            }

            $type = $t[0];

            if (in_array($type, [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT])) {
                $this->pos++;
                continue;
            }

            if (isset(self::CONTROL_FLOW[$type])) {
                $node = $this->parseControlNode($type);
                if ($node !== null)
                    $nodes[] = $node;
                continue;
            }

            $call = $this->tryExtractCall();
            if ($call !== null) {
                $nodes[] = $call;
                continue;
            }

            $this->pos++;
        }

        return $nodes;
    }

    private function parseControlNode(int $type): ?array
    {
        $keyword = self::CONTROL_FLOW[$type];
        $this->pos++;

        switch ($type) {
            case T_IF:
            case T_ELSEIF:
            case T_FOR:
            case T_FOREACH:
            case T_WHILE:
                $cond = $this->consumeParenthesized();
                $this->advanceWhitespace();
                $children = $this->consumeBodyBlock();
                return ['label' => "{$keyword} ({$cond})", 'children' => $children];

            case T_SWITCH:
                $cond = $this->consumeParenthesized();
                $this->advanceWhitespace();
                $children = $this->consumeBodyBlock();
                return ['label' => "switch ({$cond})", 'children' => $children];

            case T_ELSE:
                $this->advanceWhitespace();
                if ($this->currentType() === T_IF) {
                    $this->pos++;
                    $cond = $this->consumeParenthesized();
                    $this->advanceWhitespace();
                    $children = $this->consumeBodyBlock();
                    return ['label' => "else if ({$cond})", 'children' => $children];
                }
                $children = $this->consumeBodyBlock();
                return ['label' => 'else', 'children' => $children];

            case T_DO:
                $this->advanceWhitespace();
                $children = $this->consumeBodyBlock();
                return ['label' => 'do...while', 'children' => $children];

            case T_TRY:
                $this->advanceWhitespace();
                $children = $this->consumeBodyBlock();
                return ['label' => 'try', 'children' => $children];

            case T_CATCH:
                $cond = $this->consumeParenthesized();
                $this->advanceWhitespace();
                $children = $this->consumeBodyBlock();
                return ['label' => "catch ({$cond})", 'children' => $children];

            case T_FINALLY:
                $this->advanceWhitespace();
                $children = $this->consumeBodyBlock();
                return ['label' => 'finally', 'children' => $children];

            case T_RETURN:
            case T_THROW:
                $expr = $this->consumeToSemicolon();
                return ['label' => "{$keyword} {$expr}", 'children' => []];

            case T_CASE:
                $val = $this->consumeToColon();
                return ['label' => "case {$val}", 'children' => []];

            case T_DEFAULT:
                return ['label' => 'default:', 'children' => []];
        }

        return null;
    }

    private function tryExtractCall(): ?array
    {
        $t = $this->tokens[$this->pos] ?? null;
        if (!is_array($t))
            return null;

        $type = $t[0];

        if ($type === T_VARIABLE) {
            return $this->tryExtractMethodCall();
        }

        if ($type === T_STRING) {
            $toks = $this->tokens;
            $j = $this->pos + 1;
            $this->skipWs($toks, $j);

            if ($j < count($toks) && is_array($toks[$j]) && $toks[$j][0] === T_DOUBLE_COLON) {
                return $this->tryExtractStaticCall();
            }

            if ($j < count($toks) && is_string($toks[$j]) && $toks[$j] === '(') {
                $name = $t[1];
                $this->pos = $j;
                $this->consumeSemicolon();
                return ['label' => "{$name}()", 'children' => []];
            }
        }

        return null;
    }

    private function tryExtractMethodCall(): ?array
    {
        $toks = $this->tokens;
        $j = $this->pos;
        $label = is_array($toks[$j]) ? $toks[$j][1] : '';
        $j++;

        while (true) {
            $this->skipWs($toks, $j);
            if ($j >= count($toks))
                break;

            $op = $toks[$j];
            if (!is_array($op) || !in_array($op[0], [T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR]))
                break;
            $j++;

            $this->skipWs($toks, $j);
            if ($j >= count($toks) || !is_array($toks[$j]) || $toks[$j][0] !== T_STRING)
                break;

            $member = $toks[$j][1];
            $j++;
            $this->skipWs($toks, $j);

            if ($j < count($toks) && is_string($toks[$j]) && $toks[$j] === '(') {
                $this->pos = $j;
                $this->consumeSemicolon();
                return ['label' => "{$label}->{$member}()", 'children' => []];
            }

            $label = "{$label}->{$member}";
        }

        return null;
    }

    private function tryExtractStaticCall(): ?array
    {
        $toks = $this->tokens;
        $className = $toks[$this->pos][1];
        $j = $this->pos + 1;

        $this->skipWs($toks, $j);
        if ($j >= count($toks) || !is_array($toks[$j]) || $toks[$j][0] !== T_DOUBLE_COLON)
            return null;
        $j++;

        $this->skipWs($toks, $j);
        if ($j >= count($toks) || !is_array($toks[$j]) || $toks[$j][0] !== T_STRING)
            return null;
        $methodName = $toks[$j][1];
        $j++;

        $this->skipWs($toks, $j);
        if ($j >= count($toks) || !is_string($toks[$j]) || $toks[$j] !== '(')
            return null;

        $this->pos = $j;
        $this->consumeSemicolon();
        return ['label' => "{$className}::{$methodName}()", 'children' => []];
    }

    private function consumeParenthesized(): string
    {
        $this->advanceWhitespace();
        if ($this->pos >= count($this->tokens) || $this->tokens[$this->pos] !== '(')
            return '';

        $depth = 0;
        $text = '';

        while ($this->pos < count($this->tokens)) {
            $t = $this->tokens[$this->pos];
            $ch = is_string($t) ? $t : null;

            if ($ch === '(') {
                $depth++;
                $this->pos++;
                if ($depth === 1)
                    continue;
                $text .= '(';
            } elseif ($ch === ')') {
                $depth--;
                $this->pos++;
                if ($depth === 0)
                    break;
                $text .= ')';
            } else {
                $text .= is_array($t) ? $t[1] : $t;
                $this->pos++;
            }
        }

        $text = preg_replace('/\s+/', ' ', trim($text));
        return strlen($text) > 100 ? substr($text, 0, 97) . '...' : $text;
    }

    private function consumeBodyBlock(): array
    {
        if ($this->pos >= count($this->tokens) || $this->tokens[$this->pos] !== '{')
            return [];

        $blockTokens = $this->sliceBraceBlock($this->tokens, $this->pos);

        $depth = 0;
        $count = count($this->tokens);
        while ($this->pos < $count) {
            $ch = is_string($this->tokens[$this->pos]) ? $this->tokens[$this->pos] : null;
            if ($ch === '{')
                $depth++;
            elseif ($ch === '}') {
                $depth--;
                if ($depth === 0) {
                    $this->pos++;
                    break;
                }
            }
            $this->pos++;
        }

        $savedTokens = $this->tokens;
        $savedPos = $this->pos;
        $this->tokens = $blockTokens;
        $this->pos = 0;
        $nodes = $this->parseBlock();
        $this->tokens = $savedTokens;
        $this->pos = $savedPos;

        return $nodes;
    }

    private function consumeToSemicolon(): string
    {
        $text = '';
        $count = count($this->tokens);
        while ($this->pos < $count) {
            $t = $this->tokens[$this->pos];
            $ch = is_string($t) ? $t : null;
            if ($ch === ';') {
                $this->pos++;
                break;
            }
            $text .= is_array($t) ? $t[1] : $t;
            $this->pos++;
        }
        $text = preg_replace('/\s+/', ' ', trim($text));
        return strlen($text) > 100 ? substr($text, 0, 97) . '...' : $text;
    }

    private function consumeToColon(): string
    {
        $text = '';
        $count = count($this->tokens);
        while ($this->pos < $count) {
            $t = $this->tokens[$this->pos];
            $ch = is_string($t) ? $t : null;
            if ($ch === ':') {
                $this->pos++;
                break;
            }
            $text .= is_array($t) ? $t[1] : $t;
            $this->pos++;
        }
        return trim($text);
    }

    private function consumeSemicolon(): void
    {
        $depth = 0;
        $count = count($this->tokens);
        while ($this->pos < $count) {
            $ch = is_string($this->tokens[$this->pos]) ? $this->tokens[$this->pos] : null;
            if ($ch === '(')
                $depth++;
            elseif ($ch === ')')
                $depth--;
            if ($ch === ';' && $depth === 0) {
                $this->pos++;
                break;
            }
            $this->pos++;
        }
    }

    private function advanceWhitespace(): void
    {
        $count = count($this->tokens);
        while ($this->pos < $count && is_array($this->tokens[$this->pos]) && $this->tokens[$this->pos][0] === T_WHITESPACE) {
            $this->pos++;
        }
    }

    private function currentType(): ?int
    {
        $t = $this->tokens[$this->pos] ?? null;
        return is_array($t) ? $t[0] : null;
    }

    private function renderNodes(array $nodes, string $prefix): void
    {
        $count = count($nodes);
        foreach ($nodes as $idx => $node) {
            $last = ($idx === $count - 1);
            $conn = $last ? '└── ' : '├── ';
            $childPfx = $prefix . ($last ? '    ' : '│   ');
            echo $prefix . $conn . $node['label'] . "\n";
            if (!empty($node['children'])) {
                $this->renderNodes($node['children'], $childPfx);
            }
        }
    }
}
