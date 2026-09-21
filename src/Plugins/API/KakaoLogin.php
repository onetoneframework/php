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

/**
 * KakaoLogin class handles Kakao OAuth login and user authentication.
 * This class provides methods for generating login URLs, obtaining access tokens,
 * and retrieving user profile information through Kakao's OAuth API.
 */
class KakaoLogin
{
    /**
     * Client secret for Kakao OAuth authentication.
     * @var string
     */
    private string $clientSecret;

    /**
     * Client ID for Kakao RESTful API.
     * @var string
     */
    private string $clientId;

    /**
     * Redirect URL for OAuth callback.
     * @var string
     */
    private string $redirectUrl;

    /**
     * Sets the client secret for Kakao OAuth.
     *
     * @param string $secret The client secret from environment variables.
     * @return void
     */
    public function setClientSecret(string $secret): void
    {
        $this->clientSecret = $secret;
    }

    /**
     * Sets the client ID for Kakao RESTful API.
     *
     * @param string $id The RESTful API key from environment variables.
     * @return void
     */
    public function setClientId(string $id): void
    {
        $this->clientId = $id;
    }

    /**
     * Sets the redirect URL for OAuth callback.
     *
     * @param string $url The redirect URL.
     * @return void
     */
    public function setRedirectUrl(string $url): void
    {
        $this->redirectUrl = $url;
    }

    /**
     * Generates the Kakao OAuth login URL.
     * Creates a URL for redirecting users to Kakao's authorization page.
     *
     * @return string The complete login URL with query parameters.
     */
    public function getLoginUrl(): string
    {
        $state = md5((string) mt_rand(111111111, 999999999));

        $query = http_build_query([
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUrl,
            'state' => $state
        ]);

        return 'https://kauth.kakao.com/oauth/authorize?' . $query;
    }

    /**
     * Exchanges authorization code for access token.
     * Sends the authorization code to Kakao to obtain an access token.
     *
     * @param mixed $code The authorization code from the callback.
     * @return ArrayObject The token response data.
     */
    public function getToken($code): ArrayObject
    {
        $fields = [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUrl,
            'client_secret' => $this->clientSecret,
            'code' => $code,
            'grant_type' => 'authorization_code'
        ];

        $query = http_build_query($fields);

        $authorizedUrl = 'https://kauth.kakao.com/oauth/token';

        $cURL = new ClientURL($authorizedUrl);
        $cURL->option
            ->setURL($authorizedUrl)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setReturnTransfer()
            ->setPostMethod()
            ->setPostFields($fields);
        $response = $cURL->execute();

        $response = JSONHandler::decode($response, true);

        return $response;
    }

    /**
     * Retrieves user profile information.
     * Fetches the authenticated user's profile data using the access token.
     *
     * @param mixed $code The authorization code.
     * @param mixed $accessToken The access token for API calls.
     * @return ArrayObject The user profile data.
     */
    public function getProfile($code, $accessToken): ArrayObject
    {
        $fields = [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUrl,
            'client_secret' => $this->clientSecret,
            'code' => $code,
            'grant_type' => 'authorization_code'
        ];

        $profileUrl = 'https://kapi.kakao.com/v2/user/me';

        $cURL = new ClientURL($profileUrl);
        $cURL->option
            ->setURL($profileUrl)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setReturnTransfer()
            ->setPostMethod()
            ->setHeader('Authorization', 'Bearer ' . $accessToken)
            ->setPostFields($fields);
        $response = $cURL->execute();

        return JSONHandler::decode($response);
    }

}
