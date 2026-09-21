<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Plugin\API\Duffel;

use Clover\Plugin\API\PublicDataInterface;
use DateTimeImmutable;

/**
 * A normalized, currency-converted representation of a single Duffel offer slice.
 * Equivalent to Python's NormalizedOffer Pydantic schema.
 *
 * Baggage field logic:
 *   - checked baggage is assumed to be 20 kg when at least one checked-bag
 *     allowance exists in the offer; otherwise 0.
 *
 * Return-leg fields (returnAt / returnArrivalAt):
 *   - Populated only when the source offer contains more than one slice
 *     (i.e. round-trip offers searched via searchRoundtrip).
 *   - Always null for one-way offers.
 */
final class NormalizedOffer implements PublicDataInterface
{
    public function __construct(
        // Offer identity
        private string $offerId,

        // Carrier
        private string $carrier,
        private string $carrierIata,

        // Route
        private string $departureIata,
        private string $arrivalIata,

        // Outbound timing
        private DateTimeImmutable $departureAt,
        private DateTimeImmutable $arrivalAt,
        private int $durationMinutes,
        private int $stops,

        // Baggage
        private int $baggageCheckedKg,

        // Price (always KRW)
        private int $totalKrw,

        // Return leg (null for one-way)
        private ?DateTimeImmutable $returnAt,
        private ?DateTimeImmutable $returnArrivalAt,
    ) {
    }

    /**
     * Build a NormalizedOffer from a raw Duffel offer stdClass object.
     * Equivalent to Python's _normalize_slice().
     *
     * @param mixed     $key       Ignored (index position); kept for interface compatibility.
     * @param mixed     $data      Raw stdClass offer object from the Duffel API.
     * @param int       $sliceIdx  Which slice to read (0 = outbound).
     */
    public static function from(mixed $key = null, mixed $data = null, int $sliceIdx = 0): self
    {
        $raw    = $data ?? $key;
        $slice  = $raw->slices[$sliceIdx];
        $segs   = $slice->segments;
        $first  = $segs[0];
        $last   = $segs[count($segs) - 1];

        // Checked-baggage allowance: 20 kg when quantity > 0, else 0.
        $baggages   = (array) (($raw->passengers[0] ?? new \stdClass())->baggages ?? []);
        $checked    = self::findBaggage($baggages, 'checked');
        $checkedKg  = ($checked !== null && (int) ($checked->quantity ?? 0) > 0) ? 20 : 0;

        // Return-leg timing (only present in round-trip offers, slice index > 0 exists).
        $returnAt        = null;
        $returnArrivalAt = null;

        if ($sliceIdx === 0 && count($raw->slices) > 1) {
            $retSegs         = $raw->slices[1]->segments;
            $returnAt        = new DateTimeImmutable($retSegs[0]->departing_at);
            $returnArrivalAt = new DateTimeImmutable($retSegs[count($retSegs) - 1]->arriving_at);
        }

        return new self(
            offerId:         (string) ($raw->id ?? ''),
            carrier:         (string) ($first->operating_carrier->name      ?? ''),
            carrierIata:     (string) ($first->operating_carrier->iata_code ?? ''),
            departureIata:   (string) ($first->origin->iata_code            ?? ''),
            arrivalIata:     (string) ($last->destination->iata_code        ?? ''),
            departureAt:     new DateTimeImmutable((string) $first->departing_at),
            arrivalAt:       new DateTimeImmutable((string) $last->arriving_at),
            durationMinutes: self::parseDurationMinutes((string) ($slice->duration ?? 'PT0M')),
            stops:           count($segs) - 1,
            baggageCheckedKg: $checkedKg,
            totalKrw:        self::toKrw(
                                 (string) ($raw->total_amount   ?? '0'),
                                 (string) ($raw->total_currency ?? 'KRW'),
                             ),
            returnAt:        $returnAt,
            returnArrivalAt: $returnArrivalAt,
        );
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    public function getOfferId(): string             { return $this->offerId; }
    public function getCarrier(): string             { return $this->carrier; }
    public function getCarrierIata(): string         { return $this->carrierIata; }
    public function getDepartureIata(): string       { return $this->departureIata; }
    public function getArrivalIata(): string         { return $this->arrivalIata; }
    public function getDepartureAt(): DateTimeImmutable { return $this->departureAt; }
    public function getArrivalAt(): DateTimeImmutable   { return $this->arrivalAt; }
    public function getDurationMinutes(): int        { return $this->durationMinutes; }
    public function getStops(): int                  { return $this->stops; }
    public function getBaggageCheckedKg(): int       { return $this->baggageCheckedKg; }
    public function getTotalKrw(): int               { return $this->totalKrw; }
    public function getReturnAt(): ?DateTimeImmutable      { return $this->returnAt; }
    public function getReturnArrivalAt(): ?DateTimeImmutable { return $this->returnArrivalAt; }

    /** Return true when this offer represents a round trip. */
    public function isRoundtrip(): bool
    {
        return $this->returnAt !== null;
    }

    /** Return true when the flight is non-stop. */
    public function isDirect(): bool
    {
        return $this->stops === 0;
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    /**
     * Parse an ISO 8601 duration string into total minutes.
     * Equivalent to Python's _parse_duration_minutes().
     *
     * Examples: "PT2H30M" → 150,  "PT45M" → 45,  "PT1H" → 60
     */
    private static function parseDurationMinutes(string $duration): int
    {
        if (!preg_match('/^PT(?:(\d+)H)?(?:(\d+)M)?$/', $duration, $m)) {
            return 0;
        }

        return (int) ($m[1] ?? 0) * 60 + (int) ($m[2] ?? 0);
    }

    /**
     * Convert an amount string in the given currency to KRW.
     * Uses the same fixed approximate rates as the Python implementation.
     * Equivalent to Python's _to_krw().
     */
    private static function toKrw(string $amount, string $currency): int
    {
        $rates = ['USD' => 1380.0, 'JPY' => 9.2, 'EUR' => 1500.0];
        $value = (float) $amount;

        if ($currency === 'KRW') {
            return (int) $value;
        }

        return (int) ($value * ($rates[$currency] ?? 1.0));
    }

    /**
     * Find the first baggage entry with the given type from a baggage array.
     *
     * @param  array<int, mixed> $baggages
     */
    private static function findBaggage(array $baggages, string $type): ?object
    {
        foreach ($baggages as $bag) {
            if (($bag->type ?? '') === $type) {
                return $bag;
            }
        }

        return null;
    }
}
