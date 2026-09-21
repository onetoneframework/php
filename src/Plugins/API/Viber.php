<?php

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

declare(strict_types=1);

namespace Clover\Plugin;

use Clover\Classes\ClientURL;
use Clover\Classes\Data\ArrayObject;
use Clover\Classes\Data\JSONHandler;

class Viber
{
    private string $token;
    private string $senderName;
    private string $senderAvatar;

    private const API_BASE = 'https://chatapi.viber.com/pa/';

    /**
     * Set the Viber Public Account authentication token.
     */
    public function setToken(string $token): void
    {
        $this->token = $token;
    }

    /**
     * Set the default sender name shown on all outgoing messages.
     */
    public function setSenderName(string $name): void
    {
        $this->senderName = $name;
    }

    /**
     * Set the default sender avatar URL.
     */
    public function setSenderAvatar(string $url): void
    {
        $this->senderAvatar = $url;
    }

    /**
     * Register a webhook URL to receive incoming events.
     *
     * $eventTypes: subset of ['delivered', 'seen', 'failed', 'subscribed', 'unsubscribed', 'conversation_started']
     * Pass an empty array to receive all event types.
     */
    public function setWebhook(string $url, array $eventTypes = [], bool $sendName = false, bool $sendPhoto = false): ArrayObject
    {
        $payload = ['url' => $url, 'send_name' => $sendName, 'send_photo' => $sendPhoto];

        if (!empty($eventTypes)) {
            $payload['event_types'] = $eventTypes;
        }

        return $this->postRequest('set_webhook', $payload);
    }

    /**
     * Remove the registered webhook.
     */
    public function removeWebhook(): ArrayObject
    {
        return $this->postRequest('set_webhook', ['url' => '']);
    }

    /**
     * Get information about the Public Account.
     */
    public function getAccountInfo(): ArrayObject
    {
        return $this->postRequest('get_account_info', []);
    }

    /**
     * Get information about a specific Viber user by their ID.
     */
    public function getUserDetails(string $id): ArrayObject
    {
        return $this->postRequest('get_user_details', ['id' => $id]);
    }

    /**
     * Get the online status of multiple users (up to 100 IDs).
     */
    public function getOnlineUsers(array $ids): ArrayObject
    {
        return $this->postRequest('get_online', ['ids' => $ids]);
    }

    /**
     * Send a plain text message to a subscribed user.
     *
     * Supported $options keys:
     *   tracking_data   string  Custom string returned in delivery receipts
     *   min_api_version int     Minimum Viber app version required
     *   keyboard        array   Custom keyboard layout
     */
    public function sendTextMessage(string $receiver, string $text, array $options = []): ArrayObject
    {
        return $this->buildAndSend($receiver, array_merge([
            'type' => 'text',
            'text' => $text,
        ], $options));
    }

    /**
     * Send an image message.
     *
     * $mediaUrl: publicly accessible URL of the image (JPEG, PNG, GIF, non-animated).
     * $thumbnailUrl: URL of the thumbnail image.
     * $size: file size in bytes.
     */
    public function sendImageMessage(string $receiver, string $mediaUrl, string $thumbnailUrl = '', int $size = 0, array $options = []): ArrayObject
    {
        $payload = array_merge([
            'type'  => 'picture',
            'text'  => '',
            'media' => $mediaUrl,
        ], $options);

        if ($thumbnailUrl !== '') {
            $payload['thumbnail'] = $thumbnailUrl;
        }

        if ($size > 0) {
            $payload['size'] = $size;
        }

        return $this->buildAndSend($receiver, $payload);
    }

    /**
     * Send a video message.
     *
     * $size: file size in bytes (required).
     * $duration: video duration in seconds.
     */
    public function sendVideoMessage(string $receiver, string $mediaUrl, int $size, int $duration = 0, string $thumbnailUrl = '', array $options = []): ArrayObject
    {
        $payload = array_merge([
            'type'  => 'video',
            'media' => $mediaUrl,
            'size'  => $size,
        ], $options);

        if ($duration > 0) {
            $payload['duration'] = $duration;
        }

        if ($thumbnailUrl !== '') {
            $payload['thumbnail'] = $thumbnailUrl;
        }

        return $this->buildAndSend($receiver, $payload);
    }

    /**
     * Send a file (document) message.
     *
     * $fileName: file name with extension (e.g., "report.pdf").
     * $size: file size in bytes (required).
     */
    public function sendFileMessage(string $receiver, string $mediaUrl, string $fileName, int $size, array $options = []): ArrayObject
    {
        return $this->buildAndSend($receiver, array_merge([
            'type'      => 'file',
            'media'     => $mediaUrl,
            'file_name' => $fileName,
            'size'      => $size,
        ], $options));
    }

    /**
     * Send a contact card.
     */
    public function sendContactMessage(string $receiver, string $contactName, string $phoneNumber, array $options = []): ArrayObject
    {
        return $this->buildAndSend($receiver, array_merge([
            'type'    => 'contact',
            'contact' => ['name' => $contactName, 'phone_number' => $phoneNumber],
        ], $options));
    }

    /**
     * Send a location pin.
     */
    public function sendLocationMessage(string $receiver, float $lat, float $lon, array $options = []): ArrayObject
    {
        return $this->buildAndSend($receiver, array_merge([
            'type'     => 'location',
            'location' => ['lat' => $lat, 'lon' => $lon],
        ], $options));
    }

    /**
     * Send a URL message (opens in browser).
     */
    public function sendUrlMessage(string $receiver, string $url, array $options = []): ArrayObject
    {
        return $this->buildAndSend($receiver, array_merge([
            'type'  => 'url',
            'media' => $url,
        ], $options));
    }

    /**
     * Send a sticker message by Viber sticker ID.
     */
    public function sendStickerMessage(string $receiver, int $stickerId, array $options = []): ArrayObject
    {
        return $this->buildAndSend($receiver, array_merge([
            'type'       => 'sticker',
            'sticker_id' => $stickerId,
        ], $options));
    }

    /**
     * Broadcast a message to multiple subscribers (up to 300 receivers per call).
     * Same payload structure as send — only the 'receiver' key is replaced by 'broadcast_list'.
     */
    public function broadcastMessage(array $receiverIds, array $messagePayload): ArrayObject
    {
        unset($messagePayload['receiver']);
        $messagePayload['broadcast_list'] = $receiverIds;
        $messagePayload['sender']         = $this->buildSender();

        return $this->postRequest('broadcast_message', $messagePayload);
    }

    /**
     * Build the sender sub-object from stored name and avatar.
     */
    private function buildSender(): array
    {
        $sender = ['name' => $this->senderName];

        if (!empty($this->senderAvatar)) {
            $sender['avatar'] = $this->senderAvatar;
        }

        return $sender;
    }

    /**
     * Merge the base fields (receiver, sender) with the message payload and send.
     */
    private function buildAndSend(string $receiver, array $payload): ArrayObject
    {
        return $this->postRequest('send_message', array_merge([
            'receiver' => $receiver,
            'sender'   => $this->buildSender(),
        ], $payload));
    }

    /**
     * Send a POST request with JSON body and X-Viber-Auth-Token header, return decoded response.
     */
    private function postRequest(string $endpoint, array $fields): ArrayObject
    {
        $url = self::API_BASE . $endpoint;

        $cURL = new ClientURL($url);
        $cURL->option
            ->setURL($url)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setReturnTransfer()
            ->setPostMethod()
            ->setHeader('X-Viber-Auth-Token', $this->token)
            ->setHeader('Content-Type', 'application/json')
            ->setPostFields($fields);

        return JSONHandler::decode($cURL->execute(), true);
    }
}
