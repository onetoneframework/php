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

class WhatsApp
{
    private string $token;
    private string $phoneNumberId;

    private const API_BASE = 'https://graph.facebook.com/v17.0/';

    /**
     * Set the WhatsApp Business Cloud API access token.
     */
    public function setToken(string $token): void
    {
        $this->token = $token;
    }

    /**
     * Set the WhatsApp Business phone number ID (found in Meta Developer Console).
     */
    public function setPhoneNumberId(string $phoneNumberId): void
    {
        $this->phoneNumberId = $phoneNumberId;
    }

    /**
     * Send a plain text message to a recipient.
     *
     * $to must be a full phone number including country code (e.g., "821012345678").
     */
    public function sendTextMessage(string $to, string $text, bool $previewUrl = false): ArrayObject
    {
        return $this->postRequest('messages', [
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $to,
            'type'              => 'text',
            'text'              => [
                'body'        => $text,
                'preview_url' => $previewUrl,
            ],
        ]);
    }

    /**
     * Send a pre-approved message template.
     *
     * $components format: [['type' => 'body', 'parameters' => [['type' => 'text', 'text' => 'value']]]]
     */
    public function sendTemplate(string $to, string $templateName, string $languageCode = 'en_US', array $components = []): ArrayObject
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'to'                => $to,
            'type'              => 'template',
            'template'          => [
                'name'     => $templateName,
                'language' => ['code' => $languageCode],
            ],
        ];

        if (!empty($components)) {
            $payload['template']['components'] = $components;
        }

        return $this->postRequest('messages', $payload);
    }

    /**
     * Send an image message by URL or media ID.
     */
    public function sendImage(string $to, string $imageId, bool $isUrl = false, string $caption = ''): ArrayObject
    {
        $image = $isUrl ? ['link' => $imageId] : ['id' => $imageId];

        if ($caption !== '') {
            $image['caption'] = $caption;
        }

        return $this->postRequest('messages', [
            'messaging_product' => 'whatsapp',
            'to'                => $to,
            'type'              => 'image',
            'image'             => $image,
        ]);
    }

    /**
     * Send a document by URL or media ID.
     */
    public function sendDocument(string $to, string $documentId, bool $isUrl = false, string $caption = '', string $filename = ''): ArrayObject
    {
        $document = $isUrl ? ['link' => $documentId] : ['id' => $documentId];

        if ($caption !== '') {
            $document['caption'] = $caption;
        }

        if ($filename !== '') {
            $document['filename'] = $filename;
        }

        return $this->postRequest('messages', [
            'messaging_product' => 'whatsapp',
            'to'                => $to,
            'type'              => 'document',
            'document'          => $document,
        ]);
    }

    /**
     * Send a video by URL or media ID.
     */
    public function sendVideo(string $to, string $videoId, bool $isUrl = false, string $caption = ''): ArrayObject
    {
        $video = $isUrl ? ['link' => $videoId] : ['id' => $videoId];

        if ($caption !== '') {
            $video['caption'] = $caption;
        }

        return $this->postRequest('messages', [
            'messaging_product' => 'whatsapp',
            'to'                => $to,
            'type'              => 'video',
            'video'             => $video,
        ]);
    }

    /**
     * Send an audio file by URL or media ID.
     */
    public function sendAudio(string $to, string $audioId, bool $isUrl = false): ArrayObject
    {
        $audio = $isUrl ? ['link' => $audioId] : ['id' => $audioId];

        return $this->postRequest('messages', [
            'messaging_product' => 'whatsapp',
            'to'                => $to,
            'type'              => 'audio',
            'audio'             => $audio,
        ]);
    }

    /**
     * Send an interactive message with reply buttons (up to 3).
     *
     * $buttons format: [['id' => 'btn1', 'title' => 'Click me']]
     */
    public function sendInteractiveButtons(string $to, string $bodyText, array $buttons): ArrayObject
    {
        $buttonList = array_map(fn($btn) => [
            'type'  => 'reply',
            'reply' => ['id' => $btn['id'], 'title' => $btn['title']],
        ], $buttons);

        return $this->postRequest('messages', [
            'messaging_product' => 'whatsapp',
            'to'                => $to,
            'type'              => 'interactive',
            'interactive'       => [
                'type'   => 'button',
                'body'   => ['text' => $bodyText],
                'action' => ['buttons' => $buttonList],
            ],
        ]);
    }

    /**
     * Send an interactive list message (up to 10 rows across sections).
     *
     * $sections format: [['title' => 'Section 1', 'rows' => [['id' => 'r1', 'title' => 'Option', 'description' => '']]]]
     */
    public function sendInteractiveList(string $to, string $bodyText, string $buttonLabel, array $sections): ArrayObject
    {
        return $this->postRequest('messages', [
            'messaging_product' => 'whatsapp',
            'to'                => $to,
            'type'              => 'interactive',
            'interactive'       => [
                'type'   => 'list',
                'body'   => ['text' => $bodyText],
                'action' => [
                    'button'   => $buttonLabel,
                    'sections' => $sections,
                ],
            ],
        ]);
    }

    /**
     * Mark a received message as read.
     */
    public function markAsRead(string $messageId): ArrayObject
    {
        return $this->postRequest('messages', [
            'messaging_product' => 'whatsapp',
            'status'            => 'read',
            'message_id'        => $messageId,
        ]);
    }

    /**
     * Retrieve the download URL for an uploaded media asset.
     */
    public function getMediaUrl(string $mediaId): ArrayObject
    {
        return $this->getRequest($mediaId);
    }

    /**
     * Delete an uploaded media asset by its ID.
     */
    public function deleteMedia(string $mediaId): ArrayObject
    {
        return $this->deleteRequest($mediaId);
    }

    /**
     * Get the WhatsApp Business Account profile.
     */
    public function getBusinessProfile(): ArrayObject
    {
        return $this->getRequest($this->phoneNumberId . '/whatsapp_business_profile');
    }

    /**
     * Build the full API endpoint URL.
     */
    private function buildUrl(string $path): string
    {
        return self::API_BASE . $this->phoneNumberId . '/' . $path;
    }

    /**
     * Send a POST request with JSON body and return decoded response.
     */
    private function postRequest(string $path, array $fields): ArrayObject
    {
        $url = $this->buildUrl($path);

        $cURL = new ClientURL($url);
        $cURL->option
            ->setURL($url)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setReturnTransfer()
            ->setPostMethod()
            ->setHeader('Authorization', 'Bearer ' . $this->token)
            ->setHeader('Content-Type', 'application/json')
            ->setPostFields($fields);

        return JSONHandler::decode($cURL->execute(), true);
    }

    /**
     * Send a GET request with Bearer auth and return decoded response.
     */
    private function getRequest(string $path): ArrayObject
    {
        $url = self::API_BASE . $path;

        $cURL = new ClientURL($url);
        $cURL->option
            ->setURL($url)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setReturnTransfer()
            ->setHeader('Authorization', 'Bearer ' . $this->token);

        return JSONHandler::decode($cURL->execute(), true);
    }

    /**
     * Send a DELETE request with Bearer auth and return decoded response.
     */
    private function deleteRequest(string $path): ArrayObject
    {
        $url = self::API_BASE . $path;

        $cURL = new ClientURL($url);
        $cURL->option
            ->setURL($url)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setReturnTransfer()
            ->setCustomRequest('DELETE')
            ->setHeader('Authorization', 'Bearer ' . $this->token);

        return JSONHandler::decode($cURL->execute(), true);
    }
}
