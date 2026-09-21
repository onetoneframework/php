<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Plugin;

use Clover\Classes\Data\ArrayObject;
use Clover\Classes\Data\JSONHandler;
use Clover\Classes\ClientURL;
use Clover\Classes\Data\StringObject;
use Clover\Classes\Date\Date;
use Clover\Classes\File\Handler as FileHandler;
use DateTime;

class DMM
{

    private $dmmId = "20000000";

    public function getHoukagorScenario(string $loginToken): mixed
    {
        Date::setDefaultTimezone('Asia/Tokyo');

        $now = new DateTime();
        $millisecond = (int)($now->format('Uu'));
        $timestamp = (int)($millisecond / 1000);

        $requestURL = "https://asw-r.bokusen.net/character/list?dmm_id={$this->dmmId}&pc=1&param={%22transition%22:3}&t=".$timestamp;

        $cURL = new ClientURL($requestURL);
        $cURL->option->setURL($requestURL)
            ->setPostMethod(true)
            ->setPostField('{}')
            ->setHeader('Host', 'asw-r.bokusen.net')
            ->setHeader('Origin', 'https://asw-r.bokusen.net')
            ->setHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.0.2526.111 Safari/537.36 OPR/34.0.2036.50')
            ->setHeader('Referer', "https://asw-r.bokusen.net/asw.php?s=tutorial/mypage&spb=0")
            ->setHeader('Accept', '*')
            ->setHeader('Accept-Encoding', 'gzip, deflate, br, zstd')
            ->setHeader('Accept-Language', 'ko,zh;q=0.9,en-US;q=0.8,en;q=0.7')
            ->setContentTypeFormUrlEncoded()
            ->setHeader('X-Asw-Login-Token', $loginToken)
            ->setHeader('X-Asw-resource-commercial-version', '1')
            ->setHeader('X-Asw-resource-game-version:', '1')
            ->setReturnTransfer(true)
            ->setAutoReferer(true)
            ->setReturnHeader(false);

        $response = $cURL->execute();
        $cURL->close();

        return $response;
    }

    public function saveJyogakuenFile(string $requestURL, string $path): void
    {
        $cURL = new ClientURL($requestURL);
        $cURL->option->setURL($requestURL)
            ->setGetMethod(true)
            ->setHeader('Host', 'koi-server-master.tencross.site')
            ->setHeader('Origin', 'http://koi-server-master.tencross.site')
            ->setHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.0.2526.111 Safari/537.36 OPR/34.0.2036.50')
            ->setHeader('Referer', "https://koi-server-master.tencross.site/master/gadget_hh_html5_ssl.html")
            ->setHeader('Accept', '*')
            ->setHeader('Accept-Encoding', 'gzip, deflate, br, zstd')
            ->setHeader('Accept-Language', 'ko,zh;q=0.9,en-US;q=0.8,en;q=0.7')
            ->setXHttpRequest()
            ->setReturnTransfer(true)
            ->setAutoReferer(true)
            ->setReturnHeader(false);

        $response = $cURL->execute();
        $cURL->close();

        FileHandler::write($path, $response);
    }

    /**
     * Summary of saveJyogakuenThumbnail
     * 
     * @param mixed $pageCount
     * @param mixed $user_id
     * @param mixed $login_token
     * @param mixed $selector = [event, stage, card_evolution]
     * 
     * @return void
     */
    public function saveJyogakuenThumbnail($pageCount, $user_id, $login_token, $selector = 'stage'): void
    {
        for ($i = 1; $i <= $pageCount; $i++) {
            $response = $this->getJyogakuenAlbum($user_id, $login_token, $i, $selector);

            foreach ($response->data->lewd_data as $data) {
                $path = parse_url($data->lewd_image, PHP_URL_PATH);
                $path = basename($path);

                $this->saveJyogakuenFile($data->lewd_image, BASE_PATH."/".$path);
            }
        }
    }

    public static function requestJewepriAPI($payload): StringObject
    {
        $fields = [
            'p' => $payload
        ];

        $cURL = new ClientURL();
        $cURL->option->setURL("https://api.app.jewepri-re.com/i.php")
            ->setPostMethod(true)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setHeader('Host', 'api.app.jewepri-re.com')
            ->setHeader('Origin', 'https://api.app.jewepri-re.com')
            ->setHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36')
            ->setHeader('Accept', '*/*')
            ->setHeader('Accept-Encoding', 'gzip, deflate, br, zstd')
            ->setHeader('Accept-Language', 'ko,zh;q=0.9,en-US;q=0.8,en;q=0.7,zh-CN;q=0.6')
            ->setHeader('accept-viewtype', '7')
            ->setContentTypeFormUrlEncoded()
            ->setHeader('referer', 'https://api.app.jewepri-re.com/login.php')
            ->setReturnTransfer(true)
            ->setPostField($fields)
            ->setAutoReferer(true)
            ->setReturnHeader(false);

        $result = $cURL->execute();

        return $result;
    }

    public static function requestSiprjAPI(string $payload): StringObject
    {
        $fields = [
            'p' => $payload
        ];

        $cURL = new ClientURL();
        $cURL->option->setURL("https://prod.app.siprj.com/i.php")
            ->setPostMethod(true)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setHeader('Origin', 'https://prod.app.siprj.com')
            ->setHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36')
            ->setHeader('Accept', '*/*')
            ->setHeader('Accept-Encoding', 'gzip, deflate, br, zstd')
            ->setHeader('Accept-Language', 'ko,zh;q=0.9,en-US;q=0.8,en;q=0.7,zh-CN;q=0.6')
            ->setHeader('accept-viewtype', '7')
            ->setContentTypeFormUrlEncoded()
            ->setHeader('referer', 'https://prod.app.siprj.com/login.php')
            ->setReturnTransfer(true)
            ->setPostField(($fields))
            ->setAutoReferer(true)
            ->setReturnHeader(false);

        $result = $cURL->execute();

        return $result;
    }

    /**
     * Summary of getJyogakuenAlbum
     * 
     * @param mixed $user_id
     * @param mixed $login_token
     * @param mixed $page
     * @param mixed $selector = [event, stage, card_evolution]
     * 
     * @return ArrayObject
     */
    public function getJyogakuenAlbum($user_id, $login_token, $page = 1, $selector = 'stage'): ArrayObject
    {
        $parameter = [
            'user_id' => $user_id,
            'login_token' => $login_token,
            'page' => $page,
            'selector' => $selector,
        ];
        $requestURL = "https://koi-server-master.tencross.site/master/api/album/lewd/1109576/";
        $cURL = new ClientURL($requestURL);
        $cURL->option->setURL($requestURL)
            ->setGetMethod(true)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setHeader('Host', 'koi-server-master.tencross.site')
            ->setHeader('Origin', 'http://koi-server-master.tencross.site')
            ->setHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.0.2526.111 Safari/537.36 OPR/34.0.2036.50')
            ->setHeader('Referer', "https://koi-server-master.tencross.site/master/gadget_hh_html5_ssl.html")
            ->setHeader('Accept', '*')
            ->setHeader('Accept-Encoding', 'gzip, deflate, br, zstd')
            ->setHeader('Accept-Language', 'ko,zh;q=0.9,en-US;q=0.8,en;q=0.7')
            ->setPostFields($parameter)
            ->setXHttpRequest()
            ->setReturnTransfer(true)
            ->setAutoReferer(true)
            ->setReturnHeader(false);

        $response = $cURL->execute();
        $cURL->close();

        return JSONHandler::decode($response);
    }
}
