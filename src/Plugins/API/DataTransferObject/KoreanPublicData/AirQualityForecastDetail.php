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

class AirQualityForecastDetail implements PublicDataInterface
{
    private string $dataTime;
    private string $informCode;
    private ?string $informOverall;
    private string $informCause;
    private string $informGrade;
    private ?string $actionKnack;
    private ?string $imageUrl1;
    private ?string $imageUrl2;
    private ?string $imageUrl3;
    private ?string $imageUrl4;
    private ?string $imageUrl5;
    private ?string $imageUrl6;
    private ?string $imageUrl7;
    private ?string $imageUrl8;
    private ?string $imageUrl9;
    private ?string $informData;

    public function __construct($dataTime, $informCode, $informOverall, $informCause, $informGrade, $actionKnack, $imageUrl1, $imageUrl2, $imageUrl3, $imageUrl4, $imageUrl5, $imageUrl6, $imageUrl7, $imageUrl8, $imageUrl9, $informData)
    {
        $this->dataTime = $dataTime;
        $this->informCode = $informCode;
        $this->informOverall = $informOverall;
        $this->informCause = $informCause;
        $this->informGrade = $informGrade;
        $this->actionKnack = $actionKnack;
        $this->imageUrl1 = $imageUrl1;
        $this->imageUrl2 = $imageUrl2;
        $this->imageUrl3 = $imageUrl3;
        $this->imageUrl4 = $imageUrl4;
        $this->imageUrl5 = $imageUrl5;
        $this->imageUrl6 = $imageUrl6;
        $this->imageUrl7 = $imageUrl7;
        $this->imageUrl8 = $imageUrl8;
        $this->imageUrl9 = $imageUrl9;
        $this->informData = $informData;
    }

    public static function from(mixed $key = null, mixed $data): self
    {
        return new self($data->dataTime, $data->informCode, $data->informOverall, $data->informCause, $data->informGrade, $data->actionKnack, $data->imageUrl1, $data->imageUrl2, $data->imageUrl3, $data->imageUrl4, $data->imageUrl5, $data->imageUrl6, $data->imageUrl7, $data->imageUrl8, $data->imageUrl9, $data->informData);
    }

}