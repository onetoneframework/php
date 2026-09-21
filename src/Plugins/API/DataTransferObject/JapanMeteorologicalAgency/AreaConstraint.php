<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Plugin\API\JapanMeteorologicalAgency;

use Clover\Plugin\API\PublicDataInterface;

class AreaConstraint implements PublicDataInterface
{
    private $code;
    private $name;
    private $englishName;
    private $officeName;

    public function __construct($code, $name, $englishName, $officeName)
    {
        $this->code = $code;
        $this->name = $name;
        $this->englishName = $englishName;
        $this->officeName = $officeName;
    }

    public static function from(mixed $key = null, mixed $data): self
    {
        return new self($key, $data->name, $data->enName, $data->officeName);
    }

}