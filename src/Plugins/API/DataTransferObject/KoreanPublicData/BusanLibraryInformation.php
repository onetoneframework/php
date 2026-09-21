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

class BusanLibraryInformation implements PublicDataInterface
{
    private $area;
    private $name;
    private $address;
    private $telephone;
    private $homepage;

    public function __construct($area, $name, $address, $telephone, $homepage)
    {
        $this->area = $area;
        $this->name = $name;
        $this->address = $address;
        $this->telephone = $telephone;
        $this->homepage = $homepage;
    }

    public static function from(mixed $key = null, mixed $data): self 
    {
        return new self($data->library_area, $data->library_nm, $data->library_addr, $data->library_tel, $data->library_hompage);
    }

    public function getBusinessNumber(): string
    {
        return $this->area;
    }

    public function getStatus(): string
    {
        return $this->name;
    }

    public function getStatusCode(): int
    {
        return $this->address;
    }

    public function getTaxType(): string
    {
        return $this->telephone;
    }
}