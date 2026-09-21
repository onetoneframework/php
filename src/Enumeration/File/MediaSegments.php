<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration;

/**
 * Media Segments Enumeration
 */
abstract class MediaSegments
{
    public const SEGMENT_TYPE_BOX = "styp";
    public const MOVIE_FRAGMENT_BOX = "moof";
    public const MEDIA_DATA_BOX = "mdat";
    public const FILE_TYPE_BOX = "ftyp";
    public const MOVIE_EXTEND_BOX = "mvex";
    public const MOVIE_BOX = "moov";
    public const EDIT_LIST_BOX = "elst";
    public const EDIT_BOX = "edts";
    public const TRACK_FRAGMENT_BOX = "traf";
    public const TRACK_FRAGMENT_DECODE_TIME_BOX = "tfdt";
    public const TRACK_FRAGMENT_RUN_BOX = "trun";
}