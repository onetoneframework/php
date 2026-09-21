<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Plugin\API\SuperRich;

use Clover\Plugin\API\PublicDataInterface;
use DateTimeImmutable;

/**
 * Represents one denomination row inside an ExchangeRateCollection.
 *
 * Field mapping for cBuy* / cSell* columns:
 * ┌─────────┬────────────────────────────────────────┐
 * │ Field   │ Meaning                                │
 * ├─────────┼────────────────────────────────────────┤
 * │ cBuying │ Reference buying rate                  │
 * │ cSelling│ Reference selling rate                 │
 * │ cBuy1   │ Counter buy  – large denomination      │
 * │ cSell1  │ Counter sell – large denomination      │
 * │ cBuy2   │ Counter buy  – small denomination      │
 * │ cSell2  │ Counter sell – small denomination      │
 * │ cBuy3   │ TT (Telegraphic Transfer) buy          │
 * │ cSell3  │ TT sell (always null in this API)      │
 * │ cBuy4   │ Premium buy rate                       │
 * │ cSell4  │ Premium sell rate                      │
 * │ cBuy5   │ Preferential buy rate                  │
 * │ cSell5  │ Preferential sell rate                 │
 * │ cBuy6   │ Bank card buy rate                     │
 * │ cSell6  │ Bank card sell rate                    │
 * │ cBuy7   │ Traveler's cheque buy rate             │
 * │ cSell7  │ Traveler's cheque sell rate            │
 * └─────────┴────────────────────────────────────────┘
 */
final class ExchangeRateEntry implements PublicDataInterface
{
    public function __construct(
        // Currency identifiers
        private string $currencyCode,
        private string $currencySubCode,

        // Reference rates
        private float $buyingRate,
        private float $sellingRate,

        // Counter rates
        private float $counterBuyLarge,
        private float $counterSellLarge,
        private float $counterBuySmall,
        private float $counterSellSmall,

        // TT (Telegraphic Transfer)
        private float $ttBuy,
        private ?float $ttSell,

        // Premium rates
        private float $premiumBuy,
        private float $premiumSell,

        // Preferential rates
        private float $preferentialBuy,
        private float $preferentialSell,

        // Bank card rates
        private float $bankCardBuy,
        private float $bankCardSell,

        // Traveler's cheque rates
        private float $travelerCheckBuy,
        private float $travelerCheckSell,

        // Metadata
        private string $denomination,
        private int $rateDigit,
        private ?DateTimeImmutable $dateTime,
    ) {
    }

    /**
     * Build an instance from a raw stdClass object produced by the API decoder.
     */
    public static function from(mixed $key = null, mixed $data = null): self
    {
        $raw = $data ?? $key;

        return new self(
            currencyCode: (string) ($raw->cCode ?? ''),
            currencySubCode: (string) ($raw->curcode ?? ''),

            buyingRate: (float) ($raw->cBuying ?? 0.0),
            sellingRate: (float) ($raw->cSelling ?? 0.0),

            counterBuyLarge: (float) ($raw->cBuy1 ?? 0.0),
            counterSellLarge: (float) ($raw->cSell1 ?? 0.0),

            counterBuySmall: (float) ($raw->cBuy2 ?? 0.0),
            counterSellSmall: (float) ($raw->cSell2 ?? 0.0),

            ttBuy: (float) ($raw->cBuy3 ?? 0.0),
            // cSell3 is always null in this API; stored as nullable float
            ttSell: isset($raw->cSell3) && $raw->cSell3 !== null
            ? (float) $raw->cSell3
            : null,

            premiumBuy: (float) ($raw->cBuy4 ?? 0.0),
            premiumSell: (float) ($raw->cSell4 ?? 0.0),

            preferentialBuy: (float) ($raw->cBuy5 ?? 0.0),
            preferentialSell: (float) ($raw->cSell5 ?? 0.0),

            bankCardBuy: (float) ($raw->cBuy6 ?? 0.0),
            bankCardSell: (float) ($raw->cSell6 ?? 0.0),

            travelerCheckBuy: (float) ($raw->cBuy7 ?? 0.0),
            travelerCheckSell: (float) ($raw->cSell7 ?? 0.0),

            denomination: trim((string) ($raw->denom ?? '')),
            rateDigit: (int) ($raw->rateDigit ?? 2),
            dateTime: self::parseDateTime((string) ($raw->dateTime ?? '')),
        );
    }

    public function getCurrencyCode(): string
    {
        return $this->currencyCode;
    }
    public function getCurrencySubCode(): string
    {
        return $this->currencySubCode;
    }

    public function getBuyingRate(): float
    {
        return $this->buyingRate;
    }
    public function getSellingRate(): float
    {
        return $this->sellingRate;
    }

    /** @param bool $small  true = small denomination, false = large denomination */
    public function getCounterBuyRate(bool $small = false): float
    {
        return $small ? $this->counterBuySmall : $this->counterBuyLarge;
    }

    /** @param bool $small  true = small denomination, false = large denomination */
    public function getCounterSellRate(bool $small = false): float
    {
        return $small ? $this->counterSellSmall : $this->counterSellLarge;
    }

    public function getTtBuyRate(): float
    {
        return $this->ttBuy;
    }
    public function getTtSellRate(): ?float
    {
        return $this->ttSell;
    }
    public function getPremiumBuyRate(): float
    {
        return $this->premiumBuy;
    }
    public function getPremiumSellRate(): float
    {
        return $this->premiumSell;
    }
    public function getPreferentialBuyRate(): float
    {
        return $this->preferentialBuy;
    }
    public function getPreferentialSellRate(): float
    {
        return $this->preferentialSell;
    }
    public function getBankCardBuyRate(): float
    {
        return $this->bankCardBuy;
    }
    public function getBankCardSellRate(): float
    {
        return $this->bankCardSell;
    }
    public function getTravelerCheckBuyRate(): float
    {
        return $this->travelerCheckBuy;
    }
    public function getTravelerCheckSellRate(): float
    {
        return $this->travelerCheckSell;
    }

    public function getDenomination(): string
    {
        return $this->denomination;
    }
    public function getRateDigit(): int
    {
        return $this->rateDigit;
    }
    public function getDateTime(): ?DateTimeImmutable
    {
        return $this->dateTime;
    }

    /**
     * Format a rate value using the precision defined by rateDigit.
     */
    public function formatRate(float $rate, ?int $digits = null): string
    {
        return number_format($rate, $digits ?? $this->rateDigit, '.', '');
    }

    private static function parseDateTime(string $value): ?DateTimeImmutable
    {
        if ($value === '') {
            return null;
        }

        $dt = DateTimeImmutable::createFromFormat(DateTimeImmutable::RFC3339_EXTENDED, $value);

        return $dt !== false ? $dt : null;
    }
}
