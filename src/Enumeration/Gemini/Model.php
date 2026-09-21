<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration\Gemini;

/**
 * Enumeration class for Gemini models.
 */
abstract class Model
{
    public const GEMINI_1_5_FLASH = 'gemini-1.5-flash';
    public const GEMINI_1_5_PRO = 'gemini-1.5-pro';
    public const GEMINI_1_5_FLASH_8B = 'gemini-1.5-flash-8b';
    public const GEMINI_2_0_FLASH = 'gemini-2.0-flash';
    public const GEMINI_2_0_FLASH_LITE = 'gemini-2.0-flash-lite';
    public const GEMINI_2_5_FLASH_PREVIEW_NATIVE_AUDIO_DIALOG = 'gemini-2.5-flash-preview-native-audio-dialog';
    public const GEMINI_2_5_FLASH_EXP_NATIVE_AUDIO_THINKING_DIALOG = 'gemini-2.5-flash-exp-native-audio-thinking-dialog';
    public const GEMINI_2_5_PRO_PREVIEW_TTS = 'gemini-2.5-pro-preview-tts';
    public const GEMINI_2_5_FLASH_PREVIEW_TTS = 'gemini-2.5-flash-preview-tts';
    public const GEMINI_2_0_FLASH_PREVIEW_IMAGE_GENERATION = 'gemini-2.0-flash-preview-image-generation';
    public const GEMINI_2_0_FLASH_LIVE_001 = 'gemini-2.0-flash-live-001';
    public const GEMINI_2_5_FLASH = 'gemini-2.5-flash';
    public const GEMINI_2_5_FLASH_LITE = 'gemini-2.5-flash-lite';
    public const GEMINI_LIVE_2_5_FLASH_PREVIEW = 'gemini-live-2.5-flash-preview';
    public const GEMINI_2_5_PRO = 'gemini-2.5-pro';
    public const GEMINI_3_1_PRO_PREVIEW = 'gemini-3.1-pro-preview';
    public const GEMINI_3_1_FLASH_LITE_PREVIEW = 'gemini-3.1-flash-lite-preview';
    public const GEMINI_3_1_FLASH_PREVIEW = 'gemini-3-flash-preview';
    public const TEXT_EMBEDDING_004 = 'text-embedding-004';
    public const GEMINI_EMBEDDING_001 = 'gemini-embedding-001';
    public const VEO_2_0_GENERATE_001 = 'veo-2.0-generate-001';
    public const VEO_3_0_GENERATE_PREVIEW = 'veo-3.0-generate-preview';
    public const IMAGEN_3_0_GENERATE_002 = 'imagen-3.0-generate-002';
    public const IMAGEN_3_0_GENERATE_001 = 'imagen-3.0-generate-001';
    public const IMAGEN_4_0_GENERATE_001 = 'imagen-4.0-generate-001';
    public const IMAGEN_3_0_FAST_GENERATE_001 = 'imagen-3.0-fast-generate-001';
    public const IMAGEN_4_0_GENERATE_PREVIEW_06_06 = 'imagen-4.0-generate-preview-06-06';
    public const IMAGEN_4_0_ULTRA_GENERATE_PREVIEW_06_06 = 'imagen-4.0-ultra-generate-preview-06-06';
}
