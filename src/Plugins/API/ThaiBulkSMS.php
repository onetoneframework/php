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

class ThaiBulkSMS
{
    public function sendSMS(string $username, string $password, string $msisdn, string $message, string $sender = 'THAIBULKSMS', string $ScheduledDelivery = '', string $force = 'standard'): mixed
    {
        $fields = http_build_query([
            'username' => $username,
            'password' => $password,
            'msisdn' => $msisdn,
            'message' => $message,
            'sender' => $sender,
            'ScheduledDelivery' => $ScheduledDelivery,
            'force' => $force
        ]);

        $requestUrl = "https://www.thaibulksms.com/sms_api.php";

        $cURL = new ClientURL($requestUrl);
        $cURL->option
            ->setURL($requestUrl)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setReturnTransfer()
            ->setPostField($fields)
            ->setPostMethod();
        $response = $cURL->executeWithDecode();

        return $response;
    }
}