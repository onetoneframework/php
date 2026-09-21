<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Framework\Livewire;

use function htmlspecialchars;
use function json_encode;
use function preg_match;
use function preg_match_all;
use function preg_quote;
use function rtrim;
use function sprintf;
use function str_ends_with;
use function str_starts_with;
use function substr;
use function trim;
use function strlen;

/**
 * Turns a mounted component into transport-ready HTML.
 *
 * The output always contains exactly one root element carrying the
 * `wire:id`, `wire:component` and `wire:snapshot` attributes used by
 * the client-side runtime. If a component returns multiple sibling
 * nodes we wrap them in a `<div>` on the fly so DOM morphing and
 * event delegation work with a single entry point.
 */
final class Renderer
{
    public function __construct(private readonly Hydrator $hydrator)
    {
    }

    /**
     * Render a component and attach its signed snapshot to the root
     * element. Consumes any queued `$this->dispatch()` calls so they
     * are emitted as part of the mount payload.
     *
     * @return array{html: string, snapshot: array<string, mixed>, dispatches: array<int, array<string, mixed>>}
     */
    public function render(Component $component): array
    {
        $html = (string) $component->render();
        $html = trim($html);

        $snapshot = $this->hydrator->dehydrate($component);
        $html = $this->injectRootAttributes($html, $snapshot);

        return [
            'html' => $html,
            'snapshot' => $snapshot,
            'dispatches' => $component->__livewireConsumeDispatches(),
        ];
    }

    /**
     * Insert `wire:id`, `wire:component` and `wire:snapshot` on the
     * first HTML tag. Wraps the fragment when it starts with text or
     * contains multiple root elements.
     *
     * @param array<string, mixed> $snapshot
     */
    private function injectRootAttributes(string $html, array $snapshot): string
    {
        $encoded = (string) json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $encoded = htmlspecialchars($encoded, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $id = htmlspecialchars((string) $snapshot['id'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $name = htmlspecialchars((string) $snapshot['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $attributes = sprintf(' wire:id="%s" wire:component="%s" wire:snapshot="%s"', $id, $name, $encoded);

        if ($html === '' || $html[0] !== '<') {
            return sprintf('<div%s>%s</div>', $attributes, $html);
        }

        // Only inject into the first element; if there are multiple siblings wrap them.
        if (!preg_match('/^<\s*([a-zA-Z][a-zA-Z0-9-]*)\b([^>]*)>/', $html, $m, PREG_OFFSET_CAPTURE)) {
            return sprintf('<div%s>%s</div>', $attributes, $html);
        }

        $tagName = $m[1][0];
        $tagPattern = sprintf('/<\\/?%s\\b[^>]*>/i', preg_quote($tagName, '/'));
        preg_match_all($tagPattern, $html, $tagMatches, PREG_OFFSET_CAPTURE);
        $depth = 0;
        $rootEndOffset = null;

        foreach ($tagMatches[0] as $tagMatch) {
            $tag = $tagMatch[0];
            $tagOffset = $tagMatch[1];

            if (str_starts_with($tag, '</')) {
                $depth--;
                if ($depth === 0) {
                    $rootEndOffset = $tagOffset + strlen($tag);
                    break;
                }

                continue;
            }

            if (!str_ends_with(rtrim($tag), '/>')) {
                $depth++;
            }
        }

        if ($rootEndOffset === null || trim(substr($html, $rootEndOffset)) !== '') {
            return sprintf('<div%s>%s</div>', $attributes, $html);
        }

        $pos = $m[0][1];
        $len = strlen($m[0][0]);
        $openTag = $m[0][0];
        // Drop the trailing '>' and re-append with attributes.
        $openingWithoutGt = substr($openTag, 0, -1);
        $newOpenTag = $openingWithoutGt . $attributes . '>';

        return substr($html, 0, $pos) . $newOpenTag . substr($html, $pos + $len);
    }
}
