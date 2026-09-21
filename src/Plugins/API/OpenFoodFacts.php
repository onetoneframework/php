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
use function is_array;
use function count;
use function implode;
use function rawurlencode;
use function http_build_query;
use function rtrim;

/**
 * Open Food Facts product search over HTTP — no API key required.
 *
 * Use {@see self::fetchFromUrl()} with any full URL, or convenience helpers that
 * default to `https://world.openfoodfacts.org` (documented at
 * https://wiki.openfoodfacts.org/API ).
 *
 * @package Clover\Plugin
 * @link https://world.openfoodfacts.org/
 */
final class OpenFoodFacts
{
    public const DEFAULT_BASE_URL = 'https://world.openfoodfacts.org';
    private const SEARCH_PATH = '/cgi/search.pl';
    private const PRODUCT_SEARCH_PATH = '/api/v2/product';

    private const QUERY_SEARCH_TERMS = 'search_terms';
    private const QUERY_FIELDS = 'fields';
    private const QUERY_JSON = 'json';
    private const QUERY_JSON_VALUE = 'true';

    private const RETURN_FIELDS = [
        'code',
        'brands',
        'product_name',
        'product_name_en',
        'product_name_de',
        'product_name_fr',
        'url',
        'image_url',
        'image_front_thumb_url',
        'image_front_url',
        'product_quantity',
        'quantity',
        'serving_quantity',
        'serving_size',
        'nutriments',
    ];

    public function __construct(
        private string $apiBaseUrl = self::DEFAULT_BASE_URL,
    ) {
    }

    /**
     * GET an Open Food Facts-compatible JSON document and map `products` or `product`.
     *
     * Expected JSON keys (search): `count`, `products` (array).
     * Expected JSON keys (barcode): `status`, `product` (object).
     * 
     * @param string $url Full URL to fetch, including query parameters.
     */
    public function fetchFromUrl(string $url)
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

        // Barcode lookup returns a single `product` object.
        // Word search returns a `products` array.
        $isSingleProduct = isset($payload['product']) && is_array($payload['product']);
        $rawProducts = $isSingleProduct
            ? [$payload['product']]
            : ($payload['products'] ?? []);

        if (!is_array($rawProducts)) {
            $rawProducts = [];
        }

        $stack = [];
        foreach ($rawProducts as $product) {
            $code = (string) ($product['code'] ?? '');
            if ($code === '') {
                continue;
            }
            $stack[$code] = $product;
        }

        $totalCount = isset($payload['count']) ? (int) $payload['count'] : count($stack);

        return [$stack, $totalCount, $httpStatus];
    }

    /**
     * Search products by keyword.
     *
     * Calls `GET {base}/cgi/search.pl?search_terms=…&fields=…&json=true`
     *
     * @param string $searchString Free-text search query.
     */
    public function searchByWord(string $searchString)
    {
        $query = [
            self::QUERY_SEARCH_TERMS => $searchString,
            self::QUERY_FIELDS => implode(',', self::RETURN_FIELDS),
            self::QUERY_JSON => self::QUERY_JSON_VALUE,
        ];

        $url = rtrim($this->apiBaseUrl, '/') . self::SEARCH_PATH . '?' . http_build_query($query);

        return $this->fetchFromUrl($url);
    }

    /**
     * Look up a single product by barcode (EAN-13, UPC-A, etc.).
     *
     * Calls `GET {base}/api/v2/product/{barcode}?fields=…&json=true`
     *
     * @param string $barcode Product barcode string.
     */
    public function searchByBarcode(string $barcode)
    {
        $query = [
            self::QUERY_FIELDS => implode(',', self::RETURN_FIELDS),
            self::QUERY_JSON => self::QUERY_JSON_VALUE,
        ];

        $url = rtrim($this->apiBaseUrl, '/') . self::PRODUCT_SEARCH_PATH . '/' . rawurlencode($barcode) . '?' . http_build_query($query);

        return $this->fetchFromUrl($url);
    }

    /**
     * Convert the decoded cURL response into an array of products.
     * 
     * @param mixed $decoded Result of {@see ClientURL::executeWithDecode()}
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
