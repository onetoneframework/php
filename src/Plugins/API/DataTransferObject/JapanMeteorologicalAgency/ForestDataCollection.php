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
use function count;

class ForestDataCollection implements PublicDataInterface
{
    private $forecastDataList;

    public function __construct($forecastDataList)
    {
        $this->forecastDataList = $forecastDataList;
    }

    public static function from(mixed $key = null, mixed $data): self
    {
        $object = [];
        $count = count($data->timeDefines);
        for ($i = 0; $i < $count; $i++) {
            foreach ($data->areas as $area) {
                $object[] = new ForestData(
                    $area->area->code,
                    $area->area->name,
                    $data->timeDefines[$i],
                    $area->weatherCodes[$i] ?? null,
                    $area->weathers[$i] ?? null,
                    $area->winds[$i] ?? null,
                    $area->waves[$i] ?? null,
                );
            }
        }

        return new self($object);
    }

}