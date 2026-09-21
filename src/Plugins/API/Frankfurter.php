<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Plugin;

use Clover\Classes\ClientURL;
use Clover\Classes\Data\ArrayObject;
use Clover\Classes\Data\URLObject;
use Clover\Plugin\API\Frankfurter\ExchangeRateQuote;
use Clover\Plugin\API\Frankfurter\FrankfurterFetchResult;
use Clover\Plugin\API\PublicDataResponse;
use function is_string;
use function is_array;
use function count;

/**
 * Frankfurter (ECB-based) exchange rates over HTTP — no API key.
 *
 * Use {@see self::fetchFromUrl()} with any full URL, or convenience helpers that
 * default to `https://api.frankfurter.app` (same JSON shape as documented at
 * https://www.frankfurter.app/docs/ ).
 *
 * @package Clover\Plugin
 * @link https://www.frankfurter.app/
 */
final class Frankfurter
{
    public const DEFAULT_BASE_URL = 'https://api.frankfurter.app';

    public function __construct(
        private string $apiBaseUrl = self::DEFAULT_BASE_URL,
    ) {
    }

    /**
     * GET a Frankfurter-compatible JSON document and map `rates` into {@see PublicDataResponse}.
     *
     * Expected JSON keys: `base`, `date`, `amount` (optional), `rates` (object of code => number).
     */
    public function fetchFromUrl(string $url): FrankfurterFetchResult
    {
        $requestUrl = new URLObject($url);
        $cURL = new ClientURL($requestUrl);
        $cURL->option
            ->setURL($requestUrl)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setReturnTransfer()
            ->setGetMethod()
            ->setFollowRedirects(true);

        $decoded = $cURL->executeWithDecode();
        $httpStatus = $cURL->getLastHttpCode();

        $payload = $this->payloadToArray($decoded);

        $ratesRaw = $payload['rates'] ?? [];
        if (!is_array($ratesRaw)) {
            $ratesRaw = [];
        }

        $stack = [];
        foreach ($ratesRaw as $currency => $rate) {
            $code = (string) $currency;
            $stack[$code] = [
                'currency' => $code,
                'rate' => (float) $rate,
            ];
        }

        $data = new PublicDataResponse();
        $data->setStack($stack);
        $data->setStatusCode((string) $httpStatus);
        if (isset($payload['message']) && is_string($payload['message'])) {
            $data->setStatusMessage($payload['message']);
        }
        $data->setData(ExchangeRateQuote::class);

        $base = isset($payload['base']) && is_string($payload['base']) ? $payload['base'] : '';
        $date = isset($payload['date']) && is_string($payload['date']) ? $payload['date'] : '';
        $amount = isset($payload['amount']) ? (float) $payload['amount'] : 1.0;

        return new FrankfurterFetchResult($data, $base, $date, $amount, $httpStatus);
    }

    /**
     * Latest rates: `GET {base}/latest?from=…&to=…` (omit `to` for all supported currencies).
     */
    public function getLatest(string $from = 'USD', ?string $toCommaSeparated = null): FrankfurterFetchResult
    {
        $query = array_filter(
            [
                'from' => $from,
                'to' => $toCommaSeparated,
            ],
            static fn($v) => $v !== null && $v !== ''
        );

        $url = rtrim($this->apiBaseUrl, '/') . '/latest' . (count($query) > 0 ? '?' . http_build_query($query) : '');

        return $this->fetchFromUrl($url);
    }

    /**
     * Historical rates for a calendar date (`YYYY-MM-DD`).
     */
    public function getForDate(string $date, string $from = 'USD', ?string $toCommaSeparated = null): FrankfurterFetchResult
    {
        $query = array_filter(
            [
                'from' => $from,
                'to' => $toCommaSeparated,
            ],
            static fn($v) => $v !== null && $v !== ''
        );

        $path = rawurlencode($date);
        $url = rtrim($this->apiBaseUrl, '/') . '/' . $path . (count($query) > 0 ? '?' . http_build_query($query) : '');

        return $this->fetchFromUrl($url);
    }

    /**
     * @param mixed $decoded Result of {@see ClientURL::executeWithDecode()}
     *
     * @return array<string, mixed>
     */
    private function payloadToArray(mixed $decoded): array
    {
        if ($decoded instanceof ArrayObject) {
            $raw = $decoded->toPHPObject();

            return is_array($raw) ? $raw : [];
        }

        if (is_array($decoded)) {
            return $decoded;
        }

        return [];
    }
}
