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
use Clover\Classes\Data\ByteArray;
use Clover\Classes\File\Handler as FileHandler;
use Clover\Classes\Math\Frequency;
use Exception;
use function count;
use function intval;
use function is_resource;
use function ord;
use function sprintf;
use function strlen;
use function substr;
use function is_int;
use function chr;
use function in_array;

#endregion

/**
 * Class Mp3Parser
 *
 * This class provides functionality to parse MP3 files, extract metadata from ID3v1 and ID3v2 tags, and analyze MPEG audio frames. It includes methods to check for the presence of ID3 tags, read and interpret tag data, and calculate frame sizes based on the MPEG audio specifications.
 * 
 * @package Clover\Classes\Audio
 */
class Mp3Parser extends BaseClass
{
    #region properties

    /**
     * Layer mapping
     * 
     * @var array<int, string>
     */
    public static array $layerMap = [0 => 'reserved', 1 => 'III', 2 => 'II', 3 => 'I'];

    /**
     * Channel mode mapping
     * 
     * @var array<string>
     */
    public static array $channelModeMap = ['Stereo', 'Joint stereo', 'Dual channel', 'Single channel'];

    /**
     * Map of ID3v2 frame identifiers to their descriptions.
     * 
     * @var array<string, string>
     */
    public static array $frameMap = [
        'AENC' => 'Audio encryption',
        'APIC' => 'Attached picture',
        'COMM' => 'Comments',
        'COMR' => 'Commercial frame',
        'ENCR' => 'Encryption method registration',
        'EQUA' => 'Equalization',
        'ETCO' => 'Event timing codes',
        'GEOB' => 'General encapsulated object',
        'GRID' => 'Group identification registration',
        'IPLS' => 'Involved people list',
        'LINK' => 'Linked information',
        'MCDI' => 'Music CD identifier',
        'MLLT' => 'MPEG location lookup table',
        'OWNE' => 'Ownership frame',
        'PRIV' => 'Private frame',
        'PCNT' => 'Play counter',
        'POPM' => 'Popularimeter',
        'POSS' => 'Position synchronisation frame',
        'RBUF' => 'Recommended buffer size',
        'RVAD' => 'Relative volume adjustment',
        'RVRB' => 'Reverb',
        'SYLT' => 'Synchronized lyric/text',
        'SYTC' => 'Synchronized tempo codes',
        'TALB' => 'Album/Movie/Show title',
        'TBPM' => 'BPM (beats per minute)',
        'TCOM' => 'Composer',
        'TCON' => 'Content type',
        'TCOP' => 'Copyright message',
        'TDAT' => 'Date',
        'TDLY' => 'Playlist delay',
        'TDRC' => 'Recording time',
        'TDRL' => 'Release time',
        'TENC' => 'Encoded by',
        'TEXT' => 'Lyricist/Text writer',
        'TFLT' => 'File type',
        'TIME' => 'Time',
        'TIT1' => 'Content group description',
        'TIT2' => 'Title/songname/content description',
        'TIT3' => 'Subtitle/Description refinement',
        'TKEY' => 'Initial key',
        'TLAN' => 'Language(s)',
        'TLEN' => 'Length',
        'TMED' => 'Media type',
        'TMOO' => 'Mood',
        'TOAL' => 'Original album/movie/show title',
        'TOFN' => 'Original filename',
        'TOLY' => 'Original lyricist(s)/text writer(s)',
        'TOPE' => 'Original artist(s)/performer(s)',
        'TORY' => 'Original release year',
        'TOWN' => 'File owner/licensee',
        'TPE1' => 'Lead performer(s)/Soloist(s)',
        'TPE2' => 'Band/orchestra/accompaniment',
        'TPE3' => 'Conductor/performer refinement',
        'TPE4' => 'Interpreted, remixed, or otherwise modified by',
        'TPOS' => 'Part of a set',
        'TPUB' => 'Publisher',
        'TRCK' => 'Track number/Position in set',
        'TRDA' => 'Recording dates',
        'TRSN' => 'Internet radio station name',
        'TRSO' => 'Internet radio station owner',
        'TSIZ' => 'Size',
        'TSRC' => 'ISRC (international standard recording code)',
        'TSSE' => 'Software/Hardware and settings used for encoding',
        'TYER' => 'Year',
        'TXXX' => 'User defined text information frame',
        'UFID' => 'Unique file identifier',
        'USER' => 'Terms of use',
        'USLT' => 'Unsynchronized lyric/text transcription',
        'WCOM' => 'Commercial information',
        'WCOP' => 'Copyright/Legal information',
        'WOAF' => 'Official audio file webpage',
        'WOAR' => 'Official artist/performer webpage',
        'WOAS' => 'Official audio source webpage',
        'WORS' => 'Official internet radio station homepage',
        'WPAY' => 'Payment',
        'WPUB' => 'Publishers official webpage',
        'WXXX' => 'User defined URL link frame'
    ];

    /**
     * Map of picture types to their descriptions based on the ID3v2 specification.
     * 
     * @var array<int, string>
     */
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
        20 => 'Publisher/Studio logotype'
    ];

    /**
     * Map of genre IDs to their corresponding genre names based on the ID3v1 specification.
     * 
     * @var array<int, string>
     */
    public static array $genres = [
        0 => 'Blues',
        1 => 'Classic Rock',
        2 => 'Country',
        3 => 'Dance',
        4 => 'Disco',
        5 => 'Funk',
        6 => 'Grunge',
        7 => 'Hip-Hop',
        8 => 'Jazz',
        9 => 'Metal',
        10 => 'New Age',
        11 => 'Oldies',
        12 => 'Other',
        13 => 'Pop',
        14 => 'R&B',
        15 => 'Rap',
        16 => 'Reggae',
        17 => 'Rock',
        18 => 'Techno',
        19 => 'Industrial',
        20 => 'Alternative',
        21 => 'Ska',
        22 => 'Death Metal',
        23 => 'Pranks',
        24 => 'Soundtrack',
        25 => 'Euro-Techno',
        26 => 'Ambient',
        27 => 'Trip-Hop',
        28 => 'Vocal',
        29 => 'Jazz+Funk',
        30 => 'Fusion',
        31 => 'Trance',
        32 => 'Classical',
        33 => 'Instrumental',
        34 => 'Acid',
        35 => 'House',
        36 => 'Game',
        37 => 'Sound Clip',
        38 => 'Gospel',
        39 => 'Noise',
        40 => 'Alternative Rock',
        41 => 'Bass',
        42 => 'Soul',
        43 => 'Punk',
        44 => 'Space',
        45 => 'Meditative',
        46 => 'Instrumental Pop',
        47 => 'Instrumental Rock',
        48 => 'Ethnic',
        49 => 'Gothic',
        50 => 'Darkwave',
        51 => 'Techno-Industrial',
        52 => 'Electronic',
        53 => 'Pop-Folk',
        54 => 'Eurodance',
        55 => 'Dream',
        56 => 'Southern Rock',
        57 => 'Comedy',
        58 => 'Cult',
        59 => 'Gangsta',
        60 => 'Top 40',
        61 => 'Christian Rap',
        62 => 'Pop/Funk',
        63 => 'Jungle',
        64 => 'Native US',
        65 => 'Cabaret',
        66 => 'New Wave',
        67 => 'Psychedelic',
        68 => 'Rave',
        69 => 'Showtunes',
        70 => 'Trailer',
        71 => 'Lo-Fi',
        72 => 'Tribal',
        73 => 'Acid Punk',
        74 => 'Acid Jazz',
        75 => 'Polka',
        76 => 'Retro',
        77 => 'Musical',
        78 => 'Rock & Roll',
        79 => 'Hard Rock',
        80 => 'Folk',
        81 => 'Folk-Rock',
        82 => 'National Folk',
        83 => 'Swing',
        84 => 'Fast Fusion',
        85 => 'Bebob',
        86 => 'Latin',
        87 => 'Revival',
        88 => 'Celtic',
        89 => 'Bluegrass',
        90 => 'Avantgarde',
        91 => 'Gothic Rock',
        92 => 'Progressive Rock',
        93 => 'Psychedelic Rock',
        94 => 'Symphonic Rock',
        95 => 'Slow Rock',
        96 => 'Big Band',
        97 => 'Chorus',
        98 => 'Easy Listening',
        99 => 'Acoustic',
        100 => 'Humour',
        101 => 'Speech',
        102 => 'Chanson',
        103 => 'Opera',
        104 => 'Chamber Music',
        105 => 'Sonata',
        106 => 'Symphony',
        107 => 'Booty Bass',
        108 => 'Primus',
        109 => 'Porn Groove',
        110 => 'Satire',
        111 => 'Slow Jam',
        112 => 'Club',
        113 => 'Tango',
        114 => 'Samba',
        115 => 'Folklore',
        116 => 'Ballad',
        117 => 'Power Ballad',
        118 => 'Rhythmic Soul',
        119 => 'Freestyle',
        120 => 'Duet',
        121 => 'Punk Rock',
        122 => 'Drum Solo',
        123 => 'A capella',
        124 => 'Euro-House',
        125 => 'Dance Hall',
        126 => 'Goa',
        127 => 'Drum & Bass',
        128 => 'Club-House',
        129 => 'Hardcore',
        130 => 'Terror',
        131 => 'Indie',
        132 => 'BritPop',
        133 => 'Negerpunk',
        134 => 'Polsk Punk',
        135 => 'Beat',
        136 => 'Christian Gangsta Rap',
        137 => 'Heavy Metal',
        138 => 'Black Metal',
        139 => 'Crossover',
        140 => 'Contemporary Christian',
        141 => 'Christian Rock',
        142 => 'Merengue',
        143 => 'Salsa',
        144 => 'Thrash Metal',
        145 => 'Anime',
        146 => 'Jpop',
        147 => 'Synthpop'
    ];

    /**
     * Bitrate and sample rate tables
     * 
     * @var array<string, array<int>>
     */
    public static array $bitrateTable = [
        'V1L1' => [0, 32, 64, 96, 128, 160, 192, 224, 256, 288, 320, 352, 384, 416, 448],
        'V1L2' => [0, 32, 48, 56, 64, 80, 96, 112, 128, 160, 192, 224, 256, 320, 384],
        'V1L3' => [0, 32, 40, 48, 56, 64, 80, 96, 112, 128, 160, 192, 224, 256, 320],
        'V2L1' => [0, 32, 48, 56, 64, 80, 96, 112, 128, 144, 160, 176, 192, 224, 256],
        'V2L2' => [0, 8, 16, 24, 32, 40, 48, 56, 64, 80, 96, 112, 128, 144, 160],
        'V2L3' => [0, 8, 16, 24, 32, 40, 48, 56, 64, 80, 96, 112, 128, 144, 160]
    ];

    /**
     * Sample rate table
     * 
     * @var array<string, array<int>>
     */
    public static array $sampleRateTable = [
        '1' => [44100, 48000, 32000],
        '2' => [22050, 24000, 16000],
        '2.5' => [11025, 12000, 8000]
    ];

    /**
     * Layer mapping
     * 
     * @var array<int, string>
     */
    public static array $layers = [
        0x0 => 'x',
        0x1 => '3',
        0x2 => '2',
        0x3 => '1',
    ];

    #endregion

    #region function

    /**
     * Check if the file has an ID3v2 tag
     * 
     * @param resource $fh
     * @param string|null $header
     * 
     * @return bool
     * 
     * @throws Exception
     */
    public static function hasId3V2Tag(mixed $fh, ?string &$header = null): bool
    {
        $header = FileHandler::readBytes($fh, 10);
        return strlen($header) >= 10 && substr($header, 0, 3) === "ID3";
    }

    /**
     * Check if the file has an ID3v1 tag
     * 
     * @param resource $fh
     * 
     * @return bool
     */
    public static function hasId3V1Tag(mixed $fh): bool
    {
        $pos = ftell($fh);
        fseek($fh, -128, SEEK_END);
        $tag = fread($fh, 3);
        fseek($fh, $pos);

        return $tag === 'TAG';
    }

    /**
     * Get the ID3v1 tags from the file
     * 
     * @param string $file
     * 
     * @return array{
     *  album: string, 
     *  artist: string, 
     *  comment: string, 
     *  genre: string, 
     *  genre_id: int, 
     *  title: string, 
     *  track: int|null, 
     *  year: string
     * }|null
     * 
     * @throws Exception
     */
    public static function getId3V1Tags(string $file): ?array
    {
        $fh = fopen($file, 'rb');
        if ($fh === false) {
            throw new Exception("Cannot open file: $file");
        }

        fseek($fh, -128, SEEK_END);
        $data = fread($fh, 128);
        fclose($fh);

        if (substr($data, 0, 3) !== 'TAG') {
            return null;
        }

        $title = rtrim(substr($data, 3, 30), "\0 ");
        $artist = rtrim(substr($data, 33, 30), "\0 ");
        $album = rtrim(substr($data, 63, 30), "\0 ");
        $year = rtrim(substr($data, 93, 4), "\0 ");
        $comment = substr($data, 97, 30);
        $genreId = ord($data[127]);

        $track = null;
        if (ord($comment[28]) === 0 && ord($comment[29]) !== 0) {
            $track = ord($comment[29]);
            $comment = rtrim(substr($comment, 0, 28), "\0 ");
        } else {
            $comment = rtrim($comment, "\0 ");
        }

        return [
            'title' => $title,
            'artist' => $artist,
            'album' => $album,
            'year' => $year,
            'comment' => $comment,
            'track' => $track,
            'genre_id' => $genreId,
            'genre' => self::$genres[$genreId] ?? 'Unknown'
        ];
    }

    /**
     * Parse the MPEG header from the given bytes
     * 
     * @param string $bytes
     * 
     * @return array{
     *  channel_mode_bits: int,
     *  bitrate_key: string,  
     *  version: float|int|string,  
     *  simple_version: float|int|string, 
     *  layer: string, 
     *  protection: int, 
     *  bitrate: int|null, 
     *  sample_rate: int|null, 
     *  padding: int, 
     *  private: int, 
     *  mode: string, 
     *  mode_extension: int, 
     *  copyright: int, 
     *  original: int, 
     *  emphasis: int, 
     *  samples: int, 
     *  framesize: int|null
     * }|null
     */
    public static function parseMpegHeader(string $bytes): array
    {
        $b = array_map('ord', str_split($bytes));
        $versionBits = ($b[1] & 0x18) >> 3;
        $layerBits = ($b[1] & 0x06) >> 1;
        $layer = self::$layers[$layerBits];
        $protectionBit = ($b[1] & 0x01) ^ 1;
        $bitrateIndex = ($b[2] & 0xF0) >> 4;
        $sampleRateIndex = ($b[2] & 0x0C) >> 2;
        $paddingBit = ($b[2] & 0x02) >> 1;
        $privateBit = ($b[2] & 0x01);
        $mode = ($b[3] & 0xC0) >> 6;
        $modeExtension = ($b[3] & 0x30) >> 4;
        $copyright = ($b[3] & 0x08) >> 3;
        $original = ($b[3] & 0x04) >> 2;
        $emphasis = ($b[3] & 0x03);
        $channelModeBits = ($b[3] & 0xc0) >> 6;

        if ($versionBits === 3) {
            $version = '1';
        } elseif ($versionBits === 2) {
            $version = '2';
        } elseif ($versionBits === 0) {
            $version = '2.5';
        } else {
            $version = 'reserved';
        }
        $simpleVersion = ($version == '2.5' ? 2 : $version);
        $bitrateKey = sprintf('V%dL%d', $simpleVersion, $layer);

        $layer = self::$layerMap[$layerBits];

        $bitrate = null;
        if (isset(self::$bitrateTable[$bitrateKey]) && $bitrateIndex > 0 && $bitrateIndex < 15) {
            $bitrate = self::$bitrateTable[$bitrateKey][$bitrateIndex] * 1000;
        }

        $srArr = self::$sampleRateTable[$version] ?? null;
        $sampleRate = ($srArr && $sampleRateIndex < count($srArr)) ? $srArr[$sampleRateIndex] : null;

        if ($layer === 'I') {
            $samples = ($version === '1') ? 384 : 384;
        } else {
            $samples = ($version === '1') ? 1152 : 576;
        }

        $channelModel = self::$channelModeMap[$mode] ?? 'Unknown';

        $frameSize = self::getFramesize($version, $layer, $bitrate, $sampleRate, $paddingBit);

        return [
            'channel_mode_bits' => $channelModeBits,
            'bitrate_key' => $bitrateKey,
            'version' => $version,
            'simple_version' => $simpleVersion,
            'layer' => $layer,
            'protection' => $protectionBit,
            'bitrate' => $bitrate,
            'sample_rate' => $sampleRate,
            'padding' => $paddingBit,
            'private' => $privateBit,
            'mode' => $channelModel,
            'mode_extension' => $modeExtension,
            'copyright' => $copyright,
            'original' => $original,
            'emphasis' => $emphasis,
            'samples' => $samples,
            'framesize' => $frameSize
        ];
    }

    /**
     * Skip the ID3v2 tag in the given block
     * 
     * @param string &$block
     * 
     * @return int
     */
    public static function skipID3v2Tag(string &$block): int
    {
        if (substr($block, 0, 3) == 'ID3') {
            $id3v2_flags = ord($block[5]);
            $flag_footer_present = $id3v2_flags & 0x10 ? 1 : 0;
            $z0 = ord($block[6]);
            $z1 = ord($block[7]);
            $z2 = ord($block[8]);
            $z3 = ord($block[9]);

            if ((($z0 & 0x80) == 0) && (($z1 & 0x80) == 0) && (($z2 & 0x80) == 0) && (($z3 & 0x80) == 0)) {
                $header_size = 10;
                $tag_size = (($z0 & 0x7f) * 2097152) + (($z1 & 0x7f) * 16384) + (($z2 & 0x7f) * 128) + ($z3 & 0x7f);
                $footer_size = $flag_footer_present ? 10 : 0;

                return $header_size + $tag_size + $footer_size;
            }
        }

        return 0;
    }

    /**
     * Calculate the frame size
     * 
     * @param string $version
     * @param string $layer
     * @param int|null $bitrate
     * @param int|null $sampleRate
     * @param int $paddingBit
     * 
     * @return int|null
     */
    public static function getFramesize(string $version, string $layer, int|null $bitrate, int|null $sampleRate, int $paddingBit): int|null
    {
        $frameSize = null;

        if ($bitrate === null || $sampleRate === null) {
            return $frameSize;
        }

        if ($layer === 'I') {
            $frameSize = intval((12 * $bitrate / $sampleRate + $paddingBit) * 4);
        } elseif ($version === '1') {
            $frameSize = intval(144 * $bitrate / $sampleRate + $paddingBit);
        } else {
            $frameSize = intval(72 * $bitrate / $sampleRate + $paddingBit);
        }

        return $frameSize;
    }

    /**
     * Parse the genre string, which can be in the format "(n)" or a numeric string, and return the corresponding genre name.
     * 
     * @param string $genreString
     * 
     * @return string
     */
    public static function parseGenre(string $genreString): string
    {
        if (preg_match('/^\((\d+)\)/', $genreString, $matches)) {
            $id = (int) $matches[1];
            return self::$genres[$id] ?? $genreString;
        }

        if (is_numeric($genreString)) {
            $id = (int) $genreString;
            return self::$genres[$id] ?? $genreString;
        }

        return $genreString;
    }

    /**
     * Get the picture type name based on the ID3v2 specification
     * 
     * @param int $pictureType
     * 
     * @return string
     */
    public static function getPictureTypeName(int $pictureType): string
    {
        return self::$pictureTypes[$pictureType] ?? 'Unknown';
    }

    /**
     * Get the metadata tags from the MP3 file
     * 
     * @param string $file
     * 
     * @return array{
     *  apicData: array{
     *      data: string, 
     *      desc: string, 
     *      mime: string, 
     *      type: int, 
     *      type_name: string
     *  }|null, 
     *  apicFound: bool, 
     *  meta: array
     * }
     * 
     * @throws Exception
     */
    public static function getMetaTags(string $file): array
    {
        $fh = fopen($file, 'rb');
        if ($fh === false || !is_resource($fh)) {
            throw new Exception("Cannot open file or invalid stream resource: $file");
        }

        self::hasId3V2Tag($fh, $header);
        if (!isset($header) || strlen($header) < 10) {
            if (is_resource($fh)) {
                fclose($fh);
            }

            throw new Exception("Invalid ID3 header read from: $file");
        }

        $versionMajor = ord($header[3]);
        $versionRev = ord($header[4]);
        $flags = ord($header[5]);
        $sizeBytes = substr($header, 6, 4);
        $tagSize = ByteArray::syncsafeToInt($sizeBytes);

        $hasExtended = ($flags & 0x40) !== 0;
        $unsynchronisation = ($flags & 0x80) !== 0;

        $startPos = ftell($fh);
        if ($startPos === false) {
            if (is_resource($fh)) {
                fclose($fh);
            }

            throw new Exception("Failed to get file pointer position after reading header");
        }

        if ($hasExtended) {
            $extSizeData = FileHandler::readBytes($fh, 4);
            if ($versionMajor == 4) {
                $extSize = ByteArray::syncsafeToInt($extSizeData);
            } else {
                $extSize = ByteArray::bytesToInt($extSizeData);
            }

            fseek($fh, $startPos + $extSize);
        }

        $framesEnd = $startPos + $tagSize;
        if (!is_int($framesEnd) || $framesEnd <= $startPos) {
            if (is_resource($fh)) {
                fclose($fh);
            }

            throw new Exception("Computed invalid framesEnd; tagSize: " . intval($tagSize));
        }

        $apicFound = false;
        $apicData = null;
        $meta = [];

        $meta['version_major'] = $versionMajor;
        $meta['version_rev'] = $versionRev;
        $meta['tag_size'] = $tagSize;
        $meta['header'] = $header;

        if (!is_resource($fh)) {
            throw new Exception('File handler is not resource');
        }

        while (true) {
            $pos = ftell($fh);

            if ($pos === false) {
                if (is_resource($fh)) {
                    fclose($fh);
                }

                throw new Exception("ftell failed while scanning frames");
            }

            if ($pos >= $framesEnd) {
                break;
            }

            if ($versionMajor >= 3) {
                $frameHeader = FileHandler::readBytes($fh, 10);
                if (strlen($frameHeader) < 10) {
                    break;
                }

                $frameId = trim(substr($frameHeader, 0, 4));
                $frameSize = ByteArray::bytesToInt(substr($frameHeader, 4, 4));
                $frameFlags = substr($frameHeader, 8, 2);
                if ($frameSize <= 0) {
                    break;
                }
            } else {
                $frameHeader = FileHandler::readBytes($fh, 6);
                if (strlen($frameHeader) < 6) {
                    break;
                }

                $frameId = trim(substr($frameHeader, 0, 3));
                $frameSize = ByteArray::bytesToInt("\x00" . substr($frameHeader, 3, 3));
                $frameFlags = '';
                if ($frameSize <= 0) {
                    break;
                }
            }

            $frameData = FileHandler::readBytes($fh, $frameSize);
            if ($frameId === '') {
                break;
            }

            if ($frameId[0] === 'T') {
                if (strlen($frameData) < 1) {
                    continue;
                }

                $enc = ord($frameData[0]);
                $text = substr($frameData, 1);

                if ($enc === 0) {
                    $val = rtrim($text, "\0");
                } elseif ($enc === 1) {
                    $val = rtrim(mb_convert_encoding($text, 'UTF-8', 'UTF-16'), "\0");
                } elseif ($enc === 2) {
                    $val = rtrim(mb_convert_encoding($text, 'UTF-8', 'UTF-16BE'), "\0");
                } else {
                    $val = rtrim($text, "\0");
                }
                $key = self::$frameMap[$frameId] ?? $frameId;
                $meta[$frameId] = ['value' => $val, 'description' => $key];

                continue;
            } else if (strtoupper($frameId) === 'APIC' || strtoupper($frameId) === 'PIC') {
                $pos = 0;
                $enc = ord($frameData[$pos]);
                $pos++;
                $mime = '';

                if (strtoupper($frameId) === 'APIC') {
                    while ($pos < strlen($frameData) && $frameData[$pos] !== "\0") {
                        $mime .= $frameData[$pos];
                        $pos++;
                    }
                    $pos++;
                } else {
                    $mime = substr($frameData, $pos, 3);
                    $pos += 3;
                }

                if ($pos >= strlen($frameData)) {
                    continue;
                }

                $picType = ord($frameData[$pos]);
                $pos++;

                if ($enc === 0 || $enc === 3) {
                    $desc = '';
                    while ($pos < strlen($frameData) && $frameData[$pos] !== "\0") {
                        $desc .= $frameData[$pos];
                        $pos++;
                    }
                    $pos++;
                } else {
                    $desc = '';
                    while ($pos + 1 < strlen($frameData) && !(ord($frameData[$pos]) === 0 && ord($frameData[$pos + 1]) === 0)) {
                        $desc .= $frameData[$pos];
                        $pos++;
                    }
                    $pos += 2;
                }

                $imageData = substr($frameData, $pos);

                $mimeLower = strtolower($mime);
                if ($mimeLower === 'jpg' || $mimeLower === 'jpeg') {
                    $mime = 'image/jpeg';
                } else if ($mimeLower === 'png') {
                    $mime = 'image/png';
                } else if ($mimeLower === 'gif') {
                    $mime = 'image/gif';
                }

                $apicFound = true;
                $apicData = [
                    'mime' => $mime,
                    'type' => $picType,
                    'type_name' => self::getPictureTypeName($picType),
                    'desc' => $desc,
                    'data' => $imageData
                ];

                break;
            }
        }

        fseek($fh, 0);

        if ($fh) {
            fseek($fh, $framesEnd);

            while (!feof($fh)) {
                $b = fread($fh, 1);
                if ($b === false || $b === '') {
                    break;
                }

                if (ord($b) != 0xFF) {
                    continue;
                }

                $next = fread($fh, 3);
                if (strlen($next) < 3) {
                    break;
                }

                $hdr = sprintf("%s%s", $b, $next);
                $o = array_map('ord', str_split($hdr));
                if ((($o[1] & 0xE0) == 0xE0) && (($o[1] & 0x06) != 0x00)) {
                    $parsed = self::parseMpegHeader($hdr);
                    if ($parsed) {
                        $meta = array_merge($meta ?? [], $parsed);
                        break;
                    } else {
                        fseek($fh, -3, SEEK_CUR);
                    }
                } else {
                    fseek($fh, -3, SEEK_CUR);
                }
            }

            fclose($fh);
        }

        if (!isset($meta['bitrate']) || $meta['bitrate'] === null || $meta['bitrate'] == 0) {
            if (isset($meta['framesize']) && isset($meta['samples']) && isset($meta['sample_rate']) && $meta['framesize'] > 0) {
                $filesize = @filesize($file);

                if ($filesize !== false) {
                    $audioBytes = max(0, $filesize - $framesEnd);
                    $frameCount = intval($audioBytes / $meta['framesize']);
                    $totalSamples = $frameCount * $meta['samples'];
                    $durationSeconds = $meta['sample_rate'] > 0 ? ($totalSamples / $meta['sample_rate']) : null;
                    if ($durationSeconds !== null) {
                        $meta['duration'] = $durationSeconds;
                    }
                }
            }
        } else {
            $filesize = @filesize($file);

            if ($filesize !== false) {
                $audioStart = isset($framesEnd) ? (int) $framesEnd : 0;
                $audioEnd = $filesize;
                if ($filesize > 128) {
                    $tail = @file_get_contents($file, false, null, $filesize - 128, 3);
                    if ($tail === 'TAG') {
                        $audioEnd -= 128;
                    }
                }

                $audioBytes = max(0, $audioEnd - $audioStart);
                $bitrateBps = (int) $meta['bitrate'];
                if ($bitrateBps > 0) {
                    $durationSeconds = ($audioBytes * 8) / $bitrateBps;
                    $meta['duration'] = $durationSeconds;
                }
            }
        }

        if (isset($meta['duration'])) {
            $secs = (int) round($meta['duration']);
            $min = intdiv($secs, 60);
            $sec = $secs % 60;
            $meta['duration_human'] = sprintf('%d:%02d', $min, $sec);
        }

        if (isset($meta['TCON']['value'])) {
            $meta['TCON']['genre'] = self::parseGenre($meta['TCON']['value']);
        }

        ksort($meta);

        return [
            'meta' => $meta,
            'apicData' => $apicData,
            'apicFound' => $apicFound
        ];
    }

    /**
     * Get the title from the MP3 file metadata.
     *
     * @param string $file Path to the file.
     * @return string|null Title or null if not found.
     */
    public static function getTitle(string $file): ?string
    {
        $tags = self::getMetaTags($file);

        return $tags['meta']['TIT2']['value'] ?? null;
    }

    /**
     * Get the artist from the MP3 file metadata.
     *
     * @param string $file Path to the file.
     * @return string|null Artist or null if not found.
     */
    public static function getArtist(string $file): ?string
    {
        $tags = self::getMetaTags($file);

        return $tags['meta']['TPE1']['value'] ?? null;
    }

    /**
     * Get the album from the MP3 file metadata.
     *
     * @param string $file Path to the file.
     * @return string|null Album or null if not found.
     */
    public static function getAlbum(string $file): ?string
    {
        $tags = self::getMetaTags($file);

        return $tags['meta']['TALB']['value'] ?? null;
    }

    /**
     * Get the duration of the MP3 file in seconds.
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
     * Get the bitrate of the MP3 file.
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
     * Get the sample rate of the MP3 file.
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
     * Get the primary album art
     * 
     * @param string $file Path to the file.
     * 
     * @return array{
     *  mime: string, 
     *  type: int, 
     *  type_name: string, 
     *  desc: string, 
     *  data: string
     * }|null Array with 'mime', 'type', 'type_name', 'desc', and 'data' keys or null if not found.
     */
    public static function getAlbumArt(string $file): ?array
    {
        $tags = self::getMetaTags($file);

        return $tags['apicFound'] ? $tags['apicData'] : null;
    }

    /**
     * Save the primary album art to the specified path.
     *
     * @param string $file Path to the MP3 file.
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
     * Check if the file is a valid MP3 by looking for ID3 tags or MPEG frame headers.
     * 
     * @param string $file Path to the file.
     * @return bool True if the file is a valid MP3, false otherwise.
     */
    public static function isValidMp3(string $file): bool
    {
        if (!file_exists($file) || !is_readable($file)) {
            return false;
        }

        $fh = @fopen($file, 'rb');
        if ($fh === false) {
            return false;
        }

        $header = fread($fh, 10);
        fclose($fh);

        if (strlen($header) >= 3 && substr($header, 0, 3) === 'ID3') {
            return true;
        }

        if (strlen($header) >= 2) {
            $b = array_map('ord', str_split(substr($header, 0, 2)));
            if ($b[0] === 0xFF && ($b[1] & 0xE0) === 0xE0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Retrieve basic audio information and standard tags from the file.
     * 
     * @param string $file Path to the file.
     * 
     * @return array{
     *  title: string, 
     *  artist: string, 
     *  album: string,
     *  year: string, 
     *  track: string, 
     *  genre: string, 
     *  duration: string,
     *  duration_human: string,
     *  bitrate: string,
     *  sample_rate: string,
     *  has_album_art: bool
     * } Array consisting of standard metadata fields.
     */
    public static function getBasicInfo(string $file): array
    {
        $tags = self::getMetaTags($file);
        $meta = $tags['meta'];

        return [
            'title' => $meta['TIT2']['value'] ?? null,
            'artist' => $meta['TPE1']['value'] ?? null,
            'album' => $meta['TALB']['value'] ?? null,
            'year' => $meta['TYER']['value'] ?? $meta['TDRC']['value'] ?? null,
            'track' => $meta['TRCK']['value'] ?? null,
            'genre' => $meta['TCON']['genre'] ?? $meta['TCON']['value'] ?? null,
            'duration' => $meta['duration'] ?? null,
            'duration_human' => $meta['duration_human'] ?? null,
            'bitrate' => $meta['bitrate'] ?? null,
            'sample_rate' => $meta['sample_rate'] ?? null,
            'has_album_art' => $tags['apicFound']
        ];
    }

    /**
     * Build a single ID3v2.3 text frame.
     *
     * @param string $frameId 4-character frame identifier (e.g. TIT2, TPE1)
     * @param string $text UTF-8 text content
     * @param int $encoding Text encoding (0=ISO-8859-1, 1=UTF-16, 2=UTF-16BE, 3=UTF-8)
     * @return string Raw frame bytes
     */
    public static function buildId3v2TextFrame(string $frameId, string $text, int $encoding = 3): string
    {
        $payload = chr($encoding) . $text . "\0";
        $size = pack('N', strlen($payload));
        return $frameId . $size . "\x00\x00" . $payload;
    }

    /**
     * Build an APIC (attached picture) frame for ID3v2.3.
     *
     * @param string $imageData Raw image binary data
     * @param string $mimeType MIME type of the image
     * @param int $pictureType Picture type code per ID3v2 spec
     * @param string $description Optional description
     * @return string Raw APIC frame bytes
     */
    public static function buildApicFrame(string $imageData, string $mimeType = 'image/jpeg', int $pictureType = 3, string $description = ''): string
    {
        $payload = "\x00" . $mimeType . "\0" . chr($pictureType) . $description . "\0" . $imageData;
        $size = pack('N', strlen($payload));
        return 'APIC' . $size . "\x00\x00" . $payload;
    }

    /**
     * Assemble a complete ID3v2.3 tag from concatenated raw frame data.
     *
     * @param string $framesData Concatenated raw frame bytes
     * @return string Complete ID3v2.3 tag
     */
    public static function buildId3v2Tag(string $framesData): string
    {
        return 'ID3' . chr(3) . chr(0) . chr(0) . ByteArray::intToSyncsafe(strlen($framesData)) . $framesData;
    }

    /**
     * Get the byte offset where MPEG audio data begins (after ID3v2 tag).
     *
     * @param string $file Path to the MP3 file
     * @return int
     */
    public static function getAudioStartOffset(string $file): int
    {
        $fh = fopen($file, 'rb');
        if ($fh === false) {
            throw new Exception("Cannot open file: $file");
        }

        $header = fread($fh, 10);
        fclose($fh);

        if (strlen($header) >= 10 && substr($header, 0, 3) === 'ID3') {
            $tagSize = ByteArray::syncsafeToInt(substr($header, 6, 4));
            $flags = ord($header[5]);
            $footerSize = ($flags & 0x10) ? 10 : 0;
            return 10 + $tagSize + $footerSize;
        }

        return 0;
    }

    /**
     * Get the byte offset where MPEG audio data ends (before ID3v1 tag if present).
     *
     * @param string $file Path to the MP3 file
     * @return int
     */
    public static function getAudioEndOffset(string $file): int
    {
        $filesize = filesize($file);
        if ($filesize === false) {
            throw new Exception("Cannot get file size: $file");
        }

        if ($filesize >= 128) {
            $fh = fopen($file, 'rb');
            if ($fh === false) {
                throw new Exception("Cannot open file: $file");
            }
            fseek($fh, -128, SEEK_END);
            $tag = fread($fh, 3);
            fclose($fh);
            if ($tag === 'TAG') {
                return $filesize - 128;
            }
        }

        return $filesize;
    }

    /**
     * Extract raw MPEG audio data bytes (no ID3v1 or ID3v2 tags).
     *
     * @param string $file Path to the MP3 file
     * @return string
     */
    public static function extractRawAudio(string $file): string
    {
        $start = self::getAudioStartOffset($file);
        $end = self::getAudioEndOffset($file);
        $length = $end - $start;

        if ($length <= 0) {
            return '';
        }

        $fh = fopen($file, 'rb');
        if ($fh === false) {
            throw new Exception("Cannot open file: $file");
        }

        fseek($fh, $start);
        $data = fread($fh, $length);
        fclose($fh);

        return $data !== false ? $data : '';
    }

    /**
     * Parse all existing ID3v2 frames from a file into structured array.
     *
     * @param string $file Path to the MP3 file
     * 
     * @return array{
     *  id: string, 
     *  data: string, 
     *  payload: string
     * } Array of ['id' => string, 'data' => string, 'payload' => string]
     */
    public static function parseId3v2Frames(string $file): array
    {
        $fh = fopen($file, 'rb');
        if ($fh === false) {
            throw new Exception("Cannot open file: $file");
        }

        $header = fread($fh, 10);
        if (strlen($header) < 10 || substr($header, 0, 3) !== 'ID3') {
            fclose($fh);
            return [];
        }

        $versionMajor = ord($header[3]);
        $tagSize = ByteArray::syncsafeToInt(substr($header, 6, 4));
        $flags = ord($header[5]);
        $startPos = ftell($fh);

        if (($flags & 0x40) !== 0) {
            $extSizeData = fread($fh, 4);
            $extSize = ($versionMajor == 4) ? ByteArray::syncsafeToInt($extSizeData) : ByteArray::bytesToInt($extSizeData);
            fseek($fh, $startPos + $extSize);
        }

        $framesEnd = $startPos + $tagSize;
        $frames = [];

        while (ftell($fh) < $framesEnd) {
            if ($versionMajor >= 3) {
                $frameHeader = fread($fh, 10);
                if (strlen($frameHeader) < 10) {
                    break;
                }

                $frameId = trim(substr($frameHeader, 0, 4));
                $frameSize = ByteArray::bytesToInt(substr($frameHeader, 4, 4));

                if ($frameSize <= 0 || $frameId === '' || $frameId[0] === "\0") {
                    break;
                }

                $payload = fread($fh, $frameSize);
                $frames[] = [
                    'id' => $frameId,
                    'data' => $frameHeader . $payload,
                    'payload' => $payload
                ];
            } else {
                $frameHeader = fread($fh, 6);
                if (strlen($frameHeader) < 6) {
                    break;
                }

                $frameId = trim(substr($frameHeader, 0, 3));
                $frameSize = ByteArray::bytesToInt("\x00" . substr($frameHeader, 3, 3));

                if ($frameSize <= 0 || $frameId === '' || $frameId[0] === "\0") {
                    break;
                }

                $payload = fread($fh, $frameSize);
                $frames[] = [
                    'id' => $frameId,
                    'data' => $frameHeader . $payload,
                    'payload' => $payload
                ];
            }
        }

        fclose($fh);
        return $frames;
    }

    /**
     * Reassemble an MP3 file from ID3v2 frame data and raw MPEG audio.
     *
     * @param string $outputFile Path to write the output MP3 file
     * @param string $framesData Concatenated raw ID3v2 frame bytes
     * @param string $rawAudio Raw MPEG audio bytes
     * @return bool
     */
    public static function assembleFile(string $outputFile, string $framesData, string $rawAudio): bool
    {
        $output = (strlen($framesData) > 0) ? self::buildId3v2Tag($framesData) : '';
        $output .= $rawAudio;
        return file_put_contents($outputFile, $output) !== false;
    }

    /**
     * Collect existing ID3v2 frame data excluding specified frame IDs.
     *
     * @param array $existingFrames Parsed frames from parseId3v2Frames()
     * @param array $excludeIds Frame IDs to exclude
     * @return string Concatenated raw frame bytes
     */
    public static function collectFramesExcluding(array $existingFrames, array $excludeIds): string
    {
        $framesData = '';
        foreach ($existingFrames as $frame) {
            if (!in_array($frame['id'], $excludeIds, true)) {
                $framesData .= $frame['data'];
            }
        }

        return $framesData;
    }

    /**
     * Write or update ID3v2 text metadata tags in an MP3 file.
     *
     * @param string $inputFile Path to the source MP3 file
     * @param string $outputFile Path to write the modified MP3 file
     * @param array $tags Associative array of frameId => textValue (e.g. ['TIT2' => 'Title', 'TPE1' => 'Artist'])
     * @return bool
     */
    public static function writeMetadata(string $inputFile, string $outputFile, array $tags): bool
    {
        $existingFrames = self::parseId3v2Frames($inputFile);
        $rawAudio = self::extractRawAudio($inputFile);

        $framesData = self::collectFramesExcluding($existingFrames, array_keys($tags));

        foreach ($tags as $frameId => $value) {
            $framesData .= self::buildId3v2TextFrame($frameId, $value);
        }

        return self::assembleFile($outputFile, $framesData, $rawAudio);
    }

    /**
     * Remove a specific ID3v2 frame by its frame ID.
     *
     * @param string $inputFile Path to the source MP3 file
     * @param string $outputFile Path to write the modified MP3 file
     * @param string $frameId Frame ID to remove (e.g. 'TIT2', 'APIC')
     * @return bool
     */
    public static function removeTag(string $inputFile, string $outputFile, string $frameId): bool
    {
        $existingFrames = self::parseId3v2Frames($inputFile);
        $rawAudio = self::extractRawAudio($inputFile);
        $framesData = self::collectFramesExcluding($existingFrames, [$frameId]);
        return self::assembleFile($outputFile, $framesData, $rawAudio);
    }

    /**
     * Remove all ID3 tags (both v1 and v2) from an MP3 file.
     *
     * @param string $inputFile Path to the source MP3 file
     * @param string $outputFile Path to write the clean MP3 file
     * @return bool
     */
    public static function removeAllMetadata(string $inputFile, string $outputFile): bool
    {
        $rawAudio = self::extractRawAudio($inputFile);
        return file_put_contents($outputFile, $rawAudio) !== false;
    }

    /**
     * Set or replace the artwork (APIC frame) in an MP3 file.
     *
     * @param string $inputFile Path to the source MP3 file
     * @param string $outputFile Path to write the modified MP3 file
     * @param string $imageFile Path to the image file to embed
     * @param string $mimeType MIME type of the image
     * @param int $pictureType Picture type code per ID3v2 spec (default: 3 = front cover)
     * @param string $description Optional artwork description
     * @return bool
     */
    public static function setArtwork(string $inputFile, string $outputFile, string $imageFile, string $mimeType = 'image/jpeg', int $pictureType = 3, string $description = ''): bool
    {
        $imageData = file_get_contents($imageFile);
        if ($imageData === false) {
            throw new Exception("Cannot read image file: $imageFile");
        }

        return self::setArtworkFromData($inputFile, $outputFile, $imageData, $mimeType, $pictureType, $description);
    }

    /**
     * Set or replace the artwork from raw binary image data.
     *
     * @param string $inputFile Path to the source MP3 file
     * @param string $outputFile Path to write the modified MP3 file
     * @param string $imageData Raw image binary data
     * @param string $mimeType MIME type of the image
     * @param int $pictureType Picture type code per ID3v2 spec
     * @param string $description Optional artwork description
     * @return bool
     */
    public static function setArtworkFromData(string $inputFile, string $outputFile, string $imageData, string $mimeType = 'image/jpeg', int $pictureType = 3, string $description = ''): bool
    {
        $existingFrames = self::parseId3v2Frames($inputFile);
        $rawAudio = self::extractRawAudio($inputFile);

        $framesData = self::collectFramesExcluding($existingFrames, ['APIC', 'PIC']);
        $framesData .= self::buildApicFrame($imageData, $mimeType, $pictureType, $description);

        return self::assembleFile($outputFile, $framesData, $rawAudio);
    }

    /**
     * Remove all artwork (APIC and PIC frames) from an MP3 file.
     *
     * @param string $inputFile Path to the source MP3 file
     * @param string $outputFile Path to write the modified MP3 file
     * @return bool
     */
    public static function removeArtwork(string $inputFile, string $outputFile): bool
    {
        $existingFrames = self::parseId3v2Frames($inputFile);
        $rawAudio = self::extractRawAudio($inputFile);
        $framesData = self::collectFramesExcluding($existingFrames, ['APIC', 'PIC']);
        return self::assembleFile($outputFile, $framesData, $rawAudio);
    }

    /**
     * Find all MPEG audio frame boundaries with timing and codec properties.
     *
     * @param string $file Path to the MP3 file
     * @return array<array{offset: bool|int, size: int, time: float|int, duration: float|int, bitrate: float|int, sample_rate: float|int, samples: float|int}
     * > Array of ['offset', 'size', 'time', 'duration', 'bitrate', 'sample_rate', 'samples']
     */
    public static function findFrameOffsets(string $file): array
    {
        $fh = fopen($file, 'rb');
        if ($fh === false) {
            throw new Exception("Cannot open file: $file");
        }

        $audioStart = self::getAudioStartOffset($file);
        $audioEnd = self::getAudioEndOffset($file);
        fseek($fh, $audioStart);

        $frames = [];
        $cumulativeTime = 0.0;

        while (ftell($fh) < $audioEnd) {
            $pos = ftell($fh);
            $headerBytes = fread($fh, 4);
            if (strlen($headerBytes) < 4) {
                break;
            }

            $b = array_map('ord', str_split($headerBytes));

            if ($b[0] !== 0xFF || ($b[1] & 0xE0) !== 0xE0 || ($b[1] & 0x06) === 0x00) {
                fseek($fh, $pos + 1);
                continue;
            }

            $parsed = self::parseMpegHeader($headerBytes);
            if ($parsed === null || $parsed['framesize'] === null || $parsed['framesize'] <= 0) {
                fseek($fh, $pos + 1);
                continue;
            }

            if ($parsed['sample_rate'] === null || $parsed['sample_rate'] <= 0) {
                fseek($fh, $pos + 1);
                continue;
            }

            $frameDuration = $parsed['samples'] / $parsed['sample_rate'];

            $frames[] = [
                'offset' => $pos,
                'size' => $parsed['framesize'],
                'time' => $cumulativeTime,
                'duration' => $frameDuration,
                'bitrate' => $parsed['bitrate'],
                'sample_rate' => $parsed['sample_rate'],
                'samples' => $parsed['samples']
            ];

            $cumulativeTime += $frameDuration;
            fseek($fh, $pos + $parsed['framesize']);
        }

        fclose($fh);
        return $frames;
    }

    /**
     * Read specific MPEG frames from a file by their offset/size descriptors.
     *
     * @param resource $fh Open file handle
     * @param array{array{offset: int, size: int}} $frameDescriptors Array of ['offset' => int, 'size' => int]
     * @return string Concatenated frame bytes
     */
    public static function readFrameBytes(mixed $fh, array $frameDescriptors): string
    {
        $data = '';
        foreach ($frameDescriptors as $frame) {
            fseek($fh, $frame['offset']);
            $data .= fread($fh, $frame['size']);
        }

        return $data;
    }

    /**
     * Trim an MP3 file to a specific time range using frame-accurate cutting.
     *
     * @param string $inputFile Path to the source MP3 file
     * @param string $outputFile Path to write the trimmed MP3 file
     * @param float $startSeconds Start time in seconds
     * @param float|null $endSeconds End time in seconds (null = end of file)
     * @return bool
     */
    public static function trim(string $inputFile, string $outputFile, float $startSeconds, ?float $endSeconds = null): bool
    {
        $frames = self::findFrameOffsets($inputFile);
        if (empty($frames)) {
            return false;
        }

        $fh = fopen($inputFile, 'rb');
        if ($fh === false) {
            return false;
        }

        $selectedFrames = [];
        foreach ($frames as $frame) {
            $frameEnd = $frame['time'] + $frame['duration'];
            if ($frameEnd <= $startSeconds) {
                continue;
            }
            if ($endSeconds !== null && $frame['time'] >= $endSeconds) {
                break;
            }
            $selectedFrames[] = $frame;
        }

        $audioData = self::readFrameBytes($fh, $selectedFrames);
        fclose($fh);

        $existingFrames = self::parseId3v2Frames($inputFile);
        $framesData = '';
        foreach ($existingFrames as $f) {
            $framesData .= $f['data'];
        }

        return self::assembleFile($outputFile, $framesData, $audioData);
    }

    /**
     * Concatenate multiple MP3 files into one. Metadata is taken from the first file.
     *
     * @param array $inputFiles Array of file paths to concatenate
     * @param string $outputFile Path to write the concatenated MP3 file
     * @return bool
     */
    public static function concatenate(array $inputFiles, string $outputFile): bool
    {
        if (empty($inputFiles)) {
            return false;
        }

        $existingFrames = self::parseId3v2Frames($inputFiles[0]);
        $framesData = '';
        foreach ($existingFrames as $f) {
            $framesData .= $f['data'];
        }

        $rawAudio = '';
        foreach ($inputFiles as $file) {
            $rawAudio .= self::extractRawAudio($file);
        }

        return self::assembleFile($outputFile, $framesData, $rawAudio);
    }

    /**
     * Extract waveform amplitude envelope from an MP3 file.
     * Computes per-frame RMS energy normalized to 0.0–1.0, resampled to target resolution.
     *
     * @param string $file Path to the MP3 file
     * @param int $resolution Number of amplitude samples to return
     * @return array<float> Array of float values representing the amplitude envelope
     */
    public static function getWaveform(string $file, int $resolution = 1000): array
    {
        $frames = self::findFrameOffsets($file);
        if (empty($frames)) {
            return [];
        }

        $fh = fopen($file, 'rb');
        if ($fh === false) {
            return [];
        }

        $energies = [];
        foreach ($frames as $frame) {
            fseek($fh, $frame['offset'] + 4);
            $payload = fread($fh, $frame['size'] - 4);
            if ($payload === false || strlen($payload) === 0) {
                $energies[] = 0.0;
                continue;
            }

            $sum = 0.0;
            $len = strlen($payload);
            for ($i = 0; $i < $len; $i++) {
                $sample = (ord($payload[$i]) - 128) / 128.0;
                $sum += $sample * $sample;
            }
            $energies[] = sqrt($sum / $len);
        }

        fclose($fh);

        $maxEnergy = max($energies) ?: 1.0;
        $frameCount = count($energies);

        $waveform = [];
        for ($i = 0; $i < $resolution; $i++) {
            $startIdx = (int) floor(($i / $resolution) * $frameCount);
            $endIdx = (int) floor((($i + 1) / $resolution) * $frameCount);
            $endIdx = min($endIdx, $frameCount - 1);
            $startIdx = min($startIdx, $frameCount - 1);

            $peak = 0.0;
            for ($j = $startIdx; $j <= $endIdx; $j++) {
                if ($energies[$j] > $peak) {
                    $peak = $energies[$j];
                }
            }

            $waveform[] = round($peak / $maxEnergy, 4);
        }

        return $waveform;
    }

    /**
     * Extract frequency spectrum data from an MP3 file using FFT on the waveform envelope.
     *
     * @param string $file Path to the MP3 file
     * @param int $fftSize FFT window size (rounded up to next power of 2)
     * @return array{
     *  frequencies: float[], 
     *  magnitudes: float[], 
     *  duration: float
     * }
     */
    public static function getSpectrum(string $file, int $fftSize = 1024): array
    {
        $m = 1;
        while ($m < $fftSize) {
            $m <<= 1;
        }
        $fftSize = $m;

        $waveform = self::getWaveform($file, $fftSize);
        if (empty($waveform)) {
            return ['frequencies' => [], 'magnitudes' => [], 'duration' => 0.0];
        }

        $tags = self::getMetaTags($file);
        $sampleRate = $tags['meta']['sample_rate'] ?? 44100;
        $duration = $tags['meta']['duration'] ?? 0.0;

        $n = count($waveform);
        for ($i = 0; $i < $n; $i++) {
            $waveform[$i] *= 0.5 * (1.0 - cos(2.0 * M_PI * $i / max($n - 1, 1)));
        }

        $fftResult = Frequency::fft($waveform);

        $halfN = count($fftResult['magnitude']);
        $frequencies = [];
        for ($i = 0; $i < $halfN; $i++) {
            $frequencies[] = round($i * ($sampleRate / 2.0) / $halfN, 2);
        }

        return [
            'frequencies' => $frequencies,
            'magnitudes' => $fftResult['magnitude'],
            'duration' => $duration
        ];
    }

    /**
     * Select MPEG frames using an accumulator-based skip/duplicate algorithm.
     * Factor > 1.0 skips frames (fewer output frames), < 1.0 duplicates frames.
     *
     * @param resource $fh Open file handle to the MP3 file
     * @param array $frames Frame descriptors from findFrameOffsets()
     * @param float $factor Selection factor
     * @return string Concatenated selected frame bytes
     */
    public static function selectFramesByFactor(mixed $fh, array $frames, float $factor): string
    {
        $audioData = '';
        $frameCount = count($frames);

        if ($factor >= 1.0) {
            $accumulator = 0.0;
            for ($i = 0; $i < $frameCount; $i++) {
                $accumulator += 1.0;
                if ($accumulator >= $factor) {
                    $accumulator -= $factor;
                    continue;
                }
                fseek($fh, $frames[$i]['offset']);
                $audioData .= fread($fh, $frames[$i]['size']);
            }
        } else {
            $interval = 1.0 / (1.0 - $factor);
            $accumulator = 0.0;
            for ($i = 0; $i < $frameCount; $i++) {
                fseek($fh, $frames[$i]['offset']);
                $frameBytes = fread($fh, $frames[$i]['size']);
                $audioData .= $frameBytes;

                $accumulator += 1.0;
                if ($accumulator >= $interval) {
                    $accumulator -= $interval;
                    $audioData .= $frameBytes;
                }
            }
        }

        return $audioData;
    }

    /**
     * Change the tempo of an MP3 file by frame-level skip/duplicate.
     * This changes both playback speed and pitch proportionally.
     *
     * @param string $inputFile Path to the source MP3 file
     * @param string $outputFile Path to write the modified MP3 file
     * @param float $factor Tempo factor (e.g. 1.5 = 50% faster, 0.75 = 25% slower)
     * @return bool
     */
    public static function changeTempo(string $inputFile, string $outputFile, float $factor): bool
    {
        if ($factor <= 0.0) {
            throw new Exception("Tempo factor must be positive");
        }

        $frames = self::findFrameOffsets($inputFile);
        if (empty($frames)) {
            return false;
        }

        $fh = fopen($inputFile, 'rb');
        if ($fh === false) {
            return false;
        }

        $audioData = self::selectFramesByFactor($fh, $frames, $factor);
        fclose($fh);

        $existingFrames = self::parseId3v2Frames($inputFile);
        $framesData = '';
        foreach ($existingFrames as $f) {
            $framesData .= $f['data'];
        }

        return self::assembleFile($outputFile, $framesData, $audioData);
    }

    /**
     * Change the pitch of an MP3 file by the given number of semitones.
     * Implemented via frame-level speed change: playback rate is scaled by 2^(semitones/12).
     * Both pitch and duration change proportionally; use changeTempo() afterwards to compensate duration if needed.
     *
     * @param string $inputFile Path to the source MP3 file
     * @param string $outputFile Path to write the modified MP3 file
     * @param int $semitones Number of semitones to shift (positive = up, negative = down)
     * @return bool
     */
    public static function changePitch(string $inputFile, string $outputFile, int $semitones): bool
    {
        if ($semitones === 0) {
            return copy($inputFile, $outputFile);
        }

        $factor = pow(2.0, $semitones / 12.0);
        return self::changeTempo($inputFile, $outputFile, $factor);
    }

    #endregion
}
