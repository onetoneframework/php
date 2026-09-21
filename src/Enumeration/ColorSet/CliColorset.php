<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration;

abstract class CliColorset
{
    /**
     * #000000
     * @var string
     */
    const string BLACK = '\33[0;30m';

    /**
     * #0000ff
     * @var string
     */
    const string BLUE = '\33[0;34m';

    /**
     * #a52a2a
     * @var string
     */
    const string BROWN = '\33[0;33m';

    /**
     * #00ffff
     * @var string
     */
    const string CYAN = '\33[0;36m';

    /**
     * #a9a9a9
     * @var string
     */
    const string DARKGRAY = '\33[1;30m';

    /**
     * #008000
     * @var string
     */
    const string GREEN = '\33[0;32m';

    /**
     * #add8e6
     * @var string
     */
    const string LIGHTBLUE = '\33[1;34';

    /**
     * #e0ffff
     * @var string
     */
    const string LIGHTCYAN = '\33[1;36m';

    /**
     * #d3d3d3
     * @var string
     */
    const string LIGHTGRAY = '\33[0;37m';

    /**
     * #90ee90
     * @var string
     */
    const string LIGHTGREEN = '\33[1;32m';

    const string LIGHTPURPLE = '\33[1;35m';

    const string LIGHTRED = '\33[1;31m';

    /**
     * #800080
     * @var string
     */
    const string PURPLE = '\33[0;35m';

    /**
     * #ff0000
     * @var string
     */
    const string RED = '\33[0;31m';

    const string RESET = '\33[0m';

    /**
     * #ffffff
     * @var string
     */
    const string WHITE = '\33[1;37m';

    /**
     * #ffff00
     * @var string
     */
    const string YELLOW = '\33[1;33m';
}