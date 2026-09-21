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

class MeasurmentStationList implements PublicDataInterface
{
    public string $stationName;
    public string $address;
    public string $installYear;
    public string $measurementNetwork;
    public string $item;
    public string|float|int $x;
    public string|float|int $y;
    public function __construct($stationName, $address, $installYear, $measurementNetwork, $item, $x, $y)
    {
        $this->stationName = $stationName;
        $this->address = $address;
        $this->installYear = $installYear;
        $this->measurementNetwork = $measurementNetwork;
        $this->item = $item;
        $this->x = (int) $x;
        $this->y = (int) $y;
    }

    public static function from(mixed $key = null, mixed $data): self
    {
        return new self($data->stationName, $data->addr, $data->year, $data->mangName, $data->item, $data->dmX, $data->dmY);
    }

}
