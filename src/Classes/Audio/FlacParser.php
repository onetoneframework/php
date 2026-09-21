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
use Clover\Enumeration\Bitwise\Bit;
use Exception;
use function strlen;
use function substr;
use function ord;
use function sprintf;
use function is_array;

#endregion

/**
 * Class FlacParser
 *
 * A class to parse FLAC audio files and extract metadata, including stream information, Vorbis comments, pictures, and seek tables.
 * 
 * This class provides methods to read and interpret the FLAC file structure, allowing users to access various metadata fields such as title, artist, album, genre, track number, year, duration, bitrate, sample rate, number of channels, bits per sample, and embedded album art. It also includes functionality to save album art to a file or retrieve it as a Base64 encoded string.
 */
class FlacParser extends BaseClass
{
    #region properties

    // Constants for FLAC metadata block types based on the FLAC specification.
    public static int $BLOCK_STREAMINFO = 0;

    // Other block types (1-6) are defined for completeness, but only STREAMINFO, VORBIS_COMMENT, PICTURE, and SEEKTABLE are parsed in this implementation.
    public static int $BLOCK_PADDING = 1;

    // The following block types are reserved for future use or specific applications, but are included here for reference.
    public static int $BLOCK_APPLICATION = 2;

    // SEEKTABLE blocks contain seek points for efficient seeking within the audio stream.
    public static int $BLOCK_SEEKTABLE = 3;

    // VORBIS_COMMENT blocks contain user-defined metadata in a key=value format, similar to ID3 tags in MP3 files.
    public static int $BLOCK_VORBIS_COMMENT = 4;

    // CUESHEET blocks contain information about the track layout, such as track offsets and indices, which can be used for CD ripping or similar applications.
    public static int $BLOCK_CUESHEET = 5;

    // PICTURE blocks contain embedded images, such as album art, and include metadata about the image type, MIME type, dimensions, and the image data itself.
    public static int $BLOCK_PICTURE = 6;

    // Map of block types to their names for easier identification.
    public static array $blockTypeMap = [
        0 => 'STREAMINFO',
        1 => 'PADDING',
        2 => 'APPLICATION',
        3 => 'SEEKTABLE',
        4 => 'VORBIS_COMMENT',
        5 => 'CUESHEET',
        6 => 'PICTURE',
    ];

    // Map of picture types to their descriptions based on the FLAC specification.
    public static array $pictureTypes = [
        0 => 'Other',
        1 => '32x32 pixels file icon',
        2 => 'Other file icon',
        3 => 'Cover (front)',
        4 => 'Cover (back)',
        5 => 'Leaflet page',
        6 => 'Media',
        7 => 'Lead artist/lead performer/soloist',
        8 => 'Artist/performer',
        9 => 'Conductor',
        10 => 'Band/Orchestra',
        11 => 'Composer',
        12 => 'Lyricist/text writer',
        13 => 'Recording Location',
        14 => 'During recording',
        15 => 'During performance',
        16 => 'Movie/video screen capture',
        17 => 'A bright coloured fish',
        18 => 'Illustration',
        19 => 'Band/artist logotype',
        20 => 'Publisher/Studio logotype',
    ];

    #endregion
    
    #region function

    /**
     * Check if a given file is a valid FLAC file.
     *
     * @param string $file Path to the file.
     * @return bool True if valid, false otherwise.
     */
    public static function isValidFlac(string $file): bool
    {
        if (!file_exists($file) || !is_readable($file)) {
            return false;
        }

        $fh = @fopen($file, 'rb');
        if ($fh === false) {
            return false;
        }

        $magic = fread($fh, 4);
        fclose($fh);

        return $magic === 'fLaC';
    }

    /**
     * Parse the header of a FLAC metadata block.
     *
     * @param string $data The 4-byte block header data.
     * @return array{
     *  is_last: bool, 
     *  block_type: int, 
     *  block_type_name: string, 
     *  length: int
     * } Array containing is_last, block_type, block_type_name, and length.
     */
    public static function parseMetadataBlockHeader(string $data): array
    {
        $byte0 = ord($data[0]);
        $isLast = ($byte0 & 0x80) !== 0;
        $blockType = $byte0 & 0x7F;

        return [
            'is_last' => $isLast,
            'block_type' => $blockType,
            'block_type_name' => self::$blockTypeMap[$blockType] ?? 'RESERVED',
            'length' => BinaryHandler::readBigEndianUint24($data, 1),
        ];
    }

    /**
     * Parse a STREAMINFO metadata block.
     *
     * @param string $data The data of the STREAMINFO block.
     * @return array{
     *  min_block_size: int, 
     *  max_block_size: int, 
     *  min_frame_size: int, 
     *  max_frame_size: int, 
     *  sample_rate: int, 
     *  num_channels: int, 
     *  bits_per_sample: int, 
     *  total_samples: int, 
     *  md5: string,
     *  duration: float|int|null,
     *  duration_human: string|null
     * } Array of stream characteristics.
     */
    public static function parseStreamInfo(string $data): array
    {
        $minBlockSize = BinaryHandler::readBigEndianUint16($data, 0);
        $maxBlockSize = BinaryHandler::readBigEndianUint16($data, 2);
        $minFrameSize = BinaryHandler::readBigEndianUint24($data, 4);
        $maxFrameSize = BinaryHandler::readBigEndianUint24($data, 7);

        $b10 = ord($data[10]);
        $b11 = ord($data[11]);
        $b12 = ord($data[12]);
        $b13 = ord($data[13]);

        $sampleRate = ($b10 << 12) | ($b11 << 4) | (($b12 & 0xF0) >> 4);
        $numChannels = (($b12 & 0x0E) >> 1) + 1;
        $bitsPerSample = (($b12 & 0x01) << 4) | (($b13 & 0xF0) >> 4) + 1;

        $totalSamplesHigh = $b13 & 0x0F;
        $totalSamplesLow = BinaryHandler::readBigEndianUint32($data, 14);
        $totalSamples = ($totalSamplesHigh * Bit::UINT32_MAX_PLUS_ONE) + $totalSamplesLow;

        $md5 = bin2hex(substr($data, 18, 16));

        $duration = null;
        $durationHuman = null;
        if ($sampleRate > 0 && $totalSamples > 0) {
            $duration = $totalSamples / $sampleRate;
            $secs = (int) round($duration);
            $durationHuman = sprintf('%d:%02d', intdiv($secs, 60), $secs % 60);
        }

        return [
            'min_block_size' => $minBlockSize,
            'max_block_size' => $maxBlockSize,
            'min_frame_size' => $minFrameSize,
            'max_frame_size' => $maxFrameSize,
            'sample_rate' => $sampleRate,
            'num_channels' => $numChannels,
            'bits_per_sample' => $bitsPerSample,
            'total_samples' => $totalSamples,
            'md5' => $md5,
            'duration' => $duration,
            'duration_human' => $durationHuman,
        ];
    }

    /**
     * Parse a VORBIS_COMMENT metadata block.
     *
     * @param string $data The data of the VORBIS_COMMENT block.
     * @return array{
     *  vendor: string, 
     *  comments: array<array|string>
     * } Array containing vendor string and track comments.
     */
    public static function parseVorbisComment(string $data): array
    {
        $offset = 0;
        $vendorLength = BinaryHandler::readLittleEndianUint32($data, $offset);
        $offset += 4;

        $vendor = substr($data, $offset, $vendorLength);
        $offset += $vendorLength;

        $commentCount = BinaryHandler::readLittleEndianUint32($data, $offset);
        $offset += 4;

        $comments = [];
        for ($i = 0; $i < $commentCount && $offset < strlen($data); $i++) {
            if ($offset + 4 > strlen($data)) {
                break;
            }

            $length = BinaryHandler::readLittleEndianUint32($data, $offset);
            $offset += 4;

            if ($offset + $length > strlen($data)) {
                break;
            }

            $comment = substr($data, $offset, $length);
            $offset += $length;

            $eqPos = strpos($comment, '=');
            if ($eqPos !== false) {
                $key = strtoupper(substr($comment, 0, $eqPos));
                $value = substr($comment, $eqPos + 1);

                if (isset($comments[$key])) {
                    if (!is_array($comments[$key])) {
                        $comments[$key] = [$comments[$key]];
                    }
                    $comments[$key][] = $value;
                } else {
                    $comments[$key] = $value;
                }
            }
        }

        return [
            'vendor' => $vendor,
            'comments' => $comments,
        ];
    }

    /**
     * Parse a PICTURE metadata block.
     *
     * @param string $data The data of the PICTURE block.
     * @return array{
     *  type: int, 
     *  type_name: string, 
     *  mime: string, 
     *  description: string, 
     *  width: int, 
     *  height: int, 
     *  color_depth: int, 
     *  colors_used: int, 
     *  data: string
     * } Array of picture attributes including MIME type, dimensions, and image data.
     */
    public static function parsePicture(string $data): array
    {
        $offset = 0;

        $pictureType = BinaryHandler::readBigEndianUint32($data, $offset);
        $offset += 4;

        $mimeLength = BinaryHandler::readBigEndianUint32($data, $offset);
        $offset += 4;
        $mime = substr($data, $offset, $mimeLength);
        $offset += $mimeLength;

        $descLength = BinaryHandler::readBigEndianUint32($data, $offset);
        $offset += 4;
        $description = substr($data, $offset, $descLength);
        $offset += $descLength;

        $width = BinaryHandler::readBigEndianUint32($data, $offset);
        $offset += 4;
        $height = BinaryHandler::readBigEndianUint32($data, $offset);
        $offset += 4;
        $colorDepth = BinaryHandler::readBigEndianUint32($data, $offset);
        $offset += 4;
        $colorsUsed = BinaryHandler::readBigEndianUint32($data, $offset);
        $offset += 4;

        $dataLength = BinaryHandler::readBigEndianUint32($data, $offset);
        $offset += 4;
        $imageData = substr($data, $offset, $dataLength);

        return [
            'type' => $pictureType,
            'type_name' => self::$pictureTypes[$pictureType] ?? 'Unknown',
            'mime' => $mime,
            'description' => $description,
            'width' => $width,
            'height' => $height,
            'color_depth' => $colorDepth,
            'colors_used' => $colorsUsed,
            'data' => $imageData,
        ];
    }

    /**
     * Parse a SEEKTABLE metadata block.
     *
     * @param string $data The data of the SEEKTABLE block.
     * @return array{
     *  frame_samples: int, 
     *  sample_number: int, 
     *  stream_offset: int
     * }[] List of seek points.
     */
    public static function parseSeekTable(string $data): array
    {
        $seekPoints = [];
        $offset = 0;
        $entrySize = 18;

        while ($offset + $entrySize <= strlen($data)) {
            $sampleHigh = BinaryHandler::readBigEndianUint32($data, $offset);
            $sampleLow = BinaryHandler::readBigEndianUint32($data, $offset + 4);
            $sampleNumber = ($sampleHigh * Bit::UINT32_MAX_PLUS_ONE) + $sampleLow;

            $offsetHigh = BinaryHandler::readBigEndianUint32($data, $offset + 8);
            $offsetLow = BinaryHandler::readBigEndianUint32($data, $offset + 12);
            $streamOffset = ($offsetHigh * Bit::UINT32_MAX_PLUS_ONE) + $offsetLow;

            $frameSamples = BinaryHandler::readBigEndianUint16($data, $offset + 16);

            if ($sampleNumber !== 0xFFFFFFFFFFFFFFFF) {
                $seekPoints[] = [
                    'sample_number' => $sampleNumber,
                    'stream_offset' => $streamOffset,
                    'frame_samples' => $frameSamples,
                ];
            }

            $offset += $entrySize;
        }

        return $seekPoints;
    }

    /**
     * Parse and retrieve all meta tags from a FLAC file.
     *
     * @param string $file Path to the file.
     * @return array{
     *  meta: array{
     *      bits_per_sample: ?int, 
     *      duration: ?float, 
     *      duration_human: ?string, 
     *      bitrate: ?int, 
     *      sample_rate: ?int, 
     *      num_channels: ?int
     *  }, 
     *  vorbis_comment: array{
     *      vendor: string|null, 
     *      comments: array{
     *          TITLE: string, 
     *          ARTIST: string, 
     *          ALBUM: string, 
     *          YEAR: string, 
     *          TRACKNUMBER: string, 
     *          GENRE: string
     *      }
     *  }, 
     *  pictures: array{
     *      type: int, 
     *      type_name: string, 
     *      mime: string, 
     *      description: string, 
     *      width: int,
     *      height: int, 
     *      color_depth: int, 
     *      colors_used: int, 
     *      data: string
     *  }, 
     *  seek_table: array{
     *      frame_samples: int, 
     *      sample_number: int, 
     *      stream_offset: int
     *  }, 
     *  apicData: array{
     *      color_depth: int, 
     *      colors_used: int, 
     *      data: string, 
     *      description: string, 
     *      height: int, 
     *      mime: string, 
     *      type: int, 
     *      type_name: string, 
     *      width: int
     *  }|null, 
     *  apicFound: bool
     * } Array containing meta information, vorbis comments, pictures, and seek table.
     * @throws Exception If the file cannot be opened or parsed.
     */
    public static function getMetaTags(string $file): array
    {
        $fh = fopen($file, 'rb');
        if ($fh === false) {
            throw new Exception("Cannot open file: $file");
        }

        $magic = fread($fh, 4);
        if ($magic !== 'fLaC') {
            fclose($fh);
            throw new Exception("Not a valid FLAC file: $file");
        }

        $meta = [];
        $vorbisComment = [];
        $pictures = [];
        $seekTable = [];
        $streamInfo = [];

        while (true) {
            $blockHeaderData = fread($fh, 4);
            if ($blockHeaderData === false || strlen($blockHeaderData) < 4) {
                break;
            }

            $blockHeader = self::parseMetadataBlockHeader($blockHeaderData);
            $blockData = fread($fh, $blockHeader['length']);

            if ($blockData === false || strlen($blockData) < $blockHeader['length']) {
                break;
            }

            switch ($blockHeader['block_type']) {
                case self::$BLOCK_STREAMINFO:
                    $streamInfo = self::parseStreamInfo($blockData);
                    break;

                case self::$BLOCK_VORBIS_COMMENT:
                    $vorbisComment = self::parseVorbisComment($blockData);
                    break;

                case self::$BLOCK_PICTURE:
                    $pictures[] = self::parsePicture($blockData);
                    break;

                case self::$BLOCK_SEEKTABLE:
                    $seekTable = self::parseSeekTable($blockData);
                    break;
            }

            if ($blockHeader['is_last']) {
                break;
            }
        }

        $audioDataStart = ftell($fh);
        fclose($fh);

        $meta = $streamInfo;

        if (!empty($streamInfo) && isset($streamInfo['duration']) && $streamInfo['duration'] !== null) {
            $filesize = @filesize($file);
            if ($filesize !== false && $streamInfo['duration'] > 0) {
                $audioBytes = $filesize - $audioDataStart;
                $meta['bitrate'] = (int) round(($audioBytes * 8) / $streamInfo['duration']);
            }
        }

        ksort($meta);

        $apicFound = !empty($pictures);
        $apicData = $apicFound ? $pictures[0] : null;

        return [
            'meta' => $meta,
            'vorbis_comment' => $vorbisComment,
            'pictures' => $pictures,
            'seek_table' => $seekTable,
            'apicData' => $apicData,
            'apicFound' => $apicFound,
        ];
    }

    /**
     * Get the title from the FLAC file metadata.
     *
     * @param string $file Path to the file.
     * @return string|null Title or null if not found.
     */
    public static function getTitle(string $file): ?string
    {
        $tags = self::getMetaTags($file);
        $comments = $tags['vorbis_comment']['comments'] ?? [];

        return $comments['TITLE'] ?? null;
    }

    /**
     * Get the artist from the FLAC file metadata.
     *
     * @param string $file Path to the file.
     * @return string|null Artist or null if not found.
     */
    public static function getArtist(string $file): ?string
    {
        $tags = self::getMetaTags($file);
        $comments = $tags['vorbis_comment']['comments'] ?? [];

        return $comments['ARTIST'] ?? null;
    }

    /**
     * Get the album from the FLAC file metadata.
     *
     * @param string $file Path to the file.
     * @return string|null Album or null if not found.
     */
    public static function getAlbum(string $file): ?string
    {
        $tags = self::getMetaTags($file);
        $comments = $tags['vorbis_comment']['comments'] ?? [];

        return $comments['ALBUM'] ?? null;
    }

    /**
     * Get the genre from the FLAC file metadata.
     *
     * @param string $file Path to the file.
     * @return string|null Genre or null if not found.
     */
    public static function getGenre(string $file): ?string
    {
        $tags = self::getMetaTags($file);
        $comments = $tags['vorbis_comment']['comments'] ?? [];

        return $comments['GENRE'] ?? null;
    }

    /**
     * Get the track number from the FLAC file metadata.
     *
     * @param string $file Path to the file.
     * @return string|null Track number or null if not found.
     */
    public static function getTrackNumber(string $file): ?string
    {
        $tags = self::getMetaTags($file);
        $comments = $tags['vorbis_comment']['comments'] ?? [];

        return $comments['TRACKNUMBER'] ?? null;
    }

    /**
     * Get the year/date from the FLAC file metadata.
     *
     * @param string $file Path to the file.
     * @return string|null Year/Date or null if not found.
     */
    public static function getYear(string $file): ?string
    {
        $tags = self::getMetaTags($file);
        $comments = $tags['vorbis_comment']['comments'] ?? [];

        return $comments['DATE'] ?? null;
    }

    /**
     * Get the duration of the FLAC file in seconds.
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
     * Get the human-readable duration of the FLAC file (e.g., M:SS).
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
     * Get the bitrate of the FLAC file.
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
     * Get the sample rate of the FLAC file.
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
     * Get the primary album art from the FLAC file.
     *
     * @param string $file Path to the file.
     * @return array{
     *  color_depth: int, 
     *  colors_used: int, 
     *  data: string, 
     *  description: string, 
     *  height: int, 
     *  mime: string, 
     *  type: int, 
     *  type_name: string, 
     *  width: int
     * }|null Array containing picture data or null if not found.
     */
    public static function getAlbumArt(string $file): ?array
    {
        $tags = self::getMetaTags($file);

        return $tags['apicFound'] ? $tags['apicData'] : null;
    }

    /**
     * Save the primary album art to the specified path.
     *
     * @param string $file Path to the FLAC file.
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
     * Get the primary album art as a Base64 encoded string.
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
     * Get all Vorbis comments from the FLAC file.
     *
     * @param string $file Path to the file.
     * @return array Associative array of comments.
     */
    public static function getVorbisComments(string $file): array
    {
        $tags = self::getMetaTags($file);

        return $tags['vorbis_comment']['comments'] ?? [];
    }

    /**
     * Get the vendor string from the Vorbis comments.
     *
     * @param string $file Path to the file.
     * @return string|null Vendor string or null if not found.
     */
    public static function getVendor(string $file): ?string
    {
        $tags = self::getMetaTags($file);

        return $tags['vorbis_comment']['vendor'] ?? null;
    }

    /**
     * Retrieve basic audio information and standard tags from the file.
     *
     * @param string $file Path to the file.
     * @return array{
     *  title: string, 
     *  artist: string, 
     *  album: string, 
     *  year: string, 
     *  track: string, 
     *  genre: string, 
     *  duration: float|int|null,
     *  duration_human: string|null,
     *  bitrate: int,
     *  sample_rate: int,
     *  num_channels: int,
     *  bits_per_sample: int,
     *  has_album_art: bool
     * } Array consisting of standard metadata fields.
     */
    public static function getBasicInfo(string $file): array
    {
        $tags = self::getMetaTags($file);
        $meta = $tags['meta'];
        $comments = $tags['vorbis_comment']['comments'] ?? [];

        return [
            'title' => $comments['TITLE'] ?? null,
            'artist' => $comments['ARTIST'] ?? null,
            'album' => $comments['ALBUM'] ?? null,
            'year' => $comments['DATE'] ?? null,
            'track' => $comments['TRACKNUMBER'] ?? null,
            'genre' => $comments['GENRE'] ?? null,
            'duration' => $meta['duration'] ?? null,
            'duration_human' => $meta['duration_human'] ?? null,
            'bitrate' => $meta['bitrate'] ?? null,
            'sample_rate' => $meta['sample_rate'] ?? null,
            'num_channels' => $meta['num_channels'] ?? null,
            'bits_per_sample' => $meta['bits_per_sample'] ?? null,
            'has_album_art' => $tags['apicFound'],
        ];
    }

    #endregion
}
