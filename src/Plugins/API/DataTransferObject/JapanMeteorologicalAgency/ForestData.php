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

class ForestData implements PublicDataInterface
{
    private $areaCode;
    private $areaName;
    private $timeDefines;
    private $weatherCodes;
    private $weathers;
    private $winds;
    private $waves;

    public function __construct($areaCode, $areaName, $timeDefines, $weatherCodes, $weathers, $winds, $waves)
    {
        $this->areaCode = $areaCode;
        $this->areaName = $areaName;
        $this->timeDefines = $timeDefines;
        $this->weatherCodes = $weatherCodes;
        $this->weathers = $weathers;
        $this->winds = $winds;
        $this->waves = $waves;
    }

    public static function from(mixed $key = null, mixed $data): self
    {
        return new self($data->areaCode, $data->areaName, $data->timeDefines, $data->weatherCodes, $data->weathers, $data->winds, $data->waves);
    }

}