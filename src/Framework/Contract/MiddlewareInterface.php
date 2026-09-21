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
 * Interface MiddlewareInterface
 * @package Clover\Framework\Contract
 */
interface MiddlewareInterface
{
    /**
     * Process an incoming server request and return a response, optionally delegating to the next middleware component to create the response.
     * 
     * @param Request $request
     * @param RequestHandlerInterface $handler
     * @return Response
     */
    public function process(Request $request, RequestHandlerInterface $handler): Response;
}
