<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use Clover\Classes\ClientURL as ClientURL;

class OpenAPI
{

    private $serviceKey;

    public function test($token)
    {
        $query = http_build_query(array(
            'token' => $token
        ));

        $cURL = new ClientURL();
        $cURL->option->setURL("https://api.odcloud.kr/api/nts-businessman/v1/status?serviceKey={$this->serviceKey}")
            ->setReturnTransfer(true)
            ->setPostMethod()
            ->setContentTypeApplicationJson()
            ->setPostField($query);

        $result = $cURL->execute();

        return $result;
    }

}
