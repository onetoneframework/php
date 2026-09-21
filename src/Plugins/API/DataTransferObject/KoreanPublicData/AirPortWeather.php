<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Plugin\API\KoreaPublicData;

use Clover\Plugin\API\PublicDataInterface;

class AirPortWeather implements PublicDataInterface
{
    private $businessNumber;

    public function __construct($businessNumber)
    {
        $this->businessNumber = $businessNumber;
    }

    public static function from(mixed $key = null, mixed $data): self 
    {
        return new self($data->tm);
    }

}