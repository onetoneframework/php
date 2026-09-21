<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration\Qwen;

/**
 * Class Model
 *
 * Enumeration of available Qwen LLM models.
 *
 * @package Clover\Enumeration\Qwen
 */
abstract class Model
{
    const QWEN_CODER_TURBO_LATEST = 'qwen-coder-turbo-latest';
    const QWEN_CODER_PLUS_LATEST = 'qwen-coder-plus-latest';
    const QWEN_TURBO_LATEST = 'qwen-turbo-latest';
    const QWEN_PLUS_LATEST = 'qwen-plus-latest';
    const QWEN_MAX_LATEST = 'qwen-max-latest';
}