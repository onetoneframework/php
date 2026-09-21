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
use Clover\Classes\Data\URLObject;
use Clover\Classes\OperationSystem;
use function sprintf;

class YoutubeAPI
{
	private $API_KEY;

	/**
	 * Constructor for YoutubeAPI.
	 *
	 * @param string $apiKey The YouTube Data API key.
	 */
	public function __construct(string $apiKey)
	{
		$this->API_KEY = $apiKey;
	}

	/**
	 * Sets the YouTube Data API key.
	 *
	 * @param string $APIKey The API key to set.
	 * @return void
	 */
	public function setAPIKey(string $APIKey): void
	{
		$this->API_KEY = $APIKey;
	}

	/**
	 * Retrieves the subscriber count for a YouTube channel.
	 *
	 * @param string $channelId The YouTube channel ID.
	 * @return mixed The subscriber count or false on failure.
	 */
	public function getChannelSubscriberCount(string $channelId): mixed
	{
		$url = sprintf('https://www.googleapis.com/youtube/v3/channels?part=statistics&id=%s&fields=items/statistics/subscriberCount&key=%s', $channelId, $this->API_KEY);
		$response = $this->getJSON($url);

		if ($response) {
			return $response['items'][0]['statistics']['subscriberCount'];
		}

		return false;
	}

	/**
	 * Retrieves a list of videos from a YouTube channel.
	 *
	 * @param string $channelID The YouTube channel ID.
	 * @param int $maxResults The maximum number of results (max 50).
	 * @return mixed The list of videos or false on failure.
	 */
	public function getChannelVideos(string $channelID, int $maxResults): mixed
	{
		if ($maxResults > 50) {
			$maxResults = 50;
		}

		$queries = [
			'order' => 'date',
			'part' => 'snippet',
			'channelID' => $channelID,
			'maxResults' => $maxResults,
			'key' => $this->API_KEY,
		];

		$url = new URLObject("https://www.googleapis.com/youtube/v3/search");
		$url->setQueryString($queries);

		$response = $this->getJSON($url);

		return $response['items'];
	}

	/**
	 * Fetches JSON data from a given URL.
	 *
	 * @param string $url The URL to fetch data from.
	 * @return mixed The decoded JSON response.
	 */
	public function getJSON(string $url): mixed
	{
		$cURL = new ClientURL();
		$cURL->option->setURL($url)
			->setGetMethod(true)
			->setSSLVerifyPeer(false)
			->setContentTypeJson()
			->setReturnTransfer(true)
			->setAutoReferer(true)
			->setReturnHeader(false);

		$response = $cURL->executeWithDecode();

		return $response;
	}

	/**
	 * Retrieves basic information about a YouTube video using oEmbed.
	 *
	 * @param string $videoUrl The YouTube video URL.
	 * @return mixed The video information or null on failure.
	 */
	public function getVideoInfo(string $videoUrl): mixed
	{
		$url = sprintf("http://www.youtube.com/oembed?url=%s&format=json", $videoUrl);
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$curlData = curl_exec($curl);
		if (OperationSystem::comparePHPVersion('8.5.0', '<')) {
			// @phpstan-ignore-next-line
			curl_close($curl);
		}
		$data = json_decode($curlData, true);

		return $data;
	}

	/**
	 * Retrieves statistics for a YouTube channel.
	 *
	 * @param string $channelId The YouTube channel ID.
	 * @return mixed The channel statistics or false on failure.
	 */
	public function getChannelStatics(string $channelId): mixed
	{
		$url = "https://www.googleapis.com/youtube/v3/channels?part=statistics&id=" . $channelId . "&key=" . $this->API_KEY;
		$response = $this->getJSON($url);
		$data = $response->items;

		if ($data) {
			return $data[0]->statistics;
		}

		return false;
	}

	/**
	 * Retrieves the video count for a YouTube channel.
	 *
	 * @param string $channelId The YouTube channel ID.
	 * @return int The number of videos in the channel.
	 */
	public function getChannelVideoCounts(string $channelId): int
	{
		$data = $this->getChannelStatics($channelId);

		return isset($data->videoCount) ? $data->videoCount : 0;
	}

	/**
	 * Retrieves the subscriber count for a YouTube channel.
	 *
	 * @param string $channelId The YouTube channel ID.
	 * @return int The number of subscribers.
	 */
	public function getChannelSubscriberCounts(string $channelId): int
	{
		$data = $this->getChannelStatics($channelId);

		return isset($data->subscriberCount) ? $data->subscriberCount : 0;
	}

	/**
	 * Retrieves the total view count for a YouTube channel.
	 *
	 * @param string $channelId The YouTube channel ID.
	 * @return int The total number of views.
	 */
	public function getChannelViewCounts(string $channelId): int
	{
		$data = $this->getChannelStatics($channelId);

		return isset($data->viewCount) ? $data->viewCount : 0;
	}

	/**
	 * Retrieves the like count for a YouTube channel.
	 *
	 * @param string $channelId The YouTube channel ID.
	 * @return bool|int The number of likes or false if not available.
	 */
	public function getChannelLikeCounts(string $channelId): bool
	{
		$data = $this->getChannelStatics($channelId);

		if (!isset($data)) {
			return false;
		}

		return isset($data->likeCount) ? $data->likeCount : 0;
	}

	/**
	 * Retrieves the dislike count for a YouTube channel.
	 *
	 * @param string $channelId The YouTube channel ID.
	 * @return int The number of dislikes.
	 */
	public function getChannelDislikeCounts(string $channelId): int
	{
		$data = $this->getChannelStatics($channelId);

		return isset($data->dislikeCount) ? $data->dislikeCount : 0;
	}

	/**
	 * Retrieves the favorite count for a YouTube channel.
	 *
	 * @param string $channelId The YouTube channel ID.
	 * @return int The number of favorites.
	 */
	public function getChannelFavoriteCounts(string $channelId): int
	{
		$data = $this->getChannelStatics($channelId);

		return isset($data->favoriteCount) ? $data->favoriteCount : 0;
	}

	/**
	 * Retrieves the comment count for a YouTube channel.
	 *
	 * @param string $channelId The YouTube channel ID.
	 * @return int The number of comments.
	 */
	public function getChannelCommentCounts(string $channelId): int
	{
		$data = $this->getChannelStatics($channelId);

		return isset($data->commentCount) ? $data->commentCount : 0;
	}

	/**
	 * Retrieves statistics for a YouTube video.
	 *
	 * @param string $channelId The YouTube video ID.
	 * @return mixed The video statistics or false on failure.
	 */
	public function getVideoStatics(string $channelId): mixed
	{
		$url = "https://www.googleapis.com/youtube/v3/videos?part=statistics&id=" . $channelId . "&key=" . $this->API_KEY;
		$response = $this->getJSON($url);
		$data = $response->items;
		$data = array_shift($data);

		if ($data) {
			return $data->statistics;
		}

		return false;
	}

	/**
	 * Retrieves the view count for a YouTube video.
	 *
	 * @param string $videoID The YouTube video ID.
	 * @return int The number of views.
	 */
	public function getVideoViewCounts(string $videoID): int
	{
		$data = $this->getVideoStatics($videoID);

		return isset($data['viewCount']) ? $data['viewCount'] : 0;
	}

	/**
	 * Retrieves the like count for a YouTube video.
	 *
	 * @param string $videoID The YouTube video ID.
	 * @return int The number of likes.
	 */
	public function getVideoLikeCounts(string $videoID): int
	{
		$data = $this->getVideoStatics($videoID);

		return isset($data['likeCount']) ? $data['likeCount'] : 0;
	}

	/**
	 * Retrieves the dislike count for a YouTube video.
	 *
	 * @param string $videoID The YouTube video ID.
	 * @return int The number of dislikes.
	 */
	public function getVideoDislikeCounts(string $videoID): int
	{
		$data = $this->getVideoStatics($videoID);

		return isset($data['dislikeCount']) ? $data['dislikeCount'] : 0;
	}

	/**
	 * Retrieves the favorite count for a YouTube video.
	 *
	 * @param string $videoID The YouTube video ID.
	 * @return int The number of favorites.
	 */
	public function getVideoFavoriteCounts(string $videoID): int
	{
		$data = $this->getVideoStatics($videoID);

		return isset($data['favoriteCount']) ? $data['favoriteCount'] : 0;
	}

	/**
	 * Retrieves the comment count for a YouTube video.
	 *
	 * @param string $videoID The YouTube video ID.
	 * @return int The number of comments.
	 */
	public function getVideoCommentCounts(string $videoID): int
	{
		$data = $this->getVideoStatics($videoID);

		return isset($data['commentCount']) ? $data['commentCount'] : 0;
	}

}