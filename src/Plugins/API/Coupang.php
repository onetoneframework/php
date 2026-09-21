<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Plugin;

use Clover\Classes\Data\URLObject;
use Clover\Classes\ClientURL;
use function sprintf;
use function is_string;
use Exception;

/**
 * Coupang API client for interacting with Coupang platform
 * Handles authentication, product searches, orders, and shipping operations
 */
class Coupang
{
    private string $SECRET_KEY;
    private string $ACCESS_KEY;
    private string $VENDOR_ID;
    private const API_GATEWAY = "https://api-gateway.coupang.com";

    /**
     * Initialize Coupang API client with credentials
     */
    public function __construct(string $accessKey, string $secretKey, string $vendorId = "")
    {
        $this->ACCESS_KEY = $accessKey;
        $this->SECRET_KEY = $secretKey;
        $this->VENDOR_ID = $vendorId;
    }

    /**
     * Make an HTTP request to Coupang API
     *
     * @param string $method HTTP method (GET, POST, PUT, DELETE)
     * @param string $endpoint API endpoint path
     * @param array<string, mixed> $data Request payload
     * @param array<string, string> $queryParams Query parameters
     * @return array<string, mixed> API response
     * @throws Exception
     */
    public function request(string $method, string $endpoint, array $data = [], array $queryParams = []): array
    {
        try {
            $requestURL = new URLObject(self::API_GATEWAY);
            $requestURL->appendPath($endpoint);

            if (!empty($queryParams)) {
                foreach ($queryParams as $key => $value) {
                    $requestURL->setParameter($key, (string) $value);
                }
            }

            $cURL = new ClientURL($requestURL);
            $cURL->option->setURL($requestURL)
                ->setCustomMethod($method)
                ->setHeaders([
                    "Authorization" => $this->generateAuthorizationHeader($method, $endpoint),
                    "Content-Type" => "application/json",
                    "X-COUPANG-VENDOR-ID" => $this->VENDOR_ID
                ]);

            if (!empty($data)) {
                $cURL->option->setPostField(json_encode($data, JSON_UNESCAPED_UNICODE));
            }

            $response = $cURL->execute();
            $cURL->close();

            if (is_string($response)) {
                return json_decode($response, true, 512, JSON_THROW_ON_ERROR) ?? [];
            }

            return (array) $response;
        } catch (Exception $e) {
            throw new Exception("Coupang API request failed: " . $e->getMessage());
        }
    }

    /**
     * Generate CEA authorization header for request signing
     *
     * @param string $method HTTP method
     * @param string $endpoint API endpoint
     * @return string Authorization header value
     */
    public function generateAuthorizationHeader(string $method, string $endpoint): string
    {
        $timestamp = (int) (time() * 1000);
        $stringToSign = sprintf("%s\n%s\n%d", $method, $endpoint, $timestamp);
        $signature = hash_hmac('sha256', $stringToSign, $this->SECRET_KEY, true);
        $signatureEncoded = base64_encode($signature);

        return sprintf("CEA algorithm=HmacSHA256, access-key=%s, signed-date=%d, signature=%s", $this->ACCESS_KEY, $timestamp, $signatureEncoded);
    }

    /**
     * Search for products
     *
     * @param string $query Search keyword
     * @param int $pageNum Page number (default: 1)
     * @param int $pageSize Number of items per page (default: 20)
     * @return array<string, mixed> Search results
     */
    public function searchProducts(string $query, int $pageNum = 1, int $pageSize = 20): array
    {
        return $this->request("GET", "/v2/providers/seller/products", [], [
            "keyword" => $query,
            "pageNum" => $pageNum,
            "pageSize" => $pageSize
        ]);
    }

    /**
     * Get product details
     *
     * @param string $productId Product ID
     * @return array<string, mixed> Product information
     */
    public function getProduct(string $productId): array
    {
        return $this->request("GET", "/v2/providers/seller/products/{$productId}");
    }

    /**
     * Create a new product
     *
     * @param array<string, mixed> $productData Product information
     * @return array<string, mixed> Created product response
     */
    public function createProduct(array $productData): array
    {
        return $this->request("POST", "/v2/providers/seller/products", $productData);
    }

    /**
     * Update product information
     *
     * @param string $productId Product ID
     * @param array<string, mixed> $productData Updated product information
     * @return array<string, mixed> Update response
     */
    public function updateProduct(string $productId, array $productData): array
    {
        return $this->request("PUT", "/v2/providers/seller/products/{$productId}", $productData);
    }

    /**
     * Get open orders (unshipped)
     *
     * @param int $pageNum Page number
     * @param int $pageSize Number of items per page
     * @return array<string, mixed> Orders list
     */
    public function getOpenOrders(int $pageNum = 1, int $pageSize = 50): array
    {
        return $this->request("GET", "/v2/providers/seller/orders/open", [], [
            "pageNum" => $pageNum,
            "pageSize" => $pageSize
        ]);
    }

    /**
     * Get order details
     *
     * @param string $orderId Order ID
     * @return array<string, mixed> Order information
     */
    public function getOrder(string $orderId): array
    {
        return $this->request("GET", "/v2/providers/seller/orders/{$orderId}");
    }

    /**
     * Get shipment info for an order
     *
     * @param string $orderId Order ID
     * @return array<string, mixed> Shipment information
     */
    public function getShipmentInfo(string $orderId): array
    {
        return $this->request("GET", "/v2/providers/seller/orders/{$orderId}/shipments");
    }

    /**
     * Create shipment for an order
     *
     * @param string $orderId Order ID
     * @param array<string, mixed> $shipmentData Shipment information
     * @return array<string, mixed> Created shipment response
     */
    public function createShipment(string $orderId, array $shipmentData): array
    {
        return $this->request("POST", "/v2/providers/seller/orders/{$orderId}/shipments", $shipmentData);
    }

    /**
     * Cancel order
     *
     * @param string $orderId Order ID
     * @param string $reason Cancellation reason
     * @return array<string, mixed> Cancel response
     */
    public function cancelOrder(string $orderId, string $reason): array
    {
        return $this->request("POST", "/v2/providers/seller/orders/{$orderId}/cancel", [
            "reason" => $reason
        ]);
    }

    /**
     * Get sales statistics for a period
     *
     * @param string $startDate Start date (YYYY-MM-DD)
     * @param string $endDate End date (YYYY-MM-DD)
     * @return array<string, mixed> Sales statistics
     */
    public function getSalesStatistics(string $startDate, string $endDate): array
    {
        return $this->request("GET", "/v2/providers/seller/reports/sales", [], [
            "startDate" => $startDate,
            "endDate" => $endDate
        ]);
    }

    /**
     * Get inventory status for a product
     *
     * @param string $productId Product ID
     * @return array<string, mixed> Inventory information
     */
    public function getInventory(string $productId): array
    {
        return $this->request("GET", "/v2/providers/seller/products/{$productId}/inventory");
    }

    /**
     * Update product inventory
     *
     * @param string $productId Product ID
     * @param int $quantity Inventory quantity
     * @return array<string, mixed> Update response
     */
    public function updateInventory(string $productId, int $quantity): array
    {
        return $this->request("PUT", "/v2/providers/seller/products/{$productId}/inventory", [
            "quantity" => $quantity
        ]);
    }

    /**
     * Get shipping fees for a delivery
     *
     * @param array<string, mixed> $shippingData Shipping information
     * @return array<string, mixed> Shipping fee information
     */
    public function getShippingFee(array $shippingData): array
    {
        return $this->request("POST", "/v2/providers/seller/shipping/fees", $shippingData);
    }

    /**
     * Batch operation: Update multiple products
     *
     * @param array<string, mixed> $products Array of product updates
     * @return array<string, mixed> Batch operation response
     */
    public function batchUpdateProducts(array $products): array
    {
        return $this->request("POST", "/v2/providers/seller/products/batch-update", [
            "products" => $products
        ]);
    }
}
