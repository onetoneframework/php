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
use Clover\Classes\Data\JSONHandler;

class NaverLogin
{
    private string $clientSecret;

    private string $clientId;

    private string $redirectUrl;

    /**
     * Sets the client secret for Naver OAuth.
     *
     * @param string $secret The client secret provided by Naver.
     * @return void
     */
    public function setClientSecret(string $secret): void
    {
        $this->clientSecret = $secret;
    }

    /**
     * Sets the client ID for Naver OAuth.
     *
     * @param string $id The client ID provided by Naver.
     * @return void
     */
    public function setClientId(string $id): void
    {
        $this->clientId = $id;
    }

    /**
     * Sets the redirect URL for Naver OAuth.
     *
     * @param string $url The redirect URL after authentication.
     * @return void
     */
    public function setRedirectUrl(string $url): void
    {
        $this->redirectUrl = $url;
    }

    /**
     * Retrieves account information from Naver using an access token.
     *
     * @param string $accessToken The access token obtained from Naver OAuth.
     * @return ArrayObject|array{
     *  resultcode: string,
     *  message: string,
     *  response: array{
     *      id: string,
     *      nickname: string,
     *      email: string
     *  }
     * }|bool The account information as an ArrayObject or false on failure.
     */
    public function getAccountInfo(string $accessToken): ArrayObject|bool
    {
        $requestUrl = "https://openapi.naver.com/v1/nid/me";

        $cURL = new ClientURL($requestUrl);
        $cURL->option
            ->setContentTypeApplicationJson()
            ->setHeader('Authorization', sprintf('Authorization: Bearer %s', $accessToken))
            ->setURL($requestUrl)
            ->setReturnTransfer()
            ->setPostMethod();

        $response = $cURL->execute();
        $statusCode = $cURL->information()->getStatusCode();
        $cURL->close();

        if (!$statusCode == '200') {
            return false;
        }

        return JSONHandler::decode($response, true);
    }

    /**
     * Exchanges an authorization code for an access token from Naver.
     *
     * @param string $code The authorization code received from Naver.
     * 
     * @return ArrayObject|array{
     *  access_token: string, 
     *  refresh_token: string,
     *  token_type: string|null,
     *  expires_in: int|null
     * }|bool The token response as an ArrayObject or false on failure.
     */
    public function getToken(string $code): ArrayObject|bool
    {
        $fields = [
            'grant_type' => 'authorization_code',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => urlencode($this->redirectUrl),
            'code' => $code
        ];

        $requesetUrl = 'https://nid.naver.com/oauth2.0/token';

        $cURL = new ClientURL($requesetUrl);
        $cURL->option
            ->setURL($requesetUrl)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setReturnTransfer()
            ->setPostMethod()
            ->setPostFields($fields);

        $response = $cURL->execute();
        $statusCode = $cURL->information()->getStatusCode();
        $cURL->close();

        if (!$statusCode == '200') {
            return false;
        }

        return JSONHandler::decode($response, true);
    }

    /**
     * Generates the Naver OAuth login URL.
     *
     * @return string The complete login URL for Naver OAuth authorization.
     */
    public function getLoginUrl(): string
    {
        $query = http_build_query([
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUrl
        ]);

        return "https://nid.naver.com/oauth2.0/authorize?{$query}";
    }
}