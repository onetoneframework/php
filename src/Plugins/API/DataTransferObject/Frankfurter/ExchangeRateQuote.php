<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Plugin\API\Frankfurter;

use Clover\Plugin\API\PublicDataInterface;

/**
 * Single currency rate row for Frankfurter-style JSON (`rates` map).
 */
final class ExchangeRateQuote implements PublicDataInterface
{
    public function __construct(
        private string $currencyCode,
        private float $rate,
    ) {
    }

    public static function from(mixed $key = null, mixed $data): self
    {
        $code = is_string($key) ? $key : (string) ($data->currency ?? '');
        $rate = isset($data->rate) ? (float) $data->rate : 0.0;

        return new self($code, $rate);
    }

    public function getCurrencyCode(): string
    {
        return $this->currencyCode;
    }

    public function getRate(): float
    {
        return $this->rate;
    }
}
