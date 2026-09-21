<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\OperationSystem\Linux;

use Clover\Classes\OperationSystem\Linux\Base;

/*
 * Class Binary
 *
 * @package Clover\Classes\OperationSystem\Linux
 */
class Binary extends Base
{
    /**
     * Check if the FFProber extension is installed.
     *
     * @return bool
     */
    public static function isMetaFlacInstalled(): bool
    {
        return self::isLinuxExtensionInstalled('/usr/bin/metaflac');
    }

    /**
     * Check if the WGet extension is installed.
     *
     * @return bool
     */
    public static function isWGetInstalled(): bool
    {
        return self::isLinuxExtensionInstalled('/usr/bin/wget');
    }

    /**
     * Check if the LAME extension is installed.
     *
     * @return bool
     */
    public static function isLameInstalled(): bool
    {
        return self::isLinuxExtensionInstalled('/usr/bin/lame');
    }

    /**
     * Check if the BladeEnc extension is installed.
     *
     * @return bool
     */
    public static function isBladeEncInstalled(): bool
    {
        return self::isLinuxExtensionInstalled('/usr/bin/bladeenc');
    }

    /**
     * Check if the OggEnc extension is installed.
     *
     * @return bool
     */
    public static function isOggEncInstalled(): bool
    {
        return self::isLinuxExtensionInstalled('/usr/bin/oggenc');
    }

    /**
     * Check if the Speexenc extension is installed.
     *
     * @return bool
     */
    public static function isSpeexencInstalled(): bool
    {
        return self::isLinuxExtensionInstalled('/usr/bin/speexenc');
    }

    /**
     * Check if the OpusEnc extension is installed.
     *
     * @return bool
     */
    public static function isOpusEncInstalled(): bool
    {
        return self::isLinuxExtensionInstalled('/usr/bin/opusenc');
    }

    /**
     * Check if the Unrar extension is installed.
     *
     * @return bool
     */
    public static function isUnrarInstalled(): bool
    {
        return self::isLinuxExtensionInstalled('/usr/bin/unrar');
    }

    /**
     * Check if the Unzip extension is installed.
     *
     * @return bool
     */
    public static function isUnzipInstalled(): bool
    {
        return self::isLinuxExtensionInstalled('/usr/bin/unzip');
    }

    /**
     * Check if the Timeout extension is installed.
     *
     * @return bool
     */
    public static function isTimeoutInstalled(): bool
    {
        return self::isLinuxExtensionInstalled('/usr/bin/timeout');
    }

    /**
     * Check if the MediaInfo extension is installed.
     *
     * @return bool
     */
    public static function isMediaInfoInstalled(): bool
    {
        return self::isLinuxExtensionInstalled('/usr/bin/mediainfo');
    }

    /**
     * Check if the Flac extension is installed.
     *
     * @return bool
     */
    public static function isFlagInstalled(): bool
    {
        return self::isLinuxExtensionInstalled('/usr/bin/flac');
    }

    /**
     * Check if the Id3V2 extension is installed.
     *
     * @return bool
     */
    public static function isId3V2Installed(): bool
    {
        return self::isLinuxExtensionInstalled('/usr/bin/id3v2');
    }

    /**
     * Check if the CdParanoia extension is installed.
     *
     * @return bool
     */
    public static function isCdParanoiaInstalled(): bool
    {
        return self::isLinuxExtensionInstalled('/usr/bin/cdparanoia');
    }

    /**
     * Check if the Cdda2Wav extension is installed.
     *
     * @return bool
     */
    public static function isCdda2WavInstalled(): bool
    {
        return self::isLinuxExtensionInstalled('/usr/bin/cdda2wav');
    }
    
    /**
     * Check if the CdDiscId extension is installed.
     *
     * @return bool
     */
    public static function isCdDiscIdInstalled(): bool
    {
        return self::isLinuxExtensionInstalled('/usr/bin/cd-discid');
    }

    /**
     * Check if the CdDbTool extension is installed.
     *
     * @return bool
     */
    public static function isCdDbToolInstalled(): bool
    {
        return self::isLinuxExtensionInstalled('/usr/bin/cddb-tool');
    }

    /**
     * Check if the Eject extension is installed.
     *
     * @return bool
     */
    public static function isEjectInstalled(): bool
    {
        return self::isLinuxExtensionInstalled('/usr/bin/eject');
    }

    /**
     * Check if the VorbisComment extension is installed.
     *
     * @return bool
     */
    public static function isVorbisCommentInstalled(): bool
    {
        return self::isLinuxExtensionInstalled('/usr/bin/vorbiscomment');
    }

    /**
     * Check if the Normalize extension is installed.
     *
     * @return bool
     */
    public static function isNormalizeInstalled(): bool
    {
        return self::isLinuxExtensionInstalled('/usr/bin/normalize');
    }

    /**
     * Check if the Spek extension is installed.
     *
     * @return bool
     */
    public static function isSpekInstalled(): bool
    {
        return self::isLinuxExtensionInstalled('/usr/bin/spek');
    }

    /**
     * Check if the FFmpeg extension is installed.
     *
     * @return bool
     */
    public static function isFFMpegInstalled(): bool
    {
        return self::isLinuxExtensionInstalled('/usr/bin/ffmpeg');
    }

    /**
     * Check if the FFProbe extension is installed.
     *
     * @return bool
     */
    public static function isFFProbeInstalled(): bool
    {
        return self::isLinuxExtensionInstalled('/usr/bin/ffprobe');
    }

}
