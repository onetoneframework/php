<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Plugin\API\Frankfurter;

use Clover\Plugin\API\PublicDataResponse;

/**
 * Parsed Frankfurter (or compatible) exchange-rate response.
 */
final class FrankfurterFetchResult
{
    public function __construct(
        private PublicDataResponse $rates,
        private string $baseCurrency,
        private string $rateDate,
        private float $amount,
        private int $httpStatus,
    ) {
    }

    public function getRates(): PublicDataResponse
    {
        return $this->rates;
    }

    public function getBaseCurrency(): string
    {
        return $this->baseCurrency;
    }

    public function getRateDate(): string
    {
        return $this->rateDate;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getHttpStatus(): int
    {
        return $this->httpStatus;
    }
}
