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

class NaverImageCapcha
{
    private string $clientId;

    private string $clientSecret;

    private $requestUrl = 'https://openapi.naver.com/v1/captcha/nkey';

    /**
     * Constructor for NaverImageCapcha.
     *
     * @param string $clientId The Naver client ID.
     * @param string $clientSecret The Naver client secret.
     */
    public function __construct(string $clientId, string $clientSecret)
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
    }

    /**
     * Generates a CAPTCHA key from Naver API.
     *
     * @param string $code The CAPTCHA code type.
     * @return mixed The API response containing the CAPTCHA key.
     */
    public function generate(string $code): mixed
    {
        $headers = array(
            sprintf("X-Naver-Client-Id: %s", $this->clientId),
            sprintf("X-Naver-Client-Secret: %s", $this->clientSecret)
        );

        $postData = "code={$code}";

        $requestUrl = $this->requestUrl . "?" . $postData;

        $cURL = new ClientURL();
        $cURL->option->setURL($requestUrl)
            ->setPostMethod(true)
            ->setHeaders($headers)
            ->setReturnTransfer(true)
            ->setPostField($postData)
            ->setAutoReferer(true)
            ->setReturnHeader(false)
            ->setDisableCache(true);

        $result = $cURL->execute();

        return $result;
    }

}