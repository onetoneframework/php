<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration\OpenAI;

/**
 * Enumeration class for OpenAI models.
 */
abstract class Model
{
    public const CHATGPT_4O_LATEST = 'chatgpt-4o-latest';
    public const DALL_E_3 = 'dall-e-3';
    public const GPT_3_5_TURBO = 'gpt-3.5-turbo';
    public const GPT_4_1 = 'gpt-4.1';
    public const GPT_4_1_MINI = 'gpt-4.1-mini';
    public const GPT_4O_2024_08_06 = 'gpt-4o-2024-08-06';
    public const GPT_4O_MINI_TRANSCRIBE = 'gpt-4o-mini-transcribe';
    public const GPT_4O_MINI_TTS = 'gpt-4o-mini-tts';
    public const GPT_4O_TRANSCRIBE = 'gpt-4o-transcribe';
    public const GPT_5 = 'gpt-5';
    public const GPT_5_1 = 'gpt-5.1';
    public const GPT_5_1_CODEX_MAX = 'gpt-5.1-codex-max';
    public const GPT_5_2 = 'gpt-5.2';
    public const GPT_5_2_CODEX = 'gpt-5.2-codex';
    public const GPT_5_3_CODEX = 'gpt-5.3-codex';
    public const GPT_5_4 = 'gpt-5.4';
    public const GPT_5_CHAT_LATEST = 'gpt-5-chat-latest';
    public const GPT_5_MINI = 'gpt-5-mini';
    public const GPT_5_MINI_2025_08_07 = 'gpt-5-mini-2025-08-07';
    public const GPT_5_NANO = 'gpt-5-nano';
    public const GPT_5_PRO = 'gpt-5-pro';
    public const GPT_AUDIO = 'gpt-audio';
    public const GPT_IMAGE_1 = 'gpt-image-1';
    public const GPT_O3_DEEP_RESEARCH = 'o3-deep-research';
    public const GPT_O4_MINI_DEEP_RESEARCH = 'o4-mini-deep-research';
    public const GPT_OSS_120B = 'gpt-oss-120b';
    public const GPT_OSS_20B = 'gpt-oss-20b';
    public const GPT_REALTIME = 'gpt-realtime';
    public const OMNI_MODERATION_LATEST = 'omni-moderation-latest';
    /**
     * The o1 series of models are trained with reinforcement learning to perform complex reasoning. o1 models think before they answer, producing a long internal chain of thought before responding to the user.
     * @var string
     */
    public const O_1 = 'o-1';
}