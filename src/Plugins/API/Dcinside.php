<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Plugin;

use Clover\Classes\File\Functions as FileFunctions;
use Clover\Classes\ClientURL;
use Clover\Classes\Data\StringObject;
use Clover\Classes\Data\ArrayObject;
use Clover\Classes\XML\DOM;
use function sprintf;
use Exception;
use closure;

/**
 * Dcinside class handles interactions with the DC Inside Korean community website.
 * This class provides methods for login, document management, user blocking,
 * and other operations on DC Inside galleries and boards.
 */
class Dcinside
{
    /**
     * Path to the cookie file for session management.
     * @var string
     */
    private string $cookieFile;

    /**
     * CI token for authentication.
     * @var mixed
     */
    private string $ciT;

    /**
     * Path to the PEM certificate file.
     * @var string|bool
     */
    private string|bool $pemFile;

    /**
     * User agent string for HTTP requests.
     * @var string
     */
    private string $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36';

    /**
     * Constructor for Dcinside class.
     * Initializes the client with necessary certificates and cookie management.
     */
    public function __construct()
    {
        $this->setPemFile();

        $this->cookieFile = FileFunctions::createUniqueTemporaryOnDefaultPath('cookie_');
    }

    /**
     * Fetches the main page of DC Inside to initialize session.
     * This method retrieves the homepage and sets up cookies for authentication.
     *
     * @return mixed The response from the main page request.
     */
    private function getMainPage(): mixed
    {
        $requestURL = "https://www.dcinside.com";

        $cURL = new ClientURL($requestURL);
        $cURL->option->setURL($requestURL)
            ->setGetMethod(true)
            ->setCAInformation($this->pemFile)
            ->setSSLVerifyPeer(true)
            ->setSSLVerifyHost(true)
            ->setAcceptEncoding('')
            ->setHeader('User-Agent', $this->userAgent)
            ->setHeader('Connection', 'keep-alive')
            ->setHeader('Accept', 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7')
            ->setHeader('Accept-Encoding', 'gzip, deflate, br, zstd')
            ->setHeader('Accept-Language', 'ko,zh;q=0.9,en-US;q=0.8,en;q=0.7')
            ->setCookieJar($this->cookieFile)
            ->setCookieFile($this->cookieFile)
            ->setReturnTransfer(true)
            ->setAutoReferer(true)
            ->setReturnHeader(true);

        $response = $cURL->execute();
        $cURL->close();

        return $response;
    }

    /**
     * Sets up the PEM certificate file for SSL verification.
     * Converts DER certificate to PEM format and creates a temporary file.
     * 
     * @throws Exception
     */
    private function setPemFile(): void
    {
        $derFile = BASE_PATH . '/cacert.der';
        $derData = file_get_contents($derFile);
        if ($derData === false) {
            throw new Exception("Unable to read cacert.der file.");
        }

        $pem = "-----BEGIN CERTIFICATE-----\n";
        $pem .= chunk_split(base64_encode($derData), 64, "\n");
        $pem .= "-----END CERTIFICATE-----\n";

        $this->pemFile = tempnam(sys_get_temp_dir(), 'cacert_') . '.pem';
        if (file_put_contents($this->pemFile, $pem) === false) {
            throw new Exception("Unable to create PEM file.");
        }
    }

    /**
     * Logs into DC Inside with provided credentials.
     * Performs authentication and verifies login success.
     *
     * @param string $id The user ID for login.
     * @param string $password The password for login.
     * @return bool True if login successful, false otherwise.
     */
    public function login(string $id, string $password): bool
    {
        $main = $this->getMainPage();
        $form = (new DOM())->getSerializeForm($main, 'login_process');
        $form['user_id'] = $id;
        $form['pw'] = $password;

        $this->memberCheck($form);
        $main = $this->getMainPage();

        return (str_contains($main, 'btn_inout logout'));
    }

    /**
     * Performs member check for login validation.
     * Sends login form data to verify credentials.
     *
     * @param array{user_id: string, pw: string} $form The login form data.
     * @return mixed The response from the member check request.
     */
    private function memberCheck(array $form): mixed
    {
        $requestURL = "https://sign.dcinside.com/login/member_check";

        $cURL = new ClientURL($requestURL);
        $cURL->option->setURL($requestURL)
            ->setPostMethod(true)
            ->setReturnTransfer(true)
            ->setReturnHeader(true)
            ->setAutoReferer(true)
            ->setHeaderOut(true)
            ->setHTTPVersion_1_1()
            ->setVerbose(true)
            ->setCAInformation($this->pemFile)
            ->setSSLVerifyPeer(true)
            ->setSSLVerifyHost(true)
            ->setAcceptEncoding('')
            ->setHeader('User-Agent', $this->userAgent)
            ->setHeader("X-Requested-With", "XMLHttpRequest")
            ->setHeader('Referer', 'https://www.dcinside.com/')
            ->setHeader('Cookie', 'ssl=Y')
            ->setHeader('Accept-Encoding', 'gzip, deflate, br')
            ->setHeader('Accept-Language', 'ko,zh;q=0.9,en-US;q=0.8,en;q=0.7')
            ->setHeader('Connection', 'keep-alive')
            ->setFollowRedirects(true)
            ->setCookieJar($this->cookieFile)
            ->setCookieFile($this->cookieFile)
            ->setPostField($form);

        $response = $cURL->execute();
        $cURL->close();

        return $response;
    }

    /**
     * Retrieves quick avoid configuration for a gallery.
     * Gets settings for user blocking in the specified gallery.
     *
     * @param string $id The gallery ID.
     * @return mixed The avoid configuration response.
     */
    public function getQuickAvoidConf(string $id): mixed
    {
        $fields = [
            'ci_t' => $this->ciT,
            'gallery_id' => $id,
            '_GALLTYPE_' => 'MI'
        ];

        $requestURL = "https://gall.dcinside.com/ajax/managements_ajax/get_quick_avoid_conf";

        $cURL = new ClientURL($requestURL);
        $cURL->option->setURL($requestURL)
            ->setPostMethod(true)
            ->setCookieJar($this->cookieFile)
            ->setCookieFile($this->cookieFile)
            ->setReturnTransfer(true)
            ->setCAInformation($this->pemFile)
            ->setSSLVerifyPeer(true)
            ->setSSLVerifyHost(true)
            ->setReturnHeader(true)
            ->setAcceptEncoding('')
            ->setHeader('User-Agent', $this->userAgent)
            ->setHeader('Connection', 'keep-alive')
            ->setHeader('Host', 'gall.dcinside.com')
            ->setHeader('Origin', 'https://gall.dcinside.com')
            ->setHeader('Referer', "https://gall.dcinside.com/mini/board/lists?id={$id}")
            ->setHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8')
            ->setHeader('Accept', 'application/json, text/javascript, */*; q=0.01')
            ->setHeader('X-Requested-With', 'XMLHttpRequest')
            ->setPostField($fields);

        return $cURL->execute();
    }

    /**
     * Updates the avoid list by blocking users.
     * Adds users to the block list for the specified gallery.
     *
     * @param string $id The gallery ID.
     * @param string $nos The post numbers to block.
     * @param int $avoid_hour Hours to block (default: 1).
     * @param int $avoid_reason Reason code for blocking (default: 0).
     * @param string $avoid_reason_txt Custom reason text (default: '').
     * @param int $del_chk Delete check flag (default: 0).
     * @return mixed The response from the update request.
     */
    public function updateAvoidList(string $id, string $nos, int $avoid_hour = 1, int $avoid_reason = 0, string $avoid_reason_txt = '', int $del_chk = 0): mixed
    {
        $fields = [
            'ci_t' => $this->ciT,
            'id' => $id,
            'nos[]' => $nos,
            'parent' => '',
            'avoid_hour' => $avoid_hour,
            'avoid_reason' => $avoid_reason,
            'avoid_reason_txt' => $avoid_reason_txt,
            'del_chk' => $del_chk,
            '_GALLTYPE_' => 'MI',
            'avoid_type_chk' => '1',
        ];

        $requestURL = "https://gall.dcinside.com/ajax/mini_manager_board_ajax/update_avoid_list";

        $cURL = new ClientURL($requestURL);
        $cURL->option->setURL($requestURL)
            ->setPostMethod(true)
            ->setCookieJar($this->cookieFile)
            ->setCookieFile($this->cookieFile)
            ->setReturnTransfer(true)
            ->setCAInformation($this->pemFile)
            ->setSSLVerifyPeer(true)
            ->setSSLVerifyHost(true)
            ->setReturnHeader(true)
            ->setAcceptEncoding('')
            ->setHeader('Connection', 'keep-alive')
            ->setHeader('Host', 'gall.dcinside.com')
            ->setHeader('Origin', 'https://gall.dcinside.com')
            ->setHeader('Referer', 'https://gall.dcinside.com/mini/board/lists?id=' . $id)
            ->setHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8')
            ->setHeader('Accept', 'application/json, text/javascript, */*; q=0.01')
            ->setHeader('X-Requested-With', 'XMLHttpRequest')
            ->setPostField($fields);

        $response = $cURL->execute();
        $cURL->close();

        return $response;
    }

    /**
     * Gets the count of documents written by a user.
     * Retrieves the total number of posts from a user's gallog.
     *
     * @param string $id The user ID.
     * @return array|int|string The document count.
     */
    public function getWriteDocumentCount(string $id): array|int|string
    {
        $count = 0;

        $requestURL = sprintf("https://gallog.dcinside.com/%s", $id);

        $cURL = new ClientURL($requestURL);
        $cURL->option->setURL($requestURL)
            ->setGetMethod(true)
            ->setHTTPVersion_1_1()
            ->setCAInformation($this->pemFile)
            ->setSSLVerifyPeer(true)
            ->setSSLVerifyHost(true)
            ->setReturnTransfer(true)
            ->setAutoReferer(true)
            ->setCookieJar($this->cookieFile)
            ->setCookieFile($this->cookieFile)
            ->setAcceptEncoding('')
            ->setHeader('User-Agent', $this->userAgent)
            ->setHeader('Host', 'gallog.dcinside.com')
            ->setReturnHeader(true);

        $response = $cURL->execute();
        $cURL->close();

        if ($response instanceof StringObject) {
            $matches = [];

            $regex = '#게시글<span class=\"num\">\(([\d\,]{1,})\)#i';
            $response->match($regex, $matches);

            $count = str_replace(',', '', $matches[1]);
        }

        return $count;
    }

    /**
     * Modifies an existing document.
     * Updates the subject and content of a post.
     *
     * @param string $id The document ID.
     * @param string $subject The new subject.
     * @param string $memo The new content.
     * @return mixed The response from the modify request.
     */
    public function modifyDocument(string $id, string $subject, string $memo): mixed
    {
        $content = $this->getModifyDocumentPage($id);

        $form = (new DOM())->getSerializeForm($content, 'modify');

        $requestURL = "https://gall.dcinside.com/board/forms/modify_submit";

        $cURL = new ClientURL($requestURL);
        $cURL->option->setURL($requestURL)
            ->setPostMethod(true)
            ->setCookieJar($this->cookieFile)
            ->setCookieFile($this->cookieFile)
            ->setReturnTransfer(true)
            ->setCAInformation($this->pemFile)
            ->setSSLVerifyPeer(true)
            ->setSSLVerifyHost(true)
            ->setReturnHeader(true)
            ->setAcceptEncoding('')
            ->setHeader('Connection', 'keep-alive')
            ->setHeader('Host', 'gall.dcinside.com')
            ->setHeader('Origin', 'https://gall.dcinside.com')
            ->setHeader('Referer', 'https://gall.dcinside.com/mini/board/lists?id=' . $id)
            ->setHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8')
            ->setHeader('Accept', 'application/json, text/javascript, */*; q=0.01')
            ->setHeader('X-Requested-With', 'XMLHttpRequest');

        $form['subject'] = $subject;
        $form['memo'] = $memo;

        $cURL->option->setPostField($form);

        $response = $cURL->execute();
        $cURL->close();

        return $response;
    }

    /**
     * Fetches the modify document page.
     * Retrieves the page content for editing a document.
     *
     * @param string $id The document ID.
     * @return mixed The page content.
     */
    private function getModifyDocumentPage(string $id): mixed
    {
        $requestURL = sprintf("https://gall.dcinside.com/mini/board/modify/?id=agihype&no=%d", $id);

        $cURL = new ClientURL($requestURL);
        $cURL->option->setURL($requestURL)
            ->setGetMethod(true)
            ->setReturnTransfer(true)
            ->setAutoReferer(true)
            ->setCAInformation($this->pemFile)
            ->setSSLVerifyPeer(true)
            ->setSSLVerifyHost(true)
            ->setAcceptEncoding('')
            ->setHeader('User-Agent', $this->userAgent)
            ->setHeader('Accept', 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7')
            ->setHeader('Accept-Encoding', 'gzip, deflate, br, zstd')
            ->setHeader('Accept-Language', 'ko,zh;q=0.9,en-US;q=0.8,en;q=0.7')
            ->setHeader('connection', 'keep-alive')
            ->setHeader('Referer', 'https://sign.dcinside.com/')
            ->setCookieJar($this->cookieFile)
            ->setCookieFile($this->cookieFile)
            ->setReturnHeader(true);

        $response = $cURL->execute();
        $cURL->close();

        return $response;
    }

    /**
     * Blocks users who have written few posts.
     * Automatically blocks users based on post count threshold.
     *
     * @param int $threshold The minimum post count threshold.
     * @param string $board_id The board ID.
     * @param string $id The login ID.
     * @param string $password The login password.
     * @param string $avoidReason The reason for blocking.
     * @param int $avoidHour Hours to block.
     * @param closure|null $callback Optional callback for additional filtering.
     * 
     * @return bool True on completion.
     */
    public function blockUsersWithFewPosts(int $threshold, string $board_id, string $id, string $password, string $avoidReason, int $avoidHour, ?closure $callback = null): bool
    {
        $dcinside = new self();
        $isLogged = false;

        $documents = $dcinside->getDocuments($board_id);
        foreach ($documents as $document) {
            if (empty($document['uid'])) {
                continue;
            }

            $count = $dcinside->getWriteDocumentCount($document['uid']);

            if (!$isLogged) {
                $isLogged = $dcinside->login($id, $password);
            }

            $isClosurePassed = true;
            if ($callback != null) {
                $isClosurePassed = $callback($document);
            }

            if ($count <= $threshold || $isClosurePassed) {
                $dcinside->updateAvoidList($board_id, $document['no'], del_chk: 1, avoid_reason_txt: $avoidReason, avoid_hour: $avoidHour);
            }
        }

        return true;
    }

    /**
     * Retrieves documents from a gallery.
     * Fetches a list of posts from the specified gallery.
     *
     * @param string $id The gallery ID.
     * @return ArrayObject The list of documents.
     */
    public function getDocuments(string $id): ArrayObject
    {
        $requestURL = sprintf("https://gall.dcinside.com/mini/board/lists/?id=%s", $id);

        $documents = new ArrayObject();
        $cURL = new ClientURL($requestURL);
        $cURL->option->setURL($requestURL)
            ->setGetMethod(true)
            ->setReturnTransfer(true)
            ->setAutoReferer(true)
            ->setCAInformation($this->pemFile)
            ->setSSLVerifyPeer(true)
            ->setSSLVerifyHost(true)
            ->setAcceptEncoding('')
            ->setHeader('User-Agent', $this->userAgent)
            ->setHeader('Accept', 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7')
            ->setHeader('Accept-Encoding', 'gzip, deflate, br, zstd')
            ->setHeader('Accept-Language', 'ko,zh;q=0.9,en-US;q=0.8,en;q=0.7')
            ->setHeader('connection', 'keep-alive')
            ->setHeader('Referer', 'https://sign.dcinside.com/')
            ->setCookieJar($this->cookieFile)
            ->setCookieFile($this->cookieFile)
            ->setReturnHeader(true);

        $response = $cURL->execute();

        if ($response instanceof StringObject) {
            $matches = [];
            $regex = '#<tr\s+[^>]*data-no="(\d+)"[^>]*>.*?<td\s+class="gall_tit ub-word">.*?<a\s+[^>]*href="/mini/board/view/\?id='
                . preg_quote($id, '#') . '&no=\d+[^"]*"[^>]*>.*?<em[^>]*>.*?<\/em>\s*(?:<b>)?([^<]+)(?:<\/b>)?.*?<\/a>.*?<td\s+class="gall_writer ub-writer"[^>]*data-uid="([^"]*)"[^>]*>#is';
            $response->matchAll($regex, $matches);

            foreach ($matches as $match) {
                $no = $match[1];
                $title = $match[2];
                $uid = $match[3];

                $documents->add(['no' => $no, 'title' => $title, 'uid' => $uid]);
            }
        }

        $cURL->close();

        return $documents;
    }

}