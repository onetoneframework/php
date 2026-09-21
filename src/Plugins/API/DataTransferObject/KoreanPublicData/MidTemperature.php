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

class MidTemperature implements PublicDataInterface
{
    private ?string $taMin3;
    private ?string $taMin3Low;
    private ?string $taMin3High;
    private ?string $taMax3;
    private ?string $taMax3Low;
    private ?string $taMax3High;
    private ?string $taMin4;
    private ?string $taMin4Low;
    private ?string $taMin4High;
    private ?string $taMax4;
    private ?string $taMax4Low;
    private ?string $taMax4High;
    private ?string $taMin5;
    private ?string $taMin5Low;
    private ?string $taMin5High;
    private ?string $taMax5;
    private ?string $taMax5Low;
    private ?string $taMax5High;
    private ?string $taMin6;
    private ?string $taMin6Low;
    private ?string $taMin6High;
    private ?string $taMax6;
    private ?string $taMax6Low;
    private ?string $taMax6High;
    private ?string $taMin7;
    private ?string $taMin7Low;
    private ?string $taMin7High;
    private ?string $taMax7;
    private ?string $taMax7Low;
    private ?string $taMax7High;
    private ?string $taMin8;
    private ?string $taMin8Low;
    private ?string $taMin8High;
    private ?string $taMax8;
    private ?string $taMax8Low;
    private ?string $taMax8High;
    private ?string $taMin9;
    private ?string $taMin9Low;
    private ?string $taMin9High;
    private ?string $taMax9;
    private ?string $taMax9Low;
    private ?string $taMax9High;
    private ?string $taMin10;
    private ?string $taMin10Low;
    private ?string $taMin10High;
    private ?string $taMax10;
    private ?string $taMax10Low;
    private ?string $taMax10High;

    public function __construct(
        $taMin3,
        $taMin3Low,
        $taMin3High,
        $taMax3,
        $taMax3Low,
        $taMax3High,
        $taMin4,
        $taMin4Low,
        $taMin4High,
        $taMax4,
        $taMax4Low,
        $taMax4High,
        $taMin5,
        $taMin5Low,
        $taMin5High,
        $taMax5,
        $taMax5Low,
        $taMax5High,
        $taMin6,
        $taMin6Low,
        $taMin6High,
        $taMax6,
        $taMax6Low,
        $taMax6High,
        $taMin7,
        $taMin7Low,
        $taMin7High,
        $taMax7,
        $taMax7Low,
        $taMax7High,
        $taMin8,
        $taMin8Low,
        $taMin8High,
        $taMax8,
        $taMax8Low,
        $taMax8High,
        $taMin9,
        $taMin9Low,
        $taMin9High,
        $taMax9,
        $taMax9Low,
        $taMax9High,
        $taMin10,
        $taMin10Low,
        $taMin10High,
        $taMax10,
        $taMax10Low,
        $taMax10High
    ) {
        $this->taMin3 = $taMin3;
        $this->taMin3Low = $taMin3Low;
        $this->taMin3High = $taMin3High;
        $this->taMax3 = $taMax3;
        $this->taMax3Low = $taMax3Low;
        $this->taMax3High = $taMax3High;

        $this->taMin4 = $taMin4;
        $this->taMin4Low = $taMin4Low;
        $this->taMin4High = $taMin4High;
        $this->taMax4 = $taMax4;
        $this->taMax4Low = $taMax4Low;
        $this->taMax4High = $taMax4High;

        $this->taMin5 = $taMin5;
        $this->taMin5Low = $taMin5Low;
        $this->taMin5High = $taMin5High;
        $this->taMax5 = $taMax5;
        $this->taMax5Low = $taMax5Low;
        $this->taMax5High = $taMax5High;

        $this->taMin6 = $taMin6;
        $this->taMin6Low = $taMin6Low;
        $this->taMin6High = $taMin6High;
        $this->taMax6 = $taMax6;
        $this->taMax6Low = $taMax6Low;
        $this->taMax6High = $taMax6High;

        $this->taMin7 = $taMin7;
        $this->taMin7Low = $taMin7Low;
        $this->taMin7High = $taMin7High;
        $this->taMax7 = $taMax7;
        $this->taMax7Low = $taMax7Low;
        $this->taMax7High = $taMax7High;

        $this->taMin8 = $taMin8;
        $this->taMin8Low = $taMin8Low;
        $this->taMin8High = $taMin8High;
        $this->taMax8 = $taMax8;
        $this->taMax8Low = $taMax8Low;
        $this->taMax8High = $taMax8High;

        $this->taMin9 = $taMin9;
        $this->taMin9Low = $taMin9Low;
        $this->taMin9High = $taMin9High;
        $this->taMax9 = $taMax9;
        $this->taMax9Low = $taMax9Low;
        $this->taMax9High = $taMax9High;

        $this->taMin10 = $taMin10;
        $this->taMin10Low = $taMin10Low;
        $this->taMin10High = $taMin10High;
        $this->taMax10 = $taMax10;
        $this->taMax10Low = $taMax10Low;
        $this->taMax10High = $taMax10High;
    }

    public static function from(mixed $key = null, mixed $data): self
    {
        return new self(
            $data->taMin3,
            $data->taMin3Low,
            $data->taMin3High,
            $data->taMax3,
            $data->taMax3Low,
            $data->taMax3High,
            $data->taMin4,
            $data->taMin4Low,
            $data->taMin4High,
            $data->taMax4,
            $data->taMax4Low,
            $data->taMax4High,
            $data->taMin5,
            $data->taMin5Low,
            $data->taMin5High,
            $data->taMax5,
            $data->taMax5Low,
            $data->taMax5High,
            $data->taMin6,
            $data->taMin6Low,
            $data->taMin6High,
            $data->taMax6,
            $data->taMax6Low,
            $data->taMax6High,
            $data->taMin7,
            $data->taMin7Low,
            $data->taMin7High,
            $data->taMax7,
            $data->taMax7Low,
            $data->taMax7High,
            $data->taMin8,
            $data->taMin8Low,
            $data->taMin8High,
            $data->taMax8,
            $data->taMax8Low,
            $data->taMax8High,
            $data->taMin9,
            $data->taMin9Low,
            $data->taMin9High,
            $data->taMax9,
            $data->taMax9Low,
            $data->taMax9High,
            $data->taMin10,
            $data->taMin10Low,
            $data->taMin10High,
            $data->taMax10,
            $data->taMax10Low,
            $data->taMax10High
        );
    }

}