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
 * Class Model
 *
 * Enumeration of Claude LLM models.
 *
 * @package Clover\Enumeration\Claude
 */
abstract class Model
{
    public const HAIKU_3_20240307 = 'claude-3-haiku-20240307';
    public const HAIKU_3_5_20241022 = 'claude-3-5-haiku-20241022';
    public const HAIKU_4_20250514 = 'claude-haiku-4-20250514';
    public const HAIKU_4_5_20251001 = 'claude-haiku-4-5-20251001';
    public const OPUS_4_1_20250805 = 'claude-opus-4-1-20250805';
    public const OPUS_4_20250514 = 'claude-opus-4-20250514';
    public const OPUS_4_5_20251101 = 'claude-opus-4-5-20251101';
    public const SONNET_3_5_20241022 = 'claude-3-5-sonnet-20241022';
    public const SONNET_3_7_20250219 = 'claude-3-7-sonnet-20250219';
    public const SONNET_3_7_LATEST = 'claude-3-7-sonnet-latest';
    public const SONNET_4_20250514 = 'claude-sonnet-4-20250514';
    public const SONNET_4_5 = 'claude-sonnet-4-5';
    public const SONNET_4_5_20250929 = 'claude-sonnet-4-5-20250929';
    public const SONNET_4_6 = 'claude-sonnet-4-6';
}