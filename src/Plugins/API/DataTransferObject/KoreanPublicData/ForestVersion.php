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

class ForestVersion implements PublicDataInterface
{
    private ?string $filetype;
    private ?string $version;

    public function __construct($filetype, $version)
    {
        $this->filetype = $filetype;
        $this->version = $version;
    }

    public static function from(mixed $key = null, mixed $data): self 
    {
        return new self($data->filetype, $data->version);
    }
    
}