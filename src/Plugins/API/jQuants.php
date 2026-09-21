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

class jQuants
{
    private $REFRESH_TOKEN;

    public function __construct(string $refreshToken)
    {
        $this->REFRESH_TOKEN = $refreshToken;
    }

    public function getIdToken(): mixed
    {
        $queries = [
            'refreshtoken' => $this->REFRESH_TOKEN
        ];

        $requestUrl = new URLObject('https://api.jquants.com//v1/token/auth_refresh');
        $requestUrl->setQueryString($queries);
        
        $cURL = new ClientURL($requestUrl);
        $cURL->option
            ->setURL($requestUrl)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setReturnTransfer()
            ->setGetMethod();
        $response = $cURL->executeWithDecode();

        return $response;
    }

}