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

class TMStandardCoordinates implements PublicDataInterface
{
    private string $sidoName;
    private string $sggName;
    private string $umdName;
    private string $tmX;
    private string $tmY;
    public function __construct($tmX, $tmY, $umdName, $sggName, $sidoName)
    {
        $this->tmX = $tmX;
        $this->tmY = $tmY;
        $this->umdName = $umdName;
        $this->sggName = $sggName;
        $this->sidoName = $sidoName;
    }

    public static function from(mixed $key = null, mixed $data): self
    {
        return new self($data->tmX, $data->tmY, $data->umdName, $data->sggName, $data->sidoName);
    }

}