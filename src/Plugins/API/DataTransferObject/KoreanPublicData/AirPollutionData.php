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

class AirPollutionData implements PublicDataInterface
{
    private $so2Value;
    private $coValue;
    private $o3Value;
    private $no2Value;
    private $pm10Value;
    private $pm25Value;

    public function __construct($so2Value, $coValue, $o3Value, $no2Value, $pm10Value, $pm25Value)
    {
        $this->so2Value = $so2Value;
        $this->coValue = $coValue;
        $this->o3Value = $o3Value;
        $this->no2Value = $no2Value;
        $this->pm10Value = $pm10Value;
        $this->pm25Value = $pm25Value;
    }

    public static function from(mixed $key = null, mixed $data): self
    {
        return new self($data->so2Value, $data->coValue, $data->o3Value, $data->no2Value, $data->pm10Value, $data->pm25Value);
    }

}