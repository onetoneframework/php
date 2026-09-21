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

class BusinessNumberValidate implements PublicDataInterface
{
    private $businessNumber;
    private $valid;

    public function __construct($businessNumber, $valid)
    {
        $this->businessNumber = $businessNumber;
        $this->valid = $valid;
    }

    public static function from(mixed $key = null, mixed $data): self 
    {
        return new self($data->b_no, $data->valid);
    }

    public function getValid() {
        return $this->valid;
    }

}