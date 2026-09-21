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

class MidForest implements PublicDataInterface
{
    private ?string $wfSv;

    public function __construct($wfSv)
    {
        $this->wfSv = $wfSv;
    }

    public static function from(mixed $key = null, mixed $data): self 
    {
        return new self($data->wfSv);
    }
    
}