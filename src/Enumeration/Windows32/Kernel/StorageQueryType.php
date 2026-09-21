<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration\Windows32\Kernel;

/**
 * STORAGE_QUERY_TYPE, the `QueryType` field of STORAGE_PROPERTY_QUERY (ntddstor.h).
 *
 * Split out of {@see StorageQueryProperty}, which had crammed these four values in alongside
 * STORAGE_PROPERTY_ID. The two are distinct Windows enumerations that both start at 0, so a single
 * PHP enum could not back them: cases 0-3 collided and were written as aliases to bare undefined
 * constants, which made the whole file fatal the instant it was autoloaded.
 */
enum StorageQueryType: int
{
    case PropertyStandardQuery = 0;
    case PropertyExistsQuery = 1;
    case PropertyMaskQuery = 2;
    case PropertyQueryMaxDefined = 3;
}
