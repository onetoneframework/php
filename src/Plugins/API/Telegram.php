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

class Telegram
{
    private string $token;

    private const API_BASE = 'https://api.telegram.org/bot';

    /**
     * Set the Telegram Bot API token.
     */
    public function setToken(string $token): void
    {
        $this->token = $token;
    }

    /**
     * Return basic information about the bot.
     * Useful for verifying that the token is valid.
     */
    public function getMe(): ArrayObject
    {
        return $this->getRequest('getMe');
    }

    /**
     * Receive incoming updates via long polling.
     *
     * Supported $options keys:
     *   offset          int      Identifier of the first update to be returned
     *   limit           int      1–100 (default 100)
     *   timeout         int      Timeout in seconds for long polling
     *   allowed_updates string[] List of update types to receive
     */
    public function getUpdates(array $options = []): ArrayObject
    {
        return $this->postRequest('getUpdates', $options);
    }

    /**
     * Register a webhook URL to receive incoming updates.
     *
     * Supported $options keys:
     *   certificate         string   Public key certificate (PEM)
     *   ip_address          string   Fixed IP address for the webhook
     *   max_connections     int      1–100 simultaneous HTTPS connections (default 40)
     *   allowed_updates     string[] List of update types to receive
     *   drop_pending_updates bool    Drop all pending updates on registration
     *   secret_token        string   Token sent in X-Telegram-Bot-Api-Secret-Token header
     */
    public function setWebhook(string $url, array $options = []): ArrayObject
    {
        return $this->postRequest('setWebhook', array_merge(['url' => $url], $options));
    }

    /**
     * Remove the registered webhook.
     * Pass true to $dropPendingUpdates to discard all pending updates.
     */
    public function deleteWebhook(bool $dropPendingUpdates = false): ArrayObject
    {
        return $this->postRequest('deleteWebhook', ['drop_pending_updates' => $dropPendingUpdates]);
    }

    /**
     * Get current webhook configuration and status.
     */
    public function getWebhookInfo(): ArrayObject
    {
        return $this->getRequest('getWebhookInfo');
    }

    /**
     * Send a text message to a chat.
     *
     * Supported $options keys:
     *   message_thread_id        int     Unique identifier for the target message thread
     *   parse_mode               string  HTML | Markdown | MarkdownV2
     *   entities                 array   Special entities in the message text
     *   disable_web_page_preview bool    Disable link previews
     *   disable_notification     bool    Send message silently
     *   protect_content          bool    Protect message from forwarding and saving
     *   reply_to_message_id      int     Reply to a specific message
     *   reply_markup             array   InlineKeyboardMarkup | ReplyKeyboardMarkup etc.
     */
    public function sendMessage(int|string $chatId, string $text, array $options = []): ArrayObject
    {
        return $this->postRequest('sendMessage', array_merge([
            'chat_id' => $chatId,
            'text'    => $text,
        ], $options));
    }

    /**
     * Forward a message from one chat to another.
     */
    public function forwardMessage(int|string $chatId, int|string $fromChatId, int $messageId, array $options = []): ArrayObject
    {
        return $this->postRequest('forwardMessage', array_merge([
            'chat_id'      => $chatId,
            'from_chat_id' => $fromChatId,
            'message_id'   => $messageId,
        ], $options));
    }

    /**
     * Copy a message to another chat without the original sender information.
     *
     * Supported $options keys: same as sendMessage plus caption, caption_entities
     */
    public function copyMessage(int|string $chatId, int|string $fromChatId, int $messageId, array $options = []): ArrayObject
    {
        return $this->postRequest('copyMessage', array_merge([
            'chat_id'      => $chatId,
            'from_chat_id' => $fromChatId,
            'message_id'   => $messageId,
        ], $options));
    }

    /**
     * Send a photo.
     * $photo can be a file_id, URL, or a local file path prefixed with "@".
     *
     * Supported $options keys:
     *   caption              string  Photo caption (0–1024 characters)
     *   parse_mode           string  HTML | Markdown | MarkdownV2
     *   has_spoiler          bool    Mark photo as a spoiler
     *   disable_notification bool
     *   reply_to_message_id  int
     *   reply_markup         array
     */
    public function sendPhoto(int|string $chatId, string $photo, array $options = []): ArrayObject
    {
        return $this->postRequest('sendPhoto', array_merge([
            'chat_id' => $chatId,
            'photo'   => $photo,
        ], $options));
    }

    /**
     * Send a document (any file type).
     *
     * Supported $options keys:
     *   caption                          string
     *   parse_mode                       string
     *   disable_content_type_detection   bool    Treat file as a document regardless of MIME type
     *   disable_notification             bool
     *   reply_to_message_id              int
     *   reply_markup                     array
     */
    public function sendDocument(int|string $chatId, string $document, array $options = []): ArrayObject
    {
        return $this->postRequest('sendDocument', array_merge([
            'chat_id'  => $chatId,
            'document' => $document,
        ], $options));
    }

    /**
     * Send a video.
     *
     * Supported $options keys:
     *   duration             int
     *   width                int
     *   height               int
     *   caption              string
     *   parse_mode           string
     *   has_spoiler          bool
     *   supports_streaming   bool
     *   disable_notification bool
     *   reply_to_message_id  int
     *   reply_markup         array
     */
    public function sendVideo(int|string $chatId, string $video, array $options = []): ArrayObject
    {
        return $this->postRequest('sendVideo', array_merge([
            'chat_id' => $chatId,
            'video'   => $video,
        ], $options));
    }

    /**
     * Send an audio file (MP3, M4A, etc.).
     *
     * Supported $options keys:
     *   caption              string
     *   parse_mode           string
     *   duration             int
     *   performer            string
     *   title                string
     *   disable_notification bool
     *   reply_to_message_id  int
     *   reply_markup         array
     */
    public function sendAudio(int|string $chatId, string $audio, array $options = []): ArrayObject
    {
        return $this->postRequest('sendAudio', array_merge([
            'chat_id' => $chatId,
            'audio'   => $audio,
        ], $options));
    }

    /**
     * Edit the text of a previously sent message.
     *
     * Supported $options keys:
     *   inline_message_id    string  Required if chat_id and message_id are not provided
     *   parse_mode           string
     *   entities             array
     *   disable_web_page_preview bool
     *   reply_markup         array
     */
    public function editMessageText(int|string $chatId, int $messageId, string $text, array $options = []): ArrayObject
    {
        return $this->postRequest('editMessageText', array_merge([
            'chat_id'    => $chatId,
            'message_id' => $messageId,
            'text'       => $text,
        ], $options));
    }

    /**
     * Edit only the reply markup (inline keyboard) of a message.
     */
    public function editMessageReplyMarkup(int|string $chatId, int $messageId, array $replyMarkup): ArrayObject
    {
        return $this->postRequest('editMessageReplyMarkup', [
            'chat_id'      => $chatId,
            'message_id'   => $messageId,
            'reply_markup' => $replyMarkup,
        ]);
    }

    /**
     * Delete a message. A message can only be deleted if it was sent less than 48 hours ago.
     */
    public function deleteMessage(int|string $chatId, int $messageId): ArrayObject
    {
        return $this->postRequest('deleteMessage', [
            'chat_id'    => $chatId,
            'message_id' => $messageId,
        ]);
    }

    /**
     * Send answers to callback queries from inline keyboard buttons.
     *
     * Supported $options keys:
     *   text       string  Notification text shown to the user (0–200 characters)
     *   show_alert bool    Show an alert instead of a notification
     *   url        string  URL to be opened by the client
     *   cache_time int     Maximum cache time in seconds (default 0)
     */
    public function answerCallbackQuery(string $callbackQueryId, array $options = []): ArrayObject
    {
        return $this->postRequest('answerCallbackQuery', array_merge([
            'callback_query_id' => $callbackQueryId,
        ], $options));
    }

    /**
     * Get information about a chat (group, supergroup, or channel).
     */
    public function getChat(int|string $chatId): ArrayObject
    {
        return $this->postRequest('getChat', ['chat_id' => $chatId]);
    }

    /**
     * Get information about a member of a chat.
     */
    public function getChatMember(int|string $chatId, int $userId): ArrayObject
    {
        return $this->postRequest('getChatMember', [
            'chat_id' => $chatId,
            'user_id' => $userId,
        ]);
    }

    /**
     * Get the number of members in a chat.
     */
    public function getChatMemberCount(int|string $chatId): ArrayObject
    {
        return $this->postRequest('getChatMemberCount', ['chat_id' => $chatId]);
    }

    /**
     * Ban (kick) a user from a group, supergroup, or channel.
     *
     * Supported $options keys:
     *   until_date        int   Ban duration as a Unix timestamp; 0 or absent means permanent
     *   revoke_messages   bool  Delete all messages from the user in the group
     */
    public function banChatMember(int|string $chatId, int $userId, array $options = []): ArrayObject
    {
        return $this->postRequest('banChatMember', array_merge([
            'chat_id' => $chatId,
            'user_id' => $userId,
        ], $options));
    }

    /**
     * Unban a previously banned user.
     * The user will not return to the group automatically; they must be re-invited.
     */
    public function unbanChatMember(int|string $chatId, int $userId, bool $onlyIfBanned = true): ArrayObject
    {
        return $this->postRequest('unbanChatMember', [
            'chat_id'        => $chatId,
            'user_id'        => $userId,
            'only_if_banned' => $onlyIfBanned,
        ]);
    }

    /**
     * Send a custom action indicator (e.g., "typing", "upload_photo").
     *
     * Valid $action values:
     *   typing | upload_photo | record_video | upload_video |
     *   record_voice | upload_voice | upload_document |
     *   choose_sticker | find_location | record_video_note | upload_video_note
     */
    public function sendChatAction(int|string $chatId, string $action): ArrayObject
    {
        return $this->postRequest('sendChatAction', [
            'chat_id' => $chatId,
            'action'  => $action,
        ]);
    }

    /**
     * Build the full API endpoint URL for a given method.
     */
    private function buildUrl(string $method): string
    {
        return self::API_BASE . $this->token . '/' . $method;
    }

    /**
     * Send a POST request with JSON body and return decoded response.
     */
    private function postRequest(string $method, array $fields = []): ArrayObject
    {
        $url  = $this->buildUrl($method);
        $body = json_encode($fields);

        $cURL = new ClientURL($url);
        $cURL->option
            ->setURL($url)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setReturnTransfer()
            ->setPostMethod()
            ->setHeader('Content-Type', 'application/json')
            ->setPostField($body);

        return JSONHandler::decode($cURL->execute(), true);
    }

    /**
     * Send a GET request and return decoded response.
     */
    private function getRequest(string $method): ArrayObject
    {
        $url = $this->buildUrl($method);

        $cURL = new ClientURL($url);
        $cURL->option
            ->setURL($url)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setReturnTransfer();

        return JSONHandler::decode($cURL->execute(), true);
    }
}
