<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Data;

class VectorObject
{

    private $n;
    private $m;

    public function __construct($n, $m)
    {
        $this->n = $n;
        $this->m = $m;
    }
}
