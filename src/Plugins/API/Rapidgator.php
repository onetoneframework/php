<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use Clover\Classes\ClientURL as ClientURL;

class Rapidgator
{

    public function loginInformation(string $token): mixed
    {
        $query = http_build_query(array(
            'token' => $token
        ));

        $cURL = new ClientURL();
        $cURL->option->setURL("https://rapidgator.net/api/v2/user/info")
            ->setReturnTransfer(true)
            ->setPostMethod()
            ->setPostField($query);

        $result = $cURL->execute();

        return $result;
    }

    public function fileInformation(string $file_id, string $token): mixed
    {
        $query = http_build_query(array(
            'file_id' => $file_id,
            'token' => $token
        ));

        $cURL = new ClientURL();
        $cURL->option->setURL("https://rapidgator.net/api/v2/file/info")
            ->setReturnTransfer(true)
            ->setPostMethod()
            ->setPostField($query);

        $result = $cURL->execute();

        return $result;
    }

    public function download(string $file_id, string $token): mixed
    {
        $query = http_build_query(array(
            'file_id' => $file_id,
            'token' => $token
        ));

        $cURL = new ClientURL();
        $cURL->option->setURL("https://rapidgator.net/api/v2/file/download")
            ->setReturnTransfer(true)
            ->setPostMethod()
            ->setPostField($query);

        $result = $cURL->execute();

        return $result;
    }

    public function login(string $email, string $password): mixed
    {
        $query = http_build_query([
            'login' => $email,
            'password' => $password
        ]);

        $cURL = new ClientURL();
        $cURL->option->setURL("https://rapidgator.net/api/v2/user/login")
            ->setReturnTransfer(true)
            ->setPostMethod()
            ->setPostField($query);

        $result = $cURL->execute();

        return $result;
    }
}
