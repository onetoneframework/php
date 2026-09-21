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

/**
 * TossPayments class handles TossPayments API services including payment confirmation and cancellation.
 * This class provides methods for confirming payments and canceling payments
 * through TossPayments REST API using Basic authentication.
 */
class TossPayments
{

    /**
     * Base URL for TossPayments API.
     * @var string
     */
    private const BASE_URL = 'https://api.tosspayments.com';

    /**
     * Secret key for TossPayments API authentication.
     * @var string
     */
    private string $secretKey;

    /**
     * Constructor for TossPayments class.
     * Initializes the client with the TossPayments secret key.
     *
     * @param string $secretKey The secret key for TossPayments API.
     */
    public function __construct(string $secretKey)
    {
        $this->secretKey = $secretKey;
    }

    /**
     * Confirms a payment via TossPayments API.
     * Approves a pending payment using the payment key, order ID, and amount.
     *
     * @param string $paymentKey The TossPayments payment key.
     * @param string $orderId    The order ID associated with the payment.
     * @param int    $amount     The payment amount to confirm.
     * @return mixed The payment confirmation response data.
     */
    public function confirmPayment(string $paymentKey, string $orderId, int $amount): mixed
    {
        $fields = [
            'paymentKey' => $paymentKey,
            'orderId' => $orderId,
            'amount' => $amount,
        ];

        return $this->request('/v1/payments/confirm', $fields);
    }

    /**
     * Cancels a payment via TossPayments API.
     * Cancels a payment fully or partially using the payment key.
     *
     * @param string   $paymentKey    The TossPayments payment key.
     * @param string   $cancelReason  The reason for cancellation.
     * @param int|null $cancelAmount  The partial cancel amount (null for full cancellation).
     * @return mixed The payment cancellation response data.
     */
    public function cancelPayment(string $paymentKey, string $cancelReason, ?int $cancelAmount = null): mixed
    {
        $fields = ['cancelReason' => $cancelReason];

        if ($cancelAmount !== null) {
            $fields['cancelAmount'] = $cancelAmount;
        }

        return $this->request("/v1/payments/{$paymentKey}/cancel", $fields);
    }

    /**
     * Sends an HTTP request to the TossPayments API.
     * Handles authentication and executes the cURL request.
     *
     * @param string $path   The API endpoint path.
     * @param array  $fields The request body fields.
     * @return mixed The decoded API response data.
     */
    private function request(string $path, array $fields): mixed
    {
        $authHeader = 'Basic ' . base64_encode($this->secretKey . ':');
        $requestUrl = self::BASE_URL . $path;

        $cURL = new ClientURL($requestUrl);
        $cURL->option
            ->setURL($requestUrl)
            ->setContentTypeJSON()
            ->setHeader('Authorization', $authHeader)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setReturnTransfer()
            ->setPostMethod()
            ->setPostFields($fields);
        $response = $cURL->executeWithDecode();

        return $response;
    }

}
