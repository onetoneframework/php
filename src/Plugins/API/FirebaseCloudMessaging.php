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
use Clover\Classes\Data\JSONHandler;
use function is_array;
use function array_key_exists;
use function is_object;
use function sprintf;

class FirebaseCloudMessaging
{
	private $serverApiKey;
	private $badgeCount = 0;
	private $identify = 0;
	private $registrationIds = [];
	private $resultData = [];
	private $requestUrl = "";
	private $clickAction = "";
	private $sound = "default";
	private $dataContent = [];
	private $notificationContent = [];
	private $topic = '';

	public function __construct()
	{
		$this->requestUrl = "https://fcm.googleapis.com/fcm/send";
	}

	public function setDataContent($dataContent)
	{
		if (is_array($dataContent)) {
			$this->dataContent = $dataContent;
		}
	}

	public function setNotificationContent($notificationContent)
	{
		if (is_array($notificationContent)) {
			$this->notificationContent = $notificationContent;
		}
	}

	public function setTopic($topic)
	{
		$this->topic = $topic;
	}

	public function setServerApiKey($key)
	{
		$this->serverApiKey = $key;
	}

	public function addRegistrationId($identifier)
	{
		$this->registrationIds[] = $identifier;
	}

	public function getMulticastId()
	{
		return array_key_exists("multicast_id", $this->resultData) ? $this->resultData['multicast_id'] : -1;
	}

	public function isSuccess()
	{
		return array_key_exists("success", $this->resultData) ? $this->resultData['success'] === 1 : 0;
	}

	public function setClickAction($clickAction)
	{
		$this->clickAction = $clickAction;
	}

	public function getResults()
	{
		return is_object($this->resultData) ? $this->resultData->results : $this->resultData;
	}

	public function setBadgeCount($count)
	{
		$this->badgeCount = $count;
	}

	public function send($title, $body, $message)
	{
		$headers = [
			sprintf("Authorization: key=%s", $this->serverApiKey),
			'Content-Type: application/json'
		];

		$notificationContent = [
			"title"					=> $title,
			"body" 					=> $body,
			"sound"					=> $this->sound,
			'message'				=> $message,
			'id'					=> $this->identify,
			'badge' 				=> $this->badgeCount,
		];

		$notificationContent = array_merge($notificationContent, $this->notificationContent);

		$dataContent = [
			"title"					=> $title,
			"body" 					=> $body,
			'click_action'			=> $this->clickAction,
			"sound"					=> $this->sound,
			'mode'					=> "",
			'message'				=> $message,
			'id'					=> $this->identify,
			'badge' 				=> $this->badgeCount,
		];

		$dataContent = array_merge($dataContent, $this->dataContent);

		$postData = [
			'registration_ids'		=> $this->registrationIds,
			//'to'					=> $this->Topic,
			'notification'			 => $notificationContent,
			'data'					=> $dataContent,
			"priority"				=> "high",
			'content_available' 	=> true,
			'apns' => [
				'payload' => [
					'aps' => [
						'content-available' => 1
					],
				],
			]
		];

		$cURL = new ClientURL();
		$cURL->option->setURL($this->requestUrl)
			->setPostMethod(true)
			->setHeaders($headers)
			->setReturnTransfer(true)
			->setPostField(json_encode($postData))
			->setAutoReferer(true)
			->setReturnHeader(false)
			->setDisableCache(true);

		$result = $cURL->execute();

		$isJson = JSONHandler::isJson($result);

		if ($isJson) {
			$this->resultData = json_decode($result);
		} else {
			$this->resultData = $result;
		}
	}
}
