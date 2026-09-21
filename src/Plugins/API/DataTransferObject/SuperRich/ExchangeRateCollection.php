<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Plugin\API\SuperRich;

use Clover\Classes\Data\ArrayObject;
use Clover\Plugin\API\PublicDataInterface;
use Clover\Plugin\API\PublicDataResponse;

/**
 * Represents one country/currency block in the SuperRich exchange rate response.
 *
 * Raw JSON shape:
 * {
 *   "countryName": "Japan",
 *   "cUnit": "JPY",
 *   "imgUrl": "...",
 *   "rate": [ { ... }, ... ]
 * }
 */
final class ExchangeRateCollection implements PublicDataInterface
{
    /** @var ExchangeRateEntry[] */
    private ArrayObject $rates;

    public function __construct(
        private string $countryName,
        private string $currencyCode,
        private string $imageUrl,
    ) {
    }

    /**
     * Build an instance from a raw stdClass object produced by the API decoder.
     * The nested "rate" array is mapped into ExchangeRateEntry objects via
     * a child PublicDataResponse so the same pattern is applied recursively.
     */
    public static function from(mixed $key = null, mixed $data = null): self
    {
        $raw = $data ?? $key;

        $instance = new self(
            countryName: (string) ($raw->countryName ?? ''),
            currencyCode: strtoupper((string) ($raw->cUnit ?? '')),
            imageUrl: (string) ($raw->imgUrl ?? ''),
        );

        // Map the nested rate rows using the same PublicDataResponse pattern.
        $rateResponse = new PublicDataResponse();
        $rateResponse->setStack($raw->rate ?? []);
        $rateResponse->setData(ExchangeRateEntry::class);

        $instance->rates = $rateResponse->getData();

        return $instance;
    }

    public function getCountryName(): string
    {
        return $this->countryName;
    }
    public function getCurrencyCode(): string
    {
        return $this->currencyCode;
    }
    public function getImageUrl(): string
    {
        return $this->imageUrl;
    }

    /** @return ExchangeRateEntry[] */
    public function getRates(): array
    {
        return $this->rates;
    }

    /**
     * Return the buying rate for the highest denomination (first entry).
     */
    public function getBestBuyingRate(): ?float
    {
        return $this->rates[0]?->getBuyingRate();
    }

    /**
     * Return the selling rate for the highest denomination (first entry).
     */
    public function getBestSellingRate(): ?float
    {
        return $this->rates[0]?->getSellingRate();
    }

    /**
     * Find a rate entry by its denomination string (e.g. "100", "20 - 10").
     */
    public function findByDenomination(string $denom): ?ExchangeRateEntry
    {
        foreach ($this->rates as $entry) {
            if ($entry->getDenomination() === $denom) {
                return $entry;
            }
        }

        return null;
    }
}
