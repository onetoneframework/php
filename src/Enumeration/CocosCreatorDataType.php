<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration;

abstract class CocosCreatorDataType
{
    public const ASSET = 'cc.Asset';
    public const AUDIO_CLIP = 'cc.AudioClip';
    public const IMAGE = 'cc.ImageAsset';
    public const JSON = 'cc.JsonAsset';
    public const PARTICLE = 'cc.ParticleAsset';
    public const PREFAB = 'cc.Prefab';
    public const SKELETON = 'cc.SkeletonData';
    public const SPRITE = 'cc.SpriteFrame';
    public const TEXT_ASSET = 'cc.TextAsset';
}