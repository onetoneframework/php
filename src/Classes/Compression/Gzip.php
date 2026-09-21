<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Compression;

use Clover\Classes\BaseClass;
use Clover\Classes\Data\StringObject;

/**
 * Class GZip
 *
 * Provides methods for compressing and decompressing data using Gzip format.
 * 
 * @package Clover\Classes\Compression
 */
class GZip extends BaseClass
{
    /**
     * Compress data using Gzip
     *
     * @param string|StringObject $data Data to compress
     * @param int $level The level of compression. Can be given as 0 for no compression up to 9 for maximum compression. If -1 is used, the default compression of the zlib library is used which is 6.
     * @param int $encoding One of ZLIB_ENCODING_* constants.
     * 
     * @return bool|string Compressed data on success, false on failure
     */
    public static function compress(string|StringObject $data, int $level = -1, int $encoding = ZLIB_ENCODING_GZIP): bool|string
    {
        return gzcompress((string) $data, $level, $encoding);
    }

    /**
     * Uncompress Gzip data
     *
     * @param string|StringObject $data Data to uncompress
     * @param int $maxLength The maximum length of data to decode.
     * 
     * @return bool|string Uncompressed data on success, false on failure
     */
    public static function uncompress(string|StringObject $data, int $maxLength = 0): bool|string
    {
        return gzuncompress((string) $data, $maxLength);
    }

    /**
     * Decode Gzip data
     *
     * @param string|StringObject $data Data to decode
     * @param int $maxLength The maximum length of data to decode.
     * 
     * @return bool|string decoded data on success, false on failure
     */
    public static function decode(string|StringObject $data, int $maxLength = 0): bool|string
    {
        return gzdecode((string) $data, $maxLength);
    }

    /**
     * Decode Gzip data
     *
     * @param string|StringObject $data Data to decode
     * @param int $level The level of compression. Can be given as 0 for no compression up to 9 for maximum compression. If not given, the default compression level will be the default compression level of the zlib library.
     * @param int $encoding The encoding mode. Can be FORCE_GZIP (the default) or FORCE_DEFLATE . FORCE_DEFLATE generates RFC 1950 compliant output, consisting of a zlib header, the deflated data, and an Adler checksum.
     * 
     * @return bool|string decoded data on success, false on failure
     */
    public static function encode(string|StringObject $data, int $level = -1, int $encoding = ZLIB_ENCODING_GZIP): bool|string
    {
        return gzencode((string) $data, $level, $encoding);
    }

    /**
     * Deflate Gzip data
     *
     * @param string|StringObject $data Data to deflate
     * @param int $level The level of compression. Can be given as 0 for no compression up to 9 for maximum compression. If not given, the default compression level will be the default compression level of the zlib library.
     * @param int $encoding One of ZLIB_ENCODING_* constants.
     * 
     * @return bool|string deflated data on success, false on failure
     */
    public static function deflate(string|StringObject $data, int $level = -1, int $encoding = ZLIB_ENCODING_GZIP): bool|string
    {
        return gzdeflate((string) $data, $level, $encoding);
    }

    /**
     * Inflate Gzip data
     *
     * @param string|StringObject $data Data to inflate
     * @param int $maxLength The maximum length of decoded data.
     * 
     * @return bool|string inflated data on success, false on failure
     */
    public static function inflate(string|StringObject $data, int $maxLength = 0): bool|string
    {
        return gzinflate((string) $data, $maxLength);
    }
}
