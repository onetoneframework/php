<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use Clover\Classes\ClientURL;
use Clover\Classes\Data\URLObject;
use Clover\Classes\Date\Date;

class PassAuthorize
{
    private string $clientId;
    private string $clientSecret;

    public function __construct(string $clientId, string $clientSecret)
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
    }

    public function getToken(string $grantType = 'client_credentials', string $scope = 'default'): mixed
    {
        $queries = [
            'grant_type' => $grantType,
            'scope' => $scope
        ];

        $requestUrl = new URLObject('https://svc.niceapi.co.kr:22001/digital/niceid/oauth/oauth/token');
        $bearer = base64_encode($this->clientId . ':' . $this->clientSecret);

        $cURL = new ClientURL($requestUrl);
        $cURL->option
            ->setURL($requestUrl)
            ->setSSLVerifyPeer(false)
            ->setFollowRedirects()
            ->setHeader('Authorization', 'Basic : ' . $bearer)
            ->setSSLVerifyHost(false)
            ->setReturnTransfer()
            ->setPostFields($queries)
            ->setPostMethod();

        return $cURL->executeWithDecode();
    }

    public function revokeToken(string $accessToken): mixed
    {
        $requestUrl = new URLObject('https://svc.niceapi.co.kr:22001/digital/niceid/oauth/oauth/token/revokeById');

        $currentTimestamp = Date::getTime();
        $bearer = base64_encode($accessToken . ':' . $currentTimestamp . ':' . $this->clientId);

        $cURL = new ClientURL($requestUrl);
        $cURL->option
            ->setURL($requestUrl)
            ->setSSLVerifyPeer(false)
            ->setFollowRedirects()
            ->setHeader('Authorization', 'Basic : ' . $bearer)
            ->setSSLVerifyHost(false)
            ->setReturnTransfer()
            ->setPostMethod();

        return $cURL->executeWithDecode();
    }


}