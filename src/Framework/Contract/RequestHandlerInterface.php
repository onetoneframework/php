<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Framework\Contract;

use Clover\Framework\Component\Request;
use Clover\Framework\Component\Response;

/**
 * Interface RequestHandlerInterface
 * @package Clover\Framework\Contract
 */
interface RequestHandlerInterface
{
    /**
     * Handle the request and return a response.
     * 
     * @param Request $request
     * @return Response
     */
    public function handle(Request $request): Response;
}
