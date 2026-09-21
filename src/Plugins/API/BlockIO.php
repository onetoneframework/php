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
use Clover\Classes\Data\URLObject;
use Exception;

/**
 * BlockIO class handles Bitcoin wallet operations using the Block.io API.
 * This class provides methods for getting exchange rates, sending Bitcoin transactions,
 * and creating new wallet addresses through the Block.io service.
 */
class BlockIO
{

    /**
     * API key for authenticating with Block.io service.
     * @var string
     */
    private string $API_KEY;

    /**
     * Constructor for BlockIO class.
     * Initializes the Block.io client with the provided API key.
     *
     * @param string $apiKey The API key for Block.io authentication.
     */
    public function __construct(string $apiKey)
    {
        $this->API_KEY = $apiKey;
    }

    /**
     * Retrieves the current Bitcoin exchange rate.
     * Converts a given currency amount to Bitcoin or gets the current rate.
     *
     * @param string $currency The target currency code (default: 'KRW').
     * @param string|null $value The amount to convert (optional).
     * @return mixed The exchange rate or converted amount.
     */
    public function getBitcoinRate(string $currency = 'KRW', ?string $value = null): mixed
    {
        $queries = [
            'currency' => $currency,
            'value' => $value
        ];

        $requestUrl = new URLObject('https://www.blockchain.info/ko/tobtc');
        $requestUrl->setQueryString($queries);

        $cURL = new ClientURL($requestUrl);
        $cURL->option
            ->setURL($requestUrl)
            ->setSSLVerifyPeer(false)
            ->setFollowRedirects()
            ->setSSLVerifyHost(false)
            ->setReturnTransfer()
            ->setGetMethod();
        $response = $cURL->executeWithDecode();

        return $response;
    }

    /**
     * Sends Bitcoin from a wallet to specified addresses.
     * Validates balance and performs the withdrawal transaction.
     *
     * @param string $point The amount in points/currency to send.
     * @param string $bitcoin The amount in Bitcoin to send.
     * @param string $payment_address The source wallet address.
     * @param string $to_addresses The destination addresses.
     * @param string $secret_pin The secret PIN for transaction authorization.
     * 
     * @throws Exception Exception on failure or success indicator.
     * @return mixed
     */
    public function sendBitcoinWallet(string $point, string $bitcoin, string $payment_address, string $to_addresses, string $secret_pin): mixed
    {
        $queries = [
            'api_key' => $this->API_KEY,
            'addresses' => $payment_address
        ];

        $requestUrl = new URLObject('https://block.io/api/v2/get_address_balance/');
        $requestUrl->setQueryString($queries);

        $cURL = new ClientURL($requestUrl);
        $cURL->option
            ->setURL($requestUrl)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setReturnTransfer()
            ->setFollowRedirects()
            ->setGetMethod();
        $response = $cURL->executeWithDecode();

        $response_data = $response['data'];
        $status = $response['status'];
        if ($status != "success") {
            throw new Exception('Failed to balance of address');
        } else {
            $available_balance = ($response_data['available_balance']);
        }

        $amount = $point ? $this->getBitcoinRate($point) : $bitcoin;
        bcscale(8);
        if (!bccomp($available_balance, $amount, 8) === 1) {
            throw new Exception('Payment amount is not valid');
        }

        $queries = [
            'api_key' => $this->API_KEY,
            'amounts' => $amount,
            'from_addresses' => $payment_address,
            'to_addresses' => $to_addresses,
            'pin' => $secret_pin
        ];

        $requestUrl = new URLObject('https://block.io/api/v2/withdraw_from_addresses/');
        $requestUrl->setQueryString($queries);

        $cURL = new ClientURL($requestUrl);
        $cURL->option
            ->setURL($requestUrl)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setReturnTransfer()
            ->setFollowRedirects()
            ->setGetMethod();
        $response = $cURL->executeWithDecode();

        $response_data = $response['data'];
        $status = $response['status'];
        if ($status != "success") {
            return new Exception($response['error_message']);
        }

        return $response_data;
    }

    /**
     * Generates a new Bitcoin wallet address.
     * Creates a new address associated with the API key.
     *
     * @throws Exception
     * 
     * @return mixed The new wallet address or an Exception on failure.
     */
    public function getNewBitcoinWallet(): mixed
    {
        $queries = [
            'api_key' => $this->API_KEY,
        ];

        $requestUrl = new URLObject('https://block.io/api/v2/get_new_address/');
        $requestUrl->setQueryString($queries);


        $cURL = new ClientURL($requestUrl);
        $cURL->option
            ->setURL($requestUrl)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setReturnTransfer()
            ->setFollowRedirects()
            ->setGetMethod();
        $response = $cURL->executeWithDecode();

        $status = $response['status'];
        if ($status != "success") {
            return new Exception($response['error_message']);
        }

        return $response['address'];
    }
}