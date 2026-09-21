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

class QQ
{
    private string $appId;
    private string $clientSecret;
    private ?string $accessToken  = null;
    private int     $tokenExpiry  = 0;

    private const API_BASE  = 'https://api.sgroup.qq.com/';
    private const AUTH_URL  = 'https://bots.qq.com/app/getAppAccessToken';

    /**
     * Set the QQ Bot App ID from the QQ Developer Portal.
     */
    public function setAppId(string $appId): void
    {
        $this->appId = $appId;
    }

    /**
     * Set the QQ Bot client secret from the QQ Developer Portal.
     */
    public function setClientSecret(string $secret): void
    {
        $this->clientSecret = $secret;
    }

    /**
     * Fetch and cache an app access token.
     * Tokens expire in 7200 seconds; this method refreshes automatically when expired.
     */
    public function getAccessToken(): string
    {
        if ($this->accessToken !== null && time() < $this->tokenExpiry) {
            return $this->accessToken;
        }

        $url = self::AUTH_URL;

        $cURL = new ClientURL($url);
        $cURL->option
            ->setURL($url)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setReturnTransfer()
            ->setPostMethod()
            ->setHeader('Content-Type', 'application/json')
            ->setPostFields([
                'appId'        => $this->appId,
                'clientSecret' => $this->clientSecret,
            ]);

        $result = JSONHandler::decode($cURL->execute(), true);

        $this->accessToken = $result['access_token'];
        $this->tokenExpiry = time() + (int) $result['expires_in'] - 60;

        return $this->accessToken;
    }

    /**
     * Get basic information about the bot itself.
     */
    public function getMe(): ArrayObject
    {
        return $this->getRequest('users/@me');
    }

    /**
     * Get the list of guilds (servers) the bot belongs to.
     *
     * Supported $options keys:
     *   before  string  Get guilds before this guild ID (pagination)
     *   after   string  Get guilds after this guild ID (pagination)
     *   limit   int     Max results (1–100, default 100)
     */
    public function getGuilds(array $options = []): ArrayObject
    {
        $query = !empty($options) ? '?' . http_build_query($options) : '';
        return $this->getRequest('users/@me/guilds' . $query);
    }

    /**
     * Get detailed information about a guild.
     */
    public function getGuild(string $guildId): ArrayObject
    {
        return $this->getRequest('guilds/' . $guildId);
    }

    /**
     * Get all channels in a guild.
     */
    public function getChannels(string $guildId): ArrayObject
    {
        return $this->getRequest('guilds/' . $guildId . '/channels');
    }

    /**
     * Get information about a specific channel.
     */
    public function getChannel(string $channelId): ArrayObject
    {
        return $this->getRequest('channels/' . $channelId);
    }

    /**
     * Create a new channel in a guild.
     *
     * Supported $options keys:
     *   type          int     0 = text, 2 = voice, 4 = category, 10005 = live
     *   sub_type      int     0 = talk, 1 = post, 2 = open, 3 = notification
     *   position      int     Sorting order
     *   parent_id     string  Parent category channel ID
     *   private_type  int     0 = public, 1 = admin only, 2 = specified members only
     *   speak_permission int  0 = anyone, 1 = admin and specified members
     */
    public function createChannel(string $guildId, string $name, int $type = 0, array $options = []): ArrayObject
    {
        return $this->postRequest('guilds/' . $guildId . '/channels', array_merge([
            'name' => $name,
            'type' => $type,
        ], $options));
    }

    /**
     * Modify a channel's settings.
     */
    public function updateChannel(string $channelId, array $fields): ArrayObject
    {
        return $this->patchRequest('channels/' . $channelId, $fields);
    }

    /**
     * Delete a channel.
     */
    public function deleteChannel(string $channelId): ArrayObject
    {
        return $this->deleteRequest('channels/' . $channelId);
    }

    /**
     * Get a list of members in a guild.
     *
     * Supported $options keys:
     *   after   string  Fetch members after this user ID (pagination)
     *   limit   int     Max results (1–400, default 1)
     */
    public function getGuildMembers(string $guildId, array $options = []): ArrayObject
    {
        $query = !empty($options) ? '?' . http_build_query($options) : '';
        return $this->getRequest('guilds/' . $guildId . '/members' . $query);
    }

    /**
     * Get information about a specific guild member.
     */
    public function getGuildMember(string $guildId, string $userId): ArrayObject
    {
        return $this->getRequest('guilds/' . $guildId . '/members/' . $userId);
    }

    /**
     * Remove a member from a guild.
     *
     * Supported $options keys:
     *   add_blacklist       bool  Add to guild blacklist
     *   delete_history_msg_days int  Delete message history (0 | 3 | 7 | 15 | 30; -1 = all)
     */
    public function deleteGuildMember(string $guildId, string $userId, array $options = []): ArrayObject
    {
        return $this->deleteRequest('guilds/' . $guildId . '/members/' . $userId, $options);
    }

    /**
     * Get the roles defined in a guild.
     */
    public function getGuildRoles(string $guildId): ArrayObject
    {
        return $this->getRequest('guilds/' . $guildId . '/roles');
    }

    /**
     * Create a new role in a guild.
     *
     * $filter keys: name (bool), color (bool), hoist (bool)
     * $info keys: name (string), color (int — ARGB), hoist (int — 0|1)
     */
    public function createGuildRole(string $guildId, array $filter, array $info): ArrayObject
    {
        return $this->postRequest('guilds/' . $guildId . '/roles', [
            'filter' => $filter,
            'info'   => $info,
        ]);
    }

    /**
     * Assign a role to a guild member.
     */
    public function addMemberRole(string $guildId, string $userId, string $roleId, ?string $channelId = null): ArrayObject
    {
        $payload = [];

        if ($channelId !== null) {
            $payload['channel'] = ['id' => $channelId];
        }

        return $this->putRequest('guilds/' . $guildId . '/members/' . $userId . '/roles/' . $roleId, $payload);
    }

    /**
     * Remove a role from a guild member.
     */
    public function removeMemberRole(string $guildId, string $userId, string $roleId, ?string $channelId = null): ArrayObject
    {
        $payload = [];

        if ($channelId !== null) {
            $payload['channel'] = ['id' => $channelId];
        }

        return $this->deleteRequest('guilds/' . $guildId . '/members/' . $userId . '/roles/' . $roleId, $payload);
    }

    /**
     * Get a specific message from a channel.
     */
    public function getMessage(string $channelId, string $messageId): ArrayObject
    {
        return $this->getRequest('channels/' . $channelId . '/messages/' . $messageId);
    }

    /**
     * Send a message to a text channel.
     *
     * At least one of $content, $embed, or $fileImage must be provided.
     *
     * Supported $options keys:
     *   embed            array   Rich embed object (author, title, prompt, thumbnail, image, desc, fields)
     *   ark              array   Ark (template) message object
     *   message_reference array  Reply reference: ['message_id' => '...']
     *   file_image       string  Base64-encoded image (PNG/JPEG/GIF, max 5MB)
     *   markdown         array   Markdown message object
     *   keyboard         array   Inline keyboard attached to a markdown message
     */
    public function sendMessage(string $channelId, string $content = '', array $options = []): ArrayObject
    {
        $payload = array_merge(['content' => $content], $options);
        return $this->postRequest('channels/' . $channelId . '/messages', $payload);
    }

    /**
     * Recall (delete) a message sent by the bot.
     *
     * $hidetip: suppress the "message recalled" tip in the channel.
     */
    public function deleteMessage(string $channelId, string $messageId, bool $hideTip = false): ArrayObject
    {
        return $this->deleteRequest(
            'channels/' . $channelId . '/messages/' . $messageId . '?hidetip=' . ($hideTip ? 'true' : 'false')
        );
    }

    /**
     * Send a direct message to a user (requires an active DM guild session).
     * Call openDM() first to obtain the guild_id for the DM session.
     */
    public function sendDirectMessage(string $guildId, string $content = '', array $options = []): ArrayObject
    {
        $payload = array_merge(['content' => $content], $options);
        return $this->postRequest('dms/' . $guildId . '/messages', $payload);
    }

    /**
     * Open a DM session with a user and return the DM guild_id.
     */
    public function openDM(string $recipientId, string $sourceGuildId): ArrayObject
    {
        return $this->postRequest('users/@me/dms', [
            'recipient_id'    => $recipientId,
            'source_guild_id' => $sourceGuildId,
        ]);
    }

    /**
     * Put a message reaction (emoji) on a channel message.
     *
     * $emojiType: 1 = system emoji, 2 = custom emoji
     * $emojiId:   emoji ID or Unicode code point
     */
    public function addReaction(string $channelId, string $messageId, int $emojiType, string $emojiId): ArrayObject
    {
        return $this->putRequest(
            'channels/' . $channelId . '/messages/' . $messageId . '/reactions/' . $emojiType . '/' . $emojiId
        );
    }

    /**
     * Remove the bot's reaction from a message.
     */
    public function removeReaction(string $channelId, string $messageId, int $emojiType, string $emojiId): ArrayObject
    {
        return $this->deleteRequest(
            'channels/' . $channelId . '/messages/' . $messageId . '/reactions/' . $emojiType . '/' . $emojiId
        );
    }

    /**
     * Get the list of users who reacted with a given emoji.
     *
     * Supported $options keys:
     *   cookie  string  Pagination cursor from previous response
     *   limit   int     Max results (1–50, default 20)
     */
    public function getReactionUsers(string $channelId, string $messageId, int $emojiType, string $emojiId, array $options = []): ArrayObject
    {
        $query = !empty($options) ? '?' . http_build_query($options) : '';
        return $this->getRequest(
            'channels/' . $channelId . '/messages/' . $messageId . '/reactions/' . $emojiType . '/' . $emojiId . $query
        );
    }

    /**
     * Get the announcement message pinned in a channel.
     */
    public function getChannelPins(string $channelId): ArrayObject
    {
        return $this->getRequest('channels/' . $channelId . '/pins');
    }

    /**
     * Pin a message as an announcement in a channel.
     */
    public function pinMessage(string $channelId, string $messageId): ArrayObject
    {
        return $this->putRequest('channels/' . $channelId . '/pins/' . $messageId);
    }

    /**
     * Unpin a message from a channel.
     */
    public function unpinMessage(string $channelId, string $messageId): ArrayObject
    {
        return $this->deleteRequest('channels/' . $channelId . '/pins/' . $messageId);
    }

    /**
     * Mute the entire guild for a specified duration.
     *
     * $options must contain exactly one of:
     *   mute_end_timestamp  string  Unix timestamp when mute ends
     *   mute_seconds        string  Duration in seconds
     * Pass an empty $options (or both values as "0") to unmute.
     */
    public function muteGuild(string $guildId, array $options): ArrayObject
    {
        return $this->patchRequest('guilds/' . $guildId . '/mute', $options);
    }

    /**
     * Mute a specific guild member.
     * Same $options keys as muteGuild().
     */
    public function muteMember(string $guildId, string $userId, array $options): ArrayObject
    {
        return $this->patchRequest('guilds/' . $guildId . '/members/' . $userId . '/mute', $options);
    }

    /**
     * Build the Authorization header value using the cached access token.
     */
    private function authHeader(): string
    {
        return 'QQBot ' . $this->getAccessToken();
    }

    /**
     * Send a GET request and return decoded response.
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
            ->setHeader('Authorization', $this->authHeader())
            ->setHeader('X-Union-Appid', $this->appId);

        return JSONHandler::decode($cURL->execute(), true);
    }

    /**
     * Send a POST request with JSON body and return decoded response.
     */
    private function postRequest(string $path, array $fields): ArrayObject
    {
        $url = self::API_BASE . $path;

        $cURL = new ClientURL($url);
        $cURL->option
            ->setURL($url)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setReturnTransfer()
            ->setPostMethod()
            ->setHeader('Authorization', $this->authHeader())
            ->setHeader('X-Union-Appid', $this->appId)
            ->setHeader('Content-Type', 'application/json')
            ->setPostFields($fields);

        return JSONHandler::decode($cURL->execute(), true);
    }

    /**
     * Send a PATCH request with JSON body and return decoded response.
     */
    private function patchRequest(string $path, array $fields): ArrayObject
    {
        $url = self::API_BASE . $path;

        $cURL = new ClientURL($url);
        $cURL->option
            ->setURL($url)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setReturnTransfer()
            ->setCustomRequest('PATCH')
            ->setHeader('Authorization', $this->authHeader())
            ->setHeader('X-Union-Appid', $this->appId)
            ->setHeader('Content-Type', 'application/json')
            ->setPostFields($fields);

        return JSONHandler::decode($cURL->execute(), true);
    }

    /**
     * Send a PUT request with optional JSON body and return decoded response.
     */
    private function putRequest(string $path, array $fields = []): ArrayObject
    {
        $url = self::API_BASE . $path;

        $cURL = new ClientURL($url);
        $cURL->option
            ->setURL($url)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setReturnTransfer()
            ->setCustomRequest('PUT')
            ->setHeader('Authorization', $this->authHeader())
            ->setHeader('X-Union-Appid', $this->appId)
            ->setHeader('Content-Type', 'application/json')
            ->setPostFields($fields);

        return JSONHandler::decode($cURL->execute(), true);
    }

    /**
     * Send a DELETE request with optional JSON body and return decoded response.
     */
    private function deleteRequest(string $path, array $fields = []): ArrayObject
    {
        $url = self::API_BASE . $path;

        $cURL = new ClientURL($url);
        $cURL->option
            ->setURL($url)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setReturnTransfer()
            ->setCustomRequest('DELETE')
            ->setHeader('Authorization', $this->authHeader())
            ->setHeader('X-Union-Appid', $this->appId)
            ->setHeader('Content-Type', 'application/json')
            ->setPostFields($fields);

        return JSONHandler::decode($cURL->execute(), true);
    }
}
