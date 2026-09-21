<?php

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

declare(strict_types=1);

namespace Clover\Classes\Debug\ControlFlow;

class AsciiFlowRenderer
{
    private array $lines;

    private const TYPE_LABELS = [
        'if' => 'IF',
        'elseif' => 'ELSEIF',
        'else' => 'ELSE',
        'for' => 'FOR',
        'foreach' => 'FOREACH',
        'while' => 'WHILE',
        'do-while' => 'DO-WHILE',
        'switch' => 'SWITCH',
        'case' => 'CASE',
        'try' => 'TRY',
        'catch' => 'CATCH',
        'finally' => 'FINALLY',
        'call' => 'CALL',
        'return' => 'RETURN',
        'echo' => 'ECHO',
        'throw' => 'THROW',
    ];

    public function render(FlowNode $root): string
    {
        $this->lines = [];

        $title = ' ' . $root->label . ' ';
        $width = strlen($title) + 2;
        $this->lines[] = '╔' . str_repeat('═', $width) . '╗';
        $this->lines[] = '║ ' . $title . ' ║';
        $this->lines[] = '╚' . str_repeat('═', $width) . '╝';

        if (!empty($root->body)) {
            $this->lines[] = '  │';
            $this->renderChildren($root->body, '  ');
        }

        return implode("\n", $this->lines);
    }

    private function renderChildren(array $children, string $prefix): void
    {
        $last = count($children) - 1;
        foreach ($children as $i => $child) {
            $this->renderNode($child, $prefix, $i === $last);
        }
    }

    private function renderNode(FlowNode $node, string $prefix, bool $isLast): void
    {
        $connector = $isLast ? '└─' : '├─';
        $childPrefix = $prefix . ($isLast ? '   ' : '│  ');

        $tag = self::TYPE_LABELS[$node->type] ?? strtoupper($node->type);
        $label = $node->label !== '' ? ' ' . $node->label : '';
        $this->lines[] = $prefix . $connector . '[' . $tag . ']' . $label;

        $children = $this->expandChildren($node);
        if (!empty($children)) {
            $this->renderChildren($children, $childPrefix);
        }
    }

    private function expandChildren(FlowNode $node): array
    {
        $children = [];

        foreach ($node->body as $child) {
            $children[] = $child;
        }
        foreach ($node->cases as $case) {
            $caseNode = new FlowNode('case', $case['label']);
            $caseNode->body = $case['body'];
            $children[] = $caseNode;
        }
        foreach ($node->alternate as $alt) {
            $children[] = $alt;
        }

        return $children;
    }
}
