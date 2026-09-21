<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Plugin\Payment;

use Clover\Classes\ClientURL;
use Clover\Classes\Data\JSONHandler;
use Clover\Classes\Data\ArrayObject;
use stdClass;

/**
 * KakaoPayment class handles Kakao Pay API interactions for payment processing.
 * This class provides methods for preparing payments, approving transactions,
 * canceling payments, and managing subscriptions through Kakao Pay's online API.
 */
class KakaoPayment
{

    /**
     * Secret key for Kakao Pay API authentication.
     * @var string
     */
    private string $SECRET_KEY;

    /**
     * Client ID for Kakao Pay API.
     * @var string
     */
    private string $CLIENT_ID;

    /**
     * Constructor for KakaoPayment class.
     * Initializes the payment handler with secret key and client ID.
     *
     * @param string $secretKey The secret key for API authentication.
     * @param string $clientId The client ID for the Kakao Pay service.
     */
    public function __construct(string $secretKey, string $clientId)
    {
        $this->SECRET_KEY = $secretKey;
        $this->CLIENT_ID = $clientId;
    }

    /**
     * Prepares a payment request for Kakao Pay.
     * This method initiates the payment process by sending necessary details to Kakao Pay's ready API.
     *
     * @param string $partnerOrderId Unique order ID from the merchant.
     * @param string $partnerUserId Unique user ID from the merchant.
     * @param string $itemName Name of the item being purchased.
     * @param int $quantity Quantity of the item.
     * @param int $totalAmount Total amount to be paid.
     * @param int $taxFreeAmount Amount exempt from tax.
     * @param string $approvalUrl URL to redirect on successful payment approval.
     * @param string $cancelUrl URL to redirect on payment cancellation.
     * @param string $failUrl URL to redirect on payment failure.
     * @param mixed $cidSecret Optional secret for the client ID.
     * @param mixed $itemCode Optional code for the item.
     * @param mixed $vatAmount Optional VAT amount.
     * @param mixed $availableCards Optional list of available cards.
     * @param mixed $paymentMethodType Optional payment method type.
     * @param mixed $installMonth Optional installment months.
     * @param mixed $useShareInstallment Optional shared installment flag.
     * @param mixed $customJson Optional custom JSON data.
     * @return ArrayObject|stdClass Response from Kakao Pay API.
     */
    public function readyToPayment(string $partnerOrderId, string $partnerUserId, string $itemName, int $quantity, int $totalAmount, int $taxFreeAmount, string $approvalUrl, string $cancelUrl, string $failUrl, ?string $cidSecret = null, ?string $itemCode = null, ?int $vatAmount = null, ?array $availableCards = null, ?string $paymentMethodType = null, ?int $installMonth = null, ?string $useShareInstallment = null, ?array $customJson = null): mixed
    {
        $fields = [
            'cid' => $this->CLIENT_ID,
            'partner_order_id' => $partnerOrderId,
            'partner_user_id' => $partnerUserId,
            'item_name' => $itemName,
            'quantity' => $quantity,
            'total_amount' => $totalAmount,
            'tax_free_amount' => $taxFreeAmount,
            'approval_url' => $approvalUrl,
            'cancel_url' => $cancelUrl,
            'fail_url' => $failUrl,
            'item_code' => $itemCode,
            'cid_secret' => $cidSecret,
            'vat_amount' => $vatAmount,
            'available_cards' => $availableCards,
            'payment_method_type' => $paymentMethodType,
            'install_month' => $installMonth,
            'use_share_installment' => $useShareInstallment,
            'custom_json' => $customJson
        ];

        $requestUrl = "https://open-api.kakaopay.com/online/v1/payment/ready";

        $cURL = new ClientURL($requestUrl);
        $cURL->option
            ->setURL($requestUrl)
            ->setContentTypeApplicationJson()
            ->setHeader('Authorization', "SECRET_KEY ".$this->SECRET_KEY)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setPostField(JSONHandler::encode($fields))
            ->setReturnTransfer()
            ->setPostMethod();
        $response = $cURL->executeWithDecode();

        return $response;
    }

    /**
     * Approves a payment using the provided transaction details.
     * This method confirms the payment after the user has authorized it through Kakao Pay.
     *
     * @param string $tid Transaction ID from the ready response.
     * @param string $partnerOrderId Unique order ID from the merchant.
     * @param string $partnerUserId Unique user ID from the merchant.
     * @param string $pgToken Payment gateway token from Kakao Pay.
     * @param mixed $cidSecret Optional secret for the client ID.
     * @param mixed $payload Optional additional payload data.
     * @param mixed $totalAmount Optional total amount to verify.
     * @return ArrayObject|stdClass Response from Kakao Pay API.
     */
    public function approvePayment(string $tid, string $partnerOrderId, string $partnerUserId, string $pgToken, ?string $cidSecret = null, ?string $payload = null, ?int $totalAmount = null): mixed
    {
        $fields = [
            'cid' => $this->CLIENT_ID,
            'tid' => $tid,
            'cid_secret' => $cidSecret,
            'partner_order_id' => $partnerOrderId,
            'partner_user_id' => $partnerUserId,
            'pg_token' => $pgToken,
            'payload' => $payload,
            'total_amount' => $totalAmount
        ];

        $requestUrl = "https://open-api.kakaopay.com/online/v1/payment/approve";

        $cURL = new ClientURL($requestUrl);
        $cURL->option
            ->setURL($requestUrl)
            ->setContentTypeApplicationJson()
            ->setHeader('Authorization', "SECRET_KEY ".$this->SECRET_KEY)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setPostField(JSONHandler::encode($fields))
            ->setReturnTransfer()
            ->setPostMethod();
        $response = $cURL->executeWithDecode();

        return $response;
    }

    /**
     * Cancels a payment transaction.
     * This method allows canceling a previously approved payment.
     *
     * @param string $tid Transaction ID of the payment to cancel.
     * @param int $cancelAmount Amount to cancel.
     * @param int $cancelTaxFreeAmount Tax-free amount to cancel.
     * @param mixed $cidSecret Optional secret for the client ID.
     * @param mixed $cancelVatAmount Optional VAT amount to cancel.
     * @param mixed $cancelAvailableAmount Optional available amount to cancel.
     * @param mixed $payload Optional additional payload data.
     * @return ArrayObject|stdClass Response from Kakao Pay API.
     */
    public function cancelPayment(string $tid, int $cancelAmount, int $cancelTaxFreeAmount, ?string $cidSecret = null, ?int $cancelVatAmount = null, ?int $cancelAvailableAmount = null, ?string $payload = null): mixed
    {
        $fields = [
            'cid' => $this->CLIENT_ID,
            'cid_secret' => $cidSecret,
            'tid' => $tid,
            'cancel_amount' => $cancelAmount,
            'cancel_tax_free_amount' => $cancelTaxFreeAmount,
            'cancel_vat_amount' => $cancelVatAmount,
            'cancel_available_amount' => $cancelAvailableAmount,
            'payload' => $payload
        ];

        $requestUrl = "https://open-api.kakaopay.com/online/v1/payment/cancel";

        $cURL = new ClientURL($requestUrl);
        $cURL->option
            ->setURL($requestUrl)
            ->setContentTypeApplicationJson()
            ->setHeader('Authorization', "SECRET_KEY ".$this->SECRET_KEY)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setPostField(JSONHandler::encode($fields))
            ->setReturnTransfer()
            ->setPostMethod();
        $response = $cURL->executeWithDecode();

        return $response;
    }

    /**
     * Inquires about the details of a payment order.
     * This method retrieves information about a specific transaction using its TID.
     *
     * @param string $tid Transaction ID to inquire about.
     * @param mixed $cidSecret Optional secret for the client ID.
     * @return ArrayObject|stdClass Response from Kakao Pay API.
     */
    public function orderInquiry(string $tid, ?string $cidSecret = null): mixed
    {
        $fields = [
            'cid' => $this->CLIENT_ID,
            'tid' => $tid,
            'cid_secret' => $cidSecret
        ];

        $requestUrl = "https://open-api.kakaopay.com/online/v1/payment/order";

        $cURL = new ClientURL($requestUrl);
        $cURL->option
            ->setURL($requestUrl)
            ->setContentTypeApplicationJson()
            ->setHeader('Authorization', "SECRET_KEY ".$this->SECRET_KEY)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setPostField(JSONHandler::encode($fields))
            ->setReturnTransfer()
            ->setPostMethod();
        $response = $cURL->executeWithDecode();

        return $response;
    }

    /**
     * Processes a subscription payment.
     * This method handles recurring payments for subscriptions using Kakao Pay.
     *
     * @param string $sid Subscription ID.
     * @param string $partnerOrderId Unique order ID from the merchant.
     * @param string $partnerUserId Unique user ID from the merchant.
     * @param string $itemName Name of the item being subscribed to.
     * @param int $quantity Quantity of the item.
     * @param int $totalAmount Total amount for the subscription.
     * @param int $taxFreeAmount Tax-free amount for the subscription.
     * @param mixed $vatAmount Optional VAT amount.
     * @param mixed $greenDeposit Optional green deposit amount.
     * @param mixed $payload Optional additional payload data.
     * @return ArrayObject|stdClass Response from Kakao Pay API.
     */
    public function subscription(string $sid, string $partnerOrderId, string $partnerUserId, string $itemName, int $quantity, int $totalAmount, int $taxFreeAmount, ?int $vatAmount = null, ?int $greenDeposit = null, ?string $payload): mixed
    {
        $fields = [
            'cid' => $this->CLIENT_ID,
            'sid' => $sid,
            'partner_order_id' => $partnerOrderId,
            'partner_user_id' => $partnerUserId,
            'item_name' => $itemName,
            'quantity' => $quantity,
            'total_amount' => $totalAmount,
            'tax_free_amount' => $taxFreeAmount,
            'vat_amount' => $vatAmount,
            'green_deposit' => $greenDeposit,
            'payload' => $payload,
        ];

        $requestUrl = "https://open-api.kakaopay.com/online/v1/payment/subscription";

        $cURL = new ClientURL($requestUrl);
        $cURL->option
            ->setURL($requestUrl)
            ->setContentTypeApplicationJson()
            ->setHeader('Authorization', "SECRET_KEY ".$this->SECRET_KEY)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setPostField(JSONHandler::encode($fields))
            ->setReturnTransfer()
            ->setPostMethod();
        $response = $cURL->executeWithDecode();

        return $response;
    }

    /**
     * Inactivates a subscription.
     * This method deactivates an active subscription using the subscription ID.
     *
     * @param string $sid Subscription ID to inactivate.
     * @param mixed $cidSecret Optional secret for the client ID.
     * @return ArrayObject|stdClass Response from Kakao Pay API.
     */
    public function inactiveSubscription(string $sid, ?int $cidSecret): mixed
    {
        $fields = [
            'cid' => $this->CLIENT_ID,
            'sid' => $sid,
            'cid_secret' => $cidSecret,
        ];

        $requestUrl = "https://open-api.kakaopay.com/online/v1/payment/manage/subscription/inactive";

        $cURL = new ClientURL($requestUrl);
        $cURL->option
            ->setURL($requestUrl)
            ->setContentTypeApplicationJson()
            ->setHeader('Authorization', "SECRET_KEY ".$this->SECRET_KEY)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setPostField(JSONHandler::encode($fields))
            ->setReturnTransfer()
            ->setPostMethod();
        $response = $cURL->executeWithDecode();

        return $response;
    }

    /**
     * Retrieves the status of a subscription.
     * This method checks the current status of a subscription using the subscription ID.
     *
     * @param string $sid Subscription ID to check status for.
     * @param mixed $cidSecret Optional secret for the client ID.
     * @return ArrayObject|stdClass Response from Kakao Pay API.
     */
    public function getSubscriptionStatus(string $sid, ?int $cidSecret): mixed
    {
        $fields = [
            'cid' => $this->CLIENT_ID,
            'sid' => $sid,
            'cid_secret' => $cidSecret,
        ];

        $requestUrl = "https://open-api.kakaopay.com/online/v1/payment/manage/subscription/inactive";

        $cURL = new ClientURL($requestUrl);
        $cURL->option
            ->setURL($requestUrl)
            ->setContentTypeApplicationJson()
            ->setHeader('Authorization', "SECRET_KEY ".$this->SECRET_KEY)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setPostField(JSONHandler::encode($fields))
            ->setReturnTransfer()
            ->setPostMethod();
        $response = $cURL->executeWithDecode();

        return $response;
    }

}