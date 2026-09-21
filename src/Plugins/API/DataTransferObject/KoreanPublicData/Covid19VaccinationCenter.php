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

class Covid19VaccinationCenter implements PublicDataInterface
{
    private $centerName;
    private $sido;
    private $sigungu;
    private $facilityName;
    private $zipCode;
    private $address;
    private $lat;
    private $lng;
    private $createdAt;
    private $updatedAt;
    private $centerType;
    private $org;
    private $phoneNumber;

    public function __construct($centerName, $sido, $sigungu, $facilityName, $zipCode, $address, $lat, $lng, $createdAt, $updatedAt, $centerType, $org, $phoneNumber)
    {
        $this->centerName = $centerName;
        $this->sido = $sido;
        $this->sigungu = $sigungu;
        $this->facilityName = $facilityName;
        $this->zipCode = $zipCode;
        $this->address = $address;
        $this->lat = $lat;
        $this->lng = $lng;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
        $this->centerType = $centerType;
        $this->org = $org;
        $this->phoneNumber = $phoneNumber;
    }

    public static function from(mixed $key = null, mixed $data): self
    {
        return new self($data->centerName, $data->sido, $data->sigungu, $data->facilityName, $data->zipCode, $data->address, $data->lat, $data->lng, $data->createdAt, $data->updatedAt, $data->centerType, $data->org, $data->phoneNumber);
    }

}