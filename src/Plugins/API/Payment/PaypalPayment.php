<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use Clover\Classes\ClientURL;
use PaypalServerSdkLib\Authentication\ClientCredentialsAuthCredentialsBuilder;
use PaypalServerSdkLib\Environment;
use PaypalServerSdkLib\Logging\LoggingConfigurationBuilder;
use PaypalServerSdkLib\Logging\RequestLoggingConfigurationBuilder;
use PaypalServerSdkLib\Logging\ResponseLoggingConfigurationBuilder;
use PaypalServerSdkLib\Models\Builders\AmountWithBreakdownBuilder;
use PaypalServerSdkLib\Models\Builders\CaptureRequestBuilder;
use PaypalServerSdkLib\Models\Builders\OrderRequestBuilder;
use PaypalServerSdkLib\Models\Builders\PurchaseUnitRequestBuilder;
use PaypalServerSdkLib\Models\CheckoutPaymentIntent;
use PaypalServerSdkLib\PaypalServerSdkClientBuilder;

class PaypalPayment
{
    private $client;

    public function __construct($clientId, $clientSecret)
    {
        $oauthToken = $this->getOauthToken($clientId, $clientSecret);
        var_dump($oauthToken);
        /*$this->client = PaypalServerSdkClientBuilder::init()
            ->clientCredentialsAuthCredentials(
                ClientCredentialsAuthCredentialsBuilder::init(
                    $clientId,
                    $clientSecret
                )
            )
            ->environment(Environment::SANDBOX)
            ->loggingConfiguration(
                LoggingConfigurationBuilder::init()
                    ->level(LogLevel::INFO)
                    ->requestConfiguration(RequestLoggingConfigurationBuilder::init()->body(true))
                    ->responseConfiguration(ResponseLoggingConfigurationBuilder::init()->headers(true))
            )
            ->build();*/
    }

    public function getOauthToken($clientId, $clientSecret)
    {
        $requestUrl = "https://api-m.sandbox.paypal.com/v1/oauth2/token";

        $cURL = new ClientURL($requestUrl);
        $cURL->option
            ->setURL($requestUrl)
            ->setHeader('Accept', 'application/json')
            ->setUserPassword($clientId.":".$clientSecret)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setPostField("grant_type=client_credentials")
            ->setReturnTransfer()
            ->setPostMethod();
        $response = $cURL->executeWithDecode();

        return $response;
    }

    public function captureAuthorizedPayment($authorizationId)
    {
        $paymentsController = $this->client->getPaymentsController();

        $collect = [
            'authorizationId' => $authorizationId,
            'prefer' => 'return=minimal',
            'body' => CaptureRequestBuilder::init()
                ->finalCapture(false)
                ->build()
        ];

        return $paymentsController->captureAuthorizedPayment($collect);
    }

    public function getRefund($refundId)
    {
        $paymentsController = $this->client->getPaymentsController();

        $collect = [
            'refundId' => $refundId
        ];

        return $paymentsController->getRefund($collect);
    }

    public function getAuthorizedPayment($authorizationId)
    {
        $paymentsController = $this->client->getPaymentsController();

        $collect = [
            'authorizationId' => $authorizationId
        ];

        return $paymentsController->getAuthorizedPayment($collect);
    }

    public function voidPayment($authorizationId)
    {
        $paymentsController = $this->client->getPaymentsController();

        $collect = [
            'authorizationId' => $authorizationId,
            'prefer' => 'return=minimal'
        ];

        return $paymentsController->voidPayment($collect);
    }

    public function refundCapturedPayment($captureId)
    {
        $paymentsController = $this->client->getPaymentsController();

        $collect = [
            'captureId' => $captureId,
            'prefer' => 'return=minimal'
        ];

        return $paymentsController->refundCapturedPayment($collect);
    }

    public function getCapturedPayment($captureId)
    {
        $paymentsController = $this->client->getPaymentsController();

        $collect = [
            'captureId' => $captureId
        ];

        return $paymentsController->getCapturedPayment($collect);
    }

    public function reauthorizePayment($authorizationId)
    {
        $paymentsController = $this->client->getPaymentsController();

        $collect = [
            'authorizationId' => $authorizationId,
            'prefer' => 'return=minimal'
        ];

        return $paymentsController->reauthorizePayment($collect);
    }

    public function captureOrder()
    {
        $ordersController = $this->client->getOrdersController();

        $collect = [
            'id' => 'id0',
            'prefer' => 'return=minimal'
        ];

        return $ordersController->captureOrder($collect);
    }

    public function confirmOrder()
    {
        $ordersController = $this->client->getOrdersController();

        $collect = [
            'id' => 'id0',
            'prefer' => 'return=minimal'
        ];

        return $ordersController->confirmOrder($collect);
    }

    public function createOrder($currencyCode, $value)
    {
        $ordersController = $this->client->getOrdersController();

        $collect = [
            'body' => OrderRequestBuilder::init(
                CheckoutPaymentIntent::CAPTURE,
                [
                    PurchaseUnitRequestBuilder::init(
                        AmountWithBreakdownBuilder::init(
                            $currencyCode,
                            $value
                        )->build()
                    )->build()
                ]
            )->build(),
            'prefer' => 'return=minimal'
        ];

        return $ordersController->createOrder($collect);
    }

}