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

class Slack
{
    private string $token;

    private const API_BASE = 'https://slack.com/api/';

    /**
     * Set the Slack Bot OAuth access token.
     */
    public function setToken(string $token): void
    {
        $this->token = $token;
    }

    /**
     * Verify the token and get the associated bot/user identity.
     */
    public function authTest(): ArrayObject
    {
        return $this->postRequest('auth.test');
    }

    /**
     * Post a message to a channel, DM, or group.
     *
     * Supported $options keys:
     *   thread_ts         string  Post as a reply in a thread
     *   blocks            array   Block Kit layout blocks
     *   attachments       array   Legacy attachments
     *   parse             string  none | full — how to handle markup
     *   unfurl_links      bool
     *   unfurl_media      bool
     *   username          string  Override bot display name
     *   icon_emoji        string  Override bot icon (e.g., ":robot_face:")
     *   icon_url          string  Override bot icon with an image URL
     *   mrkdwn            bool    Enable Slack markdown (default true)
     *   reply_broadcast   bool    Also send the reply to the channel
     */
    public function postMessage(string $channel, string $text = '', array $options = []): ArrayObject
    {
        return $this->postRequest('chat.postMessage', array_merge([
            'channel' => $channel,
            'text'    => $text,
        ], $options));
    }

    /**
     * Update an existing message.
     *
     * Supported $options keys:
     *   blocks       array
     *   attachments  array
     *   parse        string
     *   link_names   bool
     */
    public function updateMessage(string $channel, string $ts, string $text = '', array $options = []): ArrayObject
    {
        return $this->postRequest('chat.update', array_merge([
            'channel' => $channel,
            'ts'      => $ts,
            'text'    => $text,
        ], $options));
    }

    /**
     * Delete a message.
     */
    public function deleteMessage(string $channel, string $ts): ArrayObject
    {
        return $this->postRequest('chat.delete', [
            'channel' => $channel,
            'ts'      => $ts,
        ]);
    }

    /**
     * Post an ephemeral message visible only to a specific user in a channel.
     */
    public function postEphemeral(string $channel, string $userId, string $text = '', array $options = []): ArrayObject
    {
        return $this->postRequest('chat.postEphemeral', array_merge([
            'channel' => $channel,
            'user'    => $userId,
            'text'    => $text,
        ], $options));
    }

    /**
     * Schedule a message to be sent at a future Unix timestamp.
     */
    public function scheduleMessage(string $channel, int $postAt, string $text = '', array $options = []): ArrayObject
    {
        return $this->postRequest('chat.scheduleMessage', array_merge([
            'channel' => $channel,
            'post_at' => $postAt,
            'text'    => $text,
        ], $options));
    }

    /**
     * Add a reaction (emoji) to a message.
     * $name should be the emoji name without colons (e.g., "thumbsup").
     */
    public function addReaction(string $channel, string $ts, string $name): ArrayObject
    {
        return $this->postRequest('reactions.add', [
            'channel'   => $channel,
            'timestamp' => $ts,
            'name'      => $name,
        ]);
    }

    /**
     * Remove a reaction from a message.
     */
    public function removeReaction(string $channel, string $ts, string $name): ArrayObject
    {
        return $this->postRequest('reactions.remove', [
            'channel'   => $channel,
            'timestamp' => $ts,
            'name'      => $name,
        ]);
    }

    /**
     * List all public channels the bot has access to.
     *
     * Supported $options keys:
     *   types   string  Comma-separated: public_channel, private_channel, mpim, im
     *   limit   int     Max results per page (default 100, max 1000)
     *   cursor  string  Pagination cursor from previous response
     */
    public function listChannels(array $options = []): ArrayObject
    {
        return $this->postRequest('conversations.list', $options);
    }

    /**
     * Get information about a conversation (channel, DM, etc.).
     */
    public function getChannelInfo(string $channel): ArrayObject
    {
        return $this->postRequest('conversations.info', ['channel' => $channel]);
    }

    /**
     * Create a new public or private channel.
     */
    public function createChannel(string $name, bool $isPrivate = false): ArrayObject
    {
        return $this->postRequest('conversations.create', [
            'name'       => $name,
            'is_private' => $isPrivate,
        ]);
    }

    /**
     * Archive a channel.
     */
    public function archiveChannel(string $channel): ArrayObject
    {
        return $this->postRequest('conversations.archive', ['channel' => $channel]);
    }

    /**
     * Invite users to a channel.
     * $userIds should be a comma-separated string of user IDs.
     */
    public function inviteToChannel(string $channel, string $userIds): ArrayObject
    {
        return $this->postRequest('conversations.invite', [
            'channel' => $channel,
            'users'   => $userIds,
        ]);
    }

    /**
     * Get a conversation's message history.
     *
     * Supported $options keys:
     *   oldest    string  Start of time range (Unix timestamp)
     *   latest    string  End of time range (Unix timestamp)
     *   limit     int     Max messages to return
     *   cursor    string  Pagination cursor
     *   inclusive bool    Include messages at oldest and latest boundaries
     */
    public function getChannelHistory(string $channel, array $options = []): ArrayObject
    {
        return $this->postRequest('conversations.history', array_merge(['channel' => $channel], $options));
    }

    /**
     * Retrieve a thread's replies.
     */
    public function getReplies(string $channel, string $ts, array $options = []): ArrayObject
    {
        return $this->postRequest('conversations.replies', array_merge([
            'channel' => $channel,
            'ts'      => $ts,
        ], $options));
    }

    /**
     * Get basic profile information about a user.
     */
    public function getUserInfo(string $userId): ArrayObject
    {
        return $this->postRequest('users.info', ['user' => $userId]);
    }

    /**
     * List all users in the workspace.
     *
     * Supported $options keys:
     *   limit   int     Max results per page
     *   cursor  string  Pagination cursor
     */
    public function listUsers(array $options = []): ArrayObject
    {
        return $this->postRequest('users.list', $options);
    }

    /**
     * Look up a user by email address.
     */
    public function lookupUserByEmail(string $email): ArrayObject
    {
        return $this->postRequest('users.lookupByEmail', ['email' => $email]);
    }

    /**
     * Open (or retrieve) a DM channel with one or more users.
     * $users should be a comma-separated string of user IDs.
     */
    public function openDM(string $users): ArrayObject
    {
        return $this->postRequest('conversations.open', ['users' => $users]);
    }

    /**
     * Publish a modal or home tab view.
     *
     * $view must be a valid Block Kit view object.
     * Use $triggerId for modals, $userId for home tab.
     */
    public function publishView(string $userId, array $view): ArrayObject
    {
        return $this->postRequest('views.publish', [
            'user_id' => $userId,
            'view'    => $view,
        ]);
    }

    /**
     * Open a modal triggered by an interaction payload.
     */
    public function openModal(string $triggerId, array $view): ArrayObject
    {
        return $this->postRequest('views.open', [
            'trigger_id' => $triggerId,
            'view'       => $view,
        ]);
    }

    /**
     * Upload a file or snippet to a channel.
     *
     * Supported $options keys:
     *   channels   string  Comma-separated channel IDs to share in
     *   filename   string
     *   filetype   string  e.g., "php", "json", "text"
     *   title      string
     *   initial_comment string  Message text to accompany the file
     *   thread_ts  string  Post in a thread
     */
    public function uploadFile(string $content, array $options = []): ArrayObject
    {
        return $this->postRequest('files.upload', array_merge(['content' => $content], $options));
    }

    /**
     * Send a POST request with JSON body to the Slack Web API and return decoded response.
     */
    private function postRequest(string $method, array $fields = []): ArrayObject
    {
        $url = self::API_BASE . $method;

        $cURL = new ClientURL($url);
        $cURL->option
            ->setURL($url)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setReturnTransfer()
            ->setPostMethod()
            ->setHeader('Authorization', 'Bearer ' . $this->token)
            ->setHeader('Content-Type', 'application/json; charset=utf-8')
            ->setPostFields($fields);

        return JSONHandler::decode($cURL->execute(), true);
    }
}
