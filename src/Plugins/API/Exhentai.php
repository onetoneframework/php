<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Plugin;

class Exhentai
{

    public function getExhen()
    {
        $result = [];

        $page = $this->getPage();

        sleep(5);

        $i = strpos($page, '<table class="itg">');
        $z = strpos($page, "</table>", $i);
        $table_manga = substr($page, $i, $z - $i);
        $arr_manga = explode("gtr", $table_manga);

        foreach ($arr_manga as $key => $val) {
            $info = [];

            $k = strpos($val, "it5");
            if ($k === 0) {
                continue;
            }

            $k = strpos($val, "href=", $k) + 6;
            $z = strpos($val, '"', $k);
            $url = substr($val, $k, $z - $k);

            $k = strpos($url, "/g/") + 3;
            $z = strpos($url, "/", $k);
            $url_params = substr($url, $k, $z - $k);

            $download_page = $this->getExhenView($url);

            sleep(3);

            $k = strpos($download_page, '<h1 id="gn">') + strlen('<h1 id="gn">');
            $z = strpos($download_page, "</h1>", $k);
            $f_name = substr($download_page, $k, $z - $k);

            $info = [
                'url' => $url,
                'name' => $f_name,
                'parameter' => $url_params
            ];

            $k = strpos($download_page, '<div id="gdt">') + strlen('<div id="gdt">');
            $fdown_url = substr($download_page, $k);
            $arr_fdown = explode('"gdtm"', $fdown_url);

            foreach ($arr_fdown as $key2 => $val2) {
                $k = strpos($val2, "a href=") + 8;
                $z = strpos($val2, '"', $k);
                $image_url = substr($val2, $k, $z - $k);

                if (!$image_url) {
                    continue;
                }

                $download_page = $this->getDownloadPage($image_url);
                if (strpos($download_page, "Download original") !== false) {
                    sleep(3);

                    $k = strpos($download_page, '<div id="i7"') + strlen('<div id="i7"');
                    $k = strpos($download_page, '<a href="', $k) + 9;
                    $z = strpos($download_page, '">', $k);
                    $down_url = htmlspecialchars_decode(substr($download_page, $k, $z - $k));

                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $down_url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_HEADER, 1);
                    curl_setopt($ch, CURLOPT_VERBOSE, 1);
                    curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                        'Host: exhentai.org',
                        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                        'Accept-Encoding: deflate, lzma, sdch, br',
                        'Accept-Language: ko-KR,ko;q=0.8,en-US;q=0.6,en;q=0.4',
                        'User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.87 Safari/537.36 OPR/42.0.2393.137',
                        "Cookie: event=1; ipb_member_id=1594532; ipb_pass_hash=53616501265fc89d1524ae57e38cbc32; igneous=2a926afa2; s=2dd899946; lv=1484846638-1484986681"
                    ));
                    $download_page = curl_exec($ch);

                    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
                    $header = substr($download_page, 0, $header_size);
                    curl_close($ch);

                    $k = strpos($header, 'Location: ') + strlen('Location: ');
                    $z = strpos($header, 'Vary', $k);
                    $dl_url = htmlspecialchars_decode(substr($header, $k, $z - $k));

                    $upload_file = $url_params . "_" . $p++ . "." . 'jpg';

                    $ext = str_replace('.', '', strrchr($upload_file, '.'));

                    sleep(3);

                    $path_file = md5($upload_file);

                    $info['download_name'] = $upload_file;
                    $info['download_url'] = $dl_url;
                } else {
                    $k = strpos($download_page, '<img id="img"') + strlen('<img id="img"');
                    $k = strpos($download_page, "src=", $k) + 5;
                    $z = strpos($download_page, '"', $k);
                    $image_url = substr($download_page, $k, $z - $k);
                    $ext = str_replace('.', '', strrchr($image_url, '.'));
                    $upload_file = $url_params . "_" . $p++ . "." . $ext;

                    sleep(3);

                    $path_file = md5($upload_file);


                    $info['download_name'] = $upload_file;
                    $info['download_url'] = $image_url;
                }
            }

            $result[] = $info;
        }

        return $result;
    }

    public function getDownloadPage($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Host: exhentai.org',
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Encoding: deflate, lzma, sdch, br',
            'Accept-Language: ko-KR,ko;q=0.8,en-US;q=0.6,en;q=0.4',
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.87 Safari/537.36 OPR/42.0.2393.137',
            "Cookie: event=1; ipb_member_id=1594532; ipb_pass_hash=53616501265fc89d1524ae57e38cbc32; igneous=2a926afa2; s=2dd899946; lv=1484846638-1484986681"
        ));
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }

    public function getExhenView($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Host: exhentai.org',
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Encoding: deflate, lzma, sdch, br',
            'Accept-Language: ko-KR,ko;q=0.8,en-US;q=0.6,en;q=0.4',
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.87 Safari/537.36 OPR/42.0.2393.137',
            "Cookie: event=1; ipb_member_id=1594532; ipb_pass_hash=53616501265fc89d1524ae57e38cbc32; igneous=2a926afa2; s=2dd899946; lv=1484846638-1484986681"
        ));
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }

    public function getPage()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://exhentai.org/?f_doujinshi=1&f_manga=1&f_artistcg=1&f_gamecg=1&f_western=1&f_non-h=1&f_imageset=1&f_cosplay=1&f_asianporn=1&f_misc=1&f_search=korea&f_apply=Apply+Filter");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Host: exhentai.org',
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Encoding: deflate, lzma, sdch, br',
            'Accept-Language: ko-KR,ko;q=0.8,en-US;q=0.6,en;q=0.4',
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.87 Safari/537.36 OPR/42.0.2393.137',
            "Cookie: event=1; ipb_member_id=1594532; ipb_pass_hash=53616501265fc89d1524ae57e38cbc32; igneous=2a926afa2; s=2dd899946; lv=1484846638-1484986681"
        ));
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }
}