<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Video;

use Clover\Classes\BaseClass;
use Clover\Classes\Chrome\ChromeDevTool;
use Clover\Classes\Data\BinaryHandler;
use Clover\Classes\Math\Vector;
use Clover\Classes\Video\H264\ContextInitialisation;
use Clover\Classes\Video\H264\IntraFrameDecoder;
use Clover\Classes\OperationSystem;
use Exception;
use function strlen;
use function substr;
use function ord;
use function sprintf;
use function chr;
use function count;
use function in_array;

/*
 * Class Mp4Parser
 *
 * A utility class for parsing MP4 files and extracting metadata and structure information.
 * 
 * This class provides methods to validate MP4 files, read box headers, and parse key boxes such as 'ftyp', 'mvhd', 'tkhd', and 'mdhd'. It also includes mappings for brand identifiers, handler types, video codecs, audio codecs, and iTunes metadata keys to facilitate interpretation of the parsed data.
 */
class Mp4Parser extends BaseClass
{
	private const HEADLESS_BROWSER_FRAME_BUDGET_MILLISECONDS = 10000;
	private const CABAC_BLOCK_CATEGORY_LUMA_DC = 0;
	private const CABAC_BLOCK_CATEGORY_LUMA_AC_16X16 = 1;
	private const CABAC_BLOCK_CATEGORY_LUMA_4X4 = 2;
	private const CABAC_BLOCK_CATEGORY_CHROMA_DC = 3;
	private const CABAC_BLOCK_CATEGORY_CHROMA_AC = 4;
	private const CABAC_BLOCK_CATEGORY_LUMA_8X8 = 5;
	private const H264_CABAC_UNAVAILABLE_INTRA_LUMA_CODED_BLOCK_PATTERN = 0x0F;

    // ISO base media file format brand identifiers
    public static array $brandMap = [
        'isom' => 'ISO Base Media File',
        'iso2' => 'ISO Base Media File v2',
        'iso3' => 'ISO Base Media File v3',
        'iso4' => 'ISO Base Media File v4',
        'iso5' => 'ISO Base Media File v5',
        'iso6' => 'ISO Base Media File v6',
        'iso7' => 'ISO Base Media File v7',
        'iso8' => 'ISO Base Media File v8',
        'iso9' => 'ISO Base Media File v9',
        'avc1' => 'AVC/H.264',
        'mp41' => 'MP4 v1',
        'mp42' => 'MP4 v2',
        'mp71' => 'MP4 w/ MPEG-7 MetaData',
        'M4V ' => 'Apple M4V',
        'M4A ' => 'Apple M4A',
        'M4B ' => 'Apple M4B',
        'M4P ' => 'Apple M4P',
        'qt  ' => 'Apple QuickTime',
        'MSNV' => 'Sony PSP',
        'dash' => 'DASH',
        'f4v ' => 'Adobe Flash Video',
        '3gp4' => '3GPP Release 4',
        '3gp5' => '3GPP Release 5',
        '3gp6' => '3GPP Release 6',
        '3gp7' => '3GPP Release 7',
        '3gs7' => '3GPP Release 7 Streaming',
        '3ge6' => '3GPP Release 6 Extended',
        '3ge7' => '3GPP Release 7 Extended',
        '3gg6' => '3GPP Release 6 General',
        '3g2a' => '3GPP2',
        '3g2b' => '3GPP2 b',
        '3g2c' => '3GPP2 c',
        'CAEP' => 'Canon Digital Camera',
        'NDSC' => 'Nikon Digital Camera',
        'KDDI' => 'KDDI 3GPP2 EZmovie',
        'LCAG' => 'Leica Digital Camera',
    ];

    // Track handler type identifiers
    public static array $handlerTypeMap = [
        'vide' => 'Video',
        'soun' => 'Audio',
        'hint' => 'Hint',
        'meta' => 'Metadata',
        'text' => 'Text',
        'subt' => 'Subtitle',
        'sbtl' => 'Subtitle',
        'subp' => 'Subtitle',
        'tmcd' => 'Timecode',
        'odsm' => 'Object Descriptor',
        'sdsm' => 'Scene Description',
        'auxv' => 'Auxiliary Video',
    ];

    // Video codec four-character codes
    public static array $videoCodecMap = [
        'avc1' => 'H.264/AVC',
        'avc2' => 'H.264/AVC',
        'avc3' => 'H.264/AVC',
        'avc4' => 'H.264/AVC',
        'hev1' => 'H.265/HEVC',
        'hvc1' => 'H.265/HEVC',
        'vp08' => 'VP8',
        'vp09' => 'VP9',
        'av01' => 'AV1',
        'mp4v' => 'MPEG-4 Visual',
        's263' => 'H.263',
        'H263' => 'H.263',
        'jpeg' => 'JPEG',
        'mjpa' => 'Motion JPEG A',
        'mjpb' => 'Motion JPEG B',
        'png ' => 'PNG',
        'SVQ1' => 'Sorenson Video 1',
        'SVQ3' => 'Sorenson Video 3',
        'dvhe' => 'Dolby Vision HEVC',
        'dvh1' => 'Dolby Vision HEVC',
        'dva1' => 'Dolby Vision AVC',
        'dvav' => 'Dolby Vision AVC',
        'ap4h' => 'Apple ProRes 4444',
        'ap4x' => 'Apple ProRes 4444 XQ',
        'apch' => 'Apple ProRes 422 HQ',
        'apcn' => 'Apple ProRes 422',
        'apcs' => 'Apple ProRes 422 LT',
        'apco' => 'Apple ProRes 422 Proxy',
    ];

    // Audio codec four-character codes
    public static array $audioCodecMap = [
        'mp4a' => 'AAC',
        'ac-3' => 'AC-3',
        'ec-3' => 'E-AC-3',
        'alac' => 'Apple Lossless',
        'samr' => 'AMR Narrowband',
        'sawb' => 'AMR Wideband',
        'sowt' => 'PCM (Little-Endian)',
        'twos' => 'PCM (Big-Endian)',
        'raw ' => 'PCM (Unsigned)',
        'lpcm' => 'Linear PCM',
        'in24' => 'PCM 24-bit Integer',
        'in32' => 'PCM 32-bit Integer',
        'fl32' => 'PCM 32-bit Float',
        'fl64' => 'PCM 64-bit Float',
        'alaw' => 'A-law',
        'ulaw' => 'mu-law',
        'Opus' => 'Opus',
        'fLaC' => 'FLAC',
        'dtsc' => 'DTS',
        'dtsh' => 'DTS-HD',
        'dtsl' => 'DTS-HD Lossless',
        'dtse' => 'DTS Express',
        'mlpa' => 'Dolby TrueHD',
        '.mp3' => 'MP3',
        'vorbis' => 'Vorbis',
    ];

    // iTunes metadata atom key to human-readable field name mapping
    public static array $itunesKeyMap = [
        "\xA9nam" => 'title',
        "\xA9ART" => 'artist',
        "\xA9alb" => 'album',
        "\xA9day" => 'year',
        "\xA9cmt" => 'comment',
        "\xA9gen" => 'genre',
        "\xA9wrt" => 'composer',
        "\xA9too" => 'encoder',
        "\xA9grp" => 'grouping',
        "\xA9lyr" => 'lyrics',
        "\xA9des" => 'description',
        'aART' => 'album_artist',
        'trkn' => 'track_number',
        'disk' => 'disc_number',
        'tmpo' => 'bpm',
        'cprt' => 'copyright',
        'cpil' => 'compilation',
        'pgap' => 'gapless_playback',
        'covr' => 'cover_art',
        'gnre' => 'genre_id',
        'desc' => 'description',
        'ldes' => 'long_description',
        'tvsh' => 'tv_show',
        'tven' => 'tv_episode_id',
        'tvsn' => 'tv_season',
        'tves' => 'tv_episode',
        'stik' => 'media_type',
        'hdvd' => 'hd_video',
        'purd' => 'purchase_date',
        'soal' => 'sort_album',
        'soar' => 'sort_artist',
        'soaa' => 'sort_album_artist',
        'sonm' => 'sort_name',
        'soco' => 'sort_composer',
        'sosn' => 'sort_show',
    ];

    // iTunes stik atom media type values
    public static array $mediaTypes = [
        0 => 'Movie',
        1 => 'Music',
        2 => 'Audiobook',
        5 => 'Whacked Bookmark',
        6 => 'Music Video',
        9 => 'Short Film',
        10 => 'TV Show',
        11 => 'Booklet',
        14 => 'Ringtone',
    ];

    /**
     * Checks for ftyp box or known top-level box types to confirm MP4 validity.
     * 
     * @param string $file
     * @return bool
     */
    public static function isValidMp4(string $file): bool
    {
        if (!file_exists($file) || !is_readable($file)) {
            return false;
        }

        $fh = @fopen($file, 'rb');
        if ($fh === false) {
            return false;
        }

        $header = fread($fh, 12);
        fclose($fh);

        if (strlen($header) < 8) {
            return false;
        }

        $type = substr($header, 4, 4);
        if (in_array($type, ['ftyp', 'moov', 'mdat', 'free', 'skip', 'wide'], true)) {
            return true;
        }

        return false;
    }

    /**
     * Build an ISO base media file format box (size + type + content).
     *
     * @param string $type Four-character box type code.
     * @param string $content Box payload bytes.
     * @return string Complete box binary data.
     */
    public static function writeBox(string $type, string $content): string
    {
        $size = 8 + strlen($content);
        return BinaryHandler::writeUint32($size) . $type . $content;
    }

    /**
     * Build an ISO base media full box (size + type + version + flags + content).
     *
     * @param string $type Four-character box type code.
     * @param int $version Box version (0 or 1).
     * @param int $flags 24-bit flags value.
     * @param string $content Box payload bytes (after version/flags).
     * @return string Complete full box binary data.
     */
    public static function writeFullBox(string $type, int $version, int $flags, string $content): string
    {
        $versionFlags = chr($version) . chr(($flags >> 16) & 0xFF) . chr(($flags >> 8) & 0xFF) . chr($flags & 0xFF);
        return self::writeBox($type, $versionFlags . $content);
    }

    /**
     * Reads an ISO base media box header (size + type), handling extended 64-bit size and size=0 (to EOF).
     * 
     * @param resource $fh
     * @return ?array{size: int, type: string, header_size: int, data_offset: int|bool, data_size: int}
     */
    public static function readBoxHeader(mixed $fh): ?array
    {
        $data = fread($fh, 8);
        if ($data === false || strlen($data) < 8) {
            return null;
        }

        $size = BinaryHandler::readUint32($data, 0);
        $type = substr($data, 4, 4);

        $headerSize = 8;

        if ($size === 1) {
            // Extended 64-bit size follows the standard 8-byte header
            $extData = fread($fh, 8);
            if ($extData === false || strlen($extData) < 8) {
                return null;
            }
            $size = BinaryHandler::readUint64($extData, 0);
            $headerSize = 16;
        } elseif ($size === 0) {
            // size=0 means the box extends to the end of the file
            $currentPos = ftell($fh);
            fseek($fh, 0, SEEK_END);
            $fileEnd = ftell($fh);
            fseek($fh, $currentPos);
            $size = $fileEnd - $currentPos + $headerSize;
        }

        return [
            'size' => $size,
            'type' => $type,
            'header_size' => $headerSize,
            'data_offset' => ftell($fh),
            'data_size' => $size - $headerSize,
        ];
    }

    /**
     * Parses the ftyp (File Type) box to extract major brand, minor version, and compatible brands.
     * 
     * @param string $data
     * @return array{major_brand: string, major_brand_name: string, minor_version: int, compatible_brands: string[]}
     */
    public static function parseFtyp(string $data): array
    {
        $majorBrand = substr($data, 0, 4);
        $minorVersion = BinaryHandler::readUint32($data, 4);

        $compatibleBrands = [];
        $offset = 8;
        while ($offset + 4 <= strlen($data)) {
            $brand = substr($data, $offset, 4);
            if (trim($brand) !== '') {
                $compatibleBrands[] = $brand;
            }
            $offset += 4;
        }

        return [
            'major_brand' => $majorBrand,
            'major_brand_name' => self::$brandMap[$majorBrand] ?? $majorBrand,
            'minor_version' => $minorVersion,
            'compatible_brands' => $compatibleBrands,
        ];
    }

    /**
     * Parses the mvhd (Movie Header) box. Supports both version 0 (32-bit timestamps) and version 1 (64-bit).
     * 
     * @param string $data
     * @return array{
     *  version: int, 
     *  creation_time: int, 
     *  modification_time: int, 
     *  timescale: int, 
     *  duration: int, 
     *  duration_seconds: float|int, 
     *  rate: float, 
     *  volume: float, 
     *  next_track_id: int
     * }
     */
    public static function parseMvhd(string $data): array
    {
        $version = ord($data[0]);
        $offset = 4;

        if ($version === 1) {
            $creationTime = BinaryHandler::readUint64($data, $offset);
            $offset += 8;
            $modificationTime = BinaryHandler::readUint64($data, $offset);
            $offset += 8;
            $timescale = BinaryHandler::readUint32($data, $offset);
            $offset += 4;
            $duration = BinaryHandler::readUint64($data, $offset);
            $offset += 8;
        } else {
            $creationTime = BinaryHandler::readUint32($data, $offset);
            $offset += 4;
            $modificationTime = BinaryHandler::readUint32($data, $offset);
            $offset += 4;
            $timescale = BinaryHandler::readUint32($data, $offset);
            $offset += 4;
            $duration = BinaryHandler::readUint32($data, $offset);
            $offset += 4;
        }

        $rate = BinaryHandler::readFixedPoint1616($data, $offset);
        $offset += 4;
        $volume = BinaryHandler::readFixedPoint88($data, $offset);
        $offset += 2;
        $offset += 10; // reserved
        $offset += 36; // 3x3 matrix

        $nextTrackId = BinaryHandler::readUint32($data, $offset);

        $durationSeconds = $timescale > 0 ? $duration / $timescale : 0;

        return [
            'version' => $version,
            'creation_time' => $creationTime,
            'modification_time' => $modificationTime,
            'timescale' => $timescale,
            'duration' => $duration,
            'duration_seconds' => $durationSeconds,
            'rate' => $rate,
            'volume' => $volume,
            'next_track_id' => $nextTrackId,
        ];
    }

    /**
     * Parses the tkhd (Track Header) box, including track flags, dimensions, and identity fields.
     * 
     * @param string $data
     * @return array{
     *  alternate_group: int, 
     *  creation_time: int, 
     *  duration: int, 
     *  flags: int, 
     *  height: int, 
     *  layer: int, 
     *  modification_time: int, 
     *  track_enabled: bool, 
     *  track_id: int, 
     *  track_in_movie: bool, 
     *  track_in_preview: bool, 
     *  version: int, 
     *  volume: float, 
     *  width: int
     * }
     */
    public static function parseTkhd(string $data): array
    {
        $version = ord($data[0]);
        $flags = (ord($data[1]) << 16) | (ord($data[2]) << 8) | ord($data[3]);
        $offset = 4;

        if ($version === 1) {
            $creationTime = BinaryHandler::readUint64($data, $offset);
            $offset += 8;
            $modificationTime = BinaryHandler::readUint64($data, $offset);
            $offset += 8;
            $trackId = BinaryHandler::readUint32($data, $offset);
            $offset += 4;
            $offset += 4; // reserved
            $duration = BinaryHandler::readUint64($data, $offset);
            $offset += 8;
        } else {
            $creationTime = BinaryHandler::readUint32($data, $offset);
            $offset += 4;
            $modificationTime = BinaryHandler::readUint32($data, $offset);
            $offset += 4;
            $trackId = BinaryHandler::readUint32($data, $offset);
            $offset += 4;
            $offset += 4; // reserved
            $duration = BinaryHandler::readUint32($data, $offset);
            $offset += 4;
        }

        $offset += 8;  // reserved

        $layer = BinaryHandler::readUint16($data, $offset);
        $offset += 2;
        $alternateGroup = BinaryHandler::readUint16($data, $offset);
        $offset += 2;
        $volume = BinaryHandler::readFixedPoint88($data, $offset);
        $offset += 2;
        $offset += 2;  // reserved
        $offset += 36; // 3x3 matrix

        $width = BinaryHandler::readFixedPoint1616($data, $offset);
        $offset += 4;
        $height = BinaryHandler::readFixedPoint1616($data, $offset);

        return [
            'version' => $version,
            'flags' => $flags,
            'track_enabled' => ($flags & 0x01) !== 0,
            'track_in_movie' => ($flags & 0x02) !== 0,
            'track_in_preview' => ($flags & 0x04) !== 0,
            'creation_time' => $creationTime,
            'modification_time' => $modificationTime,
            'track_id' => $trackId,
            'duration' => $duration,
            'layer' => $layer,
            'alternate_group' => $alternateGroup,
            'volume' => $volume,
            'width' => (int) $width,
            'height' => (int) $height,
        ];
    }

    /**
     * Parses the mdhd (Media Header) box, decoding the ISO 639-2/T packed language code.
     * 
     * @param string $data
     * @return array{
     *  creation_time: int, 
     *  duration: int, 
     *  duration_seconds: float|int, 
     *  language: string, 
     *  modification_time: int, 
     *  timescale: int, 
     *  version: int
     * }
     */
    public static function parseMdhd(string $data): array
    {
        $version = ord($data[0]);
        $offset = 4;

        if ($version === 1) {
            $creationTime = BinaryHandler::readUint64($data, $offset);
            $offset += 8;
            $modificationTime = BinaryHandler::readUint64($data, $offset);
            $offset += 8;
            $timescale = BinaryHandler::readUint32($data, $offset);
            $offset += 4;
            $duration = BinaryHandler::readUint64($data, $offset);
            $offset += 8;
        } else {
            $creationTime = BinaryHandler::readUint32($data, $offset);
            $offset += 4;
            $modificationTime = BinaryHandler::readUint32($data, $offset);
            $offset += 4;
            $timescale = BinaryHandler::readUint32($data, $offset);
            $offset += 4;
            $duration = BinaryHandler::readUint32($data, $offset);
            $offset += 4;
        }

        // Decode packed 5-bit ISO 639-2/T language code
        $langRaw = BinaryHandler::readUint16($data, $offset);
        $lang = '';
        $lang .= chr((($langRaw >> 10) & 0x1F) + 0x60);
        $lang .= chr((($langRaw >> 5) & 0x1F) + 0x60);
        $lang .= chr(($langRaw & 0x1F) + 0x60);

        return [
            'version' => $version,
            'creation_time' => $creationTime,
            'modification_time' => $modificationTime,
            'timescale' => $timescale,
            'duration' => $duration,
            'duration_seconds' => $timescale > 0 ? $duration / $timescale : 0,
            'language' => $lang,
        ];
    }

    /**
     * Parses the hdlr (Handler Reference) box to identify the media handler type and name.
     * 
     * @param string $data
     * @return array{handler_type: string, handler_type_name: mixed, name: string}
     */
    public static function parseHdlr(string $data): array
    {
        $offset = 8; // skip version/flags (4) + pre_defined (4)
        $handlerType = substr($data, $offset, 4);
        $offset += 4;
        $offset += 12; // reserved
        $name = rtrim(substr($data, $offset), "\0");

        return [
            'handler_type' => $handlerType,
            'handler_type_name' => self::$handlerTypeMap[$handlerType] ?? $handlerType,
            'name' => $name,
        ];
    }

    /**
     * Parses the stsd (Sample Description) box.
     * For video entries, extracts codec, dimensions, and optional avcC/hvcC/colr/pasp sub-boxes.
     * For audio entries, extracts codec, channel count, sample rate, and optional esds sub-box.
     * 
     * @param string $data
     * @param string $handlerType
     * 
     * @return array
     */
    public static function parseStsd(string $data, string $handlerType): array
    {
        $offset = 4; // skip version/flags
        $entryCount = BinaryHandler::readUint32($data, $offset);
        $offset += 4;

        $entries = [];

        for ($i = 0; $i < $entryCount && $offset + 8 <= strlen($data); $i++) {
            $entrySize = BinaryHandler::readUint32($data, $offset);
            $entryFormat = substr($data, $offset + 4, 4);

            $entry = [
                'format' => $entryFormat,
                'size' => $entrySize,
            ];

            if ($handlerType === 'vide' && $offset + 78 <= strlen($data)) {
                $entry['codec'] = $entryFormat;
                $entry['codec_name'] = self::$videoCodecMap[$entryFormat] ?? $entryFormat;
                $entry['data_reference_index'] = BinaryHandler::readUint16($data, $offset + 14);
                $entry['width'] = BinaryHandler::readUint16($data, $offset + 32);
                $entry['height'] = BinaryHandler::readUint16($data, $offset + 34);
                $entry['horizontal_resolution'] = BinaryHandler::readFixedPoint1616($data, $offset + 36);
                $entry['vertical_resolution'] = BinaryHandler::readFixedPoint1616($data, $offset + 40);
                $entry['frame_count'] = BinaryHandler::readUint16($data, $offset + 48);

                $compressorNameLen = ord($data[$offset + 50]);
                if ($compressorNameLen > 0 && $compressorNameLen <= 31) {
                    $entry['compressor_name'] = substr($data, $offset + 51, $compressorNameLen);
                }

                $entry['depth'] = BinaryHandler::readUint16($data, $offset + 82);

                if ($entrySize > 86 && $offset + $entrySize <= strlen($data)) {
                    $innerOffset = $offset + 86;
                    while ($innerOffset + 8 <= $offset + $entrySize) {
                        $innerSize = BinaryHandler::readUint32($data, $innerOffset);
                        $innerType = substr($data, $innerOffset + 4, 4);

                        if ($innerSize < 8) {
                            break;
                        }

                        if ($innerType === 'avcC' && $innerOffset + 12 <= strlen($data)) {
                            $avcOffset = $innerOffset + 8;
                            $entry['avc_profile'] = ord($data[$avcOffset + 1]);
                            $entry['avc_compatibility'] = ord($data[$avcOffset + 2]);
                            $entry['avc_level'] = ord($data[$avcOffset + 3]);
                            $entry['avcc_data'] = substr($data, $innerOffset + 8, $innerSize - 8);
                        } elseif ($innerType === 'hvcC' && $innerOffset + 12 <= strlen($data)) {
                            $hevcOffset = $innerOffset + 8;
                            $entry['hevc_general_profile_idc'] = ord($data[$hevcOffset + 1]) & 0x1F;
                            $entry['hevc_general_level_idc'] = ord($data[$hevcOffset + 12]);
                            $entry['hvcc_data'] = substr($data, $innerOffset + 8, $innerSize - 8);
                        } elseif ($innerType === 'colr' && $innerOffset + 18 <= strlen($data)) {
                            $colrType = substr($data, $innerOffset + 8, 4);
                            if ($colrType === 'nclx' || $colrType === 'nclc') {
                                $entry['color_primaries'] = BinaryHandler::readUint16($data, $innerOffset + 12);
                                $entry['transfer_characteristics'] = BinaryHandler::readUint16($data, $innerOffset + 14);
                                $entry['matrix_coefficients'] = BinaryHandler::readUint16($data, $innerOffset + 16);
                            }
                        } elseif ($innerType === 'pasp' && $innerOffset + 16 <= strlen($data)) {
                            $entry['pixel_aspect_ratio_h'] = BinaryHandler::readUint32($data, $innerOffset + 8);
                            $entry['pixel_aspect_ratio_v'] = BinaryHandler::readUint32($data, $innerOffset + 12);
                        }

                        $innerOffset += $innerSize;
                    }
                }
            } elseif ($handlerType === 'soun' && $offset + 36 <= strlen($data)) {
                $entry['codec'] = $entryFormat;
                $entry['codec_name'] = self::$audioCodecMap[$entryFormat] ?? $entryFormat;
                $entry['data_reference_index'] = BinaryHandler::readUint16($data, $offset + 14);
                $entry['num_channels'] = BinaryHandler::readUint16($data, $offset + 24);
                $entry['sample_size'] = BinaryHandler::readUint16($data, $offset + 26);
                // sample_rate is stored as 16.16 fixed-point; upper 16 bits hold the integer Hz value
                $entry['sample_rate'] = BinaryHandler::readUint32($data, $offset + 32) >> 16;

                if ($entrySize > 36 && $offset + $entrySize <= strlen($data)) {
                    $innerOffset = $offset + 36;
                    while ($innerOffset + 8 <= $offset + $entrySize) {
                        $innerSize = BinaryHandler::readUint32($data, $innerOffset);
                        $innerType = substr($data, $innerOffset + 4, 4);

                        if ($innerSize < 8) {
                            break;
                        }

                        if ($innerType === 'esds' && $innerSize > 12) {
                            $esdsPayloadOffset = $innerOffset + 12;
                            $esdsPayloadLen = $innerSize - 12;
                            if ($esdsPayloadOffset + $esdsPayloadLen <= strlen($data)) {
                                $entry['esds_data'] = substr($data, $esdsPayloadOffset, $esdsPayloadLen);
                            }
                            $esdsOffset = $innerOffset + 12;
                            if ($esdsOffset + 2 < $offset + $entrySize) {
                                $entry['esds_object_type'] = ord($data[$esdsOffset]) >> 3;
                            }
                        } elseif ($innerType === 'dac3' || $innerType === 'dec3') {
                            $entry['dolby_audio'] = true;
                        }

                        $innerOffset += $innerSize;
                    }
                }
            }

            $entries[] = $entry;
            $offset += $entrySize;
        }

        return $entries;
    }

    /**
     * Parses the stts (Time-to-Sample) box, aggregating total sample count and total tick duration.
     * 
     * @param string $data
     * @return array{entries: array, total_delta: int, total_samples: int}
     */
    public static function parseStts(string $data): array
    {
        $offset = 4; // skip version/flags
        $entryCount = BinaryHandler::readUint32($data, $offset);
        $offset += 4;

        $entries = [];
        $totalSamples = 0;
        $totalDelta = 0;

        for ($i = 0; $i < $entryCount && $offset + 8 <= strlen($data); $i++) {
            $sampleCount = BinaryHandler::readUint32($data, $offset);
            $sampleDelta = BinaryHandler::readUint32($data, $offset + 4);
            $entries[] = [
                'sample_count' => $sampleCount,
                'sample_delta' => $sampleDelta,
            ];
            $totalSamples += $sampleCount;
            $totalDelta += $sampleCount * $sampleDelta;
            $offset += 8;
        }

        return [
            'entries' => $entries,
            'total_samples' => $totalSamples,
            'total_delta' => $totalDelta,
        ];
    }

    /**
     * Iterates iTunes metadata atoms inside an ilst box.
     * Each atom contains one or more 'data' child atoms; cover art is separated from textual tags.
     * 
     * @param resource $fh
     * @param int $dataSize
     * @return array
     */
    public static function parseItunesMeta(mixed $fh, int $dataSize): array
    {
        $meta = [];
        $endPos = ftell($fh) + $dataSize;

        while (ftell($fh) < $endPos) {
            $box = self::readBoxHeader($fh);
            if ($box === null || $box['data_size'] <= 0) {
                break;
            }

            $key = $box['type'];
            $mappedKey = self::$itunesKeyMap[$key] ?? null;
            $itemEndPos = $box['data_offset'] + $box['data_size'];

            $value = null;
            $coverArt = null;

            while (ftell($fh) < $itemEndPos) {
                $dataBox = self::readBoxHeader($fh);
                if ($dataBox === null || $dataBox['type'] !== 'data') {
                    if ($dataBox !== null) {
                        fseek($fh, $dataBox['data_offset'] + $dataBox['data_size']);
                    }
                    continue;
                }

                $dataContent = fread($fh, $dataBox['data_size']);
                if ($dataContent === false || strlen($dataContent) < 8) {
                    break;
                }

                // data atom layout: 4-byte type indicator, 4-byte locale, then payload
                $typeIndicator = BinaryHandler::readUint32($dataContent, 0);
                $locale = BinaryHandler::readUint32($dataContent, 4);
                $payload = substr($dataContent, 8);

                if ($key === 'covr') {
                    $mime = 'image/jpeg';
                    if ($typeIndicator === 14) {
                        $mime = 'image/png';
                    }
                    $coverArt = [
                        'mime' => $mime,
                        'type' => 3,
                        'type_name' => 'Cover (front)',
                        'data' => $payload,
                    ];
                } elseif ($typeIndicator === 1) {
                    // UTF-8 string
                    $value = rtrim($payload, "\0");
                } elseif ($typeIndicator === 0 || $typeIndicator === 21) {
                    // Binary / integer
                    if (strlen($payload) === 1) {
                        $value = ord($payload);
                    } elseif (strlen($payload) === 2) {
                        $value = BinaryHandler::readUint16($payload, 0);
                    } elseif (strlen($payload) === 4) {
                        $value = BinaryHandler::readUint32($payload, 0);
                    } elseif (strlen($payload) >= 8 && ($key === 'trkn' || $key === 'disk')) {
                        $value = [
                            'number' => BinaryHandler::readUint16($payload, 2),
                            'total' => BinaryHandler::readUint16($payload, 4),
                        ];
                    } else {
                        $value = bin2hex($payload);
                    }
                } else {
                    $value = rtrim($payload, "\0");
                }
            }

            if ($coverArt !== null) {
                $meta['cover_art'] = $coverArt;
            } elseif ($value !== null && $mappedKey !== null) {
                $meta[$mappedKey] = $value;
            } elseif ($value !== null) {
                $meta[$key] = $value;
            }

            fseek($fh, $itemEndPos);
        }

        return $meta;
    }

    /**
     * Recursively scans ISO base media boxes up to $endPos, descending into known container box types.
     * 
     * @param resource $fh
     * @param int $endPos
     * @param int $depth
     * @return array<array[]|array{data_offset: bool|int, data_size: int, header_size: int, size: int, type: string}>
     */
    public static function scanBoxes(mixed $fh, int $endPos, int $depth = 0): array
    {
        $boxes = [];

        while (ftell($fh) < $endPos) {
            $box = self::readBoxHeader($fh);
            if ($box === null || $box['size'] < 8) {
                break;
            }

            $boxEnd = $box['data_offset'] + $box['data_size'];
            $box['depth'] = $depth;

            $containerTypes = ['moov', 'trak', 'mdia', 'minf', 'stbl', 'dinf', 'edts', 'udta', 'sinf', 'schi'];
            if (in_array($box['type'], $containerTypes)) {
                $box['children'] = self::scanBoxes($fh, $boxEnd, $depth + 1);
            }

            $boxes[] = $box;

            if (ftell($fh) < $boxEnd) {
                fseek($fh, $boxEnd);
            }
        }

        return $boxes;
    }

    /**
     * Main entry point. Parses ftyp and moov boxes to extract all available metadata,
     * including file type, movie header, per-track info, iTunes tags, and cover art.
     *
     * @param string $file
     * @return array{meta: array, apicData: ?array, apicFound: bool}
     */
    public static function getMetaTags(string $file): array
    {
        $fh = fopen($file, 'rb');
        if ($fh === false) {
            throw new Exception("Cannot open file: $file");
        }

        fseek($fh, 0, SEEK_END);
        $fileSize = ftell($fh);
        fseek($fh, 0);

        $ftyp = null;
        $mvhd = null;
        $tracks = [];
        $itunesMeta = [];
        $coverArt = null;

        while (ftell($fh) < $fileSize) {
            $box = self::readBoxHeader($fh);
            if ($box === null) {
                break;
            }

            $boxEnd = $box['data_offset'] + $box['data_size'];

            switch ($box['type']) {
                case 'ftyp':
                    $data = fread($fh, $box['data_size']);
                    if ($data !== false) {
                        $ftyp = self::parseFtyp($data);
                    }
                    break;

                case 'moov':
                    self::parseMoovBox($fh, $boxEnd, $mvhd, $tracks, $itunesMeta);
                    break;

                default:
                    fseek($fh, $boxEnd);
                    break;
            }
        }

        fclose($fh);

        $meta = [];

        if ($ftyp !== null) {
            $meta['ftyp'] = $ftyp;
        }

        if ($mvhd !== null) {
            $meta['duration'] = $mvhd['duration_seconds'];
            $secs = (int) round($mvhd['duration_seconds']);
            $meta['duration_human'] = sprintf('%d:%02d', intdiv($secs, 60), $secs % 60);
            $meta['timescale'] = $mvhd['timescale'];
            $meta['creation_time'] = $mvhd['creation_time'];
            $meta['modification_time'] = $mvhd['modification_time'];
        }

        $videoTracks = [];
        $audioTracks = [];

        foreach ($tracks as $track) {
            if (($track['handler_type'] ?? '') === 'vide') {
                $videoTracks[] = $track;
            } elseif (($track['handler_type'] ?? '') === 'soun') {
                $audioTracks[] = $track;
            }
        }

        if (!empty($videoTracks)) {
            $vt = $videoTracks[0];
            $meta['video'] = [
                'codec' => $vt['stsd'][0]['codec'] ?? null,
                'codec_name' => $vt['stsd'][0]['codec_name'] ?? null,
                'width' => $vt['tkhd']['width'] ?? $vt['stsd'][0]['width'] ?? null,
                'height' => $vt['tkhd']['height'] ?? $vt['stsd'][0]['height'] ?? null,
                'depth' => $vt['stsd'][0]['depth'] ?? null,
                'duration' => $vt['mdhd']['duration_seconds'] ?? null,
                'language' => $vt['mdhd']['language'] ?? null,
            ];

            if (isset($vt['stsd'][0]['avc_profile'])) {
                $meta['video']['avc_profile'] = $vt['stsd'][0]['avc_profile'];
                $meta['video']['avc_level'] = $vt['stsd'][0]['avc_level'] ?? null;
            }
            if (isset($vt['stsd'][0]['hevc_general_profile_idc'])) {
                $meta['video']['hevc_profile'] = $vt['stsd'][0]['hevc_general_profile_idc'];
                $meta['video']['hevc_level'] = $vt['stsd'][0]['hevc_general_level_idc'] ?? null;
            }
            if (isset($vt['stsd'][0]['pixel_aspect_ratio_h'])) {
                $meta['video']['pixel_aspect_ratio'] = $vt['stsd'][0]['pixel_aspect_ratio_h'] . ':' . $vt['stsd'][0]['pixel_aspect_ratio_v'];
            }

            // Frame rate = total sample count / media duration in seconds
            if (isset($vt['stts']) && isset($vt['mdhd']['timescale']) && $vt['mdhd']['timescale'] > 0) {
                $totalSamples = $vt['stts']['total_samples'];
                $durationSec = $vt['mdhd']['duration_seconds'];
                if ($durationSec > 0 && $totalSamples > 0) {
                    $meta['video']['frame_rate'] = round($totalSamples / $durationSec, 3);
                }
            }
        }

        if (!empty($audioTracks)) {
            $at = $audioTracks[0];
            $meta['audio'] = [
                'codec' => $at['stsd'][0]['codec'] ?? null,
                'codec_name' => $at['stsd'][0]['codec_name'] ?? null,
                'num_channels' => $at['stsd'][0]['num_channels'] ?? null,
                'sample_rate' => $at['stsd'][0]['sample_rate'] ?? null,
                'sample_size' => $at['stsd'][0]['sample_size'] ?? null,
                'duration' => $at['mdhd']['duration_seconds'] ?? null,
                'language' => $at['mdhd']['language'] ?? null,
            ];
        }

        $meta['tracks'] = $tracks;

        if (isset($itunesMeta['cover_art'])) {
            $coverArt = $itunesMeta['cover_art'];
            unset($itunesMeta['cover_art']);
        }

        if (!empty($itunesMeta)) {
            $meta['itunes'] = $itunesMeta;
        }

        if ($fileSize > 0 && isset($meta['duration']) && $meta['duration'] > 0) {
            $meta['overall_bitrate'] = (int) round(($fileSize * 8) / $meta['duration']);
        }

        ksort($meta);

        return [
            'meta' => $meta,
            'apicData' => $coverArt,
            'apicFound' => $coverArt !== null,
        ];
    }

    /**
     * Parse the moov (Movie) box and its sub-boxes.
     *
     * @param resource $fh File handler.
     * @param int $moovEnd End position of the moov box.
     * @param array|null &$mvhd Reference to store the mvhd (Movie Header) box data.
     * @param array &$tracks Reference to store the trak (Track) boxes data.
     * @param array &$itunesMeta Reference to store the extracted iTunes metadata.
     * @return void
     */
    private static function parseMoovBox($fh, int $moovEnd, ?array &$mvhd, array &$tracks, array &$itunesMeta): void
    {
        while (ftell($fh) < $moovEnd) {
            $box = self::readBoxHeader($fh);
            if ($box === null) {
                break;
            }

            $boxEnd = $box['data_offset'] + $box['data_size'];

            switch ($box['type']) {
                case 'mvhd':
                    $data = fread($fh, $box['data_size']);
                    if ($data !== false) {
                        $mvhd = self::parseMvhd($data);
                    }
                    break;

                case 'trak':
                    $track = self::parseTrakBox($fh, $boxEnd);
                    if ($track !== null) {
                        $tracks[] = $track;
                    }
                    break;

                case 'udta':
                    self::parseUdtaBox($fh, $boxEnd, $itunesMeta);
                    break;

                default:
                    fseek($fh, $boxEnd);
                    break;
            }
        }
    }

    /**
     * Parse a trak (Track) box and its sub-boxes.
     *
     * @param resource $fh File handler.
     * @param int $trakEnd End position of the trak box.
     * @return array|null Parsed track box data, or null if empty.
     */
    private static function parseTrakBox(mixed $fh, int $trakEnd): ?array
    {
        $track = [];

        while (ftell($fh) < $trakEnd) {
            $box = self::readBoxHeader($fh);
            if ($box === null) {
                break;
            }

            $boxEnd = $box['data_offset'] + $box['data_size'];

            switch ($box['type']) {
                case 'tkhd':
                    $data = fread($fh, $box['data_size']);
                    if ($data !== false) {
                        $track['tkhd'] = self::parseTkhd($data);
                    }
                    break;

                case 'mdia':
                    self::parseMdiaBox($fh, $boxEnd, $track);
                    break;

                default:
                    fseek($fh, $boxEnd);
                    break;
            }
        }

        return !empty($track) ? $track : null;
    }

    /**
     * Parse an mdia (Media) box.
     *
     * @param resource $fh File handler.
     * @param int $mdiaEnd End position of the mdia box.
     * @param array &$track Reference to the parent track data array.
     * @return void
     */
    private static function parseMdiaBox(mixed $fh, int $mdiaEnd, array &$track): void
    {
        while (ftell($fh) < $mdiaEnd) {
            $box = self::readBoxHeader($fh);
            if ($box === null) {
                break;
            }

            $boxEnd = $box['data_offset'] + $box['data_size'];

            switch ($box['type']) {
                case 'mdhd':
                    $data = fread($fh, $box['data_size']);
                    if ($data !== false) {
                        $track['mdhd'] = self::parseMdhd($data);
                    }
                    break;

                case 'hdlr':
                    $data = fread($fh, $box['data_size']);
                    if ($data !== false) {
                        $hdlr = self::parseHdlr($data);
                        $track['handler_type'] = $hdlr['handler_type'];
                        $track['handler_type_name'] = $hdlr['handler_type_name'];
                        $track['handler_name'] = $hdlr['name'];
                    }
                    break;

                case 'minf':
                    self::parseMinfBox($fh, $boxEnd, $track);
                    break;

                default:
                    fseek($fh, $boxEnd);
                    break;
            }
        }
    }

    /**
     * Parse a minf (Media Information) box.
     *
     * @param resource $fh File handler.
     * @param int $minfEnd End position of the minf box.
     * @param array &$track Reference to the parent track data array.
     * @return void
     */
    private static function parseMinfBox(mixed $fh, int $minfEnd, array &$track): void
    {
        while (ftell($fh) < $minfEnd) {
            $box = self::readBoxHeader($fh);
            if ($box === null) {
                break;
            }

            $boxEnd = $box['data_offset'] + $box['data_size'];

            if ($box['type'] === 'stbl') {
                self::parseStblBox($fh, $boxEnd, $track);
            } else {
                fseek($fh, $boxEnd);
            }
        }
    }

    /**
     * Parse an stbl (Sample Table) box.
     *
     * @param resource $fh File handler.
     * @param int $stblEnd End position of the stbl box.
     * @param array &$track Reference to the parent track data array.
     * @return void
     */
    private static function parseStblBox(mixed $fh, int $stblEnd, array &$track): void
    {
        $handlerType = $track['handler_type'] ?? '';

        while (ftell($fh) < $stblEnd) {
            $box = self::readBoxHeader($fh);
            if ($box === null) {
                break;
            }

            $boxEnd = $box['data_offset'] + $box['data_size'];

            switch ($box['type']) {
                case 'stsd':
                    $data = fread($fh, $box['data_size']);
                    if ($data !== false) {
                        $track['stsd'] = self::parseStsd($data, $handlerType);
                        $track['stsd_raw'] = $data;
                    }
                    break;

                case 'stts':
                    $data = fread($fh, $box['data_size']);
                    if ($data !== false) {
                        $track['stts'] = self::parseStts($data);
                    }
                    break;

                case 'stss':
                    $data = fread($fh, $box['data_size']);
                    if ($data !== false) {
                        $track['stss'] = self::parseStss($data);
                    }
                    break;

                case 'stsz':
                    $data = fread($fh, $box['data_size']);
                    if ($data !== false) {
                        $track['stsz'] = self::parseStsz($data);
                    }
                    break;

                case 'stsc':
                    $data = fread($fh, $box['data_size']);
                    if ($data !== false) {
                        $track['stsc'] = self::parseStsc($data);
                    }
                    break;

                case 'stco':
                    $data = fread($fh, $box['data_size']);
                    if ($data !== false) {
                        $track['stco'] = self::parseStco($data);
                    }
                    break;

                case 'co64':
                    $data = fread($fh, $box['data_size']);
                    if ($data !== false) {
                        $track['stco'] = self::parseCo64($data);
                    }
                    break;

                case 'ctts':
                    $data = fread($fh, $box['data_size']);
                    if ($data !== false) {
                        $track['ctts'] = self::parseCtts($data);
                    }
                    break;

                default:
                    fseek($fh, $boxEnd);
                    break;
            }
        }
    }

    /**
     * Parse an udta (User Data) box.
     *
     * @param resource $fh File handler.
     * @param int $udtaEnd End position of the udta box.
     * @param array &$itunesMeta Reference to store iTunes metadata.
     * @return void
     */
    private static function parseUdtaBox(mixed $fh, int $udtaEnd, array &$itunesMeta): void
    {
        while (ftell($fh) < $udtaEnd) {
            $box = self::readBoxHeader($fh);
            if ($box === null) {
                break;
            }

            $boxEnd = $box['data_offset'] + $box['data_size'];

            if ($box['type'] === 'meta') {
                fread($fh, 4); // skip version/flags (FullBox header)
                self::parseMetaBox($fh, $boxEnd, $itunesMeta);
            } else {
                fseek($fh, $boxEnd);
            }
        }
    }

    /**
     * Parse a meta (Metadata) box.
     *
     * @param resource $fh File handler.
     * @param int $metaEnd End position of the meta box.
     * @param array &$itunesMeta Reference to store iTunes metadata.
     * @return void
     */
    private static function parseMetaBox(mixed $fh, int $metaEnd, array &$itunesMeta): void
    {
        while (ftell($fh) < $metaEnd) {
            $box = self::readBoxHeader($fh);
            if ($box === null) {
                break;
            }

            $boxEnd = $box['data_offset'] + $box['data_size'];

            if ($box['type'] === 'ilst') {
                $itunesMeta = array_merge($itunesMeta, self::parseItunesMeta($fh, $box['data_size']));
            } else {
                fseek($fh, $boxEnd);
            }
        }
    }

    /**
     * Get the title from the MP4 file metadata.
     *
     * @param string $file Path to the file.
     * @return string|null Title or null if not found.
     */
    public static function getTitle(string $file): ?string
    {
        $tags = self::getMetaTags($file);

        return $tags['meta']['itunes']['title'] ?? null;
    }

    /**
     * Get the artist from the MP4 file metadata.
     *
     * @param string $file Path to the file.
     * @return string|null Artist or null if not found.
     */
    public static function getArtist(string $file): ?string
    {
        $tags = self::getMetaTags($file);

        return $tags['meta']['itunes']['artist'] ?? null;
    }

    /**
     * Get the album from the MP4 file metadata.
     *
     * @param string $file Path to the file.
     * @return string|null Album or null if not found.
     */
    public static function getAlbum(string $file): ?string
    {
        $tags = self::getMetaTags($file);

        return $tags['meta']['itunes']['album'] ?? null;
    }

    /**
     * Get the year/date from the MP4 file metadata.
     *
     * @param string $file Path to the file.
     * @return string|null Year/Date or null if not found.
     */
    public static function getYear(string $file): ?string
    {
        $tags = self::getMetaTags($file);

        return $tags['meta']['itunes']['year'] ?? null;
    }

    /**
     * Get the comment from the MP4 file metadata.
     *
     * @param string $file Path to the file.
     * @return string|null Comment or null if not found.
     */
    public static function getComment(string $file): ?string
    {
        $tags = self::getMetaTags($file);

        return $tags['meta']['itunes']['comment'] ?? null;
    }

    /**
     * Get the duration of the MP4 file in seconds.
     *
     * @param string $file Path to the file.
     * @return float|null Duration in seconds or null if not available.
     */
    public static function getDuration(string $file): ?float
    {
        $tags = self::getMetaTags($file);

        return $tags['meta']['duration'] ?? null;
    }

    /**
     * Get the human-readable duration of the MP4 file (e.g., M:SS).
     *
     * @param string $file Path to the file.
     * @return string|null Formatted duration or null if not available.
     */
    public static function getDurationHuman(string $file): ?string
    {
        $tags = self::getMetaTags($file);

        return $tags['meta']['duration_human'] ?? null;
    }

    /**
     * Get the primary video codec name.
     *
     * @param string $file Path to the file.
     * @return string|null Video codec name or null if not available.
     */
    public static function getVideoCodec(string $file): ?string
    {
        $tags = self::getMetaTags($file);

        return $tags['meta']['video']['codec_name'] ?? null;
    }

    /**
     * Get the primary audio codec name.
     *
     * @param string $file Path to the file.
     * @return string|null Audio codec name or null if not available.
     */
    public static function getAudioCodec(string $file): ?string
    {
        $tags = self::getMetaTags($file);

        return $tags['meta']['audio']['codec_name'] ?? null;
    }

    /**
     * Get the video resolution.
     *
     * @param string $file Path to the file.
     * @return array|null Array containing 'width' and 'height' or null if not available.
     */
    public static function getResolution(string $file): ?array
    {
        $tags = self::getMetaTags($file);

        if (!isset($tags['meta']['video']['width']) || !isset($tags['meta']['video']['height'])) {
            return null;
        }

        return [
            'width' => $tags['meta']['video']['width'],
            'height' => $tags['meta']['video']['height'],
        ];
    }

    /**
     * Get the frame rate of the primary video track.
     *
     * @param string $file Path to the file.
     * @return float|null Frame rate in fps or null if not available.
     */
    public static function getFrameRate(string $file): ?float
    {
        $tags = self::getMetaTags($file);

        return $tags['meta']['video']['frame_rate'] ?? null;
    }

    /**
     * Get the primary album/cover art from the MP4 file.
     *
     * @param string $file Path to the file.
     * @return array|null Array containing picture data or null if not found.
     */
    public static function getAlbumArt(string $file): ?array
    {
        $tags = self::getMetaTags($file);

        return $tags['apicFound'] ? $tags['apicData'] : null;
    }

    /**
     * Save the primary album/cover art to the specified path.
     *
     * @param string $file Path to the MP4 file.
     * @param string $outputPath Path to save the image.
     * @return bool True if saved successfully, false otherwise.
     */
    public static function saveAlbumArt(string $file, string $outputPath): bool
    {
        $art = self::getAlbumArt($file);

        if ($art === null || empty($art['data'])) {
            return false;
        }

        return file_put_contents($outputPath, $art['data']) !== false;
    }

    /**
     * Get the primary album/cover art as a Base64 encoded string.
     *
     * @param string $file Path to the file.
     * @return string|null Base64 data URI or null if not found.
     */
    public static function getAlbumArtBase64(string $file): ?string
    {
        $art = self::getAlbumArt($file);

        if ($art === null || empty($art['data'])) {
            return null;
        }

        return 'data:' . $art['mime'] . ';base64,' . base64_encode($art['data']);
    }

    /**
     * Parse the stss (Sync Sample) box to identify keyframe sample numbers.
     *
     * @param string $data Raw box data.
     * @return array Array of 1-based sync sample numbers.
     */
    public static function parseStss(string $data): array
    {
        $offset = 4;
        $entryCount = BinaryHandler::readUint32($data, $offset);
        $offset += 4;

        $syncSamples = [];
        for ($i = 0; $i < $entryCount && $offset + 4 <= strlen($data); $i++) {
            $syncSamples[] = BinaryHandler::readUint32($data, $offset);
            $offset += 4;
        }

        return $syncSamples;
    }

    /**
     * Parse the stsz (Sample Size) box to get per-sample byte sizes.
     *
     * @param string $data Raw box data.
     * @return array{sample_size: int, sample_count: int, entries: int[]}
     */
    public static function parseStsz(string $data): array
    {
        $offset = 4;
        $sampleSize = BinaryHandler::readUint32($data, $offset);
        $offset += 4;
        $sampleCount = BinaryHandler::readUint32($data, $offset);
        $offset += 4;

        $entries = [];
        if ($sampleSize === 0) {
            for ($i = 0; $i < $sampleCount && $offset + 4 <= strlen($data); $i++) {
                $entries[] = BinaryHandler::readUint32($data, $offset);
                $offset += 4;
            }
        }

        return [
            'sample_size' => $sampleSize,
            'sample_count' => $sampleCount,
            'entries' => $entries,
        ];
    }

    /**
     * Parse the stsc (Sample-to-Chunk) box.
     *
     * @param string $data Raw box data.
     * @return array[] Each entry: {first_chunk, samples_per_chunk, sample_description_index}
     */
    public static function parseStsc(string $data): array
    {
        $offset = 4;
        $entryCount = BinaryHandler::readUint32($data, $offset);
        $offset += 4;

        $entries = [];
        for ($i = 0; $i < $entryCount && $offset + 12 <= strlen($data); $i++) {
            $entries[] = [
                'first_chunk' => BinaryHandler::readUint32($data, $offset),
                'samples_per_chunk' => BinaryHandler::readUint32($data, $offset + 4),
                'sample_description_index' => BinaryHandler::readUint32($data, $offset + 8),
            ];
            $offset += 12;
        }

        return $entries;
    }

    /**
     * Parse the stco (Chunk Offset) box with 32-bit offsets.
     *
     * @param string $data Raw box data.
     * @return int[] Array of chunk file offsets.
     */
    public static function parseStco(string $data): array
    {
        $offset = 4;
        $entryCount = BinaryHandler::readUint32($data, $offset);
        $offset += 4;

        $offsets = [];
        for ($i = 0; $i < $entryCount && $offset + 4 <= strlen($data); $i++) {
            $offsets[] = BinaryHandler::readUint32($data, $offset);
            $offset += 4;
        }

        return $offsets;
    }

    /**
     * Parse the co64 (Chunk Offset 64-bit) box.
     *
     * @param string $data Raw box data.
     * @return int[] Array of chunk file offsets.
     */
    public static function parseCo64(string $data): array
    {
        $offset = 4;
        $entryCount = BinaryHandler::readUint32($data, $offset);
        $offset += 4;

        $offsets = [];
        for ($i = 0; $i < $entryCount && $offset + 8 <= strlen($data); $i++) {
            $offsets[] = BinaryHandler::readUint64($data, $offset);
            $offset += 8;
        }

        return $offsets;
    }

    /**
     * Parse the ctts (Composition Time to Sample) box.
     *
     * @param string $data Raw box data.
     * @return array{sample_count: int, sample_offset: int} Each entry: {sample_count, sample_offset}
     */
    public static function parseCtts(string $data): array
    {
        $version = ord($data[0]);
        $offset = 4;
        $entryCount = BinaryHandler::readUint32($data, $offset);
        $offset += 4;

        $entries = [];
        for ($i = 0; $i < $entryCount && $offset + 8 <= strlen($data); $i++) {
            $sampleCount = BinaryHandler::readUint32($data, $offset);
            $raw = BinaryHandler::readUint32($data, $offset + 4);
            $sampleOffset = ($version === 1 && ($raw & 0x80000000)) ? ($raw - 4294967296) : $raw;
            $entries[] = [
                'sample_count' => $sampleCount,
                'sample_offset' => $sampleOffset,
            ];
            $offset += 8;
        }

        return $entries;
    }

    /**
     * Convert a timestamp in seconds to a 1-based sample number using stts entries.
     *
     * @param array $stts Parsed stts data with 'entries' array.
     * @param int $timescale Media timescale from mdhd.
     * @param float $timeSeconds Target time in seconds.
     * @return int 1-based sample number.
     */
    public static function timestampToSampleNumber(array $stts, int $timescale, float $timeSeconds): int
    {
        $targetTicks = (int) round($timeSeconds * $timescale);
        $currentTick = 0;
        $currentSample = 1;

        foreach ($stts['entries'] as $entry) {
            $entryTotalTicks = $entry['sample_count'] * $entry['sample_delta'];
            if ($currentTick + $entryTotalTicks > $targetTicks) {
                $remaining = $targetTicks - $currentTick;
                $samplesForward = ($entry['sample_delta'] > 0) ? intdiv($remaining, $entry['sample_delta']) : 0;
                return $currentSample + $samplesForward;
            }

            $currentTick += $entryTotalTicks;
            $currentSample += $entry['sample_count'];
        }

        return max(1, $currentSample - 1);
    }

    /**
     * Find the nearest sync (keyframe) sample at or before the given sample number.
     * Returns the sample number itself if no stss box exists (all samples are sync).
     *
     * @param array|null $stss Array of sync sample numbers, or null.
     * @param int $sampleNumber 1-based sample number.
     * @return int 1-based nearest sync sample number.
     */
    public static function findNearestSyncSample(?array $stss, int $sampleNumber): int
    {
        if ($stss === null || empty($stss)) {
            return $sampleNumber;
        }

        $result = $stss[0];
        foreach ($stss as $sync) {
            if ($sync > $sampleNumber) {
                break;
            }

            $result = $sync;
        }

        return $result;
    }

    /**
     * Resolve the file offset and byte size for a given 1-based sample number
     * using the stsc, stco, and stsz tables.
     *
     * @param array $stsc Parsed stsc entries.
     * @param array $stco Parsed chunk offset array.
     * @param array $stsz Parsed stsz data.
     * @param int $sampleNumber 1-based target sample number.
     * @return array{offset: int, size: int}|null File offset and size, or null on failure.
     */
    public static function resolveSampleOffset(array $stsc, array $stco, array $stsz, int $sampleNumber): ?array
    {
        $sampleSize = ($stsz['sample_size'] > 0) ? $stsz['sample_size'] : ($stsz['entries'][$sampleNumber - 1] ?? null);

        if ($sampleSize === null) {
            return null;
        }

        $currentSample = 1;
        $chunkCount = count($stco);

        for ($i = 0; $i < count($stsc); $i++) {
            $firstChunk = $stsc[$i]['first_chunk'];
            $samplesPerChunk = $stsc[$i]['samples_per_chunk'];
            $lastChunk = (isset($stsc[$i + 1])) ? $stsc[$i + 1]['first_chunk'] - 1 : $chunkCount;

            for ($chunk = $firstChunk; $chunk <= $lastChunk; $chunk++) {
                if ($currentSample + $samplesPerChunk > $sampleNumber) {
                    $sampleInChunk = $sampleNumber - $currentSample;
                    $chunkOffset = $stco[$chunk - 1] ?? null;
                    if ($chunkOffset === null) {
                        return null;
                    }

                    $offsetInChunk = 0;
                    for ($s = 0; $s < $sampleInChunk; $s++) {
                        $prevSampleNum = $currentSample + $s;
                        $prevSize = ($stsz['sample_size'] > 0) ? $stsz['sample_size'] : ($stsz['entries'][$prevSampleNum - 1] ?? 0);
                        $offsetInChunk += $prevSize;
                    }

                    return [
                        'offset' => $chunkOffset + $offsetInChunk,
                        'size' => $sampleSize,
                    ];
                }
                $currentSample += $samplesPerChunk;
            }
        }

        return null;
    }

    /**
     * Find the first video track from parsed metadata.
     *
     * @param array $tags Parsed metadata from getMetaTags().
     * @return array|null The video track data, or null if none found.
     */
    private static function findVideoTrack(array $tags): ?array
    {
        foreach ($tags['meta']['tracks'] ?? [] as $track) {
            if (($track['handler_type'] ?? '') === 'vide') {
                return $track;
            }
        }

        return null;
    }

    /**
     * Extract raw compressed frame data at the specified time position.
     * For MJPEG/JPEG codecs the returned data is a valid JPEG image.
     * For H.264/HEVC the returned data contains raw NAL units of the nearest keyframe.
     *
     * @param string $file Path to the MP4 file.
     * @param float $timeSeconds Time position in seconds.
     * @return array{
     *  data: string, 
     *  sample_number: int, 
     *  codec: string, 
     *  codec_name: string, 
     *  width: int|null, 
     *  height: int|null, 
     *  mime: string|null, 
     *  is_image: bool
     * }|null Frame data array, or null on failure.
     */
    public static function getFrameData(string $file, float $timeSeconds): ?array
    {
        $tags = self::getMetaTags($file);
        $videoTrack = self::findVideoTrack($tags);

        if ($videoTrack === null) {
            return null;
        }

        $timescale = $videoTrack['mdhd']['timescale'] ?? 0;
        $stts = $videoTrack['stts'] ?? null;
        $stss = $videoTrack['stss'] ?? null;
        $stsz = $videoTrack['stsz'] ?? null;
        $stsc = $videoTrack['stsc'] ?? null;
        $stco = $videoTrack['stco'] ?? null;

        if ($timescale === 0 || $stts === null || $stsz === null || $stsc === null || $stco === null) {
            return null;
        }

        $sampleNumber = self::timestampToSampleNumber($stts, $timescale, $timeSeconds);
        $syncSample = self::findNearestSyncSample($stss, $sampleNumber);
        $location = self::resolveSampleOffset($stsc, $stco, $stsz, $syncSample);

        if ($location === null || $location['size'] === 0) {
            return null;
        }

        $fh = fopen($file, 'rb');
        if ($fh === false) {
            return null;
        }

        fseek($fh, $location['offset']);
        $data = fread($fh, $location['size']);
        fclose($fh);

        if ($data === false || strlen($data) === 0) {
            return null;
        }

        $codec = $videoTrack['stsd'][0]['codec'] ?? '';
        $codecName = $videoTrack['stsd'][0]['codec_name'] ?? $codec;
        $width = $videoTrack['stsd'][0]['width'] ?? ($videoTrack['tkhd']['width'] ?? null);
        $height = $videoTrack['stsd'][0]['height'] ?? ($videoTrack['tkhd']['height'] ?? null);

        $imageCodecs = ['jpeg', 'mjpa', 'mjpb', 'png '];
        $isImage = in_array($codec, $imageCodecs, true);

        $mime = null;
        if ($codec === 'jpeg' || $codec === 'mjpa' || $codec === 'mjpb') {
            $mime = 'image/jpeg';
        } elseif ($codec === 'png ') {
            $mime = 'image/png';
        }

        return [
            'data' => $data,
            'sample_number' => $syncSample,
            'codec' => $codec,
            'codec_name' => $codecName,
            'width' => $width,
            'height' => $height,
            'mime' => $mime,
            'is_image' => $isImage,
        ];
    }

    /**
     * Save the raw frame data at the specified time position to a file.
     * For MJPEG/PNG codecs this produces a valid image file.
     * For H.264/HEVC this produces raw NAL unit data of the nearest keyframe.
     *
     * @param string $file Path to the MP4 file.
     * @param float $timeSeconds Time position in seconds.
     * @param string $outputPath Destination file path.
     * @return bool True if saved successfully, false otherwise.
     */
    public static function saveFrame(string $file, float $timeSeconds, string $outputPath): bool
    {
        $frame = self::getFrameData($file, $timeSeconds);

        if ($frame === null || empty($frame['data'])) {
            return false;
        }

        return file_put_contents($outputPath, $frame['data']) !== false;
    }

    /**
     * Save the frame at the specified time position as a displayable image file.
     *
     * For image-based codecs (MJPEG, PNG) the raw sample is decoded by GD.
	 * Motion JPEG samples that lack standard DHT markers are repaired
	 * automatically. H.264 keyframes are decoded by the built-in decoder.
     *
     * @param string $file Path to the MP4 file.
     * @param float $timeSeconds Time position in seconds.
     * @param string $outputPath Destination image file path.
     * @return bool True if saved successfully.
     * @throws Exception If extraction or decoding fails.
     */
    public static function saveFrameAsImage(string $file, float $timeSeconds, string $outputPath): bool
    {
        if (!function_exists('imagecreatefromstring')) {
            throw new Exception('The GD extension is required for saveFrameAsImage(). Install or enable the php-gd extension.');
        }

        $tags = self::getMetaTags($file);
        $videoTrack = self::findVideoTrack($tags);
        if ($videoTrack === null) {
            throw new Exception('No video track found in the file.');
        }

        $codec = $videoTrack['stsd'][0]['codec'] ?? '';

		if (self::isCompressedVideoCodec($codec)) {
			if (!in_array($codec, ['avc1', 'avc2', 'avc3', 'avc4'], true)) {
				throw new Exception("Codec '{$codec}' cannot be decoded; only H.264 frames are supported.");
			}

			$configuration = $videoTrack['stsd'][0]['avcc_data'] ?? null;
			if ($configuration === null) {
				throw new Exception('The video track carries no AVC decoder configuration (avcC).');
			}

			$frame = self::getFrameData($file, $timeSeconds);
			if ($frame === null || empty($frame['data'])) {
				throw new Exception('Failed to extract frame data from the file.');
			}

			$decoder = new IntraFrameDecoder($configuration, $videoTrack['stsd'][0]['matrix_coefficients'] ?? null);

			return self::saveGdImage($decoder->decode($frame['data']), $outputPath);
		}

        $frame = self::getFrameData($file, $timeSeconds);
        if ($frame === null) {
            throw new Exception('Failed to extract frame data from the file.');
        }
        if (empty($frame['data'])) {
            return false;
        }

        $imageData = $frame['data'];
        if (in_array($codec, ['jpeg', 'mjpa', 'mjpb'], true)) {
            $imageData = self::mjpegToJpeg($imageData);
        }

        $gdImage = @imagecreatefromstring($imageData);
        if ($gdImage === false) {
            throw new Exception("Codec '{$codec}' frames cannot be decoded by GD.");
        }
        return self::saveGdImage($gdImage, $outputPath);
    }

    private static function isCompressedVideoCodec(string $codec): bool
    {
        return in_array($codec, ['avc1', 'avc2', 'avc3', 'avc4', 'hev1', 'hvc1', 'dvhe', 'dvh1', 'dva1', 'dvav', 'vp08', 'vp09', 'av01', 'mp4v', 's263', 'H263'], true);
    }

	private static function saveGdImage(\GdImage $img, string $path): bool
	{
		$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $r = match ($ext) {
            'jpg', 'jpeg' => imagejpeg($img, $path, 95),
            'gif' => imagegif($img, $path),
            'bmp' => imagebmp($img, $path),
            'webp' => imagewebp($img, $path, 95),
            default => imagepng($img, $path, 6),
        };
        if (OperationSystem::comparePHPVersion('8.5.0', '<')) {
            // @phpstan-ignore-next-line
            imagedestroy($img);
		}
		return $r;
	}

	private static function saveFrameAsImageWithHeadlessBrowser(string $file, float $timeSeconds, string $outputPath, int $width, int $height): bool
	{
		if (OperationSystem::isFunctionDisabled('exec')) {
			throw new Exception('The exec() function is disabled, so the Chromium frame renderer cannot be launched.');
		}

		$browserPath = self::resolveHeadlessBrowserBinaryPath();

		$temporaryBasePath = tempnam(sys_get_temp_dir(), 'mp4frm');
		if ($temporaryBasePath === false) {
			throw new Exception('Failed to allocate a temporary file for frame extraction.');
		}

		$temporaryVideoPath = $temporaryBasePath . '.mp4';
		$temporaryHtmlPath = $temporaryBasePath . '.html';
		$temporaryImagePath = $temporaryBasePath . '.png';

		if (is_file($temporaryBasePath)) {
			unlink($temporaryBasePath);
		}

		try {
			self::saveFrameAsStandaloneVideo($file, $timeSeconds, $temporaryVideoPath);

			$htmlWritten = file_put_contents(
				$temporaryHtmlPath,
				self::buildHeadlessBrowserFrameExtractorHtml(
					self::convertLocalPathToFileUrl($temporaryVideoPath),
					$width,
					$height
				)
			) !== false;
			if (!$htmlWritten) {
				throw new Exception('Failed to write the temporary browser frame extraction page.');
			}

			$command = sprintf(
				'%s --headless=new --disable-gpu --allow-file-access-from-files --autoplay-policy=no-user-gesture-required --mute-audio --hide-scrollbars --window-size=%d,%d --virtual-time-budget=%d --run-all-compositor-stages-before-draw %s %s 2>&1',
				escapeshellarg($browserPath),
				$width,
				$height,
				self::HEADLESS_BROWSER_FRAME_BUDGET_MILLISECONDS,
				escapeshellarg('--screenshot=' . $temporaryImagePath),
				escapeshellarg(self::convertLocalPathToFileUrl($temporaryHtmlPath))
			);

			$output = [];
			$resultCode = 0;
			exec($command, $output, $resultCode);

			if ($resultCode !== 0) {
				$reason = trim(implode(PHP_EOL, $output));
				if ($reason === '') {
					$reason = 'The Chromium frame renderer exited with a non-zero status.';
				}
				throw new Exception($reason);
			}

			if (!is_file($temporaryImagePath)) {
				throw new Exception('The Chromium frame renderer did not create an output image.');
			}

			$imageData = file_get_contents($temporaryImagePath);
			if (!is_string($imageData) || $imageData === '') {
				throw new Exception('The Chromium frame renderer created an empty output image.');
			}

			$gdImage = imagecreatefromstring($imageData);
			if ($gdImage === false) {
				throw new Exception('The Chromium frame renderer output could not be decoded by GD.');
			}

			return self::saveGdImage($gdImage, $outputPath);
		} finally {
			if (is_file($temporaryVideoPath)) {
				unlink($temporaryVideoPath);
			}
			if (is_file($temporaryHtmlPath)) {
				unlink($temporaryHtmlPath);
			}
			if (is_file($temporaryImagePath)) {
				unlink($temporaryImagePath);
			}
		}
	}

	private static function resolveHeadlessBrowserBinaryPath(): string
	{
		$basePaths = [];
		foreach (['LOCALAPPDATA', 'PROGRAMFILES', 'PROGRAMFILES(X86)', 'PROGRAMW6432'] as $environmentVariableName) {
			$basePath = getenv($environmentVariableName);
			if ($basePath !== false && $basePath !== '') {
				$basePaths[] = rtrim($basePath, '\\/');
			}
		}
		$basePaths = array_values(array_unique($basePaths));

		$relativePaths = [
			'Google\\Chrome\\Application\\chrome.exe',
			'Google\\Chrome Beta\\Application\\chrome.exe',
			'Google\\Chrome SxS\\Application\\chrome.exe',
			'Microsoft\\Edge\\Application\\msedge.exe',
			'Microsoft\\Edge Beta\\Application\\msedge.exe',
			'Chromium\\Application\\chrome.exe',
		];

		foreach ($basePaths as $basePath) {
			foreach ($relativePaths as $relativePath) {
				$candidatePath = $basePath . DIRECTORY_SEPARATOR . $relativePath;
				if (is_file($candidatePath)) {
					return $candidatePath;
				}
			}
		}

		foreach ([
			'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
			'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
			'C:\\Program Files\\Microsoft\\Edge\\Application\\msedge.exe',
			'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe',
		] as $candidatePath) {
			if (is_file($candidatePath)) {
				return $candidatePath;
			}
		}

		try {
			$chromeDevTool = new ChromeDevTool();
			return $chromeDevTool->getChromePath();
		} catch (Exception) {
		}

		throw new Exception('No Chromium-compatible browser executable was found for video frame rendering.');
	}

	private static function buildHeadlessBrowserFrameExtractorHtml(string $videoFileUrl, int $width, int $height): string
	{
		return sprintf(
			'<!doctype html><html><head><meta charset="utf-8"><style>html,body{margin:0;background:#000;overflow:hidden;width:%1$dpx;height:%2$dpx;}canvas{display:block;width:%1$dpx;height:%2$dpx;}</style></head><body><canvas id="canvas" width="%1$d" height="%2$d"></canvas><video id="video" muted playsinline preload="auto" src="%3$s" style="display:none"></video><script>(()=>{const video=document.getElementById("video");const canvas=document.getElementById("canvas");const context=canvas.getContext("2d");const drawFrame=()=>{window.requestAnimationFrame(()=>{context.drawImage(video,0,0,%1$d,%2$d);document.body.dataset.ready="1";});};const fail=(error)=>{document.body.dataset.error=String(error&&error.message?error.message:error);};try{video.load();if(video.readyState>=HTMLMediaElement.HAVE_CURRENT_DATA){drawFrame();return;}video.addEventListener("loadeddata",drawFrame,{once:true});video.addEventListener("error",()=>fail("Video frame could not be decoded."),{once:true});}catch(error){fail(error);}})();</script></body></html>',
			$width,
			$height,
			htmlspecialchars($videoFileUrl, ENT_QUOTES, 'UTF-8')
		);
	}

	private static function convertLocalPathToFileUrl(string $path): string
	{
		$normalizedPath = str_replace('\\', '/', $path);
		if (preg_match('/^[A-Za-z]:/', $normalizedPath) === 1) {
			$normalizedPath = '/' . $normalizedPath;
		}

		$segments = explode('/', $normalizedPath);
		$encodedSegments = [];
		foreach ($segments as $index => $segment) {
			if ($segment === '') {
				$encodedSegments[] = '';
				continue;
			}
			if ($index === 1 && preg_match('/^[A-Za-z]:$/', $segment) === 1) {
				$encodedSegments[] = $segment;
				continue;
			}
			$encodedSegments[] = rawurlencode($segment);
		}

		return 'file://' . implode('/', $encodedSegments);
	}

	private static function saveFrameAsImageWithWindowsShell(string $file, float $timeSeconds, string $outputPath, int $width, int $height): bool
	{
		if (!OperationSystem::isWindows()) {
			throw new Exception('Windows shell frame extraction is only available on Windows.');
		}
		if (OperationSystem::isFunctionDisabled('exec')) {
			throw new Exception('The exec() function is disabled, so the Windows shell thumbnail extractor cannot be launched.');
		}

		$temporaryBasePath = tempnam(sys_get_temp_dir(), 'mp4frm');
		if ($temporaryBasePath === false) {
			throw new Exception('Failed to allocate a temporary file for frame extraction.');
		}

		$temporaryVideoPath = $temporaryBasePath . '.mp4';
		$temporaryScriptPath = $temporaryBasePath . '.ps1';

		if (is_file($temporaryBasePath)) {
			unlink($temporaryBasePath);
		}

		try {
			self::saveFrameAsStandaloneVideo($file, $timeSeconds, $temporaryVideoPath);

			$scriptWritten = file_put_contents($temporaryScriptPath, self::buildWindowsShellThumbnailScript()) !== false;
			if (!$scriptWritten) {
				throw new Exception('Failed to write the Windows thumbnail extraction script.');
			}

			$command = sprintf(
				'powershell -STA -NoProfile -ExecutionPolicy Bypass -File %s -InputPath %s -OutputPath %s -Width %d -Height %d 2>&1',
				escapeshellarg($temporaryScriptPath),
				escapeshellarg($temporaryVideoPath),
				escapeshellarg($outputPath),
				$width,
				$height
			);

			$output = [];
			$resultCode = 0;
			exec($command, $output, $resultCode);

			if ($resultCode !== 0) {
				$reason = trim(implode(PHP_EOL, $output));
				if ($reason === '') {
					$reason = 'The Windows shell thumbnail extractor exited with a non-zero status.';
				}
				throw new Exception($reason);
			}

			if (!is_file($outputPath)) {
				throw new Exception('The Windows shell thumbnail extractor did not create an output image.');
			}

			$writtenBytes = filesize($outputPath);
			if ($writtenBytes === false || $writtenBytes <= 0) {
				throw new Exception('The Windows shell thumbnail extractor created an empty output image.');
			}

			return true;
		} finally {
			if (is_file($temporaryVideoPath)) {
				unlink($temporaryVideoPath);
			}
			if (is_file($temporaryScriptPath)) {
				unlink($temporaryScriptPath);
			}
		}
	}

	private static function buildWindowsShellThumbnailScript(): string
	{
		return <<<'POWERSHELL'
param(
	[string]$InputPath,
	[string]$OutputPath,
	[int]$Width,
	[int]$Height
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

Add-Type -ReferencedAssemblies System.Drawing -TypeDefinition @"
using System;
using System.Drawing;
using System.Drawing.Imaging;
using System.Runtime.InteropServices;

[ComImport]
[Guid("bcc18b79-ba16-442f-80c4-8a59c30c463b")]
[InterfaceType(ComInterfaceType.InterfaceIsIUnknown)]
interface IShellItemImageFactory
{
	void GetImage(SIZE size, SIIGBF flags, out IntPtr phbm);
}

[StructLayout(LayoutKind.Sequential)]
struct SIZE
{
	public int cx;
	public int cy;
}

[Flags]
enum SIIGBF
{
	BIGGERSIZEOK = 0x1,
	THUMBNAILONLY = 0x8
}

public static class ShellThumbnailExtractor
{
	[DllImport("shell32.dll", CharSet = CharSet.Unicode, PreserveSig = false)]
	private static extern void SHCreateItemFromParsingName(
		string pszPath,
		IntPtr pbc,
		ref Guid riid,
		[MarshalAs(UnmanagedType.Interface)] out IShellItemImageFactory ppv
	);

	[DllImport("gdi32.dll")]
	private static extern bool DeleteObject(IntPtr hObject);

	public static void Save(string inputPath, string outputPath, int width, int height)
	{
		Guid guid = new Guid("bcc18b79-ba16-442f-80c4-8a59c30c463b");
		IShellItemImageFactory factory;
		SHCreateItemFromParsingName(inputPath, IntPtr.Zero, ref guid, out factory);

		IntPtr bitmapHandle;
		factory.GetImage(new SIZE { cx = width, cy = height }, SIIGBF.BIGGERSIZEOK | SIIGBF.THUMBNAILONLY, out bitmapHandle);

		using (Bitmap bitmap = Bitmap.FromHbitmap(bitmapHandle))
		{
			bitmap.Save(outputPath, ImageFormat.Jpeg);
		}

		DeleteObject(bitmapHandle);
	}
}
"@

[ShellThumbnailExtractor]::Save(
	(Resolve-Path $InputPath).Path,
	$OutputPath,
	$Width,
	$Height
)
POWERSHELL;
	}

    private static function mjpegToJpeg(string $data): string
    {
        $len = strlen($data);
        for ($i = 0; $i < $len - 1; $i++) {
            if (ord($data[$i]) === 0xFF && ord($data[$i + 1]) === 0xD8) {
                $data = substr($data, $i);
                break;
            }
        }
        if (strlen($data) < 4 || ord($data[0]) !== 0xFF || ord($data[1]) !== 0xD8) {
            return $data;
        }
        $off = 2;
        $len = strlen($data);
        $hasDht = false;
        while ($off + 4 <= $len) {
            if (ord($data[$off]) !== 0xFF) {
                break;
            }
            $m = ord($data[$off + 1]);
            if ($m === 0xC4) {
                $hasDht = true;
                break;
            }
            if ($m === 0xDA) {
                break;
            }
            if (($m >= 0xD0 && $m <= 0xD7) || $m === 0xD8 || $m === 0xD9) {
                $off += 2;
                continue;
            }
            $sLen = BinaryHandler::readUint16($data, $off + 2);
            $off += 2 + $sLen;
        }
        if ($hasDht) {
            return $data;
        }
        $dht = self::buildJpegDht();
        $ins = 2;
        $scan = 2;
        while ($scan + 4 <= $len) {
            if (ord($data[$scan]) !== 0xFF) {
                break;
            }
            $m = ord($data[$scan + 1]);
            if ($m === 0xDA) {
                $ins = $scan;
                break;
            }
            if (($m >= 0xD0 && $m <= 0xD7) || $m === 0xD8 || $m === 0xD9) {
                $scan += 2;
                $ins = $scan;
                continue;
            }
            $sLen = BinaryHandler::readUint16($data, $scan + 2);
            $scan += 2 + $sLen;
            $ins = $scan;
        }
        return substr($data, 0, $ins) . $dht . substr($data, $ins);
    }

    private static function buildJpegDht(): string
    {
        $tables = [
            [0x00, [0, 1, 5, 1, 1, 1, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0], [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11]],
            [0x10, [0, 2, 1, 3, 3, 2, 4, 3, 5, 5, 4, 4, 0, 0, 1, 0x7D], [0x01, 0x02, 0x03, 0x00, 0x04, 0x11, 0x05, 0x12, 0x21, 0x31, 0x41, 0x06, 0x13, 0x51, 0x61, 0x07, 0x22, 0x71, 0x14, 0x32, 0x81, 0x91, 0xA1, 0x08, 0x23, 0x42, 0xB1, 0xC1, 0x15, 0x52, 0xD1, 0xF0, 0x24, 0x33, 0x62, 0x72, 0x82, 0x09, 0x0A, 0x16, 0x17, 0x18, 0x19, 0x1A, 0x25, 0x26, 0x27, 0x28, 0x29, 0x2A, 0x34, 0x35, 0x36, 0x37, 0x38, 0x39, 0x3A, 0x43, 0x44, 0x45, 0x46, 0x47, 0x48, 0x49, 0x4A, 0x53, 0x54, 0x55, 0x56, 0x57, 0x58, 0x59, 0x5A, 0x63, 0x64, 0x65, 0x66, 0x67, 0x68, 0x69, 0x6A, 0x73, 0x74, 0x75, 0x76, 0x77, 0x78, 0x79, 0x7A, 0x83, 0x84, 0x85, 0x86, 0x87, 0x88, 0x89, 0x8A, 0x92, 0x93, 0x94, 0x95, 0x96, 0x97, 0x98, 0x99, 0x9A, 0xA2, 0xA3, 0xA4, 0xA5, 0xA6, 0xA7, 0xA8, 0xA9, 0xAA, 0xB2, 0xB3, 0xB4, 0xB5, 0xB6, 0xB7, 0xB8, 0xB9, 0xBA, 0xC2, 0xC3, 0xC4, 0xC5, 0xC6, 0xC7, 0xC8, 0xC9, 0xCA, 0xD2, 0xD3, 0xD4, 0xD5, 0xD6, 0xD7, 0xD8, 0xD9, 0xDA, 0xE1, 0xE2, 0xE3, 0xE4, 0xE5, 0xE6, 0xE7, 0xE8, 0xE9, 0xEA, 0xF1, 0xF2, 0xF3, 0xF4, 0xF5, 0xF6, 0xF7, 0xF8, 0xF9, 0xFA]],
            [0x01, [0, 3, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0, 0, 0, 0, 0], [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11]],
            [0x11, [0, 2, 1, 2, 4, 4, 3, 4, 7, 5, 4, 4, 0, 1, 2, 0x77], [0x00, 0x01, 0x02, 0x03, 0x11, 0x04, 0x05, 0x21, 0x31, 0x06, 0x12, 0x41, 0x51, 0x07, 0x61, 0x71, 0x13, 0x22, 0x32, 0x81, 0x08, 0x14, 0x42, 0x91, 0xA1, 0xB1, 0xC1, 0x09, 0x23, 0x33, 0x52, 0xF0, 0x15, 0x62, 0x72, 0xD1, 0x0A, 0x16, 0x24, 0x34, 0xE1, 0x25, 0xF1, 0x17, 0x18, 0x19, 0x1A, 0x26, 0x27, 0x28, 0x29, 0x2A, 0x35, 0x36, 0x37, 0x38, 0x39, 0x3A, 0x43, 0x44, 0x45, 0x46, 0x47, 0x48, 0x49, 0x4A, 0x53, 0x54, 0x55, 0x56, 0x57, 0x58, 0x59, 0x5A, 0x63, 0x64, 0x65, 0x66, 0x67, 0x68, 0x69, 0x6A, 0x73, 0x74, 0x75, 0x76, 0x77, 0x78, 0x79, 0x7A, 0x82, 0x83, 0x84, 0x85, 0x86, 0x87, 0x88, 0x89, 0x8A, 0x92, 0x93, 0x94, 0x95, 0x96, 0x97, 0x98, 0x99, 0x9A, 0xA2, 0xA3, 0xA4, 0xA5, 0xA6, 0xA7, 0xA8, 0xA9, 0xAA, 0xB2, 0xB3, 0xB4, 0xB5, 0xB6, 0xB7, 0xB8, 0xB9, 0xBA, 0xC2, 0xC3, 0xC4, 0xC5, 0xC6, 0xC7, 0xC8, 0xC9, 0xCA, 0xD2, 0xD3, 0xD4, 0xD5, 0xD6, 0xD7, 0xD8, 0xD9, 0xDA, 0xE2, 0xE3, 0xE4, 0xE5, 0xE6, 0xE7, 0xE8, 0xE9, 0xEA, 0xF2, 0xF3, 0xF4, 0xF5, 0xF6, 0xF7, 0xF8, 0xF9, 0xFA]],
        ];
        $out = '';
        foreach ($tables as [$tid, $lens, $vals]) {
            $p = chr($tid);
            foreach ($lens as $l) {
                $p .= chr($l);
            }
            foreach ($vals as $v) {
                $p .= chr($v);
            }
            $out .= "\xFF\xC4" . BinaryHandler::writeUint16(2 + strlen($p)) . $p;
        }
        return $out;
    }

    private static function decodeH264Frame(string $sampleData, array $vt, int $w, int $h): \GdImage
    {
        $codec = $vt['stsd'][0]['codec'] ?? '';
        if (!in_array($codec, ['avc1', 'avc2', 'avc3', 'avc4'], true)) {
            throw new Exception("Codec '{$codec}' pure-PHP decoding not supported. Use saveFrameAsStandaloneVideo().");
        }
        $avccData = $vt['stsd'][0]['avcc_data'] ?? null;
        if ($avccData === null) {
            throw new Exception('No avcC configuration found.');
        }
        $config = self::parseAvcC($avccData);
        $nalLenSize = $config['nal_length_size'];

        $spsInfo = null;
        foreach ($config['sps'] as $spsNal) {
            $spsInfo = self::h264ParseSps(self::h264Rbsp(substr($spsNal, 1)));
            break;
        }
        if (!$spsInfo) {
            throw new Exception('No SPS found.');
        }

        $ppsInfo = null;
        foreach ($config['pps'] as $ppsNal) {
            $ppsInfo = self::h264ParsePps(self::h264Rbsp(substr($ppsNal, 1)));
            break;
        }
        if (!$ppsInfo) {
            throw new Exception('No PPS found.');
        }

        $sliceNals = [];
        $off = 0;
        $len = strlen($sampleData);
        while ($off + $nalLenSize <= $len) {
            $nLen = 0;
            for ($i = 0; $i < $nalLenSize; $i++) {
                $nLen = ($nLen << 8) | ord($sampleData[$off + $i]);
            }
            $off += $nalLenSize;
            if ($nLen <= 0 || $off + $nLen > $len) {
                break;
            }
            $nalType = ord($sampleData[$off]) & 0x1F;
            if ($nalType === 5) {
                $sliceNals[] = substr($sampleData, $off, $nLen);
            }
            $off += $nLen;
        }
        if (empty($sliceNals)) {
            throw new Exception('No IDR slice found.');
        }

        $mbW = $spsInfo['mb_w'];
        $mbH = $spsInfo['mb_h'];
        $lumaW = $mbW * 16;
        $lumaH = $mbH * 16;
        $chrW = $mbW * 8;
        $chrH = $mbH * 8;

        $yP = array_fill(0, $lumaH, array_fill(0, $lumaW, 128));
        $uP = array_fill(0, $chrH, array_fill(0, $chrW, 128));
        $vP = array_fill(0, $chrH, array_fill(0, $chrW, 128));

        $isCabac = ($ppsInfo['entropy_coding_mode_flag'] === 1);

        foreach ($sliceNals as $nalData) {
            $rbsp = self::h264Rbsp(substr($nalData, 1));
            $br = ['d' => $rbsp, 'p' => 0, 'l' => strlen($rbsp) * 8];

			$firstMacroblockInSlice = self::h264UE($br);
			self::h264UE($br);
			self::h264UE($br);
            self::h264Bits($br, $spsInfo['log2_max_frame_num']);
            if (!$spsInfo['frame_mbs_only_flag']) {
                if (self::h264Bits($br, 1)) {
                    self::h264Bits($br, 1);
                }
            }
            self::h264UE($br);
            if ($spsInfo['poc_type'] === 0) {
                self::h264Bits($br, $spsInfo['log2_max_poc_lsb']);
            }
            self::h264Bits($br, 1);
            self::h264Bits($br, 1);
            $sliceQpDelta = self::h264SE($br);
            $qpY = $ppsInfo['pic_init_qp'] + $sliceQpDelta;

            if ($ppsInfo['db_filter_flag']) {
                $dbf = self::h264UE($br);
                if ($dbf !== 1) {
                    self::h264SE($br);
                    self::h264SE($br);
                }
            }

			if ($isCabac) {
				$br['p'] = (($br['p'] + 7) >> 3) << 3;
				$cab = self::cabacInit($rbsp, $br['p'] >> 3, $qpY);
				self::h264DecodeMbsCabac($cab, $mbW, $mbH, $firstMacroblockInSlice, $qpY, $ppsInfo, $yP, $uP, $vP);
			} else {
				self::h264DecodeMbsCavlc($br, $mbW, $mbH, $firstMacroblockInSlice, $qpY, $ppsInfo, $yP, $uP, $vP);
			}
		}

        $img = imagecreatetruecolor($w, $h);
        for ($row = 0; $row < $h; $row++) {
            for ($col = 0; $col < $w; $col++) {
                $y = $yP[$row][$col] ?? 128;
                $cb = $uP[$row >> 1][$col >> 1] ?? 128;
                $cr = $vP[$row >> 1][$col >> 1] ?? 128;
                $c = $y - 16;
                $d = $cb - 128;
                $e = $cr - 128;
                $r = max(0, min(255, (298 * $c + 409 * $e + 128) >> 8));
                $g = max(0, min(255, (298 * $c - 100 * $d - 208 * $e + 128) >> 8));
                $b = max(0, min(255, (298 * $c + 516 * $d + 128) >> 8));
                imagesetpixel($img, $col, $row, ($r << 16) | ($g << 8) | $b);
            }
        }
        return $img;
    }

    private static function extractJpegFromSample(string $data): string
    {
        $len = strlen($data);
        for ($i = 0; $i < $len - 1; $i++) {
            if (ord($data[$i]) === 0xFF && ord($data[$i + 1]) === 0xD8) {
                return substr($data, $i);
            }
        }

        return $data;
    }

    private static function ensureJpegDht(string $data): string
    {
        if (strlen($data) < 4 || ord($data[0]) !== 0xFF || ord($data[1]) !== 0xD8) {
            return $data;
        }

        $offset = 2;
        $len = strlen($data);
        while ($offset + 4 <= $len) {
            if (ord($data[$offset]) !== 0xFF) {
                break;
            }
            $marker = ord($data[$offset + 1]);
            if ($marker === 0xC4) {
                return $data;
            }
            if ($marker === 0xDA) {
                break;
            }
            if ($marker === 0xD8 || $marker === 0xD9 || ($marker >= 0xD0 && $marker <= 0xD7)) {
                $offset += 2;
                continue;
            }
            $segLen = BinaryHandler::readUint16($data, $offset + 2);
            $offset += 2 + $segLen;
        }

        $dht = self::buildStandardJpegDht();
        $insertPos = 2;
        $scanOffset = 2;
        while ($scanOffset + 4 <= $len) {
            if (ord($data[$scanOffset]) !== 0xFF) {
                break;
            }
            $m = ord($data[$scanOffset + 1]);
            if ($m === 0xDA) {
                $insertPos = $scanOffset;
                break;
            }
            if ($m === 0xD8 || $m === 0xD9 || ($m >= 0xD0 && $m <= 0xD7)) {
                $scanOffset += 2;
                $insertPos = $scanOffset;
                continue;
            }
            $segLen = BinaryHandler::readUint16($data, $scanOffset + 2);
            $scanOffset += 2 + $segLen;
            $insertPos = $scanOffset;
        }

        return substr($data, 0, $insertPos) . $dht . substr($data, $insertPos);
    }

    private static function buildStandardJpegDht(): string
    {
        $dcLumLengths = [0, 1, 5, 1, 1, 1, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0];
        $dcLumValues = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11];
        $dcChrLengths = [0, 3, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0, 0, 0, 0, 0];
        $dcChrValues = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11];
        $acLumLengths = [0, 2, 1, 3, 3, 2, 4, 3, 5, 5, 4, 4, 0, 0, 1, 0x7D];
        $acLumValues = [0x01, 0x02, 0x03, 0x00, 0x04, 0x11, 0x05, 0x12, 0x21, 0x31, 0x41, 0x06, 0x13, 0x51, 0x61, 0x07, 0x22, 0x71, 0x14, 0x32, 0x81, 0x91, 0xA1, 0x08, 0x23, 0x42, 0xB1, 0xC1, 0x15, 0x52, 0xD1, 0xF0, 0x24, 0x33, 0x62, 0x72, 0x82, 0x09, 0x0A, 0x16, 0x17, 0x18, 0x19, 0x1A, 0x25, 0x26, 0x27, 0x28, 0x29, 0x2A, 0x34, 0x35, 0x36, 0x37, 0x38, 0x39, 0x3A, 0x43, 0x44, 0x45, 0x46, 0x47, 0x48, 0x49, 0x4A, 0x53, 0x54, 0x55, 0x56, 0x57, 0x58, 0x59, 0x5A, 0x63, 0x64, 0x65, 0x66, 0x67, 0x68, 0x69, 0x6A, 0x73, 0x74, 0x75, 0x76, 0x77, 0x78, 0x79, 0x7A, 0x83, 0x84, 0x85, 0x86, 0x87, 0x88, 0x89, 0x8A, 0x92, 0x93, 0x94, 0x95, 0x96, 0x97, 0x98, 0x99, 0x9A, 0xA2, 0xA3, 0xA4, 0xA5, 0xA6, 0xA7, 0xA8, 0xA9, 0xAA, 0xB2, 0xB3, 0xB4, 0xB5, 0xB6, 0xB7, 0xB8, 0xB9, 0xBA, 0xC2, 0xC3, 0xC4, 0xC5, 0xC6, 0xC7, 0xC8, 0xC9, 0xCA, 0xD2, 0xD3, 0xD4, 0xD5, 0xD6, 0xD7, 0xD8, 0xD9, 0xDA, 0xE1, 0xE2, 0xE3, 0xE4, 0xE5, 0xE6, 0xE7, 0xE8, 0xE9, 0xEA, 0xF1, 0xF2, 0xF3, 0xF4, 0xF5, 0xF6, 0xF7, 0xF8, 0xF9, 0xFA];
        $acChrLengths = [0, 2, 1, 2, 4, 4, 3, 4, 7, 5, 4, 4, 0, 1, 2, 0x77];
        $acChrValues = [0x00, 0x01, 0x02, 0x03, 0x11, 0x04, 0x05, 0x21, 0x31, 0x06, 0x12, 0x41, 0x51, 0x07, 0x61, 0x71, 0x13, 0x22, 0x32, 0x81, 0x08, 0x14, 0x42, 0x91, 0xA1, 0xB1, 0xC1, 0x09, 0x23, 0x33, 0x52, 0xF0, 0x15, 0x62, 0x72, 0xD1, 0x0A, 0x16, 0x24, 0x34, 0xE1, 0x25, 0xF1, 0x17, 0x18, 0x19, 0x1A, 0x26, 0x27, 0x28, 0x29, 0x2A, 0x35, 0x36, 0x37, 0x38, 0x39, 0x3A, 0x43, 0x44, 0x45, 0x46, 0x47, 0x48, 0x49, 0x4A, 0x53, 0x54, 0x55, 0x56, 0x57, 0x58, 0x59, 0x5A, 0x63, 0x64, 0x65, 0x66, 0x67, 0x68, 0x69, 0x6A, 0x73, 0x74, 0x75, 0x76, 0x77, 0x78, 0x79, 0x7A, 0x82, 0x83, 0x84, 0x85, 0x86, 0x87, 0x88, 0x89, 0x8A, 0x92, 0x93, 0x94, 0x95, 0x96, 0x97, 0x98, 0x99, 0x9A, 0xA2, 0xA3, 0xA4, 0xA5, 0xA6, 0xA7, 0xA8, 0xA9, 0xAA, 0xB2, 0xB3, 0xB4, 0xB5, 0xB6, 0xB7, 0xB8, 0xB9, 0xBA, 0xC2, 0xC3, 0xC4, 0xC5, 0xC6, 0xC7, 0xC8, 0xC9, 0xCA, 0xD2, 0xD3, 0xD4, 0xD5, 0xD6, 0xD7, 0xD8, 0xD9, 0xDA, 0xE2, 0xE3, 0xE4, 0xE5, 0xE6, 0xE7, 0xE8, 0xE9, 0xEA, 0xF2, 0xF3, 0xF4, 0xF5, 0xF6, 0xF7, 0xF8, 0xF9, 0xFA];

        $dht = '';
        foreach ([
            [0x00, $dcLumLengths, $dcLumValues],
            [0x10, $acLumLengths, $acLumValues],
            [0x01, $dcChrLengths, $dcChrValues],
            [0x11, $acChrLengths, $acChrValues],
        ] as [$tableId, $lengths, $values]) {
            $payload = chr($tableId);
            foreach ($lengths as $l) {
                $payload .= chr($l);
            }
            foreach ($values as $v) {
                $payload .= chr($v);
            }
            $segLen = 2 + strlen($payload);
            $dht .= "\xFF\xC4" . BinaryHandler::writeUint16($segLen) . $payload;
        }

        return $dht;
    }

	private static function decodeCompressedFrame(string $sampleData, array $videoTrack, int $width, int $height): \GdImage
	{
		$codec = $videoTrack['stsd'][0]['codec'] ?? '';
		$avcCodecs = ['avc1', 'avc2', 'avc3', 'avc4'];

		if (in_array($codec, $avcCodecs, true)) {
			$primaryImage = self::decodeH264Frame($sampleData, $videoTrack, $width, $height);
			try {
				$secondaryImage = self::decodeH264IdrToImage($sampleData, self::resolveDecoderConfig($videoTrack), $width, $height);
				return self::mergeInternalH264DecodeImages($primaryImage, $secondaryImage);
			} catch (\Throwable) {
				return $primaryImage;
			}
		}

		throw new Exception("Codec '{$codec}' frame decoding is not supported by the built-in decoder.");
	}

	private static function mergeInternalH264DecodeImages(\GdImage $primaryImage, \GdImage $secondaryImage): \GdImage
	{
		$imageWidth = imagesx($primaryImage);
		$imageHeight = imagesy($primaryImage);
		for ($y = 0; $y < $imageHeight; $y++) {
			for ($x = 0; $x < $imageWidth; $x++) {
				$primaryRgb = imagecolorat($primaryImage, $x, $y);
				$primaryRed = ($primaryRgb >> 16) & 0xFF;
				$primaryGreen = ($primaryRgb >> 8) & 0xFF;
				$primaryBlue = $primaryRgb & 0xFF;

				if (!self::isNeutralDecoderPixel($primaryRed, $primaryGreen, $primaryBlue)) {
					continue;
				}

				$secondaryRgb = imagecolorat($secondaryImage, $x, $y);
				imagesetpixel($primaryImage, $x, $y, $secondaryRgb);
			}
		}

		if (OperationSystem::comparePHPVersion('8.5.0', '<')) {
			imagedestroy($secondaryImage);
		}

		return $primaryImage;
	}

	private static function isNeutralDecoderPixel(int $red, int $green, int $blue): bool
	{
		return abs($red - $green) <= 6
			&& abs($green - $blue) <= 6
			&& abs($red - $blue) <= 6
			&& abs($red - 128) <= 12
			&& abs($green - 128) <= 12
			&& abs($blue - 128) <= 12;
	}

    private static function naluToRbsp(string $nalu): string
    {
        $rbsp = '';
        $len = strlen($nalu);
        $i = 0;
        while ($i < $len) {
            if ($i + 2 < $len && ord($nalu[$i]) === 0x00 && ord($nalu[$i + 1]) === 0x00 && ord($nalu[$i + 2]) === 0x03) {
                $rbsp .= "\x00\x00";
                $i += 3;
            } else {
                $rbsp .= $nalu[$i];
                $i++;
            }
        }
        return $rbsp;
    }

    private static function h264Rbsp(string $n): string
    {
        $r = '';
        $l = strlen($n);
        $i = 0;
        while ($i < $l) {
            if ($i + 2 < $l && ord($n[$i]) === 0 && ord($n[$i + 1]) === 0 && ord($n[$i + 2]) === 3) {
                $r .= "\x00\x00";
                $i += 3;
            } else {
                $r .= $n[$i++];
            }
        }
        return $r;
    }

    private static function brCreate(string $data): array
    {
        return ['data' => $data, 'pos' => 0, 'len' => strlen($data) * 8];
    }

    private static function brRead(array &$br, int $n): int
    {
        $val = 0;
        for ($i = 0; $i < $n; $i++) {
            $byteIdx = ($br['pos'] + $i) >> 3;
            $bitIdx = 7 - (($br['pos'] + $i) & 7);
            if ($byteIdx < strlen($br['data'])) {
                $val = ($val << 1) | ((ord($br['data'][$byteIdx]) >> $bitIdx) & 1);
            } else {
                $val <<= 1;
            }
        }
        $br['pos'] += $n;
        return $val;
    }

    private static function brReadUE(array &$br): int
    {
        $zeros = 0;
        while ($br['pos'] < $br['len'] && self::brRead($br, 1) === 0) {
            $zeros++;
            if ($zeros > 31) {
                return 0;
            }
        }
        if ($zeros === 0) {
            return 0;
        }
        return (1 << $zeros) - 1 + self::brRead($br, $zeros);
    }

    private static function brReadSE(array &$br): int
    {
        $val = self::brReadUE($br);
        return ($val & 1) ? (($val + 1) >> 1) : -($val >> 1);
    }

    private static function h264Bits(array &$br, int $n): int
    {
        $v = 0;
        for ($i = 0; $i < $n; $i++) {
            $bi = $br['p'] + $i;
            $v = ($v << 1) | ((ord($br['d'][$bi >> 3] ?? "\0") >> (7 - ($bi & 7))) & 1);
        }
        $br['p'] += $n;
        return $v;
    }

    private static function h264UE(array &$br): int
    {
        $z = 0;
        while ($br['p'] < $br['l'] && self::h264Bits($br, 1) === 0) {
            $z++;
            if ($z > 31) {
                return 0;
            }
        }

        return $z === 0 ? 0 : (1 << $z) - 1 + self::h264Bits($br, $z);
    }

    private static function h264SE(array &$br): int
    {
        $v = self::h264UE($br);
        return ($v & 1) ? (($v + 1) >> 1) : -($v >> 1);
    }

    private static function h264ParseSps(string $rbsp): array
    {
        $br = ['d' => $rbsp, 'p' => 0, 'l' => strlen($rbsp) * 8];
        $prof = self::h264Bits($br, 8);
        self::h264Bits($br, 8);
        self::h264Bits($br, 8);
        self::h264UE($br);
        $chroma = 1;
        if (in_array($prof, [100, 110, 122, 244, 44, 83, 86, 118, 128, 138, 139, 134, 135], true)) {
            $chroma = self::h264UE($br);
            if ($chroma === 3) {
                self::h264Bits($br, 1);
            }
            self::h264UE($br);
            self::h264UE($br);
            self::h264Bits($br, 1);
            if (self::h264Bits($br, 1)) {
                $cnt = ($chroma !== 3) ? 8 : 12;
                for ($i = 0; $i < $cnt; $i++) {
                    if (self::h264Bits($br, 1)) {
                        $sz = ($i < 6) ? 16 : 64;
                        $last = 8;
                        $next = 8;
                        for ($j = 0; $j < $sz; $j++) {
                            if ($next !== 0) {
                                $d = self::h264SE($br);
                                $next = ($last + $d + 256) % 256;
                            }
                            $last = ($next === 0) ? $last : $next;
                        }
                    }
                }
            }
        }

        $log2MFN = self::h264UE($br) + 4;
        $pocType = self::h264UE($br);
        $log2PocLsb = 0;
        if ($pocType === 0) {
            $log2PocLsb = self::h264UE($br) + 4;
        } elseif ($pocType === 1) {
            self::h264Bits($br, 1);
            self::h264SE($br);
            self::h264SE($br);
            $nr = self::h264UE($br);
            for ($i = 0; $i < $nr; $i++) {
                self::h264SE($br);
            }
        }
        self::h264UE($br);
        self::h264Bits($br, 1);
        $mbW = self::h264UE($br) + 1;
        $mbH = self::h264UE($br) + 1;
        $frmMbs = self::h264Bits($br, 1);
        if (!$frmMbs) {
            self::h264Bits($br, 1);
        }
        self::h264Bits($br, 1);
        if (self::h264Bits($br, 1)) {
            self::h264UE($br);
            self::h264UE($br);
            self::h264UE($br);
            self::h264UE($br);
        }
        return [
            'profile' => $prof,
            'mb_w' => $mbW,
            'mb_h' => $mbH,
            'log2_max_frame_num' => $log2MFN,
            'poc_type' => $pocType,
            'log2_max_poc_lsb' => $log2PocLsb,
            'frame_mbs_only_flag' => $frmMbs,
            'chroma_format_idc' => $chroma,
        ];
    }

    private static function decodeSpsRbsp(string $rbsp): array
    {
        $br = self::brCreate($rbsp);
        $profileIdc = self::brRead($br, 8);
        self::brRead($br, 8);
        $levelIdc = self::brRead($br, 8);
        self::brReadUE($br);

        $chromaFormatIdc = 1;
        $bitDepthLuma = 8;

        if (in_array($profileIdc, [100, 110, 122, 244, 44, 83, 86, 118, 128, 138, 139, 134, 135], true)) {
            $chromaFormatIdc = self::brReadUE($br);
            if ($chromaFormatIdc === 3) {
                self::brRead($br, 1);
            }
            $bitDepthLuma = self::brReadUE($br) + 8;
            self::brReadUE($br);
            self::brRead($br, 1);
            if (self::brRead($br, 1)) {
                $scalingListCount = ($chromaFormatIdc !== 3) ? 8 : 12;
                for ($i = 0; $i < $scalingListCount; $i++) {
                    if (self::brRead($br, 1)) {
                        $size = ($i < 6) ? 16 : 64;
                        $lastScale = 8;
                        $nextScale = 8;
                        for ($j = 0; $j < $size; $j++) {
                            if ($nextScale !== 0) {
                                $delta = self::brReadSE($br);
                                $nextScale = ($lastScale + $delta + 256) % 256;
                            }
                            $lastScale = ($nextScale === 0) ? $lastScale : $nextScale;
                        }
                    }
                }
            }
        }

        $log2MaxFrameNum = self::brReadUE($br) + 4;
        $picOrderCntType = self::brReadUE($br);
        $log2MaxPicOrderCntLsb = 0;

        if ($picOrderCntType === 0) {
            $log2MaxPicOrderCntLsb = self::brReadUE($br) + 4;
        } elseif ($picOrderCntType === 1) {
            self::brRead($br, 1);
            self::brReadSE($br);
            self::brReadSE($br);
            $numRefFrames = self::brReadUE($br);
            for ($i = 0; $i < $numRefFrames; $i++) {
                self::brReadSE($br);
            }
        }

        self::brReadUE($br);
        self::brRead($br, 1);

        $picWidthInMbs = self::brReadUE($br) + 1;
        $picHeightInMapUnits = self::brReadUE($br) + 1;
        $frameMbsOnlyFlag = self::brRead($br, 1);

        if (!$frameMbsOnlyFlag) {
            self::brRead($br, 1);
        }

        self::brRead($br, 1);

        $cropLeft = 0;
        $cropRight = 0;
        $cropTop = 0;
        $cropBottom = 0;
        if (self::brRead($br, 1)) {
            $cropLeft = self::brReadUE($br);
            $cropRight = self::brReadUE($br);
            $cropTop = self::brReadUE($br);
            $cropBottom = self::brReadUE($br);
        }

        $subWidthC = ($chromaFormatIdc === 1 || $chromaFormatIdc === 2) ? 2 : 1;
        $subHeightC = ($chromaFormatIdc === 1) ? 2 : 1;
        $cropUnitX = ($chromaFormatIdc === 0) ? 1 : $subWidthC;
        $cropUnitY = ($chromaFormatIdc === 0) ? 1 : $subHeightC * (2 - $frameMbsOnlyFlag);

        $picWidth = $picWidthInMbs * 16 - $cropUnitX * ($cropLeft + $cropRight);
        $picHeight = (2 - $frameMbsOnlyFlag) * $picHeightInMapUnits * 16 - $cropUnitY * ($cropTop + $cropBottom);

        return [
            'profile_idc' => $profileIdc,
            'level_idc' => $levelIdc,
            'chroma_format_idc' => $chromaFormatIdc,
            'bit_depth_luma' => $bitDepthLuma,
            'log2_max_frame_num' => $log2MaxFrameNum,
            'log2_max_pic_order_cnt_lsb' => $log2MaxPicOrderCntLsb,
            'pic_order_cnt_type' => $picOrderCntType,
            'pic_width_in_mbs' => $picWidthInMbs,
            'pic_height_in_map_units' => $picHeightInMapUnits,
            'frame_mbs_only_flag' => $frameMbsOnlyFlag,
            'pic_width' => $picWidth,
            'pic_height' => $picHeight,
        ];
    }

    private static function decodePpsRbsp(string $rbsp): array
    {
        $br = self::brCreate($rbsp);
        $ppsId = self::brReadUE($br);                        // pic_parameter_set_id
        $spsId = self::brReadUE($br);                        // seq_parameter_set_id
        $entropyCodingModeFlag = self::brRead($br, 1);       // entropy_coding_mode_flag
        self::brRead($br, 1);                                // bottom_field_pic_order_in_frame_present_flag
        $numSliceGroups = self::brReadUE($br) + 1;           // num_slice_groups_minus1 + 1

        if ($numSliceGroups > 1) {
            $sliceGroupMapType = self::brReadUE($br);
            if ($sliceGroupMapType === 0) {
                for ($i = 0; $i < $numSliceGroups; $i++) {
                    self::brReadUE($br);
                }
            } elseif ($sliceGroupMapType === 2) {
                for ($i = 0; $i < $numSliceGroups - 1; $i++) {
                    self::brReadUE($br);
                    self::brReadUE($br);
                }
            } elseif (in_array($sliceGroupMapType, [3, 4, 5], true)) {
                self::brRead($br, 1);
                self::brReadUE($br);
            } elseif ($sliceGroupMapType === 6) {
                $picSizeInMapUnits = self::brReadUE($br) + 1;
                $bits = 0;
                $n = $numSliceGroups;
                while ($n > 0) {
                    $bits++;
                    $n >>= 1;
                }
                for ($i = 0; $i < $picSizeInMapUnits; $i++) {
                    self::brRead($br, $bits);
                }
            }
        }

        self::brReadUE($br);                                 // num_ref_idx_l0_default_active_minus1
        self::brReadUE($br);                                 // num_ref_idx_l1_default_active_minus1
        self::brRead($br, 1);                                // weighted_pred_flag
        self::brRead($br, 2);                                // weighted_bipred_idc (H.264 spec 7.3.2.2)
        $picInitQp = self::brReadSE($br) + 26;              // pic_init_qp_minus26 + 26
        self::brReadSE($br);                                 // pic_init_qs_minus26
        $chromaQpOffset = self::brReadSE($br);               // chroma_qp_index_offset
        $dbFilterControlPresentFlag = self::brRead($br, 1);  // deblocking_filter_control_present_flag
        self::brRead($br, 1);                                // constrained_intra_pred_flag
        self::brRead($br, 1);                                // redundant_pic_cnt_present_flag
        // transform_8x8_mode_flag: only present in High Profile+ (after more_rbsp_data check)
        $transform8x8Flag = 0;
        if ($br['pos'] + 1 <= $br['len']) {
            $transform8x8Flag = self::brRead($br, 1);
        }

        return [
            'pps_id' => $ppsId,
            'sps_id' => $spsId,
            'entropy_coding_mode_flag' => $entropyCodingModeFlag,
            'pic_init_qp' => $picInitQp,
            'chroma_qp_offset' => $chromaQpOffset,
            'deblocking_filter_control_present_flag' => $dbFilterControlPresentFlag,
            'transform_8x8_mode_flag' => $transform8x8Flag,
        ];
    }

    private static function h264ParsePps(string $rbsp): array
    {
        $br = ['d' => $rbsp, 'p' => 0, 'l' => strlen($rbsp) * 8];
        self::h264UE($br);              // pic_parameter_set_id
        self::h264UE($br);              // seq_parameter_set_id
        $ecm = self::h264Bits($br, 1);  // entropy_coding_mode_flag
        self::h264Bits($br, 1);         // bottom_field_pic_order_in_frame_present_flag
        $nsg = self::h264UE($br) + 1;   // num_slice_groups_minus1 + 1
        if ($nsg > 1) {
            $sgt = self::h264UE($br);
            if ($sgt === 0) {
                for ($i = 0; $i < $nsg; $i++) {
                    self::h264UE($br);
                }
            } elseif ($sgt === 2) {
                for ($i = 0; $i < $nsg - 1; $i++) {
                    self::h264UE($br);
                    self::h264UE($br);
                }
            } elseif (in_array($sgt, [3, 4, 5], true)) {
                self::h264Bits($br, 1);
                self::h264UE($br);
            } elseif ($sgt === 6) {
                $psu = self::h264UE($br) + 1;
                $b = 0;
                $n = $nsg;
                while ($n > 0) {
                    $b++;
                    $n >>= 1;
                }
                for ($i = 0; $i < $psu; $i++) {
                    self::h264Bits($br, $b);
                }
            }
        }
        self::h264UE($br);              // num_ref_idx_l0_default_active_minus1
        self::h264UE($br);              // num_ref_idx_l1_default_active_minus1
        self::h264Bits($br, 1);         // weighted_pred_flag
        self::h264Bits($br, 2);         // weighted_bipred_idc (H.264 spec 7.3.2.2)
        $piq = self::h264SE($br) + 26;  // pic_init_qp_minus26 + 26
        self::h264SE($br);              // pic_init_qs_minus26
        $cqo = self::h264SE($br);       // chroma_qp_index_offset
        $dbf = self::h264Bits($br, 1);  // deblocking_filter_control_present_flag
        self::h264Bits($br, 1);         // constrained_intra_pred_flag
        self::h264Bits($br, 1);         // redundant_pic_cnt_present_flag
        // transform_8x8_mode_flag: only present in High Profile+ (after more_rbsp_data check)
        $t8 = 0;
        if ($br['p'] + 1 <= $br['l']) {
            $t8 = self::h264Bits($br, 1);
        }
        return ['entropy_coding_mode_flag' => $ecm, 'pic_init_qp' => $piq, 'chroma_qp_offset' => $cqo, 'db_filter_flag' => $dbf, 'transform_8x8_mode_flag' => $t8];
    }

    // CABAC tables
    private static function cabacRangeTabLPS(): array
    {
        return [
            [128, 176, 208, 240],
            [128, 167, 197, 227],
            [128, 158, 187, 216],
            [123, 150, 178, 205],
            [116, 142, 169, 195],
            [111, 135, 160, 185],
            [105, 128, 152, 175],
            [100, 122, 144, 166],
            [95, 116, 137, 158],
            [90, 110, 130, 150],
            [85, 104, 123, 142],
            [81, 99, 117, 135],
            [77, 94, 111, 128],
            [73, 89, 105, 122],
            [69, 85, 100, 116],
            [66, 80, 95, 110],
            [62, 76, 90, 104],
            [59, 72, 86, 99],
            [56, 69, 81, 94],
            [53, 65, 77, 89],
            [51, 62, 73, 85],
            [48, 59, 69, 80],
            [46, 56, 66, 76],
            [43, 53, 63, 72],
            [41, 50, 59, 69],
            [39, 48, 56, 65],
            [37, 45, 54, 62],
            [35, 43, 51, 59],
            [33, 41, 48, 56],
            [32, 39, 46, 53],
            [30, 37, 43, 50],
            [29, 35, 41, 48],
            [27, 33, 39, 45],
            [26, 31, 37, 43],
            [24, 30, 35, 41],
            [23, 28, 33, 39],
            [22, 27, 32, 37],
            [21, 26, 30, 35],
            [20, 24, 29, 33],
            [19, 23, 27, 31],
            [18, 22, 26, 30],
            [17, 21, 25, 28],
            [16, 20, 23, 27],
            [15, 19, 22, 25],
            [14, 18, 21, 24],
            [14, 17, 20, 23],
            [13, 16, 19, 22],
            [12, 15, 18, 21],
            [12, 14, 17, 20],
            [11, 14, 16, 19],
            [11, 13, 15, 18],
            [10, 12, 15, 17],
            [10, 12, 14, 16],
            [9, 11, 13, 15],
            [9, 11, 12, 14],
            [8, 10, 12, 14],
            [8, 9, 11, 13],
            [7, 9, 11, 12],
            [7, 9, 10, 12],
            [7, 8, 10, 11],
            [6, 8, 9, 11],
            [6, 7, 9, 10],
            [6, 7, 8, 9],
            [2, 2, 2, 2],
        ];
    }

    private static array $cabacTransLPS = [0, 0, 1, 2, 2, 4, 4, 5, 6, 7, 8, 9, 9, 11, 11, 12, 13, 13, 15, 15, 16, 16, 18, 18, 19, 19, 21, 21, 22, 22, 23, 24, 24, 25, 26, 26, 27, 27, 28, 29, 29, 30, 30, 30, 31, 32, 32, 33, 33, 33, 34, 34, 35, 35, 35, 36, 36, 36, 37, 37, 37, 38, 38, 63];
    private static array $cabacTransMPS = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36, 37, 38, 39, 40, 41, 42, 43, 44, 45, 46, 47, 48, 49, 50, 51, 52, 53, 54, 55, 56, 57, 58, 59, 60, 61, 62, 62, 63];

    private static function getCabacContextInitializationForIntraSlices(): array
    {
        return ContextInitialisation::forIntraSlices();
    }

    private static function cabacInit(string $rbsp, int $byteOffset, int $qp): array
    {
        $initMN = self::getCabacContextInitializationForIntraSlices();

        $ctxs = [];
        for ($i = 0; $i < 1024; $i++) {
            $mn = $initMN[$i] ?? [0, 64];
            $pre = max(1, min(126, (($mn[0] * $qp) >> 4) + $mn[1]));
            if ($pre <= 63) {
                $ctxs[$i] = ['s' => 63 - $pre, 'mps' => 0];
            } else {
                $ctxs[$i] = ['s' => $pre - 64, 'mps' => 1];
            }
        }

        $br = ['d' => $rbsp, 'p' => $byteOffset * 8, 'l' => strlen($rbsp) * 8];
        $range = 510;
        $offset = self::h264Bits($br, 9);

        return ['ctx' => $ctxs, 'range' => $range, 'offset' => $offset, 'br' => $br];
    }

    private static function getCoeffTokenTable(int $nC): array
    {
        if ($nC >= 8) {
            $t = [];
            for ($tc = 0; $tc <= 16; $tc++) {
                $maxT1 = min($tc, 3);
                for ($t1 = 0; $t1 <= $maxT1; $t1++) {
                    $prefix = $tc - $t1;
                    $suffix = $t1;
                    if ($tc === 0) {
                        $code = 3;
                        $len = 6;
                    } else {
                        $code = ($prefix << 2) | $suffix;
                        $len = 6;
                    }
                    $t[] = [$len, $code, $tc, $t1];
                }
            }
            return $t;
        }

        if ($nC === -1) {
            return [
                [1, 1, 0, 0],
                [2, 1, 1, 1],
                [3, 1, 2, 2],
                [4, 1, 3, 2],
                [5, 1, 3, 3],
                [5, 3, 4, 3],
                [6, 3, 2, 0],
                [6, 2, 4, 2],
                [7, 7, 1, 0],
                [7, 5, 3, 1],
                [7, 4, 2, 1],
                [7, 3, 4, 1],
                [7, 2, 3, 0],
                [8, 1, 4, 0],
            ];
        }

        if ($nC < 2) {
            return [
                [1, 1, 0, 0],
                [2, 1, 1, 1],
                [5, 3, 3, 3],
                [6, 5, 1, 0],
                [6, 4, 2, 2],
                [6, 3, 4, 3],
                [7, 7, 2, 1],
                [7, 6, 3, 2],
                [7, 5, 5, 3],
                [8, 7, 2, 0],
                [8, 5, 3, 1],
                [8, 4, 4, 2],
                [8, 3, 6, 3],
                [9, 7, 3, 0],
                [9, 5, 4, 1],
                [9, 4, 5, 2],
                [9, 3, 7, 3],
                [10, 7, 4, 0],
                [10, 5, 5, 1],
                [10, 4, 6, 2],
                [10, 3, 8, 3],
                [11, 7, 5, 0],
                [11, 5, 6, 1],
                [11, 4, 7, 2],
                [11, 3, 9, 3],
                [13, 15, 6, 0],
                [13, 14, 7, 1],
                [13, 13, 8, 2],
                [13, 12, 10, 3],
                [13, 1, 7, 0],
                [13, 11, 11, 3],
                [14, 15, 8, 0],
                [14, 14, 8, 1],
                [14, 13, 9, 2],
                [14, 11, 12, 3],
                [14, 1, 9, 0],
                [14, 12, 9, 1],
                [15, 15, 10, 0],
                [15, 14, 10, 1],
                [15, 13, 10, 2],
                [15, 11, 13, 3],
                [15, 1, 11, 0],
                [15, 12, 11, 1],
                [16, 15, 12, 0],
                [16, 14, 12, 1],
                [16, 13, 11, 2],
                [16, 7, 14, 3],
                [16, 11, 13, 0],
                [16, 12, 13, 1],
                [16, 9, 13, 2],
                [16, 3, 15, 3],
                [16, 5, 14, 0],
                [16, 10, 14, 1],
                [16, 8, 14, 2],
                [16, 1, 16, 3],
                [16, 4, 15, 0],
                [16, 6, 15, 1],
                [16, 2, 15, 2],
                [16, 0, 16, 0],
            ];
        }

        if ($nC < 4) {
            return [
                [2, 3, 0, 0],
                [2, 2, 1, 1],
                [3, 3, 2, 2],
                [4, 5, 3, 3],
                [6, 13, 1, 0],
                [5, 4, 4, 3],
                [6, 12, 2, 1],
                [6, 11, 3, 2],
                [6, 10, 5, 3],
                [7, 15, 2, 0],
                [7, 14, 3, 1],
                [7, 13, 4, 2],
                [7, 12, 6, 3],
                [8, 15, 3, 0],
                [8, 14, 4, 1],
                [8, 13, 5, 2],
                [8, 11, 7, 3],
                [9, 15, 4, 0],
                [9, 14, 5, 1],
                [9, 13, 6, 2],
                [9, 11, 8, 3],
                [11, 15, 5, 0],
                [11, 14, 6, 1],
                [11, 11, 7, 2],
                [11, 8, 9, 3],
                [11, 13, 7, 0],
                [11, 12, 8, 1],
                [11, 10, 9, 2],
                [11, 9, 10, 3],
                [12, 15, 9, 0],
                [12, 14, 9, 1],
                [12, 13, 10, 2],
                [12, 9, 11, 3],
                [12, 11, 10, 0],
                [12, 12, 10, 1],
                [12, 8, 11, 2],
                [12, 7, 12, 3],
                [13, 15, 11, 0],
                [13, 14, 11, 1],
                [13, 13, 12, 2],
                [13, 7, 13, 3],
                [13, 9, 12, 0],
                [13, 12, 12, 1],
                [13, 11, 13, 2],
                [13, 5, 14, 3],
                [13, 8, 13, 0],
                [13, 10, 13, 1],
                [14, 13, 14, 2],
                [14, 3, 15, 3],
                [14, 7, 14, 0],
                [14, 10, 14, 1],
                [14, 9, 15, 2],
                [14, 1, 16, 3],
                [14, 5, 15, 0],
                [14, 8, 15, 1],
                [14, 6, 16, 0],
                [14, 4, 16, 1],
                [14, 11, 16, 2],
            ];
        }

        return [
            [4, 15, 0, 0],
            [4, 14, 1, 1],
            [4, 13, 2, 2],
            [4, 12, 3, 3],
            [4, 11, 4, 3],
            [5, 15, 1, 0],
            [5, 14, 2, 1],
            [5, 13, 3, 2],
            [5, 12, 5, 3],
            [5, 11, 4, 2],
            [6, 15, 2, 0],
            [6, 14, 3, 1],
            [6, 11, 6, 3],
            [6, 8, 4, 1],
            [6, 13, 5, 2],
            [6, 12, 7, 3],
            [6, 9, 5, 1],
            [7, 15, 3, 0],
            [7, 14, 4, 0],
            [7, 13, 6, 2],
            [7, 11, 8, 3],
            [7, 12, 6, 1],
            [7, 10, 7, 2],
            [7, 9, 9, 3],
            [8, 15, 5, 0],
            [8, 14, 7, 1],
            [8, 13, 8, 2],
            [8, 11, 10, 3],
            [8, 12, 7, 0],
            [8, 10, 9, 2],
            [8, 9, 11, 3],
            [9, 15, 6, 0],
            [9, 14, 8, 1],
            [9, 13, 10, 2],
            [9, 11, 12, 3],
            [9, 12, 8, 0],
            [9, 10, 9, 1],
            [9, 9, 13, 3],
            [10, 15, 9, 0],
            [10, 14, 10, 1],
            [10, 13, 11, 2],
            [10, 11, 14, 3],
            [10, 12, 10, 0],
            [10, 10, 11, 1],
            [10, 9, 15, 3],
            [10, 8, 12, 2],
            [10, 7, 13, 2],
            [10, 6, 14, 2],
            [10, 5, 15, 2],
            [10, 4, 16, 2],
            [10, 3, 11, 0],
            [10, 2, 12, 0],
            [10, 1, 13, 0],
            [10, 15, 16, 3],
            [10, 0, 14, 0],
            [10, 14, 12, 1],
            [10, 13, 13, 1],
            [10, 12, 14, 1],
            [10, 11, 15, 1],
            [10, 10, 16, 1],
            [10, 9, 15, 0],
            [10, 8, 16, 0],
        ];
    }

    private static function cabacRenorm(array &$cab): void
    {
        while ($cab['range'] < 256) {
            $cab['range'] <<= 1;
            $cab['offset'] = ($cab['offset'] << 1) | self::h264Bits($cab['br'], 1);
        }
    }

    private static function cabacDecodeBin(array &$cab, int $ctxIdx): int
    {
        $pState = $cab['ctx'][$ctxIdx]['s'];
        $valMPS = $cab['ctx'][$ctxIdx]['mps'];
        $rLPS = self::cabacRangeTabLPS()[$pState][($cab['range'] >> 6) & 3] ?? 2;
        $cab['range'] -= $rLPS;

        if ($cab['offset'] >= $cab['range']) {
            $binVal = 1 - $valMPS;
            $cab['offset'] -= $cab['range'];
            $cab['range'] = $rLPS;
            if ($pState === 0) {
                $cab['ctx'][$ctxIdx]['mps'] = 1 - $valMPS;
            }
            $cab['ctx'][$ctxIdx]['s'] = self::$cabacTransLPS[$pState];
        } else {
            $binVal = $valMPS;
            $cab['ctx'][$ctxIdx]['s'] = self::$cabacTransMPS[$pState];
        }
        self::cabacRenorm($cab);
        return $binVal;
    }

    private static function cabacDecodeBypass(array &$cab): int
    {
        $cab['offset'] = ($cab['offset'] << 1) | self::h264Bits($cab['br'], 1);
        if ($cab['offset'] >= $cab['range']) {
            $cab['offset'] -= $cab['range'];
            return 1;
        }
        return 0;
    }

    private static function cabacDecodeTerminate(array &$cab): int
    {
        $cab['range'] -= 2;
        if ($cab['offset'] >= $cab['range']) {
            return 1;
        }
        self::cabacRenorm($cab);
        return 0;
    }

    private static function readCoeffToken(array &$br, int $nC): array
    {
        if ($nC >= 8) {
            $code = self::brRead($br, 6);
            if ($code === 3) {
                return [0, 0];
            }
            $tc = ($code >> 2) + 1;
            $t1 = $code & 3;
            if ($tc > 16) {
                $tc = 16;
            }
            if ($t1 > min($tc, 3)) {
                $t1 = min($tc, 3);
            }
            return [$tc, $t1];
        }

        $table = self::getCoeffTokenTable($nC);
        $savedPos = $br['pos'];

        usort($table, function ($a, $b) {
            return $a[0] - $b[0];
        });

        foreach ($table as [$len, $code, $tc, $t1]) {
            $br['pos'] = $savedPos;
            if ($br['pos'] + $len > $br['len']) {
                continue;
            }
            $bits = self::brRead($br, $len);
            if ($bits === $code) {
                return [$tc, $t1];
            }
        }

        $br['pos'] = $savedPos;
        self::brRead($br, 1);
        return [0, 0];
    }

    private static function cabacDecodeMbTypeI(array &$cab, bool $hasLeftIntra16x16OrPcmMacroblock, bool $hasTopIntra16x16OrPcmMacroblock): int
    {
		$contextIndex = 3;
		if ($hasLeftIntra16x16OrPcmMacroblock) {
			$contextIndex++;
		}
		if ($hasTopIntra16x16OrPcmMacroblock) {
			$contextIndex++;
		}

		if (self::cabacDecodeBin($cab, $contextIndex) === 0) {
			return 0;
		}
		if (self::cabacDecodeTerminate($cab) === 1) {
			return 25;
		}

		$macroblockType = 1;
		$macroblockType += 12 * self::cabacDecodeBin($cab, 6);
		if (self::cabacDecodeBin($cab, 7) !== 0) {
			$macroblockType += 4 + 4 * self::cabacDecodeBin($cab, 8);
		}
		$macroblockType += 2 * self::cabacDecodeBin($cab, 9);
		$macroblockType += self::cabacDecodeBin($cab, 10);
		return $macroblockType;
    }

    private static function cabacDecodeIntra4x4PredMode(array &$cab, int $predMode): int
    {
		if (self::cabacDecodeBin($cab, 68) !== 0) {
			return $predMode;
		}

		$mode = self::cabacDecodeBin($cab, 69);
		$mode += self::cabacDecodeBin($cab, 69) << 1;
		$mode += self::cabacDecodeBin($cab, 69) << 2;
		if ($mode >= $predMode) {
			$mode++;
		}
		return $mode;
    }

    private static function cabacDecodeIntraChromaPredMode(array &$cab, int $leftChromaPredMode, int $topChromaPredMode): int
    {
		$contextIndex = 64;
		if ($leftChromaPredMode !== 0) {
			$contextIndex++;
		}
		if ($topChromaPredMode !== 0) {
			$contextIndex++;
		}

		if (self::cabacDecodeBin($cab, $contextIndex) === 0) {
			return 0;
		}
		if (self::cabacDecodeBin($cab, 67) === 0) {
			return 1;
		}
		if (self::cabacDecodeBin($cab, 67) === 0) {
			return 2;
		}
		return 3;
    }

    private static function cabacDecodeCodedBlockPatternLuma(array &$cab, int $leftCodedBlockPattern, int $topCodedBlockPattern): int
    {
		$codedBlockPattern = 0;

		$contextIndex = (($leftCodedBlockPattern & 0x02) === 0 ? 1 : 0) + (($topCodedBlockPattern & 0x04) === 0 ? 2 : 0);
		$codedBlockPattern += self::cabacDecodeBin($cab, 73 + $contextIndex);

		$contextIndex = (($codedBlockPattern & 0x01) === 0 ? 1 : 0) + (($topCodedBlockPattern & 0x08) === 0 ? 2 : 0);
		$codedBlockPattern += self::cabacDecodeBin($cab, 73 + $contextIndex) << 1;

		$contextIndex = (($leftCodedBlockPattern & 0x08) === 0 ? 1 : 0) + (($codedBlockPattern & 0x01) === 0 ? 2 : 0);
		$codedBlockPattern += self::cabacDecodeBin($cab, 73 + $contextIndex) << 2;

		$contextIndex = (($codedBlockPattern & 0x04) === 0 ? 1 : 0) + (($codedBlockPattern & 0x02) === 0 ? 2 : 0);
		$codedBlockPattern += self::cabacDecodeBin($cab, 73 + $contextIndex) << 3;

		return $codedBlockPattern;
    }

    private static function cabacDecodeCodedBlockPatternChroma(array &$cab, int $leftCodedBlockPattern, int $topCodedBlockPattern): int
    {
		$leftChromaPattern = ($leftCodedBlockPattern >> 4) & 0x03;
		$topChromaPattern = ($topCodedBlockPattern >> 4) & 0x03;

		$contextIndex = 77;
		if ($leftChromaPattern > 0) {
			$contextIndex++;
		}
		if ($topChromaPattern > 0) {
			$contextIndex += 2;
		}
		if (self::cabacDecodeBin($cab, $contextIndex) === 0) {
			return 0;
		}

		$contextIndex = 81;
		if ($leftChromaPattern === 2) {
			$contextIndex++;
		}
		if ($topChromaPattern === 2) {
			$contextIndex += 2;
		}
		return 1 + self::cabacDecodeBin($cab, $contextIndex);
    }

    private static function cabacDecodeMbQpDelta(array &$cab, bool $hasPreviousNonZeroQuantizerScaleDifference): int
    {
        $firstContextIndex = 60 + ($hasPreviousNonZeroQuantizerScaleDifference ? 1 : 0);
        if (self::cabacDecodeBin($cab, $firstContextIndex) === 0) {
            return 0;
        }

        $value = 1;
        $contextOffset = 2;
        while (self::cabacDecodeBin($cab, 60 + $contextOffset) !== 0) {
            $contextOffset = 3;
            $value++;
            if ($value > 103) {
                throw new Exception('CABAC mb_qp_delta exceeded the supported range.');
            }
        }

        if (($value & 1) !== 0) {
            return ($value + 1) >> 1;
        }
        return -(($value + 1) >> 1);
    }

	private static function h264WrapLumaQuantizationParameter(int $quantizationParameter): int
	{
		$wrappedQuantizationParameter = $quantizationParameter % 52;
		if ($wrappedQuantizationParameter < 0) {
			$wrappedQuantizationParameter += 52;
		}
		return $wrappedQuantizationParameter;
	}

	private static function h264ResolveChromaQuantizationParameter(int $lumaQuantizationParameter, int $chromaQuantizationOffset): int
	{
		static $chromaQuantizationParameterTable = [
			0, 1, 2, 3, 4, 5, 6, 7, 8, 9,
			10, 11, 12, 13, 14, 15, 16, 17, 18, 19,
			20, 21, 22, 23, 24, 25, 26, 27, 28, 29,
			29, 30, 31, 32, 32, 33, 34, 34, 35, 35,
			36, 36, 37, 37, 37, 38, 38, 38, 39, 39,
			39, 39,
		];

		$chromaQuantizationIndex = self::h264WrapLumaQuantizationParameter($lumaQuantizationParameter + $chromaQuantizationOffset);
		return $chromaQuantizationParameterTable[$chromaQuantizationIndex];
	}

    private static function readTotalZeros(array &$br, int $totalCoeff, int $maxCoeff): int
    {
        if ($totalCoeff >= $maxCoeff) {
            return 0;
        }

        $maxZeros = $maxCoeff - $totalCoeff;

        if ($maxCoeff === 4) {
            $code = self::brRead($br, 1);
            if ($totalCoeff === 1) {
                if ($code === 0) {
                    $b2 = self::brRead($br, 1);
                    if ($b2 === 0) {
                        return self::brRead($br, 1) === 0 ? 3 : 2;
                    }
                    return 1;
                }
                return 0;
            }
            if ($totalCoeff === 2) {
                if ($code === 0) {
                    return self::brRead($br, 1) === 0 ? 2 : 1;
                }
                return 0;
            }
            return $code === 0 ? 1 : 0;
        }

        $zeros = 0;
        while ($zeros < $maxZeros && self::brRead($br, 1) === 0) {
            $zeros++;
        }
        return min($zeros, $maxZeros);
    }

    private static function cabacDecodeSignificantCoeffFlag(array &$cab, int $ctxBase, int $pos): int
    {
        return self::cabacDecodeBin($cab, $ctxBase + min($pos, 14));
    }

    private static function cabacDecodeLastSignificantCoeffFlag(array &$cab, int $ctxBase, int $pos): int
    {
        return self::cabacDecodeBin($cab, $ctxBase + min($pos, 14));
    }

    private static function readRunBefore(array &$br, int $zerosLeft): int
    {
        if ($zerosLeft === 0) {
            return 0;
        }
        if ($zerosLeft === 1) {
            return self::brRead($br, 1);
        }
        if ($zerosLeft === 2) {
            $b = self::brRead($br, 1);
            if ($b === 1) {
                return 0;
            }
            return self::brRead($br, 1) === 1 ? 1 : 2;
        }

        $run = 0;
        $maxRun = min($zerosLeft, 15);
        while ($run < $maxRun && self::brRead($br, 1) === 0) {
            $run++;
        }
        return $run;
    }

    private static function cabacDecodeCoeffAbsLevel(array &$cab, int $ctxBase, int &$nodeContext): int
    {
		$absLevelOneContextIndexes = [1, 2, 3, 4, 0, 0, 0, 0];
		$absLevelGreaterThanOneContextIndexes = [5, 5, 5, 5, 6, 7, 8, 9];
		$nodeContextAfterLevelOne = [1, 2, 3, 3, 4, 5, 6, 7];
		$nodeContextAfterLevelGreaterThanOne = [4, 4, 4, 4, 5, 6, 7, 7];

		$absoluteLevelContextIndex = $ctxBase + $absLevelOneContextIndexes[$nodeContext];
		if (self::cabacDecodeBin($cab, $absoluteLevelContextIndex) === 0) {
			$nodeContext = $nodeContextAfterLevelOne[$nodeContext];
			$coefficientAbsoluteValue = 1;
		} else {
			$coefficientAbsoluteValue = 2;
			$absoluteLevelGreaterThanOneContextIndex = $ctxBase + $absLevelGreaterThanOneContextIndexes[$nodeContext];
			$nodeContext = $nodeContextAfterLevelGreaterThanOne[$nodeContext];

			while ($coefficientAbsoluteValue < 15 && self::cabacDecodeBin($cab, $absoluteLevelGreaterThanOneContextIndex) !== 0) {
				$coefficientAbsoluteValue++;
			}

			if ($coefficientAbsoluteValue >= 15) {
				$prefixLength = 0;
				while (self::cabacDecodeBypass($cab) !== 0 && $prefixLength < 23) {
					$prefixLength++;
				}

				$coefficientAbsoluteValue = 1;
				while ($prefixLength-- > 0) {
					$coefficientAbsoluteValue += $coefficientAbsoluteValue + self::cabacDecodeBypass($cab);
				}
				$coefficientAbsoluteValue += 14;
			}
		}

		$signBit = self::cabacDecodeBypass($cab);
		return $signBit === 0 ? $coefficientAbsoluteValue : -$coefficientAbsoluteValue;
    }

    private static function cabacDecodeBypassBits(array &$cab, int $n): int
    {
        $v = 0;
        for ($i = 0; $i < $n; $i++) {
            $v = ($v << 1) | self::cabacDecodeBypass($cab);
        }
        return $v;
    }

    private static function cabacGetMostProbableIntra4x4PredictionMode(array $lumaPredictionModes, int $blockX, int $blockY): int
    {
		$leftPredictionMode = $blockX > 0 ? ($lumaPredictionModes[$blockY][$blockX - 1] ?? 2) : 2;
		$topPredictionMode = $blockY > 0 ? ($lumaPredictionModes[$blockY - 1][$blockX] ?? 2) : 2;
		return min($leftPredictionMode, $topPredictionMode);
    }

    private static function cabacGetResidualBlockDescriptor(int $category, int $codedBlockFlagContextIndex, int $maxCoeff): array
    {
		[$significantBase, $lastBase, $absoluteLevelBase] = match ($category) {
			self::CABAC_BLOCK_CATEGORY_LUMA_DC => [105, 166, 227],
			self::CABAC_BLOCK_CATEGORY_LUMA_AC_16X16 => [120, 181, 237],
			self::CABAC_BLOCK_CATEGORY_LUMA_4X4 => [134, 195, 247],
			self::CABAC_BLOCK_CATEGORY_CHROMA_DC => [149, 210, 257],
			self::CABAC_BLOCK_CATEGORY_CHROMA_AC => [152, 213, 266],
			self::CABAC_BLOCK_CATEGORY_LUMA_8X8 => [402, 417, 426],
			default => throw new Exception('Unsupported CABAC residual category.'),
		};

		return [
			'max_coeff' => $maxCoeff,
			'significant_base' => $significantBase,
			'last_base' => $lastBase,
			'absolute_level_base' => $absoluteLevelBase,
			'coded_block_flag_context_index' => $codedBlockFlagContextIndex,
		];
    }

    private static function cabacGetCodedBlockFlagContextIndex(array $decoderState, array $blockDescriptor): int
    {
		$category = (int) ($blockDescriptor['category'] ?? self::CABAC_BLOCK_CATEGORY_LUMA_4X4);
		$macroblockX = (int) ($blockDescriptor['macroblock_x'] ?? 0);
		$macroblockY = (int) ($blockDescriptor['macroblock_y'] ?? 0);
		$blockIndex = (int) ($blockDescriptor['block_index'] ?? 0);
		$planeIndex = (int) ($blockDescriptor['plane_index'] ?? 0);
		$lumaBlockGridCoordinates = [[0, 0], [1, 0], [0, 1], [1, 1], [2, 0], [3, 0], [2, 1], [3, 1], [0, 2], [1, 2], [0, 3], [1, 3], [2, 2], [3, 2], [2, 3], [3, 3]];
		$chromaBlockGridCoordinates = [[0, 0], [1, 0], [0, 1], [1, 1]];

		switch ($category) {
			case self::CABAC_BLOCK_CATEGORY_LUMA_DC:
				$leftContextPresence = $macroblockX > 0 ? (($decoderState['luma_dc_presence'][$macroblockY][$macroblockX - 1] ?? false) ? 1 : 0) : 0;
				$topContextPresence = $macroblockY > 0 ? (($decoderState['luma_dc_presence'][$macroblockY - 1][$macroblockX] ?? false) ? 1 : 0) : 0;
				return 85 + $leftContextPresence + ($topContextPresence << 1);

			case self::CABAC_BLOCK_CATEGORY_LUMA_AC_16X16:
			case self::CABAC_BLOCK_CATEGORY_LUMA_4X4:
			case self::CABAC_BLOCK_CATEGORY_LUMA_8X8:
				$lumaBlockCoordinates = $lumaBlockGridCoordinates[$blockIndex] ?? [0, 0];
				$pictureBlockX = $macroblockX * 4 + $lumaBlockCoordinates[0];
				$pictureBlockY = $macroblockY * 4 + $lumaBlockCoordinates[1];
				$leftContextPresence = $pictureBlockX > 0 ? ((($decoderState['luma_non_zero_counts'][$pictureBlockY][$pictureBlockX - 1] ?? 0) > 0) ? 1 : 0) : 1;
				$topContextPresence = $pictureBlockY > 0 ? ((($decoderState['luma_non_zero_counts'][$pictureBlockY - 1][$pictureBlockX] ?? 0) > 0) ? 1 : 0) : 1;
				$baseContextIndex = match ($category) {
					self::CABAC_BLOCK_CATEGORY_LUMA_AC_16X16 => 89,
					self::CABAC_BLOCK_CATEGORY_LUMA_4X4 => 93,
					self::CABAC_BLOCK_CATEGORY_LUMA_8X8 => 1012,
					default => 93,
				};
				return $baseContextIndex + $leftContextPresence + ($topContextPresence << 1);

			case self::CABAC_BLOCK_CATEGORY_CHROMA_DC:
				$leftContextPresence = $macroblockX > 0 ? (($decoderState['chroma_dc_presence'][$macroblockY][$macroblockX - 1][$planeIndex] ?? false) ? 1 : 0) : 0;
				$topContextPresence = $macroblockY > 0 ? (($decoderState['chroma_dc_presence'][$macroblockY - 1][$macroblockX][$planeIndex] ?? false) ? 1 : 0) : 0;
				return 97 + $leftContextPresence + ($topContextPresence << 1);

			case self::CABAC_BLOCK_CATEGORY_CHROMA_AC:
				$chromaBlockCoordinates = $chromaBlockGridCoordinates[$blockIndex] ?? [0, 0];
				$pictureBlockX = $macroblockX * 2 + $chromaBlockCoordinates[0];
				$pictureBlockY = $macroblockY * 2 + $chromaBlockCoordinates[1];
				$chromaNonZeroCounts = $decoderState['chroma_non_zero_counts'][$planeIndex] ?? [];
				$leftContextPresence = $pictureBlockX > 0 ? ((($chromaNonZeroCounts[$pictureBlockY][$pictureBlockX - 1] ?? 0) > 0) ? 1 : 0) : 1;
				$topContextPresence = $pictureBlockY > 0 ? ((($chromaNonZeroCounts[$pictureBlockY - 1][$pictureBlockX] ?? 0) > 0) ? 1 : 0) : 1;
				return 101 + $leftContextPresence + ($topContextPresence << 1);
		}

		throw new Exception('Unsupported CABAC coded block flag category.');
    }

    private static function cabacDecodeBlock(array &$cab, array $descriptor): array
    {
		$maxCoeff = (int) ($descriptor['max_coeff'] ?? 0);
		$coeffs = array_fill(0, $maxCoeff, 0);
		if ($maxCoeff <= 0) {
			return $coeffs;
		}

		$codedBlockFlagContextIndex = (int) ($descriptor['coded_block_flag_context_index'] ?? 0);
		if (self::cabacDecodeBin($cab, $codedBlockFlagContextIndex) === 0) {
			return $coeffs;
		}

		$significantBase = (int) ($descriptor['significant_base'] ?? 0);
		$lastBase = (int) ($descriptor['last_base'] ?? 0);
		$absoluteLevelBase = (int) ($descriptor['absolute_level_base'] ?? 0);
		$significantCoeffFlagOffset8x8 = [0, 1, 2, 3, 4, 5, 5, 4, 4, 3, 3, 4, 4, 4, 5, 5, 4, 4, 4, 4, 3, 3, 6, 7, 7, 7, 8, 9, 10, 9, 8, 7, 7, 6, 11, 12, 13, 11, 6, 7, 8, 9, 14, 10, 9, 8, 6, 11, 12, 13, 11, 6, 9, 14, 10, 9, 11, 12, 13, 11, 14, 10, 12];
		$lastCoeffFlagOffset8x8 = [0, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 3, 3, 3, 3, 3, 3, 3, 3, 4, 4, 4, 4, 4, 4, 4, 4, 5, 5, 5, 5, 6, 6, 6, 6, 7, 7, 7, 7, 8, 8, 8];

		$significantPositions = [];
		$foundLastCoefficient = false;
		for ($coefficientIndex = 0; $coefficientIndex < $maxCoeff - 1; $coefficientIndex++) {
			$significantContextIndex = $significantBase + ($maxCoeff === 64 ? $significantCoeffFlagOffset8x8[$coefficientIndex] : min($coefficientIndex, 14));
			if (self::cabacDecodeBin($cab, $significantContextIndex) !== 0) {
				$significantPositions[] = $coefficientIndex;
				$lastContextIndex = $lastBase + ($maxCoeff === 64 ? $lastCoeffFlagOffset8x8[$coefficientIndex] : min($coefficientIndex, 14));
				if (self::cabacDecodeBin($cab, $lastContextIndex) !== 0) {
					$foundLastCoefficient = true;
					break;
				}
			}
		}
		if (!$foundLastCoefficient) {
			$significantPositions[] = $maxCoeff - 1;
		}

		$nodeContext = 0;
		for ($positionIndex = count($significantPositions) - 1; $positionIndex >= 0; $positionIndex--) {
			$coefficientPosition = $significantPositions[$positionIndex];
			$coeffs[$coefficientPosition] = self::cabacDecodeCoeffAbsLevel($cab, $absoluteLevelBase, $nodeContext);
		}

		return $coeffs;
    }

    private static function countNonZeroValues(array $values): int
    {
		$nonZeroCount = 0;
		foreach ($values as $value) {
			if ($value !== 0) {
				$nonZeroCount++;
			}
		}
		return $nonZeroCount;
    }

	private static function h264DecodeMbsCabac(array &$cab, int $mbW, int $mbH, int $firstMacroblockInSlice, int $qpY, array $pps, array &$yP, array &$uP, array &$vP): void
	{
		$qpC = self::h264ResolveChromaQuantizationParameter($qpY, $pps['chroma_qp_offset']);
		$lumaW = $mbW * 16;
		$lumaH = $mbH * 16;
		$chrW = $mbW * 8;
		$chrH = $mbH * 8;
		$totalMbs = $mbW * $mbH;
		$blkScan = [[0, 0], [4, 0], [0, 4], [4, 4], [8, 0], [12, 0], [8, 4], [12, 4], [0, 8], [4, 8], [0, 12], [4, 12], [8, 8], [12, 8], [8, 12], [12, 12]];
		$lumaBlockGridCoordinates = [[0, 0], [1, 0], [0, 1], [1, 1], [2, 0], [3, 0], [2, 1], [3, 1], [0, 2], [1, 2], [0, 3], [1, 3], [2, 2], [3, 2], [2, 3], [3, 3]];
		$zigzag = [0, 1, 4, 8, 5, 2, 3, 6, 9, 12, 13, 10, 7, 11, 14, 15];
		$zigzag8x8 = [0, 1, 8, 16, 9, 2, 3, 10, 17, 24, 32, 25, 18, 11, 4, 5, 12, 19, 26, 33, 40, 48, 41, 34, 27, 20, 13, 6, 7, 14, 21, 28, 35, 42, 49, 56, 57, 50, 43, 36, 29, 22, 15, 23, 30, 37, 44, 51, 58, 59, 52, 45, 38, 31, 39, 46, 53, 60, 61, 54, 47, 55, 62, 63];
		$chrBlk = [[0, 0], [4, 0], [0, 4], [4, 4]];
		$macroblockTypes = array_fill(0, $mbH, array_fill(0, $mbW, 0));
		$macroblockUsesTransform8x8 = array_fill(0, $mbH, array_fill(0, $mbW, false));
		$macroblockCodedBlockPatterns = array_fill(0, $mbH, array_fill(0, $mbW, 0));
		$macroblockChromaPredictionModes = array_fill(0, $mbH, array_fill(0, $mbW, 0));
		$macroblockLumaDcPresence = array_fill(0, $mbH, array_fill(0, $mbW, false));
		$macroblockChromaDcPresence = array_fill(0, $mbH, array_fill(0, $mbW, [false, false]));
		$lumaPredictionModes = array_fill(0, $mbH * 4, array_fill(0, $mbW * 4, 2));
		$lumaNonZeroCounts = array_fill(0, $mbH * 4, array_fill(0, $mbW * 4, 0));
		$chromaNonZeroCounts = [
			array_fill(0, $mbH * 2, array_fill(0, $mbW * 2, 0)),
			array_fill(0, $mbH * 2, array_fill(0, $mbW * 2, 0)),
		];
		$decoderState = [
			'luma_dc_presence' => &$macroblockLumaDcPresence,
			'chroma_dc_presence' => &$macroblockChromaDcPresence,
			'coded_block_patterns' => &$macroblockCodedBlockPatterns,
			'luma_non_zero_counts' => &$lumaNonZeroCounts,
			'chroma_non_zero_counts' => &$chromaNonZeroCounts,
		];
		$lastQuantizerScaleDifference = 0;

		for ($mbIdx = $firstMacroblockInSlice; $mbIdx < $totalMbs; $mbIdx++) {
			$mbX = $mbIdx % $mbW;
			$mbY = intdiv($mbIdx, $mbW);
			try {
				$leftMacroblockType = (int) ($macroblockTypes[$mbY][$mbX - 1] ?? 0);
				$topMacroblockType = (int) ($macroblockTypes[$mbY - 1][$mbX] ?? 0);
				$hasLeftIntra16x16OrPcmMacroblock = $mbX > 0 && (($leftMacroblockType >= 1 && $leftMacroblockType <= 24) || $leftMacroblockType === 25);
				$hasTopIntra16x16OrPcmMacroblock = $mbY > 0 && (($topMacroblockType >= 1 && $topMacroblockType <= 24) || $topMacroblockType === 25);
				$mbType = self::cabacDecodeMbTypeI($cab, $hasLeftIntra16x16OrPcmMacroblock, $hasTopIntra16x16OrPcmMacroblock);
				$macroblockTypes[$mbY][$mbX] = $mbType;
				if ($mbType === 25) {
					$cab['br']['p'] = (($cab['br']['p'] + 7) >> 3) << 3;
					for ($y = 0; $y < 16; $y++) {
						for ($x = 0; $x < 16; $x++) {
							$yP[$mbY * 16 + $y][$mbX * 16 + $x] = self::h264Bits($cab['br'], 8);
						}
					}
					for ($y = 0; $y < 8; $y++) {
						for ($x = 0; $x < 8; $x++) {
							$uP[$mbY * 8 + $y][$mbX * 8 + $x] = self::h264Bits($cab['br'], 8);
							$vP[$mbY * 8 + $y][$mbX * 8 + $x] = self::h264Bits($cab['br'], 8);
						}
					}
					$macroblockUsesTransform8x8[$mbY][$mbX] = false;
					$macroblockCodedBlockPatterns[$mbY][$mbX] = 47;
					$macroblockLumaDcPresence[$mbY][$mbX] = true;
					$macroblockChromaDcPresence[$mbY][$mbX] = [true, true];
					$macroblockChromaPredictionModes[$mbY][$mbX] = 0;
					for ($blockY = 0; $blockY < 4; $blockY++) {
						for ($blockX = 0; $blockX < 4; $blockX++) {
							$lumaNonZeroCounts[$mbY * 4 + $blockY][$mbX * 4 + $blockX] = 16;
						}
					}
					for ($blockY = 0; $blockY < 2; $blockY++) {
						for ($blockX = 0; $blockX < 2; $blockX++) {
							$chromaNonZeroCounts[0][$mbY * 2 + $blockY][$mbX * 2 + $blockX] = 16;
							$chromaNonZeroCounts[1][$mbY * 2 + $blockY][$mbX * 2 + $blockX] = 16;
						}
					}
					$cab['range'] = 510;
					$cab['offset'] = self::h264Bits($cab['br'], 9);
					$lastQuantizerScaleDifference = 0;
					continue;
				}

				$isI16 = ($mbType >= 1 && $mbType <= 24);
				$leftChromaPredMode = $mbX > 0 ? ($macroblockChromaPredictionModes[$mbY][$mbX - 1] ?? 0) : 0;
				$topChromaPredMode = $mbY > 0 ? ($macroblockChromaPredictionModes[$mbY - 1][$mbX] ?? 0) : 0;
				$chromaPredMode = 0;
				$cbpLuma = 0;
				$cbpChroma = 0;

				if ($isI16) {
					$i16mode = ($mbType - 1) % 4;
					$cbpChroma = intdiv($mbType - 1, 4) % 3;
					$cbpLuma = ($mbType > 12) ? 15 : 0;
					$chromaPredMode = self::cabacDecodeIntraChromaPredMode($cab, $leftChromaPredMode, $topChromaPredMode);
					$macroblockChromaPredictionModes[$mbY][$mbX] = $chromaPredMode;
					$macroblockCodedBlockPatterns[$mbY][$mbX] = $cbpLuma | ($cbpChroma << 4);

					self::h264PredI16x16($yP, $mbX, $mbY, $i16mode, $lumaW, $lumaH);
					self::h264PredChroma($uP, $mbX, $mbY, $chromaPredMode, $chrW, $chrH);
					self::h264PredChroma($vP, $mbX, $mbY, $chromaPredMode, $chrW, $chrH);

					$qpDelta = self::cabacDecodeMbQpDelta($cab, $lastQuantizerScaleDifference !== 0);
					$lastQuantizerScaleDifference = $qpDelta;
					$qpY = self::h264WrapLumaQuantizationParameter($qpY + $qpDelta);
					$qpC = self::h264ResolveChromaQuantizationParameter($qpY, $pps['chroma_qp_offset']);

					$lumaDcContextIndex = self::cabacGetCodedBlockFlagContextIndex($decoderState, [
						'category' => self::CABAC_BLOCK_CATEGORY_LUMA_DC,
						'macroblock_x' => $mbX,
						'macroblock_y' => $mbY,
						'block_index' => 0,
					]);
					$dcBlock = self::cabacDecodeBlock($cab, self::cabacGetResidualBlockDescriptor(self::CABAC_BLOCK_CATEGORY_LUMA_DC, $lumaDcContextIndex, 16));
					$macroblockLumaDcPresence[$mbY][$mbX] = self::countNonZeroValues($dcBlock) > 0;
					$dcT = self::h264InvHadamard4x4($dcBlock, $qpY);

					$dcRemap = [0, 1, 4, 5, 2, 3, 6, 7, 8, 9, 12, 13, 10, 11, 14, 15];
					for ($blk = 0; $blk < 16; $blk++) {
						$acCoeffs = array_fill(0, 15, 0);
						if ($cbpLuma > 0) {
							$lumaAcContextIndex = self::cabacGetCodedBlockFlagContextIndex($decoderState, [
								'category' => self::CABAC_BLOCK_CATEGORY_LUMA_AC_16X16,
								'macroblock_x' => $mbX,
								'macroblock_y' => $mbY,
								'block_index' => $blk,
							]);
							$acCoeffs = self::cabacDecodeBlock($cab, self::cabacGetResidualBlockDescriptor(self::CABAC_BLOCK_CATEGORY_LUMA_AC_16X16, $lumaAcContextIndex, 15));
						}
						$lumaBlockCoordinates = $lumaBlockGridCoordinates[$blk];
						$lumaNonZeroCounts[$mbY * 4 + $lumaBlockCoordinates[1]][$mbX * 4 + $lumaBlockCoordinates[0]] = self::countNonZeroValues($acCoeffs);

						$coefficients = array_fill(0, 16, 0);
						$coefficients[0] = $dcT[$dcRemap[$blk]];
						for ($coefficientIndex = 0; $coefficientIndex < 15; $coefficientIndex++) {
							$coefficients[$coefficientIndex + 1] = self::h264Dequant($acCoeffs[$coefficientIndex], $qpY, $coefficientIndex + 1);
						}
						$block4x4 = array_fill(0, 4, array_fill(0, 4, 0));
						for ($coefficientIndex = 0; $coefficientIndex < 16; $coefficientIndex++) {
							$block4x4[$zigzag[$coefficientIndex] >> 2][$zigzag[$coefficientIndex] & 3] = $coefficients[$coefficientIndex];
						}
						$residual = self::h264InvTransform4x4($block4x4);
						$pixelX = $mbX * 16 + $blkScan[$blk][0];
						$pixelY = $mbY * 16 + $blkScan[$blk][1];
						for ($residualY = 0; $residualY < 4; $residualY++) {
							for ($residualX = 0; $residualX < 4; $residualX++) {
								if ($pixelY + $residualY < $lumaH && $pixelX + $residualX < $lumaW) {
									$yP[$pixelY + $residualY][$pixelX + $residualX] = max(0, min(255, $yP[$pixelY + $residualY][$pixelX + $residualX] + $residual[$residualY][$residualX]));
								}
							}
						}
					}
				} else {
					$usesTransform8x8 = false;
					if (($pps['transform_8x8_mode_flag'] ?? 0) !== 0) {
						$neighborTransformSize = 0;
						if ($mbX > 0 && ($macroblockUsesTransform8x8[$mbY][$mbX - 1] ?? false)) {
							$neighborTransformSize++;
						}
						if ($mbY > 0 && ($macroblockUsesTransform8x8[$mbY - 1][$mbX] ?? false)) {
							$neighborTransformSize++;
						}
						$usesTransform8x8 = self::cabacDecodeBin($cab, 399 + $neighborTransformSize) !== 0;
					}
					$macroblockUsesTransform8x8[$mbY][$mbX] = $usesTransform8x8;

					$predModes = [];
					if ($usesTransform8x8) {
						for ($groupIndex = 0; $groupIndex < 4; $groupIndex++) {
							$baseBlockX = $mbX * 4 + ($groupIndex % 2) * 2;
							$baseBlockY = $mbY * 4 + intdiv($groupIndex, 2) * 2;
							$predMode = self::cabacGetMostProbableIntra4x4PredictionMode($lumaPredictionModes, $baseBlockX, $baseBlockY);
							$decodedMode = self::cabacDecodeIntra4x4PredMode($cab, $predMode);
							for ($innerY = 0; $innerY < 2; $innerY++) {
								for ($innerX = 0; $innerX < 2; $innerX++) {
									$blockIndex = $groupIndex * 4 + $innerY * 2 + $innerX;
									$predModes[$blockIndex] = $decodedMode;
									$lumaPredictionModes[$baseBlockY + $innerY][$baseBlockX + $innerX] = $decodedMode;
								}
							}
						}
					} else {
						for ($blk = 0; $blk < 16; $blk++) {
							$lumaBlockCoordinates = $lumaBlockGridCoordinates[$blk];
							$blockX = $mbX * 4 + $lumaBlockCoordinates[0];
							$blockY = $mbY * 4 + $lumaBlockCoordinates[1];
							$predMode = self::cabacGetMostProbableIntra4x4PredictionMode($lumaPredictionModes, $blockX, $blockY);
							$predModes[$blk] = self::cabacDecodeIntra4x4PredMode($cab, $predMode);
							$lumaPredictionModes[$blockY][$blockX] = $predModes[$blk];
						}
					}

					$chromaPredMode = self::cabacDecodeIntraChromaPredMode($cab, $leftChromaPredMode, $topChromaPredMode);
					$macroblockChromaPredictionModes[$mbY][$mbX] = $chromaPredMode;
					$leftCodedBlockPattern = $mbX > 0 ? ($macroblockCodedBlockPatterns[$mbY][$mbX - 1] ?? 0) : self::H264_CABAC_UNAVAILABLE_INTRA_LUMA_CODED_BLOCK_PATTERN;
					$topCodedBlockPattern = $mbY > 0 ? ($macroblockCodedBlockPatterns[$mbY - 1][$mbX] ?? 0) : self::H264_CABAC_UNAVAILABLE_INTRA_LUMA_CODED_BLOCK_PATTERN;
					$cbpLuma = self::cabacDecodeCodedBlockPatternLuma($cab, $leftCodedBlockPattern, $topCodedBlockPattern);
					$cbpChroma = self::cabacDecodeCodedBlockPatternChroma($cab, $leftCodedBlockPattern, $topCodedBlockPattern);
					$macroblockCodedBlockPatterns[$mbY][$mbX] = $cbpLuma | ($cbpChroma << 4);

					self::h264PredChroma($uP, $mbX, $mbY, $chromaPredMode, $chrW, $chrH);
					self::h264PredChroma($vP, $mbX, $mbY, $chromaPredMode, $chrW, $chrH);

					if ($cbpLuma > 0 || $cbpChroma > 0) {
						$qpDelta = self::cabacDecodeMbQpDelta($cab, $lastQuantizerScaleDifference !== 0);
						$lastQuantizerScaleDifference = $qpDelta;
						$qpY = self::h264WrapLumaQuantizationParameter($qpY + $qpDelta);
						$qpC = self::h264ResolveChromaQuantizationParameter($qpY, $pps['chroma_qp_offset']);
					}

					if ($usesTransform8x8) {
						for ($groupIndex = 0; $groupIndex < 4; $groupIndex++) {
							$groupBasePixelX = $mbX * 16 + (($groupIndex % 2) * 8);
							$groupBasePixelY = $mbY * 16 + (intdiv($groupIndex, 2) * 8);
							$groupBlockIndexes = [$groupIndex * 4, $groupIndex * 4 + 1, $groupIndex * 4 + 2, $groupIndex * 4 + 3];
							self::h264PredI8x8($yP, $groupBasePixelX, $groupBasePixelY, $predModes[$groupBlockIndexes[0]], $lumaW, $lumaH);

							$lumaCoefficients8x8 = array_fill(0, 64, 0);
							if ((($cbpLuma >> $groupIndex) & 1) !== 0) {
								$lumaContextIndex = self::cabacGetCodedBlockFlagContextIndex($decoderState, [
									'category' => self::CABAC_BLOCK_CATEGORY_LUMA_8X8,
									'macroblock_x' => $mbX,
									'macroblock_y' => $mbY,
									'block_index' => $groupBlockIndexes[0],
								]);
								$lumaCoefficients8x8 = self::cabacDecodeBlock($cab, self::cabacGetResidualBlockDescriptor(self::CABAC_BLOCK_CATEGORY_LUMA_8X8, $lumaContextIndex, 64));
							}

							$nonZeroCount = self::countNonZeroValues($lumaCoefficients8x8);
							foreach ($groupBlockIndexes as $groupBlockIndex) {
								$lumaBlockCoordinates = $lumaBlockGridCoordinates[$groupBlockIndex];
								$lumaNonZeroCounts[$mbY * 4 + $lumaBlockCoordinates[1]][$mbX * 4 + $lumaBlockCoordinates[0]] = $nonZeroCount;
							}

							$block8x8 = array_fill(0, 8, array_fill(0, 8, 0));
							for ($coefficientIndex = 0; $coefficientIndex < 64; $coefficientIndex++) {
								$rasterIndex = $zigzag8x8[$coefficientIndex];
								$block8x8[intdiv($rasterIndex, 8)][$rasterIndex % 8] = self::h264Dequant8x8($lumaCoefficients8x8[$coefficientIndex], $qpY, $rasterIndex);
							}
							$residual8x8 = self::h264InvTransform8x8($block8x8);
							for ($residualY = 0; $residualY < 8; $residualY++) {
								for ($residualX = 0; $residualX < 8; $residualX++) {
									if ($groupBasePixelY + $residualY < $lumaH && $groupBasePixelX + $residualX < $lumaW) {
										$yP[$groupBasePixelY + $residualY][$groupBasePixelX + $residualX] = max(0, min(255, $yP[$groupBasePixelY + $residualY][$groupBasePixelX + $residualX] + $residual8x8[$residualY][$residualX]));
									}
								}
							}
						}
					} else {
						for ($blk = 0; $blk < 16; $blk++) {
							$pixelX = $mbX * 16 + $blkScan[$blk][0];
							$pixelY = $mbY * 16 + $blkScan[$blk][1];
							self::h264PredI4x4($yP, $pixelX, $pixelY, $predModes[$blk], $lumaW, $lumaH);

							$blockGroup = intdiv($blk, 4);
							$lumaCoefficients = array_fill(0, 16, 0);
							if ((($cbpLuma >> $blockGroup) & 1) !== 0) {
								$lumaContextIndex = self::cabacGetCodedBlockFlagContextIndex($decoderState, [
									'category' => self::CABAC_BLOCK_CATEGORY_LUMA_4X4,
									'macroblock_x' => $mbX,
									'macroblock_y' => $mbY,
									'block_index' => $blk,
								]);
								$lumaCoefficients = self::cabacDecodeBlock($cab, self::cabacGetResidualBlockDescriptor(self::CABAC_BLOCK_CATEGORY_LUMA_4X4, $lumaContextIndex, 16));
							}
							$lumaBlockCoordinates = $lumaBlockGridCoordinates[$blk];
							$lumaNonZeroCounts[$mbY * 4 + $lumaBlockCoordinates[1]][$mbX * 4 + $lumaBlockCoordinates[0]] = self::countNonZeroValues($lumaCoefficients);

							$block4x4 = array_fill(0, 4, array_fill(0, 4, 0));
							for ($coefficientIndex = 0; $coefficientIndex < 16; $coefficientIndex++) {
								$block4x4[$zigzag[$coefficientIndex] >> 2][$zigzag[$coefficientIndex] & 3] = self::h264Dequant($lumaCoefficients[$coefficientIndex], $qpY, $coefficientIndex);
							}
							$residual = self::h264InvTransform4x4($block4x4);
							for ($residualY = 0; $residualY < 4; $residualY++) {
								for ($residualX = 0; $residualX < 4; $residualX++) {
									if ($pixelY + $residualY < $lumaH && $pixelX + $residualX < $lumaW) {
										$yP[$pixelY + $residualY][$pixelX + $residualX] = max(0, min(255, $yP[$pixelY + $residualY][$pixelX + $residualX] + $residual[$residualY][$residualX]));
									}
								}
							}
						}
					}
				}

				if ($cbpChroma > 0) {
					foreach ([0 => &$uP, 1 => &$vP] as $planeIndex => &$chromaPlane) {
						$chromaDcContextIndex = self::cabacGetCodedBlockFlagContextIndex($decoderState, [
							'category' => self::CABAC_BLOCK_CATEGORY_CHROMA_DC,
							'macroblock_x' => $mbX,
							'macroblock_y' => $mbY,
							'block_index' => 0,
							'plane_index' => $planeIndex,
						]);
						$chromaDcCoefficients = self::cabacDecodeBlock($cab, self::cabacGetResidualBlockDescriptor(self::CABAC_BLOCK_CATEGORY_CHROMA_DC, $chromaDcContextIndex, 4));
						$macroblockChromaDcPresence[$mbY][$mbX][$planeIndex] = self::countNonZeroValues($chromaDcCoefficients) > 0;
						$dcChr = self::h264InvHadamard2x2($chromaDcCoefficients, $qpC);

						for ($blk = 0; $blk < 4; $blk++) {
							$chromaAcCoefficients = array_fill(0, 15, 0);
							if ($cbpChroma > 1) {
								$chromaAcContextIndex = self::cabacGetCodedBlockFlagContextIndex($decoderState, [
									'category' => self::CABAC_BLOCK_CATEGORY_CHROMA_AC,
									'macroblock_x' => $mbX,
									'macroblock_y' => $mbY,
									'block_index' => $blk,
									'plane_index' => $planeIndex,
								]);
								$chromaAcCoefficients = self::cabacDecodeBlock($cab, self::cabacGetResidualBlockDescriptor(self::CABAC_BLOCK_CATEGORY_CHROMA_AC, $chromaAcContextIndex, 15));
							}
							$chromaNonZeroCounts[$planeIndex][$mbY * 2 + intdiv($blk, 2)][$mbX * 2 + ($blk % 2)] = self::countNonZeroValues($chromaAcCoefficients);

							$coefficients = array_fill(0, 16, 0);
							$coefficients[0] = $dcChr[$blk];
							for ($coefficientIndex = 0; $coefficientIndex < 15; $coefficientIndex++) {
								$coefficients[$coefficientIndex + 1] = self::h264Dequant($chromaAcCoefficients[$coefficientIndex], $qpC, $coefficientIndex + 1);
							}
							$block4x4 = array_fill(0, 4, array_fill(0, 4, 0));
							for ($coefficientIndex = 0; $coefficientIndex < 16; $coefficientIndex++) {
								$block4x4[$zigzag[$coefficientIndex] >> 2][$zigzag[$coefficientIndex] & 3] = $coefficients[$coefficientIndex];
							}
							$residual = self::h264InvTransform4x4($block4x4);
							$chromaPixelX = $mbX * 8 + $chrBlk[$blk][0];
							$chromaPixelY = $mbY * 8 + $chrBlk[$blk][1];
							for ($residualY = 0; $residualY < 4; $residualY++) {
								for ($residualX = 0; $residualX < 4; $residualX++) {
									if ($chromaPixelY + $residualY < $chrH && $chromaPixelX + $residualX < $chrW) {
										$chromaPlane[$chromaPixelY + $residualY][$chromaPixelX + $residualX] = max(0, min(255, $chromaPlane[$chromaPixelY + $residualY][$chromaPixelX + $residualX] + $residual[$residualY][$residualX]));
									}
								}
							}
						}
					}
					unset($chromaPlane);
				}

				if (self::cabacDecodeTerminate($cab) !== 0) {
					break;
				}
			} catch (\Throwable $throwable) {
				throw new Exception('Failed to decode the CABAC macroblock at position (' . $mbX . ', ' . $mbY . ').', 0, $throwable);
			}
		}
	}

    private static function h264DecodeMbsCavlc(array &$br, int $mbW, int $mbH, int $firstMacroblockInSlice, int $qpY, array $pps, array &$yP, array &$uP, array &$vP): void
    {
        $qpC = max(0, min(51, $qpY + $pps['chroma_qp_offset']));
        $lumaW = $mbW * 16;
        $lumaH = $mbH * 16;
        $chrW = $mbW * 8;
        $chrH = $mbH * 8;
        $totalMbs = $mbW * $mbH;
        $blkScan = [[0, 0], [4, 0], [0, 4], [4, 4], [8, 0], [12, 0], [8, 4], [12, 4], [0, 8], [4, 8], [0, 12], [4, 12], [8, 8], [12, 8], [8, 12], [12, 12]];
        $zigzag = [0, 1, 4, 8, 5, 2, 3, 6, 9, 12, 13, 10, 7, 11, 14, 15];
        $chrBlk = [[0, 0], [4, 0], [0, 4], [4, 4]];
        $cbpMapIntra = [47, 31, 15, 0, 23, 27, 29, 30, 7, 11, 13, 14, 39, 43, 45, 46, 16, 3, 5, 10, 12, 19, 21, 26, 28, 35, 37, 42, 44, 1, 2, 4, 8, 17, 18, 20, 24, 6, 9, 22, 25, 32, 33, 34, 36, 40, 38, 41, 48];

        for ($mbIdx = $firstMacroblockInSlice; $mbIdx < $totalMbs; $mbIdx++) {
            try {
                $mbX = $mbIdx % $mbW;
                $mbY = intdiv($mbIdx, $mbW);
                $mbType = self::h264UE($br);

                if ($mbType === 25) {
                    $br['p'] = (($br['p'] + 7) >> 3) << 3;
                    for ($y = 0; $y < 16; $y++) {
                        for ($x = 0; $x < 16; $x++) {
                            $yP[$mbY * 16 + $y][$mbX * 16 + $x] = self::h264Bits($br, 8);
                        }
                    }
                    for ($y = 0; $y < 8; $y++) {
                        for ($x = 0; $x < 8; $x++) {
                            $uP[$mbY * 8 + $y][$mbX * 8 + $x] = self::h264Bits($br, 8);
                        }
                    }
                    for ($y = 0; $y < 8; $y++) {
                        for ($x = 0; $x < 8; $x++) {
                            $vP[$mbY * 8 + $y][$mbX * 8 + $x] = self::h264Bits($br, 8);
                        }
                    }
                    continue;
                }

                $isI16 = ($mbType >= 1 && $mbType <= 24);
                $i16mode = 0;
                $cbpLuma = 0;
                $cbpChroma = 0;

                if ($isI16) {
                    $i16mode = ($mbType - 1) % 4;
                    $cbpChroma = intdiv($mbType - 1, 4) % 3;
                    $cbpLuma = ($mbType > 12) ? 15 : 0;
                    $chromaPredMode = self::h264UE($br);

                    self::h264PredI16x16($yP, $mbX, $mbY, $i16mode, $lumaW, $lumaH);
                    self::h264PredChroma($uP, $mbX, $mbY, $chromaPredMode, $chrW, $chrH);
                    self::h264PredChroma($vP, $mbX, $mbY, $chromaPredMode, $chrW, $chrH);

                    $qpDelta = self::h264SE($br);
                    $qpY = max(0, min(51, $qpY + $qpDelta));
                    $qpC = max(0, min(51, $qpY + $pps['chroma_qp_offset']));

                    $dcBlock = self::cavlcDecodeBlock($br, 0, 16);
                    $dcT = self::h264InvHadamard4x4($dcBlock, $qpY);

                    $dcRemap = [0, 1, 4, 5, 2, 3, 6, 7, 8, 9, 12, 13, 10, 11, 14, 15];
                    for ($blk = 0; $blk < 16; $blk++) {
                        $acCoeffs = ($cbpLuma > 0) ? self::cavlcDecodeBlock($br, 0, 15) : array_fill(0, 15, 0);
                        $c = array_fill(0, 16, 0);
                        $c[0] = $dcT[$dcRemap[$blk]];
                        for ($k = 0; $k < 15; $k++) {
                            $c[$k + 1] = self::h264Dequant($acCoeffs[$k], $qpY, $k + 1);
                        }
                        $b4 = array_fill(0, 4, array_fill(0, 4, 0));
                        for ($k = 0; $k < 16; $k++) {
                            $b4[$zigzag[$k] >> 2][$zigzag[$k] & 3] = $c[$k];
                        }
                        $res = self::h264InvTransform4x4($b4);
                        $px = $mbX * 16 + $blkScan[$blk][0];
                        $py = $mbY * 16 + $blkScan[$blk][1];
                        for ($ry = 0; $ry < 4; $ry++) {
                            for ($rx = 0; $rx < 4; $rx++) {
                                if ($py + $ry < $lumaH && $px + $rx < $lumaW) {
                                    $yP[$py + $ry][$px + $rx] = max(0, min(255, $yP[$py + $ry][$px + $rx] + $res[$ry][$rx]));
                                }
                            }
                        }
                    }

                    if ($cbpChroma > 0) {
                        foreach ([&$uP, &$vP] as &$cP) {
                            $chrDc = self::cavlcDecodeBlock($br, -1, 4);
                            $dcChr = self::h264InvHadamard2x2($chrDc, $qpC);
                            for ($blk = 0; $blk < 4; $blk++) {
                                $ac = ($cbpChroma > 1) ? self::cavlcDecodeBlock($br, 0, 15) : array_fill(0, 15, 0);
                                $c = array_fill(0, 16, 0);
                                $c[0] = $dcChr[$blk];
                                for ($k = 0; $k < 15; $k++) {
                                    $c[$k + 1] = self::h264Dequant($ac[$k], $qpC, $k + 1);
                                }
                                $b4 = array_fill(0, 4, array_fill(0, 4, 0));
                                for ($k = 0; $k < 16; $k++) {
                                    $b4[$zigzag[$k] >> 2][$zigzag[$k] & 3] = $c[$k];
                                }
                                $res = self::h264InvTransform4x4($b4);
                                $cpx = $mbX * 8 + $chrBlk[$blk][0];
                                $cpy = $mbY * 8 + $chrBlk[$blk][1];
                                for ($ry = 0; $ry < 4; $ry++) {
                                    for ($rx = 0; $rx < 4; $rx++) {
                                        if ($cpy + $ry < $chrH && $cpx + $rx < $chrW) {
                                            $cP[$cpy + $ry][$cpx + $rx] = max(0, min(255, $cP[$cpy + $ry][$cpx + $rx] + $res[$ry][$rx]));
                                        }
                                    }
                                }
                            }
                        }
                        unset($cP);
                    }
                } else {
                    // I_NxN
                    $predModes = [];
                    for ($blk = 0; $blk < 16; $blk++) {
                        if (self::h264Bits($br, 1)) {
                            $predModes[$blk] = 2;
                        } else {
                            $predModes[$blk] = self::h264Bits($br, 3);
                        }
                    }
                    $chromaPredMode = self::h264UE($br);
                    $cbpIdx = self::h264UE($br);
                    $cbp = $cbpMapIntra[$cbpIdx] ?? 0;
                    $cbpLuma = $cbp & 15;
                    $cbpChroma = ($cbp >> 4) & 3;

                    self::h264PredChroma($uP, $mbX, $mbY, $chromaPredMode, $chrW, $chrH);
                    self::h264PredChroma($vP, $mbX, $mbY, $chromaPredMode, $chrW, $chrH);

                    if ($cbpLuma > 0 || $cbpChroma > 0) {
                        $qpDelta = self::h264SE($br);
                        $qpY = max(0, min(51, $qpY + $qpDelta));
                        $qpC = max(0, min(51, $qpY + $pps['chroma_qp_offset']));
                    }

                    for ($blk = 0; $blk < 16; $blk++) {
                        $px = $mbX * 16 + $blkScan[$blk][0];
                        $py = $mbY * 16 + $blkScan[$blk][1];
                        self::h264PredI4x4($yP, $px, $py, $predModes[$blk], $lumaW, $lumaH);
                        $blkGroup = intdiv($blk, 4);
                        if (($cbpLuma >> $blkGroup) & 1) {
                            $cArr = self::cavlcDecodeBlock($br, 0, 16);
                            $b4 = array_fill(0, 4, array_fill(0, 4, 0));
                            for ($k = 0; $k < 16; $k++) {
                                $b4[$zigzag[$k] >> 2][$zigzag[$k] & 3] = self::h264Dequant($cArr[$k], $qpY, $k);
                            }
                            $res = self::h264InvTransform4x4($b4);
                            for ($ry = 0; $ry < 4; $ry++) {
                                for ($rx = 0; $rx < 4; $rx++) {
                                    if ($py + $ry < $lumaH && $px + $rx < $lumaW) {
                                        $yP[$py + $ry][$px + $rx] = max(0, min(255, $yP[$py + $ry][$px + $rx] + $res[$ry][$rx]));
                                    }
                                }
                            }
                        }
                    }

                    if ($cbpChroma > 0) {
                        foreach ([&$uP, &$vP] as &$cP) {
                            $chrDc = self::cavlcDecodeBlock($br, -1, 4);
                            $dcChr = self::h264InvHadamard2x2($chrDc, $qpC);
                            for ($blk = 0; $blk < 4; $blk++) {
                                $ac = ($cbpChroma > 1) ? self::cavlcDecodeBlock($br, 0, 15) : array_fill(0, 15, 0);
                                $c = array_fill(0, 16, 0);
                                $c[0] = $dcChr[$blk];
                                for ($k = 0; $k < 15; $k++) {
                                    $c[$k + 1] = self::h264Dequant($ac[$k], $qpC, $k + 1);
                                }
                                $b4 = array_fill(0, 4, array_fill(0, 4, 0));
                                for ($k = 0; $k < 16; $k++) {
                                    $b4[$zigzag[$k] >> 2][$zigzag[$k] & 3] = $c[$k];
                                }
                                $res = self::h264InvTransform4x4($b4);
                                $cpx = $mbX * 8 + $chrBlk[$blk][0];
                                $cpy = $mbY * 8 + $chrBlk[$blk][1];
                                for ($ry = 0; $ry < 4; $ry++) {
                                    for ($rx = 0; $rx < 4; $rx++) {
                                        if ($cpy + $ry < $chrH && $cpx + $rx < $chrW) {
                                            $cP[$cpy + $ry][$cpx + $rx] = max(0, min(255, $cP[$cpy + $ry][$cpx + $rx] + $res[$ry][$rx]));
                                        }
                                    }
                                }
                            }
                        }
                        unset($cP);
                    }
                }
            } catch (\Throwable $e) {
                continue;
            }
        }
    }

    private static function cavlcDecodeBlock(array &$br, int $nC, int $maxCoeff): array
    {
        $coeffs = array_fill(0, $maxCoeff, 0);
        [$tc, $t1] = self::cavlcReadCoeffToken($br, $nC);
        if ($tc === 0) {
            return $coeffs;
        }

        $levels = [];
        for ($i = 0; $i < $t1; $i++) {
            $levels[] = self::h264Bits($br, 1) ? -1 : 1;
        }
        $levels = array_reverse($levels);

        $suffLen = ($tc > 10 && $t1 < 3) ? 1 : 0;
        for ($i = $t1; $i < $tc; $i++) {
            $prefix = 0;
            while (self::h264Bits($br, 1) === 0) {
                $prefix++;
                if ($prefix > 20) {
                    break;
                }
            }
            $levelCode = $prefix << $suffLen;
            if ($suffLen > 0 || $prefix >= 14) {
                $sb = ($prefix >= 15) ? max(1, $prefix - 3) : max(1, $suffLen);
                $levelCode += self::h264Bits($br, $sb);
            }
            if ($i === $t1 && $t1 < 3) {
                $levelCode += 2;
            }
            $lv = ($levelCode & 1) ? -(($levelCode + 1) >> 1) : (($levelCode + 2) >> 1);
            array_unshift($levels, $lv);
            if ($suffLen === 0) {
                $suffLen = 1;
            }
            if (abs($lv) > (3 << ($suffLen - 1)) && $suffLen < 6) {
                $suffLen++;
            }
        }

        $totalZeros = 0;
        if ($tc < $maxCoeff) {
            $totalZeros = self::cavlcReadTotalZeros($br, $tc, $maxCoeff);
        }

        $zerosLeft = $totalZeros;
        $ci = $tc + $totalZeros - 1;
        for ($i = 0; $i < $tc - 1 && $ci >= 0; $i++) {
            $run = ($zerosLeft > 0) ? self::cavlcReadRunBefore($br, $zerosLeft) : 0;
            if ($ci < $maxCoeff) {
                $coeffs[$ci] = $levels[$i];
            }
            $zerosLeft -= $run;
            $ci -= 1 + $run;
        }
        if ($ci >= 0 && $ci < $maxCoeff && $tc > 0) {
            $coeffs[$ci] = $levels[$tc - 1];
        }
        return $coeffs;
    }

    /**
     * CAVLC coeff_token VLC decoding (H.264 Table 9-5)
     *
     * Finds a matching entry in the predefined VLC table (getCoeffTokenTable)
     * based on nC value and returns (TotalCoeff, TrailingOnes).
     * Tries codes in ascending length order to ensure prefix-free matching.
     *
     * @param array &$br Bitstream reader state ['d'=>data, 'p'=>position, 'l'=>length]
     * @param int $nC Neighbor-block-based predicted coefficient count (nC), -1 for chroma DC
     * @return array [TotalCoeff, TrailingOnes]
     */
    private static function cavlcReadCoeffToken(array &$br, int $nC): array
    {
        // nC >= 8: 6-bit fixed-length code (FLC)
        if ($nC >= 8) {
            $code = self::h264Bits($br, 6);
            if ($code === 3) {
                return [0, 0];
            }
            $tc = ($code >> 2) + 1;
            $t1 = $code & 3;
            if ($tc > 16) {
                $tc = 16;
            }
            if ($t1 > min($tc, 3)) {
                $t1 = min($tc, 3);
            }
            return [$tc, $t1];
        }

        // Match bit patterns from VLC table (shortest code first)
        $table = self::getCoeffTokenTable($nC);
        $savedPos = $br['p'];

        // Sort by code length ascending (prefix-free VLC, try shortest first)
        usort($table, function ($a, $b) {
            return $a[0] - $b[0];
        });

        foreach ($table as [$len, $code, $tc, $t1]) {
            $br['p'] = $savedPos;
            if ($br['p'] + $len > $br['l']) {
                continue;
            }
            $bits = self::h264Bits($br, $len);
            if ($bits === $code) {
                return [$tc, $t1];
            }
        }

        // Fallback: no table match found, consume 1 bit and return zero coefficients
        $br['p'] = $savedPos;
        self::h264Bits($br, 1);
        return [0, 0];
    }

    /**
     * CAVLC total_zeros VLC decoding (H.264 Table 9-7 and Table 9-9)
     *
     * Given totalCoeff non-zero coefficients, decode the total number of zeros
     * between/before them from the bitstream. Uses different tables based on maxC:
     *   maxC <= 4: chroma DC Table 9-9(a) -- truncated unary code
     *   maxC > 4:  4x4 block Table 9-7 -- per-tc dedicated VLC table
     *
     * @param array &$br Bitstream reader state
     * @param int $tc totalCoeff (number of non-zero coefficients)
     * @param int $maxC Maximum coefficient count (4=chroma DC, 15=AC, 16=full block)
     * @return array|int total_zeros value
     */
    private static function cavlcReadTotalZeros(array &$br, int $tc, int $maxC): array|int
    {
        $maxZeros = $maxC - $tc;
        if ($maxZeros <= 0) {
            return 0;
        }

        // Chroma DC (Table 9-9(a)): truncated unary code
        if ($maxC <= 4) {
            $z = 0;
            while ($z < $maxZeros && self::h264Bits($br, 1) === 0) {
                $z++;
            }
            return min($z, $maxZeros);
        }

        // 4x4 block total_zeros (Table 9-7)
        // tc >= 9: truncated unary code (matches H.264 standard)
        if ($tc >= 9) {
            $z = 0;
            while ($z < $maxZeros && self::h264Bits($br, 1) === 0) {
                $z++;
            }
            return min($z, $maxZeros);
        }

        // tc 1-8: use dedicated VLC table (table matching)
        $table = self::getTotalZerosTable4x4($tc);
        if ($table === null) {
            // Fallback: truncated unary
            $z = 0;
            while ($z < $maxZeros && self::h264Bits($br, 1) === 0) {
                $z++;
            }
            return min($z, $maxZeros);
        }

        $savedPos = $br['p'];

        // Sort by code length ascending
        usort($table, function ($a, $b) {
            return $a[0] - $b[0];
        });

        foreach ($table as [$len, $code, $tz]) {
            $br['p'] = $savedPos;
            if ($br['p'] + $len > $br['l']) {
                continue;
            }
            $bits = self::h264Bits($br, $len);
            if ($bits === $code) {
                return $tz;
            }
        }

        // No match found, fallback to truncated unary
        $br['p'] = $savedPos;
        $z = 0;
        while ($z < $maxZeros && self::h264Bits($br, 1) === 0) {
            $z++;
        }
        return min($z, $maxZeros);
    }

    /**
     * H.264 Table 9-7: total_zeros VLC table for 4x4 blocks
     *
     * Returns VLC entry array for tzVlcIndex = totalCoeff - 1.
     * Each entry: [code_length, code_value, total_zeros_value]
     *
     * @param int $tc totalCoeff value (1-8)
     * @return array|null VLC entry array, or null for unsupported tc
     */
    private static function getTotalZerosTable4x4(int $tc): ?array
    {
        // H.264 Rec. ITU-T Table 9-7 — VLC for total_zeros (4x4 blocks)
        // Format: [bit_length, code_value, total_zeros_value]
        return match ($tc) {
            1 => [
                [1, 1, 0],
                [3, 3, 1],
                [3, 2, 2],
                [4, 3, 3],
                [4, 2, 4],
                [5, 3, 5],
                [5, 2, 6],
                [6, 3, 7],
                [6, 2, 8],
                [7, 3, 9],
                [7, 2, 10],
                [8, 3, 11],
                [8, 2, 12],
                [9, 3, 13],
                [9, 2, 14],
                [9, 1, 15],
            ],
            2 => [
                [3, 7, 0],
                [3, 6, 1],
                [3, 5, 2],
                [3, 4, 3],
                [3, 3, 4],
                [4, 5, 5],
                [4, 4, 6],
                [4, 3, 7],
                [4, 2, 8],
                [5, 3, 9],
                [5, 2, 10],
                [6, 3, 11],
                [6, 2, 12],
                [6, 1, 13],
                [6, 0, 14],
            ],
            3 => [
                [4, 5, 0],
                [3, 7, 1],
                [3, 6, 2],
                [3, 5, 3],
                [4, 4, 4],
                [4, 3, 5],
                [3, 4, 6],
                [4, 2, 7],
                [5, 3, 8],
                [5, 2, 9],
                [6, 1, 10],
                [5, 1, 11],
                [6, 0, 12],
                [6, 2, 13],
            ],
            4 => [
                [5, 3, 0],
                [3, 7, 1],
                [3, 6, 2],
                [4, 5, 3],
                [3, 5, 4],
                [3, 4, 5],
                [4, 4, 6],
                [3, 3, 7],
                [4, 3, 8],
                [5, 2, 9],
                [5, 1, 10],
                [5, 0, 11],
                [4, 2, 12],
            ],
            5 => [
                [4, 7, 0],
                [4, 6, 1],
                [3, 7, 2],
                [3, 6, 3],
                [3, 5, 4],
                [3, 4, 5],
                [4, 5, 6],
                [3, 3, 7],
                [4, 4, 8],
                [4, 3, 9],
                [4, 2, 10],
                [4, 1, 11],
            ],
            6 => [
                [6, 1, 0],
                [5, 1, 1],
                [3, 7, 2],
                [3, 6, 3],
                [3, 5, 4],
                [3, 4, 5],
                [3, 3, 6],
                [3, 2, 7],
                [4, 3, 8],
                [3, 1, 9],
                [6, 0, 10],
            ],
            7 => [
                [6, 1, 0],
                [5, 1, 1],
                [3, 5, 2],
                [3, 4, 3],
                [3, 3, 4],
                [2, 3, 5],
                [3, 2, 6],
                [4, 1, 7],
                [6, 0, 8],
                [5, 0, 9],
            ],
            8 => [
                [6, 1, 0],
                [4, 1, 1],
                [5, 1, 2],
                [3, 3, 3],
                [2, 3, 4],
                [2, 2, 5],
                [3, 2, 6],
                [6, 0, 7],
                [5, 0, 8],
            ],
            default => null,
        };
    }

    /**
     * CAVLC run_before VLC decoding (H.264 Table 9-10)
     *
     * Decode the number of consecutive zeros before the current coefficient
     * based on the remaining zeros count (zerosLeft).
     * Uses different VLC tables per zerosLeft value:
     *   1: 1-bit, 2: 1-2 bits, 3: 2-bit fixed,
     *   4-5: 2-3 bits, 6: 3-bit fixed, >=7: 3-bit prefix + extension
     *
     * @param array &$br Bitstream reader state
     * @param int $zl Remaining zeros count (zerosLeft)
     * @return int run_before value
     */
    private static function cavlcReadRunBefore(array &$br, int $zl): int
    {
        if ($zl <= 0) {
            return 0;
        }
        if ($zl === 1) {
            return self::h264Bits($br, 1);
        }
        if ($zl === 2) {
            $b = self::h264Bits($br, 1);
            return $b ? 0 : (self::h264Bits($br, 1) ? 1 : 2);
        }
        if ($zl === 3) {
            // 2-bit code: 11->0, 10->1, 01->2, 00->3
            $v = self::h264Bits($br, 2);
            return 3 - $v;
        }
        if ($zl === 4) {
            // 11->0, 10->1, 01->2 (2-bit), 001->3, 000->4 (3-bit)
            $v = self::h264Bits($br, 2);
            if ($v > 0) {
                return 3 - $v;
            }
            return self::h264Bits($br, 1) ? 3 : 4;
        }
        if ($zl === 5) {
            // 11->0, 10->1 (2-bit), 011->2, 010->3, 001->4, 000->5 (3-bit)
            $v = self::h264Bits($br, 2);
            if ($v >= 2) {
                return 3 - $v;
            }
            $m = self::h264Bits($br, 1);
            if ($v === 1) {
                return $m ? 2 : 3;
            }
            return $m ? 4 : 5;
        }
        if ($zl === 6) {
            // 3-bit code: 111->0, 110->1, 101->2, 100->3, 011->4, 010->5, 001->6
            $v = self::h264Bits($br, 3);
            if ($v === 0) {
                return 6; // bitstream error guard
            }
            return 7 - $v;
        }
        // zerosLeft >= 7: 3-bit prefix + extension (Table 9-10 last column)
        // 111->0, 110->1, 101->2, 100->3, 011->4, 010->5, 001->6
        // 000 + unary extension: 0001->7, 00001->8, 000001->9, ...
        $v = self::h264Bits($br, 3);
        if ($v > 0) {
            return 7 - $v;
        }
        $run = 7;
        while ($run < $zl && self::h264Bits($br, 1) === 0) {
            $run++;
        }
        return min($run, $zl);
    }

    // H.264 transforms
    private static function h264InvTransform4x4(array $d): array
    {
        $r = array_fill(0, 4, array_fill(0, 4, 0));
        $e = array_fill(0, 4, array_fill(0, 4, 0));
        for ($i = 0; $i < 4; $i++) {
            $e[$i][0] = $d[$i][0] + $d[$i][2];
            $e[$i][1] = $d[$i][0] - $d[$i][2];
            $e[$i][2] = ($d[$i][1] >> 1) - $d[$i][3];
            $e[$i][3] = $d[$i][1] + ($d[$i][3] >> 1);
        }

        $f = array_fill(0, 4, array_fill(0, 4, 0));
        for ($i = 0; $i < 4; $i++) {
            $f[$i][0] = $e[$i][0] + $e[$i][3];
            $f[$i][1] = $e[$i][1] + $e[$i][2];
            $f[$i][2] = $e[$i][1] - $e[$i][2];
            $f[$i][3] = $e[$i][0] - $e[$i][3];
        }

        for ($j = 0; $j < 4; $j++) {
            $g0 = $f[0][$j] + $f[2][$j];
            $g1 = $f[0][$j] - $f[2][$j];
            $g2 = ($f[1][$j] >> 1) - $f[3][$j];
            $g3 = $f[1][$j] + ($f[3][$j] >> 1);
            $r[0][$j] = ($g0 + $g3 + 32) >> 6;
            $r[1][$j] = ($g1 + $g2 + 32) >> 6;
            $r[2][$j] = ($g1 - $g2 + 32) >> 6;
            $r[3][$j] = ($g0 - $g3 + 32) >> 6;
        }
        return $r;
    }

    private static function h264InvHadamard4x4(array $dc, int $qp): array
    {
        $d = array_fill(0, 4, array_fill(0, 4, 0));
        for ($i = 0; $i < 4; $i++) {
            for ($j = 0; $j < 4; $j++) {
                $d[$i][$j] = $dc[$i * 4 + $j] ?? 0;
            }
        }

        $e = array_fill(0, 4, array_fill(0, 4, 0));
        for ($i = 0; $i < 4; $i++) {
            $e[$i][0] = $d[$i][0] + $d[$i][2];
            $e[$i][1] = $d[$i][0] - $d[$i][2];
            $e[$i][2] = $d[$i][1] - $d[$i][3];
            $e[$i][3] = $d[$i][1] + $d[$i][3];
        }

        $f = array_fill(0, 4, array_fill(0, 4, 0));
        for ($i = 0; $i < 4; $i++) {
            $f[$i][0] = $e[$i][0] + $e[$i][3];
            $f[$i][1] = $e[$i][1] + $e[$i][2];
            $f[$i][2] = $e[$i][1] - $e[$i][2];
            $f[$i][3] = $e[$i][0] - $e[$i][3];
        }

        $g = array_fill(0, 4, array_fill(0, 4, 0));
        for ($j = 0; $j < 4; $j++) {
            $g[0][$j] = $f[0][$j] + $f[2][$j];
            $g[1][$j] = $f[0][$j] - $f[2][$j];
            $g[2][$j] = $f[1][$j] - $f[3][$j];
            $g[3][$j] = $f[1][$j] + $f[3][$j];
        }

        $h = array_fill(0, 4, array_fill(0, 4, 0));
        for ($j = 0; $j < 4; $j++) {
            $h[0][$j] = $g[0][$j] + $g[3][$j];
            $h[1][$j] = $g[1][$j] + $g[2][$j];
            $h[2][$j] = $g[1][$j] - $g[2][$j];
            $h[3][$j] = $g[0][$j] - $g[3][$j];
        }

        $v = [[10, 16, 13], [11, 18, 14], [13, 20, 16], [14, 23, 18], [16, 25, 20], [18, 29, 23]];
        $qpPer = intdiv($qp, 6);
        $qpRem = $qp % 6;
        $s = $v[$qpRem][0];
        $r = [];
        for ($i = 0; $i < 4; $i++) {
            for ($j = 0; $j < 4; $j++) {
                $r[$i * 4 + $j] = ($qpPer >= 2) ? ($h[$i][$j] * $s) << ($qpPer - 2) : ($h[$i][$j] * $s + (1 << (1 - $qpPer))) >> (2 - $qpPer);
            }
        }

        return $r;
    }

    private static function h264InvHadamard2x2(array $dc, int $qp): array
    {
        $d00 = ($dc[0] ?? 0) + ($dc[1] ?? 0) + ($dc[2] ?? 0) + ($dc[3] ?? 0);
        $d01 = ($dc[0] ?? 0) - ($dc[1] ?? 0) + ($dc[2] ?? 0) - ($dc[3] ?? 0);
        $d10 = ($dc[0] ?? 0) + ($dc[1] ?? 0) - ($dc[2] ?? 0) - ($dc[3] ?? 0);
        $d11 = ($dc[0] ?? 0) - ($dc[1] ?? 0) - ($dc[2] ?? 0) + ($dc[3] ?? 0);
        $v = [[10, 16, 13], [11, 18, 14], [13, 20, 16], [14, 23, 18], [16, 25, 20], [18, 29, 23]];
        $qpPer = intdiv($qp, 6);
        $qpRem = $qp % 6;
        $s = $v[$qpRem][0];
        $fn = function ($val) use ($s, $qpPer) {
            return ($qpPer >= 1) ? ($val * $s) << ($qpPer - 1) : ($val * $s + 1) >> 1;
        };
        return [$fn($d00), $fn($d01), $fn($d10), $fn($d11)];
    }

    private static function h264InvTransform8x8(array $coefficients): array
    {
		$coefficients[0][0] = ($coefficients[0][0] ?? 0) + 32;
		$intermediate = array_fill(0, 8, array_fill(0, 8, 0));
		for ($columnIndex = 0; $columnIndex < 8; $columnIndex++) {
			$a0 = $coefficients[0][$columnIndex] + $coefficients[4][$columnIndex];
			$a2 = $coefficients[0][$columnIndex] - $coefficients[4][$columnIndex];
			$a4 = ($coefficients[2][$columnIndex] >> 1) - $coefficients[6][$columnIndex];
			$a6 = ($coefficients[6][$columnIndex] >> 1) + $coefficients[2][$columnIndex];

			$b0 = $a0 + $a6;
			$b2 = $a2 + $a4;
			$b4 = $a2 - $a4;
			$b6 = $a0 - $a6;

			$a1 = -$coefficients[3][$columnIndex] + $coefficients[5][$columnIndex] - $coefficients[7][$columnIndex] - ($coefficients[7][$columnIndex] >> 1);
			$a3 = $coefficients[1][$columnIndex] + $coefficients[7][$columnIndex] - $coefficients[3][$columnIndex] - ($coefficients[3][$columnIndex] >> 1);
			$a5 = -$coefficients[1][$columnIndex] + $coefficients[7][$columnIndex] + $coefficients[5][$columnIndex] + ($coefficients[5][$columnIndex] >> 1);
			$a7 = $coefficients[3][$columnIndex] + $coefficients[5][$columnIndex] + $coefficients[1][$columnIndex] + ($coefficients[1][$columnIndex] >> 1);

			$b1 = $a1 + ($a7 >> 2);
			$b3 = $a3 + ($a5 >> 2);
			$b5 = ($a3 >> 2) - $a5;
			$b7 = $a7 - ($a1 >> 2);

			$intermediate[0][$columnIndex] = $b0 + $b7;
			$intermediate[7][$columnIndex] = $b0 - $b7;
			$intermediate[1][$columnIndex] = $b2 + $b5;
			$intermediate[6][$columnIndex] = $b2 - $b5;
			$intermediate[2][$columnIndex] = $b4 + $b3;
			$intermediate[5][$columnIndex] = $b4 - $b3;
			$intermediate[3][$columnIndex] = $b6 + $b1;
			$intermediate[4][$columnIndex] = $b6 - $b1;
		}

		$residual = array_fill(0, 8, array_fill(0, 8, 0));
		for ($rowIndex = 0; $rowIndex < 8; $rowIndex++) {
			$a0 = $intermediate[$rowIndex][0] + $intermediate[$rowIndex][4];
			$a2 = $intermediate[$rowIndex][0] - $intermediate[$rowIndex][4];
			$a4 = ($intermediate[$rowIndex][2] >> 1) - $intermediate[$rowIndex][6];
			$a6 = ($intermediate[$rowIndex][6] >> 1) + $intermediate[$rowIndex][2];

			$b0 = $a0 + $a6;
			$b2 = $a2 + $a4;
			$b4 = $a2 - $a4;
			$b6 = $a0 - $a6;

			$a1 = -$intermediate[$rowIndex][3] + $intermediate[$rowIndex][5] - $intermediate[$rowIndex][7] - ($intermediate[$rowIndex][7] >> 1);
			$a3 = $intermediate[$rowIndex][1] + $intermediate[$rowIndex][7] - $intermediate[$rowIndex][3] - ($intermediate[$rowIndex][3] >> 1);
			$a5 = -$intermediate[$rowIndex][1] + $intermediate[$rowIndex][7] + $intermediate[$rowIndex][5] + ($intermediate[$rowIndex][5] >> 1);
			$a7 = $intermediate[$rowIndex][3] + $intermediate[$rowIndex][5] + $intermediate[$rowIndex][1] + ($intermediate[$rowIndex][1] >> 1);

			$b1 = $a1 + ($a7 >> 2);
			$b3 = $a3 + ($a5 >> 2);
			$b5 = ($a3 >> 2) - $a5;
			$b7 = $a7 - ($a1 >> 2);

			$residual[$rowIndex][0] = ($b0 + $b7) >> 6;
			$residual[$rowIndex][1] = ($b2 + $b5) >> 6;
			$residual[$rowIndex][2] = ($b4 + $b3) >> 6;
			$residual[$rowIndex][3] = ($b6 + $b1) >> 6;
			$residual[$rowIndex][4] = ($b6 - $b1) >> 6;
			$residual[$rowIndex][5] = ($b4 - $b3) >> 6;
			$residual[$rowIndex][6] = ($b2 - $b5) >> 6;
			$residual[$rowIndex][7] = ($b0 - $b7) >> 6;
		}

		return $residual;
    }

    private static function h264Dequant(int $c, int $qp, int $pos): int
    {
        if ($c === 0) {
            return 0;
        }
        $v = [[10, 16, 13], [11, 18, 14], [13, 20, 16], [14, 23, 18], [16, 25, 20], [18, 29, 23]];
        $qpPer = intdiv($qp, 6);
        $qpRem = $qp % 6;
        // Map zigzag scan position to 2D coordinate (row, col) then determine V matrix index:
        //   V[0] = both row,col even (0,0),(0,2),(2,0),(2,2) -> zigzag pos 0,3,5,11
        //   V[1] = mixed row/col parity positions -> zigzag pos 1,2,6,7,8,9,13,14
        //   V[2] = both row,col odd (1,1),(1,3),(3,1),(3,3) -> zigzag pos 4,10,12,15
        $pm = [0 => 0, 1 => 1, 2 => 1, 3 => 0, 4 => 2, 5 => 0, 6 => 1, 7 => 1, 8 => 1, 9 => 1, 10 => 2, 11 => 0, 12 => 2, 13 => 1, 14 => 1, 15 => 2];
        $idx = $pm[$pos % 16] ?? 0;
        return ($c * $v[$qpRem][$idx]) << $qpPer;
    }

    private static function h264Dequant8x8(int $c, int $qp, int $rasterIndex): int
    {
		if ($c === 0) {
			return 0;
		}

		$dequant8x8Scan = [0, 3, 4, 3, 3, 1, 5, 1, 4, 5, 2, 5, 3, 1, 5, 1];
		$dequant8x8Values = [
			[20, 18, 32, 19, 25, 24],
			[22, 19, 35, 21, 28, 26],
			[26, 23, 42, 24, 33, 31],
			[28, 25, 45, 26, 35, 33],
			[32, 28, 51, 30, 40, 38],
			[36, 32, 58, 34, 46, 43],
		];

		$qpPer = intdiv($qp, 6);
		$qpRem = $qp % 6;
		$scanIndex = ((($rasterIndex >> 1) & 12) | ($rasterIndex & 3));
		$dequantFactor = ($dequant8x8Values[$qpRem][$dequant8x8Scan[$scanIndex]] * 16) << $qpPer;
		$scaledCoefficient = $c * $dequantFactor;
		if ($scaledCoefficient >= 0) {
			return intdiv($scaledCoefficient + 32, 64);
		}
		return -intdiv((-1 * $scaledCoefficient) + 32, 64);
    }

    // Intra prediction
    private static function h264PredI16x16(array &$p, int $mx, int $my, int $mode, int $pw, int $ph): void
    {
        $ox = $mx * 16;
        $oy = $my * 16;
        $pred = array_fill(0, 16, array_fill(0, 16, 128));
        switch ($mode) {
            case 0:
                if ($oy > 0) {
                    for ($y = 0; $y < 16; $y++) {
                        for ($x = 0; $x < 16; $x++) {
                            $pred[$y][$x] = $p[$oy - 1][$ox + $x] ?? 128;
                        }
                    }
                }
                break;
            case 1:
                if ($ox > 0) {
                    for ($y = 0; $y < 16; $y++) {
                        for ($x = 0; $x < 16; $x++) {
                            $pred[$y][$x] = $p[$oy + $y][$ox - 1] ?? 128;
                        }
                    }
                }
                break;
            case 2:
                $s = 0;
                $c = 0;
                if ($oy > 0) {
                    for ($x = 0; $x < 16; $x++) {
                        $s += $p[$oy - 1][$ox + $x] ?? 128;
                        $c++;
                    }
                }
                if ($ox > 0) {
                    for ($y = 0; $y < 16; $y++) {
                        $s += $p[$oy + $y][$ox - 1] ?? 128;
                        $c++;
                    }
                }
                $dc = $c > 0 ? intdiv($s + ($c >> 1), $c) : 128;
                $pred = array_fill(0, 16, array_fill(0, 16, $dc));
                break;
            case 3:
                if ($oy > 0 && $ox > 0) {
                    $hv = [0, 0];
                    for ($i = 0; $i < 8; $i++) {
                        $hv[0] += ($i + 1) * (($p[$oy - 1][min($ox + 8 + $i, $pw - 1)] ?? 128) - ($p[$oy - 1][max($ox + 6 - $i, 0)] ?? 128));
                        $hv[1] += ($i + 1) * (($p[min($oy + 8 + $i, $ph - 1)][$ox - 1] ?? 128) - ($p[max($oy + 6 - $i, 0)][$ox - 1] ?? 128));
                    }

                    $a = 16 * (($p[$oy - 1][min($ox + 15, $pw - 1)] ?? 128) + ($p[min($oy + 15, $ph - 1)][$ox - 1] ?? 128));
                    $b = (5 * $hv[0] + 32) >> 6;
                    $c = (5 * $hv[1] + 32) >> 6;
                    for ($y = 0; $y < 16; $y++) {
                        for ($x = 0; $x < 16; $x++) {
                            $pred[$y][$x] = max(0, min(255, ($a + $b * ($x - 7) + $c * ($y - 7) + 16) >> 5));
                        }
                    }
                }
                break;
        }

        for ($y = 0; $y < 16; $y++) {
            for ($x = 0; $x < 16; $x++) {
                if ($oy + $y < $ph && $ox + $x < $pw) {
                    $p[$oy + $y][$ox + $x] = $pred[$y][$x];
                }
            }
        }
    }

    private static function h264PredI8x8(array &$p, int $bx, int $by, int $mode, int $pw, int $ph): void
    {
		$top = [];
		for ($index = 0; $index < 16; $index++) {
			$top[$index] = ($by > 0 && $bx + $index < $pw) ? ($p[$by - 1][$bx + $index] ?? 128) : ($top[$index - 1] ?? 128);
		}
		$left = [];
		for ($index = 0; $index < 8; $index++) {
			$left[$index] = ($bx > 0 && $by + $index < $ph) ? ($p[$by + $index][$bx - 1] ?? 128) : ($left[$index - 1] ?? 128);
		}
		$topLeft = ($bx > 0 && $by > 0) ? ($p[$by - 1][$bx - 1] ?? 128) : 128;

		$f1 = static fn(int $a, int $b): int => ($a + $b + 1) >> 1;
		$f2 = static fn(int $a, int $b, int $c): int => ($a + (2 * $b) + $c + 2) >> 2;
		$pred = array_fill(0, 8, array_fill(0, 8, 128));

		switch ($mode) {
			case 0:
				for ($y = 0; $y < 8; $y++) {
					for ($x = 0; $x < 8; $x++) {
						$pred[$y][$x] = $top[$x];
					}
				}
				break;

			case 1:
				for ($y = 0; $y < 8; $y++) {
					for ($x = 0; $x < 8; $x++) {
						$pred[$y][$x] = $left[$y];
					}
				}
				break;

			case 2:
				$sum = 0;
				for ($index = 0; $index < 8; $index++) {
					$sum += $top[$index] + $left[$index];
				}
				$dc = ($sum + 8) >> 4;
				$pred = array_fill(0, 8, array_fill(0, 8, $dc));
				break;

			case 3:
				$pred[0][0] = $f2($top[0], $top[1], $top[2]);
				$pred[0][1] = $pred[1][0] = $f2($top[1], $top[2], $top[3]);
				$pred[0][2] = $pred[1][1] = $pred[2][0] = $f2($top[2], $top[3], $top[4]);
				$pred[0][3] = $pred[1][2] = $pred[2][1] = $pred[3][0] = $f2($top[3], $top[4], $top[5]);
				$pred[0][4] = $pred[1][3] = $pred[2][2] = $pred[3][1] = $pred[4][0] = $f2($top[4], $top[5], $top[6]);
				$pred[0][5] = $pred[1][4] = $pred[2][3] = $pred[3][2] = $pred[4][1] = $pred[5][0] = $f2($top[5], $top[6], $top[7]);
				$pred[0][6] = $pred[1][5] = $pred[2][4] = $pred[3][3] = $pred[4][2] = $pred[5][1] = $pred[6][0] = $f2($top[6], $top[7], $top[8]);
				$pred[0][7] = $pred[1][6] = $pred[2][5] = $pred[3][4] = $pred[4][3] = $pred[5][2] = $pred[6][1] = $pred[7][0] = $f2($top[7], $top[8], $top[9]);
				$pred[1][7] = $pred[2][6] = $pred[3][5] = $pred[4][4] = $pred[5][3] = $pred[6][2] = $pred[7][1] = $f2($top[8], $top[9], $top[10]);
				$pred[2][7] = $pred[3][6] = $pred[4][5] = $pred[5][4] = $pred[6][3] = $pred[7][2] = $f2($top[9], $top[10], $top[11]);
				$pred[3][7] = $pred[4][6] = $pred[5][5] = $pred[6][4] = $pred[7][3] = $f2($top[10], $top[11], $top[12]);
				$pred[4][7] = $pred[5][6] = $pred[6][5] = $pred[7][4] = $f2($top[11], $top[12], $top[13]);
				$pred[5][7] = $pred[6][6] = $pred[7][5] = $f2($top[12], $top[13], $top[14]);
				$pred[6][7] = $pred[7][6] = $f2($top[13], $top[14], $top[15]);
				$pred[7][7] = $f2($top[14], $top[15], $top[15]);
				break;

			case 4:
				$pred[7][0] = $f2($left[7], $left[6], $left[5]);
				$pred[6][0] = $pred[7][1] = $f2($left[6], $left[5], $left[4]);
				$pred[5][0] = $pred[6][1] = $pred[7][2] = $f2($left[5], $left[4], $left[3]);
				$pred[4][0] = $pred[5][1] = $pred[6][2] = $pred[7][3] = $f2($left[4], $left[3], $left[2]);
				$pred[3][0] = $pred[4][1] = $pred[5][2] = $pred[6][3] = $pred[7][4] = $f2($left[3], $left[2], $left[1]);
				$pred[2][0] = $pred[3][1] = $pred[4][2] = $pred[5][3] = $pred[6][4] = $pred[7][5] = $f2($left[2], $left[1], $left[0]);
				$pred[1][0] = $pred[2][1] = $pred[3][2] = $pred[4][3] = $pred[5][4] = $pred[6][5] = $pred[7][6] = $f2($left[1], $left[0], $topLeft);
				$pred[0][0] = $pred[1][1] = $pred[2][2] = $pred[3][3] = $pred[4][4] = $pred[5][5] = $pred[6][6] = $pred[7][7] = $f2($left[0], $topLeft, $top[0]);
				$pred[1][0] = $pred[2][1] = $pred[3][2] = $pred[4][3] = $pred[5][4] = $pred[6][5] = $pred[7][6] = $f2($topLeft, $top[0], $top[1]);
				$pred[2][0] = $pred[3][1] = $pred[4][2] = $pred[5][3] = $pred[6][4] = $pred[7][5] = $f2($top[0], $top[1], $top[2]);
				$pred[3][0] = $pred[4][1] = $pred[5][2] = $pred[6][3] = $pred[7][4] = $f2($top[1], $top[2], $top[3]);
				$pred[4][0] = $pred[5][1] = $pred[6][2] = $pred[7][3] = $f2($top[2], $top[3], $top[4]);
				$pred[5][0] = $pred[6][1] = $pred[7][2] = $f2($top[3], $top[4], $top[5]);
				$pred[6][0] = $pred[7][1] = $f2($top[4], $top[5], $top[6]);
				$pred[7][0] = $f2($top[5], $top[6], $top[7]);
				break;

			case 5:
				$pred[0][6] = $f2($left[5], $left[4], $left[3]);
				$pred[0][7] = $f2($left[6], $left[5], $left[4]);
				$pred[0][4] = $pred[1][6] = $f2($left[3], $left[2], $left[1]);
				$pred[0][5] = $pred[1][7] = $f2($left[4], $left[3], $left[2]);
				$pred[0][2] = $pred[1][4] = $pred[2][6] = $f2($left[1], $left[0], $topLeft);
				$pred[0][3] = $pred[1][5] = $pred[2][7] = $f2($left[2], $left[1], $left[0]);
				$pred[0][1] = $pred[1][3] = $pred[2][5] = $pred[3][7] = $f2($left[0], $topLeft, $top[0]);
				$pred[0][0] = $pred[1][2] = $pred[2][4] = $pred[3][6] = $f1($topLeft, $top[0]);
				$pred[1][1] = $pred[2][3] = $pred[3][5] = $pred[4][7] = $f2($topLeft, $top[0], $top[1]);
				$pred[1][0] = $pred[2][2] = $pred[3][4] = $pred[4][6] = $f1($top[0], $top[1]);
				$pred[2][1] = $pred[3][3] = $pred[4][5] = $pred[5][7] = $f2($top[0], $top[1], $top[2]);
				$pred[2][0] = $pred[3][2] = $pred[4][4] = $pred[5][6] = $f1($top[1], $top[2]);
				$pred[3][1] = $pred[4][3] = $pred[5][5] = $pred[6][7] = $f2($top[1], $top[2], $top[3]);
				$pred[3][0] = $pred[4][2] = $pred[5][4] = $pred[6][6] = $f1($top[2], $top[3]);
				$pred[4][1] = $pred[5][3] = $pred[6][5] = $pred[7][7] = $f2($top[2], $top[3], $top[4]);
				$pred[4][0] = $pred[5][2] = $pred[6][4] = $pred[7][6] = $f1($top[3], $top[4]);
				$pred[5][1] = $pred[6][3] = $pred[7][5] = $f2($top[3], $top[4], $top[5]);
				$pred[5][0] = $pred[6][2] = $pred[7][4] = $f1($top[4], $top[5]);
				$pred[6][1] = $pred[7][3] = $f2($top[4], $top[5], $top[6]);
				$pred[6][0] = $pred[7][2] = $f1($top[5], $top[6]);
				$pred[7][1] = $f2($top[5], $top[6], $top[7]);
				$pred[7][0] = $f1($top[6], $top[7]);
				break;

			case 6:
				$pred[0][7] = $f1($left[6], $left[7]);
				$pred[1][7] = $f2($left[5], $left[6], $left[7]);
				$pred[0][6] = $pred[2][7] = $f1($left[5], $left[6]);
				$pred[1][6] = $pred[3][7] = $f2($left[4], $left[5], $left[6]);
				$pred[0][5] = $pred[2][6] = $f1($left[4], $left[5]);
				$pred[1][5] = $pred[3][6] = $f2($left[3], $left[4], $left[5]);
				$pred[0][4] = $pred[2][5] = $pred[4][6] = $f1($left[3], $left[4]);
				$pred[1][4] = $pred[3][5] = $pred[5][6] = $f2($left[2], $left[3], $left[4]);
				$pred[0][3] = $pred[2][4] = $pred[4][5] = $pred[6][6] = $f1($left[2], $left[3]);
				$pred[1][3] = $pred[3][4] = $pred[5][5] = $pred[7][6] = $f2($left[1], $left[2], $left[3]);
				$pred[0][2] = $pred[2][3] = $pred[4][4] = $pred[6][5] = $f1($left[1], $left[2]);
				$pred[1][2] = $pred[3][3] = $pred[5][4] = $pred[7][5] = $f2($left[0], $left[1], $left[2]);
				$pred[0][1] = $pred[2][2] = $pred[4][3] = $pred[6][4] = $f1($left[0], $left[1]);
				$pred[1][1] = $pred[3][2] = $pred[5][3] = $pred[7][4] = $f2($topLeft, $left[0], $left[1]);
				$pred[0][0] = $pred[2][1] = $pred[4][2] = $pred[6][3] = $f1($topLeft, $left[0]);
				$pred[1][0] = $pred[3][1] = $pred[5][2] = $pred[7][3] = $f2($top[0], $topLeft, $left[0]);
				$pred[2][0] = $pred[4][1] = $pred[6][2] = $f2($top[1], $top[0], $topLeft);
				$pred[3][0] = $pred[5][1] = $pred[7][2] = $f2($top[2], $top[1], $top[0]);
				$pred[4][0] = $pred[6][1] = $f2($top[3], $top[2], $top[1]);
				$pred[5][0] = $pred[7][1] = $f2($top[4], $top[3], $top[2]);
				$pred[6][0] = $f2($top[5], $top[4], $top[3]);
				$pred[7][0] = $f2($top[6], $top[5], $top[4]);
				break;

			case 7:
				$pred[0][0] = $f1($top[0], $top[1]);
				$pred[1][0] = $f2($top[0], $top[1], $top[2]);
				$pred[0][1] = $pred[2][0] = $f1($top[1], $top[2]);
				$pred[1][1] = $pred[3][0] = $f2($top[1], $top[2], $top[3]);
				$pred[0][2] = $pred[2][1] = $pred[4][0] = $f1($top[2], $top[3]);
				$pred[1][2] = $pred[3][1] = $pred[5][0] = $f2($top[2], $top[3], $top[4]);
				$pred[0][3] = $pred[2][2] = $pred[4][1] = $pred[6][0] = $f1($top[3], $top[4]);
				$pred[1][3] = $pred[3][2] = $pred[5][1] = $pred[7][0] = $f2($top[3], $top[4], $top[5]);
				$pred[0][4] = $pred[2][3] = $pred[4][2] = $pred[6][1] = $f1($top[4], $top[5]);
				$pred[1][4] = $pred[3][3] = $pred[5][2] = $pred[7][1] = $f2($top[4], $top[5], $top[6]);
				$pred[0][5] = $pred[2][4] = $pred[4][3] = $pred[6][2] = $f1($top[5], $top[6]);
				$pred[1][5] = $pred[3][4] = $pred[5][3] = $pred[7][2] = $f2($top[5], $top[6], $top[7]);
				$pred[0][6] = $pred[2][5] = $pred[4][4] = $pred[6][3] = $f1($top[6], $top[7]);
				$pred[1][6] = $pred[3][5] = $pred[5][4] = $pred[7][3] = $f2($top[6], $top[7], $top[8]);
				$pred[0][7] = $pred[2][6] = $pred[4][5] = $pred[6][4] = $f1($top[7], $top[8]);
				$pred[1][7] = $pred[3][6] = $pred[5][5] = $pred[7][4] = $f2($top[7], $top[8], $top[9]);
				$pred[2][7] = $pred[4][6] = $pred[6][5] = $f1($top[8], $top[9]);
				$pred[3][7] = $pred[5][6] = $pred[7][5] = $f2($top[8], $top[9], $top[10]);
				$pred[4][7] = $pred[6][6] = $f1($top[9], $top[10]);
				$pred[5][7] = $pred[7][6] = $f2($top[9], $top[10], $top[11]);
				$pred[6][7] = $f1($top[10], $top[11]);
				$pred[7][7] = $f2($top[10], $top[11], $top[12]);
				break;

			case 8:
				$pred[0][0] = $f1($left[0], $left[1]);
				$pred[0][1] = $f2($left[0], $left[1], $left[2]);
				$pred[1][0] = $pred[0][2] = $f1($left[1], $left[2]);
				$pred[1][1] = $pred[0][3] = $f2($left[1], $left[2], $left[3]);
				$pred[2][0] = $pred[1][2] = $pred[0][4] = $f1($left[2], $left[3]);
				$pred[2][1] = $pred[1][3] = $pred[0][5] = $f2($left[2], $left[3], $left[4]);
				$pred[3][0] = $pred[2][2] = $pred[1][4] = $pred[0][6] = $f1($left[3], $left[4]);
				$pred[3][1] = $pred[2][3] = $pred[1][5] = $pred[0][7] = $f2($left[3], $left[4], $left[5]);
				$pred[4][0] = $pred[3][2] = $pred[2][4] = $pred[1][6] = $f1($left[4], $left[5]);
				$pred[4][1] = $pred[3][3] = $pred[2][5] = $pred[1][7] = $f2($left[4], $left[5], $left[6]);
				$pred[5][0] = $pred[4][2] = $pred[3][4] = $pred[2][6] = $f1($left[5], $left[6]);
				$pred[5][1] = $pred[4][3] = $pred[3][5] = $pred[2][7] = $f2($left[5], $left[6], $left[7]);
				$pred[6][0] = $pred[5][2] = $pred[4][4] = $pred[3][6] = $f1($left[6], $left[7]);
				$pred[6][1] = $pred[5][3] = $pred[4][5] = $pred[3][7] = $f2($left[6], $left[7], $left[7]);
				$pred[7][0] = $pred[6][2] = $pred[5][4] = $pred[4][6] = $f1($left[7], $left[7]);
				$pred[7][1] = $pred[6][3] = $pred[5][5] = $pred[4][7] = $left[7];
				$pred[7][2] = $pred[6][4] = $pred[5][6] = $left[7];
				$pred[7][3] = $pred[6][5] = $pred[5][7] = $left[7];
				$pred[7][4] = $pred[6][6] = $left[7];
				$pred[7][5] = $pred[6][7] = $left[7];
				$pred[7][6] = $pred[7][7] = $left[7];
				break;

			default:
				break;
		}

		for ($y = 0; $y < 8; $y++) {
			for ($x = 0; $x < 8; $x++) {
				if ($by + $y < $ph && $bx + $x < $pw) {
					$p[$by + $y][$bx + $x] = $pred[$y][$x];
				}
			}
		}
    }

    private static function h264PredI4x4(array &$p, int $bx, int $by, int $mode, int $pw, int $ph): void
    {
        $top = [];
        for ($index = 0; $index < 8; $index++) {
            $top[$index] = ($by > 0 && $bx + $index < $pw) ? ($p[$by - 1][$bx + $index] ?? 128) : ($top[$index - 1] ?? 128);
        }
        $left = [];
        for ($index = 0; $index < 4; $index++) {
            $left[$index] = ($bx > 0 && $by + $index < $ph) ? ($p[$by + $index][$bx - 1] ?? 128) : ($left[$index - 1] ?? 128);
        }
        $topLeft = ($bx > 0 && $by > 0) ? ($p[$by - 1][$bx - 1] ?? 128) : 128;

        $f1 = static fn(int $a, int $b): int => ($a + $b + 1) >> 1;
        $f2 = static fn(int $a, int $b, int $c): int => ($a + (2 * $b) + $c + 2) >> 2;
        $pred = array_fill(0, 4, array_fill(0, 4, 128));

        switch ($mode) {
            case 0:
                for ($y = 0; $y < 4; $y++) {
                    for ($x = 0; $x < 4; $x++) {
                        $pred[$y][$x] = $top[$x];
                    }
                }
                break;

            case 1:
                for ($y = 0; $y < 4; $y++) {
                    for ($x = 0; $x < 4; $x++) {
                        $pred[$y][$x] = $left[$y];
                    }
                }
                break;

            case 2:
                $sum = 0;
                $count = 0;
                if ($by > 0) {
                    for ($x = 0; $x < 4; $x++) {
                        $sum += $top[$x];
                        $count++;
                    }
                }
                if ($bx > 0) {
                    for ($y = 0; $y < 4; $y++) {
                        $sum += $left[$y];
                        $count++;
                    }
                }
                $dc = $count > 0 ? intdiv($sum + ($count >> 1), $count) : 128;
                $pred = array_fill(0, 4, array_fill(0, 4, $dc));
                break;

            case 3:
                $pred[0][0] = $f2($top[0], $top[1], $top[2]);
                $pred[0][1] = $pred[1][0] = $f2($top[1], $top[2], $top[3]);
                $pred[0][2] = $pred[1][1] = $pred[2][0] = $f2($top[2], $top[3], $top[4]);
                $pred[0][3] = $pred[1][2] = $pred[2][1] = $pred[3][0] = $f2($top[3], $top[4], $top[5]);
                $pred[1][3] = $pred[2][2] = $pred[3][1] = $f2($top[4], $top[5], $top[6]);
                $pred[2][3] = $pred[3][2] = $f2($top[5], $top[6], $top[7]);
                $pred[3][3] = $f2($top[6], $top[7], $top[7]);
                break;

            case 4:
                $pred[3][0] = $f2($top[3], $top[2], $top[1]);
                $pred[2][0] = $pred[3][1] = $f2($top[2], $top[1], $top[0]);
                $pred[1][0] = $pred[2][1] = $pred[3][2] = $f2($top[1], $top[0], $topLeft);
                $pred[0][0] = $pred[1][1] = $pred[2][2] = $pred[3][3] = $f2($top[0], $topLeft, $left[0]);
                $pred[0][1] = $pred[1][2] = $pred[2][3] = $f2($topLeft, $left[0], $left[1]);
                $pred[0][2] = $pred[1][3] = $f2($left[0], $left[1], $left[2]);
                $pred[0][3] = $f2($left[1], $left[2], $left[3]);
                break;

            case 5:
                $pred[0][3] = $f2($left[2], $left[1], $left[0]);
                $pred[0][2] = $f2($left[1], $left[0], $topLeft);
                $pred[0][1] = $pred[1][3] = $f2($left[0], $topLeft, $top[0]);
                $pred[0][0] = $pred[1][2] = $f1($topLeft, $top[0]);
                $pred[1][1] = $pred[2][3] = $f2($topLeft, $top[0], $top[1]);
                $pred[1][0] = $pred[2][2] = $f1($top[0], $top[1]);
                $pred[2][1] = $pred[3][3] = $f2($top[0], $top[1], $top[2]);
                $pred[2][0] = $pred[3][2] = $f1($top[1], $top[2]);
                $pred[3][1] = $f2($top[1], $top[2], $top[3]);
                $pred[3][0] = $f1($top[2], $top[3]);
                break;

            case 6:
                $pred[0][3] = $f1($left[2], $left[3]);
                $pred[1][3] = $f2($left[1], $left[2], $left[3]);
                $pred[0][2] = $pred[2][3] = $f1($left[1], $left[2]);
                $pred[1][2] = $pred[3][3] = $f2($left[0], $left[1], $left[2]);
                $pred[0][1] = $pred[2][2] = $f1($left[0], $left[1]);
                $pred[1][1] = $pred[3][2] = $f2($topLeft, $left[0], $left[1]);
                $pred[0][0] = $pred[2][1] = $f1($topLeft, $left[0]);
                $pred[1][0] = $pred[3][1] = $f2($top[0], $topLeft, $left[0]);
                $pred[2][0] = $f2($top[1], $top[0], $topLeft);
                $pred[3][0] = $f2($top[2], $top[1], $top[0]);
                break;

            case 7:
                $pred[0][0] = $f1($top[0], $top[1]);
                $pred[0][1] = $f2($top[0], $top[1], $top[2]);
                $pred[1][0] = $pred[0][2] = $f1($top[1], $top[2]);
                $pred[1][1] = $pred[0][3] = $f2($top[1], $top[2], $top[3]);
                $pred[2][0] = $pred[1][2] = $f1($top[2], $top[3]);
                $pred[2][1] = $pred[1][3] = $f2($top[2], $top[3], $top[4]);
                $pred[3][0] = $pred[2][2] = $f1($top[3], $top[4]);
                $pred[3][1] = $pred[2][3] = $f2($top[3], $top[4], $top[5]);
                $pred[3][2] = $f1($top[4], $top[5]);
                $pred[3][3] = $f2($top[4], $top[5], $top[6]);
                break;

            case 8:
                $pred[0][0] = $f1($left[0], $left[1]);
                $pred[1][0] = $f2($left[0], $left[1], $left[2]);
                $pred[2][0] = $pred[0][1] = $f1($left[1], $left[2]);
                $pred[3][0] = $pred[1][1] = $f2($left[1], $left[2], $left[3]);
                $pred[2][1] = $pred[0][2] = $f1($left[2], $left[3]);
                $pred[3][1] = $pred[1][2] = $f2($left[2], $left[3], $left[3]);
                $pred[3][2] = $pred[1][3] = $pred[0][3] = $left[3];
                $pred[2][2] = $pred[2][3] = $pred[3][3] = $left[3];
                break;
        }

        for ($y = 0; $y < 4; $y++) {
            for ($x = 0; $x < 4; $x++) {
                if ($by + $y < $ph && $bx + $x < $pw) {
                    $p[$by + $y][$bx + $x] = $pred[$y][$x];
                }
            }
        }
    }

    private static function h264PredChroma(array &$p, int $mx, int $my, int $mode, int $pw, int $ph): void
    {
        $ox = $mx * 8;
        $oy = $my * 8;
        $pred = array_fill(0, 8, array_fill(0, 8, 128));
        switch ($mode) {
            case 0:
                for ($blk = 0; $blk < 4; $blk++) {
                    $bx2 = ($blk & 1) * 4;
                    $by2 = ($blk >> 1) * 4;
                    $s = 0;
                    $c = 0;
                    if ($oy > 0) {
                        for ($x = 0; $x < 4; $x++) {
                            $s += $p[$oy - 1][$ox + $bx2 + $x] ?? 128;
                            $c++;
                        }
                    }
                    if ($ox > 0) {
                        for ($y = 0; $y < 4; $y++) {
                            $s += $p[$oy + $by2 + $y][$ox - 1] ?? 128;
                            $c++;
                        }
                    }
                    $dc = $c > 0 ? intdiv($s + ($c >> 1), $c) : 128;
                    for ($y = 0; $y < 4; $y++) {
                        for ($x = 0; $x < 4; $x++) {
                            $pred[$by2 + $y][$bx2 + $x] = $dc;
                        }
                    }
                }
                break;
            case 1:
                if ($ox > 0) {
                    for ($y = 0; $y < 8; $y++) {
                        for ($x = 0; $x < 8; $x++) {
                            $pred[$y][$x] = $p[$oy + $y][$ox - 1] ?? 128;
                        }
                    }
                }
                break;
            case 2:
                if ($oy > 0) {
                    for ($y = 0; $y < 8; $y++) {
                        for ($x = 0; $x < 8; $x++) {
                            $pred[$y][$x] = $p[$oy - 1][$ox + $x] ?? 128;
                        }
                    }
                }
                break;
            case 3:
                if ($oy > 0 && $ox > 0) {
                    $hv = [0, 0];
                    for ($i = 0; $i < 4; $i++) {
                        $hv[0] += ($i + 1) * (($p[$oy - 1][min($ox + 4 + $i, $pw - 1)] ?? 128) - ($p[$oy - 1][max($ox + 2 - $i, 0)] ?? 128));
                        $hv[1] += ($i + 1) * (($p[min($oy + 4 + $i, $ph - 1)][$ox - 1] ?? 128) - ($p[max($oy + 2 - $i, 0)][$ox - 1] ?? 128));
                    }
                    $a = 16 * (($p[$oy - 1][min($ox + 7, $pw - 1)] ?? 128) + ($p[min($oy + 7, $ph - 1)][$ox - 1] ?? 128));
                    $b = (17 * $hv[0] + 16) >> 5;
                    $c2 = (17 * $hv[1] + 16) >> 5;
                    for ($y = 0; $y < 8; $y++) {
                        for ($x = 0; $x < 8; $x++) {
                            $pred[$y][$x] = max(0, min(255, ($a + $b * ($x - 3) + $c2 * ($y - 3) + 16) >> 5));
                        }
                    }
                }
                break;
        }

        for ($y = 0; $y < 8; $y++) {
            for ($x = 0; $x < 8; $x++) {
                if ($oy + $y < $ph && $ox + $x < $pw) {
                    $p[$oy + $y][$ox + $x] = $pred[$y][$x];
                }
            }
        }
    }

    private static function dequantCoeff(int $coeff, int $qp, int $pos, bool $isIntra): int
    {
        $vMat = [
            [10, 16, 13],
            [11, 18, 14],
            [13, 20, 16],
            [14, 23, 18],
            [16, 25, 20],
            [18, 29, 23],
        ];
        $qpPer = intdiv($qp, 6);
        $qpRem = $qp % 6;

        // Map zigzag scan position to 2D coordinate (row, col) then determine V matrix index:
        //   V[0] = both row,col even -> zigzag pos 0,3,5,11
        //   V[1] = mixed row/col parity positions -> zigzag pos 1,2,6,7,8,9,13,14
        //   V[2] = both row,col odd -> zigzag pos 4,10,12,15
        $posMap = [
            0 => 0,
            3 => 0,
            5 => 0,
            11 => 0,
            1 => 1,
            2 => 1,
            6 => 1,
            7 => 1,
            8 => 1,
            9 => 1,
            13 => 1,
            14 => 1,
            4 => 2,
            10 => 2,
            12 => 2,
            15 => 2
        ];
        $idx = $posMap[$pos % 16] ?? 0;

        return ($coeff * $vMat[$qpRem][$idx]) << $qpPer;
    }

    private static function decodeCavlcBlock(array &$br, int $nC, int $maxCoeff, bool $isChromaDC = false): array
    {
        [$totalCoeff, $trailingOnes] = self::readCoeffToken($br, $isChromaDC ? -1 : $nC);

        if ($totalCoeff === 0) {
            return [
                'coeffs' => array_fill(0, $maxCoeff, 0),
                'totalCoeff' => 0,
            ];
        }

        $levels = [];
        for ($i = 0; $i < $trailingOnes; $i++) {
            $levels[] = self::brRead($br, 1) === 0 ? 1 : -1;
        }
        $levels = array_reverse($levels);

        $suffixLength = ($totalCoeff > 10 && $trailingOnes < 3) ? 1 : 0;
        for ($i = $trailingOnes; $i < $totalCoeff; $i++) {
            $levelPrefix = 0;
            while (self::brRead($br, 1) === 0) {
                $levelPrefix++;
                if ($levelPrefix > 20) {
                    break;
                }
            }

            $levelCode = $levelPrefix << $suffixLength;
            if ($suffixLength > 0 || $levelPrefix >= 14) {
                $suffBits = ($levelPrefix >= 15) ? ($levelPrefix - 3) : max($suffixLength, 1);
                if ($suffBits > 0 && $suffBits < 20) {
                    $levelCode += self::brRead($br, $suffBits);
                }
            }

            if ($i === $trailingOnes && $trailingOnes < 3) {
                $levelCode += 2;
            }

            $level = ($levelCode & 1) ? -(($levelCode + 1) >> 1) : (($levelCode + 2) >> 1);
            array_unshift($levels, $level);

            if ($suffixLength === 0) {
                $suffixLength = 1;
            }
            if (abs($level) > (3 << ($suffixLength - 1)) && $suffixLength < 6) {
                $suffixLength++;
            }
        }

        $totalZeros = ($totalCoeff < $maxCoeff) ? self::readTotalZeros($br, $totalCoeff, $maxCoeff) : 0;

        $coeffs = array_fill(0, $maxCoeff, 0);
        $zerosLeft = $totalZeros;
        $coeffIdx = $totalCoeff + $totalZeros - 1;

        for ($i = 0; $i < $totalCoeff - 1 && $coeffIdx >= 0; $i++) {
            $runBefore = ($zerosLeft > 0) ? self::readRunBefore($br, $zerosLeft) : 0;
            $coeffs[$coeffIdx] = $levels[$i];
            $zerosLeft -= $runBefore;
            $coeffIdx -= 1 + $runBefore;
        }
        if ($coeffIdx >= 0 && $totalCoeff > 0) {
            $coeffs[$coeffIdx] = $levels[$totalCoeff - 1];
        }

        return ['coeffs' => $coeffs, 'totalCoeff' => $totalCoeff];
    }

    private static function decodeH264IdrToImage(string $sampleData, array $config, int $width, int $height): \GdImage
    {
        $nalLengthSize = $config['nal_length_size'];

        $sps = null;
        foreach ($config['sps'] as $spsNalu) {
            $rbsp = self::naluToRbsp(substr($spsNalu, 1));
            $sps = self::decodeSpsRbsp($rbsp);
            break;
        }
        if ($sps === null) {
            throw new Exception('No SPS found in decoder configuration.');
        }

        $pps = null;
        foreach ($config['pps'] as $ppsNalu) {
            $rbsp = self::naluToRbsp(substr($ppsNalu, 1));
            $pps = self::decodePpsRbsp($rbsp);
            break;
        }
        if ($pps === null) {
            throw new Exception('No PPS found in decoder configuration.');
        }

        $sliceNalus = [];
        $offset = 0;
        $len = strlen($sampleData);
        while ($offset + $nalLengthSize <= $len) {
            $naluLen = 0;
            for ($i = 0; $i < $nalLengthSize; $i++) {
                $naluLen = ($naluLen << 8) | ord($sampleData[$offset + $i]);
            }
            $offset += $nalLengthSize;
            if ($naluLen > 0 && $offset + $naluLen <= $len) {
                $naluType = ord($sampleData[$offset]) & 0x1F;
                if ($naluType === 5) {
                    $sliceNalus[] = substr($sampleData, $offset, $naluLen);
                }
                $offset += $naluLen;
            } else {
                break;
            }
        }

        if (empty($sliceNalus)) {
            throw new Exception('No IDR slice NAL unit found in sample data.');
        }

        $mbW = $sps['pic_width_in_mbs'];
        $mbH = $sps['pic_height_in_map_units'];
        $lumaW = $mbW * 16;
        $lumaH = $mbH * 16;
        $chromaW = $lumaW >> 1;
        $chromaH = $lumaH >> 1;

        $yPlane = array_fill(0, $lumaH, array_fill(0, $lumaW, 128));
        $uPlane = array_fill(0, $chromaH, array_fill(0, $chromaW, 128));
        $vPlane = array_fill(0, $chromaH, array_fill(0, $chromaW, 128));

        $nzCoeffs = array_fill(0, $mbH, array_fill(0, $mbW, array_fill(0, 24, 0)));

        foreach ($sliceNalus as $naluData) {
            $rbsp = self::naluToRbsp(substr($naluData, 1));
            $br = self::brCreate($rbsp);

            self::brReadUE($br);
            $sliceType = self::brReadUE($br);
            self::brReadUE($br);
            self::brRead($br, $sps['log2_max_frame_num']);

            if (!$sps['frame_mbs_only_flag']) {
                if (self::brRead($br, 1)) {
                    self::brRead($br, 1);
                }
            }

            self::brReadUE($br);

            if ($sps['pic_order_cnt_type'] === 0) {
                self::brRead($br, $sps['log2_max_pic_order_cnt_lsb']);
            }

            self::brRead($br, 1);
            self::brRead($br, 1);

            $sliceQpDelta = self::brReadSE($br);
            $qpY = $pps['pic_init_qp'] + $sliceQpDelta;
            $qpC = max(0, min(51, $qpY + $pps['chroma_qp_offset']));

            if ($pps['deblocking_filter_control_present_flag']) {
                $disableDbFilter = self::brReadUE($br);
                if ($disableDbFilter !== 1) {
                    self::brReadSE($br);
                    self::brReadSE($br);
                }
            }

            $totalMbs = $mbW * $mbH;
            for ($mbIdx = 0; $mbIdx < $totalMbs; $mbIdx++) {
                try {
                    $mbX = $mbIdx % $mbW;
                    $mbY = intdiv($mbIdx, $mbW);

                    $mbType = self::brReadUE($br);

                    if ($mbType === 25) {
                        for ($y = 0; $y < 16; $y++) {
                            for ($x = 0; $x < 16; $x++) {
                                $yPlane[$mbY * 16 + $y][$mbX * 16 + $x] = self::brRead($br, 8);
                            }
                        }
                        for ($y = 0; $y < 8; $y++) {
                            for ($x = 0; $x < 8; $x++) {
                                $uPlane[$mbY * 8 + $y][$mbX * 8 + $x] = self::brRead($br, 8);
                            }
                        }
                        for ($y = 0; $y < 8; $y++) {
                            for ($x = 0; $x < 8; $x++) {
                                $vPlane[$mbY * 8 + $y][$mbX * 8 + $x] = self::brRead($br, 8);
                            }
                        }
                        continue;
                    }

                    $isI16x16 = ($mbType >= 1 && $mbType <= 24);
                    $i16mode = 0;
                    $chromaPredMode = 0;

                    if ($isI16x16) {
                        // I_16x16 macroblock type decoding:
                        //   mbType-1 = cbpLuma_flag(0 or 1)*12 + cbpChroma(0-2)*4 + i16mode(0-3)
                        $i16mode = ($mbType - 1) % 4;
                        // cbpChroma derived from mbType (0=none, 1=DC only, 2=DC+AC)
                        $cbpChroma = intdiv($mbType - 1, 4) % 3;
                        $cbpLuma = ($mbType > 12) ? 15 : 0;
                        // intra_chroma_pred_mode must be read separately from the bitstream
                        // (must not be derived from mbType -- H.264 spec 7.3.5 slice_data)
                        $chromaPredMode = self::brReadUE($br);
                    } else {
                        // I_NxN (I_4x4) macroblock
                        // Read prediction mode for each 4x4 block from bitstream into array
                        $predModes = [];
                        if (!$pps['transform_8x8_mode_flag']) {
                            for ($blk = 0; $blk < 16; $blk++) {
                                if (self::brRead($br, 1) === 1) {
                                    // prev_intra4x4_pred_mode_flag = 1 -> use most probable mode
                                    $predModes[$blk] = 2; // DC prediction (default fallback)
                                } else {
                                    // prev_intra4x4_pred_mode_flag = 0 -> read 3-bit mode from bitstream
                                    $predModes[$blk] = self::brRead($br, 3);
                                }
                            }
                        }
                        $chromaPredMode = self::brReadUE($br);
                        $codedBlockPatternIdx = self::brReadUE($br);
                        $cbpMap = [0, 16, 32, 15, 1, 2, 4, 8, 48, 3, 5, 10, 12, 9, 6, 47, 31, 17, 18, 20, 24, 19, 22, 33, 34, 36, 40, 28, 26, 21, 14, 35, 38, 44, 13, 11, 7, 37, 42, 25, 29, 41, 23, 43, 39, 45, 27, 46];
                        $cbp = $cbpMap[$codedBlockPatternIdx] ?? 0;
                        $cbpLuma = $cbp & 15;
                        $cbpChroma = ($cbp >> 4) & 3;
                    }

                    if ($isI16x16) {
                        Vector::predictIntra16x16($yPlane, $mbX, $mbY, $i16mode, $lumaW, $lumaH);
                    }
                    Vector::predictChroma($uPlane, $mbX, $mbY, $chromaPredMode, $chromaW, $chromaH);
                    Vector::predictChroma($vPlane, $mbX, $mbY, $chromaPredMode, $chromaW, $chromaH);

                    $hasResidual = $isI16x16 || (isset($cbpLuma) && ($cbpLuma > 0 || $cbpChroma > 0));

                    if ($hasResidual && ($isI16x16 || $cbpLuma > 0 || $cbpChroma > 0)) {
                        $mbQpDelta = self::brReadSE($br);
                        $qpY = max(0, min(51, $qpY + $mbQpDelta));
                        $qpC = max(0, min(51, $qpY + $pps['chroma_qp_offset']));
                    }

                    if ($isI16x16) {
                        $dcResult = self::decodeCavlcBlock($br, 0, 16, false);
                        $dcCoeffs = is_array($dcResult['coeffs'] ?? null) ? $dcResult['coeffs'] : array_fill(0, 16, 0);
                        $dcTransformed = Vector::inverseHadamard4x4($dcCoeffs, $qpY);

                        for ($blk = 0; $blk < 16; $blk++) {
                            $nA = 0;
                            $nB = 0;
                            $nC = ($nA + $nB + 1) >> 1;
                            $acResult = ($cbpLuma > 0) ? self::decodeCavlcBlock($br, $nC, 15) : ['coeffs' => array_fill(0, 15, 0), 'totalCoeff' => 0];
                            $acCoeffs = $acResult['coeffs'];
                            $nzCoeffs[$mbY][$mbX][$blk] = $acResult['totalCoeff'];

                            $block4x4 = array_fill(0, 4, array_fill(0, 4, 0));
                            $bx = ($blk & 3);
                            $by = ($blk >> 2);
                            $remap = [$by * 4 + $bx];
                            $dcIdx = (($blk >> 2) & 3) * 4 + (($blk & 3));
                            $dcRemap = [0, 1, 4, 5, 2, 3, 6, 7, 8, 9, 12, 13, 10, 11, 14, 15];
                            $dcVal = $dcTransformed[$dcRemap[$blk]] ?? 0;

                            $coeffArray = array_fill(0, 16, 0);
                            $coeffArray[0] = $dcVal;
                            for ($k = 0; $k < 15 && $k < count($acCoeffs); $k++) {
                                $coeffArray[$k + 1] = self::dequantCoeff($acCoeffs[$k], $qpY, $k + 1, true);
                            }

                            $zigzag = [0, 1, 4, 8, 5, 2, 3, 6, 9, 12, 13, 10, 7, 11, 14, 15];
                            for ($k = 0; $k < 16; $k++) {
                                $zy = $zigzag[$k] >> 2;
                                $zx = $zigzag[$k] & 3;
                                $block4x4[$zy][$zx] = $coeffArray[$k];
                            }

                            $residual = Vector::inverseTransform4x4($block4x4);

                            $blkScan = [
                                [0, 0],
                                [4, 0],
                                [0, 4],
                                [4, 4],
                                [8, 0],
                                [12, 0],
                                [8, 4],
                                [12, 4],
                                [0, 8],
                                [4, 8],
                                [0, 12],
                                [4, 12],
                                [8, 8],
                                [12, 8],
                                [8, 12],
                                [12, 12],
                            ];
                            $px = $mbX * 16 + $blkScan[$blk][0];
                            $py = $mbY * 16 + $blkScan[$blk][1];

                            for ($ry = 0; $ry < 4; $ry++) {
                                for ($rx = 0; $rx < 4; $rx++) {
                                    if ($py + $ry < $lumaH && $px + $rx < $lumaW) {
                                        $yPlane[$py + $ry][$px + $rx] = max(0, min(255, $yPlane[$py + $ry][$px + $rx] + $residual[$ry][$rx]));
                                    }
                                }
                            }
                        }
                    } elseif (!$isI16x16) {
                        // I_NxN (I_4x4) residual decoding: apply prediction to all blocks and
                        // add residual only when cbpLuma includes the block group
                        $blkScan = [
                            [0, 0],
                            [4, 0],
                            [0, 4],
                            [4, 4],
                            [8, 0],
                            [12, 0],
                            [8, 4],
                            [12, 4],
                            [0, 8],
                            [4, 8],
                            [0, 12],
                            [4, 12],
                            [8, 8],
                            [12, 8],
                            [8, 12],
                            [12, 12],
                        ];
                        for ($blk = 0; $blk < 16; $blk++) {
                            $px = $mbX * 16 + $blkScan[$blk][0];
                            $py = $mbY * 16 + $blkScan[$blk][1];

                            // Apply I_4x4 intra prediction (always, regardless of residual presence)
                            $mode = $predModes[$blk] ?? 2;
                            Vector::predictIntra4x4($yPlane, $px, $py, $mode, $lumaW, $lumaH);

                            $blkGroup = intdiv($blk, 4);
                            if (isset($cbpLuma) && (($cbpLuma >> $blkGroup) & 1)) {
                                $nC = 0;
                                $result = self::decodeCavlcBlock($br, $nC, 16);
                                $coeffArray = $result['coeffs'];
                                $nzCoeffs[$mbY][$mbX][$blk] = $result['totalCoeff'];

                                $block4x4 = array_fill(0, 4, array_fill(0, 4, 0));
                                $zigzag = [0, 1, 4, 8, 5, 2, 3, 6, 9, 12, 13, 10, 7, 11, 14, 15];
                                for ($k = 0; $k < 16; $k++) {
                                    $zy = $zigzag[$k] >> 2;
                                    $zx = $zigzag[$k] & 3;
                                    $block4x4[$zy][$zx] = self::dequantCoeff($coeffArray[$k], $qpY, $k, true);
                                }

                                $residual = Vector::inverseTransform4x4($block4x4);

                                for ($ry = 0; $ry < 4; $ry++) {
                                    for ($rx = 0; $rx < 4; $rx++) {
                                        if ($py + $ry < $lumaH && $px + $rx < $lumaW) {
                                            $yPlane[$py + $ry][$px + $rx] = max(0, min(255, $yPlane[$py + $ry][$px + $rx] + $residual[$ry][$rx]));
                                        }
                                    }
                                }
                            }
                        }
                    }

                    if ($cbpChroma > 0) {
                        foreach ([&$uPlane, &$vPlane] as &$chromaPlane) {
                            $dcResult = self::decodeCavlcBlock($br, -1, 4, true);
                            $chromaDc = $dcResult['coeffs'];
                            $dcTransformed = Vector::inverseHadamard2x2($chromaDc, $qpC);

                            for ($blk = 0; $blk < 4; $blk++) {
                                $nC = 0;
                                $acResult = ($cbpChroma > 1) ? self::decodeCavlcBlock($br, $nC, 15) : ['coeffs' => array_fill(0, 15, 0), 'totalCoeff' => 0];

                                $coeffArray = array_fill(0, 16, 0);
                                $coeffArray[0] = $dcTransformed[$blk];
                                for ($k = 0; $k < 15 && $k < count($acResult['coeffs']); $k++) {
                                    $coeffArray[$k + 1] = self::dequantCoeff($acResult['coeffs'][$k], $qpC, $k + 1, true);
                                }

                                $block4x4 = array_fill(0, 4, array_fill(0, 4, 0));
                                $zigzag = [0, 1, 4, 8, 5, 2, 3, 6, 9, 12, 13, 10, 7, 11, 14, 15];
                                for ($k = 0; $k < 16; $k++) {
                                    $zy = $zigzag[$k] >> 2;
                                    $zx = $zigzag[$k] & 3;
                                    $block4x4[$zy][$zx] = $coeffArray[$k];
                                }

                                $residual = Vector::inverseTransform4x4($block4x4);

                                $chromaBlkScan = [[0, 0], [4, 0], [0, 4], [4, 4]];
                                $cpx = $mbX * 8 + $chromaBlkScan[$blk][0];
                                $cpy = $mbY * 8 + $chromaBlkScan[$blk][1];

                                for ($ry = 0; $ry < 4; $ry++) {
                                    for ($rx = 0; $rx < 4; $rx++) {
                                        if ($cpy + $ry < $chromaH && $cpx + $rx < $chromaW) {
                                            $chromaPlane[$cpy + $ry][$cpx + $rx] = max(0, min(255, $chromaPlane[$cpy + $ry][$cpx + $rx] + $residual[$ry][$rx]));
                                        }
                                    }
                                }
                            }
                        }
                        unset($chromaPlane);
                    }
                } catch (\Throwable $e) {
                    break 2;
                }
            }
        }

        $image = imagecreatetruecolor($width, $height);
        if ($image === false) {
            throw new Exception('Failed to create GD image.');
        }

        for ($row = 0; $row < $height; $row++) {
            for ($col = 0; $col < $width; $col++) {
                $y = $yPlane[$row][$col] ?? 128;
                $u = $uPlane[$row >> 1][$col >> 1] ?? 128;
                $v = $vPlane[$row >> 1][$col >> 1] ?? 128;

                $c = $y - 16;
                $d = $u - 128;
                $e = $v - 128;
                $r = max(0, min(255, (int) round((298 * $c + 409 * $e + 128) >> 8)));
                $g = max(0, min(255, (int) round((298 * $c - 100 * $d - 208 * $e + 128) >> 8)));
                $b = max(0, min(255, (int) round((298 * $c + 516 * $d + 128) >> 8)));

                imagesetpixel($image, $col, $row, ($r << 16) | ($g << 8) | $b);
            }
        }

        return $image;
    }

    /**
     * Parse an AVC Decoder Configuration Record (avcC box payload).
     * Extracts SPS/PPS NAL units and the NAL unit length size used in samples.
     *
     * @param string $data Raw avcC payload bytes.
     * @return array{nal_length_size: int, sps: string[], pps: string[]}
     */
    private static function parseAvcC(string $data): array
    {
        if (strlen($data) < 7) {
            return ['nal_length_size' => 4, 'sps' => [], 'pps' => []];
        }

        $offset = 4;
        $nalLengthSize = (ord($data[$offset++]) & 0x03) + 1;

        $numSps = ord($data[$offset++]) & 0x1F;
        $spsNalUnits = [];
        for ($i = 0; $i < $numSps && $offset + 2 <= strlen($data); $i++) {
            $spsLen = BinaryHandler::readUint16($data, $offset);
            $offset += 2;
            if ($offset + $spsLen <= strlen($data)) {
                $spsNalUnits[] = substr($data, $offset, $spsLen);
                $offset += $spsLen;
            }
        }

        $numPps = ($offset < strlen($data)) ? ord($data[$offset++]) : 0;
        $ppsNalUnits = [];
        for ($i = 0; $i < $numPps && $offset + 2 <= strlen($data); $i++) {
            $ppsLen = BinaryHandler::readUint16($data, $offset);
            $offset += 2;
            if ($offset + $ppsLen <= strlen($data)) {
                $ppsNalUnits[] = substr($data, $offset, $ppsLen);
                $offset += $ppsLen;
            }
        }

        return [
            'nal_length_size' => $nalLengthSize,
            'sps' => $spsNalUnits,
            'pps' => $ppsNalUnits,
        ];
    }

    /**
     * Parse an HEVC Decoder Configuration Record (hvcC box payload).
     * Extracts VPS, SPS, PPS NAL units and the NAL unit length size.
     *
     * @param string $data Raw hvcC payload bytes.
     * @return array{nal_length_size: int, sps: string[], pps: string[]}
     */
    private static function parseHvcC(string $data): array
    {
        if (strlen($data) < 23) {
            return ['nal_length_size' => 4, 'sps' => [], 'pps' => []];
        }

        $nalLengthSize = (ord($data[21]) & 0x03) + 1;
        $numOfArrays = ord($data[22]);
        $offset = 23;

        $parameterNals = [];
        $ppsNals = [];

        for ($i = 0; $i < $numOfArrays && $offset + 3 <= strlen($data); $i++) {
            $nalType = ord($data[$offset]) & 0x3F;
            $offset++;
            $numNalus = BinaryHandler::readUint16($data, $offset);
            $offset += 2;

            for ($j = 0; $j < $numNalus && $offset + 2 <= strlen($data); $j++) {
                $naluLen = BinaryHandler::readUint16($data, $offset);
                $offset += 2;
                if ($offset + $naluLen <= strlen($data)) {
                    $naluData = substr($data, $offset, $naluLen);
                    $offset += $naluLen;
                    if ($nalType === 34) {
                        $ppsNals[] = $naluData;
                    } else {
                        $parameterNals[] = $naluData;
                    }
                }
            }
        }

        return [
            'nal_length_size' => $nalLengthSize,
            'sps' => $parameterNals,
            'pps' => $ppsNals,
        ];
    }

    /**
     * Convert a sample's NAL units from MP4 length-prefixed format to Annex B
     * byte stream format, prepending parameter set NAL units (SPS, PPS) with
     * four-byte start codes (0x00000001).
     *
     * @param string $sampleData Raw sample data from the MP4 file.
     * @param int $nalLengthSize Number of bytes used for NAL unit length prefix (1-4).
     * @param string[] $spsNals SPS (and VPS for HEVC) NAL units to prepend.
     * @param string[] $ppsNals PPS NAL units to prepend.
     * @return string Annex B byte stream.
     */
    private static function convertSampleToAnnexB(string $sampleData, int $nalLengthSize, array $spsNals, array $ppsNals): string
    {
        $startCode = "\x00\x00\x00\x01";
        $output = '';

        foreach ($spsNals as $sps) {
            $output .= $startCode . $sps;
        }

        foreach ($ppsNals as $pps) {
            $output .= $startCode . $pps;
        }

        $offset = 0;
        $len = strlen($sampleData);
        while ($offset + $nalLengthSize <= $len) {
            $nalLen = 0;
            for ($i = 0; $i < $nalLengthSize; $i++) {
                $nalLen = ($nalLen << 8) | ord($sampleData[$offset + $i]);
            }
            $offset += $nalLengthSize;

            if ($nalLen > 0 && $offset + $nalLen <= $len) {
                $output .= $startCode . substr($sampleData, $offset, $nalLen);
                $offset += $nalLen;
            } else {
                break;
            }
        }

        return $output;
    }

    /**
     * Resolve the decoder configuration record (avcC or hvcC) from a video track.
     *
     * @param array $videoTrack Parsed video track data.
     * @return array{nal_length_size: int, sps: string[], pps: string[]}
     * @throws Exception If no configuration record is found.
     */
    private static function resolveDecoderConfig(array $videoTrack): array
    {
        $codec = $videoTrack['stsd'][0]['codec'] ?? '';
        $hevcCodecs = ['hev1', 'hvc1', 'dvhe', 'dvh1'];

        if (in_array($codec, $hevcCodecs, true)) {
            $data = $videoTrack['stsd'][0]['hvcc_data'] ?? null;
            if ($data === null) {
                throw new Exception('No HEVC decoder configuration (hvcC) found.');
            }
            return self::parseHvcC($data);
        }

        $data = $videoTrack['stsd'][0]['avcc_data'] ?? null;
        if ($data === null) {
            throw new Exception('No AVC decoder configuration (avcC) found.');
        }
        return self::parseAvcC($data);
    }

    /**
     * Save the keyframe nearest to the specified time as an Annex B elementary
     * stream file (.h264 or .h265). The output contains start-code-delimited
     * NAL units with SPS and PPS prepended, suitable for playback in VLC,
     * decoding with libavcodec, or further conversion to image formats.
     *
     * @param string $file Path to the MP4 file.
     * @param float $timeSeconds Time position in seconds.
     * @param string $outputPath Destination file path (.h264 or .h265).
     * @return bool True if saved successfully.
     * @throws Exception If the video track is not H.264/HEVC or extraction fails.
     */
    public static function saveFrameAsAnnexBStream(string $file, float $timeSeconds, string $outputPath): bool
    {
        $tags = self::getMetaTags($file);
        $videoTrack = self::findVideoTrack($tags);

        if ($videoTrack === null) {
            throw new Exception('No video track found in the file.');
        }

        $codec = $videoTrack['stsd'][0]['codec'] ?? '';
        $supportedCodecs = ['avc1', 'avc2', 'avc3', 'avc4', 'hev1', 'hvc1', 'dvhe', 'dvh1'];
        if (!in_array($codec, $supportedCodecs, true)) {
            throw new Exception("Codec '{$codec}' is not H.264 or HEVC. Annex B conversion is not applicable.");
        }

        $config = self::resolveDecoderConfig($videoTrack);

        $frame = self::getFrameData($file, $timeSeconds);
        if ($frame === null || empty($frame['data'])) {
            throw new Exception('Failed to extract frame data at the specified time.');
        }

        $annexB = self::convertSampleToAnnexB($frame['data'], $config['nal_length_size'], $config['sps'], $config['pps']);

        return file_put_contents($outputPath, $annexB) !== false;
    }

    /**
     * Extract the keyframe nearest to the specified time and save it as a
     * self-contained single-frame MP4 file. The output is a fully playable
     * ISO Base Media File that any video player or browser can render.
     *
     * @param string $file Path to the source MP4 file.
     * @param float $timeSeconds Time position in seconds.
     * @param string $outputPath Destination file path (.mp4).
     * @return bool True if saved successfully.
     * @throws Exception If no video track found or extraction fails.
     */
    public static function saveFrameAsStandaloneVideo(string $file, float $timeSeconds, string $outputPath): bool
    {
        $tags = self::getMetaTags($file);
        $videoTrack = self::findVideoTrack($tags);

        if ($videoTrack === null) {
            throw new Exception('No video track found in the file.');
        }

        $frame = self::getFrameData($file, $timeSeconds);
        if ($frame === null || empty($frame['data'])) {
            throw new Exception('Failed to extract frame data at the specified time.');
        }

        $sampleData = $frame['data'];
        $sampleSize = strlen($sampleData);
        $timescale = $videoTrack['mdhd']['timescale'] ?? 90000;

        $singleFrameTrack = $videoTrack;
        $singleFrameTrack['stts'] = [
            'entries' => [['sample_count' => 1, 'sample_delta' => $timescale]],
            'total_samples' => 1,
            'total_delta' => $timescale,
        ];
        $singleFrameTrack['mdhd'] = array_merge($videoTrack['mdhd'] ?? [], [
            'timescale' => $timescale,
            'duration' => $timescale,
            'duration_seconds' => 1.0,
        ]);
        $singleFrameTrack['tkhd'] = array_merge($videoTrack['tkhd'] ?? [], [
            'duration' => $tags['meta']['timescale'] ?? $timescale,
        ]);
        unset($singleFrameTrack['ctts']);
        unset($singleFrameTrack['stss']);

        $ftyp = self::writeBox('ftyp', 'isom' . BinaryHandler::writeUint32(0x200) . 'isomiso2mp41');
        $mdatDataOffset = strlen($ftyp) + 8;

        $moov = self::buildVideoMoov($tags, $singleFrameTrack, $mdatDataOffset, [$sampleSize], [1]);

        $dstFh = fopen($outputPath, 'wb');
        if ($dstFh === false) {
            throw new Exception("Cannot open output file: {$outputPath}");
        }

        fwrite($dstFh, $ftyp);
        fwrite($dstFh, BinaryHandler::writeUint32(8 + $sampleSize));
        fwrite($dstFh, 'mdat');
        fwrite($dstFh, $sampleData);
        fwrite($dstFh, $moov);
        fclose($dstFh);

        return true;
    }

    /**
     * Read the variable-length descriptor size used in MPEG-4 descriptor structures.
     *
     * @param string $data Binary data.
     * @param int &$offset Current read offset (advanced past the length bytes).
     * @return int Decoded length value.
     */
    private static function readDescriptorLength(string $data, int &$offset): int
    {
        $length = 0;
        for ($i = 0; $i < 4 && $offset < strlen($data); $i++) {
            $byte = ord($data[$offset++]);
            $length = ($length << 7) | ($byte & 0x7F);
            if (($byte & 0x80) === 0) {
                break;
            }
        }

        return $length;
    }

    /**
     * Parse the AudioSpecificConfig from raw ES_Descriptor payload data.
     * Locates the DecoderSpecificInfo descriptor (tag 0x05) and extracts
     * audioObjectType, samplingFrequencyIndex, and channelConfiguration.
     *
     * @param string $esdsData Raw ES_Descriptor bytes (after version/flags of esds box).
     * @return array{object_type: int, frequency_index: int, channel_config: int}|null
     */
    private static function parseAudioSpecificConfig(string $esdsData): ?array
    {
        $offset = 0;
        $len = strlen($esdsData);

        while ($offset < $len) {
            if ($offset >= $len) {
                return null;
            }
            $tag = ord($esdsData[$offset++]);
            $descLen = self::readDescriptorLength($esdsData, $offset);

            if ($tag === 0x05 && $offset + 2 <= $len) {
                $byte0 = ord($esdsData[$offset]);
                $byte1 = ord($esdsData[$offset + 1]);
                $objectType = ($byte0 >> 3) & 0x1F;
                $freqIndex = (($byte0 & 0x07) << 1) | (($byte1 >> 7) & 0x01);
                $channelConfig = ($byte1 >> 3) & 0x0F;

                return [
                    'object_type' => $objectType,
                    'frequency_index' => $freqIndex,
                    'channel_config' => $channelConfig,
                ];
            }

            if ($tag === 0x03 || $tag === 0x04) {
                if ($tag === 0x03 && $offset + 3 <= $len) {
                    $offset += 3;
                }
                continue;
            }

            $offset += $descLen;
        }

        return null;
    }

    /**
     * Map a sample rate in Hz to the MPEG-4 Audio sampling frequency index.
     *
     * @param int $sampleRate Sample rate in Hz.
     * @return int Frequency index (0-12), or 15 if not in the standard table.
     */
    private static function getSamplingFrequencyIndex(int $sampleRate): int
    {
        $table = [
            96000 => 0,
            88200 => 1,
            64000 => 2,
            48000 => 3,
            44100 => 4,
            32000 => 5,
            24000 => 6,
            22050 => 7,
            16000 => 8,
            12000 => 9,
            11025 => 10,
            8000 => 11,
            7350 => 12
        ];

        return $table[$sampleRate] ?? 15;
    }

    /**
     * Build an ADTS frame header (7 bytes, no CRC) prepended to an AAC access unit.
     *
     * @param int $objectType MPEG-4 Audio Object Type (e.g. 2 for AAC-LC).
     * @param int $freqIndex Sampling frequency index (0-12).
     * @param int $channelConfig Channel configuration (1-7).
     * @param int $frameLength Byte length of the AAC access unit (excluding ADTS header).
     * @return string 7-byte ADTS header.
     */
    private static function buildAdtsHeader(int $objectType, int $freqIndex, int $channelConfig, int $frameLength): string
    {
        $adtsProfile = $objectType - 1;
        $totalLength = 7 + $frameLength;

        $header = '';
        $header .= chr(0xFF);
        $header .= chr(0xF1);
        $header .= chr((($adtsProfile & 0x03) << 6) | (($freqIndex & 0x0F) << 2) | (($channelConfig >> 2) & 0x01));
        $header .= chr((($channelConfig & 0x03) << 6) | (($totalLength >> 11) & 0x03));
        $header .= chr(($totalLength >> 3) & 0xFF);
        $header .= chr((($totalLength & 0x07) << 5) | 0x1F);
        $header .= chr(0xFC);

        return $header;
    }

    /**
     * Iterate through all samples of a track and invoke a callback for each sample's
     * file offset and byte size, resolved via the stsc, stco, and stsz tables.
     *
     * @param array $stsc Parsed stsc entries.
     * @param array $stco Parsed chunk offset array.
     * @param array $stsz Parsed stsz data.
     * @param callable $callback function(int $sampleIndex, int $fileOffset, int $sampleSize): void
     * @return void
     */
    private static function iterateSamples(array $stsc, array $stco, array $stsz, callable $callback): void
    {
        $sampleIndex = 0;
        $totalSamples = $stsz['sample_count'];
        $chunkCount = count($stco);
        $stscCount = count($stsc);

        for ($i = 0; $i < $stscCount && $sampleIndex < $totalSamples; $i++) {
            $firstChunk = $stsc[$i]['first_chunk'];
            $samplesPerChunk = $stsc[$i]['samples_per_chunk'];
            $lastChunk = ($i + 1 < $stscCount) ? $stsc[$i + 1]['first_chunk'] - 1 : $chunkCount;

            for ($chunk = $firstChunk; $chunk <= $lastChunk && $sampleIndex < $totalSamples; $chunk++) {
                $chunkOffset = $stco[$chunk - 1] ?? 0;
                $offsetInChunk = 0;

                for ($s = 0; $s < $samplesPerChunk && $sampleIndex < $totalSamples; $s++) {
                    $size = ($stsz['sample_size'] > 0) ? $stsz['sample_size'] : ($stsz['entries'][$sampleIndex] ?? 0);
                    $callback($sampleIndex, $chunkOffset + $offsetInChunk, $size);

                    $offsetInChunk += $size;
                    $sampleIndex++;
                }
            }
        }
    }

    /**
     * Find the first audio track from parsed metadata.
     *
     * @param array $tags Parsed metadata from getMetaTags().
     * @return array|null The audio track data, or null if none found.
     */
    private static function findAudioTrack(array $tags): ?array
    {
        foreach ($tags['meta']['tracks'] ?? [] as $track) {
            if (($track['handler_type'] ?? '') === 'soun') {
                return $track;
            }
        }

        return null;
    }

    /**
     * Extract the audio track from an MP4 file and save it to the specified path.
     * For AAC audio, produces an ADTS-wrapped .aac stream.
     * For MP3 and AC-3, produces raw frame data.
     * Throws an exception if no audio track exists or the codec is unsupported.
     *
     * @param string $file Path to the MP4 file.
     * @param string $outputPath Destination file path.
     * @return void
     * @throws Exception If no audio track found or codec is unsupported for raw extraction.
     */
    public static function extractAudio(string $file, string $outputPath): void
    {
        $tags = self::getMetaTags($file);
        $audioTrack = self::findAudioTrack($tags);

        if ($audioTrack === null) {
            throw new Exception('No audio track found in the file.');
        }

        $stsc = $audioTrack['stsc'] ?? null;
        $stco = $audioTrack['stco'] ?? null;
        $stsz = $audioTrack['stsz'] ?? null;

        if ($stsc === null || $stco === null || $stsz === null) {
            throw new Exception('Audio track sample table is incomplete.');
        }

        $codec = $audioTrack['stsd'][0]['codec'] ?? '';
        $sampleRate = $audioTrack['stsd'][0]['sample_rate'] ?? 44100;
        $numChannels = $audioTrack['stsd'][0]['num_channels'] ?? 2;

        $isAac = ($codec === 'mp4a');
        $isRawStreamable = in_array($codec, ['.mp3', 'ac-3', 'ec-3'], true);

        if (!$isAac && !$isRawStreamable) {
            throw new Exception("Audio codec '{$codec}' is not supported for raw extraction.");
        }

        $objectType = 2;
        $freqIndex = self::getSamplingFrequencyIndex($sampleRate);
        $channelConfig = $numChannels;

        if ($isAac && isset($audioTrack['stsd'][0]['esds_data'])) {
            $asc = self::parseAudioSpecificConfig($audioTrack['stsd'][0]['esds_data']);
            if ($asc !== null) {
                $objectType = $asc['object_type'];
                $freqIndex = $asc['frequency_index'];
                $channelConfig = $asc['channel_config'];
            }
        }

        $srcFh = fopen($file, 'rb');
        if ($srcFh === false) {
            throw new Exception("Cannot open source file: {$file}");
        }

        $dstFh = fopen($outputPath, 'wb');
        if ($dstFh === false) {
            fclose($srcFh);
            throw new Exception("Cannot open output file: {$outputPath}");
        }

        self::iterateSamples($stsc, $stco, $stsz, function (int $idx, int $offset, int $size) use ($srcFh, $dstFh, $isAac, $objectType, $freqIndex, $channelConfig) {
            if ($size <= 0) {
                return;
            }

            fseek($srcFh, $offset);
            $sampleData = fread($srcFh, $size);

            if ($sampleData === false || strlen($sampleData) === 0) {
                return;
            }

            if ($isAac) {
                fwrite($dstFh, self::buildAdtsHeader($objectType, $freqIndex, $channelConfig, strlen($sampleData)));
            }

            fwrite($dstFh, $sampleData);
        });

        fclose($srcFh);
        fclose($dstFh);
    }

    /**
     * Build a 3x3 identity transformation matrix (36 bytes) for MP4 box structures.
     *
     * @return string 36-byte identity matrix.
     */
    private static function buildIdentityMatrix(): string
    {
        $matrix = BinaryHandler::writeUint32(0x00010000) . str_repeat("\x00", 8);
        $matrix .= str_repeat("\x00", 4) . BinaryHandler::writeUint32(0x00010000) . str_repeat("\x00", 4);
        $matrix .= str_repeat("\x00", 8) . BinaryHandler::writeUint32(0x40000000);

        return $matrix;
    }

    /**
     * Build a complete moov box containing a single video track with the given sample table data.
     *
     * @param array $tags Full parsed metadata from getMetaTags().
     * @param array $videoTrack Parsed video track data.
     * @param int $mdatDataOffset File offset where mdat payload begins in the output file.
     * @param int[] $sampleSizes Ordered array of sample byte sizes.
     * @param int[] $syncSamples 1-based sync sample numbers (empty if all are sync).
     * @return string Complete moov box binary data.
     */
    private static function buildVideoMoov(array $tags, array $videoTrack, int $mdatDataOffset, array $sampleSizes, array $syncSamples): string
    {
        $timescale = $videoTrack['mdhd']['timescale'] ?? 90000;
        $mediaDuration = $videoTrack['mdhd']['duration'] ?? 0;
        $movieTimescale = $tags['meta']['timescale'] ?? $timescale;
        $mediaDurationSec = ($timescale > 0) ? $mediaDuration / $timescale : 0;
        $movieDuration = (int) round($mediaDurationSec * $movieTimescale);
        $width = $videoTrack['tkhd']['width'] ?? ($videoTrack['stsd'][0]['width'] ?? 0);
        $height = $videoTrack['tkhd']['height'] ?? ($videoTrack['stsd'][0]['height'] ?? 0);
        $totalSamples = count($sampleSizes);

        // mvhd (version 0)
        $mvhdData = BinaryHandler::writeUint32(0) . BinaryHandler::writeUint32(0);
        $mvhdData .= BinaryHandler::writeUint32($movieTimescale);
        $mvhdData .= BinaryHandler::writeUint32($movieDuration);
        $mvhdData .= BinaryHandler::writeUint32(0x00010000);
        $mvhdData .= BinaryHandler::writeUint16(0x0100);
        $mvhdData .= str_repeat("\x00", 10);
        $mvhdData .= self::buildIdentityMatrix();
        $mvhdData .= str_repeat("\x00", 24);
        $mvhdData .= BinaryHandler::writeUint32(2);
        $mvhd = self::writeFullBox('mvhd', 0, 0, $mvhdData);

        // tkhd (version 0, flags = enabled + in_movie)
        $tkhdData = BinaryHandler::writeUint32(0) . BinaryHandler::writeUint32(0);
        $tkhdData .= BinaryHandler::writeUint32(1);
        $tkhdData .= str_repeat("\x00", 4);
        $tkhdData .= BinaryHandler::writeUint32($movieDuration);
        $tkhdData .= str_repeat("\x00", 8);
        $tkhdData .= BinaryHandler::writeUint16(0);
        $tkhdData .= BinaryHandler::writeUint16(0);
        $tkhdData .= BinaryHandler::writeUint16(0);
        $tkhdData .= str_repeat("\x00", 2);
        $tkhdData .= self::buildIdentityMatrix();
        $tkhdData .= BinaryHandler::writeUint32($width << 16);
        $tkhdData .= BinaryHandler::writeUint32($height << 16);
        $tkhd = self::writeFullBox('tkhd', 0, 3, $tkhdData);

        // mdhd (version 0)
        $mdhdData = BinaryHandler::writeUint32(0) . BinaryHandler::writeUint32(0);
        $mdhdData .= BinaryHandler::writeUint32($timescale);
        $mdhdData .= BinaryHandler::writeUint32((int) $mediaDuration);
        $mdhdData .= BinaryHandler::writeUint16(0x55C4);
        $mdhdData .= BinaryHandler::writeUint16(0);
        $mdhd = self::writeFullBox('mdhd', 0, 0, $mdhdData);

        // hdlr
        $hdlrData = str_repeat("\x00", 4) . 'vide' . str_repeat("\x00", 12) . "VideoHandler\x00";
        $hdlr = self::writeFullBox('hdlr', 0, 0, $hdlrData);

        // vmhd
        $vmhd = self::writeFullBox('vmhd', 0, 1, str_repeat("\x00", 8));

        // dinf → dref → url
        $urlBox = self::writeFullBox("url ", 0, 1, '');
        $drefData = BinaryHandler::writeUint32(1) . $urlBox;
        $dref = self::writeFullBox('dref', 0, 0, $drefData);
        $dinf = self::writeBox('dinf', $dref);

        // stsd (raw copy from original)
        $stsdRaw = $videoTrack['stsd_raw'] ?? '';
        $stsd = self::writeBox('stsd', $stsdRaw);

        // stts (rebuild from parsed entries)
        $sttsEntries = $videoTrack['stts']['entries'] ?? [];
        $sttsData = BinaryHandler::writeUint32(count($sttsEntries));
        foreach ($sttsEntries as $entry) {
            $sttsData .= BinaryHandler::writeUint32($entry['sample_count']);
            $sttsData .= BinaryHandler::writeUint32($entry['sample_delta']);
        }
        $stts = self::writeFullBox('stts', 0, 0, $sttsData);

        // stss (sync samples, omit if empty = all sync)
        $stssBox = '';
        if (!empty($syncSamples)) {
            $stssData = BinaryHandler::writeUint32(count($syncSamples));
            foreach ($syncSamples as $ss) {
                $stssData .= BinaryHandler::writeUint32($ss);
            }
            $stssBox = self::writeFullBox('stss', 0, 0, $stssData);
        }

        // ctts (composition time offsets, optional)
        $cttsBox = '';
        if (!empty($videoTrack['ctts'])) {
            $cttsEntries = $videoTrack['ctts'];
            $cttsData = BinaryHandler::writeUint32(count($cttsEntries));
            foreach ($cttsEntries as $entry) {
                $cttsData .= BinaryHandler::writeUint32($entry['sample_count']);
                $cttsData .= BinaryHandler::writeUint32($entry['sample_offset'] & 0xFFFFFFFF);
            }
            $cttsBox = self::writeFullBox('ctts', 0, 0, $cttsData);
        }

        // stsc (single chunk containing all samples)
        $stscData = BinaryHandler::writeUint32(1);
        $stscData .= BinaryHandler::writeUint32(1);
        $stscData .= BinaryHandler::writeUint32($totalSamples);
        $stscData .= BinaryHandler::writeUint32(1);
        $stsc = self::writeFullBox('stsc', 0, 0, $stscData);

        // stsz
        $stszData = BinaryHandler::writeUint32(0);
        $stszData .= BinaryHandler::writeUint32($totalSamples);
        foreach ($sampleSizes as $sz) {
            $stszData .= BinaryHandler::writeUint32($sz);
        }
        $stsz = self::writeFullBox('stsz', 0, 0, $stszData);

        // stco (single chunk offset)
        $stcoData = BinaryHandler::writeUint32(1);
        $stcoData .= BinaryHandler::writeUint32($mdatDataOffset);
        $stco = self::writeFullBox('stco', 0, 0, $stcoData);

        $stbl = self::writeBox('stbl', $stsd . $stts . $stssBox . $cttsBox . $stsc . $stsz . $stco);
        $minf = self::writeBox('minf', $vmhd . $dinf . $stbl);
        $mdia = self::writeBox('mdia', $mdhd . $hdlr . $minf);
        $trak = self::writeBox('trak', $tkhd . $mdia);
        $moov = self::writeBox('moov', $mvhd . $trak);

        return $moov;
    }

    /**
     * Extract the video track from an MP4 file and save it as a new MP4 file
     * containing only the video stream (no audio). The output is a valid
     * ISO Base Media File with ftyp, mdat, and moov boxes.
     *
     * @param string $file Path to the source MP4 file.
     * @param string $outputPath Destination file path.
     * @return void
     * @throws Exception If no video track found or sample table is incomplete.
     */
    public static function extractVideo(string $file, string $outputPath): void
    {
        $tags = self::getMetaTags($file);
        $videoTrack = self::findVideoTrack($tags);

        if ($videoTrack === null) {
            throw new Exception('No video track found in the file.');
        }

        $stsc = $videoTrack['stsc'] ?? null;
        $stco = $videoTrack['stco'] ?? null;
        $stsz = $videoTrack['stsz'] ?? null;

        if ($stsc === null || $stco === null || $stsz === null) {
            throw new Exception('Video track sample table is incomplete.');
        }

        $ftyp = self::writeBox('ftyp', 'isom' . BinaryHandler::writeUint32(0x200) . 'isomiso2mp41');
        $ftypSize = strlen($ftyp);

        $sampleSizes = [];
        $totalMdatPayload = 0;
        self::iterateSamples($stsc, $stco, $stsz, function (int $idx, int $offset, int $size) use (&$sampleSizes, &$totalMdatPayload) {
            $sampleSizes[] = $size;
            $totalMdatPayload += $size;
        });

        $mdatHeaderSize = 8;
        if ($totalMdatPayload + 8 > 0xFFFFFFFF) {
            $mdatHeaderSize = 16;
        }
        $mdatDataOffset = $ftypSize + $mdatHeaderSize;

        $syncSamples = $videoTrack['stss'] ?? [];

        $srcFh = fopen($file, 'rb');
        if ($srcFh === false) {
            throw new Exception("Cannot open source file: {$file}");
        }

        $dstFh = fopen($outputPath, 'wb');
        if ($dstFh === false) {
            fclose($srcFh);
            throw new Exception("Cannot open output file: {$outputPath}");
        }

        fwrite($dstFh, $ftyp);

        if ($mdatHeaderSize === 16) {
            fwrite($dstFh, BinaryHandler::writeUint32(1));
            fwrite($dstFh, 'mdat');
            $totalMdatSize = 16 + $totalMdatPayload;
            fwrite($dstFh, BinaryHandler::writeUint32(($totalMdatSize >> 32) & 0xFFFFFFFF));
            fwrite($dstFh, BinaryHandler::writeUint32($totalMdatSize & 0xFFFFFFFF));
        } else {
            fwrite($dstFh, BinaryHandler::writeUint32(8 + $totalMdatPayload));
            fwrite($dstFh, 'mdat');
        }

        self::iterateSamples($stsc, $stco, $stsz, function (int $idx, int $offset, int $size) use ($srcFh, $dstFh) {
            if ($size <= 0) {
                return;
            }

            fseek($srcFh, $offset);
            $remaining = $size;
            while ($remaining > 0) {
                $chunkSize = min($remaining, 65536);
                $data = fread($srcFh, $chunkSize);
                if ($data === false || strlen($data) === 0) {
                    break;
                }
                fwrite($dstFh, $data);
                $remaining -= strlen($data);
            }
        });

        fclose($srcFh);

        $moov = self::buildVideoMoov($tags, $videoTrack, $mdatDataOffset, $sampleSizes, $syncSamples);
        fwrite($dstFh, $moov);
        fclose($dstFh);
    }

    /**
     * Retrieve basic video/audio information and standard tags from the file.
     *
     * @param string $file Path to the file.
     * @return array{
     *  album: mixed, 
     *  artist: mixed, 
     *  audio_channels: mixed, 
     *  audio_codec: mixed, 
     *  audio_sample_rate: mixed, 
     *  comment: mixed, 
     *  duration: mixed, 
     *  duration_human: mixed, 
     *  file_type: mixed, 
     *  genre: mixed, 
     *  has_album_art: bool, 
     *  overall_bitrate: mixed, 
     *  title: mixed, 
     *  video_codec: mixed, 
     *  video_frame_rate: mixed, 
     *  video_height: mixed, 
     *  video_width: mixed, 
     *  year: mixed
     * } Array consisting of standard metadata fields.
     */
    public static function getBasicInfo(string $file): array
    {
        $tags = self::getMetaTags($file);
        $meta = $tags['meta'];
        $itunes = $meta['itunes'] ?? [];

        return [
            'title' => $itunes['title'] ?? null,
            'artist' => $itunes['artist'] ?? null,
            'album' => $itunes['album'] ?? null,
            'year' => $itunes['year'] ?? null,
            'comment' => $itunes['comment'] ?? null,
            'genre' => $itunes['genre'] ?? null,
            'duration' => $meta['duration'] ?? null,
            'duration_human' => $meta['duration_human'] ?? null,
            'overall_bitrate' => $meta['overall_bitrate'] ?? null,
            'video_codec' => $meta['video']['codec_name'] ?? null,
            'video_width' => $meta['video']['width'] ?? null,
            'video_height' => $meta['video']['height'] ?? null,
            'video_frame_rate' => $meta['video']['frame_rate'] ?? null,
            'audio_codec' => $meta['audio']['codec_name'] ?? null,
            'audio_channels' => $meta['audio']['num_channels'] ?? null,
            'audio_sample_rate' => $meta['audio']['sample_rate'] ?? null,
            'has_album_art' => $tags['apicFound'],
            'file_type' => $meta['ftyp']['major_brand_name'] ?? null,
        ];
    }
}
