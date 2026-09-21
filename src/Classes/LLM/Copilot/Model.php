<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration\Copilot;

/**
 * Enum Model
 *
 * Enumeration of models available in the GitHub Copilot LLM API.
 *
 * @package Clover\Enumeration\Copilot
 */
abstract class Model
{
    const GPT_4O = 'gpt-4o';
    const GPT_4O_MINI = 'gpt-4o-mini';
    const O1_PREVIEW = 'o1-preview';
    const O1_MINI = 'o1-mini';
    const CLAUDE_3_5_SONNET = 'claude-3.5-sonnet';
}