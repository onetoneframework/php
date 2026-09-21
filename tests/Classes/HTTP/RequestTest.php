<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\HTTP;

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use Clover\Classes\HTTP\Request;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{

    public function setUp(): void
    {
        $_SERVER['HTTP_ACCEPT_ENCODING'] = 'deflate, gzip;q=1.0, *;q=0.5';
    }

    public function testUserAgent(): void
    {
        $this->assertEquals('deflate, gzip;q=1.0, *;q=0.5', Request::getAcceptEncoding());

        // Linux Android
        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 (Linux; U; Android 4.0.3; Device Name) AppleWebkit/534.30 (KHTML, like Gecko) Version/4.0 Safari/534.30');
        $this->assertEquals('Linux', $parsedUserAgent['operation']);
        $this->assertEquals('Android', $parsedUserAgent['device']);
        $this->assertEquals('4.0.3', $parsedUserAgent['device_version']);

        // Linux Android Chrome
        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 (Linux; Android 4.4.4; One Build/KTU84L.H4) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/33.0.0.0 Mobile Safari/537.36');
        $this->assertEquals('4.4.4', $parsedUserAgent['device_version']);
        $this->assertEquals('Linux', $parsedUserAgent['operation']);
        $this->assertEquals('One Build/KTU84L.H4', $parsedUserAgent['device']);
        $this->assertEquals('Chrome', $parsedUserAgent['browser']);
        $this->assertEquals('33.0.0.0', $parsedUserAgent['browser_version']);

        // Linux Android Chrome
        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 (Linux; Android 5.1.1; Nexus 5 Build/LMY48B; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/43.0.2357.65 Mobile Safari/537.36');
        $this->assertEquals('Linux', $parsedUserAgent['operation']);
        $this->assertEquals('Nexus 5 Build/LMY48B', $parsedUserAgent['device']);
        $this->assertEquals('5.1.1', $parsedUserAgent['device_version']);
        $this->assertEquals('Chrome', $parsedUserAgent['browser']);

        // Linux Android Chrome
        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 (Linux; Android 5.0; Nexus 5 Build/LPX13D) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/38.0.2125.102 Mobile Safari/537.36');
        $this->assertEquals('Linux', $parsedUserAgent['operation']);
        $this->assertEquals('Nexus 5 Build/LPX13D', $parsedUserAgent['device']);
        $this->assertEquals('5.0', $parsedUserAgent['device_version']);
        $this->assertEquals('Chrome', $parsedUserAgent['browser']);
        $this->assertEquals('38.0.2125.102', $parsedUserAgent['browser_version']);

        // Windows Chrome
        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.103 Safari/537.36');
        $this->assertEquals('Windows NT 10.0', $parsedUserAgent['operation']);
        $this->assertEquals('Chrome', $parsedUserAgent['browser']);
        $this->assertEquals('51.0.2704.103', $parsedUserAgent['browser_version']);

        // X11 Chrome
        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2227.0 Safari/537.36');
        $this->assertEquals('X11', $parsedUserAgent['operation']);
        $this->assertEquals('Chrome', $parsedUserAgent['browser']);
        $this->assertEquals('41.0.2227.0', $parsedUserAgent['browser_version']);

        // Windows Edge
        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/94.0.4606.81 Safari/537.36 Edg/94.0.992.47');
        $this->assertEquals('Windows NT 10.0', $parsedUserAgent['operation']);
        $this->assertEquals('Edge', $parsedUserAgent['browser']);
        $this->assertEquals('94.0.992.47', $parsedUserAgent['browser_version']);

        // X11 Opera
        $parsedUserAgent = Request::parseUserAgent('Opera/9.80 (X11; Linux i686; Ubuntu/14.10) Presto/2.12.388 Version/12.16');
        $this->assertEquals('X11', $parsedUserAgent['operation']);
        $this->assertEquals('Opera', $parsedUserAgent['browser']);
        $this->assertEquals('12.16', $parsedUserAgent['browser_version']);

        // Linux Whale
        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 (Linux; Android 9) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/69.0.3497.128 Whale/1.0.0.0 Crosswalk/23.69.590.31 Mobile Safari/537.36 NAVER(inapp; search; 660; 10.7.2)');
        $this->assertEquals('Linux', $parsedUserAgent['operation']);
        $this->assertEquals('Whale', $parsedUserAgent['browser']);
        $this->assertEquals('9', $parsedUserAgent['device_version']);
        $this->assertEquals('1.0.0.0', $parsedUserAgent['browser_version']);

        // X11 Firefox
        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 (X11; Linux i686; rv:10.0.7) Gecko/20100101 Iceweasel/10.0.7');
        $this->assertEquals('X11', $parsedUserAgent['operation']);
        $this->assertEquals('Firefox', $parsedUserAgent['browser']);
        $this->assertEquals('10.0.7', $parsedUserAgent['browser_version']);

        // Macintosh Firefox
        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10; rv:33.0) Gecko/20100101 Firefox/33.0');
        $this->assertEquals('Macintosh', $parsedUserAgent['machine']);
        $this->assertEquals('Firefox', $parsedUserAgent['browser']);
        $this->assertEquals('33.0', $parsedUserAgent['browser_version']);
        $this->assertEquals('Intel Mac OS X 10_10', $parsedUserAgent['device']);

        // iPhone Chrome
        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 (iPhone; U; CPU iPhone OS 5_1_1 like Mac OS X; en) AppleWebKit/534.46.0 (KHTML, like Gecko) CriOS/19.0.1084.60 Mobile/9B206 Safari/7534.48.3');
        $this->assertEquals('iPhone', $parsedUserAgent['machine']);
        $this->assertEquals('iOS', $parsedUserAgent['operation']);
        $this->assertEquals('5.1.1', $parsedUserAgent['operation_version']);
        $this->assertEquals('Chrome', $parsedUserAgent['browser']);
        $this->assertEquals('19.0.1084.60', $parsedUserAgent['browser_version']);

        // iPad Safari
        $parsedUserAgent = Request::parseUserAgent('iPad: Mozilla/5.0 (iPad; CPU OS 5_1 like Mac OS X) AppleWebKit/534.46 (KHTML, like Gecko) Version/5.1 Mobile/9B176 Safari/7534.48.3');
        $this->assertEquals('iPad', $parsedUserAgent['machine']);
        $this->assertEquals('Safari', $parsedUserAgent['browser']);
        $this->assertEquals('5.1', $parsedUserAgent['browser_version']);
        $this->assertEquals('CPU OS 5_1 like Mac OS X', $parsedUserAgent['device']);

        // Macintosh Safari
        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.10 Safari/605.1.1');
        $this->assertEquals('Macintosh', $parsedUserAgent['machine']);
        $this->assertEquals('Safari', $parsedUserAgent['browser']);
        $this->assertEquals('17.10', $parsedUserAgent['browser_version']);
        $this->assertEquals('Intel Mac OS X 10_15_7', $parsedUserAgent['device']);

        // iOS iPhone Safari
        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 (iPhone; CPU iPhone OS 17_7_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.3 Mobile/15E148 Safari/604.1');
        $this->assertEquals('iPhone', $parsedUserAgent['machine']);
        $this->assertEquals('iOS', $parsedUserAgent['operation']);
        $this->assertEquals('17.7.2', $parsedUserAgent['operation_version']);
        $this->assertEquals('Safari', $parsedUserAgent['browser']);
        $this->assertEquals('18.3', $parsedUserAgent['browser_version']);

        // Linux Android SamsungBrowser
        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 (Linux; Android 13; SAMSUNG SM-G990B2) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/23.0 Chrome/115.0.0.0 Mobile Safari/537.3');
        $this->assertEquals('Linux', $parsedUserAgent['machine']);
        $this->assertEquals('Linux', $parsedUserAgent['operation']);
        $this->assertEquals('Android', $parsedUserAgent['device']);
        $this->assertEquals('13', $parsedUserAgent['device_version']);
        $this->assertEquals('SamsungBrowser', $parsedUserAgent['browser']);
        $this->assertEquals('23.0', $parsedUserAgent['browser_version']);

        // iPod touch Firefox
        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 (iPod touch; CPU iPhone OS 14_3 like Mac OS X) AppleWebKit/604.5.6 (KHTML, like Gecko) FxiOS/122.0 Mobile/15E148 Safari/605.1.15');
        $this->assertEquals('iPod touch', $parsedUserAgent['machine']);
        $this->assertEquals('iOS', $parsedUserAgent['operation']);
        $this->assertEquals('Firefox', $parsedUserAgent['browser']);
        $this->assertEquals('122.0', $parsedUserAgent['browser_version']);
        $this->assertEquals('14.3', $parsedUserAgent['operation_version']);

        // Windows Opera
        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.0.2526.111 Safari/537.36 OPR/34.0.2036.50');
        $this->assertEquals('Windows NT 10.0', $parsedUserAgent['operation']);
        $this->assertEquals('Opera', $parsedUserAgent['browser']);
        $this->assertEquals('34.0.2036.50', $parsedUserAgent['browser_version']);

        // Linux Android Edge
        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 (Linux; Android 10; ONEPLUS A6003) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.6998.135 Mobile Safari/537.36 EdgA/134.0.3124.68');
        $this->assertEquals('Linux', $parsedUserAgent['operation']);
        $this->assertEquals('Edge', $parsedUserAgent['browser']);
        $this->assertEquals('134.0.3124.68', $parsedUserAgent['browser_version']);
        $this->assertEquals('Android', $parsedUserAgent['device']);
        $this->assertEquals('10', $parsedUserAgent['device_version']);

        // Linux Android Chrome
        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 (Linux; U; Android 2.3.3; zh-tw; HTC Pyramid Build/GRI40) AppleWebKit/533.1 (KHTML, like Gecko) Version/4.0 Mobile Safari/533.1');
        $this->assertEquals('zh-tw', $parsedUserAgent['locale']);
        $this->assertEquals('Linux', $parsedUserAgent['operation']);
        $this->assertEquals('Chrome', $parsedUserAgent['browser']);
        $this->assertEquals('4.0', $parsedUserAgent['browser_version']);
        $this->assertEquals('HTC Pyramid Build/GRI40', $parsedUserAgent['device']);
        $this->assertEquals('2.3.3', $parsedUserAgent['device_version']);

        // Windows Vivaldi
        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Vivaldi/7.5.3735.58');
        $this->assertEquals('Windows NT 10.0', $parsedUserAgent['operation']);
        $this->assertEquals('Vivaldi', $parsedUserAgent['browser']);
        $this->assertEquals('7.5.3735.58', $parsedUserAgent['browser_version']);

        // Macintosh Safari
        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 (Macintosh; Intel Mac OS X 15_5) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Safari/605.1.15');
        $this->assertEquals('Macintosh', $parsedUserAgent['operation']);
        $this->assertEquals('Safari', $parsedUserAgent['browser']);
        $this->assertEquals('18.5', $parsedUserAgent['browser_version']);

        // Linux Android Brave Browser
        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 (Linux; Android 11; SM-G975F) AppleWebKit/537.36 (KHTML, like Gecko) Brave Chrome/134.0.6954.0 Mobile Safari/537.36');
        $this->assertEquals('Linux', $parsedUserAgent['operation']);
        $this->assertEquals('Brave', $parsedUserAgent['browser']);
        $this->assertEquals('134.0.6954.0', $parsedUserAgent['browser_version']);

        // Macintosh Brave Browser
        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 (Macintosh; Intel Mac OS X 5_15_0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0 Brave Browser/133.0.6943.126 Safari/537.36');
        $this->assertEquals('Macintosh', $parsedUserAgent['operation']);
        $this->assertEquals('Brave', $parsedUserAgent['browser']);
        $this->assertEquals('133.0.6943.126', $parsedUserAgent['browser_version']);

        // Windows Epic Browser
        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.2.10) Gecko/20101026 Epic/1.2 Firefox/3.6.10');
        $this->assertEquals('Windows', $parsedUserAgent['operation']);
        $this->assertEquals('Epic', $parsedUserAgent['browser']);
        $this->assertEquals('1.2', $parsedUserAgent['browser_version']);

        // X11 Haiku Midori Browser
        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 (X11; Haiku x86_64) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.4 Safari/605.1.15 Midori/6');
        $this->assertEquals('X11', $parsedUserAgent['operation']);
        $this->assertEquals('Midori', $parsedUserAgent['browser']);
        $this->assertEquals('6', $parsedUserAgent['browser_version']);

        // X64 Windows AVG Secure Browser
        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 AVG/138.0.0.0');
        $this->assertEquals('Windows NT 10.0', $parsedUserAgent['operation']);
        $this->assertEquals('AVG Secure Browser', $parsedUserAgent['browser']);
        $this->assertEquals('138.0.0.0', $parsedUserAgent['browser_version']);

        // Linux Android Kindle Browser
        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 (X11; U; Linux army71 like Android; en-us) AppleWebKit/531.2+ (KHTML; like Gecko) Version/5.0 Safari/533.2+ Kindle/3.0+');
        $this->assertEquals('X11', $parsedUserAgent['operation']);
        $this->assertEquals('Kindle Browser', $parsedUserAgent['browser']);
        $this->assertEquals('3.0+', $parsedUserAgent['browser_version']);
        $this->assertEquals('en-us', $parsedUserAgent['locale']);

        // Linux Android UCBrowser
        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 (Linux; U; Android 15; zh-CN; PJX110 Build/UKQ1.231108.001) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/123.0.6312.80 UCBrowser/17.7.8.1409 Mobile Safari/537.36');
        $this->assertEquals('Linux', $parsedUserAgent['operation']);
        $this->assertEquals('UCBrowser', $parsedUserAgent['browser']);
        $this->assertEquals('17.7.8.1409', $parsedUserAgent['browser_version']);
        $this->assertEquals('zh-CN', $parsedUserAgent['locale']);
        $this->assertEquals('PJX110 Build/UKQ1.231108.001', $parsedUserAgent['device']);

        // SMART-TV SamsungBrowser
        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 (SMART-TV; Linux; Tizen 6.5) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/5.0 Chrome/108.0.5359.1 TV Safari/537.36 [ip:2.138.174.220]');
        $this->assertEquals('Tizen', $parsedUserAgent['operation']);
        $this->assertEquals('SamsungBrowser', $parsedUserAgent['browser']);
        $this->assertEquals('5.0', $parsedUserAgent['browser_version']);

        // SAMSUNG Dolfin Browser
        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 ( SAMSUNG; SAMSUNG-GT-S8600/S8600XXKJC; U; Bada/2.0; tr-tr) AppleWebKit/534.20 (KHTML, like Gecko) Dolfin/3.0 Mobile WVGA SMM-MMS/1.2.0 OPN-BSmartphone deals');
        $this->assertEquals('SAMSUNG', $parsedUserAgent['operation']);
        $this->assertEquals('Dolfin Browser', $parsedUserAgent['browser']);
        $this->assertEquals('3.0', $parsedUserAgent['browser_version']);

        // Linux Android QQ Browser
        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 (Linux; U; Android 15; zh-cn; 25053RT47C Build/AQ3A.250107.001) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/121.0.6167.71 MQQBrowser/19.1 Mobile Safari/537.36 COVC/048301Best Android smartphones');
        $this->assertEquals('Linux', $parsedUserAgent['operation']);
        $this->assertEquals('QQ Browser', $parsedUserAgent['browser']);
        $this->assertEquals('19.1', $parsedUserAgent['browser_version']);
        $this->assertEquals('zh-cn', $parsedUserAgent['locale']);
        $this->assertEquals('25053RT47C Build/AQ3A.250107.001', $parsedUserAgent['device']);
        $this->assertEquals('15', $parsedUserAgent['device_version']);

        // Linux Android DuckDuckGo Privacy Browser
        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 (Linux; Android 9) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/138.0.7204.179 Mobile DuckDuckGo/8 Safari/537.36');
        $this->assertEquals('Linux', $parsedUserAgent['operation']);
        $this->assertEquals('DuckDuckGo Privacy Browser', $parsedUserAgent['browser']);
        $this->assertEquals('8', $parsedUserAgent['browser_version']);
        $this->assertEquals('Android', $parsedUserAgent['device']);
        $this->assertEquals('9', $parsedUserAgent['device_version']);

        // iPhone iOS Puffin
        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 (iPhone; CPU iPhone OS 7_1_2 like Mac OS X; vi-VN) AppleWebKit/537.36 (KHTML, like Gecko) Version/7.1.2 Mobile/11D257 Safari/537.36 Puffin/4.7.3IP');
        $this->assertEquals('iOS', $parsedUserAgent['operation']);
        $this->assertEquals('7.1.2', $parsedUserAgent['operation_version']);
        $this->assertEquals('Puffin Browser', $parsedUserAgent['browser']);
        $this->assertEquals('4.7.3IP', $parsedUserAgent['browser_version']);
        $this->assertEquals('iPhone', $parsedUserAgent['machine']);
        $this->assertEquals('vi-VN', $parsedUserAgent['locale']);

        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 (Linux; U; Android 4.0.3; Device Name) AppleWebkit/534.30 (KHTML, like Gecko) Version/4.0 Safari/534.30');
        $this->assertEquals('Linux', $parsedUserAgent['operation'], 'Linux Android: operation');
        $this->assertEquals('Android', $parsedUserAgent['device'], 'Linux Android: device');
        $this->assertEquals('4.0.3', $parsedUserAgent['device_version'], 'Linux Android: device_version');

        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 (Linux; Android 4.4.4; One Build/KTU84L.H4) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/33.0.0.0 Mobile Safari/537.36');
        $this->assertEquals('4.4.4', $parsedUserAgent['device_version'], 'Linux Android Chrome 1: device_version');
        $this->assertEquals('Linux', $parsedUserAgent['operation'], 'Linux Android Chrome 1: operation');
        $this->assertEquals('One Build/KTU84L.H4', $parsedUserAgent['device'], 'Linux Android Chrome 1: device');
        $this->assertEquals('Chrome', $parsedUserAgent['browser'], 'Linux Android Chrome 1: browser');
        $this->assertEquals('33.0.0.0', $parsedUserAgent['browser_version'], 'Linux Android Chrome 1: browser_version');

        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 (Linux; Android 5.1.1; Nexus 5 Build/LMY48B; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/43.0.2357.65 Mobile Safari/537.36');
        $this->assertEquals('Linux', $parsedUserAgent['operation'], 'Nexus wv: operation');
        $this->assertEquals('Nexus 5 Build/LMY48B', $parsedUserAgent['device'], 'Nexus wv: device');
        $this->assertEquals('5.1.1', $parsedUserAgent['device_version'], 'Nexus wv: device_version');
        $this->assertEquals('Chrome', $parsedUserAgent['browser'], 'Nexus wv: browser');

        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 (Linux; Android 5.0; Nexus 5 Build/LPX13D) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/38.0.2125.102 Mobile Safari/537.36');
        $this->assertEquals('Linux', $parsedUserAgent['operation'], 'Nexus direct: operation');
        $this->assertEquals('Nexus 5 Build/LPX13D', $parsedUserAgent['device'], 'Nexus direct: device');
        $this->assertEquals('5.0', $parsedUserAgent['device_version'], 'Nexus direct: device_version');
        $this->assertEquals('Chrome', $parsedUserAgent['browser'], 'Nexus direct: browser');
        $this->assertEquals('38.0.2125.102', $parsedUserAgent['browser_version'], 'Nexus direct: browser_version');

        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.103 Safari/537.36');
        $this->assertEquals('Windows NT 10.0', $parsedUserAgent['operation'], 'Win Chrome: operation');
        $this->assertEquals('Chrome', $parsedUserAgent['browser'], 'Win Chrome: browser');
        $this->assertEquals('51.0.2704.103', $parsedUserAgent['browser_version'], 'Win Chrome: version');

        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2227.0 Safari/537.36');
        $this->assertEquals('X11', $parsedUserAgent['operation'], 'X11 Chrome: operation');
        $this->assertEquals('Chrome', $parsedUserAgent['browser'], 'X11 Chrome: browser');
        $this->assertEquals('41.0.2227.0', $parsedUserAgent['browser_version'], 'X11 Chrome: version');

        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/94.0.4606.81 Safari/537.36 Edg/94.0.992.47');
        $this->assertEquals('Windows NT 10.0', $parsedUserAgent['operation'], 'Win Edge: operation');
        $this->assertEquals('Edge', $parsedUserAgent['browser'], 'Win Edge: browser');
        $this->assertEquals('94.0.992.47', $parsedUserAgent['browser_version'], 'Win Edge: version');

        $parsedUserAgent = Request::parseUserAgent('Opera/9.80 (X11; Linux i686; Ubuntu/14.10) Presto/2.12.388 Version/12.16');
        $this->assertEquals('X11', $parsedUserAgent['operation'], 'X11 Opera: operation');
        $this->assertEquals('Opera', $parsedUserAgent['browser'], 'X11 Opera: browser');
        $this->assertEquals('12.16', $parsedUserAgent['browser_version'], 'X11 Opera: version');

        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 (Linux; Android 9) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/69.0.3497.128 Whale/1.0.0.0 Crosswalk/23.69.590.31 Mobile Safari/537.36 NAVER(inapp; search; 660; 10.7.2)');
        $this->assertEquals('Linux', $parsedUserAgent['operation'], 'Whale: operation');
        $this->assertEquals('Whale', $parsedUserAgent['browser'], 'Whale: browser');
        $this->assertEquals('9', $parsedUserAgent['device_version'], 'Whale: device_version');
        $this->assertEquals('1.0.0.0', $parsedUserAgent['browser_version'], 'Whale: browser_version');

        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 (X11; Linux i686; rv:10.0.7) Gecko/20100101 Iceweasel/10.0.7');
        $this->assertEquals('X11', $parsedUserAgent['operation'], 'X11 Firefox: operation');
        $this->assertEquals('Firefox', $parsedUserAgent['browser'], 'X11 Firefox: browser');
        $this->assertEquals('10.0.7', $parsedUserAgent['browser_version'], 'X11 Firefox: version');

        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10; rv:33.0) Gecko/20100101 Firefox/33.0');
        $this->assertEquals('Macintosh', $parsedUserAgent['machine'], 'Mac Firefox: machine');
        $this->assertEquals('Firefox', $parsedUserAgent['browser'], 'Mac Firefox: browser');
        $this->assertEquals('33.0', $parsedUserAgent['browser_version'], 'Mac Firefox: version');
        $this->assertEquals('Intel Mac OS X 10_10', $parsedUserAgent['device'], 'Mac Firefox: device');

        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 (iPhone; U; CPU iPhone OS 5_1_1 like Mac OS X; en) AppleWebKit/534.46.0 (KHTML, like Gecko) CriOS/19.0.1084.60 Mobile/9B206 Safari/7534.48.3');
        $this->assertEquals('iPhone', $parsedUserAgent['machine'], 'iPhone Chrome: machine');
        $this->assertEquals('iOS', $parsedUserAgent['operation'], 'iPhone Chrome: operation');
        $this->assertEquals('5.1.1', $parsedUserAgent['operation_version'], 'iPhone Chrome: op_version');
        $this->assertEquals('Chrome', $parsedUserAgent['browser'], 'iPhone Chrome: browser');
        $this->assertEquals('19.0.1084.60', $parsedUserAgent['browser_version'], 'iPhone Chrome: version');

        $parsedUserAgent = Request::parseUserAgent('iPad: Mozilla/5.0 (iPad; CPU OS 5_1 like Mac OS X) AppleWebKit/534.46 (KHTML, like Gecko) Version/5.1 Mobile/9B176 Safari/7534.48.3');
        $this->assertEquals('iPad', $parsedUserAgent['machine'], 'iPad Safari: machine');
        $this->assertEquals('Safari', $parsedUserAgent['browser'], 'iPad Safari: browser');
        $this->assertEquals('5.1', $parsedUserAgent['browser_version'], 'iPad Safari: browser_version');
        $this->assertEquals('iOS', $parsedUserAgent['operation'], 'iPad Safari: operation');
        $this->assertEquals('5.1', $parsedUserAgent['operation_version'], 'iPad Safari: op_version');

        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.10 Safari/605.1.1');
        $this->assertEquals('Macintosh', $parsedUserAgent['machine'], 'Mac Safari: machine');
        $this->assertEquals('Safari', $parsedUserAgent['browser'], 'Mac Safari: browser');
        $this->assertEquals('17.10', $parsedUserAgent['browser_version'], 'Mac Safari: version');
        $this->assertEquals('Intel Mac OS X 10_15_7', $parsedUserAgent['device'], 'Mac Safari: device');

        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 (iPhone; CPU iPhone OS 17_7_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.3 Mobile/15E148 Safari/604.1');
        $this->assertEquals('iPhone', $parsedUserAgent['machine'], 'iPhone Safari: machine');
        $this->assertEquals('iOS', $parsedUserAgent['operation'], 'iPhone Safari: operation');
        $this->assertEquals('17.7.2', $parsedUserAgent['operation_version'], 'iPhone Safari: op_version');
        $this->assertEquals('Safari', $parsedUserAgent['browser'], 'iPhone Safari: browser');
        $this->assertEquals('18.3', $parsedUserAgent['browser_version'], 'iPhone Safari: version');

        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 (Linux; Android 13; SAMSUNG SM-G990B2) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/23.0 Chrome/115.0.0.0 Mobile Safari/537.3');
        $this->assertEquals('Linux', $parsedUserAgent['machine'], 'Samsung: machine');
        $this->assertEquals('Linux', $parsedUserAgent['operation'], 'Samsung: operation');
        $this->assertEquals('Android', $parsedUserAgent['device'], 'Samsung: device');
        $this->assertEquals('13', $parsedUserAgent['device_version'], 'Samsung: device_version');
        $this->assertEquals('SamsungBrowser', $parsedUserAgent['browser'], 'Samsung: browser');
        $this->assertEquals('23.0', $parsedUserAgent['browser_version'], 'Samsung: version');

        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 (iPod touch; CPU iPhone OS 14_3 like Mac OS X) AppleWebKit/604.5.6 (KHTML, like Gecko) FxiOS/122.0 Mobile/15E148 Safari/605.1.15');
        $this->assertEquals('iPod touch', $parsedUserAgent['machine'], 'iPod Firefox: machine');
        $this->assertEquals('iOS', $parsedUserAgent['operation'], 'iPod Firefox: operation');
        $this->assertEquals('Firefox', $parsedUserAgent['browser'], 'iPod Firefox: browser');
        $this->assertEquals('122.0', $parsedUserAgent['browser_version'], 'iPod Firefox: version');
        $this->assertEquals('14.3', $parsedUserAgent['operation_version'], 'iPod Firefox: op_version');

        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.0.2526.111 Safari/537.36 OPR/34.0.2036.50');
        $this->assertEquals('Windows NT 10.0', $parsedUserAgent['operation'], 'Win Opera: operation');
        $this->assertEquals('Opera', $parsedUserAgent['browser'], 'Win Opera: browser');
        $this->assertEquals('34.0.2036.50', $parsedUserAgent['browser_version'], 'Win Opera: version');

        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 (Linux; Android 10; ONEPLUS A6003) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.6998.135 Mobile Safari/537.36 EdgA/134.0.3124.68');
        $this->assertEquals('Linux', $parsedUserAgent['operation'], 'Android Edge: operation');
        $this->assertEquals('Edge', $parsedUserAgent['browser'], 'Android Edge: browser');
        $this->assertEquals('134.0.3124.68', $parsedUserAgent['browser_version'], 'Android Edge: version');
        $this->assertEquals('Android', $parsedUserAgent['device'], 'Android Edge: device');
        $this->assertEquals('10', $parsedUserAgent['device_version'], 'Android Edge: device_version');

        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 (Linux; U; Android 2.3.3; zh-tw; HTC Pyramid Build/GRI40) AppleWebKit/533.1 (KHTML, like Gecko) Version/4.0 Mobile Safari/533.1');
        $this->assertEquals('zh-tw', $parsedUserAgent['locale'], 'old Android: locale');
        $this->assertEquals('Linux', $parsedUserAgent['operation'], 'old Android: operation');
        $this->assertEquals('Chrome', $parsedUserAgent['browser'], 'old Android: browser');
        $this->assertEquals('4.0', $parsedUserAgent['browser_version'], 'old Android: version');
        $this->assertEquals('HTC Pyramid Build/GRI40', $parsedUserAgent['device'], 'old Android: device');
        $this->assertEquals('2.3.3', $parsedUserAgent['device_version'], 'old Android: device_version');

        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Vivaldi/7.5.3735.58');
        $this->assertEquals('Windows NT 10.0', $parsedUserAgent['operation'], 'Vivaldi: operation');
        $this->assertEquals('Vivaldi', $parsedUserAgent['browser'], 'Vivaldi: browser');
        $this->assertEquals('7.5.3735.58', $parsedUserAgent['browser_version'], 'Vivaldi: version');

        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 (Macintosh; Intel Mac OS X 15_5) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Safari/605.1.15');
        $this->assertEquals('Macintosh', $parsedUserAgent['operation'], 'Mac Safari 18: operation');
        $this->assertEquals('Safari', $parsedUserAgent['browser'], 'Mac Safari 18: browser');
        $this->assertEquals('18.5', $parsedUserAgent['browser_version'], 'Mac Safari 18: version');

        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 (Linux; Android 11; SM-G975F) AppleWebKit/537.36 (KHTML, like Gecko) Brave Chrome/134.0.6954.0 Mobile Safari/537.36');
        $this->assertEquals('Linux', $parsedUserAgent['operation'], 'Brave mobile: operation');
        $this->assertEquals('Brave', $parsedUserAgent['browser'], 'Brave mobile: browser');
        $this->assertEquals('134.0.6954.0', $parsedUserAgent['browser_version'], 'Brave mobile: version');

        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 (Macintosh; Intel Mac OS X 5_15_0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0 Brave Browser/133.0.6943.126 Safari/537.36');
        $this->assertEquals('Macintosh', $parsedUserAgent['operation'], 'Brave desktop: operation');
        $this->assertEquals('Brave', $parsedUserAgent['browser'], 'Brave desktop: browser');
        $this->assertEquals('133.0.6943.126', $parsedUserAgent['browser_version'], 'Brave desktop: version');

        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.2.10) Gecko/20101026 Epic/1.2 Firefox/3.6.10');
        $this->assertEquals('Windows', $parsedUserAgent['operation'], 'Epic: operation');
        $this->assertEquals('Epic', $parsedUserAgent['browser'], 'Epic: browser');
        $this->assertEquals('1.2', $parsedUserAgent['browser_version'], 'Epic: version');

        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 (X11; Haiku x86_64) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.4 Safari/605.1.15 Midori/6');
        $this->assertEquals('X11', $parsedUserAgent['operation'], 'Midori: operation');
        $this->assertEquals('Midori', $parsedUserAgent['browser'], 'Midori: browser');
        $this->assertEquals('6', $parsedUserAgent['browser_version'], 'Midori: version');

        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 AVG/138.0.0.0');
        $this->assertEquals('Windows NT 10.0', $parsedUserAgent['operation'], 'AVG: operation');
        $this->assertEquals('AVG Secure Browser', $parsedUserAgent['browser'], 'AVG: browser');
        $this->assertEquals('138.0.0.0', $parsedUserAgent['browser_version'], 'AVG: version');

        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 (X11; U; Linux army71 like Android; en-us) AppleWebKit/531.2+ (KHTML; like Gecko) Version/5.0 Safari/533.2+ Kindle/3.0+');
        $this->assertEquals('X11', $parsedUserAgent['operation'], 'Kindle: operation');
        $this->assertEquals('Kindle Browser', $parsedUserAgent['browser'], 'Kindle: browser');
        $this->assertEquals('3.0+', $parsedUserAgent['browser_version'], 'Kindle: version');
        $this->assertEquals('en-us', $parsedUserAgent['locale'], 'Kindle: locale');

        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 (Linux; U; Android 15; zh-CN; PJX110 Build/UKQ1.231108.001) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/123.0.6312.80 UCBrowser/17.7.8.1409 Mobile Safari/537.36');
        $this->assertEquals('Linux', $parsedUserAgent['operation'], 'UCBrowser: operation');
        $this->assertEquals('UCBrowser', $parsedUserAgent['browser'], 'UCBrowser: browser');
        $this->assertEquals('17.7.8.1409', $parsedUserAgent['browser_version'], 'UCBrowser: version');
        $this->assertEquals('zh-CN', $parsedUserAgent['locale'], 'UCBrowser: locale');
        $this->assertEquals('PJX110 Build/UKQ1.231108.001', $parsedUserAgent['device'], 'UCBrowser: device');

        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 (SMART-TV; Linux; Tizen 6.5) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/5.0 Chrome/108.0.5359.1 TV Safari/537.36 [ip:2.138.174.220]');
        $this->assertEquals('Tizen', $parsedUserAgent['operation'], 'Tizen: operation');
        $this->assertEquals('SamsungBrowser', $parsedUserAgent['browser'], 'SMART-TV: browser');
        $this->assertEquals('5.0', $parsedUserAgent['browser_version'], 'SMART-TV: version');

        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 ( SAMSUNG; SAMSUNG-GT-S8600/S8600XXKJC; U; Bada/2.0; tr-tr) AppleWebKit/534.20 (KHTML, like Gecko) Dolfin/3.0 Mobile WVGA SMM-MMS/1.2.0 OPN-BSmartphone deals');
        $this->assertEquals('SAMSUNG', $parsedUserAgent['operation'], 'Dolfin: operation');
        $this->assertEquals('Dolfin Browser', $parsedUserAgent['browser'], 'Dolfin: browser');
        $this->assertEquals('3.0', $parsedUserAgent['browser_version'], 'Dolfin: version');

        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 (Linux; U; Android 15; zh-cn; 25053RT47C Build/AQ3A.250107.001) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/121.0.6167.71 MQQBrowser/19.1 Mobile Safari/537.36 COVC/048301Best Android smartphones');
        $this->assertEquals('Linux', $parsedUserAgent['operation'], 'QQ: operation');
        $this->assertEquals('QQ Browser', $parsedUserAgent['browser'], 'QQ: browser');
        $this->assertEquals('19.1', $parsedUserAgent['browser_version'], 'QQ: version');
        $this->assertEquals('zh-cn', $parsedUserAgent['locale'], 'QQ: locale');
        $this->assertEquals('25053RT47C Build/AQ3A.250107.001', $parsedUserAgent['device'], 'QQ: device');
        $this->assertEquals('15', $parsedUserAgent['device_version'], 'QQ: device_version');

        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 (Linux; Android 9) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/138.0.7204.179 Mobile DuckDuckGo/8 Safari/537.36');
        $this->assertEquals('Linux', $parsedUserAgent['operation'], 'DDG: operation');
        $this->assertEquals('DuckDuckGo Privacy Browser', $parsedUserAgent['browser'], 'DDG: browser');
        $this->assertEquals('8', $parsedUserAgent['browser_version'], 'DDG: version');
        $this->assertEquals('Android', $parsedUserAgent['device'], 'DDG: device');
        $this->assertEquals('9', $parsedUserAgent['device_version'], 'DDG: device_version');

        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 (iPhone; CPU iPhone OS 7_1_2 like Mac OS X; vi-VN) AppleWebKit/537.36 (KHTML, like Gecko) Version/7.1.2 Mobile/11D257 Safari/537.36 Puffin/4.7.3IP');
        $this->assertEquals('iOS', $parsedUserAgent['operation'], 'Puffin: operation');
        $this->assertEquals('7.1.2', $parsedUserAgent['operation_version'], 'Puffin: op_version');
        $this->assertEquals('Puffin Browser', $parsedUserAgent['browser'], 'Puffin: browser');
        $this->assertEquals('4.7.3IP', $parsedUserAgent['browser_version'], 'Puffin: version');
        $this->assertEquals('iPhone', $parsedUserAgent['machine'], 'Puffin: machine');
        $this->assertEquals('vi-VN', $parsedUserAgent['locale'], 'Puffin: locale');

        // Bots
        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)');
        $this->assertEquals(true, $parsedUserAgent['is_bot'], 'Googlebot: is_bot');
        $this->assertEquals('Googlebot', $parsedUserAgent['bot_name'], 'Googlebot: bot_name');
        $this->assertEquals('2.1', $parsedUserAgent['bot_version'], 'Googlebot: version');
        $this->assertEquals('bot', $parsedUserAgent['device_type'], 'Googlebot: device_type');

        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)');
        $this->assertEquals(true, $parsedUserAgent['is_bot'], 'bingbot: is_bot');
        $this->assertEquals('Bingbot', $parsedUserAgent['bot_name'], 'bingbot: name');

        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; GPTBot/1.2; +https://openai.com/gptbot)');
        $this->assertEquals(true, $parsedUserAgent['is_bot'], 'GPTBot: is_bot');
        $this->assertEquals('GPTBot', $parsedUserAgent['bot_name'], 'GPTBot: name');
        $this->assertEquals('1.2', $parsedUserAgent['bot_version'], 'GPTBot: version');

        $parsedUserAgent = Request::parseUserAgent('ClaudeBot/1.0; +https://www.anthropic.com');
        $this->assertEquals(true, $parsedUserAgent['is_bot'], 'ClaudeBot: is_bot');
        $this->assertEquals('ClaudeBot', $parsedUserAgent['bot_name'], 'ClaudeBot: name');

        $parsedUserAgent = Request::parseUserAgent('facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)');
        $this->assertEquals(true, $parsedUserAgent['is_bot'], 'Facebook: is_bot');
        $this->assertEquals('Facebook', $parsedUserAgent['bot_name'], 'Facebook: name');

        $parsedUserAgent = Request::parseUserAgent('python-requests/2.31.0');
        $this->assertEquals(true, $parsedUserAgent['is_bot'], 'python-requests: is_bot');
        $this->assertEquals('Python Requests', $parsedUserAgent['bot_name'], 'python-requests: name');

        $parsedUserAgent = Request::parseUserAgent('curl/8.1.2');
        $this->assertEquals(true, $parsedUserAgent['is_bot'], 'curl: is_bot');
        $this->assertEquals('curl', $parsedUserAgent['bot_name'], 'curl: name');
        $this->assertEquals('8.1.2', $parsedUserAgent['bot_version'], 'curl: version');

        $parsedUserAgent = Request::parseUserAgent('Wget/1.21.4');
        $this->assertEquals(true, $parsedUserAgent['is_bot'], 'wget: is_bot');
        $this->assertEquals('Wget', $parsedUserAgent['bot_name'], 'wget: name');

        $parsedUserAgent = Request::parseUserAgent('Twitterbot/1.0');
        $this->assertEquals(true, $parsedUserAgent['is_bot'], 'Twitterbot: is_bot');
        $this->assertEquals('Twitterbot', $parsedUserAgent['bot_name'], 'Twitterbot: name');

        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 (compatible; YandexBot/3.0; +http://yandex.com/bots)');
        $this->assertEquals(true, $parsedUserAgent['is_bot'], 'YandexBot: is_bot');
        $this->assertEquals('YandexBot', $parsedUserAgent['bot_name'], 'YandexBot: name');

        $parsedUserAgent = Request::parseUserAgent('WhatsApp/2.23.20.78 A');
        $this->assertEquals(true, $parsedUserAgent['is_bot'], 'WhatsApp: is_bot');
        $this->assertEquals('WhatsApp', $parsedUserAgent['bot_name'], 'WhatsApp: name');

        $parsedUserAgent = Request::parseUserAgent('Discordbot/2.0');
        $this->assertEquals(true, $parsedUserAgent['is_bot'], 'Discordbot: is_bot');
        $this->assertEquals('Discordbot', $parsedUserAgent['bot_name'], 'Discordbot: name');

        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 (compatible; Bytespider; spider-feedback@bytedance.com)');
        $this->assertEquals(true, $parsedUserAgent['is_bot'], 'Bytespider: is_bot');
        $this->assertEquals('Bytespider', $parsedUserAgent['bot_name'], 'Bytespider: name');

        // Game Consoles
        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 (Nintendo Switch; WifiWebAuthApplet) AppleWebKit/606.4 (KHTML, like Gecko) NF/6.0.1.15.4 NintendoBrowser/5.1.0.20393');
        $this->assertEquals('Nintendo Switch', $parsedUserAgent['machine'], 'Switch: machine');
        $this->assertEquals('Nintendo', $parsedUserAgent['operation'], 'Switch: operation');
        $this->assertEquals('console', $parsedUserAgent['device_type'], 'Switch: device_type');
        $this->assertEquals('NintendoBrowser', $parsedUserAgent['browser'], 'Switch: browser');
        $this->assertEquals('5.1.0.20393', $parsedUserAgent['browser_version'], 'Switch: browser_version');

        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 (Nintendo WiiU) AppleWebKit/536.30 (KHTML, like Gecko) NX/3.0.4.2.13 NintendoBrowser/4.3.2.11274.US');
        $this->assertEquals('Nintendo Wii U', $parsedUserAgent['machine'], 'WiiU: machine');
        $this->assertEquals('Nintendo', $parsedUserAgent['operation'], 'WiiU: operation');
        $this->assertEquals('console', $parsedUserAgent['device_type'], 'WiiU: device_type');
        $this->assertEquals('NintendoBrowser', $parsedUserAgent['browser'], 'WiiU: browser');

        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 (New Nintendo 3DS like iPhone) AppleWebKit/536.30 (KHTML, like Gecko) NX/3.0.0.5.22 Mobile NintendoBrowser/1.11.10161 NX');
        $this->assertEquals('Nintendo 3DS', $parsedUserAgent['machine'], '3DS: machine');
        $this->assertEquals('Nintendo', $parsedUserAgent['operation'], '3DS: operation');
        $this->assertEquals('console', $parsedUserAgent['device_type'], '3DS: device_type');

        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 (PlayStation 4 3.11) AppleWebKit/537.73 (KHTML, like Gecko)');
        $this->assertEquals('PlayStation 4', $parsedUserAgent['machine'], 'PS4: machine');
        $this->assertEquals('PlayStation', $parsedUserAgent['operation'], 'PS4: operation');
        $this->assertEquals('3.11', $parsedUserAgent['operation_version'], 'PS4: op_version');
        $this->assertEquals('console', $parsedUserAgent['device_type'], 'PS4: device_type');

        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 (PlayStation; PlayStation 5/2.26) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0 Safari/605.1.15');
        $this->assertEquals('PlayStation 5', $parsedUserAgent['machine'], 'PS5: machine');
        $this->assertEquals('PlayStation', $parsedUserAgent['operation'], 'PS5: operation');
        $this->assertEquals('console', $parsedUserAgent['device_type'], 'PS5: device_type');

        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 (PlayStation Vita 3.73) AppleWebKit/537.73 (KHTML, like Gecko) Silk/3.2');
        $this->assertEquals('PlayStation Vita', $parsedUserAgent['machine'], 'PSVita: machine');
        $this->assertEquals('PlayStation', $parsedUserAgent['operation'], 'PSVita: operation');
        $this->assertEquals('3.73', $parsedUserAgent['operation_version'], 'PSVita: op_version');
        $this->assertEquals('console', $parsedUserAgent['device_type'], 'PSVita: device_type');

        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64; Xbox; Xbox One) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/48.0.2564.82 Safari/537.36 Edge/20.02');
        $this->assertEquals('Xbox One', $parsedUserAgent['machine'], 'Xbox One: machine');
        $this->assertEquals('Xbox', $parsedUserAgent['operation'], 'Xbox One: operation');
        $this->assertEquals('console', $parsedUserAgent['device_type'], 'Xbox One: device_type');

        // TV
        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 (Web0S; Linux/SmartTV) AppleWebKit/537.36 (KHTML, like Gecko) QtWebEngine/5.2.1 Chrome/53.0.2785.143 Safari/537.36 WebAppManager');
        $this->assertEquals('LG TV', $parsedUserAgent['machine'], 'LG TV: machine');
        $this->assertEquals('webOS', $parsedUserAgent['operation'], 'LG TV: operation');
        $this->assertEquals('tv', $parsedUserAgent['device_type'], 'LG TV: device_type');

        // ChromeOS
        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 (X11; CrOS x86_64 15236.80.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.5414.125 Safari/537.36');
        $this->assertEquals('ChromeOS', $parsedUserAgent['operation'], 'ChromeOS: operation');
        $this->assertEquals('15236.80.0', $parsedUserAgent['operation_version'], 'ChromeOS: op_version');
        $this->assertEquals('desktop', $parsedUserAgent['device_type'], 'ChromeOS: device_type');

        // IE11
        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 (Windows NT 10.0; WOW64; Trident/7.0; rv:11.0) like Gecko');
        $this->assertEquals('Internet Explorer', $parsedUserAgent['browser'], 'IE11: browser');
        $this->assertEquals('11.0', $parsedUserAgent['browser_version'], 'IE11: version');

        // IE10
        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; WOW64; Trident/6.0)');
        $this->assertEquals('Internet Explorer', $parsedUserAgent['browser'], 'IE10: browser');
        $this->assertEquals('10.0', $parsedUserAgent['browser_version'], 'IE10: version');

        // Device types
        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 (Linux; Android 11; SM-G975F) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.6954.0 Mobile Safari/537.36');
        $this->assertEquals('mobile', $parsedUserAgent['device_type'], 'Android mobile: device_type');

        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 (iPad; CPU OS 16_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.0 Mobile/15E148 Safari/604.1');
        $this->assertEquals('tablet', $parsedUserAgent['device_type'], 'iPad: device_type');

        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
        $this->assertEquals('desktop', $parsedUserAgent['device_type'], 'Desktop Chrome: device_type');

        // Engine detection
        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
        $this->assertEquals('WebKit', $parsedUserAgent['engine'], 'Chrome: engine');
        $this->assertEquals('537.36', $parsedUserAgent['engine_version'], 'Chrome: engine_version');

        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:121.0) Gecko/20100101 Firefox/121.0');
        $this->assertEquals('Gecko', $parsedUserAgent['engine'], 'Firefox: engine');
        $this->assertEquals('20100101', $parsedUserAgent['engine_version'], 'Firefox: engine_version');

        // Not a bot
        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
        $this->assertEquals(false, $parsedUserAgent['is_bot'], 'Chrome not bot: is_bot');

        // HarmonyOS
        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 (Linux; U; HarmonyOS 4.0; HUAWEI Mate60) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/99.0.4844.88 Mobile Safari/537.36');
        $this->assertEquals('HarmonyOS', $parsedUserAgent['operation'], 'HarmonyOS: operation');
        $this->assertEquals('mobile', $parsedUserAgent['device_type'], 'HarmonyOS: device_type');

        // FreeBSD
        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 (X11; FreeBSD amd64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.5414.87 Safari/537.36');
        $this->assertEquals('FreeBSD', $parsedUserAgent['operation'], 'FreeBSD: operation');
        $this->assertEquals('desktop', $parsedUserAgent['device_type'], 'FreeBSD: device_type');

        // Yandex Browser
        $parsedUserAgent = Request::parseUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 YaBrowser/24.1.0.0 Safari/537.36');
        $this->assertEquals('Yandex Browser', $parsedUserAgent['browser'], 'Yandex: browser');
        $this->assertEquals('24.1.0.0', $parsedUserAgent['browser_version'], 'Yandex: version');
    }
}
