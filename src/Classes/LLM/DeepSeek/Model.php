<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration\DeepSeek;

/**
 * Enum Model
 *
 * Enumeration of models available in the DeepSeek LLM API.
 *
 * @package Clover\Enumeration\DeepSeek
 */
abstract class Model
{
    public const DEEPSEEK_CHAT = 'deepseek-chat';
    public const DEEPSEEK_REASONER = 'deepseek-reasoner';
}