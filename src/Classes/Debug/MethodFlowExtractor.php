<?php

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

declare(strict_types=1);

namespace Clover\Classes\Debug\ControlFlow;
use function in_array;
use function count;

class MethodFlowExtractor
{
    private TokenCursor $cur;

    public function extract(string $filePath, string $shortClassName, string $methodName): ?FlowNode
    {
        $this->cur = new TokenCursor(file_get_contents($filePath));

        if (!$this->seekToMethodBody($shortClassName, $methodName)) {
            return null;
        }

        $root = new FlowNode('method', $shortClassName . '::' . $methodName . '()');
        $root->body = $this->parseBlock();
        return $root;
    }

    // ─── Seeking ─────────────────────────────────────────────────────────────

    private function seekToMethodBody(string $className, string $methodName): bool
    {
        while ($this->cur->valid()) {
            if ($this->cur->id() === T_CLASS) {
                $this->cur->advance()->skip();
                if ($this->cur->text() === $className) {
                    break;
                }
            }
            $this->cur->advance();
        }
        if (!$this->cur->valid()) {
            return false;
        }

        while ($this->cur->valid() && $this->cur->text() !== '{') {
            $this->cur->advance();
        }

        $depth = 0;
        while ($this->cur->valid()) {
            $text = $this->cur->text();
            $id = $this->cur->id();

            if ($text === '{') {
                $depth++;
            }
            if ($text === '}') {
                $depth--;
                if ($depth === 0) {
                    return false;
                }
            }

            if ($id === T_FUNCTION) {
                $this->cur->advance()->skip();
                if ($this->cur->text() === $methodName) {
                    while ($this->cur->valid() && $this->cur->text() !== '{') {
                        $this->cur->advance();
                    }
                    return $this->cur->valid();
                }
                continue;
            }

            $this->cur->advance();
        }

        return false;
    }

    // ─── Block / Statement ────────────────────────────────────────────────────

    private function parseBlock(): array
    {
        $this->cur->skip();
        if ($this->cur->text() === '{') {
            $this->cur->advance();
        }

        $nodes = [];
        while ($this->cur->valid()) {
            $this->cur->skip();
            if ($this->cur->text() === '}' || $this->cur->text() === '') {
                if ($this->cur->text() === '}') {
                    $this->cur->advance();
                }
                break;
            }
            $node = $this->parseStatement();
            if ($node !== null) {
                $nodes[] = $node;
            }
        }
        return $nodes;
    }

    private function parseBlockOrSingle(): array
    {
        $this->cur->skip();
        if ($this->cur->text() === '{') {
            return $this->parseBlock();
        }
        $node = $this->parseStatement();
        return $node !== null ? [$node] : [];
    }

    private function parseStatement(): ?FlowNode
    {
        $this->cur->skip();
        return match ($this->cur->id()) {
            T_IF => $this->parseIf(),
            T_FOR => $this->parseFor(),
            T_FOREACH => $this->parseForeach(),
            T_WHILE => $this->parseWhile(),
            T_DO => $this->parseDo(),
            T_SWITCH => $this->parseSwitch(),
            T_TRY => $this->parseTry(),
            T_RETURN => $this->parseLinear('return'),
            T_ECHO => $this->parseLinear('echo'),
            T_THROW => $this->parseLinear('throw'),
            default => $this->parseExpressionStatement(),
        };
    }

    // ─── Control Flow Parsers ─────────────────────────────────────────────────

    private function parseIf(): FlowNode
    {
        $this->cur->advance();
        $node = new FlowNode('if', $this->cur->collectInner('(', ')'));
        $node->body = $this->parseBlockOrSingle();

        $this->cur->skip();
        while ($this->cur->id() === T_ELSEIF) {
            $this->cur->advance();
            $elseif = new FlowNode('elseif', $this->cur->collectInner('(', ')'));
            $elseif->body = $this->parseBlockOrSingle();
            $node->alternate[] = $elseif;
            $this->cur->skip();
        }

        if ($this->cur->id() === T_ELSE) {
            $this->cur->advance()->skip();
            if ($this->cur->id() === T_IF) {
                $this->cur->advance();
                $elseif = new FlowNode('elseif', $this->cur->collectInner('(', ')'));
                $elseif->body = $this->parseBlockOrSingle();
                $node->alternate[] = $elseif;
                $this->cur->skip();
                if ($this->cur->id() === T_ELSE) {
                    $this->cur->advance();
                    $else = new FlowNode('else', '');
                    $else->body = $this->parseBlockOrSingle();
                    $node->alternate[] = $else;
                }
            } else {
                $else = new FlowNode('else', '');
                $else->body = $this->parseBlockOrSingle();
                $node->alternate[] = $else;
            }
        }

        return $node;
    }

    private function parseFor(): FlowNode
    {
        $this->cur->advance();
        $node = new FlowNode('for', $this->cur->collectInner('(', ')'));
        $node->body = $this->parseBlockOrSingle();
        return $node;
    }

    private function parseForeach(): FlowNode
    {
        $this->cur->advance();
        $node = new FlowNode('foreach', $this->cur->collectInner('(', ')'));
        $node->body = $this->parseBlockOrSingle();
        return $node;
    }

    private function parseWhile(): FlowNode
    {
        $this->cur->advance();
        $node = new FlowNode('while', $this->cur->collectInner('(', ')'));
        $node->body = $this->parseBlockOrSingle();
        return $node;
    }

    private function parseDo(): FlowNode
    {
        $this->cur->advance();
        $body = $this->parseBlockOrSingle();
        $this->cur->skip();
        $cond = '';
        if ($this->cur->id() === T_WHILE) {
            $this->cur->advance();
            $cond = $this->cur->collectInner('(', ')');
            $this->cur->consume(';');
        }
        $node = new FlowNode('do-while', $cond);
        $node->body = $body;
        return $node;
    }

    private function parseSwitch(): FlowNode
    {
        $this->cur->advance();
        $node = new FlowNode('switch', $this->cur->collectInner('(', ')'));
        $this->cur->skip()->consume('{');

        while ($this->cur->valid()) {
            $this->cur->skip();
            if ($this->cur->text() === '}') {
                $this->cur->advance();
                break;
            }

            $id = $this->cur->id();
            if ($id !== T_CASE && $id !== T_DEFAULT) {
                $this->cur->advance();
                continue;
            }

            $isDefault = ($id === T_DEFAULT);
            $this->cur->advance()->skip();

            $caseLabel = '';
            if (!$isDefault) {
                while ($this->cur->valid() && $this->cur->text() !== ':' && $this->cur->text() !== ';') {
                    if ($this->cur->id() !== T_WHITESPACE) {
                        $caseLabel .= $this->cur->text();
                    }
                    $this->cur->advance();
                }
            }
            $this->cur->consume(':');

            $caseBody = [];
            while ($this->cur->valid()) {
                $this->cur->skip();
                $nextId = $this->cur->id();
                $nextText = $this->cur->text();
                if ($nextId === T_CASE || $nextId === T_DEFAULT || $nextText === '}') {
                    break;
                }
                if ($nextId === T_BREAK || $nextId === T_CONTINUE) {
                    $this->cur->advance()->consume(';');
                    break;
                }
                $stmt = $this->parseStatement();
                if ($stmt !== null) {
                    $caseBody[] = $stmt;
                }
            }

            $node->cases[] = [
                'label' => $isDefault ? 'default' : $caseLabel,
                'body' => $caseBody,
            ];
        }

        return $node;
    }

    private function parseTry(): FlowNode
    {
        $this->cur->advance();
        $node = new FlowNode('try', '');
        $node->body = $this->parseBlock();

        $this->cur->skip();
        while ($this->cur->id() === T_CATCH) {
            $this->cur->advance();
            $catch = new FlowNode('catch', $this->cur->collectInner('(', ')'));
            $catch->body = $this->parseBlock();
            $node->alternate[] = $catch;
            $this->cur->skip();
        }
        if ($this->cur->id() === T_FINALLY) {
            $this->cur->advance();
            $finally = new FlowNode('finally', '');
            $finally->body = $this->parseBlock();
            $node->alternate[] = $finally;
        }

        return $node;
    }

    private function parseLinear(string $type): FlowNode
    {
        $this->cur->advance()->skip();
        $parts = [];
        while ($this->cur->valid() && $this->cur->text() !== ';') {
            if ($this->cur->id() !== T_WHITESPACE) {
                $parts[] = $this->cur->text();
            }
            $this->cur->advance();
        }
        $this->cur->consume(';');

        $expr = implode('', $parts);
        return new FlowNode($type, $expr);
    }

    // ─── Expression Statement ─────────────────────────────────────────────────

    private function parseExpressionStatement(): ?FlowNode
    {
        $this->cur->skip();
        $text = $this->cur->text();
        if ($text === '}' || $text === '') {
            return null;
        }
        if ($text === ';') {
            $this->cur->advance();
            return null;
        }

        $tokens = $this->cur->collectStatement();
        return empty($tokens) ? null : $this->classifyExpression($tokens);
    }

    private function classifyExpression(array $tokens): ?FlowNode
    {
        $depth = 0;
        $assignAt = -1;

        foreach ($tokens as $i => $t) {
            if (in_array($t, ['(', '[', '{'], true)) {
                $depth++;
                continue;
            }
            if (in_array($t, [')', ']', '}'], true)) {
                $depth--;
                continue;
            }
            if ($depth === 0 && $t === '=') {
                $assignAt = $i;
                break;
            }
            if ($depth === 0 && in_array($t, ['+=', '-=', '*=', '/=', '.=', '??='], true)) {
                $assignAt = $i;
                break;
            }
        }

        $rhs = $assignAt >= 0 ? array_slice($tokens, $assignAt + 1) : $tokens;
        $callLabel = $this->detectCall($rhs);

        return $callLabel !== null ? new FlowNode('call', $callLabel) : null;
    }

    private function detectCall(array $tokens): ?string
    {
        if (empty($tokens)) {
            return null;
        }

        $depth = 0;
        $callPart = '';
        $parenIdx = -1;

        foreach ($tokens as $i => $t) {
            if ($t === '(' && $depth === 0) {
                $parenIdx = $i;
                break;
            }
            if (in_array($t, ['[', '{'], true)) {
                $depth++;
                continue;
            }
            if (in_array($t, [']', '}'], true)) {
                $depth--;
                continue;
            }
            if ($depth === 0) {
                $needsSpace = $callPart !== ''
                    && preg_match('/\w$/', $callPart)
                    && preg_match('/^\w/', $t);
                $callPart .= ($needsSpace ? ' ' : '') . $t;
            }
        }

        if ($parenIdx < 0) {
            return null;
        }

        $argStr = $this->summarizeArgs(array_slice($tokens, $parenIdx + 1));
        $result = $callPart . '(' . $argStr . ')';

        return $result;
    }

    private function summarizeArgs(array $tokens): string
    {
        $depth = 0;
        $parts = [];
        $current = '';

        foreach ($tokens as $t) {
            if (in_array($t, ['(', '[', '{'], true)) {
                $depth++;
                $current .= $t;
                continue;
            }
            if (in_array($t, [')', ']', '}'], true)) {
                if ($depth === 0) {
                    break;
                }
                $depth--;
                $current .= $t;
                continue;
            }
            if ($t === ',' && $depth === 0) {
                $parts[] = trim($current);
                $current = '';
                continue;
            }
            $current .= $t;
        }
        if ($current !== '') {
            $parts[] = trim($current);
        }

        if (empty($parts)) {
            return '';
        }

        $shrink = fn(string $p): string => strlen($p) > 15 ? substr($p, 0, 12) . '...' : $p;

        if (count($parts) <= 3) {
            return implode(', ', array_map($shrink, $parts));
        }
        return implode(', ', array_map($shrink, array_slice($parts, 0, 2))) . ', …';
    }
}
