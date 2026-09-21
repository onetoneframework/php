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

class AccidentDeathBoardInformation implements PublicDataInterface
{
    private $contents;
    private $keyword;
    private $arno;

    public function __construct($contents, $keyword, $arno)
    {
        $this->contents = $contents;
        $this->keyword = $keyword;
        $this->arno = $arno;
    }

    public static function from(mixed $key = null, mixed $data): self
    {
        return new self($data->contents, $data->keyword, $data->arno);
    }

}