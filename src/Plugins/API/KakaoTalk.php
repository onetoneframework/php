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

/**
 * KakaoTalk class handles Kakao API services including geolocation and messaging.
 * This class provides methods for coordinate transformation, region code lookup,
 * and sending messages to friends through Kakao's APIs.
 */
class KakaoTalk
{

    /**
     * Access token for Kakao API authentication.
     * @var string
     */
    private string $KAKAO_ACCESS_TOKEN;

    /**
     * Constructor for KakaoTalk class.
     * Initializes the client with the Kakao access token.
     *
     * @param string $accessToken The access token for Kakao API.
     */
    public function __construct(string $accessToken)
    {
        $this->KAKAO_ACCESS_TOKEN = $accessToken;
    }

    /**
     * Transforms coordinates between different coordinate systems.
     * Converts coordinates from one system (e.g., WGS84) to another (e.g., TM).
     *
     * @param float $x The X coordinate (longitude).
     * @param float $y The Y coordinate (latitude).
     * @param string $input The input coordinate system (default: 'WGS84').
     * @param string $output The output coordinate system (default: 'TM').
     * @return mixed The transformed coordinate data.
     */
    public function transCoord(float $x, float $y, string $input = "WGS84", string $output = "TM"): mixed
    {
        $queries = [
            'x' => $x,
            'y' => $y,
            'input_coord' => $input,
            'output_coord' => $output
        ];

        $queries = http_build_query($queries);

        $requestUrl = "http://dapi.kakao.com/v2/local/geo/transcoord.json?{$queries}";

        $cURL = new ClientURL($requestUrl);
        $cURL->option
            ->setURL($requestUrl)
            ->setContentTypeFormUrlEncoded()
            ->setHeader('Authorization', "KakaoAK " . $this->KAKAO_ACCESS_TOKEN)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setReturnTransfer()
            ->setGetMethod();
        $response = $cURL->executeWithDecode();

        return $response;
    }

    /**
     * Converts coordinates to region code information.
     * Retrieves administrative region information for given coordinates.
     *
     * @param float $x The X coordinate (longitude).
     * @param float $y The Y coordinate (latitude).
     * @param mixed $input The input coordinate system (default: 'WGS84').
     * @return mixed The region code data.
     */
    public function coordToRegionCode(float $x, float $y, $input = "WGS84"): mixed
    {
        $queries = [
            'x' => $x,
            'y' => $y,
            'input_coord' => $input
        ];

        $queries = http_build_query($queries);

        $requestUrl = "https://dapi.kakao.com/v2/local/geo/coord2regioncode.json?{$queries}";

        $cURL = new ClientURL($requestUrl);
        $cURL->option
            ->setURL($requestUrl)
            ->setContentTypeFormUrlEncoded()
            ->setHeader('Authorization', "KakaoAK " . $this->KAKAO_ACCESS_TOKEN)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setReturnTransfer()
            ->setGetMethod();
        $response = $cURL->executeWithDecode();

        return $response;
    }

    /**
     * Sends a message to friends on KakaoTalk.
     * Sends a text message with a link to specified friend UUIDs.
     *
     * @param string $receiverUuids The UUIDs of the receivers.
     * @param string $returnUrl The URL to include in the message.
     * @param string $message The text message to send.
     * @return mixed The response from the message send request.
     */
    public function sendMessageToFriend(string $receiverUuids, string $returnUrl, string $message): mixed
    {
        $fields = [
            'receiver_uuids' => $receiverUuids,
            'template_object' => [
                'object_type' => 'text',
                "text" => $message,
                "link" => [
                    "web_url" => $returnUrl,
                    "mobile_web_url" => $returnUrl
                ]
            ]
        ];

        $requestUrl = "https://kapi.kakao.com/v1/api/talk/friends/message/default/send";

        $cURL = new ClientURL($requestUrl);
        $cURL->option
            ->setURL($requestUrl)
            ->setContentTypeFormUrlEncoded()
            ->setHeader('Authorization', "Bearer " . $this->KAKAO_ACCESS_TOKEN)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setReturnTransfer()
            ->setPostMethod()
            ->setPostFields($fields);
        $response = $cURL->execute();

        $response = json_decode($response);

        return $response;
    }

}