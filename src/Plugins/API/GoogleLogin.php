<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Plugin;

use Google_Client;

class GoogleLogin
{
    private $client;

    public function __construct()
    {
        $this->client = new Google_Client();
    }
    
    public function setClientInformations($clientId, $secret)
    {
        $this->client->setClientId($clientId);
        $this->client->setClientSecret($secret);
    }

    public function setRedirectUrl($url)
    {
        $this->client->setRedirectUri($url);
    }

    /**
     * @param mixed $scope : email, profile
     * @return void
     */
    public function addScope($scope)
    {
        $this->client->addScope('email');
    }

    public function createAuthUrl()
    {
        return $this->client->createAuthUrl();
    }

    public function authenticate($code)
    {
        $this->client->authenticate($code);
    }

    public function getAccessToken()
    {
        return $this->client->getAccessToken();
    }

    public function getUserInfoFromAccessToken($accessToken)
    {
        $this->client->setAccessToken($accessToken);
        $googleOauth = new \Google_Service_Oauth2($this->client);
        $googleAccountInfo = $googleOauth->userinfo->get();

        return $googleAccountInfo;
    }

}