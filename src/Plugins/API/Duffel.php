<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Plugin;

use Clover\Plugin\API\Duffel\NormalizedOffer;
use Clover\Plugin\API\PublicDataResponse;
use RuntimeException;
use function count;
use function sprintf;

final class Duffel
{
    public const BASE_URL = 'https://api.duffel.com';

    /** Korean airport IATA codes supported by this integration. */
    public const SUPPORTED_KR = ['ICN', 'GMP'];

    /** Japanese airport IATA codes supported by this integration. */
    public const SUPPORTED_JP = ['KIX', 'NRT', 'HND', 'FUK', 'CTS', 'KMI'];

    /** Max retry attempts on transient failures (5xx / 429 / network errors). */
    private const MAX_RETRIES = 3;

    /** Approximate FX rates used when the API returns a non-KRW price. */
    private const APPROX_RATES = [
        'USD' => 1380.0,
        'JPY' => 9.2,
        'EUR' => 1500.0,
    ];

    public function __construct(private readonly string $apiKey)
    {
    }

    /**
     * Search for round-trip offers and return a typed PublicDataResponse
     * mapped to NormalizedOffer entries.
     */
    public function searchRoundtrip(string $origin, string $destination, string $depart, string $ret, int $adults = 1): PublicDataResponse
    {
        $requestId = $this->createOfferRequest($origin, $destination, $depart, $ret, $adults);
        $raw = $this->listOffers($requestId);

        $data = new PublicDataResponse();
        $data->setStack($raw);
        $data->setStatusCode(200);
        $data->setStatusMessage('OK');
        $data->setData(NormalizedOffer::class);

        return $data;
    }

    /**
     * Search for one-way offers and return a typed PublicDataResponse
     * mapped to NormalizedOffer entries.
     */
    public function searchOneway(string $origin, string $destination, string $depart): PublicDataResponse
    {
        $requestId = $this->createOfferRequest($origin, $destination, $depart, null);
        $raw = $this->listOffers($requestId);

        $data = new PublicDataResponse();
        $data->setStack($raw);
        $data->setStatusCode(200);
        $data->setStatusMessage('OK');
        $data->setData(NormalizedOffer::class);

        return $data;
    }

    /**
     * Fetch outbound and inbound one-way offers in parallel using curl_multi.
     * Equivalent to Python's search_oneway_pair() with asyncio.gather().
     *
     * @return array{outbound: PublicDataResponse, inbound: PublicDataResponse}
     */
    public function searchOnewayPair(string $origin, string $destination, string $depart, string $ret, int $adults = 1): array
    {
        // Build both offer-request payloads and dispatch them in parallel.
        [$outRequestId, $inRequestId] = $this->parallelOfferRequests([
            ['origin' => $origin, 'destination' => $destination, 'date' => $depart, 'adults' => $adults],
            ['origin' => $destination, 'destination' => $origin, 'date' => $ret, 'adults' => $adults],
        ]);

        // Fetch offer lists for both request IDs in parallel.
        [$outRaw, $inRaw] = $this->parallelListOffers([$outRequestId, $inRequestId]);

        $outbound = new PublicDataResponse();
        $outbound->setStack($outRaw);
        $outbound->setStatusCode(200);
        $outbound->setStatusMessage('OK');
        $outbound->setData(NormalizedOffer::class);

        $inbound = new PublicDataResponse();
        $inbound->setStack($inRaw);
        $inbound->setStatusCode(200);
        $inbound->setStatusMessage('OK');
        $inbound->setData(NormalizedOffer::class);

        return ['outbound' => $outbound, 'inbound' => $inbound];
    }

    /**
     * Return the live KRW price for a single offer, or null if not found (404).
     */
    public function getOfferPrice(string $offerId): ?int
    {
        $response = $this->withRetry(
            fn() => $this->httpGet(self::BASE_URL . "/air/offers/{$offerId}")
        );

        if ($response === null) {
            return null; // 404
        }

        $data = json_decode($response, false);

        return $this->toKrw(
            (string) ($data->data->total_amount ?? '0'),
            (string) ($data->data->total_currency ?? 'KRW'),
        );
    }

    /**
     * POST /air/offer_requests and return the new offer-request ID.
     */
    private function createOfferRequest(string $origin, string $destination, string $departDate, ?string $returnDate, int $adults = 1): string
    {
        $slices = [
            ['origin' => $origin, 'destination' => $destination, 'departure_date' => $departDate],
        ];

        if ($returnDate !== null) {
            $slices[] = [
                'origin' => $destination,
                'destination' => $origin,
                'departure_date' => $returnDate,
            ];
        }

        $payload = json_encode([
            'data' => [
                'slices' => $slices,
                'passengers' => array_fill(0, $adults, ['type' => 'adult']),
                'cabin_class' => 'economy',
            ],
        ]);

        $raw = $this->withRetry(fn() => $this->httpPost(self::BASE_URL . '/air/offer_requests', $payload));
        $body = json_decode($raw, false);

        return (string) $body->data->id;
    }

    /**
     * GET /air/offers?offer_request_id=...&sort=total_amount and return raw array.
     *
     * @return array<int, mixed>
     */
    private function listOffers(string $offerRequestId, int $limit = 50): array
    {
        $url = self::BASE_URL . '/air/offers?' . http_build_query([
            'offer_request_id' => $offerRequestId,
            'sort' => 'total_amount',
            'limit' => $limit,
        ]);

        $raw = $this->withRetry(fn() => $this->httpGet($url));
        $body = json_decode($raw, false);

        return (array) ($body->data ?? []);
    }

    /**
     * Fire multiple offer-request POSTs in parallel and return their IDs
     * in the same order as the input.
     *
     * @param  array<int, array{origin: string, destination: string, date: string, adults: int}> $requests
     * @return string[]
     */
    private function parallelOfferRequests(array $requests): array
    {
        $payloads = [];

        foreach ($requests as $req) {
            $payloads[] = json_encode([
                'data' => [
                    'slices' => [
                        [
                            'origin' => $req['origin'],
                            'destination' => $req['destination'],
                            'departure_date' => $req['date'],
                        ]
                    ],
                    'passengers' => array_fill(0, $req['adults'], ['type' => 'adult']),
                    'cabin_class' => 'economy',
                ],
            ]);
        }

        $bodies = $this->curlMultiPost(
            array_fill(0, count($payloads), self::BASE_URL . '/air/offer_requests'),
            $payloads,
        );

        return array_map(static function (string $raw): string {
            $body = json_decode($raw, false);
            return (string) $body->data->id;
        }, $bodies);
    }

    /**
     * Fire multiple offer-list GETs in parallel and return raw data arrays
     * in the same order as the input request IDs.
     *
     * @param  string[] $requestIds
     * @return array<int, array<int, mixed>>
     */
    private function parallelListOffers(array $requestIds, int $limit = 50): array
    {
        $urls = array_map(static function (string $id) use ($limit): string {
            return self::BASE_URL . '/air/offers?' . http_build_query([
                'offer_request_id' => $id,
                'sort' => 'total_amount',
                'limit' => $limit,
            ]);
        }, $requestIds);

        $bodies = $this->curlMultiGet($urls);

        return array_map(static function (string $raw): array {
            $body = json_decode($raw, false);
            return (array) ($body->data ?? []);
        }, $bodies);
    }

    /**
     * Execute a GET request and return the raw response body.
     * Returns null on HTTP 404; throws on other non-2xx codes.
     */
    private function httpGet(string $url): ?string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $this->buildCurlHeaders(),
            CURLOPT_TIMEOUT => 30,
        ]);

        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($status === 404) {
            return null;
        }

        $this->assertSuccessStatus($status, (string) $body);

        return (string) $body;
    }

    /**
     * Execute a POST request and return the raw response body.
     */
    private function httpPost(string $url, string $jsonPayload): string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $jsonPayload,
            CURLOPT_HTTPHEADER => $this->buildCurlHeaders(),
            CURLOPT_TIMEOUT => 30,
        ]);

        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $this->assertSuccessStatus($status, (string) $body);

        return (string) $body;
    }

    /**
     * Fire multiple GET requests in parallel and return response bodies
     * in the same order as the input URLs.
     *
     * @param  string[] $urls
     * @return string[]
     */
    private function curlMultiGet(array $urls): array
    {
        return $this->curlMultiExec($urls, static function (string $url, \CurlHandle $ch): void {
            curl_setopt($ch, CURLOPT_URL, $url);
        });
    }

    /**
     * Fire multiple POST requests in parallel and return response bodies
     * in the same order as the input URLs.
     *
     * @param  string[] $urls
     * @param  string[] $payloads  JSON strings, one per URL.
     * @return string[]
     */
    private function curlMultiPost(array $urls, array $payloads): array
    {
        return $this->curlMultiExec($urls, static function (string $url, \CurlHandle $ch) use ($payloads, $urls): void {
            $idx = array_search($url, $urls, true);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payloads[$idx]);
        });
    }

    /**
     * Generic curl_multi runner. Applies $configure callback to each handle,
     * then executes all requests in parallel.
     *
     * @param  string[]                              $urls
     * @param  callable(string, \CurlHandle): void  $configure
     * @return string[]
     */
    private function curlMultiExec(array $urls, callable $configure): array
    {
        $mh = curl_multi_init();
        $handles = [];

        foreach ($urls as $url) {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $this->buildCurlHeaders(),
                CURLOPT_TIMEOUT => 30,
            ]);
            $configure($url, $ch);
            curl_multi_add_handle($mh, $ch);
            $handles[] = $ch;
        }

        // Run until all transfers are complete.
        do {
            $status = curl_multi_exec($mh, $active);
            if ($active) {
                curl_multi_select($mh);
            }
        } while ($active && $status === CURLM_OK);

        $bodies = [];
        foreach ($handles as $ch) {
            $bodies[] = (string) curl_multi_getcontent($ch);
            curl_multi_remove_handle($mh, $ch);
        }

        curl_multi_close($mh);

        return $bodies;
    }

    /**
     * Wrap a callable with exponential-backoff retry logic.
     * Retries on: network errors, HTTP 429, HTTP 5xx.
     * Equivalent to Python's @_retry decorator using tenacity.
     *
     * @template T
     * @param  callable(): T $fn
     * @return T
     */
    private function withRetry(callable $fn): mixed
    {
        $attempt = 0;

        while (true) {
            try {
                return $fn();
            } catch (\Throwable $e) {
                $attempt++;

                if ($attempt >= self::MAX_RETRIES || !$this->isRetryable($e)) {
                    throw $e;
                }

                // Exponential backoff: 0.5 s → 1 s → 2 s (capped at 4 s).
                $delay = min(0.5 * (2 ** ($attempt - 1)), 4.0);
                usleep((int) ($delay * 1_000_000));
            }
        }
    }

    /**
     * Determine whether an exception warrants a retry attempt.
     * Equivalent to Python's _is_retryable().
     */
    private function isRetryable(\Throwable $e): bool
    {
        if ($e instanceof RuntimeException) {
            $code = $e->getCode();
            return $code === 429 || $code >= 500;
        }

        // Treat curl / network-level errors as retryable.
        return str_contains($e->getMessage(), 'cURL error');
    }

    /**
     * Return default Duffel API headers as a flat array suitable for curl.
     *
     * @return string[]
     */
    private function buildCurlHeaders(): array
    {
        return [
            "Authorization: Bearer {$this->apiKey}",
            'Duffel-Version: v2',
            'Content-Type: application/json',
            'Accept-Encoding: gzip',
        ];
    }

    /**
     * Convert an amount string in the given currency to KRW using fixed
     * approximate rates. Equivalent to Python's _to_krw().
     */
    private function toKrw(string $amount, string $currency): int
    {
        $value = (float) $amount;

        if ($currency === 'KRW') {
            return (int) $value;
        }

        $rate = self::APPROX_RATES[$currency] ?? 1.0;

        return (int) ($value * $rate);
    }

    /**
     * Throw a RuntimeException if the HTTP status code indicates failure.
     * The exception code is set to the HTTP status so isRetryable() can inspect it.
     */
    private function assertSuccessStatus(int $status, string $body): void
    {
        if ($status >= 200 && $status < 300) {
            return;
        }

        throw new RuntimeException(sprintf('Duffel API error [HTTP %d]: %s', $status, $body), $status);
    }
}
