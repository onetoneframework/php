<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Audio;

#region use

use Clover\Classes\BaseClass;
use Clover\Classes\Data\BinaryHandler;
use Exception;
use function strlen;
use function substr;
use function sprintf;

#endregion

/*
 * Class WavParser
 *
 * This class provides functionality to parse WAV files, extract metadata, and analyze audio properties. It includes methods to validate WAV files, read RIFF headers, parse format chunks, and retrieve standard tags such as title, artist, genre, and duration.
 */
class WavParser extends BaseClass
{
    #region properties

    // Map of audio format codes to their corresponding format names based on the WAV specification.
    public static array $audioFormats = [
        0x0000 => 'Unknown',
        0x0001 => 'PCM',
        0x0002 => 'Microsoft ADPCM',
        0x0003 => 'IEEE Float',
        0x0004 => 'Compaq VSELP',
        0x0005 => 'IBM CSVD',
        0x0006 => 'A-law',
        0x0007 => 'mu-law',
        0x0008 => 'Microsoft DTS',
        0x0009 => 'DRM',
        0x000A => 'WMA 9 Speech',
        0x000B => 'Microsoft Windows Media RT Voice',
        0x0010 => 'OKI ADPCM',
        0x0011 => 'Intel IMA/DVI ADPCM',
        0x0012 => 'Videologic Mediaspace ADPCM',
        0x0013 => 'Sierra ADPCM',
        0x0014 => 'Antex G.723 ADPCM',
        0x0015 => 'DSP Solutions DIGISTD',
        0x0016 => 'DSP Solutions DIGIFIX',
        0x0017 => 'Dialogic OKI ADPCM',
        0x0018 => 'HP CU',
        0x0020 => 'Yamaha ADPCM',
        0x0021 => 'Speech Compression Sonarc',
        0x0022 => 'DSP Group TrueSpeech',
        0x0023 => 'Echo Speech EchoSC1',
        0x0024 => 'Audiofile AF36',
        0x0025 => 'Audio Processing Technology APTX',
        0x0026 => 'Audiofile AF10',
        0x0027 => 'Prosody 1612',
        0x0028 => 'LRC',
        0x0030 => 'Dolby AC2',
        0x0031 => 'Microsoft GSM 6.10',
        0x0032 => 'MSNAudio',
        0x0033 => 'Antex ADPCME',
        0x0034 => 'Control Resources VQLPC',
        0x0035 => 'DSP Solutions DIGIREAL',
        0x0036 => 'DSP Solutions DIGIADPCM',
        0x0037 => 'Control Res CR10',
        0x0038 => 'NMS VBXADPCM',
        0x0039 => 'CS IMA ADPCM (Roland RDAC)',
        0x003A => 'Echo Speech EchoSC3',
        0x003B => 'Rockwell ADPCM',
        0x003C => 'Rockwell DIGITALK',
        0x003D => 'Xebec Multimedia',
        0x0040 => 'Antex G.721 ADPCM',
        0x0041 => 'Antex G.728 CELP',
        0x0042 => 'Intel G.723',
        0x0043 => 'Intel G.723.1',
        0x0044 => 'Intel G.729',
        0x0045 => 'Sharp G.726',
        0x0050 => 'MPEG',
        0x0052 => 'RT24',
        0x0053 => 'PAC',
        0x0055 => 'MPEG Layer 3',
        0x0059 => 'Lucent G.723',
        0x0060 => 'Cirrus',
        0x0061 => 'ESPCM',
        0x0062 => 'Voxware',
        0x0063 => 'Canopus Atrac',
        0x0064 => 'G.726 ADPCM',
        0x0065 => 'G.722 ADPCM',
        0x0066 => 'DSAT',
        0x0067 => 'DSAT Display',
        0x0069 => 'Voxware Byte Aligned',
        0x0070 => 'Voxware AC8',
        0x0071 => 'Voxware AC10',
        0x0072 => 'Voxware AC16',
        0x0073 => 'Voxware AC20',
        0x0074 => 'Voxware MetaVoice',
        0x0075 => 'Voxware MetaSound',
        0x0076 => 'Voxware RT29HW',
        0x0077 => 'Voxware VR12',
        0x0078 => 'Voxware VR18',
        0x0079 => 'Voxware TQ40',
        0x0080 => 'Softsound',
        0x0081 => 'Voxware TQ60',
        0x0082 => 'MSRT24',
        0x0083 => 'G.729A',
        0x0084 => 'MVI MV12',
        0x0085 => 'DF G.726',
        0x0086 => 'DF GSM610',
        0x0088 => 'ISIAudio',
        0x0089 => 'Onlive',
        0x0091 => 'SBC24',
        0x0092 => 'Dolby AC3 SPDIF',
        0x0097 => 'ZyXEL ADPCM',
        0x0098 => 'Philips LPCBB',
        0x0099 => 'Packed',
        0x0100 => 'Rhetorex ADPCM',
        0x0101 => 'BeCubed Software\'s IRAT',
        0x0111 => 'Vivo G.723',
        0x0112 => 'Vivo Siren',
        0x0113 => 'Digital G.723',
        0x0200 => 'Creative ADPCM',
        0x0202 => 'Creative FastSpeech8',
        0x0203 => 'Creative FastSpeech10',
        0x0220 => 'Quarterdeck',
        0x0300 => 'FM Towns Snd',
        0x0400 => 'BTV Digital',
        0x0680 => 'VME VMPCM',
        0x1000 => 'OLIGSM',
        0x1001 => 'OLIADPCM',
        0x1002 => 'OLICELP',
        0x1003 => 'OLISBC',
        0x1004 => 'OLIOPR',
        0x1100 => 'LH Codec',
        0x1400 => 'Norris',
        0x1401 => 'ISIAudio',
        0x1500 => 'Soundspace Music Compression',
        0x2000 => 'AC3 DVM',
    ];

    // Map of INFO chunk IDs to their corresponding descriptions based on the WAV specification.
    public static array $infoChunkMap = [
        'IARL' => 'Archival location',
        'IART' => 'Artist',
        'ICMS' => 'Commissioned',
        'ICMT' => 'Comment',
        'ICOP' => 'Copyright',
        'ICRD' => 'Creation date',
        'ICRP' => 'Cropped',
        'IDIM' => 'Dimensions',
        'IDPI' => 'Dots per inch',
        'IENG' => 'Engineer',
        'IGNR' => 'Genre',
        'IKEY' => 'Keywords',
        'ILGT' => 'Lightness',
        'IMED' => 'Medium',
        'INAM' => 'Title',
        'IPLT' => 'Palette setting',
        'IPRD' => 'Product',
        'ISBJ' => 'Subject',
        'ISFT' => 'Software',
        'ISHP' => 'Sharpness',
        'ISRC' => 'Source',
        'ISRF' => 'Source form',
        'ITCH' => 'Technician',
        'ITRK' => 'Track number',
        'TURL' => 'URL',
    ];

    #endregion

    #region function

    /**
     * Check if a given file is a valid WAV file.
     *
     * @param string $file Path to the file.
     * @return bool True if valid, false otherwise.
     */
    public static function isValidWav(string $file): bool
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

        if (strlen($header) < 12) {
            return false;
        }

        return substr($header, 0, 4) === 'RIFF' && substr($header, 8, 4) === 'WAVE';
    }

    /**
     * Parse the RIFF header of a WAV file.
     *
     * @param string $header 12-byte header data.
     * @return array{
     *  chunk_id: string, 
     *  chunk_size: int, 
     *  format: string
     * } Array containing chunk ID, chunk size, and format.
     */
    public static function parseRiffHeader(string $header): array
    {
        return [
            'chunk_id' => substr($header, 0, 4),
            'chunk_size' => BinaryHandler::readLittleEndianUint32($header, 4),
            'format' => substr($header, 8, 4),
        ];
    }

    /**
     * Parse the 'fmt ' chunk of a WAV file.
     *
     * @param string $data Data of the 'fmt ' chunk.
     * @return array{
     *  audio_format: int, 
     *  audio_format_name: string, 
     *  num_channels: int,
     *  sample_rate: int,
     *  byte_rate: int,
     *  block_align: int,
     *  bits_per_sample: int,
     *  extra_param_size: int|null,
     *  extra_params: string|null,
     *  valid_bits_per_sample: int|null,
     *  channel_mask: int|null,
     *  sub_format: int|null,
     *  sub_format_name: string|null
     * } Array containing format details.
     */
    public static function parseFmtChunk(string $data): array
    {
        $audioFormat = BinaryHandler::readLittleEndianUint16($data, 0);
        $numChannels = BinaryHandler::readLittleEndianUint16($data, 2);
        $sampleRate = BinaryHandler::readLittleEndianUint32($data, 4);
        $byteRate = BinaryHandler::readLittleEndianUint32($data, 8);
        $blockAlign = BinaryHandler::readLittleEndianUint16($data, 12);
        $bitsPerSample = BinaryHandler::readLittleEndianUint16($data, 14);

        $result = [
            'audio_format' => $audioFormat,
            'audio_format_name' => self::$audioFormats[$audioFormat] ?? 'Unknown',
            'num_channels' => $numChannels,
            'sample_rate' => $sampleRate,
            'byte_rate' => $byteRate,
            'block_align' => $blockAlign,
            'bits_per_sample' => $bitsPerSample,
        ];

        if (strlen($data) >= 18) {
            $result['extra_param_size'] = BinaryHandler::readLittleEndianUint16($data, 16);
            if ($result['extra_param_size'] > 0 && strlen($data) >= 18 + $result['extra_param_size']) {
                $result['extra_params'] = substr($data, 18, $result['extra_param_size']);
            }
        }

        if ($audioFormat === 0xFFFE && strlen($data) >= 40) {
            $result['valid_bits_per_sample'] = BinaryHandler::readLittleEndianUint16($data, 18);
            $result['channel_mask'] = BinaryHandler::readLittleEndianUint32($data, 20);
            $subFormatCode = BinaryHandler::readLittleEndianUint16($data, 24);
            $result['sub_format'] = $subFormatCode;
            $result['sub_format_name'] = self::$audioFormats[$subFormatCode] ?? 'Unknown';
        }

        return $result;
    }

    /**
     * Parse INFO list chunks (metadata) of a WAV file.
     *
     * @param resource $fh File handler.
     * @param int $listSize Size of the LIST chunk.
     * @return array{
     *  description: mixed, 
     *  value: string
     * }[] Array of parsed INFO tags.
     */
    public static function parseInfoChunk($fh, int $listSize): array
    {
        $info = [];
        $bytesRead = 0;

        while ($bytesRead < $listSize) {
            $chunkIdData = fread($fh, 4);
            if ($chunkIdData === false || strlen($chunkIdData) < 4) {
                break;
            }
            $bytesRead += 4;

            $chunkSizeData = fread($fh, 4);
            if ($chunkSizeData === false || strlen($chunkSizeData) < 4) {
                break;
            }
            $bytesRead += 4;

            $chunkId = $chunkIdData;
            $chunkSize = BinaryHandler::readLittleEndianUint32($chunkSizeData, 0);

            if ($chunkSize <= 0) {
                break;
            }

            $chunkData = fread($fh, $chunkSize);
            if ($chunkData === false) {
                break;
            }
            $bytesRead += $chunkSize;

            if ($chunkSize % 2 !== 0) {
                fread($fh, 1);
                $bytesRead++;
            }

            $key = self::$infoChunkMap[$chunkId] ?? $chunkId;
            $info[$chunkId] = [
                'value' => rtrim($chunkData, "\0"),
                'description' => $key,
            ];
        }

        return $info;
    }

    /**
     * Parse the 'cue ' chunk of a WAV file.
     *
     * @param string $data Data of the 'cue ' chunk.
     * @return array{
     *  block_start: int, 
     *  chunk_start: int, 
     *  data_chunk_id: string, 
     *  id: int, 
     *  position: int, 
     *  sample_offset: int
     * }[] Array of cue points.
     */
    public static function parseCueChunk(string $data): array
    {
        $numCuePoints = BinaryHandler::readLittleEndianUint32($data, 0);
        $cuePoints = [];
        $offset = 4;

        for ($i = 0; $i < $numCuePoints && $offset + 24 <= strlen($data); $i++) {
            $cuePoints[] = [
                'id' => BinaryHandler::readLittleEndianUint32($data, $offset),
                'position' => BinaryHandler::readLittleEndianUint32($data, $offset + 4),
                'data_chunk_id' => substr($data, $offset + 8, 4),
                'chunk_start' => BinaryHandler::readLittleEndianUint32($data, $offset + 12),
                'block_start' => BinaryHandler::readLittleEndianUint32($data, $offset + 16),
                'sample_offset' => BinaryHandler::readLittleEndianUint32($data, $offset + 20),
            ];
            $offset += 24;
        }

        return $cuePoints;
    }

    /**
     * Parse and retrieve all meta tags from a WAV file.
     *
     * @param string $file Path to the file.
     * @return array{
     *  meta: array, 
     *  info: array, 
     *  cue_points: array
     * } Array containing meta information, INFO tags, and cue points.
     * @throws Exception If the file cannot be opened or parsed.
     */
    public static function getMetaTags(string $file): array
    {
        $fh = fopen($file, 'rb');
        if ($fh === false) {
            throw new Exception("Cannot open file: $file");
        }

        $riffHeaderData = fread($fh, 12);
        if ($riffHeaderData === false || strlen($riffHeaderData) < 12) {
            fclose($fh);
            throw new Exception("Failed to read RIFF header from: $file");
        }

        $riffHeader = self::parseRiffHeader($riffHeaderData);
        if ($riffHeader['chunk_id'] !== 'RIFF' || $riffHeader['format'] !== 'WAVE') {
            fclose($fh);
            throw new Exception("Not a valid WAV file: $file");
        }

        $meta = [
            'file_size' => $riffHeader['chunk_size'] + 8,
        ];
        $fmt = [];
        $info = [];
        $cuePoints = [];
        $dataSize = 0;

        $fileEnd = $riffHeader['chunk_size'] + 8;

        while (ftell($fh) < $fileEnd) {
            $chunkHeader = fread($fh, 8);
            if ($chunkHeader === false || strlen($chunkHeader) < 8) {
                break;
            }

            $chunkId = substr($chunkHeader, 0, 4);
            $chunkSize = BinaryHandler::readLittleEndianUint32($chunkHeader, 4);

            if ($chunkSize < 0) {
                break;
            }

            $chunkStart = ftell($fh);

            switch ($chunkId) {
                case 'fmt ':
                    $fmtData = fread($fh, $chunkSize);
                    if ($fmtData !== false && strlen($fmtData) >= 16) {
                        $fmt = self::parseFmtChunk($fmtData);
                    }
                    break;

                case 'data':
                    $dataSize = $chunkSize;
                    fseek($fh, $chunkSize, SEEK_CUR);
                    break;

                case 'LIST':
                    $listType = fread($fh, 4);
                    if ($listType === 'INFO') {
                        $info = self::parseInfoChunk($fh, $chunkSize - 4);
                    } else {
                        fseek($fh, $chunkSize - 4, SEEK_CUR);
                    }
                    break;

                case 'cue ':
                    $cueData = fread($fh, $chunkSize);
                    if ($cueData !== false) {
                        $cuePoints = self::parseCueChunk($cueData);
                    }
                    break;

                default:
                    fseek($fh, $chunkSize, SEEK_CUR);
                    break;
            }

            $expectedPos = $chunkStart + $chunkSize;
            if ($chunkSize % 2 !== 0) {
                $expectedPos++;
            }
            fseek($fh, $expectedPos);
        }

        fclose($fh);

        $meta = array_merge($meta, $fmt);
        $meta['data_size'] = $dataSize;

        if (!empty($fmt) && $fmt['byte_rate'] > 0 && $dataSize > 0) {
            $durationSeconds = $dataSize / $fmt['byte_rate'];
            $meta['duration'] = $durationSeconds;
            $secs = (int) round($durationSeconds);
            $meta['duration_human'] = sprintf('%d:%02d', intdiv($secs, 60), $secs % 60);
        }

        if (!empty($fmt) && $dataSize > 0) {
            $meta['bitrate'] = $fmt['byte_rate'] * 8;
        }

        ksort($meta);

        return [
            'meta' => $meta,
            'info' => $info,
            'cue_points' => $cuePoints,
        ];
    }

    /**
     * Get the title (INAM) from the WAV file metadata.
     *
     * @param string $file Path to the file.
     * @return string|null Title or null if not found.
     */
    public static function getTitle(string $file): ?string
    {
        $tags = self::getMetaTags($file);

        return $tags['info']['INAM']['value'] ?? null;
    }

    /**
     * Get the artist (IART) from the WAV file metadata.
     *
     * @param string $file Path to the file.
     * @return string|null Artist or null if not found.
     */
    public static function getArtist(string $file): ?string
    {
        $tags = self::getMetaTags($file);

        return $tags['info']['IART']['value'] ?? null;
    }

    /**
     * Get the genre (IGNR) from the WAV file metadata.
     *
     * @param string $file Path to the file.
     * @return string|null Genre or null if not found.
     */
    public static function getGenre(string $file): ?string
    {
        $tags = self::getMetaTags($file);

        return $tags['info']['IGNR']['value'] ?? null;
    }

    /**
     * Get the comment (ICMT) from the WAV file metadata.
     *
     * @param string $file Path to the file.
     * @return string|null Comment or null if not found.
     */
    public static function getComment(string $file): ?string
    {
        $tags = self::getMetaTags($file);

        return $tags['info']['ICMT']['value'] ?? null;
    }

    /**
     * Get the duration of the WAV file in seconds.
     *
     * @param string $file Path to the file.
     * @return float|null Duration in seconds or null if unable to determine.
     */
    public static function getDuration(string $file): ?float
    {
        $tags = self::getMetaTags($file);

        return $tags['meta']['duration'] ?? null;
    }

    /**
     * Get the human-readable duration of the WAV file (e.g., M:SS).
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
     * Get the bitrate of the WAV file.
     *
     * @param string $file Path to the file.
     * @return int|null Bitrate in bps or null if not available.
     */
    public static function getBitrate(string $file): ?int
    {
        $tags = self::getMetaTags($file);

        return $tags['meta']['bitrate'] ?? null;
    }

    /**
     * Get the sample rate of the WAV file.
     *
     * @param string $file Path to the file.
     * @return int|null Sample rate in Hz or null if not available.
     */
    public static function getSampleRate(string $file): ?int
    {
        $tags = self::getMetaTags($file);

        return $tags['meta']['sample_rate'] ?? null;
    }

    /**
     * Get the number of audio channels.
     *
     * @param string $file Path to the file.
     * @return int|null Number of channels or null if not available.
     */
    public static function getNumChannels(string $file): ?int
    {
        $tags = self::getMetaTags($file);

        return $tags['meta']['num_channels'] ?? null;
    }

    /**
     * Get the bits per sample parameter.
     *
     * @param string $file Path to the file.
     * @return int|null Bits per sample or null if not available.
     */
    public static function getBitsPerSample(string $file): ?int
    {
        $tags = self::getMetaTags($file);

        return $tags['meta']['bits_per_sample'] ?? null;
    }

    /**
     * Retrieve basic audio information and standard tags from the file.
     *
     * @param string $file Path to the file.
     * @return array{
     *  artist: mixed, 
     *  audio_format: mixed, 
     *  bitrate: mixed, 
     *  bits_per_sample: mixed, 
     *  comment: mixed, 
     *  creation_date: mixed, 
     *  duration: mixed, 
     *  duration_human: mixed, 
     *  genre: mixed, 
     *  num_channels: mixed, 
     *  sample_rate: mixed, 
     *  software: mixed, 
     *  title: mixed
     * } Array consisting of standard info keys.
     */
    public static function getBasicInfo(string $file): array
    {
        $tags = self::getMetaTags($file);
        $meta = $tags['meta'];
        $info = $tags['info'];

        return [
            'title' => $info['INAM']['value'] ?? null,
            'artist' => $info['IART']['value'] ?? null,
            'genre' => $info['IGNR']['value'] ?? null,
            'comment' => $info['ICMT']['value'] ?? null,
            'software' => $info['ISFT']['value'] ?? null,
            'creation_date' => $info['ICRD']['value'] ?? null,
            'audio_format' => $meta['audio_format_name'] ?? null,
            'num_channels' => $meta['num_channels'] ?? null,
            'sample_rate' => $meta['sample_rate'] ?? null,
            'bits_per_sample' => $meta['bits_per_sample'] ?? null,
            'bitrate' => $meta['bitrate'] ?? null,
            'duration' => $meta['duration'] ?? null,
            'duration_human' => $meta['duration_human'] ?? null,
        ];
    }

    #endregion
}
