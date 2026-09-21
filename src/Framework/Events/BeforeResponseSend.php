<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Framework\Event;

use Clover\Framework\Component\Response;

/**
 * Before Response Send Event
 */
class BeforeResponseSend
{
    public $response;

    public function __construct(Response $response)
    {
        $this->response = $response;
    }
}
