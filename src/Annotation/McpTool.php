<?php

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

declare(strict_types=1);

namespace Clover\Annotation;

use Attribute;

/**
 * #[McpTool] Attribute for defining MCP tools
 */
#[Attribute(Attribute::TARGET_METHOD)]
class McpTool
{
    /**
     * Declares an MCP tool name and description for the marked method.
     *
     * @param string $name        Registered tool identifier exposed to MCP clients.
     * @param string $description Human-readable summary of what the tool does.
     */
    public function __construct(
        public string $name,
        public string $description = ''
    ) {
    }
}
