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

class FacebookMessenger
{
    private string $pageAccessToken;
    private string $appSecret;
    private string $verifyToken;

    private const API_BASE = 'https://graph.facebook.com/v17.0/';

    /**
     * Set the Facebook Page Access Token.
     */
    public function setPageAccessToken(string $token): void
    {
        $this->pageAccessToken = $token;
    }

    /**
     * Set the app secret (used for webhook signature verification).
     */
    public function setAppSecret(string $secret): void
    {
        $this->appSecret = $secret;
    }

    /**
     * Set the webhook verify token (used when registering the webhook).
     */
    public function setVerifyToken(string $token): void
    {
        $this->verifyToken = $token;
    }

    /**
     * Verify an incoming webhook challenge from Facebook.
     * Returns the hub.challenge value if valid, null otherwise.
     */
    public function verifyWebhook(array $params): ?string
    {
        if (
            isset($params['hub_mode'], $params['hub_verify_token'], $params['hub_challenge']) &&
            $params['hub_mode'] === 'subscribe' &&
            $params['hub_verify_token'] === $this->verifyToken
        ) {
            return $params['hub_challenge'];
        }

        return null;
    }

    /**
     * Verify the X-Hub-Signature-256 header of an incoming webhook payload.
     */
    public function verifySignature(string $rawBody, string $signatureHeader): bool
    {
        if (!str_starts_with($signatureHeader, 'sha256=')) {
            return false;
        }

        $expected = 'sha256=' . hash_hmac('sha256', $rawBody, $this->appSecret);
        return hash_equals($expected, $signatureHeader);
    }

    /**
     * Send a plain text message to a recipient.
     */
    public function sendTextMessage(string $recipientId, string $text, string $messagingType = 'RESPONSE'): ArrayObject
    {
        return $this->sendMessage($recipientId, ['text' => $text], $messagingType);
    }

    /**
     * Send an image attachment by URL.
     */
    public function sendImage(string $recipientId, string $url, bool $reusable = false): ArrayObject
    {
        return $this->sendAttachment($recipientId, 'image', $url, $reusable);
    }

    /**
     * Send a video attachment by URL.
     */
    public function sendVideo(string $recipientId, string $url, bool $reusable = false): ArrayObject
    {
        return $this->sendAttachment($recipientId, 'video', $url, $reusable);
    }

    /**
     * Send an audio attachment by URL.
     */
    public function sendAudio(string $recipientId, string $url, bool $reusable = false): ArrayObject
    {
        return $this->sendAttachment($recipientId, 'audio', $url, $reusable);
    }

    /**
     * Send a file attachment by URL.
     */
    public function sendFile(string $recipientId, string $url, bool $reusable = false): ArrayObject
    {
        return $this->sendAttachment($recipientId, 'file', $url, $reusable);
    }

    /**
     * Send quick reply buttons with a text prompt.
     *
     * $quickReplies format:
     *   [
     *     ['content_type' => 'text', 'title' => 'Yes', 'payload' => 'YES'],
     *     ['content_type' => 'text', 'title' => 'No',  'payload' => 'NO'],
     *     ['content_type' => 'phone_number'],
     *     ['content_type' => 'email'],
     *   ]
     */
    public function sendQuickReplies(string $recipientId, string $text, array $quickReplies): ArrayObject
    {
        return $this->sendMessage($recipientId, [
            'text'          => $text,
            'quick_replies' => $quickReplies,
        ]);
    }

    /**
     * Send a button template message (up to 3 buttons).
     *
     * $buttons format:
     *   [
     *     ['type' => 'web_url',   'url' => 'https://...', 'title' => 'Visit'],
     *     ['type' => 'postback',  'title' => 'Click',     'payload' => 'PAYLOAD'],
     *     ['type' => 'phone_number', 'title' => 'Call',   'payload' => '+1234567890'],
     *   ]
     */
    public function sendButtonTemplate(string $recipientId, string $text, array $buttons): ArrayObject
    {
        return $this->sendMessage($recipientId, [
            'attachment' => [
                'type'    => 'template',
                'payload' => [
                    'template_type' => 'button',
                    'text'          => $text,
                    'buttons'       => $buttons,
                ],
            ],
        ]);
    }

    /**
     * Send a generic template (horizontal scrollable card carousel, up to 10 elements).
     *
     * Each element format:
     *   ['title' => '...', 'subtitle' => '...', 'image_url' => '...', 'buttons' => [...]]
     */
    public function sendGenericTemplate(string $recipientId, array $elements, string $imageAspectRatio = 'horizontal'): ArrayObject
    {
        return $this->sendMessage($recipientId, [
            'attachment' => [
                'type'    => 'template',
                'payload' => [
                    'template_type'    => 'generic',
                    'image_aspect_ratio' => $imageAspectRatio,
                    'elements'         => $elements,
                ],
            ],
        ]);
    }

    /**
     * Send a media template (image or video with optional buttons).
     *
     * $mediaType: 'image' | 'video'
     * $attachmentId: a previously uploaded Facebook attachment ID, or use 'url' key instead.
     */
    public function sendMediaTemplate(string $recipientId, string $mediaType, string $attachmentId, array $buttons = []): ArrayObject
    {
        $element = [
            'media_type'    => $mediaType,
            'attachment_id' => $attachmentId,
        ];

        if (!empty($buttons)) {
            $element['buttons'] = $buttons;
        }

        return $this->sendMessage($recipientId, [
            'attachment' => [
                'type'    => 'template',
                'payload' => [
                    'template_type' => 'media',
                    'elements'      => [$element],
                ],
            ],
        ]);
    }

    /**
     * Show or hide the typing indicator / seen mark.
     *
     * $action: 'typing_on' | 'typing_off' | 'mark_seen'
     */
    public function sendSenderAction(string $recipientId, string $action): ArrayObject
    {
        return $this->postRequest('me/messages', [
            'recipient'     => ['id' => $recipientId],
            'sender_action' => $action,
        ]);
    }

    /**
     * Get a user's public profile (first_name, last_name, profile_pic, locale, timezone, gender).
     * Requires pages_messaging and pages_user_gender/locale permissions as applicable.
     *
     * $fields: comma-separated list (e.g., "first_name,last_name,profile_pic")
     */
    public function getUserProfile(string $userId, string $fields = 'first_name,last_name,profile_pic'): ArrayObject
    {
        $url = self::API_BASE . $userId . '?fields=' . urlencode($fields) . '&access_token=' . $this->pageAccessToken;
        return $this->getRequest($url);
    }

    /**
     * Set the Get Started button payload. Sent once when a new user opens the chat.
     */
    public function setGetStarted(string $payload): ArrayObject
    {
        return $this->postRequest('me/messenger_profile', [
            'get_started' => ['payload' => $payload],
        ]);
    }

    /**
     * Set the persistent menu shown in the chat composer.
     *
     * $menuItems format: same as button template buttons (postback or web_url items).
     */
    public function setPersistentMenu(array $menuItems, string $locale = 'default', bool $composerInputDisabled = false): ArrayObject
    {
        return $this->postRequest('me/messenger_profile', [
            'persistent_menu' => [[
                'locale'                  => $locale,
                'composer_input_disabled' => $composerInputDisabled,
                'call_to_actions'         => $menuItems,
            ]],
        ]);
    }

    /**
     * Delete one or more messenger profile properties.
     * $properties: e.g., ['persistent_menu', 'get_started', 'greeting']
     */
    public function deleteMessengerProfile(array $properties): ArrayObject
    {
        return $this->deleteRequest('me/messenger_profile', ['fields' => $properties]);
    }

    /**
     * Set a greeting text shown on the welcome screen before a conversation starts.
     * $greetings: [['locale' => 'default', 'text' => 'Hello!']]
     */
    public function setGreeting(array $greetings): ArrayObject
    {
        return $this->postRequest('me/messenger_profile', ['greeting' => $greetings]);
    }

    /**
     * Build and send a message payload to me/messages.
     */
    private function sendMessage(string $recipientId, array $message, string $messagingType = 'RESPONSE'): ArrayObject
    {
        return $this->postRequest('me/messages', [
            'recipient'      => ['id' => $recipientId],
            'message'        => $message,
            'messaging_type' => $messagingType,
        ]);
    }

    /**
     * Build and send an attachment message (image, video, audio, file).
     */
    private function sendAttachment(string $recipientId, string $type, string $url, bool $reusable): ArrayObject
    {
        return $this->sendMessage($recipientId, [
            'attachment' => [
                'type'    => $type,
                'payload' => [
                    'url'         => $url,
                    'is_reusable' => $reusable,
                ],
            ],
        ]);
    }

    /**
     * Send a POST request with JSON body and the page access token appended.
     */
    private function postRequest(string $path, array $fields): ArrayObject
    {
        $url = self::API_BASE . $path . '?access_token=' . $this->pageAccessToken;

        $cURL = new ClientURL($url);
        $cURL->option
            ->setURL($url)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setReturnTransfer()
            ->setPostMethod()
            ->setHeader('Content-Type', 'application/json')
            ->setPostFields($fields);

        return JSONHandler::decode($cURL->execute(), true);
    }

    /**
     * Send a GET request and return decoded response.
     */
    private function getRequest(string $url): ArrayObject
    {
        $cURL = new ClientURL($url);
        $cURL->option
            ->setURL($url)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setReturnTransfer();

        return JSONHandler::decode($cURL->execute(), true);
    }

    /**
     * Send a DELETE request with JSON body and the page access token appended.
     */
    private function deleteRequest(string $path, array $fields): ArrayObject
    {
        $url = self::API_BASE . $path . '?access_token=' . $this->pageAccessToken;

        $cURL = new ClientURL($url);
        $cURL->option
            ->setURL($url)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setReturnTransfer()
            ->setCustomRequest('DELETE')
            ->setHeader('Content-Type', 'application/json')
            ->setPostFields($fields);

        return JSONHandler::decode($cURL->execute(), true);
    }
}
