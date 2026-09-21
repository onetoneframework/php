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

class AnniversaryInformation implements PublicDataInterface
{
    private ?string $locdate;
    private ?string $seq;
    private ?string $dateKind;
    private ?string $isHoliday;
    private ?string $dateName;

    public function __construct($locdate, $seq, $dateKind, $isHoliday, $dateName)
    {
        $this->locdate = $locdate;
        $this->seq = $seq;
        $this->dateKind = $dateKind;
        $this->isHoliday = $isHoliday;
        $this->dateName = $dateName;
    }

    public static function from(mixed $key = null, mixed $data): self 
    {
        return new self($data->locdate, $data->seq, $data->dateKind, $data->isHoliday, $data->dateName);
    }
    
}