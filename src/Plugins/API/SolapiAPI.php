<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Plugin;

use Exception;
use DateTime;
use Nurigo\Solapi\Exceptions\MessageNotReceivedException;
use Nurigo\Solapi\Models\Message;
use Nurigo\Solapi\Services\SolapiMessageService;
use Nurigo\Solapi\Models\Request\GetMessagesRequest;
use Nurigo\Solapi\Models\Request\GetStatisticsRequest;
use Nurigo\Solapi\Models\Kakao\KakaoOption;
use Nurigo\Solapi\Models\Voice\VoiceOption;
use Nurigo\Solapi\Models\Voice\VoiceType;
use Nurigo\Solapi\Models\Kakao\KakaoBms;
use Nurigo\Solapi\Models\Kakao\KakaoBmsTargetingType;
use Nurigo\Solapi\Models\Kakao\Bms\BmsChatBubbleType;

/**
 * Solapi class handles SMS/LMS messaging using the Solapi service.
 * This class provides methods for sending single or multiple messages,
 * and handles automatic conversion to LMS when text length exceeds 90 bytes.
 */
class SolapiAPI
{

    /**
     * API key for authenticating with Solapi service.
     * @var string
     */
    private string $API_KEY;

    /**
     * API Secret for authenticating with Solapi service.
     * @var string
     */
    private string $API_SECRET;

    /**
     * Constructor for Solapi class.
     * Initializes the credentials with the provided API Key and API Secret.
     *
     * @param string $apiKey The API key for Solapi authentication.
     * @param string $apiSecret The API secret for Solapi authentication.
     */
    public function __construct(string $apiKey, string $apiSecret)
    {
        $this->API_KEY = $apiKey;
        $this->API_SECRET = $apiSecret;
    }

    /**
     * Initializes and returns the SolapiMessageService.
     * 
     * @return SolapiMessageService
     */
    private function getMessageService(): SolapiMessageService
    {
        return new SolapiMessageService($this->API_KEY, $this->API_SECRET);
    }

    /**
     * Sends a single text message (SMS/LMS/MMS).
     *
     * @param string $to Destination phone number (must exclude '-', '*' etc.).
     * @param string $from Source phone number registered in the account (must exclude '-', '*' etc.).
     * @param string $text The message text content to send.
     * @param DateTime|null $scheduledDate Optional scheduled time for sending.
     * @param string|null $subject Optional title for LMS/MMS messages.
     * @param string|null $imageFilePath Optional local absolute file path for MMS image upload.
     * 
     * @throws MessageNotReceivedException
     * @throws Exception
     * @return mixed
     */
    public function sendMessage(string $to, string $from, string $text, ?DateTime $scheduledDate = null, ?string $subject = null, ?string $imageFilePath = null): mixed
    {
        $messageService = $this->getMessageService();

        $message = new Message();
        $message->setTo($to)
            ->setFrom($from)
            ->setText($text);

        if ($subject !== null) {
            $message->setSubject($subject);
        }

        if ($imageFilePath !== null) {
            $imageId = $messageService->uploadFile($imageFilePath);
            $message->setImageId($imageId);
        }

        if ($scheduledDate !== null) {
            return $messageService->send($message, $scheduledDate);
        }

        return $messageService->send($message);
    }

    /**
     * Sends multiple text messages at once in a batch.
     *
     * @param array $messagesData Array of messages. Each item should be an array containing 'to', 'from', 'text', and optionally 'subject', 'imageFilePath'.
     * @param DateTime|null $scheduledDate Optional scheduled time for sending.
     * 
     * @throws MessageNotReceivedException
     * @throws Exception
     * @return mixed
     */
    public function sendMessages(array $messagesData, ?DateTime $scheduledDate = null): mixed
    {
        $messageService = $this->getMessageService();

        $messages = [];
        foreach ($messagesData as $data) {
            $tempMessage = new Message();
            $tempMessage->setTo($data['to'] ?? '')
                ->setFrom($data['from'] ?? '')
                ->setText($data['text'] ?? '');

            if (isset($data['subject'])) {
                $tempMessage->setSubject($data['subject']);
            }
            if (isset($data['imageFilePath'])) {
                $imageId = $messageService->uploadFile($data['imageFilePath']);
                $tempMessage->setImageId($imageId);
            }

            $messages[] = $tempMessage;
        }

        if ($scheduledDate !== null) {
            return $messageService->send($messages, $scheduledDate);
        }

        return $messageService->send($messages);
    }

    /**
     * Retrieves the current balance and points.
     * 
     * @return mixed
     */
    public function getBalance(): mixed
    {
        return $this->getMessageService()->getBalance();
    }

    /**
     * Retrieves statistics, optionally filtered by date.
     * 
     * @param DateTime|null $startDate
     * @param DateTime|null $endDate
     * @return mixed
     */
    public function getStatistics(?DateTime $startDate = null, ?DateTime $endDate = null): mixed
    {
        $parameter = new GetStatisticsRequest();

        if ($startDate !== null && $endDate !== null) {
            $parameter->setStartDate($startDate->format("c"));
            $parameter->setEndDate($endDate->format("c"));
        }

        return $this->getMessageService()->getStatistics($parameter);
    }

    /**
     * Retrieves a list of messages.
     * 
     * @param array $params Optional search parameters. 
     *  Can include keys: 'from', 'to', 'limit', 'startKey', 'groupId', 'messageId', 'messageIds', 'type', 'statusCode', 'startDate', 'endDate'
     * @return mixed
     */
    public function getMessages(array $params = []): mixed
    {
        $parameter = new GetMessagesRequest();

        if (isset($params['from'])) {
            $parameter->setFrom($params['from']);
        }

        if (isset($params['to'])) {
            $parameter->setTo($params['to']);
        }

        if (isset($params['limit'])) {
            $parameter->setLimit($params['limit']);
        }

        if (isset($params['startKey'])) {
            $parameter->setStartKey($params['startKey']);
        }

        if (isset($params['groupId'])) {
            $parameter->setGroupId($params['groupId']);
        }

        if (isset($params['messageId'])) {
            $parameter->setMessageId($params['messageId']);
        }

        if (isset($params['messageIds'])) {
            $parameter->setMessageIds($params['messageIds']);
        }

        if (isset($params['type'])) {
            $parameter->setType($params['type']);
        }

        if (isset($params['statusCode'])) {
            $parameter->setStatusCode($params['statusCode']);
        }

        if (isset($params['startDate']) && isset($params['endDate']) && $params['startDate'] instanceof DateTime && $params['endDate'] instanceof DateTime) {
            $parameter->setStartDate($params['startDate']->format("c"));
            $parameter->setEndDate($params['endDate']->format("c"));
        }

        return $this->getMessageService()->getMessages($parameter);
    }

    /**
     * Sends a Kakao Alimtalk message.
     * 
     * @param string $to Destination phone number (must exclude '-', '*' etc.).
     * @param string $pfId ID of the linked business channel.
     * @param string $templateId ID of the registered Alimtalk template.
     * @param array $variables Variables for template replacement (e.g. ['#{name}' => 'John']).
     * @param string|null $from Source phone number (if alternative text sending fails over).
     * @param DateTime|null $scheduledDate Optional scheduled time for sending.
     * 
     * @throws MessageNotReceivedException
     * @throws Exception
     * @return mixed
     */
    public function sendAlimtalk(string $to, string $pfId, string $templateId, array $variables = [], ?string $from = null, ?DateTime $scheduledDate = null): mixed
    {
        $messageService = $this->getMessageService();

        $kakaoOption = new KakaoOption();
        $kakaoOption->setPfId($pfId)
            ->setTemplateId($templateId);

        if (!empty($variables)) {
            $kakaoOption->setVariables($variables);
        }

        $message = new Message();
        $message->setTo($to)->setKakaoOptions($kakaoOption);

        if ($from !== null) {
            $message->setFrom($from);
        }

        if ($scheduledDate !== null) {
            return $messageService->send($message, $scheduledDate);
        }

        return $messageService->send($message);
    }

    /**
     * Sends a voice message.
     * 
     * @param string $to Destination phone number.
     * @param string $from Source phone number.
     * @param string $text The message text to convert to voice.
     * @param string $voiceType Voice type (e.g. VoiceType::FEMALE or VoiceType::MALE).
     * @param string|null $headerMessage Header message indicating start of voice message.
     * @param string|null $tailMessage Tail message indicating end of voice message.
     * @param DateTime|null $scheduledDate Optional scheduled time for sending.
     * 
     * @return mixed
     */
    public function sendVoiceMessage(string $to, string $from, string $text, string $voiceType = VoiceType::FEMALE, ?string $headerMessage = null, ?string $tailMessage = null, ?DateTime $scheduledDate = null): mixed
    {
        $messageService = $this->getMessageService();

        $voiceOption = new VoiceOption();
        $voiceOption->setVoiceType($voiceType);

        if ($headerMessage !== null) {
            $voiceOption->setHeaderMessage($headerMessage);
        }
        if ($tailMessage !== null) {
            $voiceOption->setTailMessage($tailMessage);
        }

        $message = new Message();
        $message->setTo($to)
            ->setFrom($from)
            ->setText($text)
            ->setVoiceOptions($voiceOption);

        if ($scheduledDate !== null) {
            return $messageService->send($message, $scheduledDate);
        }

        return $messageService->send($message);
    }

    /**
     * Sends a Kakao Brand Message (Template).
     * 
     * @param string $to Destination phone number.
     * @param string $pfId ID of the linked business channel.
     * @param string $templateId ID of the registered Alimtalk template.
     * @param array $variables Variables for template replacement (e.g. ['#{name}' => 'John']).
     * @param string $targeting Targeting type (e.g. KakaoBmsTargetingType::I).
     * @param string|null $from Source phone number (if alternative text sending fails over).
     * @param DateTime|null $scheduledDate Optional scheduled time for sending.
     * 
     * @return mixed
     */
    public function sendBrandMessage(string $to, string $pfId, string $templateId, array $variables = [], string $targeting = KakaoBmsTargetingType::I, ?string $from = null, ?DateTime $scheduledDate = null): mixed
    {
        $messageService = $this->getMessageService();

        $kakaoBms = new KakaoBms();
        $kakaoBms->setTargeting($targeting);

        $kakaoOption = new KakaoOption();
        $kakaoOption->setPfId($pfId)
            ->setTemplateId($templateId)
            ->setBms($kakaoBms);

        if (!empty($variables)) {
            $kakaoOption->setVariables($variables);
        }

        $message = new Message();
        $message->setTo($to)->setKakaoOptions($kakaoOption);

        if ($from !== null) {
            $message->setFrom($from);
        }

        if ($scheduledDate !== null) {
            return $messageService->send($message, $scheduledDate);
        }

        return $messageService->send($message);
    }

    /**
     * Sends a Kakao Brand Message (BMS Free Text).
     * 
     * @param string $to Destination phone number.
     * @param string $from Source phone number.
     * @param string $text The message text content to send.
     * @param string $pfId ID of the linked business channel.
     * @param string $targeting Targeting type (e.g. 'I').
     * @param DateTime|null $scheduledDate Optional scheduled time for sending.
     * 
     * @return mixed
     */
    public function sendBmsFreeText(string $to, string $from, string $text, string $pfId, string $targeting = 'I', ?DateTime $scheduledDate = null): mixed
    {
        $messageService = $this->getMessageService();

        $bms = new KakaoBms();
        $bms->setTargeting($targeting)
            ->setChatBubbleType(BmsChatBubbleType::TEXT);

        $kakaoOption = new KakaoOption();
        $kakaoOption->setPfId($pfId)
            ->setBms($bms);

        $message = new Message();
        $message->setTo($to)
            ->setFrom($from)
            ->setText($text)
            ->setType('BMS_FREE')
            ->setKakaoOptions($kakaoOption);

        if ($scheduledDate !== null) {
            return $messageService->send($message, $scheduledDate);
        }

        return $messageService->send($message);
    }
}
