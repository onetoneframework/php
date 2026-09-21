<?php

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

declare(strict_types=1);

namespace Clover\Classes\Debug\ControlFlow;

class FlowNode
{
    /** @var FlowNode[] */
    public array $body = [];
    /** @var FlowNode[] */
    public array $alternate = [];
    /** @var array<array{label: string, body: FlowNode[]}> */
    public array $cases = [];

    public function __construct(
        public readonly string $type,
        public readonly string $label = ''
    ) {
    }
}
