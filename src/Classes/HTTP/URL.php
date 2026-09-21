<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\HTTP;

use Clover\Classes\BaseClass;
use Clover\Enumeration\ISO2Code;
use function sprintf;

class URL extends BaseClass
{
    /**
     * Generate URL-encoded query string
     * 
     * @param object|array $data The data to be encoded as a query string. Can be an associative array or an object.
     * @param string $numeric_prefix If numeric indices are used in the data, this prefix will be added to the beginning of the index in the query string. Default is an empty string.
     * @param string|null $arg_separator The separator used between arguments in the query string. If null, it defaults to '&' (ampersand). You can also use '&amp;' for HTML contexts.
     * @param int $encoding_type The encoding type for the query string. Can be PHP_QUERY_RFC1738 (default) which encodes spaces as '+' and other special characters as percent
     * 
     * @return string
     */
    public static function generateEncodedQueryString(object|array $data, string $numeric_prefix = "", ?string $arg_separator = null, int $encoding_type = PHP_QUERY_RFC1738): string
    {
        return http_build_query($data, $numeric_prefix, $arg_separator, $encoding_type);
    }

    /**
     * Generate iTunes link
     * 
     * @param ISO2Code $country The country code for the iTunes store (default is Japan).
     * @param int $identifier The unique identifier for the album or track on iTunes (default is 1).
     * 
     * @return string
     */
    public static function getItunesLink(ISO2Code $country = ISO2Code::JAPAN, int $identifier = 1): string
    {
        return sprintf("https://music.apple.com/%s/album/%d", $country->value, $identifier);
    }

    /**
     * Generate Amazon JP music link
     * 
     * @param string $identifier The unique identifier for the music product on Amazon JP (e.g., ASIN).
     * 
     * @return string
     */
    public static function getAmazoneJpMusicLink(string $identifier): string
    {
        return sprintf("https://www.amazon.co.jp/dp/%s", $identifier);
    }

    /**
     * Generate DMM music link
     * 
     * @param string $identifier The unique identifier for the music product on DMM (e.g., product code).
     * 
     * @return string
     */
    public static function getDMMMusicLink(string $identifier): string
    {
        return sprintf("http://dlsoft.dmm.co.jp/music/detail/%s", $identifier);
    }

    /**
     * Generate DLsite product link
     * 
     * @param string $identifier The unique identifier for the product on DLsite (e.g., product code).
     * 
     * @return string
     */
    public static function getDlsiteProductLink(string $identifier): string
    {
        return sprintf("https://www.dlsite.com/pro/work/=/product_id/%s.html", $identifier);
    }

    /**
     * Generate YouTube video link
     * 
     * @param string $identifier The unique identifier for the YouTube video (e.g., video ID).
     * 
     * @return string
     */
    public static function getYoutubeLink(string $identifier): string
    {
        return sprintf("https://www.youtube.com/watch?v=%s", $identifier);
    }

}
