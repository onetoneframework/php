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

class NearMeasurmentStationList implements PublicDataInterface
{
    public ?string $stationName;
    public ?string $address;
    public ?float $tm;
    public ?string $items;
    public function __construct($stationName, $address, $tm, $items)
    {
        $this->stationName = $stationName;
        $this->address = $address;
        $this->tm = $tm;
        $this->items = $items;
    }

    public static function from(mixed $key = null, mixed $data): self
    {
        return new self($data->stationName, $data->addr, $data->tm, $data->items);
    }

}
