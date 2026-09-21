<?php

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

declare(strict_types=1);

namespace Clover\Enumeration\Claude;

/**
 * Enum Role
 *
 * Enumeration of roles in the Claude LLM API.
 *
 * @package Clover\Enumeration\Claude
 */
enum Role: string
{
    case USER = 'user';
    case ASSISTANT = 'assistant';
}