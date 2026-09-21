<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Plugin;

use Clover\Annotation\Deprecated;
use Clover\Classes\ClientURL;
use function sprintf;

#[Deprecated('Use the official Naver SDK or another maintained package instead.')]
class NaverPapago
{
    private string $clientId;

    private string $clientSecret;

    private string $requestUrl = 'https://openapi.naver.com/v1/papago/n2mt';

    /**
     * Constructor for NaverPapago.
     *
     * @param string $clientId     Naver API Client ID.
     * @param string $clientSecret Naver API Client Secret.
     */
    public function __construct(string $clientId, string $clientSecret)
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
    }

    /**
     * Translate text using Naver Papago API.
     *
     * @param string $text   The text to translate.
     * @param string $source The source language code.
     * @param string $target The target language code.
     *
     * @return mixed The translated text or error response.
     */
    #[Deprecated('This method is deprecated. Please use the official Naver SDK or another maintained package instead.')]
    public function translate(string $text, string $source, string $target): mixed
    {
        $text = urlencode($text);
        $postData = "source={$source}&target={$target}&text={$text}";

        $cURL = new ClientURL();
        $cURL->option->setURL($this->requestUrl)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setPostMethod(true)
            ->setHeaders([
                sprintf("X-Naver-Client-Id: %s", $this->clientId),
                sprintf("X-Naver-Client-Secret: %s", $this->clientSecret)
            ])
            ->setReturnTransfer(true)
            ->setPostField($postData)
            ->setAutoReferer(true)
            ->setReturnHeader(false)
            ->setDisableCache(true);

        $result = $cURL->executeWithDecode();

        if (isset($result->message->result->translatedText)) {
            return $result->message->result->translatedText;
        }

        return $result;
    }
}
